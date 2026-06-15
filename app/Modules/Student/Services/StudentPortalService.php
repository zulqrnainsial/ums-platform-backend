<?php

namespace App\Modules\Student\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Modules\Student\Models\StudentRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
class StudentPortalService
{
    public function dashboard(): array
    {
        $student = $this->currentStudent();
        $studentArray = (array) $student;
        $studentArray['profile_photo_url'] = !empty($student->profile_photo_path)
            ? Storage::disk('public')->url($student->profile_photo_path)
            : null;

        return [
            'student' => $studentArray,
            'current_enrollment' => $this->currentEnrollment((int) $student->id),
            'courses_count' => count($this->courses()),
            'documents_count' => count($this->documents()),
            'pending_documents_count' => $this->pendingDocumentsCount((int) $student->id),
            'academic_status' => $student->student_status ?? ($student->status_code ?? null),
            'lifecycle_status' => $student->lifecycle_status ?? null,
        ];
    }

    public function profile(): array
{
    $student = $this->currentStudent();

    $studentArray = (array) $student;

    $studentArray['profile_photo_url'] = !empty($student->profile_photo_path)
        ? Storage::disk('public')->url($student->profile_photo_path)
        : null;

    return [
        'student' => $studentArray,
        'guardians' => $this->guardians((int) $student->id),
        'previous_educations' => $this->previousEducations((int) $student->id),
    ];
}

    public function enrollment(): array
    {
        $student = $this->currentStudent();

        return [
            'student' => $student,
            'current_enrollment' => $this->currentEnrollment((int) $student->id),
            'enrollments' => $this->allEnrollments((int) $student->id),
        ];
    }

    public function courses(): array
    {
        $student = $this->currentStudent();

        if (!Schema::hasTable('student_course_registrations')) {
            return [];
        }

        return DB::table('student_course_registrations as scr')
            ->where('scr.tenant_id', $student->tenant_id)
            ->where('scr.student_id', $student->id)
            ->whereIn('scr.status', ['registered', 'approved', 'active'])
            ->orderBy('scr.course_code')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'course_code' => $row->course_code,
                'course_title' => $row->course_title,
                'credit_hours' => $row->credit_hours,
                'subject_type_code' => $row->subject_type_code,
                'registration_type' => $row->registration_type,
                'status' => $row->status,
                'is_locked' => (bool) $row->is_locked,
                'registered_at' => $row->registered_at,
            ])
            ->values()
            ->toArray();
    }

    public function documents(): array
{
    $student = $this->currentStudent();

    if (!Schema::hasTable('student_documents')) {
        return [];
    }

    return DB::table('student_documents')
        ->where('tenant_id', $student->tenant_id)
        ->where('student_id', $student->id)
        ->orderByDesc('id')
        ->get()
        ->map(fn ($row) => [
            'id' => $row->id,
            'document_title' => $row->document_title ?? null,
            'document_type' => $row->document_type ?? null,
            'file_name' => $row->file_name ?? null,
            'file_path' => $row->file_path ?? null,
            'file_url' => !empty($row->file_path) ? Storage::disk('public')->url($row->file_path) : null,
            'mime_type' => $row->mime_type ?? null,
            'file_size' => $row->file_size ?? null,
            'verification_status' => $row->verification_status ?? 'pending',
            'remarks' => $row->remarks ?? null,
            'verified_at' => $row->verified_at ?? null,
            'uploaded_at' => $row->uploaded_at ?? null,
        ])
        ->values()
        ->toArray();
}
public function availableCourses(): array
{
    $student = $this->currentStudent();
    $enrollment = $this->currentEnrollment((int) $student->id);

    if (!$enrollment || !Schema::hasTable('curriculum_subjects')) {
        return [];
    }

    $query = DB::table('curriculum_subjects as cs');

    if (Schema::hasColumn('curriculum_subjects', 'tenant_id')) {
        $query->where('cs.tenant_id', $student->tenant_id);
    }

    if (!empty($enrollment->program_id) && Schema::hasColumn('curriculum_subjects', 'program_id')) {
        $query->where('cs.program_id', $enrollment->program_id);
    }

    if (Schema::hasTable('subjects') && Schema::hasColumn('curriculum_subjects', 'subject_id')) {
        $query->leftJoin('subjects as sub', 'sub.id', '=', 'cs.subject_id');
    }

    $registered = Schema::hasTable('student_course_registrations')
        ? DB::table('student_course_registrations')
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->where('student_enrollment_id', $enrollment->id)
            ->whereIn('status', ['registered', 'approved', 'active'])
            ->get()
        : collect();

    $registeredCurriculumSubjectIds = $registered->pluck('curriculum_subject_id')->filter()->values()->toArray();
    $registeredSubjectIds = $registered->pluck('subject_id')->filter()->values()->toArray();

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

    $rows = $query
        ->select([
            'cs.id',
            DB::raw(Schema::hasColumn('curriculum_subjects', 'subject_id') ? 'cs.subject_id' : 'NULL as subject_id'),
            DB::raw($codeExpr),
            DB::raw($titleExpr),
            DB::raw(Schema::hasColumn('curriculum_subjects', 'credit_hours') ? 'cs.credit_hours' : (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'credit_hours') ? 'sub.credit_hours' : '0 as credit_hours')),
        ])
        ->orderBy('cs.id')
        ->get();

    return $rows
        ->filter(function ($row) use ($registeredCurriculumSubjectIds, $registeredSubjectIds) {
            if (in_array($row->id, $registeredCurriculumSubjectIds, true)) {
                return false;
            }

            if (!empty($row->subject_id) && in_array($row->subject_id, $registeredSubjectIds, true)) {
                return false;
            }

            return true;
        })
        ->map(fn ($row) => [
            'id' => $row->id,
            'label' => trim(($row->course_code ?? '') . ' - ' . ($row->course_title ?? '')),
            'subject_id' => $row->subject_id,
            'course_code' => $row->course_code,
            'course_title' => $row->course_title,
            'credit_hours' => $row->credit_hours,
        ])
        ->values()
        ->toArray();
}

