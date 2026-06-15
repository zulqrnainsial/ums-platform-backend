<?php

namespace App\Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
class AdmissionConfirmationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        if (!Schema::hasTable('admission_confirmations')) {
            return response()->json([
                'data' => [],
                'message' => 'Admission confirmation table not found.',
            ]);
        }

        $query = DB::table('admission_confirmations as c')
            ->leftJoin('applicants as a', 'a.id', '=', 'c.applicant_id');

        if ($tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id')) {
            $query->where('c.tenant_id', $tenantId);
        }

        if ($request->filled('applicant_id')) {
            $query->where('c.applicant_id', (int) $request->query('applicant_id'));
        }

        $items = $query
            ->select([
                'c.*',
                'a.applicant_no',
                DB::raw("CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, '')) as applicant_name"),
            ])
            ->orderByDesc('c.id')
            ->limit(200)
            ->get();

        return response()->json([
            'data' => $items,
            'message' => 'Admission confirmations fetched successfully.',
        ]);
    }
private function createStudentFromApplicant(
    ?int $tenantId,
    int $applicantId,
    ?int $admissionSessionId,
    ?int $offeredProgramId,
    ?int $userId
): void {
    if (!Schema::hasTable('students') || !Schema::hasTable('applicants')) {
        return;
    }

    $applicant = DB::table('applicants')
        ->where('id', $applicantId)
        ->first();

    if (!$applicant) {
        return;
    }

    $existing = DB::table('students')
        ->when(Schema::hasColumn('students', 'tenant_id') && $tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
        ->where(function ($q) use ($applicant) {
            if (Schema::hasColumn('students', 'applicant_id')) {
                $q->orWhere('applicant_id', $applicant->id);
            }

            if (Schema::hasColumn('students', 'cnic_bform') && !empty($applicant->cnic_bform)) {
                $q->orWhere('cnic_bform', $applicant->cnic_bform);
            }
        })
        ->first();

    if ($existing) {
        return;
    }

    $studentNo = 'STD-' . now()->format('Y') . '-' . str_pad((string) $applicant->id, 6, '0', STR_PAD_LEFT);

    $payload = $this->filterColumns('students', [
        'tenant_id' => $tenantId,
        'applicant_id' => $applicant->id,
        'student_no' => $studentNo,
        'registration_no' => $studentNo,
        'roll_no' => $studentNo,

        'first_name' => $applicant->first_name ?? null,
        'last_name' => $applicant->last_name ?? null,
        'full_name' => trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? '')),
        'father_name' => $applicant->father_name ?? null,
        'mother_name' => $applicant->mother_name ?? null,
        'cnic_bform' => $applicant->cnic_bform ?? null,
        'date_of_birth' => $applicant->date_of_birth ?? null,
        'gender_id' => $applicant->gender_id ?? null,
        'email' => $applicant->email ?? null,
        'phone' => $applicant->phone ?? null,
        'current_address' => $applicant->current_address ?? null,
        'permanent_address' => $applicant->permanent_address ?? null,

        'admission_session_id' => $admissionSessionId,
        'offered_program_id' => $offeredProgramId,

        'status_code' => 'active',
        'admission_status_code' => 'confirmed',

        'created_by' => $userId,
        'updated_by' => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('students')->insert($payload);
}
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'admission_merit_list_applicant_id' => ['required', 'integer'],
            'remarks' => ['nullable', 'string'],
        ]);

        $tenantId = $request->user()?->tenant_id;
        $meritListApplicantId = (int) $request->input('admission_merit_list_applicant_id');

        if (!Schema::hasTable('admission_merit_list_applicants')) {
            return response()->json(['message' => 'Merit list applicant table not found.'], 422);
        }

        if (!Schema::hasTable('admission_confirmations')) {
            return response()->json(['message' => 'Admission confirmation table not found.'], 422);
        }

        if (!Schema::hasTable('admission_offer_fee_vouchers')) {
            return response()->json(['message' => 'Voucher table not found.'], 422);
        }

        return DB::transaction(function () use ($request, $tenantId, $meritListApplicantId) {
            $offer = DB::table('admission_merit_list_applicants as mla')
                ->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'mla.admission_merit_list_id')
                ->where('mla.id', $meritListApplicantId)
                ->when($tenantId, fn ($q) => $q->where('mla.tenant_id', $tenantId))
                ->select([
                    'mla.*',
                    'ml.admission_session_id',
                    'ml.offered_program_id',
                    'ml.program_quota_seat_id',
                ])
                ->first();

            if (!$offer) {
                return response()->json(['message' => 'Offer record not found.'], 404);
            }

            if (($offer->selection_status_code ?? null) !== 'selected') {
                return response()->json(['message' => 'Only selected applicants can be confirmed.'], 422);
            }

            if (($offer->offer_status_code ?? null) !== 'accepted') {
                return response()->json(['message' => 'Applicant must accept the offer before confirmation.'], 422);
            }

            $voucher = DB::table('admission_offer_fee_vouchers')
                ->where('admission_merit_list_applicant_id', $meritListApplicantId)
                ->when($tenantId && Schema::hasColumn('admission_offer_fee_vouchers', 'tenant_id'), fn ($q) => $q->where('tenant_id', $tenantId))
                ->orderByDesc('id')
                ->first();

            if (!$voucher) {
                return response()->json(['message' => 'Please generate admission fee voucher first.'], 422);
            }

            if (($voucher->status_code ?? null) !== 'paid') {
                return response()->json(['message' => 'Admission fee voucher must be paid before confirmation.'], 422);
            }

            $existing = DB::table('admission_confirmations')
                ->where('admission_merit_list_applicant_id', $meritListApplicantId)
                ->when($tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id'), fn ($q) => $q->where('tenant_id', $tenantId))
                ->first();

            if ($existing) {
                return response()->json([
                    'data' => $existing,
                    'message' => 'Admission already confirmed.',
                ]);
            }

            $confirmationNo = 'ADM-CONF-' . now()->format('YmdHis') . '-' . $offer->applicant_id;

            $payload = $this->filterColumns('admission_confirmations', [
                'tenant_id' => $tenantId,
                'confirmation_no' => $confirmationNo,
                'applicant_id' => $offer->applicant_id,
                'admission_merit_list_applicant_id' => $meritListApplicantId,
                'admission_merit_list_id' => $offer->admission_merit_list_id,
                'admission_offer_fee_voucher_id' => $voucher->id,
                'admission_session_id' => $offer->admission_session_id ?? null,
                'offered_program_id' => $offer->offered_program_id ?? null,
                'program_quota_seat_id' => $offer->program_quota_seat_id ?? null,
                'status_code' => 'confirmed',
                'confirmed_at' => now(),
                'remarks' => $request->input('remarks', 'Admission confirmed after paid voucher.'),
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $confirmationId = DB::table('admission_confirmations')->insertGetId($payload);
            $transfer = $this->transferApplicantToStudentDepartment(
                tenantId: $tenantId,
                applicantId: (int) $offer->applicant_id,
                admissionMeritListApplicantId: $meritListApplicantId,
                admissionSessionId: $offer->admission_session_id ?? null,
                offeredProgramId: $offer->offered_program_id ?? null,
                programQuotaSeatId: $offer->program_quota_seat_id ?? null,
                confirmationId: $confirmationId,
                userId: $request->user()?->id
            );
            /*$this->createStudentFromApplicant(
                tenantId: $tenantId,
                applicantId: (int) $offer->applicant_id,
                admissionSessionId: $offer->admission_session_id ?? null,
                offeredProgramId: $offer->offered_program_id ?? null,
                userId: $request->user()?->id
            );*/
            DB::table('admission_merit_list_applicants')
                ->where('id', $meritListApplicantId)
                ->update($this->filterColumns('admission_merit_list_applicants', [
                    'admission_confirmation_status_code' => 'confirmed',
                    'admission_confirmed_at' => now(),
                    'updated_at' => now(),
                ]));

            if (Schema::hasTable('applicants')) {
                DB::table('applicants')
                    ->where('id', $offer->applicant_id)
                    ->update($this->filterColumns('applicants', [
                        'admission_status_code' => 'confirmed',
                        'status_code' => 'admitted',
                        'updated_at' => now(),
                    ]));
            }

            $confirmation = DB::table('admission_confirmations')->where('id', $confirmationId)->first();

            return response()->json([
                'data' => $confirmation,
                'message' => 'Admission confirmed successfully.',
            ]);
        });
    }
private function transferApplicantToStudentDepartment(
    ?int $tenantId,
    int $applicantId,
    int $admissionMeritListApplicantId,
    ?int $admissionSessionId,
    ?int $offeredProgramId,
    ?int $programQuotaSeatId,
    int $confirmationId,
    ?int $userId
): array {
    if (!Schema::hasTable('applicants') || !Schema::hasTable('students')) {
        return [
            'student_id' => null,
            'department_id' => null,
            'program_id' => null,
            'status' => 'student_table_missing',
        ];
    }

    $applicant = DB::table('applicants')
        ->where('id', $applicantId)
        ->first();

    if (!$applicant) {
        return [
            'student_id' => null,
            'department_id' => null,
            'program_id' => null,
            'status' => 'applicant_not_found',
        ];
    }

    $academicTarget = $this->resolveAcademicTarget($offeredProgramId);

    $studentId = $this->createOrUpdateStudentFromApplicant(
        tenantId: $tenantId,
        applicant: $applicant,
        admissionSessionId: $admissionSessionId,
        offeredProgramId: $offeredProgramId,
        programQuotaSeatId: $programQuotaSeatId,
        departmentId: $academicTarget['department_id'],
        programId: $academicTarget['program_id'],
        userId: $userId
    );

    $this->createStudentEnrollmentIfPossible(
        tenantId: $tenantId,
        studentId: $studentId,
        applicantId: $applicantId,
        admissionSessionId: $admissionSessionId,
        offeredProgramId: $offeredProgramId,
        programQuotaSeatId: $programQuotaSeatId,
        departmentId: $academicTarget['department_id'],
        programId: $academicTarget['program_id'],
        userId: $userId
    );

    $this->updateTransferStatus(
        admissionMeritListApplicantId: $admissionMeritListApplicantId,
        confirmationId: $confirmationId,
        studentId: $studentId,
        departmentId: $academicTarget['department_id'],
        programId: $academicTarget['program_id']
    );

    return [
        'student_id' => $studentId,
        'department_id' => $academicTarget['department_id'],
        'program_id' => $academicTarget['program_id'],
        'status' => 'transferred',
    ];
}
private function resolveAcademicTarget(?int $offeredProgramId): array
{
    $result = [
        'department_id' => null,
        'program_id' => null,
    ];

    if (!$offeredProgramId || !Schema::hasTable('offered_programs')) {
        return $result;
    }

    $offeredProgram = DB::table('offered_programs')
        ->where('id', $offeredProgramId)
        ->first();

    if (!$offeredProgram) {
        return $result;
    }

    foreach (['program_id', 'academic_program_id'] as $column) {
        if (Schema::hasColumn('offered_programs', $column) && !empty($offeredProgram->{$column})) {
            $result['program_id'] = (int) $offeredProgram->{$column};
            break;
        }
    }

    foreach (['department_id', 'academic_department_id'] as $column) {
        if (Schema::hasColumn('offered_programs', $column) && !empty($offeredProgram->{$column})) {
            $result['department_id'] = (int) $offeredProgram->{$column};
            break;
        }
    }

    if (!$result['department_id'] && $result['program_id'] && Schema::hasTable('programs')) {
        $program = DB::table('programs')
            ->where('id', $result['program_id'])
            ->first();

        if ($program) {
            foreach (['department_id', 'academic_department_id'] as $column) {
                if (Schema::hasColumn('programs', $column) && !empty($program->{$column})) {
                    $result['department_id'] = (int) $program->{$column};
                    break;
                }
            }
        }
    }

    return $result;
}
private function createOrUpdateStudentFromApplicant(
    ?int $tenantId,
    object $applicant,
    ?int $admissionSessionId,
    ?int $offeredProgramId,
    ?int $programQuotaSeatId,
    ?int $departmentId,
    ?int $programId,
    ?int $userId
): int {
    $existing = DB::table('students')
        ->when(
            Schema::hasColumn('students', 'tenant_id') && $tenantId,
            fn ($q) => $q->where('tenant_id', $tenantId)
        )
        ->where(function ($q) use ($applicant) {
            if (Schema::hasColumn('students', 'applicant_id')) {
                $q->orWhere('applicant_id', $applicant->id);
            }

            if (Schema::hasColumn('students', 'cnic_bform') && !empty($applicant->cnic_bform)) {
                $q->orWhere('cnic_bform', $applicant->cnic_bform);
            }

            if (Schema::hasColumn('students', 'email') && !empty($applicant->email)) {
                $q->orWhere('email', $applicant->email);
            }
        })
        ->first();

    $studentNo = $existing?->student_no
        ?? $existing?->registration_no
        ?? $this->generateStudentNo($tenantId);
    $studentUser = $this->createOrLinkStudentPortalUser(
        tenantId: $tenantId,
        applicant: $applicant,
        studentNo: $studentNo,
        adminUserId: $userId
    );
    $payload = $this->filterColumns('students', [
        'tenant_id' => $tenantId,
        'applicant_id' => $applicant->id,

        'student_no' => $studentNo,
        'registration_no' => $studentNo,
        'roll_no' => $studentNo,
        'user_id' => $studentUser?->id,
        'portal_access_enabled' => true,
        'portal_activated_at' => now(),
        'first_name' => $applicant->first_name ?? null,
        'last_name' => $applicant->last_name ?? null,
        'full_name' => trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? '')),
        'father_name' => $applicant->father_name ?? null,
        'mother_name' => $applicant->mother_name ?? null,
        'cnic_bform' => $applicant->cnic_bform ?? null,
        'passport_no' => $applicant->passport_no ?? null,
        'date_of_birth' => $applicant->date_of_birth ?? null,

        'gender_id' => $applicant->gender_id ?? null,
        'gender' => $applicant->gender ?? null,

        'email' => $applicant->email ?? null,
        'phone' => $applicant->phone ?? null,

        'nationality_id' => $applicant->nationality_id ?? null,
        'religion_id' => $applicant->religion_id ?? null,
        'country_id' => $applicant->country_id ?? null,
        'province_id' => $applicant->province_id ?? null,
        'city_id' => $applicant->city_id ?? null,

        'current_address' => $applicant->current_address ?? null,
        'permanent_address' => $applicant->permanent_address ?? null,

        'admission_session_id' => $admissionSessionId,
        'offered_program_id' => $offeredProgramId,
        'program_quota_seat_id' => $programQuotaSeatId,
        'department_id' => $departmentId,
        'program_id' => $programId,

        'status_code' => 'active',
        'admission_status_code' => 'confirmed',

        'created_by' => $userId,
        'updated_by' => $userId,
        'updated_at' => now(),
    ]);

    if ($existing) {
        DB::table('students')
            ->where('id', $existing->id)
            ->update($payload);

        return (int) $existing->id;
    }

    $payload = array_merge($payload, $this->filterColumns('students', [
        'created_at' => now(),
    ]));

    return (int) DB::table('students')->insertGetId($payload);
}
private function createOrLinkStudentPortalUser(
    ?int $tenantId,
    object $applicant,
    string $studentNo,
    ?int $adminUserId
): ?User {
    if (!Schema::hasTable('users')) {
        return null;
    }

    $email = $applicant->email ?? null;

    if (!$email) {
        $email = strtolower($studentNo) . '@student.local';
    }

    $name = trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? ''));

    if ($name === '') {
        $name = $studentNo;
    }

    $userQuery = User::query()
        ->where('email', $email);

    if (Schema::hasColumn('users', 'tenant_id') && $tenantId) {
        $userQuery->where('tenant_id', $tenantId);
    }

    $user = $userQuery->first();

    if (!$user) {
        $payload = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($this->defaultStudentPassword($applicant, $studentNo)),
        ];

        if (Schema::hasColumn('users', 'tenant_id')) {
            $payload['tenant_id'] = $tenantId;
        }

        if (Schema::hasColumn('users', 'status')) {
            $payload['status'] = 'active';
        }

        if (Schema::hasColumn('users', 'created_by')) {
            $payload['created_by'] = $adminUserId;
        }

        if (Schema::hasColumn('users', 'updated_by')) {
            $payload['updated_by'] = $adminUserId;
        }

        $user = User::create($payload);
    }

    $this->assignStudentRoleToUser($user);

    return $user;
}
private function defaultStudentPassword(object $applicant, string $studentNo): string
{
    if (!empty($applicant->cnic_bform)) {
        $digits = preg_replace('/\D+/', '', (string) $applicant->cnic_bform);

        if (strlen($digits) >= 5) {
            return 'Student@' . substr($digits, -5);
        }
    }

    return 'Student@12345';
}
private function assignStudentRoleToUser(User $user): void
{
    if (!class_exists(Role::class)) {
        return;
    }

    $studentRole = Role::query()
        ->where('guard_name', 'web')
        ->where('name', 'Student')
        ->first();

    if (!$studentRole) {
        return;
    }

    if (!$user->hasRole($studentRole->name)) {
        $user->assignRole($studentRole);
    }
}
private function generateStudentNo(?int $tenantId): string
{
    $prefix = 'STD-' . now()->format('Y') . '-';

    $count = DB::table('students')
        ->when(
            Schema::hasColumn('students', 'tenant_id') && $tenantId,
            fn ($q) => $q->where('tenant_id', $tenantId)
        )
        ->count();

    return $prefix . str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
}
private function createStudentEnrollmentIfPossible(
    ?int $tenantId,
    int $studentId,
    int $applicantId,
    ?int $admissionSessionId,
    ?int $offeredProgramId,
    ?int $programQuotaSeatId,
    ?int $departmentId,
    ?int $programId,
    ?int $userId
): void {
    $table = null;

    foreach ([
        'student_program_enrollments',
        'student_enrollments',
        'program_enrollments',
    ] as $candidateTable) {
        if (Schema::hasTable($candidateTable)) {
            $table = $candidateTable;
            break;
        }
    }

    if (!$table) {
        return;
    }

    $exists = DB::table($table)
        ->when(
            Schema::hasColumn($table, 'tenant_id') && $tenantId,
            fn ($q) => $q->where('tenant_id', $tenantId)
        )
        ->where('student_id', $studentId)
        ->when(
            Schema::hasColumn($table, 'offered_program_id') && $offeredProgramId,
            fn ($q) => $q->where('offered_program_id', $offeredProgramId)
        )
        ->when(
            Schema::hasColumn($table, 'admission_session_id') && $admissionSessionId,
            fn ($q) => $q->where('admission_session_id', $admissionSessionId)
        )
        ->first();

    if ($exists) {
        return;
    }

    $payload = $this->filterColumns($table, [
        'tenant_id' => $tenantId,
        'student_id' => $studentId,
        'applicant_id' => $applicantId,

        'admission_session_id' => $admissionSessionId,
        'academic_session_id' => $admissionSessionId,

        'offered_program_id' => $offeredProgramId,
        'program_quota_seat_id' => $programQuotaSeatId,
        'department_id' => $departmentId,
        'program_id' => $programId,

        'enrollment_no' => 'ENR-' . now()->format('YmdHis') . '-' . $studentId,
        'status_code' => 'active',
        'enrollment_status_code' => 'active',

        'created_by' => $userId,
        'updated_by' => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    if (!empty($payload)) {
        DB::table($table)->insert($payload);
    }
}
private function updateTransferStatus(
    int $admissionMeritListApplicantId,
    int $confirmationId,
    int $studentId,
    ?int $departmentId,
    ?int $programId
): void {
    if (Schema::hasTable('admission_confirmations')) {
        DB::table('admission_confirmations')
            ->where('id', $confirmationId)
            ->update($this->filterColumns('admission_confirmations', [
                'student_id' => $studentId,
                'department_id' => $departmentId,
                'program_id' => $programId,
                'transfer_status_code' => 'transferred',
                'transferred_at' => now(),
                'updated_at' => now(),
            ]));
    }

    if (Schema::hasTable('admission_merit_list_applicants')) {
        DB::table('admission_merit_list_applicants')
            ->where('id', $admissionMeritListApplicantId)
            ->update($this->filterColumns('admission_merit_list_applicants', [
                'student_id' => $studentId,
                'department_transfer_status_code' => 'transferred',
                'department_transferred_at' => now(),
                'updated_at' => now(),
            ]));
    }
}
public function transferConfirmedApplicant(Request $request, int $confirmationId): JsonResponse
{
    $tenantId = $request->user()?->tenant_id;

    $confirmation = DB::table('admission_confirmations')
        ->where('id', $confirmationId)
        ->when(
            $tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id'),
            fn ($q) => $q->where('tenant_id', $tenantId)
        )
        ->first();

    if (!$confirmation) {
        return response()->json([
            'message' => 'Admission confirmation not found.',
        ], 404);
    }

    if (!empty($confirmation->student_id) && ($confirmation->transfer_status_code ?? null) === 'transferred') {
        return response()->json([
            'data' => [
                'student_id' => $confirmation->student_id,
                'transfer_status_code' => $confirmation->transfer_status_code,
            ],
            'message' => 'Candidate is already transferred as student.',
        ]);
    }

    $offer = DB::table('admission_merit_list_applicants as mla')
        ->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'mla.admission_merit_list_id')
        ->where('mla.id', $confirmation->admission_merit_list_applicant_id)
        ->select([
            'mla.id as merit_list_applicant_id',
            'mla.applicant_id',
            'ml.id as admission_merit_list_id',
            'ml.admission_session_id',
            'ml.offered_program_id',
            'ml.program_quota_seat_id',
        ])
        ->first();

    if (!$offer) {
        return response()->json([
            'message' => 'Merit list applicant record not found for this confirmation.',
        ], 422);
    }

    $transfer = $this->transferApplicantToStudentDepartment(
        tenantId: $tenantId,
        applicantId: (int) $confirmation->applicant_id,
        admissionMeritListApplicantId: (int) $confirmation->admission_merit_list_applicant_id,
        admissionSessionId: $confirmation->admission_session_id ?? $offer->admission_session_id ?? null,
        offeredProgramId: $confirmation->offered_program_id ?? $offer->offered_program_id ?? null,
        programQuotaSeatId: $confirmation->program_quota_seat_id ?? $offer->program_quota_seat_id ?? null,
        confirmationId: $confirmationId,
        userId: $request->user()?->id
    );

    return response()->json([
        'data' => $transfer,
        'message' => 'Confirmed applicant transferred to student successfully.',
    ]);
}
    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->toArray();
    }
}