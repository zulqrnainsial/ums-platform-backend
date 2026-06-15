<?php

namespace App\Modules\Student\Services;

use App\Modules\Student\Models\StudentRequest;
use App\Modules\Student\Models\StudentCourseRegistration;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentRequestAdminService
{
    public function index(array $filters): LengthAwarePaginator
    {
        $tenantId = $this->tenantId();

        $query = DB::table('student_requests as sr')
            ->join('students as s', 's.id', '=', 'sr.student_id')
            ->where('sr.tenant_id', $tenantId)
            ->where('s.tenant_id', $tenantId);

        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);

            $query->where(function ($sub) use ($q) {
                $sub->where('sr.request_no', 'like', "%{$q}%")
                    ->orWhere('s.student_no', 'like', "%{$q}%")
                    ->orWhere('s.full_name', 'like', "%{$q}%")
                    ->orWhere('s.cnic_bform', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['request_type'])) {
            $query->where('sr.request_type', $filters['request_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('sr.status', $filters['status']);
        }

        return $query
            ->select([
                'sr.*',
                's.student_no',
                's.full_name as student_name',
                's.cnic_bform',
            ])
            ->orderByDesc('sr.id')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function show(int $requestId): array
    {
        $tenantId = $this->tenantId();

        $request = DB::table('student_requests as sr')
            ->join('students as s', 's.id', '=', 'sr.student_id')
            ->where('sr.tenant_id', $tenantId)
            ->where('s.tenant_id', $tenantId)
            ->where('sr.id', $requestId)
            ->select([
                'sr.*',
                's.student_no',
                's.full_name as student_name',
                's.cnic_bform',
                's.phone',
                's.email',
            ])
            ->first();

        abort_if(!$request, 404, 'Student request not found.');

        return [
            'request' => $request,
            'payload' => $request->requested_payload_json
                ? json_decode($request->requested_payload_json, true)
                : null,
        ];
    }

    public function decide(int $requestId, array $data): array
    {
        $tenantId = $this->tenantId();

        $decision = strtolower(trim((string) $data['decision']));

        abort_if(
            !in_array($decision, ['approved', 'rejected'], true),
            422,
            'Invalid request decision.'
        );

        $request = StudentRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $requestId)
            ->firstOrFail();

        abort_if($request->status !== 'pending', 422, 'Only pending request can be decided.');

        return DB::transaction(function () use ($request, $decision, $data) {
            $appliedResult = null;

            if ($decision === 'approved' && (bool) ($data['apply_changes'] ?? false)) {
                $appliedResult = $this->applyApprovedRequest($request);
            }

            $request->update([
                'status' => $decision,
                'admin_decision_payload_json' => [
                    'decision' => $decision,
                    'apply_changes' => (bool) ($data['apply_changes'] ?? false),
                    'applied_result' => $appliedResult,
                ],
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'admin_remarks' => $data['admin_remarks'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            return [
                'request' => $request->fresh(),
                'applied_result' => $appliedResult,
            ];
        });
    }

    private function applyApprovedRequest(StudentRequest $request): ?array
    {
        return match ($request->request_type) {
            'profile_correction' => $this->applyProfileCorrection($request),
            'document_resubmission' => $this->applyDocumentResubmission($request),
            'course_add_drop' => $this->applyCourseAddDrop($request),
            default => null,
        };
    }

    private function applyProfileCorrection(StudentRequest $request): array
    {
        $payload = $request->requested_payload_json ?? [];
        $changes = $payload['requested_changes'] ?? [];

        abort_if(empty($changes), 422, 'No profile changes found.');

        $update = [];

        foreach ($changes as $field => $value) {
            if (Schema::hasColumn('students', $field)) {
                $update[$field] = $value;
            }
        }

        abort_if(empty($update), 422, 'No valid profile field found to update.');

        if (Schema::hasColumn('students', 'updated_by')) {
            $update['updated_by'] = auth()->id();
        }

        $update['updated_at'] = now();

        DB::table('students')
            ->where('tenant_id', $request->tenant_id)
            ->where('id', $request->student_id)
            ->update($update);

        return [
            'updated_table' => 'students',
            'updated_fields' => array_keys($update),
        ];
    }

    private function applyDocumentResubmission(StudentRequest $request): array
    {
        $payload = $request->requested_payload_json ?? [];
        $documentId = $request->related_document_id;

        abort_if(!$documentId, 422, 'Related document not found.');

        $update = [];

        if (!empty($payload['new_file_path']) && Schema::hasColumn('student_documents', 'file_path')) {
            $update['file_path'] = $payload['new_file_path'];
        }

        if (!empty($payload['new_file_name']) && Schema::hasColumn('student_documents', 'file_name')) {
            $update['file_name'] = $payload['new_file_name'];
        }

        if (Schema::hasColumn('student_documents', 'verification_status')) {
            $update['verification_status'] = 'pending';
        }

        if (Schema::hasColumn('student_documents', 'remarks')) {
            $update['remarks'] = 'Resubmitted by student request.';
        }

        if (Schema::hasColumn('student_documents', 'updated_by')) {
            $update['updated_by'] = auth()->id();
        }

        $update['updated_at'] = now();

        DB::table('student_documents')
            ->where('tenant_id', $request->tenant_id)
            ->where('student_id', $request->student_id)
            ->where('id', $documentId)
            ->update($update);

        return [
            'updated_table' => 'student_documents',
            'document_id' => $documentId,
        ];
    }

    private function applyCourseAddDrop(StudentRequest $request): array
    {
        $payload = $request->requested_payload_json ?? [];
        $actionType = $payload['action_type'] ?? null;

        if ($actionType === 'add') {
            return $this->applyCourseAdd($request, $payload);
        }

        if ($actionType === 'drop') {
            return $this->applyCourseDrop($request, $payload);
        }

        abort(422, 'Invalid course add/drop payload.');
    }

    private function applyCourseAdd(StudentRequest $request, array $payload): array
    {
        abort_if(!Schema::hasTable('student_course_registrations'), 404, 'Course registrations table not found.');

        $curriculumSubjectId = $payload['curriculum_subject_id'] ?? null;

        abort_if(!$curriculumSubjectId, 422, 'Curriculum subject is required.');

        $curriculumSubject = DB::table('curriculum_subjects')
            ->where('tenant_id', $request->tenant_id)
            ->where('id', $curriculumSubjectId)
            ->first();

        abort_if(!$curriculumSubject, 404, 'Curriculum subject not found.');

        $subjectId = $curriculumSubject->subject_id ?? null;

        if ($subjectId) {
            $exists = DB::table('student_course_registrations')
                ->where('tenant_id', $request->tenant_id)
                ->where('student_id', $request->student_id)
                ->where('student_enrollment_id', $request->student_enrollment_id)
                ->where('subject_id', $subjectId)
                ->whereIn('status', ['registered', 'approved', 'active'])
                ->exists();

            abort_if($exists, 422, 'Course is already registered.');
        }

        $subject = null;

        if ($subjectId && Schema::hasTable('subjects')) {
            $subject = DB::table('subjects')
                ->where('id', $subjectId)
                ->first();
        }

        $registration = StudentCourseRegistration::create([
            'tenant_id' => $request->tenant_id,
            'student_id' => $request->student_id,
            'student_enrollment_id' => $request->student_enrollment_id,
            'program_id' => $curriculumSubject->program_id ?? null,
            'academic_term_id' => $curriculumSubject->academic_term_id ?? null,
            'term_id' => $curriculumSubject->term_id ?? null,
            'curriculum_id' => $curriculumSubject->curriculum_id ?? null,
            'curriculum_subject_id' => $curriculumSubject->id,
            'subject_id' => $subjectId,
            'course_code' => $curriculumSubject->course_code ?? ($subject->code ?? null),
            'course_title' => $curriculumSubject->course_title ?? ($subject->name ?? null),
            'credit_hours' => $curriculumSubject->credit_hours ?? ($subject->credit_hours ?? 0),
            'subject_type_code' => $curriculumSubject->subject_type_code ?? null,
            'registration_type' => 'add_request',
            'status' => 'registered',
            'is_locked' => false,
            'registered_at' => now(),
            'remarks' => 'Approved student add request: ' . ($payload['reason'] ?? ''),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return [
            'created_table' => 'student_course_registrations',
            'registration_id' => $registration->id,
        ];
    }

    private function applyCourseDrop(StudentRequest $request, array $payload): array
    {
        $courseRegistrationId = $payload['course_registration_id'] ?? null;

        abort_if(!$courseRegistrationId, 422, 'Course registration is required.');

        DB::table('student_course_registrations')
            ->where('tenant_id', $request->tenant_id)
            ->where('student_id', $request->student_id)
            ->where('id', $courseRegistrationId)
            ->update([
                'status' => 'dropped',
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return [
            'updated_table' => 'student_course_registrations',
            'registration_id' => $courseRegistrationId,
            'status' => 'dropped',
        ];
    }

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }
}