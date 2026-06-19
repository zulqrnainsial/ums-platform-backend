<?php

namespace App\Modules\Student\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Modules\Student\Models\StudentCourseRegistration;
use App\Modules\Student\Models\Guardian;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentDocument;
use App\Modules\Student\Models\StudentGuardian;
use App\Modules\Student\Models\StudentPreviousEducation;
class StudentAcademicService
{
    public function students(array $filters): LengthAwarePaginator
    {
        $tenantId = $this->tenantId();

        $query = DB::table('students as s')
            ->where('s.tenant_id', $tenantId);

        $this->joinAdmissionConfirmation($query);
        $this->joinApplicant($query);
        $this->joinLatestEnrollment($query);

        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);

            $query->where(function ($sub) use ($q) {
                $sub->where('s.student_no', 'like', "%{$q}%")
                    ->orWhere('s.admission_no', 'like', "%{$q}%")
                    ->orWhere('s.full_name', 'like', "%{$q}%")
                    ->orWhere('s.first_name', 'like', "%{$q}%")
                    ->orWhere('s.last_name', 'like', "%{$q}%")
                    ->orWhere('s.cnic_bform', 'like', "%{$q}%")
                    ->orWhere('s.email', 'like', "%{$q}%")
                    ->orWhere('s.phone', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['student_status'])) {
    $query->where(function ($sub) use ($filters) {
        if (Schema::hasColumn('students', 'student_status')) {
            $sub->orWhere('s.student_status', $filters['student_status']);
        }

        if (Schema::hasColumn('students', 'status_code')) {
            $sub->orWhere('s.status_code', $filters['student_status']);
        }
    });
}

        return $query
            ->select($this->studentSelectColumns())
            ->orderByDesc('s.id')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function showStudent(int $studentId): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('students as s')
            ->where('s.tenant_id', $tenantId)
            ->where('s.id', $studentId);

        $this->joinAdmissionConfirmation($query);
        $this->joinApplicant($query);
        $this->joinLatestEnrollment($query);

        $student = $query
            ->select($this->studentSelectColumns())
            ->first();

        abort_if(!$student, 404, 'Student not found.');

        return [
            'student' => $student,
            'enrollments' => $this->studentEnrollments($studentId),
            'guardians' => $this->studentGuardians($studentId),
            'documents' => $this->studentDocuments($studentId),
            'previous_educations' => $this->studentPreviousEducations($studentId),
            'status_histories' => $this->studentStatusHistories($studentId),
        ];
    }

    public function enrollments(array $filters): LengthAwarePaginator
    {
        $tenantId = $this->tenantId();

        if (!Schema::hasTable('student_enrollments')) {
            return new LengthAwarePaginator([], 0, (int) ($filters['per_page'] ?? 20));
        }

        $query = DB::table('student_enrollments as se')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->where('se.tenant_id', $tenantId)
            ->where('s.tenant_id', $tenantId);

        $this->joinEnrollmentProgram($query);
        $this->joinEnrollmentAcademicSession($query);
        $this->joinEnrollmentAdmissionConfirmation($query);

        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);

            $query->where(function ($sub) use ($q) {
                $sub->where('s.student_no', 'like', "%{$q}%")
                    ->orWhere('s.full_name', 'like', "%{$q}%")
                    ->orWhere('se.roll_no', 'like', "%{$q}%")
                    ->orWhere('se.registration_no', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['program_id']) && Schema::hasColumn('student_enrollments', 'program_id')) {
            $query->where('se.program_id', $filters['program_id']);
        }

        if (!empty($filters['academic_session_id']) && Schema::hasColumn('student_enrollments', 'academic_session_id')) {
            $query->where('se.academic_session_id', $filters['academic_session_id']);
        }

        if (!empty($filters['status'])) {
            $query->where(function ($sub) use ($filters) {
                if (Schema::hasColumn('student_enrollments', 'enrollment_status_code')) {
                    $sub->orWhere('se.enrollment_status_code', $filters['status']);
                }
                if (Schema::hasColumn('student_enrollments', 'status_code')) {
                    $sub->orWhere('se.status_code', $filters['status']);
                }
                if (Schema::hasColumn('student_enrollments', 'allocation_status')) {
                    $sub->orWhere('se.allocation_status', $filters['status']);
                }
                if (Schema::hasColumn('student_enrollments', 'status')) {
                    $sub->orWhere('se.status', $filters['status']);
                }
            });
        }

        return $query
            ->select($this->enrollmentSelectColumns())
            ->orderByDesc('se.id')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }
public function updateStudentProfile(int $studentId, array $data): array
{
    $tenantId = $this->tenantId();

    $student = Student::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $studentId)
        ->firstOrFail();

    $allowed = [
        'first_name',
        'last_name',
        'father_name',
        'mother_name',
        'cnic_bform',
        'passport_no',
        'date_of_birth',
        'gender',
        'blood_group_id',
        'religion_id',
        'nationality_id',
        'phone',
        'alternate_phone',
        'email',
        'current_address',
        'permanent_address',
        'country_id',
        'province_id',
        'city_id',
        'remarks',
    ];

    $payload = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $data) && Schema::hasColumn('students', $field)) {
            $payload[$field] = $data[$field];
        }
    }

    if (Schema::hasColumn('students', 'updated_by')) {
        $payload['updated_by'] = auth()->id();
    }

    abort_if(empty($payload), 422, 'No valid student profile field found.');

    $student->update($payload);

    return [
        'student' => $student->fresh(),
        'completion' => $this->profileCompletionSummary($studentId),
    ];
}

public function upsertGuardian(int $studentId, array $data): array
{
    $tenantId = $this->tenantId();

    $student = Student::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $studentId)
        ->firstOrFail();

    return DB::transaction(function () use ($tenantId, $student, $data) {
        $guardian = null;

        if (!empty($data['guardian_id'])) {
            $guardian = Guardian::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['guardian_id'])
                ->firstOrFail();
        }

        if (!$guardian && !empty($data['cnic'])) {
            $guardian = Guardian::query()
                ->where('tenant_id', $tenantId)
                ->where('cnic', $data['cnic'])
                ->first();
        }

        $guardianPayload = [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'cnic' => $data['cnic'] ?? null,
            'phone' => $data['phone'] ?? null,
            'alternate_phone' => $data['alternate_phone'] ?? null,
            'email' => $data['email'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'monthly_income' => $data['monthly_income'] ?? null,
            'address' => $data['address'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'province_id' => $data['province_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'updated_by' => auth()->id(),
        ];

        if (!$guardian) {
            $guardianPayload['created_by'] = auth()->id();
            $guardian = Guardian::create($guardianPayload);
        } else {
            $guardian->update($guardianPayload);
        }

        if (!empty($data['is_primary'])) {
            StudentGuardian::query()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->update(['is_primary' => false]);
        }

        $studentGuardian = null;

        if (!empty($data['student_guardian_id'])) {
            $studentGuardian = StudentGuardian::query()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('id', $data['student_guardian_id'])
                ->firstOrFail();
        }

        if (!$studentGuardian) {
            $studentGuardian = StudentGuardian::query()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('guardian_id', $guardian->id)
                ->first();
        }

        $linkPayload = [
            'tenant_id' => $tenantId,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'relationship_type_id' => $data['relationship_type_id'] ?? null,
            'is_primary' => (bool) ($data['is_primary'] ?? false),
            'is_emergency_contact' => (bool) ($data['is_emergency_contact'] ?? false),
            'can_pick_student' => (bool) ($data['can_pick_student'] ?? false),
            'remarks' => $data['remarks'] ?? null,
            'status' => $data['status'] ?? 'active',
            'updated_by' => auth()->id(),
        ];

        if (!$studentGuardian) {
            $linkPayload['created_by'] = auth()->id();
            $studentGuardian = StudentGuardian::create($linkPayload);
        } else {
            $studentGuardian->update($linkPayload);
        }

        return [
            'guardian' => $guardian->fresh(),
            'student_guardian' => $studentGuardian->fresh(),
            'completion' => $this->profileCompletionSummary($student->id),
        ];
    });
}

public function deleteGuardian(int $studentGuardianId): array
{
    $tenantId = $this->tenantId();

    $link = StudentGuardian::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $studentGuardianId)
        ->firstOrFail();

    $studentId = $link->student_id;

    $link->delete();

    return [
        'student_guardian_id' => $studentGuardianId,
        'deleted' => true,
        'completion' => $this->profileCompletionSummary($studentId),
    ];
}

public function upsertPreviousEducation(int $studentId, array $data): array
{
    $tenantId = $this->tenantId();

    $student = Student::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $studentId)
        ->firstOrFail();

    if (
        isset($data['total_marks'], $data['obtained_marks'])
        && (int) $data['total_marks'] > 0
        && !isset($data['percentage'])
    ) {
        $data['percentage'] = round(((int) $data['obtained_marks'] / (int) $data['total_marks']) * 100, 2);
    }

    $payload = [
        'tenant_id' => $tenantId,
        'student_id' => $student->id,
        'qualification_level_id' => $data['qualification_level_id'] ?? null,
        'education_board_id' => $data['education_board_id'] ?? null,
        'external_institution_id' => $data['external_institution_id'] ?? null,
        'degree_class_name' => $data['degree_class_name'] ?? null,
        'roll_no' => $data['roll_no'] ?? null,
        'registration_no' => $data['registration_no'] ?? null,
        'passing_year' => $data['passing_year'] ?? null,
        'total_marks' => $data['total_marks'] ?? null,
        'obtained_marks' => $data['obtained_marks'] ?? null,
        'percentage' => $data['percentage'] ?? null,
        'grade' => $data['grade'] ?? null,
        'cgpa' => $data['cgpa'] ?? null,
        'document_path' => $data['document_path'] ?? null,
        'remarks' => $data['remarks'] ?? null,
        'status' => $data['status'] ?? 'active',
        'updated_by' => auth()->id(),
    ];

    if (!empty($data['id'])) {
        $previousEducation = StudentPreviousEducation::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->where('id', $data['id'])
            ->firstOrFail();

        $previousEducation->update($payload);
    } else {
        $payload['created_by'] = auth()->id();
        $previousEducation = StudentPreviousEducation::create($payload);
    }

    return [
        'previous_education' => $previousEducation->fresh(),
        'completion' => $this->profileCompletionSummary($student->id),
    ];
}

public function deletePreviousEducation(int $previousEducationId): array
{
    $tenantId = $this->tenantId();

    $record = StudentPreviousEducation::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $previousEducationId)
        ->firstOrFail();

    $studentId = $record->student_id;

    $record->delete();

    return [
        'previous_education_id' => $previousEducationId,
        'deleted' => true,
        'completion' => $this->profileCompletionSummary($studentId),
    ];
}

public function verifyDocument(int $documentId, array $data): array
{
    $tenantId = $this->tenantId();

    $allowedStatuses = [
        'pending',
        'verified',
        'rejected',
        'resubmission_required',
    ];

    $status = strtolower(trim((string) $data['verification_status']));

    abort_if(
        !in_array($status, $allowedStatuses, true),
        422,
        'Invalid document verification status.'
    );

    $document = StudentDocument::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $documentId)
        ->firstOrFail();

    $document->update([
        'verification_status' => $status,
        'verified_at' => in_array($status, ['verified', 'rejected'], true) ? now() : null,
        'verified_by' => in_array($status, ['verified', 'rejected'], true) ? auth()->id() : null,
        'remarks' => $data['remarks'] ?? null,
        'updated_by' => auth()->id(),
    ]);

    return [
        'document' => $document->fresh(),
        'completion' => $this->profileCompletionSummary($document->student_id),
    ];
}