private function currentEnrollmentId(int $studentId): ?int
{
    if (!Schema::hasTable('student_enrollments')) {
        return null;
    }

    $student = DB::table('students')->where('id', $studentId)->first();

    $enrollment = DB::table('student_enrollments')
        ->where('tenant_id', $student->tenant_id)
        ->where('student_id', $studentId)
        ->orderByDesc('id')
        ->first();

    return $enrollment?->id;
}

private function nextRequestNo(int $tenantId): string
{
    $nextId = ((int) DB::table('student_requests')
        ->where('tenant_id', $tenantId)
        ->max('id')) + 1;

    return 'SR-' . now()->format('Y') . '-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
}
private function filterPayloadByColumns(string $table, array $payload): array
{
    return collect($payload)
        ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
        ->toArray();
}
    private function currentStudent(): object
    {
        $user = auth()->user();

        abort_if(!$user, 401, 'Unauthenticated.');

        $query = DB::table('students')
            ->where('tenant_id', $user->tenant_id ?? 0);

        if (Schema::hasColumn('students', 'user_id')) {
            $query->where('user_id', $user->id);
        } elseif (Schema::hasColumn('students', 'email')) {
            $query->where('email', $user->email);
        } else {
            abort(404, 'Student portal account is not linked with student record.');
        }

        $student = $query->first();

        abort_if(!$student, 404, 'Student record not found for this portal account.');

        if (
            Schema::hasColumn('students', 'portal_access_enabled')
            && isset($student->portal_access_enabled)
            && !(bool) $student->portal_access_enabled
        ) {
            abort(403, 'Student portal access is disabled.');
        }

        if (Schema::hasColumn('students', 'last_portal_login_at')) {
            DB::table('students')
                ->where('id', $student->id)
                ->update([
                    'last_portal_login_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return $student;
    }

    private function currentEnrollment(int $studentId): ?object
    {
        if (!Schema::hasTable('student_enrollments')) {
            return null;
        }

        $student = DB::table('students')->where('id', $studentId)->first();

        $query = DB::table('student_enrollments as se')
            ->where('se.tenant_id', $student->tenant_id)
            ->where('se.student_id', $studentId);

        $this->joinEnrollmentLookups($query);

        return $query
            ->select($this->enrollmentSelectColumns())
            ->orderByDesc('se.id')
            ->first();
    }

    private function allEnrollments(int $studentId): array
    {
        if (!Schema::hasTable('student_enrollments')) {
            return [];
        }

        $student = DB::table('students')->where('id', $studentId)->first();

        $query = DB::table('student_enrollments as se')
            ->where('se.tenant_id', $student->tenant_id)
            ->where('se.student_id', $studentId);

        $this->joinEnrollmentLookups($query);

        return $query
            ->select($this->enrollmentSelectColumns())
            ->orderByDesc('se.id')
            ->get()
            ->toArray();
    }

    private function guardians(int $studentId): array
    {
        if (!Schema::hasTable('student_guardians')) {
            return [];
        }

        $student = DB::table('students')->where('id', $studentId)->first();

        $query = DB::table('student_guardians as sg')
            ->where('sg.tenant_id', $student->tenant_id)
            ->where('sg.student_id', $studentId);

        if (Schema::hasTable('guardians')) {
            $query->leftJoin('guardians as g', 'g.id', '=', 'sg.guardian_id');
        }

        return $query
            ->select([
                'sg.id',
                'sg.guardian_id',
                'sg.relationship_type_id',
                'sg.is_primary',
                'sg.is_emergency_contact',
                'sg.can_pick_student',
                'sg.status',
                DB::raw(Schema::hasTable('guardians') ? 'g.name as guardian_name' : 'NULL as guardian_name'),
                DB::raw(Schema::hasTable('guardians') ? 'g.phone as guardian_phone' : 'NULL as guardian_phone'),
                DB::raw(Schema::hasTable('guardians') ? 'g.email as guardian_email' : 'NULL as guardian_email'),
                DB::raw(Schema::hasTable('guardians') ? 'g.cnic as guardian_cnic' : 'NULL as guardian_cnic'),
                DB::raw(Schema::hasTable('guardians') ? 'g.occupation as occupation' : 'NULL as occupation'),
            ])
            ->get()
            ->toArray();
    }

    private function previousEducations(int $studentId): array
    {
        if (!Schema::hasTable('student_previous_educations')) {
            return [];
        }

        $student = DB::table('students')->where('id', $studentId)->first();

        return DB::table('student_previous_educations')
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $studentId)
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }

    private function pendingDocumentsCount(int $studentId): int
    {
        if (!Schema::hasTable('student_documents')) {
            return 0;
        }

        $student = DB::table('students')->where('id', $studentId)->first();

        return DB::table('student_documents')
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $studentId)
            ->where(function ($q) {
                $q->whereNull('verification_status')
                    ->orWhere('verification_status', 'pending')
                    ->orWhere('verification_status', 'resubmission_required');
            })
            ->count();
    }

    private function joinEnrollmentLookups($query): void
    {
        if (Schema::hasTable('programs') && Schema::hasColumn('student_enrollments', 'program_id')) {
            $query->leftJoin('programs as p', 'p.id', '=', 'se.program_id');
        }

        if (Schema::hasTable('academic_sessions') && Schema::hasColumn('student_enrollments', 'academic_session_id')) {
            $query->leftJoin('academic_sessions as ases', 'ases.id', '=', 'se.academic_session_id');
        }

        if (Schema::hasTable('student_batches') && Schema::hasColumn('student_enrollments', 'student_batch_id')) {
            $query->leftJoin('student_batches as sb', 'sb.id', '=', 'se.student_batch_id');
        }
    }

    private function enrollmentSelectColumns(): array
    {
        return [
            'se.id',
            'se.student_id',
            DB::raw(Schema::hasColumn('student_enrollments', 'program_id') ? 'se.program_id' : 'NULL as program_id'),
            DB::raw(Schema::hasColumn('student_enrollments', 'academic_session_id') ? 'se.academic_session_id' : 'NULL as academic_session_id'),
            DB::raw(Schema::hasColumn('student_enrollments', 'term_id') ? 'se.term_id' : 'NULL as term_id'),
            DB::raw(Schema::hasColumn('student_enrollments', 'student_batch_id') ? 'se.student_batch_id' : 'NULL as student_batch_id'),
            DB::raw(Schema::hasColumn('student_enrollments', 'section') ? 'se.section' : 'NULL as section'),
            DB::raw(Schema::hasColumn('student_enrollments', 'roll_no') ? 'se.roll_no' : 'NULL as roll_no'),
            DB::raw(Schema::hasColumn('student_enrollments', 'registration_no') ? 'se.registration_no' : 'NULL as registration_no'),
            DB::raw(Schema::hasColumn('student_enrollments', 'enrollment_status_code') ? 'se.enrollment_status_code as status' : (Schema::hasColumn('student_enrollments', 'status_code') ? 'se.status_code as status' : (Schema::hasColumn('student_enrollments', 'status') ? 'se.status as status' : 'NULL as status'))),
            DB::raw(Schema::hasColumn('student_enrollments', 'allocation_status') ? 'se.allocation_status' : 'NULL as allocation_status'),
            DB::raw(Schema::hasColumn('student_enrollments', 'lifecycle_status') ? 'se.lifecycle_status' : 'NULL as lifecycle_status'),
            DB::raw(Schema::hasTable('programs') ? 'p.name as program_name' : 'NULL as program_name'),
            DB::raw(Schema::hasTable('academic_sessions') ? 'ases.name as academic_session_name' : 'NULL as academic_session_name'),
            DB::raw(Schema::hasTable('student_batches') ? 'sb.name as batch_name' : 'NULL as batch_name'),
        ];
    }
    public function requests(): array
{
    $student = $this->currentStudent();

    if (!Schema::hasTable('student_requests')) {
        return [];
    }

    return DB::table('student_requests')
        ->where('tenant_id', $student->tenant_id)
        ->where('student_id', $student->id)
        ->orderByDesc('id')
        ->get()
        ->map(fn ($row) => [
            'id' => $row->id,
            'request_no' => $row->request_no,
            'request_type' => $row->request_type,
            'title' => $row->title,
            'description' => $row->description,
            'requested_payload_json' => $row->requested_payload_json
                ? json_decode($row->requested_payload_json, true)
                : null,
            'admin_decision_payload_json' => $row->admin_decision_payload_json
                ? json_decode($row->admin_decision_payload_json, true)
                : null,
            'status' => $row->status,
            'submitted_at' => $row->submitted_at,
            'reviewed_at' => $row->reviewed_at,
            'student_remarks' => $row->student_remarks,
            'admin_remarks' => $row->admin_remarks,
        ])
        ->values()
        ->toArray();
}

public function submitProfileCorrectionRequest(array $data): array
{
    $student = $this->currentStudent();

    $allowedFields = [
        'first_name',
        'last_name',
        'father_name',
        'mother_name',
        'cnic_bform',
        'passport_no',
        'date_of_birth',
        'gender',
        'phone',
        'alternate_phone',
        'email',
        'current_address',
        'permanent_address',
    ];

    $requestedChanges = [];

    foreach (($data['requested_changes'] ?? []) as $field => $value) {
        if (in_array($field, $allowedFields, true)) {
            $requestedChanges[$field] = $value;
        }
    }

    abort_if(empty($requestedChanges), 422, 'No valid profile correction field found.');

    $request = StudentRequest::create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'student_enrollment_id' => $this->currentEnrollmentId((int) $student->id),
        'request_no' => $this->nextRequestNo($student->tenant_id),
        'request_type' => 'profile_correction',
        'title' => 'Profile Correction Request',
        'description' => 'Student requested profile correction.',
        'requested_payload_json' => [
            'requested_changes' => $requestedChanges,
            'reason' => $data['reason'],
        ],
        'status' => 'pending',
        'submitted_at' => now(),
        'student_remarks' => $data['reason'],
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    return [
        'request' => $request,
    ];
}

public function submitDocumentResubmissionRequest(array $data): array
{
    $student = $this->currentStudent();

    abort_if(!Schema::hasTable('student_documents'), 404, 'Student documents table not found.');

    $document = DB::table('student_documents')
        ->where('tenant_id', $student->tenant_id)
        ->where('student_id', $student->id)
        ->where('id', $data['student_document_id'])
        ->first();

    abort_if(!$document, 404, 'Student document not found.');

    $request = StudentRequest::create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'student_enrollment_id' => $this->currentEnrollmentId((int) $student->id),
        'request_no' => $this->nextRequestNo($student->tenant_id),
        'request_type' => 'document_resubmission',
        'title' => 'Document Resubmission Request',
        'description' => 'Student requested document resubmission.',
        'requested_payload_json' => [
            'student_document_id' => $document->id,
            'old_file_name' => $document->file_name ?? null,
            'new_file_path' => $data['new_file_path'] ?? null,
            'new_file_name' => $data['new_file_name'] ?? null,
            'reason' => $data['reason'],
        ],
        'related_document_id' => $document->id,
        'status' => 'pending',
        'submitted_at' => now(),
        'student_remarks' => $data['reason'],
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    return [
        'request' => $request,
    ];
}

public function submitCourseAddDropRequest(array $data): array
{
    $student = $this->currentStudent();

    $actionType = strtolower(trim((string) $data['action_type']));

    abort_if(
        !in_array($actionType, ['add', 'drop'], true),
        422,
        'Invalid course request action.'
    );

    $currentEnrollmentId = $data['student_enrollment_id'] ?? $this->currentEnrollmentId((int) $student->id);

    abort_if(!$currentEnrollmentId, 422, 'Student enrollment is required.');

    if ($actionType === 'add') {
        abort_if(empty($data['curriculum_subject_id']), 422, 'Curriculum subject is required for course add request.');

        $curriculumSubject = DB::table('curriculum_subjects')
            ->where('tenant_id', $student->tenant_id)
            ->where('id', $data['curriculum_subject_id'])
            ->first();

        abort_if(!$curriculumSubject, 404, 'Curriculum subject not found.');

        $subjectId = $curriculumSubject->subject_id ?? null;

        $alreadyRegistered = false;

        if ($subjectId && Schema::hasTable('student_course_registrations')) {
            $alreadyRegistered = DB::table('student_course_registrations')
                ->where('tenant_id', $student->tenant_id)
                ->where('student_id', $student->id)
                ->where('student_enrollment_id', $currentEnrollmentId)
                ->where('subject_id', $subjectId)
                ->whereIn('status', ['registered', 'approved', 'active'])
                ->exists();
        }

        abort_if($alreadyRegistered, 422, 'Course is already registered.');

        $payload = [
            'action_type' => 'add',
            'student_enrollment_id' => $currentEnrollmentId,
            'curriculum_subject_id' => $curriculumSubject->id,
            'subject_id' => $subjectId,
            'course_code' => $curriculumSubject->course_code ?? null,
            'course_title' => $curriculumSubject->course_title ?? null,
            'reason' => $data['reason'],
        ];

        $relatedCurriculumSubjectId = $curriculumSubject->id;
        $relatedSubjectId = $subjectId;
        $relatedCourseRegistrationId = null;
        $title = 'Course Add Request';
    } else {
        abort_if(empty($data['course_registration_id']), 422, 'Course registration is required for drop request.');

        $courseRegistration = DB::table('student_course_registrations')
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->where('id', $data['course_registration_id'])
            ->first();

        abort_if(!$courseRegistration, 404, 'Course registration not found.');

        abort_if(
            isset($courseRegistration->is_locked) && (bool) $courseRegistration->is_locked,
            422,
            'Locked course cannot be dropped through request.'
        );

        $payload = [
            'action_type' => 'drop',
            'student_enrollment_id' => $courseRegistration->student_enrollment_id,
            'course_registration_id' => $courseRegistration->id,
            'curriculum_subject_id' => $courseRegistration->curriculum_subject_id,
            'subject_id' => $courseRegistration->subject_id,
            'course_code' => $courseRegistration->course_code,
            'course_title' => $courseRegistration->course_title,
            'reason' => $data['reason'],
        ];

        $relatedCurriculumSubjectId = $courseRegistration->curriculum_subject_id;
        $relatedSubjectId = $courseRegistration->subject_id;
        $relatedCourseRegistrationId = $courseRegistration->id;
        $title = 'Course Drop Request';
    }

    $request = StudentRequest::create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'student_enrollment_id' => $currentEnrollmentId,
        'request_no' => $this->nextRequestNo($student->tenant_id),
        'request_type' => 'course_add_drop',
        'title' => $title,
        'description' => $title,
        'requested_payload_json' => $payload,
        'related_course_registration_id' => $relatedCourseRegistrationId,
        'related_curriculum_subject_id' => $relatedCurriculumSubjectId,
        'related_subject_id' => $relatedSubjectId,
        'status' => 'pending',
        'submitted_at' => now(),
        'student_remarks' => $data['reason'],
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    return [
        'request' => $request,
    ];
}
public function uploadProfilePicture(UploadedFile $photo): array
{
    $student = $this->currentStudent();

    $path = $photo->store("students/{$student->id}/profile", 'public');

    $payload = [
        'profile_photo_path' => $path,
        'profile_photo_uploaded_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('students')
        ->where('tenant_id', $student->tenant_id)
        ->where('id', $student->id)
        ->update($payload);

    return [
        'profile_photo_path' => $path,
        'profile_photo_url' => Storage::disk('public')->url($path),
    ];
}

public function feeStatus(): array
{
    $student = $this->currentStudent();

    $result = [
        'student_id' => $student->id,
        'admission_fee' => null,
        'vouchers' => [],
        'payments' => [],
        'summary' => [
            'total_payable' => 0,
            'total_paid' => 0,
            'balance' => 0,
            'status' => 'not_found',
        ],
    ];

    $applicantId = $student->applicant_id ?? null;

    if (!$applicantId && Schema::hasTable('admission_confirmations')) {
        $confirmation = DB::table('admission_confirmations')
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->first();

        $applicantId = $confirmation->applicant_id ?? null;
    }

    if (!$applicantId) {
        return $result;
    }

    if (Schema::hasTable('applicant_fee_vouchers')) {
        $vouchers = DB::table('applicant_fee_vouchers')
            ->where('tenant_id', $student->tenant_id)
            ->where('applicant_id', $applicantId)
            ->orderByDesc('id')
            ->get();

        $result['vouchers'] = $vouchers->toArray();

        $totalPayable = 0;
        $totalPaid = 0;

        foreach ($vouchers as $voucher) {
            $amount = (float) (
                $voucher->amount
                ?? $voucher->total_amount
                ?? $voucher->payable_amount
                ?? 0
            );

            $paid = (float) (
                $voucher->paid_amount
                ?? 0
            );

            if ((($voucher->status ?? null) === 'paid' || ($voucher->status_code ?? null) === 'paid')) {
                $paid = $amount;
            }

            $totalPayable += $amount;
            $totalPaid += $paid;
        }

        $result['summary'] = [
            'total_payable' => $totalPayable,
            'total_paid' => $totalPaid,
            'balance' => max($totalPayable - $totalPaid, 0),
            'status' => $totalPayable <= $totalPaid && $totalPayable > 0 ? 'paid' : 'pending',
        ];
    }

    if (Schema::hasTable('applicant_payments')) {
        $result['payments'] = DB::table('applicant_payments')
            ->where('tenant_id', $student->tenant_id)
            ->where('applicant_id', $applicantId)
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }

    return $result;
}

public function uploadDocument(array $data, UploadedFile $file): array
{
    $student = $this->currentStudent();

    abort_if(!Schema::hasTable('student_documents'), 404, 'Student documents table not found.');

    $path = $file->store("students/{$student->id}/documents", 'public');

    $payload = [
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'document_title' => $data['document_title'],
        'document_type' => $data['document_type'],
        'file_path' => $path,
        'file_name' => $file->getClientOriginalName(),
        'mime_type' => $file->getClientMimeType(),
        'file_size' => $file->getSize(),
        'uploaded_by_student' => true,
        'uploaded_at' => now(),
        'verification_status' => 'pending',
        'remarks' => $data['remarks'] ?? null,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $payload = $this->filterPayloadByColumns('student_documents', $payload);

    $documentId = DB::table('student_documents')->insertGetId($payload);

    return [
        'document_id' => $documentId,
        'file_path' => $path,
        'file_url' => Storage::disk('public')->url($path),
    ];
}

public function researchPublications(): array
{
    $student = $this->currentStudent();

    if (!Schema::hasTable('student_research_publications')) {
        return [];
    }

    return DB::table('student_research_publications')
        ->where('tenant_id', $student->tenant_id)
        ->where('student_id', $student->id)
        ->orderByDesc('id')
        ->get()
        ->map(fn ($row) => [
            'id' => $row->id,
            'type' => $row->type,
            'title' => $row->title,
            'journal_or_conference' => $row->journal_or_conference,
            'publisher' => $row->publisher,
            'doi' => $row->doi,
            'url' => $row->url,
            'publication_year' => $row->publication_year,
            'abstract' => $row->abstract,
            'attachment_path' => $row->attachment_path,
            'attachment_url' => $row->attachment_path ? Storage::disk('public')->url($row->attachment_path) : null,
            'status' => $row->status,
            'submitted_at' => $row->submitted_at,
            'remarks' => $row->remarks,
        ])
        ->values()
        ->toArray();
}

public function storeResearchPublication(array $data, ?UploadedFile $attachment): array
{
    $student = $this->currentStudent();

    abort_if(
        !Schema::hasTable('student_research_publications'),
        404,
        'Student research/publications table not found.'
    );

    $attachmentPath = null;
    $attachmentName = null;

    if ($attachment) {
        $attachmentPath = $attachment->store("students/{$student->id}/research", 'public');
        $attachmentName = $attachment->getClientOriginalName();
    }

    $payload = [
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'type' => $data['type'] ?? 'publication',
        'title' => $data['title'],
        'journal_or_conference' => $data['journal_or_conference'] ?? null,
        'publisher' => $data['publisher'] ?? null,
        'doi' => $data['doi'] ?? null,
        'url' => $data['url'] ?? null,
        'publication_year' => $data['publication_year'] ?? null,
        'abstract' => $data['abstract'] ?? null,
        'attachment_path' => $attachmentPath,
        'attachment_name' => $attachmentName,
        'status' => 'submitted',
        'submitted_at' => now(),
        'remarks' => $data['remarks'] ?? null,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $publicationId = DB::table('student_research_publications')->insertGetId($payload);

    return [
        'publication_id' => $publicationId,
        'attachment_url' => $attachmentPath ? Storage::disk('public')->url($attachmentPath) : null,
    ];
}

public function deleteResearchPublication(int $publicationId): array
{
    $student = $this->currentStudent();

    $record = DB::table('student_research_publications')
        ->where('tenant_id', $student->tenant_id)
        ->where('student_id', $student->id)
        ->where('id', $publicationId)
        ->first();

    abort_if(!$record, 404, 'Research/publication record not found.');

    if (!empty($record->attachment_path)) {
        Storage::disk('public')->delete($record->attachment_path);
    }

    DB::table('student_research_publications')
        ->where('tenant_id', $student->tenant_id)
        ->where('student_id', $student->id)
        ->where('id', $publicationId)
        ->delete();

    return [
        'publication_id' => $publicationId,
        'deleted' => true,
    ];
}
}