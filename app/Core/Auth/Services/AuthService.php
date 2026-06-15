<?php

namespace App\Core\Auth\Services;

use App\Core\Audit\Models\LoginLog;
use App\Core\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(array $data, Request $request): array
    {
        $email = $data['email'];
        $password = $data['password'];
        $tenantCode = $data['tenant_code'] ?? null;
        $deviceName = $data['device_name'] ?? 'web';

        $tenant = null;

        $query = User::query()
            ->with('tenant')
            ->where('email', $email);

        $matchingUsers = User::query()
            ->with('tenant')
            ->where('email', $email)
            ->get();

        if ($tenantCode) {
            $tenant = Tenant::query()
                ->where('code', $tenantCode)
                ->first();

            if (!$tenant) {
                $this->recordFailedLogin($request, $email, reason: 'Invalid tenant code.');

                throw ValidationException::withMessages([
                    'tenant_code' => ['Invalid tenant code.'],
                ]);
            }

            $query->where('tenant_id', $tenant->id);
        } else {
            if ($matchingUsers->count() === 1) {
                $onlyUser = $matchingUsers->first();

                $query->where('id', $onlyUser->id);
            } elseif ($matchingUsers->count() > 1) {
                throw ValidationException::withMessages([
                    'tenant_code' => ['This email exists in multiple tenants. Please select tenant.'],
                ]);
            } else {
                $query->whereNull('tenant_id');
            }
        }

        $user = $query->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->recordFailedLogin(
                request: $request,
                email: $email,
                tenantId: $tenant?->id,
                reason: 'Invalid email or password.'
            );

            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        if (!$user->isActive()) {
            $this->recordFailedLogin(
                request: $request,
                email: $email,
                tenantId: $user->tenant_id,
                userId: $user->id,
                reason: 'User account is not active.'
            );

            throw ValidationException::withMessages([
                'email' => ['Your account is not active.'],
            ]);
        }

        if (!$user->isSuperAdmin()) {
            if (!$user->tenant) {
                $this->recordFailedLogin(
                    request: $request,
                    email: $email,
                    userId: $user->id,
                    reason: 'Tenant not found.'
                );

                throw ValidationException::withMessages([
                    'tenant_code' => ['Tenant not found for this user.'],
                ]);
            }

            if (!$user->tenant->isActive()) {
                $this->recordFailedLogin(
                    request: $request,
                    email: $email,
                    tenantId: $user->tenant_id,
                    userId: $user->id,
                    reason: 'Tenant is not active.'
                );

                throw ValidationException::withMessages([
                    'tenant_code' => ['Tenant is not active.'],
                ]);
            }
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        LoginLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_successful' => true,
            'logged_in_at' => now(),
        ]);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('tenant'),
            'permissions' => method_exists($user, 'getAllPermissions')
                ? $user->getAllPermissions()->pluck('name')->values()
                : [],
            'roles' => method_exists($user, 'getRoleNames')
                ? $user->getRoleNames()
                : [],
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();

        LoginLog::query()
            ->where('user_id', $user->id)
            ->whereNull('logged_out_at')
            ->latest()
            ->limit(1)
            ->update([
                'logged_out_at' => now(),
            ]);
    }

    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();

        LoginLog::query()
            ->where('user_id', $user->id)
            ->whereNull('logged_out_at')
            ->update([
                'logged_out_at' => now(),
            ]);
    }

    private function recordFailedLogin(
        Request $request,
        string $email,
        ?int $tenantId = null,
        ?int $userId = null,
        string $reason = 'Login failed.'
    ): void {
        LoginLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_successful' => false,
            'failure_reason' => $reason,
            'logged_in_at' => now(),
        ]);
    }
}