public function profileCompletionSummary(int $studentId): array
{
    $tenantId = $this->tenantId();

    $student = Student::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $studentId)
        ->firstOrFail();

    $requiredProfileFields = [
        'first_name',
        'father_name',
        'cnic_bform',
        'date_of_birth',
        'gender',
        'phone',
        'current_address',
    ];

    $missingProfileFields = [];

    foreach ($requiredProfileFields as $field) {
        if (empty($student->{$field})) {
            $missingProfileFields[] = $field;
        }
    }

    $guardianCount = StudentGuardian::query()
        ->where('tenant_id', $tenantId)
        ->where('student_id', $studentId)
        ->count();

    $primaryGuardianExists = StudentGuardian::query()
        ->where('tenant_id', $tenantId)
        ->where('student_id', $studentId)
        ->where('is_primary', true)
        ->exists();

    $previousEducationCount = StudentPreviousEducation::query()
        ->where('tenant_id', $tenantId)
        ->where('student_id', $studentId)
        ->count();

    $documentCount = StudentDocument::query()
        ->where('tenant_id', $tenantId)
        ->where('student_id', $studentId)
        ->count();

    $pendingDocumentCount = StudentDocument::query()
        ->where('tenant_id', $tenantId)
        ->where('student_id', $studentId)
        ->where(function ($q) {
            $q->whereNull('verification_status')
                ->orWhere('verification_status', 'pending');
        })
        ->count();

    $rejectedDocumentCount = StudentDocument::query()
        ->where('tenant_id', $tenantId)
        ->where('student_id', $studentId)
        ->whereIn('verification_status', ['rejected', 'resubmission_required'])
        ->count();

    $issues = [];

    foreach ($missingProfileFields as $field) {
        $issues[] = 'Missing profile field: ' . str_replace('_', ' ', $field);
    }

    if ($guardianCount === 0) {
        $issues[] = 'No guardian added.';
    }

    if (!$primaryGuardianExists) {
        $issues[] = 'Primary guardian not selected.';
    }

    if ($previousEducationCount === 0) {
        $issues[] = 'Previous education not added.';
    }

    if ($documentCount === 0) {
        $issues[] = 'No student documents found.';
    }

    if ($pendingDocumentCount > 0) {
        $issues[] = "{$pendingDocumentCount} document(s) pending verification.";
    }

    if ($rejectedDocumentCount > 0) {
        $issues[] = "{$rejectedDocumentCount} document(s) rejected/resubmission required.";
    }

    $sections = [
        'profile' => count($missingProfileFields) === 0,
        'guardian' => $guardianCount > 0 && $primaryGuardianExists,
        'previous_education' => $previousEducationCount > 0,
        'documents' => $documentCount > 0 && $pendingDocumentCount === 0 && $rejectedDocumentCount === 0,
    ];

    $completedSections = collect($sections)->filter()->count();
    $totalSections = count($sections);

    return [
        'student_id' => $studentId,
        'is_complete' => count($issues) === 0,
        'completion_percentage' => round(($completedSections / $totalSections) * 100, 2),
        'sections' => $sections,
        'missing_profile_fields' => $missingProfileFields,
        'guardian_count' => $guardianCount,
        'primary_guardian_exists' => $primaryGuardianExists,
        'previous_education_count' => $previousEducationCount,
        'document_count' => $documentCount,
        'pending_document_count' => $pendingDocumentCount,
        'rejected_document_count' => $rejectedDocumentCount,
        'issues' => $issues,
    ];
}
public function lifecycleContext(array $filters): array
{
    $tenantId = $this->tenantId();

    abort_if(!Schema::hasTable('student_enrollments'), 404, 'Student enrollments table not found.');

    $query = DB::table('students as s')
        ->leftJoin('student_enrollments as se', function ($join) {
            $join->on('se.student_id', '=', 's.id');

            if (Schema::hasColumn('student_enrollments', 'tenant_id')) {
                $join->whereColumn('se.tenant_id', 's.tenant_id');
            }
        })
        ->where('s.tenant_id', $tenantId);

    if (Schema::hasTable('programs') && Schema::hasColumn('student_enrollments', 'program_id')) {
        $query->leftJoin('programs as p', 'p.id', '=', 'se.program_id');
    }

    if (Schema::hasTable('academic_sessions') && Schema::hasColumn('student_enrollments', 'academic_session_id')) {
        $query->leftJoin('academic_sessions as ases', 'ases.id', '=', 'se.academic_session_id');
    }

    if (Schema::hasTable('student_batches') && Schema::hasColumn('student_enrollments', 'student_batch_id')) {
        $query->leftJoin('student_batches as sb', 'sb.id', '=', 'se.student_batch_id');
    }

    if (!empty($filters['q'])) {
        $q = trim((string) $filters['q']);

        $query->where(function ($sub) use ($q) {
            $sub->where('s.student_no', 'like', "%{$q}%")
                ->orWhere('s.full_name', 'like', "%{$q}%")
                ->orWhere('s.cnic_bform', 'like', "%{$q}%");

            if (Schema::hasColumn('student_enrollments', 'roll_no')) {
                $sub->orWhere('se.roll_no', 'like', "%{$q}%");
            }

            if (Schema::hasColumn('student_enrollments', 'registration_no')) {
                $sub->orWhere('se.registration_no', 'like', "%{$q}%");
            }
        });
    }

    if (!empty($filters['student_status'])) {
    $query->where(function ($sub) use ($filters) {
        if (Schema::hasColumn('students', 'student_status')) {
            $sub->orWhere('s.student_status', $filters['student_status']);
        }

        if (Schema::hasColumn('students', 'status_code')) {
            $sub->orWhere('s.status_code', $filters['student_status']);
        }
    });
}

    if (!empty($filters['lifecycle_status']) && Schema::hasColumn('students', 'lifecycle_status')) {
        $query->where('s.lifecycle_status', $filters['lifecycle_status']);
    }

    if (!empty($filters['program_id']) && Schema::hasColumn('student_enrollments', 'program_id')) {
        $query->where('se.program_id', $filters['program_id']);
    }

    if (!empty($filters['academic_session_id']) && Schema::hasColumn('student_enrollments', 'academic_session_id')) {
        $query->where('se.academic_session_id', $filters['academic_session_id']);
    }

    $students = $query
        ->select($this->lifecycleSelectColumns())
        ->orderByDesc('s.id')
        ->paginate((int) ($filters['per_page'] ?? 20));

    return [
        'students' => $students,
        'actions' => $this->lifecycleActions(),
    ];
}

