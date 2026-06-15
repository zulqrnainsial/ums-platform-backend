<?php

namespace App\Modules\Admission\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AdmissionFinalizationService
{
    public function finalize(int $confirmationId, ?int $tenantId, ?int $userId = null, ?string $remarks = null): array
    {
        return DB::transaction(function () use ($confirmationId, $tenantId, $userId, $remarks) {
            $confirmation = $this->getConfirmation($confirmationId, $tenantId);

            if (!$confirmation) {
                throw new RuntimeException('Admission confirmation not found.');
            }

            if (!$tenantId && !empty($confirmation->tenant_id)) {
                $tenantId = (int) $confirmation->tenant_id;
            }

            if (($confirmation->status_code ?? null) !== 'confirmed') {
                throw new RuntimeException('Only confirmed admissions can be finalized.');
            }

            $studentId = $this->resolveStudentId($confirmation);

            if (!$studentId) {
                throw new RuntimeException('Student is not created yet. Please transfer candidate to student first.');
            }

            $student = DB::table('students')
                ->where('id', $studentId)
                ->when(
                    $tenantId && Schema::hasColumn('students', 'tenant_id'),
                    fn ($q) => $q->where('tenant_id', $tenantId)
                )
                ->first();

            if (!$student) {
                throw new RuntimeException('Student record not found.');
            }

            if (!Schema::hasTable('student_enrollments')) {
                throw new RuntimeException('student_enrollments table not found.');
            }

            $enrollmentId = $this->findOrCreateEnrollment($confirmation, $student, $tenantId, $userId);

            $this->updateConfirmation($confirmation, $enrollmentId, $userId, $remarks);
            $this->updateMeritListApplicant($confirmation, $studentId, $enrollmentId);
            $this->updateStudent($studentId, $tenantId);

            $this->log([
                'tenant_id' => $tenantId,
                'admission_confirmation_id' => $confirmation->id,
                'applicant_id' => $confirmation->applicant_id ?? null,
                'student_id' => $studentId,
                'student_enrollment_id' => $enrollmentId,
                'action_code' => 'admission_finalized',
                'status_code' => 'finalized',
                'remarks' => $remarks ?: 'Admission finalized and student enrollment linked.',
                'created_by' => $userId,
            ]);

            return [
                'confirmation_id' => $confirmation->id,
                'student_id' => $studentId,
                'student_enrollment_id' => $enrollmentId,
                'finalization_status_code' => 'finalized',
            ];
        });
    }

    private function getConfirmation(int $confirmationId, ?int $tenantId): ?object
    {
        if (!Schema::hasTable('admission_confirmations')) {
            return null;
        }

        return DB::table('admission_confirmations')
            ->where('id', $confirmationId)
            ->when(
                $tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            )
            ->first();
    }

    private function resolveStudentId(object $confirmation): ?int
    {
        if (!empty($confirmation->student_id)) {
            return (int) $confirmation->student_id;
        }

        if (
            Schema::hasTable('students')
            && Schema::hasColumn('students', 'applicant_id')
            && !empty($confirmation->applicant_id)
        ) {
            $student = DB::table('students')
                ->where('applicant_id', $confirmation->applicant_id)
                ->first();

            if ($student) {
                return (int) $student->id;
            }
        }

        return null;
    }

