<?php

namespace App\Modules\FacultyAllocation\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FacultyAllocationService
{
    public function context(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        return [
            'academic_sessions' => $this->options('academic_sessions', $tenantId, 'name', [
                'status' => ['active', 'planned'],
            ]),
            'academic_terms' => $this->academicTerms($tenantId, $filters),
            'programs' => $this->options('programs', $tenantId, 'name', [
                'status' => ['active'],
            ]),
            'student_batches' => $this->studentBatches($tenantId, $filters),
            'sections' => $this->sections($tenantId, $filters),
            'curriculum_subjects' => $this->curriculumSubjects($tenantId, $filters),
            'faculty_members' => $this->facultyMemberOptions($tenantId),
            'employment_types' => $this->lookupValues($tenantId, 'faculty_employment_type'),
            'designations' => $this->lookupValues($tenantId, 'faculty_designation'),
            'subject_types' => $this->lookupValues($tenantId, 'subject_type'),
        ];
    }

    public function facultyMembers(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('faculty_members as fm')
            ->where('fm.tenant_id', $tenantId)
            ->whereNull('fm.deleted_at');

        foreach (['department_id', 'faculty_id', 'employment_type_code', 'designation_code', 'status_code'] as $field) {
            if (!empty($filters[$field])) {
                $query->where("fm.$field", $filters[$field]);
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';

            $query->where(function ($q) use ($search) {
                $q->where('fm.full_name', 'like', $search)
                    ->orWhere('fm.employee_no', 'like', $search)
                    ->orWhere('fm.email', 'like', $search);
            });
        }

        return $query
            ->select('fm.*')
            ->orderBy('fm.full_name')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }
    public function createSplitCourseOfferings(array $data): array
{
    $tenantId = $this->tenantId();

    foreach ([
        'academic_session_id',
        'academic_term_id',
        'program_id',
        'student_batch_id',
        'curriculum_subject_id',
        'offering_types',
    ] as $field) {
        abort_if(empty($data[$field]), 422, "{$field} is required.");
    }

    $curriculumSubject = DB::table('curriculum_subjects')
        ->where('id', $data['curriculum_subject_id'])
        ->first();

    abort_if(!$curriculumSubject, 404, 'Curriculum subject not found.');

    $created = [];

    DB::transaction(function () use ($tenantId, $data, $curriculumSubject, &$created) {
        foreach ($data['offering_types'] as $typeConfig) {
            $subjectType = $typeConfig['subject_type_code'] ?? 'theory';

            if ($subjectType === 'theory') {
                foreach (($typeConfig['section_ids'] ?? []) as $sectionId) {
                    $payload = $this->buildSplitOfferingPayload(
                        $tenantId,
                        $data,
                        $curriculumSubject,
                        $subjectType,
                        $sectionId,
                        null,
                        $typeConfig
                    );

                    $created[] = $this->insertCourseOfferingIfMissing($payload);
                }

                continue;
            }

            foreach (($typeConfig['teaching_group_ids'] ?? []) as $groupId) {
                $group = DB::table('academic_teaching_groups')
                    ->where('tenant_id', $tenantId)
                    ->where('id', $groupId)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$group) {
                    continue;
                }

                $payload = $this->buildSplitOfferingPayload(
                    $tenantId,
                    $data,
                    $curriculumSubject,
                    $subjectType,
                    $group->section_id,
                    $group->id,
                    $typeConfig
                );

                $created[] = $this->insertCourseOfferingIfMissing($payload);
            }
        }
    });

    return collect($created)
        ->filter()
        ->values()
        ->toArray();
}
private function buildSplitOfferingPayload(
    int $tenantId,
    array $data,
    object $curriculumSubject,
    string $subjectType,
    ?int $sectionId,
    ?int $teachingGroupId,
    array $typeConfig
): array {
    $creditHours = (float) (
        $typeConfig['credit_hours']
        ?? $curriculumSubject->credit_hours
        ?? 0
    );

    $contactHours = (float) (
        $typeConfig['contact_hours_per_week']
        ?? $this->defaultContactHours($subjectType, $creditHours)
    );

    return $this->onlyColumns('course_offerings', [
        'tenant_id' => $tenantId,

        'academic_session_id' => $data['academic_session_id'],
        'academic_term_id' => $data['academic_term_id'],
        'program_id' => $data['program_id'],
        'student_batch_id' => $data['student_batch_id'],
        'section_id' => $sectionId,
        'academic_teaching_group_id' => $teachingGroupId,

        'curriculum_subject_id' => $data['curriculum_subject_id'],
        'subject_id' => $curriculumSubject->subject_id ?? null,

        'course_code' => $curriculumSubject->subject_code ?? null,
        'course_title' => $curriculumSubject->subject_name ?? null,

        'subject_type_code' => $subjectType,

        'credit_hours' => $creditHours,
        'contact_hours_per_week' => $contactHours,

        'required_sessions_per_week' => $typeConfig['required_sessions_per_week'] ?? null,
        'required_hours_per_session' => $typeConfig['required_hours_per_session'] ?? null,

        'required_room_type_code' => $typeConfig['required_room_type_code'] ?? null,
        'required_capacity' => $typeConfig['required_capacity'] ?? null,

        'requires_multimedia' => (bool) ($typeConfig['requires_multimedia'] ?? false),
        'requires_lab' => (bool) ($typeConfig['requires_lab'] ?? in_array($subjectType, ['practical', 'lab'], true)),

        'status_code' => $typeConfig['status_code'] ?? 'offered',

        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

private function insertCourseOfferingIfMissing(array $payload): array
{
    $existing = DB::table('course_offerings')
        ->where('tenant_id', $payload['tenant_id'])
        ->where('academic_session_id', $payload['academic_session_id'])
        ->where('academic_term_id', $payload['academic_term_id'])
        ->where('program_id', $payload['program_id'])
        ->where('student_batch_id', $payload['student_batch_id'])
        ->where('curriculum_subject_id', $payload['curriculum_subject_id'])
        ->where('subject_type_code', $payload['subject_type_code'])
        ->where(function ($q) use ($payload) {
            if (!empty($payload['section_id'])) {
                $q->where('section_id', $payload['section_id']);
            } else {
                $q->whereNull('section_id');
            }
        })
        ->where(function ($q) use ($payload) {
            if (!empty($payload['academic_teaching_group_id'])) {
                $q->where('academic_teaching_group_id', $payload['academic_teaching_group_id']);
            } else {
                $q->whereNull('academic_teaching_group_id');
            }
        })
        ->whereNull('deleted_at')
        ->first();

    if ($existing) {
        return (array) $existing;
    }

    $id = DB::table('course_offerings')->insertGetId($payload);

    return $this->courseOffering($id);
}
public function teachingGroups(array $filters = []): array
{
    $tenantId = $this->tenantId();

    $query = DB::table('academic_teaching_groups as atg')
        ->leftJoin('academic_sessions as acs', 'acs.id', '=', 'atg.academic_session_id')
        ->leftJoin('academic_terms as act', 'act.id', '=', 'atg.academic_term_id')
        ->leftJoin('programs as p', 'p.id', '=', 'atg.program_id')
        ->leftJoin('student_batches as sb', 'sb.id', '=', 'atg.student_batch_id')
        ->leftJoin('sections as sec', 'sec.id', '=', 'atg.section_id')
        ->where('atg.tenant_id', $tenantId)
        ->whereNull('atg.deleted_at');

    foreach ([
        'academic_session_id',
        'academic_term_id',
        'program_id',
        'student_batch_id',
        'section_id',
        'group_type_code',
        'status_code',
    ] as $field) {
        if (!empty($filters[$field])) {
            $query->where("atg.$field", $filters[$field]);
        }
    }

    return $query
        ->select([
            'atg.*',
            'acs.name as academic_session_name',
            'act.name as academic_term_name',
            'p.name as program_name',
            'sb.name as batch_name',
            'sec.name as section_name',
        ])
        ->orderBy('atg.group_type_code')
        ->orderBy('atg.group_code')
        ->paginate((int) ($filters['per_page'] ?? 15))
        ->toArray();
}

public function createTeachingGroup(array $data): array
{
    $tenantId = $this->tenantId();

    $payload = $this->onlyColumns('academic_teaching_groups', array_merge($data, [
        'tenant_id' => $tenantId,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));

    $id = DB::table('academic_teaching_groups')->insertGetId($payload);

    return $this->teachingGroup($id);
}

public function updateTeachingGroup(int $id, array $data): array
{
    $tenantId = $this->tenantId();

    $group = DB::table('academic_teaching_groups')
        ->where('tenant_id', $tenantId)
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();

    abort_if(!$group, 404, 'Teaching group not found.');

    $payload = $this->onlyColumns('academic_teaching_groups', array_merge($data, [
        'updated_by' => auth()->id(),
        'updated_at' => now(),
    ]));

    DB::table('academic_teaching_groups')
        ->where('id', $id)
        ->update($payload);

    return $this->teachingGroup($id);
}

public function teachingGroupMembers(int $groupId, array $filters = []): array
{
    $tenantId = $this->tenantId();

    $group = DB::table('academic_teaching_groups')
        ->where('tenant_id', $tenantId)
        ->where('id', $groupId)
        ->whereNull('deleted_at')
        ->first();

    abort_if(!$group, 404, 'Teaching group not found.');

    return DB::table('academic_teaching_group_members as atgm')
        ->join('students as s', 's.id', '=', 'atgm.student_id')
        ->leftJoin('student_enrollments as se', 'se.id', '=', 'atgm.student_enrollment_id')
        ->where('atgm.tenant_id', $tenantId)
        ->where('atgm.academic_teaching_group_id', $groupId)
        ->select([
            'atgm.*',
            's.student_no',
            's.full_name as student_name',
            'se.roll_no',
            'se.registration_no',
            'se.section_id',
            'se.section',
        ])
        ->orderBy('se.roll_sequence_no')
        ->orderBy('s.full_name')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
}

public function eligibleStudentsForTeachingGroup(array $filters): array
{
    $tenantId = $this->tenantId();

    foreach ([
        'academic_session_id',
        'program_id',
        'student_batch_id',
    ] as $field) {
        abort_if(empty($filters[$field]), 422, "{$field} is required.");
    }

    $query = DB::table('student_enrollments as se')
        ->join('students as s', 's.id', '=', 'se.student_id')
        ->where('se.tenant_id', $tenantId)
        ->where('se.academic_session_id', $filters['academic_session_id'])
        ->where('se.program_id', $filters['program_id'])
        ->where('se.student_batch_id', $filters['student_batch_id'])
        ->whereIn('se.status_code', ['active', 'enrolled'])
        ->whereIn('se.enrollment_status_code', ['active', 'enrolled']);

    if (!empty($filters['section_id']) && Schema::hasColumn('student_enrollments', 'section_id')) {
        $query->where('se.section_id', $filters['section_id']);
    }

    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';

        $query->where(function ($q) use ($search) {
            $q->where('s.full_name', 'like', $search)
                ->orWhere('s.student_no', 'like', $search)
                ->orWhere('se.roll_no', 'like', $search)
                ->orWhere('se.registration_no', 'like', $search);
        });
    }

    return $query
        ->select([
            's.id as student_id',
            'se.id as student_enrollment_id',
            's.student_no',
            's.full_name as student_name',
            'se.roll_no',
            'se.registration_no',
            'se.section_id',
            'se.section',
        ])
        ->orderBy('se.roll_sequence_no')
        ->orderBy('s.full_name')
        ->limit((int) ($filters['limit'] ?? 300))
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
}

public function syncTeachingGroupMembers(int $groupId, array $studentRows): array
{
    $tenantId = $this->tenantId();

    $group = DB::table('academic_teaching_groups')
        ->where('tenant_id', $tenantId)
        ->where('id', $groupId)
        ->whereNull('deleted_at')
        ->first();

    abort_if(!$group, 404, 'Teaching group not found.');

    DB::transaction(function () use ($tenantId, $groupId, $studentRows) {
        DB::table('academic_teaching_group_members')
            ->where('tenant_id', $tenantId)
            ->where('academic_teaching_group_id', $groupId)
            ->delete();

        foreach ($studentRows as $row) {
            abort_if(empty($row['student_id']), 422, 'student_id is required.');
            abort_if(empty($row['student_enrollment_id']), 422, 'student_enrollment_id is required.');

            DB::table('academic_teaching_group_members')->insert([
                'tenant_id' => $tenantId,
                'academic_teaching_group_id' => $groupId,
                'student_id' => $row['student_id'],
                'student_enrollment_id' => $row['student_enrollment_id'],
                'status_code' => $row['status_code'] ?? 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('academic_teaching_groups')
            ->where('id', $groupId)
            ->update([
                'actual_strength' => count($studentRows),
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);
    });

    return [
        'group' => $this->teachingGroup($groupId),
        'members' => $this->teachingGroupMembers($groupId),
    ];
}

public function createPracticalGroupsFromSection(array $data): array
{
    $tenantId = $this->tenantId();

    foreach ([
        'academic_session_id',
        'academic_term_id',
        'program_id',
        'student_batch_id',
        'section_id',
        'group_count',
    ] as $field) {
        abort_if(empty($data[$field]), 422, "{$field} is required.");
    }

    $groupCount = max(1, (int) $data['group_count']);
    $groupPrefix = $data['group_prefix'] ?? 'PG';

    $students = $this->eligibleStudentsForTeachingGroup([
        'academic_session_id' => $data['academic_session_id'],
        'program_id' => $data['program_id'],
        'student_batch_id' => $data['student_batch_id'],
        'section_id' => $data['section_id'],
        'limit' => 1000,
    ]);

    $chunks = array_chunk($students, (int) ceil(max(count($students), 1) / $groupCount));

    $created = [];

    DB::transaction(function () use ($tenantId, $data, $chunks, $groupCount, $groupPrefix, &$created) {
        for ($i = 0; $i < $groupCount; $i++) {
            $letter = chr(65 + $i);
            $code = "{$groupPrefix}-{$letter}";
            $name = "Practical Group {$letter}";

            $groupId = DB::table('academic_teaching_groups')->insertGetId([
                'tenant_id' => $tenantId,
                'academic_session_id' => $data['academic_session_id'],
                'academic_term_id' => $data['academic_term_id'],
                'program_id' => $data['program_id'],
                'student_batch_id' => $data['student_batch_id'],
                'section_id' => $data['section_id'],
                'group_code' => $code,
                'group_name' => $name,
                'group_type_code' => $data['group_type_code'] ?? 'practical_group',
                'capacity' => $data['capacity'] ?? null,
                'actual_strength' => count($chunks[$i] ?? []),
                'status_code' => 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (($chunks[$i] ?? []) as $student) {
                DB::table('academic_teaching_group_members')->insert([
                    'tenant_id' => $tenantId,
                    'academic_teaching_group_id' => $groupId,
                    'student_id' => $student['student_id'],
                    'student_enrollment_id' => $student['student_enrollment_id'],
                    'status_code' => 'active',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $created[] = $this->teachingGroup($groupId);
        }
    });

    return $created;
}

private function teachingGroup(int $id): array
{
    return (array) DB::table('academic_teaching_groups')->where('id', $id)->first();
}
    public function createFacultyMember(array $data): array
    {
        $tenantId = $this->tenantId();

        $payload = $this->onlyColumns('faculty_members', array_merge($data, [
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $id = DB::table('faculty_members')->insertGetId($payload);

        return $this->facultyMember($id);
    }

    public function updateFacultyMember(int $id, array $data): array
    {
        $tenantId = $this->tenantId();

        $faculty = DB::table('faculty_members')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$faculty, 404, 'Faculty member not found.');

        $payload = $this->onlyColumns('faculty_members', array_merge($data, [
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]));

        DB::table('faculty_members')
            ->where('id', $id)
            ->update($payload);

        return $this->facultyMember($id);
    }

    public function loadPolicies(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('faculty_load_policies')
            ->where('tenant_id', $tenantId);

        foreach (['employment_type_code', 'designation_code', 'faculty_type_code', 'status_code'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query
            ->orderBy('employment_type_code')
            ->orderBy('designation_code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    public function saveLoadPolicy(array $data): array
    {
        $tenantId = $this->tenantId();

        $payload = $this->onlyColumns('faculty_load_policies', array_merge($data, [
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $id = DB::table('faculty_load_policies')->insertGetId($payload);

        return (array) DB::table('faculty_load_policies')->where('id', $id)->first();
    }

    public function availability(int $facultyMemberId, array $filters = []): array
    {
        $tenantId = $this->tenantId();

        return DB::table('faculty_availability')
            ->where('tenant_id', $tenantId)
            ->where('faculty_member_id', $facultyMemberId)
            ->when(!empty($filters['academic_session_id']), fn ($q) => $q->where('academic_session_id', $filters['academic_session_id']))
            ->when(!empty($filters['academic_term_id']), fn ($q) => $q->where('academic_term_id', $filters['academic_term_id']))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    public function saveAvailability(int $facultyMemberId, array $records): array
    {
        $tenantId = $this->tenantId();

        $faculty = DB::table('faculty_members')
            ->where('tenant_id', $tenantId)
            ->where('id', $facultyMemberId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$faculty, 404, 'Faculty member not found.');

        DB::transaction(function () use ($tenantId, $facultyMemberId, $records) {
            foreach ($records as $record) {
                $payload = $this->onlyColumns('faculty_availability', array_merge($record, [
                    'tenant_id' => $tenantId,
                    'faculty_member_id' => $facultyMemberId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));

                DB::table('faculty_availability')->insert($payload);
            }
        });

        return $this->availability($facultyMemberId);
    }

    public function subjectExpertise(int $facultyMemberId): array
    {
        $tenantId = $this->tenantId();

        return DB::table('faculty_subject_expertise as fse')
            ->leftJoin('subjects as s', 's.id', '=', 'fse.subject_id')
            ->leftJoin('curriculum_subjects as cs', 'cs.id', '=', 'fse.curriculum_subject_id')
            ->where('fse.tenant_id', $tenantId)
            ->where('fse.faculty_member_id', $facultyMemberId)
            ->select([
                'fse.*',
                's.name as subject_name',
                'cs.subject_code as curriculum_subject_code',
                'cs.subject_name as curriculum_subject_name',
            ])
            ->orderBy('s.name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    public function saveSubjectExpertise(int $facultyMemberId, array $records): array
    {
        $tenantId = $this->tenantId();

        $faculty = DB::table('faculty_members')
            ->where('tenant_id', $tenantId)
            ->where('id', $facultyMemberId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$faculty, 404, 'Faculty member not found.');

        DB::transaction(function () use ($tenantId, $facultyMemberId, $records) {
            foreach ($records as $record) {
                $payload = $this->onlyColumns('faculty_subject_expertise', array_merge($record, [
                    'tenant_id' => $tenantId,
                    'faculty_member_id' => $facultyMemberId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));

                DB::table('faculty_subject_expertise')->insert($payload);
            }
        });

        return $this->subjectExpertise($facultyMemberId);
    }

    public function courseOfferings(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('course_offerings as co')
            ->leftJoin('academic_sessions as acs', 'acs.id', '=', 'co.academic_session_id')
            ->leftJoin('academic_terms as act', 'act.id', '=', 'co.academic_term_id')
            ->leftJoin('programs as p', 'p.id', '=', 'co.program_id')
            ->leftJoin('student_batches as sb', 'sb.id', '=', 'co.student_batch_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'co.section_id')
            ->leftJoin('academic_teaching_groups as atg', 'atg.id', '=', 'co.academic_teaching_group_id')
            ->where('co.tenant_id', $tenantId)
            ->whereNull('co.deleted_at');

        foreach ([
            'academic_session_id',
            'academic_term_id',
            'program_id',
            'student_batch_id',
            'section_id',
            'academic_teaching_group_id',
            'curriculum_subject_id',
            'subject_type_code',
            'status_code',
        ] as $field) {
            if (!empty($filters[$field])) {
                $query->where("co.$field", $filters[$field]);
            }
        }

        return $query
            ->select([
                'co.*',
                'acs.name as academic_session_name',
                'act.name as academic_term_name',
                'p.name as program_name',
                'sb.name as batch_name',
                'sec.code as section_code',
                'sec.name as section_name',
                'atg.group_code as teaching_group_code',
                'atg.group_name as teaching_group_name',
            ])
            ->orderBy('co.course_code')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function createCourseOffering(array $data): array
    {
        $tenantId = $this->tenantId();

        $data = $this->hydrateCourseOfferingFromCurriculum($data);

        $payload = $this->onlyColumns('course_offerings', array_merge($data, [
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $id = DB::table('course_offerings')->insertGetId($payload);

        return $this->courseOffering($id);
    }

    public function updateCourseOffering(int $id, array $data): array
    {
        $tenantId = $this->tenantId();

        $offering = DB::table('course_offerings')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$offering, 404, 'Course offering not found.');

        $data = $this->hydrateCourseOfferingFromCurriculum($data);

        $payload = $this->onlyColumns('course_offerings', array_merge($data, [
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]));

        DB::table('course_offerings')
            ->where('id', $id)
            ->update($payload);

        return $this->courseOffering($id);
    }

    public function allocations(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('course_teacher_allocations as cta')
            ->join('course_offerings as co', 'co.id', '=', 'cta.course_offering_id')
            ->join('faculty_members as fm', 'fm.id', '=', 'cta.faculty_member_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'co.section_id')
            ->leftJoin('academic_teaching_groups as atg', 'atg.id', '=', 'co.academic_teaching_group_id');

        foreach ([
            'course_offering_id',
            'faculty_member_id',
            'allocation_role_code',
            'allocation_status_code',
        ] as $field) {
            if (!empty($filters[$field])) {
                $query->where("cta.$field", $filters[$field]);
            }
        }

        return $query
            ->select([
                'cta.*',
                'co.course_code',
                'co.course_title',
                'co.subject_type_code',
                'co.credit_hours',
                'co.contact_hours_per_week',
                'sec.code as section_code',
                'sec.name as section_name',
                'atg.group_code as teaching_group_code',
                'atg.group_name as teaching_group_name',
                'fm.full_name as faculty_name',
                'fm.employee_no',
                'fm.employment_type_code',
                'fm.designation_code',
            ])
            ->orderBy('co.course_code')
            ->orderBy('fm.full_name')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function validateAllocation(array $data): array
    {
        $tenantId = $this->tenantId();

        $offering = DB::table('course_offerings')
            ->where('tenant_id', $tenantId)
            ->where('id', $data['course_offering_id'])
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$offering, 404, 'Course offering not found.');

        $faculty = DB::table('faculty_members')
            ->where('tenant_id', $tenantId)
            ->where('id', $data['faculty_member_id'])
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$faculty, 404, 'Faculty member not found.');

        $conflicts = [];

        $conflicts = array_merge($conflicts, $this->validateFacultySubjectType($faculty, $offering));
        $conflicts = array_merge($conflicts, $this->validateFacultyLoad($faculty, $offering, $data));
        $conflicts = array_merge($conflicts, $this->validateFacultyExpertise($faculty, $offering));

        return [
            'valid' => collect($conflicts)->where('conflict_severity', 'error')->isEmpty(),
            'conflicts' => $conflicts,
        ];
    }

    public function createAllocation(array $data): array
    {
        $tenantId = $this->tenantId();

        $validation = $this->validateAllocation($data);

        $status = $validation['valid'] ? 'valid' : 'conflicted';

        $offering = DB::table('course_offerings')
            ->where('tenant_id', $tenantId)
            ->where('id', $data['course_offering_id'])
            ->first();

        $payload = $this->onlyColumns('course_teacher_allocations', array_merge($data, [
            'tenant_id' => $tenantId,
            'allocated_credit_hours' => $data['allocated_credit_hours'] ?? $offering->credit_hours ?? 0,
            'allocated_contact_hours' => $data['allocated_contact_hours'] ?? $offering->contact_hours_per_week ?? 0,
            'allocation_status_code' => $status,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $allocationId = null;

        DB::transaction(function () use ($payload, $validation, &$allocationId) {
            $allocationId = DB::table('course_teacher_allocations')->insertGetId($payload);

            foreach ($validation['conflicts'] as $conflict) {
                DB::table('faculty_allocation_conflicts')->insert([
                    'tenant_id' => $payload['tenant_id'],
                    'course_teacher_allocation_id' => $allocationId,
                    'course_offering_id' => $payload['course_offering_id'],
                    'faculty_member_id' => $payload['faculty_member_id'],
                    'conflict_code' => $conflict['conflict_code'],
                    'conflict_severity' => $conflict['conflict_severity'],
                    'conflict_message' => $conflict['conflict_message'],
                    'conflict_context' => json_encode($conflict['conflict_context'] ?? []),
                    'status_code' => 'open',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return [
            'allocation' => $this->allocation($allocationId),
            'validation' => $validation,
        ];
    }

    public function conflicts(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('faculty_allocation_conflicts as fac')
            ->leftJoin('faculty_members as fm', 'fm.id', '=', 'fac.faculty_member_id')
            ->leftJoin('course_offerings as co', 'co.id', '=', 'fac.course_offering_id')
            ->where('fac.tenant_id', $tenantId);

        foreach (['faculty_member_id', 'course_offering_id', 'conflict_code', 'conflict_severity', 'status_code'] as $field) {
            if (!empty($filters[$field])) {
                $query->where("fac.$field", $filters[$field]);
            }
        }

        return $query
            ->select([
                'fac.*',
                'fm.full_name as faculty_name',
                'co.course_code',
                'co.course_title',
                'co.subject_type_code',
            ])
            ->orderByDesc('fac.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    private function facultyMember(int $id): array
    {
        return (array) DB::table('faculty_members')->where('id', $id)->first();
    }

    private function courseOffering(int $id): array
    {
        return (array) DB::table('course_offerings')->where('id', $id)->first();
    }

    private function allocation(int $id): array
    {
        return (array) DB::table('course_teacher_allocations')->where('id', $id)->first();
    }

    private function validateFacultySubjectType(object $faculty, object $offering): array
    {
        $policy = $this->loadPolicyForFaculty($faculty);

        if (!$policy) {
            return [[
                'conflict_code' => 'LOAD_POLICY_MISSING',
                'conflict_severity' => 'warning',
                'conflict_message' => 'No faculty load policy found for this employment/designation.',
                'conflict_context' => [
                    'employment_type_code' => $faculty->employment_type_code,
                    'designation_code' => $faculty->designation_code,
                ],
            ]];
        }

        $type = $offering->subject_type_code;

        $allowed = match ($type) {
            'theory' => (bool) $policy->allow_theory,
            'practical' => (bool) $policy->allow_practical,
            'lab' => (bool) $policy->allow_lab,
            'tutorial' => (bool) $policy->allow_tutorial,
            default => true,
        };

        if ($allowed) {
            return [];
        }

        return [[
            'conflict_code' => 'SUBJECT_TYPE_NOT_ALLOWED',
            'conflict_severity' => 'error',
            'conflict_message' => "Faculty is not allowed to teach {$type} offerings.",
            'conflict_context' => [
                'subject_type_code' => $type,
            ],
        ]];
    }

    private function validateFacultyLoad(object $faculty, object $offering, array $data): array
    {
        $policy = $this->loadPolicyForFaculty($faculty);

        if (!$policy) {
            return [];
        }

        $tenantId = $this->tenantId();

        $current = DB::table('course_teacher_allocations')
            ->where('tenant_id', $tenantId)
            ->where('faculty_member_id', $faculty->id)
            ->whereNull('deleted_at')
            ->whereIn('allocation_status_code', ['valid', 'approved'])
            ->selectRaw('COALESCE(SUM(allocated_credit_hours), 0) as credit_hours, COALESCE(SUM(allocated_contact_hours), 0) as contact_hours')
            ->first();

        $newCredit = (float) ($data['allocated_credit_hours'] ?? $offering->credit_hours ?? 0);
        $newContact = (float) ($data['allocated_contact_hours'] ?? $offering->contact_hours_per_week ?? 0);

        $totalCredit = (float) $current->credit_hours + $newCredit;
        $totalContact = (float) $current->contact_hours + $newContact;

        $conflicts = [];

        if ($policy->max_weekly_credit_hours !== null && $totalCredit > (float) $policy->max_weekly_credit_hours) {
            $conflicts[] = [
                'conflict_code' => 'MAX_WEEKLY_CREDIT_HOURS_EXCEEDED',
                'conflict_severity' => 'error',
                'conflict_message' => 'Faculty weekly credit-hour load would exceed the allowed policy.',
                'conflict_context' => [
                    'current_credit_hours' => (float) $current->credit_hours,
                    'new_credit_hours' => $newCredit,
                    'total_credit_hours' => $totalCredit,
                    'max_weekly_credit_hours' => (float) $policy->max_weekly_credit_hours,
                ],
            ];
        }

        if ($policy->max_weekly_contact_hours !== null && $totalContact > (float) $policy->max_weekly_contact_hours) {
            $conflicts[] = [
                'conflict_code' => 'MAX_WEEKLY_CONTACT_HOURS_EXCEEDED',
                'conflict_severity' => 'error',
                'conflict_message' => 'Faculty weekly contact-hour load would exceed the allowed policy.',
                'conflict_context' => [
                    'current_contact_hours' => (float) $current->contact_hours,
                    'new_contact_hours' => $newContact,
                    'total_contact_hours' => $totalContact,
                    'max_weekly_contact_hours' => (float) $policy->max_weekly_contact_hours,
                ],
            ];
        }

        return $conflicts;
    }

    private function validateFacultyExpertise(object $faculty, object $offering): array
    {
        $exists = DB::table('faculty_subject_expertise')
            ->where('tenant_id', $faculty->tenant_id)
            ->where('faculty_member_id', $faculty->id)
            ->where('status_code', 'active')
            ->where('can_teach', true)
            ->where(function ($q) use ($offering) {
                if ($offering->curriculum_subject_id) {
                    $q->orWhere('curriculum_subject_id', $offering->curriculum_subject_id);
                }

                if ($offering->subject_id) {
                    $q->orWhere('subject_id', $offering->subject_id);
                }
            })
            ->exists();

        if ($exists) {
            return [];
        }

        return [[
            'conflict_code' => 'SUBJECT_EXPERTISE_NOT_FOUND',
            'conflict_severity' => 'warning',
            'conflict_message' => 'Faculty subject expertise is not defined for this course.',
            'conflict_context' => [
                'curriculum_subject_id' => $offering->curriculum_subject_id,
                'subject_id' => $offering->subject_id,
            ],
        ]];
    }

    private function loadPolicyForFaculty(object $faculty): ?object
    {
        return DB::table('faculty_load_policies')
            ->where('tenant_id', $faculty->tenant_id)
            ->where('status_code', 'active')
            ->where(function ($q) use ($faculty) {
                $q->whereNull('employment_type_code')
                    ->orWhere('employment_type_code', $faculty->employment_type_code);
            })
            ->where(function ($q) use ($faculty) {
                $q->whereNull('designation_code')
                    ->orWhere('designation_code', $faculty->designation_code);
            })
            ->where(function ($q) use ($faculty) {
                $q->whereNull('faculty_type_code')
                    ->orWhere('faculty_type_code', $faculty->faculty_type_code);
            })
            ->orderByRaw('employment_type_code IS NULL')
            ->orderByRaw('designation_code IS NULL')
            ->orderByRaw('faculty_type_code IS NULL')
            ->first();
    }

    private function hydrateCourseOfferingFromCurriculum(array $data): array
    {
        if (empty($data['curriculum_subject_id'])) {
            return $data;
        }

        $subject = DB::table('curriculum_subjects')
            ->where('id', $data['curriculum_subject_id'])
            ->first();

        if (!$subject) {
            return $data;
        }

        $data['subject_id'] = $data['subject_id'] ?? ($subject->subject_id ?? null);
        $data['course_code'] = $data['course_code'] ?? ($subject->subject_code ?? null);
        $data['course_title'] = $data['course_title'] ?? ($subject->subject_name ?? null);
        $data['credit_hours'] = $data['credit_hours'] ?? ($subject->credit_hours ?? 0);

        if (empty($data['contact_hours_per_week'])) {
            $data['contact_hours_per_week'] = $this->defaultContactHours(
                (string) ($data['subject_type_code'] ?? $subject->subject_type_code ?? 'theory'),
                (float) ($data['credit_hours'] ?? 0)
            );
        }

        return $data;
    }

    private function defaultContactHours(string $subjectType, float $creditHours): float
    {
        return match ($subjectType) {
            'practical', 'lab', 'studio' => $creditHours * 2,
            default => $creditHours,
        };
    }

    private function options(string $table, int $tenantId, string $labelColumn, array $filters = []): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        foreach ($filters as $field => $values) {
            if (Schema::hasColumn($table, $field)) {
                $query->whereIn($field, $values);
            }
        }

        return $query
            ->select('id as value', DB::raw("{$labelColumn} as label"))
            ->orderBy($labelColumn)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function academicTerms(int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('academic_terms')) {
            return [];
        }

        $query = DB::table('academic_terms')->where('tenant_id', $tenantId);

        if (!empty($filters['program_id']) && Schema::hasColumn('academic_terms', 'program_id')) {
            $query->where(function ($q) use ($filters) {
                $q->where('program_id', $filters['program_id'])->orWhereNull('program_id');
            });
        }

        if (Schema::hasColumn('academic_terms', 'status')) {
            $query->where('status', 'active');
        }

        return $query
            ->select('id as value', 'name as label', 'code')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function studentBatches(int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('student_batches')) {
            return [];
        }

        $query = DB::table('student_batches')->where('tenant_id', $tenantId);

        foreach (['academic_session_id', 'program_id'] as $field) {
            if (!empty($filters[$field]) && Schema::hasColumn('student_batches', $field)) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query
            ->select('id as value', DB::raw("CONCAT(code, ' - ', name) as label"), 'code')
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function sections(int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('sections')) {
            return [];
        }

        $query = DB::table('sections')->where('tenant_id', $tenantId);

        foreach (['program_id', 'academic_term_id'] as $field) {
            if (!empty($filters[$field]) && Schema::hasColumn('sections', $field)) {
                $query->where($field, $filters[$field]);
            }
        }

        if (Schema::hasColumn('sections', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query
            ->select('id as value', 'name as label', 'code')
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function curriculumSubjects(int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('curriculum_subjects')) {
            return [];
        }

        $query = DB::table('curriculum_subjects');

        if (Schema::hasColumn('curriculum_subjects', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        foreach (['program_id', 'academic_term_id'] as $field) {
            if (!empty($filters[$field]) && Schema::hasColumn('curriculum_subjects', $field)) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query
            ->select([
                'id as value',
                DB::raw("CONCAT(subject_code, ' - ', subject_name) as label"),
                'subject_code',
                'subject_name',
                'credit_hours',
            ])
            ->orderBy('subject_code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function facultyMemberOptions(int $tenantId): array
    {
        return DB::table('faculty_members')
            ->where('tenant_id', $tenantId)
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->select('id as value', 'full_name as label', 'employee_no')
            ->orderBy('full_name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function lookupValues(int $tenantId, string $categoryCode): array
    {
        if (!Schema::hasTable('lookup_categories') || !Schema::hasTable('lookup_values')) {
            return [];
        }

        $category = DB::table('lookup_categories')
            ->where('code', $categoryCode)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderByRaw('tenant_id IS NULL')
            ->first();

        if (!$category) {
            return [];
        }

        return DB::table('lookup_values')
            ->where('category_id', $category->id)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->where('status', 'active')
            ->select('code as value', 'label')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function onlyColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->toArray();
    }

    private function tenantId(): int
    {
        return (int) (auth()->user()?->tenant_id ?? 0);
    }
}