public function applyLifecycleAction(int $studentId, array $data): array
{
    $tenantId = $this->tenantId();

    $student = DB::table('students')
        ->where('tenant_id', $tenantId)
        ->where('id', $studentId)
        ->first();

    abort_if(!$student, 404, 'Student not found.');

    $actionCode = strtolower(trim((string) $data['action_code']));
    $allowedActions = array_keys($this->lifecycleActions());

    abort_if(!in_array($actionCode, $allowedActions, true), 422, 'Invalid lifecycle action.');

    $effectiveDate = $data['effective_date'] ?? now()->toDateString();

    $currentEnrollment = $this->resolveLifecycleEnrollment($studentId, $data['student_enrollment_id'] ?? null);

    return DB::transaction(function () use (
        $tenantId,
        $student,
        $currentEnrollment,
        $actionCode,
        $effectiveDate,
        $data
    ) {
        return match ($actionCode) {
            'freeze' => $this->freezeStudent($tenantId, $student, $currentEnrollment, $data, $effectiveDate),
            'withdraw' => $this->withdrawStudent($tenantId, $student, $currentEnrollment, $data, $effectiveDate),
            'reactivate' => $this->reactivateStudent($tenantId, $student, $currentEnrollment, $data, $effectiveDate),
            'cancel' => $this->cancelStudent($tenantId, $student, $currentEnrollment, $data, $effectiveDate),
            'transfer' => $this->transferStudent($tenantId, $student, $currentEnrollment, $data, $effectiveDate),
            default => abort(422, 'Invalid lifecycle action.'),
        };
    });
}
public function academicPlacementOptions(array $filters = []): array
{
    $tenantId = $this->tenantId();

    return [
        'programs' => $this->optionPrograms($tenantId, $filters),
        'academic_sessions' => $this->optionAcademicSessions($tenantId, $filters),
        'academic_terms' => $this->optionAcademicTerms($tenantId, $filters),
        'sections' => $this->optionSections($tenantId, $filters),
        'student_batches' => $this->optionStudentBatches($tenantId, $filters),
        'available_courses' => $this->optionAvailableCourses($tenantId, $filters),
    ];
}
    private function studentEnrollments(int $studentId): array
    {
        if (!Schema::hasTable('student_enrollments')) {
            return [];
        }

        $tenantId = $this->tenantId();

        $query = DB::table('student_enrollments as se')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->where('se.tenant_id', $tenantId)
            ->where('s.tenant_id', $tenantId)
            ->where('se.student_id', $studentId);

        $this->joinEnrollmentProgram($query);
        $this->joinEnrollmentAcademicSession($query);
        $this->joinEnrollmentAdmissionConfirmation($query);

        return $query
            ->select($this->enrollmentSelectColumns())
            ->orderByDesc('se.id')
            ->get()
            ->toArray();
    }

    private function studentGuardians(int $studentId): array
    {
        if (!Schema::hasTable('student_guardians')) {
            return [];
        }

        $query = DB::table('student_guardians as sg')
            ->where('sg.tenant_id', $this->tenantId())
            ->where('sg.student_id', $studentId);

        if (Schema::hasTable('guardians') && Schema::hasColumn('student_guardians', 'guardian_id')) {
            $query->leftJoin('guardians as g', 'g.id', '=', 'sg.guardian_id');
        }

        return $query
            ->select([
                'sg.*',
                DB::raw(Schema::hasTable('guardians') ? 'g.name as guardian_name' : 'NULL as guardian_name'),
                DB::raw(Schema::hasTable('guardians') ? 'g.phone as guardian_phone' : 'NULL as guardian_phone'),
                DB::raw(Schema::hasTable('guardians') ? 'g.cnic as guardian_cnic' : 'NULL as guardian_cnic'),
                DB::raw(Schema::hasTable('guardians') ? 'g.email as guardian_email' : 'NULL as guardian_email'),
                DB::raw(Schema::hasTable('guardians') ? 'g.alternate_phone as guardian_alternate_phone' : 'NULL as guardian_alternate_phone'),
                DB::raw(Schema::hasTable('guardians') ? 'g.occupation as occupation' : 'NULL as occupation'),
                DB::raw(Schema::hasTable('guardians') ? 'g.monthly_income as monthly_income' : 'NULL as monthly_income'),
                DB::raw(Schema::hasTable('guardians') ? 'g.address as address' : 'NULL as address'),
            ])
            ->get()
            ->toArray();
    }

    private function studentDocuments(int $studentId): array
    {
        if (!Schema::hasTable('student_documents')) {
            return [];
        }

        return DB::table('student_documents')
            ->where('tenant_id', $this->tenantId())
            ->where('student_id', $studentId)
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }

    private function studentPreviousEducations(int $studentId): array
    {
        if (!Schema::hasTable('student_previous_educations')) {
            return [];
        }

        return DB::table('student_previous_educations')
            ->where('tenant_id', $this->tenantId())
            ->where('student_id', $studentId)
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }

    private function studentStatusHistories(int $studentId): array
    {
        if (!Schema::hasTable('student_status_histories')) {
            return [];
        }

        return DB::table('student_status_histories')
            ->where('tenant_id', $this->tenantId())
            ->where('student_id', $studentId)
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }

    private function joinAdmissionConfirmation($query): void
    {
        if (
            Schema::hasTable('admission_confirmations')
            && Schema::hasColumn('admission_confirmations', 'student_id')
        ) {
            $query->leftJoin('admission_confirmations as ac', function ($join) {
                $join->on('ac.student_id', '=', 's.id');

                if (Schema::hasColumn('admission_confirmations', 'tenant_id')) {
                    $join->whereColumn('ac.tenant_id', 's.tenant_id');
                }
            });
        }
    }

    private function joinApplicant($query): void
    {
        if (
            Schema::hasTable('applicants')
            && Schema::hasTable('admission_confirmations')
            && Schema::hasColumn('admission_confirmations', 'applicant_id')
        ) {
            $query->leftJoin('applicants as a', 'a.id', '=', 'ac.applicant_id');
        }
    }

    private function joinLatestEnrollment($query): void
    {
        if (!Schema::hasTable('student_enrollments')) {
            return;
        }

        $query->leftJoin('student_enrollments as se', function ($join) {
            $join->on('se.student_id', '=', 's.id');

            if (Schema::hasColumn('student_enrollments', 'tenant_id')) {
                $join->whereColumn('se.tenant_id', 's.tenant_id');
            }
        });

        $this->joinEnrollmentProgram($query);
        $this->joinEnrollmentAcademicSession($query);
    }

    private function joinEnrollmentProgram($query): void
    {
        if (
            Schema::hasTable('programs')
            && Schema::hasTable('student_enrollments')
            && Schema::hasColumn('student_enrollments', 'program_id')
        ) {
            $query->leftJoin('programs as p', 'p.id', '=', 'se.program_id');
        }
    }

    private function joinEnrollmentAcademicSession($query): void
    {
        if (
            Schema::hasTable('academic_sessions')
            && Schema::hasTable('student_enrollments')
            && Schema::hasColumn('student_enrollments', 'academic_session_id')
        ) {
            $query->leftJoin('academic_sessions as ases', 'ases.id', '=', 'se.academic_session_id');
        }
    }

    private function joinEnrollmentAdmissionConfirmation($query): void
    {
        if (
            Schema::hasTable('admission_confirmations')
            && Schema::hasColumn('student_enrollments', 'admission_confirmation_id')
        ) {
            $query->leftJoin('admission_confirmations as ac', 'ac.id', '=', 'se.admission_confirmation_id');
        } elseif (
            Schema::hasTable('admission_confirmations')
            && Schema::hasColumn('admission_confirmations', 'student_id')
        ) {
            $query->leftJoin('admission_confirmations as ac', 'ac.student_id', '=', 'se.student_id');
        }
    }

    private function studentSelectColumns(): array
    {
        return [
            's.id',
            's.student_no',
            's.admission_no',
            's.first_name',
            's.last_name',
            's.full_name',
            's.father_name',
            's.cnic_bform',
            's.phone',
            's.email',
            's.gender',
            's.admission_date',
            's.student_status',

            DB::raw(Schema::hasTable('admission_confirmations') ? 'ac.id as admission_confirmation_id' : 'NULL as admission_confirmation_id'),
            DB::raw(Schema::hasTable('admission_confirmations') && Schema::hasColumn('admission_confirmations', 'confirmation_no') ? 'ac.confirmation_no as admission_confirmation_no' : 'NULL as admission_confirmation_no'),
            DB::raw(Schema::hasTable('admission_confirmations') && Schema::hasColumn('admission_confirmations', 'applicant_id') ? 'ac.applicant_id as applicant_id' : 'NULL as applicant_id'),
            DB::raw(Schema::hasTable('applicants') ? 'a.applicant_no as applicant_no' : 'NULL as applicant_no'),

            DB::raw(Schema::hasTable('student_enrollments') ? 'se.id as latest_enrollment_id' : 'NULL as latest_enrollment_id'),
            DB::raw(Schema::hasTable('student_enrollments') && Schema::hasColumn('student_enrollments', 'roll_no') ? 'se.roll_no as roll_no' : 'NULL as roll_no'),
            DB::raw(Schema::hasTable('student_enrollments') && Schema::hasColumn('student_enrollments', 'registration_no') ? 'se.registration_no as registration_no' : 'NULL as registration_no'),
            DB::raw(Schema::hasTable('student_enrollments') && Schema::hasColumn('student_enrollments', 'status') ? 'se.status as enrollment_status' : 'NULL as enrollment_status'),

            DB::raw(Schema::hasTable('programs') ? 'p.name as program_name' : 'NULL as program_name'),
            DB::raw(Schema::hasTable('academic_sessions') ? 'ases.name as academic_session_name' : 'NULL as academic_session_name'),

            DB::raw("
                CASE
                    WHEN " . (Schema::hasTable('admission_confirmations') ? 'ac.id IS NOT NULL' : '0') . "
                    THEN 'Admission Finalization'
                    ELSE 'Manual / Legacy'
                END as source_label
            "),
        ];
    }

    private function enrollmentSelectColumns(): array
    {
        return [
            'se.id',
            'se.student_id',
            's.student_no',
            's.full_name as student_name',
            's.father_name',
            's.cnic_bform',

            DB::raw(Schema::hasColumn('student_enrollments', 'program_id') ? 'se.program_id' : 'NULL as program_id'),
            DB::raw(Schema::hasColumn('student_enrollments', 'academic_session_id') ? 'se.academic_session_id' : 'NULL as academic_session_id'),
            DB::raw(Schema::hasColumn('student_enrollments', 'term_id') ? 'se.term_id' : 'NULL as term_id'),
            DB::raw(Schema::hasColumn('student_enrollments', 'section') ? 'se.section' : 'NULL as section'),
            DB::raw(Schema::hasColumn('student_enrollments', 'roll_no') ? 'se.roll_no' : 'NULL as roll_no'),
            DB::raw(Schema::hasColumn('student_enrollments', 'registration_no') ? 'se.registration_no' : 'NULL as registration_no'),
            DB::raw(Schema::hasColumn('student_enrollments', 'status') ? 'se.status' : 'NULL as status'),

            DB::raw(Schema::hasTable('programs') ? 'p.name as program_name' : 'NULL as program_name'),
            DB::raw(Schema::hasTable('academic_sessions') ? 'ases.name as academic_session_name' : 'NULL as academic_session_name'),

            DB::raw(Schema::hasTable('admission_confirmations') ? 'ac.id as admission_confirmation_id' : 'NULL as admission_confirmation_id'),
            DB::raw(Schema::hasTable('admission_confirmations') && Schema::hasColumn('admission_confirmations', 'confirmation_no') ? 'ac.confirmation_no as admission_confirmation_no' : 'NULL as admission_confirmation_no'),
        ];
    }
private function allocationBatches(array $filters): array
{
    if (!Schema::hasTable('student_batches')) {
        return [];
    }

    $tenantId = $this->tenantId();

    $query = DB::table('student_batches')
        ->where('tenant_id', $tenantId);

    if (!empty($filters['program_id']) && Schema::hasColumn('student_batches', 'program_id')) {
        $query->where('program_id', $filters['program_id']);
    }

    if (!empty($filters['academic_session_id']) && Schema::hasColumn('student_batches', 'academic_session_id')) {
        $query->where('academic_session_id', $filters['academic_session_id']);
    }

    if (Schema::hasColumn('student_batches', 'status')) {
        $query->whereIn('status', ['active', 'open', 'running']);
    }

    return $query
        ->select([
            'id',
            'code',
            'name',
            'academic_session_id',
            'program_id',
            'capacity',
            'shift',
            'status',
        ])
        ->orderByDesc('id')
        ->get()
        ->toArray();
}

private function existingSections(array $filters): array
{
    if (!Schema::hasTable('student_enrollments') || !Schema::hasColumn('student_enrollments', 'section')) {
        return [];
    }

    $tenantId = $this->tenantId();

    $query = DB::table('student_enrollments')
        ->where('tenant_id', $tenantId)
        ->whereNotNull('section')
        ->where('section', '!=', '');

    if (!empty($filters['program_id']) && Schema::hasColumn('student_enrollments', 'program_id')) {
        $query->where('program_id', $filters['program_id']);
    }

    if (!empty($filters['academic_session_id']) && Schema::hasColumn('student_enrollments', 'academic_session_id')) {
        $query->where('academic_session_id', $filters['academic_session_id']);
    }

    return $query
        ->select('section')
        ->distinct()
        ->orderBy('section')
        ->pluck('section')
        ->values()
        ->toArray();
}

private function allocationSelectColumns(): array
{
    return [
        'se.id',
        'se.student_id',
        DB::raw(Schema::hasColumn('students', 'student_no') ? 's.student_no as student_no' : 'CAST(s.id AS CHAR) as student_no'),
        DB::raw(Schema::hasColumn('students', 'full_name') ? 's.full_name as student_name' : "CONCAT(COALESCE(s.first_name,''), ' ', COALESCE(s.last_name,'')) as student_name"),
        DB::raw(Schema::hasColumn('students', 'father_name') ? 's.father_name as father_name' : 'NULL as father_name'),
        DB::raw(Schema::hasColumn('students', 'cnic_bform') ? 's.cnic_bform as cnic_bform' : 'NULL as cnic_bform'),
        DB::raw(Schema::hasColumn('student_enrollments', 'program_id') ? 'se.program_id' : 'NULL as program_id'),
        DB::raw(Schema::hasColumn('student_enrollments', 'academic_session_id') ? 'se.academic_session_id' : 'NULL as academic_session_id'),
        DB::raw(Schema::hasColumn('student_enrollments', 'student_batch_id') ? 'se.student_batch_id' : 'NULL as student_batch_id'),
        DB::raw(Schema::hasColumn('student_enrollments', 'section') ? 'se.section' : 'NULL as section'),
        DB::raw(Schema::hasColumn('student_enrollments', 'roll_no') ? 'se.roll_no' : 'NULL as roll_no'),
        DB::raw(Schema::hasColumn('student_enrollments', 'registration_no') ? 'se.registration_no' : 'NULL as registration_no'),
        DB::raw(Schema::hasColumn('student_enrollments', 'roll_sequence_no') ? 'se.roll_sequence_no' : 'NULL as roll_sequence_no'),
        DB::raw(Schema::hasColumn('student_enrollments', 'allocation_status') ? 'se.allocation_status' : 'NULL as allocation_status'),
        DB::raw(Schema::hasColumn('student_enrollments', 'enrollment_status_code') ? 'se.enrollment_status_code as enrollment_status' : (Schema::hasColumn('student_enrollments', 'status_code') ? 'se.status_code as enrollment_status' : (Schema::hasColumn('student_enrollments', 'status') ? 'se.status as enrollment_status' : 'NULL as enrollment_status'))),
        DB::raw(Schema::hasTable('student_batches') && Schema::hasColumn('student_batches', 'code') ? 'sb.code as batch_code' : 'NULL as batch_code'),
        DB::raw(Schema::hasTable('student_batches') && Schema::hasColumn('student_batches', 'name') ? 'sb.name as batch_name' : 'NULL as batch_name'),
        DB::raw(Schema::hasTable('programs') && Schema::hasColumn('programs', 'name') ? 'p.name as program_name' : 'NULL as program_name'),
        DB::raw(Schema::hasTable('academic_sessions') && Schema::hasColumn('academic_sessions', 'name') ? 'ases.name as academic_session_name' : 'NULL as academic_session_name'),
    ];
}
private function lifecycleActions(): array
{
    return [
        'freeze' => [
            'label' => 'Freeze',
            'student_status' => 'frozen',
            'enrollment_status' => 'frozen',
            'description' => 'Temporarily freeze student academic activity.',
        ],
        'withdraw' => [
            'label' => 'Withdraw',
            'student_status' => 'withdrawn',
            'enrollment_status' => 'withdrawn',
            'description' => 'Withdraw student from the current academic program/session.',
        ],
        'transfer' => [
            'label' => 'Transfer',
            'student_status' => 'active',
            'enrollment_status' => 'transferred',
            'description' => 'Close current enrollment and create/move to another academic placement.',
        ],
        'reactivate' => [
            'label' => 'Reactivate',
            'student_status' => 'active',
            'enrollment_status' => 'active',
            'description' => 'Reactivate a frozen/inactive/withdrawn student.',
        ],
        'cancel' => [
            'label' => 'Cancel',
            'student_status' => 'cancelled',
            'enrollment_status' => 'cancelled',
            'description' => 'Cancel student enrollment record.',
        ],
    ];
}

private function resolveLifecycleEnrollment(int $studentId, ?int $enrollmentId): ?object
{
    if (!Schema::hasTable('student_enrollments')) {
        return null;
    }

    $query = DB::table('student_enrollments')
        ->where('tenant_id', $this->tenantId())
        ->where('student_id', $studentId);

    if ($enrollmentId) {
        $query->where('id', $enrollmentId);
    }

    return $query
        ->orderByDesc('id')
        ->first();
}

private function freezeStudent(
    int $tenantId,
    object $student,
    ?object $enrollment,
    array $data,
    string $effectiveDate
): array {
    $this->updateStudentLifecycle($tenantId, $student, 'frozen', 'freeze', $data, $effectiveDate);

    if ($enrollment) {
        $this->updateEnrollmentLifecycle($tenantId, $enrollment, 'frozen', 'freeze', $data, $effectiveDate);
    }

    $this->insertStatusHistory($tenantId, $student->id, $student->student_status ?? null, 'frozen', 'freeze', $data, $effectiveDate);

    return [
        'student_id' => $student->id,
        'action' => 'freeze',
        'new_status' => 'frozen',
    ];
}

private function withdrawStudent(
    int $tenantId,
    object $student,
    ?object $enrollment,
    array $data,
    string $effectiveDate
): array {
    $this->updateStudentLifecycle($tenantId, $student, 'withdrawn', 'withdraw', $data, $effectiveDate);

    if ($enrollment) {
        $this->updateEnrollmentLifecycle($tenantId, $enrollment, 'withdrawn', 'withdraw', $data, $effectiveDate);
    }

    $this->insertStatusHistory($tenantId, $student->id, $student->student_status ?? null, 'withdrawn', 'withdraw', $data, $effectiveDate);

    return [
        'student_id' => $student->id,
        'action' => 'withdraw',
        'new_status' => 'withdrawn',
    ];
}

private function reactivateStudent(
    int $tenantId,
    object $student,
    ?object $enrollment,
    array $data,
    string $effectiveDate
): array {
    $this->updateStudentLifecycle($tenantId, $student, 'active', 'reactivate', $data, $effectiveDate);

    if ($enrollment) {
        $this->updateEnrollmentLifecycle($tenantId, $enrollment, 'active', 'reactivate', $data, $effectiveDate);
    }

    $this->insertStatusHistory($tenantId, $student->id, $student->student_status ?? null, 'active', 'reactivate', $data, $effectiveDate);

    return [
        'student_id' => $student->id,
        'action' => 'reactivate',
        'new_status' => 'active',
    ];
}

private function cancelStudent(
    int $tenantId,
    object $student,
    ?object $enrollment,
    array $data,
    string $effectiveDate
): array {
    $this->updateStudentLifecycle($tenantId, $student, 'cancelled', 'cancel', $data, $effectiveDate);

    if ($enrollment) {
        $this->updateEnrollmentLifecycle($tenantId, $enrollment, 'cancelled', 'cancel', $data, $effectiveDate);
    }

    $this->insertStatusHistory($tenantId, $student->id, $student->student_status ?? null, 'cancelled', 'cancel', $data, $effectiveDate);

    return [
        'student_id' => $student->id,
        'action' => 'cancel',
        'new_status' => 'cancelled',
    ];
}

private function transferStudent(
    int $tenantId,
    object $student,
    ?object $enrollment,
    array $data,
    string $effectiveDate
): array {
    abort_if(!$enrollment, 422, 'Current enrollment is required for transfer.');

    $targetProgramId = $data['target_program_id'] ?? null;
    $targetAcademicSessionId = $data['target_academic_session_id'] ?? null;

    abort_if(!$targetProgramId, 422, 'Target program is required for transfer.');
    abort_if(!$targetAcademicSessionId, 422, 'Target academic session is required for transfer.');

    $this->updateEnrollmentLifecycle($tenantId, $enrollment, 'transferred', 'transfer', $data, $effectiveDate);

    $newEnrollmentPayload = [
        'tenant_id' => $tenantId,
        'student_id' => $student->id,
    ];

    foreach ([
        'program_id' => $targetProgramId,
        'academic_session_id' => $targetAcademicSessionId,
        'term_id' => $data['target_term_id'] ?? null,
        'section' => $data['target_section'] ?? null,
        'student_batch_id' => $data['target_student_batch_id'] ?? null,
        'status' => 'active',
        'lifecycle_status' => 'active',
        'lifecycle_reason' => $data['reason'] ?? null,
        'lifecycle_effective_date' => $effectiveDate,
        'lifecycle_action_at' => now(),
        'lifecycle_action_by' => auth()->id(),
        'transfer_from_enrollment_id' => $enrollment->id,
        'transfer_remarks' => $data['remarks'] ?? null,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ] as $column => $value) {
        if (Schema::hasColumn('student_enrollments', $column)) {
            $newEnrollmentPayload[$column] = $value;
        }
    }

    $newEnrollmentPayload['created_at'] = now();
    $newEnrollmentPayload['updated_at'] = now();

    $newEnrollmentId = DB::table('student_enrollments')->insertGetId($newEnrollmentPayload);

    $this->updateStudentLifecycle($tenantId, $student, 'active', 'transfer', $data, $effectiveDate);

    $this->insertStatusHistory($tenantId, $student->id, $student->student_status ?? null, 'active', 'transfer', $data, $effectiveDate);

    return [
        'student_id' => $student->id,
        'action' => 'transfer',
        'old_enrollment_id' => $enrollment->id,
        'new_enrollment_id' => $newEnrollmentId,
        'new_status' => 'active',
    ];
}

private function updateStudentLifecycle(
    int $tenantId,
    object $student,
    string $newStatus,
    string $actionCode,
    array $data,
    string $effectiveDate
): void {
    $payload = [];

    if (Schema::hasColumn('students', 'student_status')) {
        $payload['student_status'] = $newStatus;
    }

    if (Schema::hasColumn('students', 'lifecycle_status')) {
        $payload['lifecycle_status'] = $actionCode;
    }

    if (Schema::hasColumn('students', 'lifecycle_reason')) {
        $payload['lifecycle_reason'] = $data['reason'] ?? null;
    }

    if (Schema::hasColumn('students', 'lifecycle_effective_date')) {
        $payload['lifecycle_effective_date'] = $effectiveDate;
    }

    if (Schema::hasColumn('students', 'lifecycle_action_at')) {
        $payload['lifecycle_action_at'] = now();
    }

    if (Schema::hasColumn('students', 'lifecycle_action_by')) {
        $payload['lifecycle_action_by'] = auth()->id();
    }

    if (Schema::hasColumn('students', 'updated_by')) {
        $payload['updated_by'] = auth()->id();
    }

    $payload['updated_at'] = now();

    DB::table('students')
        ->where('tenant_id', $tenantId)
        ->where('id', $student->id)
        ->update($payload);
}

private function updateEnrollmentLifecycle(
    int $tenantId,
    object $enrollment,
    string $newStatus,
    string $actionCode,
    array $data,
    string $effectiveDate
): void {
    $payload = [];

    if (Schema::hasColumn('student_enrollments', 'status')) {
        $payload['status'] = $newStatus;
    }

    if (Schema::hasColumn('student_enrollments', 'lifecycle_status')) {
        $payload['lifecycle_status'] = $actionCode;
    }

    if (Schema::hasColumn('student_enrollments', 'lifecycle_reason')) {
        $payload['lifecycle_reason'] = $data['reason'] ?? null;
    }

    if (Schema::hasColumn('student_enrollments', 'lifecycle_effective_date')) {
        $payload['lifecycle_effective_date'] = $effectiveDate;
    }

    if (Schema::hasColumn('student_enrollments', 'lifecycle_action_at')) {
        $payload['lifecycle_action_at'] = now();
    }

    if (Schema::hasColumn('student_enrollments', 'lifecycle_action_by')) {
        $payload['lifecycle_action_by'] = auth()->id();
    }

    if (Schema::hasColumn('student_enrollments', 'updated_by')) {
        $payload['updated_by'] = auth()->id();
    }

    $payload['updated_at'] = now();

    DB::table('student_enrollments')
        ->where('tenant_id', $tenantId)
        ->where('id', $enrollment->id)
        ->update($payload);
}

private function insertStatusHistory(
    int $tenantId,
    int $studentId,
    ?string $oldStatus,
    string $newStatus,
    string $actionCode,
    array $data,
    string $effectiveDate
): void {
    if (!Schema::hasTable('student_status_histories')) {
        return;
    }

    $payload = [
        'tenant_id' => $tenantId,
        'student_id' => $studentId,
        'from_status' => $oldStatus,
        'to_status' => $newStatus,
        'reason' => $data['reason'] ?? null,
        'effective_date' => $effectiveDate,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('student_status_histories', 'action_code')) {
        $payload['action_code'] = $actionCode;
    }

    if (Schema::hasColumn('student_status_histories', 'remarks')) {
        $payload['remarks'] = $data['remarks'] ?? null;
    }

    if (Schema::hasColumn('student_status_histories', 'created_by')) {
        $payload['created_by'] = auth()->id();
    }

    DB::table('student_status_histories')->insert($payload);
}

private function lifecycleSelectColumns(): array
{
    return [
        's.id as student_id',
        DB::raw(Schema::hasColumn('students', 'student_no') ? 's.student_no as student_no' : 'CAST(s.id AS CHAR) as student_no'),
        DB::raw(Schema::hasColumn('students', 'full_name') ? 's.full_name as student_name' : "CONCAT(COALESCE(s.first_name,''), ' ', COALESCE(s.last_name,'')) as student_name"),
        DB::raw(Schema::hasColumn('students', 'father_name') ? 's.father_name as father_name' : 'NULL as father_name'),
        DB::raw(Schema::hasColumn('students', 'cnic_bform') ? 's.cnic_bform as cnic_bform' : 'NULL as cnic_bform'),
        DB::raw(Schema::hasColumn('students', 'student_status') ? 's.student_status as student_status' : (Schema::hasColumn('students', 'status_code') ? 's.status_code as student_status' : 'NULL as student_status')),
        DB::raw(Schema::hasColumn('students', 'lifecycle_status') ? 's.lifecycle_status as lifecycle_status' : 'NULL as lifecycle_status'),
        DB::raw(Schema::hasColumn('students', 'lifecycle_reason') ? 's.lifecycle_reason as lifecycle_reason' : 'NULL as lifecycle_reason'),
        DB::raw(Schema::hasColumn('students', 'lifecycle_effective_date') ? 's.lifecycle_effective_date as lifecycle_effective_date' : 'NULL as lifecycle_effective_date'),
        DB::raw(Schema::hasTable('student_enrollments') ? 'se.id as student_enrollment_id' : 'NULL as student_enrollment_id'),
        DB::raw(Schema::hasColumn('student_enrollments', 'program_id') ? 'se.program_id' : 'NULL as program_id'),
        DB::raw(Schema::hasColumn('student_enrollments', 'academic_session_id') ? 'se.academic_session_id' : 'NULL as academic_session_id'),
        DB::raw(Schema::hasColumn('student_enrollments', 'term_id') ? 'se.term_id' : 'NULL as term_id'),
        DB::raw(Schema::hasColumn('student_enrollments', 'section') ? 'se.section' : 'NULL as section'),
        DB::raw(Schema::hasColumn('student_enrollments', 'roll_no') ? 'se.roll_no' : 'NULL as roll_no'),
        DB::raw(Schema::hasColumn('student_enrollments', 'registration_no') ? 'se.registration_no' : 'NULL as registration_no'),
        DB::raw(Schema::hasColumn('student_enrollments', 'enrollment_status_code') ? 'se.enrollment_status_code as enrollment_status' : (Schema::hasColumn('student_enrollments', 'status_code') ? 'se.status_code as enrollment_status' : (Schema::hasColumn('student_enrollments', 'status') ? 'se.status as enrollment_status' : (Schema::hasColumn('student_enrollments', 'allocation_status') ? 'se.allocation_status as enrollment_status' : 'NULL as enrollment_status')))),
        DB::raw(Schema::hasColumn('student_enrollments', 'lifecycle_status') ? 'se.lifecycle_status as enrollment_lifecycle_status' : 'NULL as enrollment_lifecycle_status'),
        DB::raw(Schema::hasTable('programs') && Schema::hasColumn('programs', 'name') ? 'p.name as program_name' : 'NULL as program_name'),
        DB::raw(Schema::hasTable('academic_sessions') && Schema::hasColumn('academic_sessions', 'name') ? 'ases.name as academic_session_name' : 'NULL as academic_session_name'),
        DB::raw(Schema::hasTable('student_batches') && Schema::hasColumn('student_batches', 'name') ? 'sb.name as batch_name' : 'NULL as batch_name'),
    ];
}

private function optionPrograms(int $tenantId, array $filters): array
{
    if (!Schema::hasTable('programs')) {
        return [];
    }

    $query = DB::table('programs');

    if (Schema::hasColumn('programs', 'tenant_id')) {
        $query->where('tenant_id', $tenantId);
    }

    return $query
        ->select([
            'id',
            DB::raw(Schema::hasColumn('programs', 'name') ? 'name as label' : 'id as label'),
        ])
        ->orderBy('label')
        ->get()
        ->toArray();
}

private function optionAcademicSessions(int $tenantId, array $filters): array
{
    if (!Schema::hasTable('academic_sessions')) {
        return [];
    }

    $query = DB::table('academic_sessions');

    if (Schema::hasColumn('academic_sessions', 'tenant_id')) {
        $query->where('tenant_id', $tenantId);
    }

    return $query
        ->select([
            'id',
            DB::raw(Schema::hasColumn('academic_sessions', 'name') ? 'name as label' : 'id as label'),
        ])
        ->orderByDesc('id')
        ->get()
        ->toArray();
}

private function optionAcademicTerms(int $tenantId, array $filters): array
{
    $table = null;

    foreach (['academic_terms', 'program_terms', 'terms'] as $candidate) {
        if (Schema::hasTable($candidate)) {
            $table = $candidate;
            break;
        }
    }

    if (!$table) {
        return [];
    }

    $query = DB::table($table);

    if (Schema::hasColumn($table, 'tenant_id')) {
        $query->where('tenant_id', $tenantId);
    }

    if (!empty($filters['program_id']) && Schema::hasColumn($table, 'program_id')) {
        $query->where('program_id', $filters['program_id']);
    }

    if (!empty($filters['academic_session_id']) && Schema::hasColumn($table, 'academic_session_id')) {
        $query->where('academic_session_id', $filters['academic_session_id']);
    }

    $labelExpression = Schema::hasColumn($table, 'name')
        ? 'name as label'
        : (Schema::hasColumn($table, 'term_name') ? 'term_name as label' : 'id as label');

    return $query
        ->select([
            'id',
            DB::raw($labelExpression),
        ])
        ->orderBy('id')
        ->get()
        ->toArray();
}

private function optionSections(int $tenantId, array $filters): array
{
    foreach (['sections', 'academic_sections', 'class_sections', 'program_sections'] as $table) {
        if (!Schema::hasTable($table)) {
            continue;
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if (!empty($filters['program_id']) && Schema::hasColumn($table, 'program_id')) {
            $query->where('program_id', $filters['program_id']);
        }

        if (!empty($filters['academic_session_id']) && Schema::hasColumn($table, 'academic_session_id')) {
            $query->where('academic_session_id', $filters['academic_session_id']);
        }

        $labelExpression = Schema::hasColumn($table, 'name')
            ? 'name as label'
            : (Schema::hasColumn($table, 'section_name') ? 'section_name as label' : 'id as label');

        $codeExpression = Schema::hasColumn($table, 'code')
            ? 'code'
            : (Schema::hasColumn($table, 'section_code') ? 'section_code as code' : 'NULL as code');

        return $query
            ->select([
                'id',
                DB::raw($labelExpression),
                DB::raw($codeExpression),
            ])
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'label' => $row->label,
                'code' => $row->code ?? $row->label,
                'value' => $row->code ?? $row->label,
            ])
            ->values()
            ->toArray();
    }

    if (Schema::hasTable('student_enrollments') && Schema::hasColumn('student_enrollments', 'section')) {
        return DB::table('student_enrollments')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('section')
            ->where('section_id', $filters['section_id'] ?? null)
            ->select('section')
            ->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => [
                'id' => $section,
                'label' => $section,
                'code' => $section,
                'value' => $section,
            ])
            ->values()
            ->toArray();
    }

    return [];
}