    private function findOrCreateEnrollment(object $confirmation, object $student, ?int $tenantId, ?int $userId): int
    {
        $query = DB::table('student_enrollments');

        if ($tenantId && Schema::hasColumn('student_enrollments', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if (Schema::hasColumn('student_enrollments', 'student_id')) {
            $query->where('student_id', $student->id);
        }

        if (
            Schema::hasColumn('student_enrollments', 'admission_session_id')
            && !empty($confirmation->admission_session_id)
        ) {
            $query->where('admission_session_id', $confirmation->admission_session_id);
        }

        if (
            Schema::hasColumn('student_enrollments', 'offered_program_id')
            && !empty($confirmation->offered_program_id)
        ) {
            $query->where('offered_program_id', $confirmation->offered_program_id);
        }

        if (
            Schema::hasColumn('student_enrollments', 'program_id')
            && !empty($confirmation->program_id)
        ) {
            $query->where('program_id', $confirmation->program_id);
        }

        $existing = $query->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $payload = $this->filterColumns('student_enrollments', [
            'tenant_id' => $tenantId,
            'student_id' => $student->id,

            'admission_confirmation_id' => $confirmation->id ?? null,
            'admission_merit_list_applicant_id' => $confirmation->admission_merit_list_applicant_id ?? null,
            'admission_merit_list_id' => $confirmation->admission_merit_list_id ?? null,

            'admission_session_id' => $confirmation->admission_session_id ?? null,
            'academic_session_id' => $confirmation->admission_session_id ?? null,

            'offered_program_id' => $confirmation->offered_program_id ?? null,
            'program_id' => $confirmation->program_id ?? null,
            'department_id' => $confirmation->department_id ?? null,
            'program_quota_seat_id' => $confirmation->program_quota_seat_id ?? null,

            'enrollment_no' => $this->generateEnrollmentNo($confirmation),
            'registration_no' => $student->registration_no ?? null,
            'roll_no' => $student->roll_no ?? null,

            'enrollment_type_code' => 'admission',
            'status_code' => 'active',
            'enrollment_status_code' => 'enrolled',

            'enrolled_at' => now(),
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (empty($payload['student_id'] ?? null)) {
            throw new RuntimeException('student_enrollments.student_id column is required for enrollment bridge.');
        }

        return (int) DB::table('student_enrollments')->insertGetId($payload);
    }

    private function generateEnrollmentNo(object $confirmation): string
    {
        $year = now()->format('Y');
        $idPart = str_pad((string) ($confirmation->id ?? 0), 6, '0', STR_PAD_LEFT);

        return 'ENR-' . $year . '-' . $idPart;
    }

    private function updateConfirmation(object $confirmation, int $enrollmentId, ?int $userId, ?string $remarks): void
    {
        DB::table('admission_confirmations')
            ->where('id', $confirmation->id)
            ->update($this->filterColumns('admission_confirmations', [
                'student_id' => $this->resolveStudentId($confirmation),
                'student_enrollment_id' => $enrollmentId,
                'finalization_status_code' => 'finalized',
                'finalized_at' => now(),
                'finalized_by' => $userId,
                'finalization_remarks' => $remarks,
                'updated_at' => now(),
            ]));
    }

    private function updateMeritListApplicant(object $confirmation, int $studentId, int $enrollmentId): void
    {
        if (
            empty($confirmation->admission_merit_list_applicant_id)
            || !Schema::hasTable('admission_merit_list_applicants')
        ) {
            return;
        }

        DB::table('admission_merit_list_applicants')
            ->where('id', $confirmation->admission_merit_list_applicant_id)
            ->update($this->filterColumns('admission_merit_list_applicants', [
                'student_id' => $studentId,
                'student_enrollment_id' => $enrollmentId,
                'admission_finalization_status_code' => 'finalized',
                'admission_finalized_at' => now(),
                'updated_at' => now(),
            ]));
    }

    private function updateStudent(int $studentId, ?int $tenantId): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        DB::table('students')
            ->where('id', $studentId)
            ->when(
                $tenantId && Schema::hasColumn('students', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            )
            ->update($this->filterColumns('students', [
                'status_code' => 'active',
                'admission_status_code' => 'confirmed',
                'enrollment_status_code' => 'enrolled',
                'updated_at' => now(),
            ]));
    }

    private function log(array $payload): void
    {
        if (!Schema::hasTable('admission_finalization_logs')) {
            return;
        }

        DB::table('admission_finalization_logs')
            ->insert($this->filterColumns('admission_finalization_logs', array_merge($payload, [
                'created_at' => now(),
                'updated_at' => now(),
            ])));
    }

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->toArray();
    }
}