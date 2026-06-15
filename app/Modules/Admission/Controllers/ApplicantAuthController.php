<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admission\Models\Applicant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class ApplicantAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'father_name' => ['required', 'string', 'max:150'],
            'cnic_bform' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        return DB::transaction(function () use ($validated) {
            $tenantId = $this->resolveTenantId();

            $existingApplicant = Applicant::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($query) use ($validated) {
                    $query->where('email', $validated['email'])
                        ->orWhere('cnic_bform', $validated['cnic_bform']);
                })
                ->first();

            if ($existingApplicant) {
                throw ValidationException::withMessages([
                    'email' => ['Applicant already exists with this email or CNIC/B-Form.'],
                ]);
            }

            $existingUser = User::query()
                ->where('email', $validated['email'])
                ->first();

            if ($existingUser) {
                throw ValidationException::withMessages([
                    'email' => ['User already exists with this email.'],
                ]);
            }

            $fullName = trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? ''));

            $userPayload = [
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ];

            if (Schema::hasColumn('users', 'tenant_id')) {
                $userPayload['tenant_id'] = $tenantId;
            }

            if (Schema::hasColumn('users', 'status')) {
                $userPayload['status'] = 'active';
            }

            if (Schema::hasColumn('users', 'user_type')) {
                /*
                | Do not set applicant here because current users.user_type
                | column does not support applicant value.
                | Applicant identity is controlled by Applicant role.
                */
            }

            $user = User::create($userPayload);
            /*$applicant->update([
                'user_id' => $user->id,
            ]);*/
            $role = Role::where('name', 'Applicant')
                ->where('guard_name', 'web')
                ->first();

            if ($role) {
                $user->assignRole($role);
            }

            $applicantNo = $this->generateApplicantNo($tenantId);

            $applicantPayload = [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'applicant_no' => $applicantNo,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? null,
                'full_name' => $fullName,
                'father_name' => $validated['father_name'],
                'cnic_bform' => $validated['cnic_bform'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'profile_status_code' => 'draft',
                'applicant_status_code' => 'active',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $insertPayload = [];

            foreach ($applicantPayload as $column => $value) {
                if (Schema::hasColumn('applicants', $column)) {
                    $insertPayload[$column] = $value;
                }
            }

            $applicantId = DB::table('applicants')->insertGetId($insertPayload);

            $applicant = Applicant::query()->findOrFail($applicantId);

            $token = $user->createToken('applicant-token')->plainTextToken;

            return ApiResponse::success([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'Applicant',
                    'tenant_id' => $tenantId,
                ],
                'applicant' => [
                    'id' => $applicant->id,
                    'applicant_no' => $applicant->applicant_no,
                    'full_name' => $applicant->full_name,
                    'profile_status_code' => $applicant->profile_status_code,
                    'applicant_status_code' => $applicant->applicant_status_code,
                ],
            ], 'Applicant registered successfully.');
        });
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $tenantId = $this->resolveTenantId();

        $user = User::query()
            ->where('email', $validated['email'])
            ->when(Schema::hasColumn('users', 'tenant_id'), function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid applicant login credentials.'],
            ]);
        }

        if (!$user->hasRole('Applicant')) {
            throw ValidationException::withMessages([
                'email' => ['This login is not an applicant account.'],
            ]);
        }

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->first();

        if (!$applicant) {
            throw ValidationException::withMessages([
                'email' => ['Applicant profile was not found for this account.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('applicant-token')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'Applicant',
                'tenant_id' => $tenantId,
            ],
            'applicant' => [
                'id' => $applicant->id,
                'applicant_no' => $applicant->applicant_no,
                'full_name' => $applicant->full_name,
                'profile_status_code' => $applicant->profile_status_code,
                'applicant_status_code' => $applicant->applicant_status_code,
            ],
        ], 'Applicant logged in successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('Applicant')) {
            abort(403, 'Applicant account is required.');
        }

        $tenantId = $user->tenant_id;

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'Applicant',
                'tenant_id' => $tenantId,
            ],
            'applicant' => [
                'id' => $applicant->id,
                'applicant_no' => $applicant->applicant_no,
                'full_name' => $applicant->full_name,
                'profile_status_code' => $applicant->profile_status_code,
                'applicant_status_code' => $applicant->applicant_status_code,
            ],
        ], 'Applicant profile fetched successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Applicant logged out successfully.');
    }

    private function resolveTenantId(): int
{
    if (auth()->check() && auth()->user()?->tenant_id) {
        return (int) auth()->user()->tenant_id;
    }

    $tenantCode = request()->header('X-Tenant-Code')
        ?: request()->query('tenant')
        ?: request()->input('tenant_code');

    if ($tenantCode) {
        $tenant = DB::table('tenants')
            ->where(function ($query) use ($tenantCode) {
                if (Schema::hasColumn('tenants', 'code')) {
                    $query->orWhere('code', $tenantCode);
                }

                if (Schema::hasColumn('tenants', 'slug')) {
                    $query->orWhere('slug', $tenantCode);
                }

                if (Schema::hasColumn('tenants', 'subdomain')) {
                    $query->orWhere('subdomain', $tenantCode);
                }
            })
            ->where('status', 'active')
            ->first();

        if ($tenant) {
            return (int) $tenant->id;
        }

        abort(422, 'Invalid tenant code.');
    }

    if (app()->environment(['local', 'development'])) {
        $tenant = DB::table('tenants')
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if ($tenant) {
            return (int) $tenant->id;
        }
    }

    abort(422, 'Tenant could not be resolved for applicant registration.');
}

    private function generateApplicantNo(int $tenantId): string
    {
        $count = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->count() + 1;

        return 'APP-' . now()->format('Y') . '-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}