private function optionStudentBatches(int $tenantId, array $filters): array
{
    if (!Schema::hasTable('student_batches')) {
        return [];
    }

    $query = DB::table('student_batches');

    if (Schema::hasColumn('student_batches', 'tenant_id')) {
        $query->where('tenant_id', $tenantId);
    }

    if (!empty($filters['program_id']) && Schema::hasColumn('student_batches', 'program_id')) {
        $query->where('program_id', $filters['program_id']);
    }

    if (!empty($filters['academic_session_id']) && Schema::hasColumn('student_batches', 'academic_session_id')) {
        $query->where('academic_session_id', $filters['academic_session_id']);
    }

    $labelExpression = Schema::hasColumn('student_batches', 'name')
        ? 'name as label'
        : (Schema::hasColumn('student_batches', 'code') ? 'code as label' : 'id as label');

    return $query
        ->select([
            'id',
            DB::raw($labelExpression),
            DB::raw(Schema::hasColumn('student_batches', 'code') ? 'code' : 'NULL as code'),
            DB::raw(Schema::hasColumn('student_batches', 'capacity') ? 'capacity' : 'NULL as capacity'),
        ])
        ->orderByDesc('id')
        ->get()
        ->toArray();
}

private function optionAvailableCourses(int $tenantId, array $filters): array
{
    if (!Schema::hasTable('curriculum_subjects')) {
        return [];
    }

    $query = DB::table('curriculum_subjects as cs');

    if (Schema::hasColumn('curriculum_subjects', 'tenant_id')) {
        $query->where('cs.tenant_id', $tenantId);
    }

    if (!empty($filters['program_id']) && Schema::hasColumn('curriculum_subjects', 'program_id')) {
        $query->where('cs.program_id', $filters['program_id']);
    }

    if (!empty($filters['academic_term_id']) && Schema::hasColumn('curriculum_subjects', 'academic_term_id')) {
        $query->where('cs.academic_term_id', $filters['academic_term_id']);
    }

    if (Schema::hasTable('subjects') && Schema::hasColumn('curriculum_subjects', 'subject_id')) {
        $query->leftJoin('subjects as sub', 'sub.id', '=', 'cs.subject_id');
    }

    $codeExpr = Schema::hasColumn('curriculum_subjects', 'subject_code')
        ? 'cs.subject_code as course_code'
        : (Schema::hasColumn('curriculum_subjects', 'course_code')
            ? 'cs.course_code as course_code'
            : (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'code') ? 'sub.code as course_code' : 'NULL as course_code'));

    $titleExpr = Schema::hasColumn('curriculum_subjects', 'subject_name')
        ? 'cs.subject_name as course_title'
        : (Schema::hasColumn('curriculum_subjects', 'course_title')
            ? 'cs.course_title as course_title'
            : (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'name') ? 'sub.name as course_title' : 'NULL as course_title'));

    return $query
        ->select([
            'cs.id',
            DB::raw(Schema::hasColumn('curriculum_subjects', 'subject_id') ? 'cs.subject_id' : 'NULL as subject_id'),
            DB::raw($codeExpr),
            DB::raw($titleExpr),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'credit_hours') ? 'cs.credit_hours' : (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'credit_hours') ? 'sub.credit_hours' : '0 as credit_hours')),
        ])
        ->orderBy('course_code')
        ->get()
        ->map(fn ($row) => [
            'id' => $row->id,
            'label' => trim(($row->course_code ?? '') . ' - ' . ($row->course_title ?? '')),
            'subject_id' => $row->subject_id,
            'course_code' => $row->course_code ?? $row->subject_code ?? null,
'course_title' => $row->course_title ?? $row->subject_name ?? null,
            'credit_hours' => $row->credit_hours,
        ])
        ->values()
        ->toArray();
}
public function courseRegistrationContext(int $studentId): array
{
    $tenantId = $this->tenantId();

    $student = DB::table('students')
        ->where('tenant_id', $tenantId)
        ->where('id', $studentId)
        ->first();

    abort_if(!$student, 404, 'Student not found.');

    $enrollment = DB::table('student_enrollments')
        ->where('tenant_id', $tenantId)
        ->where('student_id', $studentId)
        ->orderByDesc('id')
        ->first();

    abort_if(!$enrollment, 404, 'Student enrollment not found.');

    return [
        'student' => $student,
        'current_enrollment' => $enrollment,
        'registered_courses' => $this->registeredCourses($studentId),
    ];
}
public function availableCourses(int $studentId, array $filters): array
{
    $tenantId = $this->tenantId();

    abort_if(!Schema::hasTable('student_enrollments'), 404, 'Student enrollments table not found.');
    abort_if(!Schema::hasTable('curriculum_subjects'), 404, 'Curriculum subjects table not found.');

    $student = DB::table('students')->where('tenant_id', $tenantId)->where('id', $studentId)->first();
    abort_if(!$student, 404, 'Student not found.');

    $enrollment = DB::table('student_enrollments')
        ->where('tenant_id', $tenantId)
        ->where('student_id', $studentId)
        ->when(!empty($filters['student_enrollment_id']), fn ($q) => $q->where('id', $filters['student_enrollment_id']))
        ->orderByDesc('id')
        ->first();

    abort_if(!$enrollment, 404, 'Student enrollment not found.');

    $query = DB::table('curriculum_subjects as cs');

    if (Schema::hasColumn('curriculum_subjects', 'tenant_id')) {
        $query->where('cs.tenant_id', $tenantId);
    }

    if (!empty($enrollment->program_id) && Schema::hasColumn('curriculum_subjects', 'program_id')) {
        $query->where('cs.program_id', $enrollment->program_id);
    }

    if (!empty($filters['academic_term_id']) && Schema::hasColumn('curriculum_subjects', 'academic_term_id')) {
        $query->where('cs.academic_term_id', $filters['academic_term_id']);
    }

    if (!empty($filters['term_no']) && Schema::hasColumn('curriculum_subjects', 'term_number')) {
        $query->where('cs.term_number', $filters['term_no']);
    }

    if (Schema::hasTable('subjects') && Schema::hasColumn('curriculum_subjects', 'subject_id')) {
        $query->leftJoin('subjects as sub', 'sub.id', '=', 'cs.subject_id');
    }

    $registered = Schema::hasTable('student_course_registrations')
        ? DB::table('student_course_registrations')
            ->where('tenant_id', $tenantId)
            ->where('student_enrollment_id', $enrollment->id)
            ->whereIn('status', ['registered', 'approved', 'active'])
            ->get()
        : collect();

    $registeredSubjectIds = $registered->pluck('subject_id')->filter()->values()->toArray();
    $registeredCurriculumSubjectIds = $registered->pluck('curriculum_subject_id')->filter()->values()->toArray();

    $codeExpr = Schema::hasColumn('curriculum_subjects', 'subject_code')
        ? 'cs.subject_code as course_code'
        : (Schema::hasColumn('curriculum_subjects', 'course_code') ? 'cs.course_code as course_code' : (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'code') ? 'sub.code as course_code' : 'NULL as course_code'));

    $titleExpr = Schema::hasColumn('curriculum_subjects', 'subject_name')
        ? 'cs.subject_name as course_title'
        : (Schema::hasColumn('curriculum_subjects', 'course_title') ? 'cs.course_title as course_title' : (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'name') ? 'sub.name as course_title' : 'NULL as course_title'));

    $rows = $query
        ->select([
            'cs.id as curriculum_subject_id',
            DB::raw(Schema::hasColumn('curriculum_subjects', 'curriculum_id') ? 'cs.curriculum_id' : 'NULL as curriculum_id'),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'subject_code') ? 'cs.subject_code as course_code' : 'NULL as course_code'),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'subject_name') ? 'cs.subject_name as course_title' : 'NULL as course_title'),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'subject_id') ? 'cs.subject_id' : 'NULL as subject_id'),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'program_id') ? 'cs.program_id' : 'NULL as program_id'),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'academic_term_id') ? 'cs.academic_term_id' : 'NULL as academic_term_id'),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'term_number') ? 'cs.term_number as term_no' : 'NULL as term_no'),
            DB::raw($codeExpr),
            DB::raw($titleExpr),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'credit_hours') ? 'cs.credit_hours' : (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'credit_hours') ? 'sub.credit_hours' : '0 as credit_hours')),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'subject_nature') ? 'cs.subject_nature as subject_type_code' : 'NULL as subject_type_code'),
        ])
        ->orderBy('term_no')
        ->orderBy('course_code')
        ->get();

    return [
        'student' => $student,
        'enrollment' => $enrollment,
        'courses' => $rows->map(fn ($row) => [
            'curriculum_subject_id' => $row->curriculum_subject_id,
            'curriculum_id' => $row->curriculum_id,
            'subject_id' => $row->subject_id ?? null,
            'program_id' => $row->program_id,
            'academic_term_id' => $row->academic_term_id,
            'term_no' => $row->term_no ?? $row->term_number ?? null,
            'course_code' => $row->course_code ?? $row->subject_code ?? null,
'course_title' => $row->course_title ?? $row->subject_name ?? null,
            'credit_hours' => $row->credit_hours,
            'subject_type_code' => $row->subject_type_code ?? $row->subject_nature ?? null,
            'already_registered' => in_array($row->curriculum_subject_id, $registeredCurriculumSubjectIds, true)
                || (!empty($row->subject_id) && in_array($row->subject_id, $registeredSubjectIds, true)),
        ])->values()->toArray(),
    ];
}

public function registeredCourses(int $studentId): array
{
    $tenantId = $this->tenantId();

    if (!Schema::hasTable('student_course_registrations')) {
        return [];
    }

    return DB::table('student_course_registrations as scr')
        ->where('scr.tenant_id', $tenantId)
        ->where('scr.student_id', $studentId)
        ->orderByDesc('scr.id')
        ->get()
        ->toArray();
}

public function registerCourses(int $studentId, array $data): array
{
    $tenantId = $this->tenantId();

    $student = DB::table('students')
        ->where('tenant_id', $tenantId)
        ->where('id', $studentId)
        ->first();

    abort_if(!$student, 404, 'Student not found.');

    $enrollment = DB::table('student_enrollments')
        ->where('tenant_id', $tenantId)
        ->where('student_id', $studentId)
        ->where('id', $data['student_enrollment_id'])
        ->first();

    abort_if(!$enrollment, 404, 'Student enrollment not found.');

    $registrationType = $data['registration_type'] ?? 'regular';

    $created = [];

    DB::transaction(function () use ($tenantId, $studentId, $enrollment, $data, $registrationType, &$created) {
        foreach ($data['curriculum_subject_ids'] as $curriculumSubjectId) {
            $curriculumSubject = DB::table('curriculum_subjects')
                ->where('tenant_id', $tenantId)
                ->where('id', $curriculumSubjectId)
                ->first();

            abort_if(!$curriculumSubject, 404, 'Curriculum subject not found.');

            $subjectId = $curriculumSubject->subject_id ?? null;

            if ($subjectId) {
                $exists = DB::table('student_course_registrations')
                    ->where('tenant_id', $tenantId)
                    ->where('student_enrollment_id', $enrollment->id)
                    ->where('subject_id', $subjectId)
                    ->where('registration_type', $registrationType)
                    ->whereIn('status', ['registered', 'approved'])
                    ->exists();

                abort_if($exists, 422, 'Subject is already registered for this student enrollment.');
            }

            $subject = null;

            if ($subjectId && Schema::hasTable('subjects')) {
                $subject = DB::table('subjects')
                    ->where('id', $subjectId)
                    ->first();
            }

            $registration = StudentCourseRegistration::create([
                'tenant_id' => $tenantId,
                'student_id' => $studentId,
                'student_enrollment_id' => $enrollment->id,

                'program_id' => $enrollment->program_id ?? ($curriculumSubject->program_id ?? null),
                'academic_session_id' => $enrollment->academic_session_id ?? null,
                'academic_term_id' => $curriculumSubject->academic_term_id ?? null,
                'term_id' => $curriculumSubject->term_id ?? ($enrollment->term_id ?? null),

                'curriculum_id' => $curriculumSubject->curriculum_id ?? null,
                'curriculum_subject_id' => $curriculumSubject->id,
                'subject_id' => $subjectId,

                'course_code' => $curriculumSubject->course_code ?? ($subject->code ?? null),
                'course_title' => $curriculumSubject->course_title ?? ($subject->name ?? null),
                'credit_hours' => $curriculumSubject->credit_hours ?? ($subject->credit_hours ?? 0),
                'subject_type_code' => $curriculumSubject->subject_type_code ?? null,

                'registration_type' => $registrationType,
                'status' => 'registered',
                'is_locked' => false,
                'registered_at' => now(),
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $created[] = $registration;
        }
    });

    return [
        'student_id' => $studentId,
        'student_enrollment_id' => $enrollment->id,
        'created_count' => count($created),
        'registrations' => $created,
    ];
}

public function unregisterCourse(int $registrationId): array
{
    $tenantId = $this->tenantId();

    $registration = StudentCourseRegistration::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $registrationId)
        ->first();

    abort_if(!$registration, 404, 'Course registration not found.');

    abort_if(
        (bool) $registration->is_locked,
        422,
        'Locked course registration cannot be removed.'
    );

    $registration->update([
        'status' => 'cancelled',
        'updated_by' => auth()->id(),
    ]);

    return [
        'registration_id' => $registrationId,
        'status' => 'cancelled',
    ];
}
public function bulkCourseRegistrationContext(array $filters = []): array
{
    $tenantId = $this->tenantId();

    return [
        'academic_sessions' => $this->optionAcademicSessions($tenantId, $filters),
        'programs' => $this->optionPrograms($tenantId, $filters),
        'academic_terms' => $this->optionAcademicTerms($tenantId, $filters),
        'student_batches' => $this->optionStudentBatches($tenantId, $filters),
        'sections' => $this->optionSections($tenantId, $filters),
        'settings' => $this->courseRegistrationSettings($filters),
    ];
}
public function previewBulkCourseRegistration(array $filters): array
{
    $tenantId = $this->tenantId();

    $students = $this->studentsForBulkCourseRegistration($tenantId, $filters);
    $subjects = $this->curriculumSubjectsForBulkCourseRegistration($tenantId, $filters);

    $enrollmentIds = collect($students)->pluck('student_enrollment_id')->filter()->values()->all();
    $subjectIds = collect($subjects)->pluck('curriculum_subject_id')->filter()->values()->all();

    $existing = [];

    if (
        Schema::hasTable('student_course_registrations') &&
        !empty($enrollmentIds) &&
        !empty($subjectIds)
    ) {
        $existing = DB::table('student_course_registrations')
            ->where('tenant_id', $tenantId)
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->whereIn('curriculum_subject_id', $subjectIds)
            ->whereIn('status', ['pending', 'registered', 'approved', 'completed'])
            ->get()
            ->map(fn ($row) => $row->student_enrollment_id . ':' . $row->curriculum_subject_id)
            ->toArray();
    }

    $totalPossible = count($students) * count($subjects);
    $alreadyRegistered = count($existing);

    return [
        'students' => $students,
        'subjects' => $subjects,
        'summary' => [
            'students_count' => count($students),
            'subjects_count' => count($subjects),
            'total_possible_registrations' => $totalPossible,
            'already_registered_count' => $alreadyRegistered,
            'missing_registrations_count' => max($totalPossible - $alreadyRegistered, 0),
        ],
        'existing_keys' => $existing,
    ];
}
public function registerBulkCourses(array $data): array
{
    $tenantId = $this->tenantId();

    abort_if(!Schema::hasTable('student_course_registrations'), 404, 'Student course registrations table not found.');
    abort_if(!Schema::hasTable('student_enrollments'), 404, 'Student enrollments table not found.');
    abort_if(!Schema::hasTable('curriculum_subjects'), 404, 'Curriculum subjects table not found.');

    $registrationType = $data['registration_type'] ?? 'regular';

    $enrollments = DB::table('student_enrollments')
        ->where('tenant_id', $tenantId)
        ->whereIn('id', $data['student_enrollment_ids'])
        ->where('program_id', $data['program_id'])
        ->where('academic_session_id', $data['academic_session_id'])
        ->get();

    abort_if($enrollments->isEmpty(), 422, 'No valid student enrollments found.');

    $subjectsQuery = DB::table('curriculum_subjects')
        ->where('tenant_id', $tenantId)
        ->whereIn('id', $data['curriculum_subject_ids'])
        ->where('program_id', $data['program_id'])
        ->where('status', 'active')
        ->whereNull('deleted_at');

    if (!empty($data['academic_term_id'])) {
        $subjectsQuery->where('academic_term_id', $data['academic_term_id']);
    }

    if (!empty($data['term_number'])) {
        $subjectsQuery->where('term_number', $data['term_number']);
    }

    $subjects = $subjectsQuery->get();

    abort_if($subjects->isEmpty(), 422, 'No valid curriculum subjects found.');

    $created = [];
    $skipped = [];

    DB::transaction(function () use (
        $tenantId,
        $enrollments,
        $subjects,
        $registrationType,
        $data,
        &$created,
        &$skipped
    ) {
        foreach ($enrollments as $enrollment) {
            foreach ($subjects as $subject) {
                $result = $this->createCourseRegistrationIfMissing(
                    tenantId: $tenantId,
                    enrollment: $enrollment,
                    curriculumSubject: $subject,
                    registrationType: $registrationType,
                    registrationSource: 'admin_bulk',
                    status: 'registered',
                    remarks: $data['remarks'] ?? null
                );

                if ($result['created']) {
                    $created[] = $result['registration'];
                } else {
                    $skipped[] = $result['reason'];
                }
            }
        }
    });

    return [
        'created_count' => count($created),
        'skipped_count' => count($skipped),
        'created' => $created,
        'skipped' => $skipped,
    ];
}
public function courseRegistrationSettings(array $filters = []): array
{
    $tenantId = $this->tenantId();

    if (!Schema::hasTable('course_registration_settings')) {
        return [
            'student_self_registration_enabled' => false,
            'requires_admin_approval' => true,
            'allow_add_drop' => false,
        ];
    }

    $query = DB::table('course_registration_settings')
        ->where('tenant_id', $tenantId)
        ->where('status_code', 'active');

    if (!empty($filters['academic_session_id'])) {
        $query->where('academic_session_id', $filters['academic_session_id']);
    }

    if (!empty($filters['program_id'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('program_id', $filters['program_id'])
                ->orWhereNull('program_id');
        });
    }

    if (!empty($filters['academic_term_id'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('academic_term_id', $filters['academic_term_id'])
                ->orWhereNull('academic_term_id');
        });
    }

    $setting = $query
        ->orderByRaw('program_id IS NULL')
        ->orderByRaw('academic_term_id IS NULL')
        ->orderByDesc('id')
        ->first();

    return $setting ? (array) $setting : [
        'student_self_registration_enabled' => false,
        'requires_admin_approval' => true,
        'allow_add_drop' => false,
    ];
}

public function saveCourseRegistrationSettings(array $data): array
{
    $tenantId = $this->tenantId();

    abort_if(!Schema::hasTable('course_registration_settings'), 404, 'Course registration settings table not found.');

    $lookup = [
        'tenant_id' => $tenantId,
        'academic_session_id' => $data['academic_session_id'],
        'program_id' => $data['program_id'] ?? null,
        'academic_term_id' => $data['academic_term_id'] ?? null,
    ];

    $payload = [
        'student_self_registration_enabled' => (bool) ($data['student_self_registration_enabled'] ?? false),
        'registration_start_at' => $data['registration_start_at'] ?? null,
        'registration_end_at' => $data['registration_end_at'] ?? null,
        'requires_admin_approval' => (bool) ($data['requires_admin_approval'] ?? true),
        'allow_add_drop' => (bool) ($data['allow_add_drop'] ?? false),
        'add_drop_start_at' => $data['add_drop_start_at'] ?? null,
        'add_drop_end_at' => $data['add_drop_end_at'] ?? null,
        'min_credit_hours' => $data['min_credit_hours'] ?? null,
        'max_credit_hours' => $data['max_credit_hours'] ?? null,
        'status_code' => $data['status_code'] ?? 'active',
        'updated_by' => auth()->id(),
        'updated_at' => now(),
    ];

    $existing = DB::table('course_registration_settings')
        ->where($lookup)
        ->first();

    if ($existing) {
        DB::table('course_registration_settings')
            ->where('id', $existing->id)
            ->update($payload);

        return ['id' => $existing->id, 'updated' => true];
    }

    $payload = array_merge($lookup, $payload, [
        'created_by' => auth()->id(),
        'created_at' => now(),
    ]);

    $id = DB::table('course_registration_settings')->insertGetId($payload);

    return ['id' => $id, 'created' => true];
}
private function studentsForBulkCourseRegistration(int $tenantId, array $filters): array
{
    $query = DB::table('student_enrollments as se')
        ->join('students as s', 's.id', '=', 'se.student_id')
        ->where(function ($q) use ($tenantId) {
            $q->where('se.tenant_id', $tenantId)
                ->orWhereNull('se.tenant_id');
        })
        ->where(function ($q) use ($tenantId) {
            $q->where('s.tenant_id', $tenantId)
                ->orWhereNull('s.tenant_id');
        });

    if (!empty($filters['program_id'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('se.program_id', $filters['program_id']);

            if (Schema::hasColumn('student_enrollments', 'offered_program_id')) {
                $q->orWhere('se.offered_program_id', $filters['program_id']);
            }
        });
    }

    if (!empty($filters['academic_session_id'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('se.academic_session_id', $filters['academic_session_id']);

            if (Schema::hasColumn('student_enrollments', 'admission_session_id')) {
                $q->orWhere('se.admission_session_id', $filters['academic_session_id']);
            }
        });
    }

    if (!empty($filters['student_batch_id'])) {
        $query->where('se.student_batch_id', $filters['student_batch_id']);
    }

    if (!empty($filters['section'])) {
        $query->where('se.section', $filters['section']);
    }

    if (Schema::hasColumn('student_enrollments', 'status_code')) {
        $query->where(function ($q) {
            $q->whereNull('se.status_code')
                ->orWhereIn('se.status_code', ['active', 'enrolled']);
        });
    }

    if (Schema::hasColumn('student_enrollments', 'enrollment_status_code')) {
        $query->where(function ($q) {
            $q->whereNull('se.enrollment_status_code')
                ->orWhereIn('se.enrollment_status_code', ['enrolled', 'active']);
        });
    }

    return $query
        ->select([
            'se.id as student_enrollment_id',
            'se.student_id',
            'se.student_batch_id',
            'se.section',
            'se.roll_no',
            'se.registration_no',
            'se.enrollment_status_code',
            'se.status_code',
            's.student_no',
            's.full_name as student_name',
            's.cnic_bform',
        ])
        ->orderBy('se.roll_sequence_no')
        ->orderBy('s.full_name')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->values()
        ->toArray();
}

private function curriculumSubjectsForBulkCourseRegistration(int $tenantId, array $filters): array
{
    $query = DB::table('curriculum_subjects as cs')
        ->where('cs.tenant_id', $tenantId)
        ->where('cs.program_id', $filters['program_id'])
        ->where('cs.status', 'active')
        ->whereNull('cs.deleted_at');

    if (!empty($filters['academic_term_id'])) {
        $query->where('cs.academic_term_id', $filters['academic_term_id']);
    }

    if (!empty($filters['term_number'])) {
        $query->where('cs.term_number', $filters['term_number']);
    }

    return $query
        ->select([
            'cs.id as curriculum_subject_id',
            'cs.curriculum_id',
            'cs.program_id',
            'cs.academic_term_id',
            'cs.subject_id',
            'cs.subject_code as course_code',
            'cs.subject_name as course_title',
            'cs.subject_nature as subject_type_code',
            'cs.term_number as term_no',
            'cs.credit_hours',
            'cs.is_compulsory',
            'cs.is_credit_subject',
        ])
        ->orderBy('cs.term_number')
        ->orderBy('cs.display_order')
        ->orderBy('cs.subject_code')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->values()
        ->toArray();
}

private function createCourseRegistrationIfMissing(
    int $tenantId,
    object $enrollment,
    object $curriculumSubject,
    string $registrationType,
    string $registrationSource,
    string $status,
    ?string $remarks = null
): array {
    $exists = DB::table('student_course_registrations')
        ->where('tenant_id', $tenantId)
        ->where('student_enrollment_id', $enrollment->id)
        ->where('curriculum_subject_id', $curriculumSubject->id)
        ->where('registration_type', $registrationType)
        ->whereIn('status', ['pending', 'registered', 'approved', 'completed'])
        ->first();

    if ($exists) {
        return [
            'created' => false,
            'reason' => [
                'student_enrollment_id' => $enrollment->id,
                'curriculum_subject_id' => $curriculumSubject->id,
                'message' => 'Already registered.',
            ],
        ];
    }

    $payload = [
        'tenant_id' => $tenantId,
        'student_id' => $enrollment->student_id,
        'student_enrollment_id' => $enrollment->id,

        'program_id' => $enrollment->program_id,
        'academic_session_id' => $enrollment->academic_session_id,
        'academic_term_id' => $curriculumSubject->academic_term_id ?? null,
        'term_id' => null,

        'student_batch_id' => $enrollment->student_batch_id ?? null,
        'section' => $enrollment->section ?? null,

        'curriculum_id' => $curriculumSubject->curriculum_id ?? null,
        'curriculum_subject_id' => $curriculumSubject->id,
        'subject_id' => $curriculumSubject->subject_id ?? null,

        'course_code' => $curriculumSubject->subject_code ?? null,
        'course_title' => $curriculumSubject->subject_name ?? null,
        'credit_hours' => $curriculumSubject->credit_hours ?? 0,
        'subject_type_code' => $curriculumSubject->subject_nature ?? null,

        'registration_type' => $registrationType,
        'registration_source' => $registrationSource,
        'status' => $status,
        'is_locked' => false,
        'registered_at' => now(),

        'requested_by' => $registrationSource === 'student_self' ? auth()->id() : null,
        'approved_by' => in_array($status, ['registered', 'approved'], true) ? auth()->id() : null,
        'approved_at' => in_array($status, ['registered', 'approved'], true) ? now() : null,

        'remarks' => $remarks,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $payload = collect($payload)
        ->filter(fn ($value, $column) => Schema::hasColumn('student_course_registrations', $column))
        ->toArray();

    $id = DB::table('student_course_registrations')->insertGetId($payload);

    return [
        'created' => true,
        'registration' => array_merge(['id' => $id], $payload),
    ];
}
public function allocationContext(array $filters): array
{
    $tenantId = $this->tenantId();

    abort_if(
        !Schema::hasTable('student_enrollments'),
        404,
        'Student enrollments table not found.'
    );

    $query = DB::table('student_enrollments as se')
        ->join('students as s', 's.id', '=', 'se.student_id')
        ->where('se.tenant_id', $tenantId)
        ->where('s.tenant_id', $tenantId);

    if (Schema::hasTable('student_batches') && Schema::hasColumn('student_enrollments', 'student_batch_id')) {
        $query->leftJoin('student_batches as sb', 'sb.id', '=', 'se.student_batch_id');
    }

    if (Schema::hasTable('programs') && Schema::hasColumn('student_enrollments', 'program_id')) {
        $query->leftJoin('programs as p', 'p.id', '=', 'se.program_id');
    }

    if (Schema::hasTable('academic_sessions') && Schema::hasColumn('student_enrollments', 'academic_session_id')) {
        $query->leftJoin('academic_sessions as ases', 'ases.id', '=', 'se.academic_session_id');
    }

    if (!empty($filters['q'])) {
        $q = trim((string) $filters['q']);

        $query->where(function ($sub) use ($q) {
            $sub->where('s.student_no', 'like', "%{$q}%")
                ->orWhere('s.full_name', 'like', "%{$q}%")
                ->orWhere('s.cnic_bform', 'like', "%{$q}%");

            if (Schema::hasColumn('student_enrollments', 'roll_no')) {
                $sub->orWhere('se.roll_no', 'like', "%{$q}%");
            }

            if (Schema::hasColumn('student_enrollments', 'registration_no')) {
                $sub->orWhere('se.registration_no', 'like', "%{$q}%");
            }
        });
    }

    if (!empty($filters['program_id']) && Schema::hasColumn('student_enrollments', 'program_id')) {
        $query->where('se.program_id', $filters['program_id']);
    }

    if (!empty($filters['academic_session_id']) && Schema::hasColumn('student_enrollments', 'academic_session_id')) {
        $query->where('se.academic_session_id', $filters['academic_session_id']);
    }

    if (!empty($filters['student_batch_id']) && Schema::hasColumn('student_enrollments', 'student_batch_id')) {
        $query->where('se.student_batch_id', $filters['student_batch_id']);
    }

    if (!empty($filters['section']) && Schema::hasColumn('student_enrollments', 'section')) {
        $query->where('se.section', $filters['section']);
    }

    if (!empty($filters['allocation_status']) && Schema::hasColumn('student_enrollments', 'allocation_status')) {
        $query->where('se.allocation_status', $filters['allocation_status']);
    }

    $enrollments = $query
        ->select($this->allocationSelectColumns())
        ->orderByDesc('se.id')
        ->paginate((int) ($filters['per_page'] ?? 20));

    return [
        'enrollments' => $enrollments,
        'batches' => $this->allocationBatches($filters),
        'sections' => $this->existingSections($filters),
    ];
}

public function bulkAllocate(array $data): array
{
    $tenantId = $this->tenantId();

    abort_if(
        !Schema::hasTable('student_enrollments'),
        404,
        'Student enrollments table not found.'
    );

    $enrollmentIds = array_values(array_unique(array_map('intval', $data['student_enrollment_ids'])));
    $overwriteExisting = (bool) ($data['overwrite_existing'] ?? false);
    $section = strtoupper(trim((string) $data['section']));
    $allocationStatus = $data['allocation_status'] ?? 'allocated';
    $padding = (int) ($data['padding'] ?? 3);
    $startRollNo = (int) ($data['start_roll_no'] ?? 1);
    $rollPrefix = trim((string) ($data['roll_prefix'] ?? ''));
    $registrationPrefix = trim((string) ($data['registration_prefix'] ?? ''));

    $allowedStatuses = ['pending', 'allocated', 'enrolled', 'active', 'cancelled'];

    abort_if(
        !in_array($allocationStatus, $allowedStatuses, true),
        422,
        'Invalid allocation status.'
    );

    $batch = null;

    if (!empty($data['student_batch_id'])) {
        abort_if(!Schema::hasTable('student_batches'), 404, 'Student batches table not found.');

        $batch = DB::table('student_batches')
            ->where('tenant_id', $tenantId)
            ->where('id', $data['student_batch_id'])
            ->first();

        abort_if(!$batch, 404, 'Student batch not found.');
    }

    $enrollments = DB::table('student_enrollments as se')
        ->join('students as s', 's.id', '=', 'se.student_id')
        ->where('se.tenant_id', $tenantId)
        ->where('s.tenant_id', $tenantId)
        ->whereIn('se.id', $enrollmentIds)
        ->select('se.*')
        ->orderBy('se.id')
        ->get();

    abort_if(
        $enrollments->count() !== count($enrollmentIds),
        404,
        'One or more selected enrollments were not found.'
    );

    if (!$overwriteExisting) {
        foreach ($enrollments as $enrollment) {
            if (
                (Schema::hasColumn('student_enrollments', 'roll_no') && !empty($enrollment->roll_no))
                || (Schema::hasColumn('student_enrollments', 'registration_no') && !empty($enrollment->registration_no))
                || (Schema::hasColumn('student_enrollments', 'student_batch_id') && !empty($enrollment->student_batch_id))
            ) {
                abort(422, 'One or more selected enrollments are already allocated. Enable overwrite to reallocate.');
            }
        }
    }

    if ($batch && isset($batch->capacity) && (int) $batch->capacity > 0) {
        $alreadyAllocated = DB::table('student_enrollments')
            ->where('tenant_id', $tenantId)
            ->where('student_batch_id', $batch->id)
            ->whereNotIn('id', $enrollmentIds)
            ->count();

        abort_if(
            ($alreadyAllocated + count($enrollmentIds)) > (int) $batch->capacity,
            422,
            'Batch capacity exceeded.'
        );
    }

    $updated = [];

    DB::transaction(function () use (
        $tenantId,
        $enrollments,
        $data,
        $section,
        $allocationStatus,
        $startRollNo,
        $padding,
        $rollPrefix,
        $registrationPrefix,
        &$updated
    ) {
        $sequence = $startRollNo;

        foreach ($enrollments as $enrollment) {
            $rollNo = $rollPrefix !== ''
                ? $rollPrefix . str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT)
                : ($enrollment->roll_no ?? null);

            $registrationNo = $registrationPrefix !== ''
                ? $registrationPrefix . str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT)
                : ($enrollment->registration_no ?? null);

            $this->ensureEnrollmentNumberIsUnique($tenantId, (int) $enrollment->id, 'roll_no', $rollNo);
            $this->ensureEnrollmentNumberIsUnique($tenantId, (int) $enrollment->id, 'registration_no', $registrationNo);

            $payload = [];

            if (Schema::hasColumn('student_enrollments', 'student_batch_id')) {
                $payload['student_batch_id'] = $data['student_batch_id'] ?? null;
            }

            if (Schema::hasColumn('student_enrollments', 'section')) {
                $payload['section'] = $section;
            }

            if (Schema::hasColumn('student_enrollments', 'roll_no')) {
                $payload['roll_no'] = $rollNo;
            }

            if (Schema::hasColumn('student_enrollments', 'registration_no')) {
                $payload['registration_no'] = $registrationNo;
            }

            if (Schema::hasColumn('student_enrollments', 'roll_sequence_no')) {
                $payload['roll_sequence_no'] = $sequence;
            }

            if (Schema::hasColumn('student_enrollments', 'allocation_status')) {
                $payload['allocation_status'] = $allocationStatus;
            }

            if (Schema::hasColumn('student_enrollments', 'allocated_at')) {
                $payload['allocated_at'] = now();
            }

            if (Schema::hasColumn('student_enrollments', 'allocated_by')) {
                $payload['allocated_by'] = auth()->id();
            }

            if (Schema::hasColumn('student_enrollments', 'allocation_remarks')) {
                $payload['allocation_remarks'] = $data['remarks'] ?? null;
            }

            if (Schema::hasColumn('student_enrollments', 'updated_by')) {
                $payload['updated_by'] = auth()->id();
            }

            $payload['updated_at'] = now();

            DB::table('student_enrollments')
                ->where('tenant_id', $tenantId)
                ->where('id', $enrollment->id)
                ->update($payload);

            $updated[] = [
                'enrollment_id' => $enrollment->id,
                'roll_no' => $rollNo,
                'registration_no' => $registrationNo,
                'section' => $section,
            ];

            $sequence++;
        }
    });

    return [
        'updated_count' => count($updated),
        'updated' => $updated,
    ];
}

public function updateEnrollmentAllocation(int $enrollmentId, array $data): array
{
    $tenantId = $this->tenantId();

    $enrollment = DB::table('student_enrollments as se')
        ->join('students as s', 's.id', '=', 'se.student_id')
        ->where('se.tenant_id', $tenantId)
        ->where('s.tenant_id', $tenantId)
        ->where('se.id', $enrollmentId)
        ->select('se.*')
        ->first();

    abort_if(!$enrollment, 404, 'Student enrollment not found.');

    if (!empty($data['student_batch_id'])) {
        abort_if(!Schema::hasTable('student_batches'), 404, 'Student batches table not found.');

        $batchExists = DB::table('student_batches')
            ->where('tenant_id', $tenantId)
            ->where('id', $data['student_batch_id'])
            ->exists();

        abort_if(!$batchExists, 404, 'Student batch not found.');
    }

    $allowedStatuses = ['pending', 'allocated', 'enrolled', 'active', 'cancelled'];

    if (!empty($data['allocation_status'])) {
        abort_if(
            !in_array($data['allocation_status'], $allowedStatuses, true),
            422,
            'Invalid allocation status.'
        );
    }

    $this->ensureEnrollmentNumberIsUnique($tenantId, $enrollmentId, 'roll_no', $data['roll_no'] ?? null);
    $this->ensureEnrollmentNumberIsUnique($tenantId, $enrollmentId, 'registration_no', $data['registration_no'] ?? null);

    $payload = [];

    foreach (
        [
            'student_batch_id',
            'section',
            'roll_no',
            'registration_no',
            'roll_sequence_no',
            'allocation_status',
        ] as $column
    ) {
        if (array_key_exists($column, $data) && Schema::hasColumn('student_enrollments', $column)) {
            $payload[$column] = $column === 'section' && $data[$column]
                ? strtoupper(trim((string) $data[$column]))
                : $data[$column];
        }
    }

    if (array_key_exists('remarks', $data) && Schema::hasColumn('student_enrollments', 'allocation_remarks')) {
        $payload['allocation_remarks'] = $data['remarks'];
    }

    if (Schema::hasColumn('student_enrollments', 'allocated_at')) {
        $payload['allocated_at'] = now();
    }

    if (Schema::hasColumn('student_enrollments', 'allocated_by')) {
        $payload['allocated_by'] = auth()->id();
    }

    if (Schema::hasColumn('student_enrollments', 'updated_by')) {
        $payload['updated_by'] = auth()->id();
    }

    $payload['updated_at'] = now();

    abort_if(empty($payload), 422, 'No valid allocation field found.');

    DB::table('student_enrollments')
        ->where('tenant_id', $tenantId)
        ->where('id', $enrollmentId)
        ->update($payload);

    return [
        'enrollment_id' => $enrollmentId,
        'updated_fields' => array_keys($payload),
    ];
}
private function ensureEnrollmentNumberIsUnique(
    $tenantId,
    string $column,
    ?string $value,
    $ignoreEnrollmentId = null
): void {
    if (!$value) {
        return;
    }

    if (!Schema::hasTable('student_enrollments')) {
        return;
    }

    if (!Schema::hasColumn('student_enrollments', $column)) {
        return;
    }

    $allowedColumns = [
        'roll_no',
        'registration_no',
        'enrollment_no',
    ];

    if (!in_array($column, $allowedColumns, true)) {
        return;
    }

    $ignoreEnrollmentId = is_numeric($ignoreEnrollmentId)
        ? (int) $ignoreEnrollmentId
        : null;

    $tenantId = is_numeric($tenantId)
        ? (int) $tenantId
        : null;

    $query = DB::table('student_enrollments')
        ->where($column, $value);

    if (Schema::hasColumn('student_enrollments', 'tenant_id') && $tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($ignoreEnrollmentId) {
        $query->where('id', '!=', $ignoreEnrollmentId);
    }

    $exists = $query->exists();

    abort_if(
        $exists,
        422,
        ucfirst(str_replace('_', ' ', $column)) . " already exists: {$value}"
    );
}
public function verifyStudentDocument(int $documentId, array $data): array
{
    $tenantId = $this->tenantId();

    abort_if(!Schema::hasTable('student_documents'), 404, 'Student documents table not found.');

    $allowedStatuses = [
        'pending',
        'verified',
        'rejected',
        'resubmission_required',
    ];

    $status = strtolower(trim((string) $data['verification_status']));

    abort_if(
        !in_array($status, $allowedStatuses, true),
        422,
        'Invalid document verification status.'
    );

    $document = DB::table('student_documents')
        ->where('id', $documentId)
        ->when(
            Schema::hasColumn('student_documents', 'tenant_id') && $tenantId,
            fn ($q) => $q->where('tenant_id', $tenantId)
        )
        ->first();

    abort_if(!$document, 404, 'Student document not found.');

    $payload = [
        'verification_status' => $status,
        'remarks' => $data['remarks'] ?? null,
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('student_documents', 'verified_at')) {
        $payload['verified_at'] = $status === 'verified' ? now() : null;
    }

    if (Schema::hasColumn('student_documents', 'verified_by')) {
        $payload['verified_by'] = $status === 'verified' ? auth()->id() : null;
    }

    if (Schema::hasColumn('student_documents', 'updated_by')) {
        $payload['updated_by'] = auth()->id();
    }

    DB::table('student_documents')
        ->where('id', $documentId)
        ->update($payload);

    return [
        'document_id' => $documentId,
        'verification_status' => $status,
    ];
}
    private function tenantId(): int
    {
        $user = auth()->user();

        if ($user && isset($user->tenant_id) && $user->tenant_id) {
            return (int) $user->tenant_id;
        }

        if (function_exists('tenant') && tenant()) {
            return (int) (tenant('id') ?? tenant()->id ?? 0);
        }

        return 0;
    }

}