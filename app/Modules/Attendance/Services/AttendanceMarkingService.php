<?php

namespace App\Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class AttendanceMarkingService
{
    public function context(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        if ($this->isTeacher()) {
            return $this->teacherContext($tenantId);
        }

        return [
            'mode' => 'admin',
            'academic_sessions' => $this->academicSessions($tenantId),
            'programs' => $this->programs($tenantId),
            'academic_terms' => $this->academicTerms($tenantId, $filters),
            'student_batches' => $this->studentBatches($tenantId, $filters),
            'sections' => $this->sections($tenantId, $filters),
            'courses' => $this->registeredCourses($tenantId, $filters),
            'published_classes' => [],
        ];
    }

    public function students(array $filters): array
    {
        $tenantId = $this->tenantId();
        $publishedClass = null;

        if ($this->isTeacher()) {
            abort_if(empty($filters['timetable_entry_id']), 422, 'Select one of your published timetable classes.');
            $publishedClass = $this->teacherPublishedClass($tenantId, (int) $filters['timetable_entry_id']);
            $filters = array_merge($filters, $this->classScope($publishedClass));
        }

        foreach ([
            'academic_session_id',
            'program_id',
            'student_batch_id',
            'section_id',
            'curriculum_subject_id',
        ] as $field) {
            abort_if(empty($filters[$field]), 422, "{$field} is required.");
        }

        $query = DB::table('student_course_registrations as scr')
            ->join('students as s', 's.id', '=', 'scr.student_id')
            ->join('student_enrollments as se', 'se.id', '=', 'scr.student_enrollment_id')
            ->where('scr.tenant_id', $tenantId)
            ->where('scr.academic_session_id', $filters['academic_session_id'])
            ->where('scr.program_id', $filters['program_id'])
            ->where('scr.student_batch_id', $filters['student_batch_id'])
            ->where('scr.curriculum_subject_id', $filters['curriculum_subject_id'])
            ->whereIn('scr.status', ['registered', 'approved', 'completed']);

        if (Schema::hasColumn('student_course_registrations', 'section_id')) {
            $query->where('scr.section_id', $filters['section_id']);
        } else {
            $section = DB::table('sections')->where('id', $filters['section_id'])->first();
            if ($section) {
                $query->where(function ($q) use ($section) {
                    $q->where('scr.section', $section->code)
                        ->orWhere('scr.section', $section->name);
                });
            }
        }

        if (!empty($filters['academic_term_id'])) {
            $query->where('scr.academic_term_id', $filters['academic_term_id']);
        }

        if (!empty($publishedClass?->academic_teaching_group_id)) {
            $query->join('academic_teaching_group_members as atgm', function ($join) use ($tenantId, $publishedClass) {
                $join->on('atgm.student_enrollment_id', '=', 'scr.student_enrollment_id')
                    ->where('atgm.tenant_id', '=', $tenantId)
                    ->where('atgm.academic_teaching_group_id', '=', $publishedClass->academic_teaching_group_id)
                    ->where('atgm.status_code', '=', 'active');
            });
        }

        return $query
            ->select([
                'scr.id as student_course_registration_id',
                'scr.student_id',
                'scr.student_enrollment_id',
                'scr.course_code',
                'scr.course_title',
                'scr.credit_hours',
                'scr.subject_type_code',
                's.student_no',
                's.full_name as student_name',
                'se.roll_no',
                'se.registration_no',
            ])
            ->distinct()
            ->orderBy('se.roll_sequence_no')
            ->orderBy('s.full_name')
            ->get()
            ->map(fn ($row) => [
                'student_course_registration_id' => $row->student_course_registration_id,
                'student_id' => $row->student_id,
                'student_enrollment_id' => $row->student_enrollment_id,
                'student_no' => $row->student_no,
                'student_name' => $row->student_name,
                'roll_no' => $row->roll_no,
                'registration_no' => $row->registration_no,
                'course_code' => $row->course_code,
                'course_title' => $row->course_title,
                'credit_hours' => $row->credit_hours,
                'subject_type_code' => $row->subject_type_code,
                'status_code' => 'present',
                'remarks' => null,
            ])
            ->values()
            ->toArray();
    }

    public function save(array $data): array
    {
        $tenantId = $this->tenantId();

        if ($this->isTeacher()) {
            abort_if(empty($data['timetable_entry_id']), 422, 'A published timetable class is required for teacher attendance.');
            $publishedClass = $this->teacherPublishedClass($tenantId, (int) $data['timetable_entry_id']);
            $data = array_merge($data, $this->classScope($publishedClass));

            $attendanceDay = Carbon::parse($data['attendance_date'])->dayOfWeekIso;
            abort_if(
                !empty($publishedClass->day_of_week) && (int) $publishedClass->day_of_week !== $attendanceDay,
                422,
                'Attendance date does not match the selected published timetable day.'
            );
        }

        abort_if(empty($data['records']), 422, 'Attendance records are required.');

        $sessionId = null;

        DB::transaction(function () use ($tenantId, $data, &$sessionId) {
            $sessionId = $this->createOrUpdateSession($tenantId, $data);

            foreach ($data['records'] as $record) {
                $this->upsertRecord($tenantId, $sessionId, $record);
            }
        });

        return [
            'attendance_session_id' => $sessionId,
            'records_count' => count($data['records']),
        ];
    }

    public function lock(int $sessionId): array
    {
        $tenantId = $this->tenantId();

        $session = DB::table('attendance_sessions')
            ->where('tenant_id', $tenantId)
            ->where('id', $sessionId)
            ->first();

        abort_if(!$session, 404, 'Attendance session not found.');

        DB::table('attendance_sessions')
            ->where('id', $sessionId)
            ->update([
                'status_code' => 'locked',
                'locked_at' => now(),
                'locked_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return [
            'attendance_session_id' => $sessionId,
            'status_code' => 'locked',
        ];
    }

    private function createOrUpdateSession(int $tenantId, array $data): int
    {
        $attendanceDate = Carbon::parse($data['attendance_date'])->toDateString();
        if (!empty($data['timetable_entry_id'])) {
            $facultyId = DB::table('faculty_members')
                ->where('tenant_id', $tenantId)
                ->where('user_id', auth()->id())
                ->where('status_code', 'active')
                ->whereNull('deleted_at')
                ->value('id');

            abort_if(!$facultyId, 403, 'No active faculty profile is linked to this user.');

            $publishedEntry = DB::table('timetable_entries')
                ->where('tenant_id', $tenantId)
                ->where('id', $data['timetable_entry_id'])
                ->where('faculty_member_id', $facultyId)
                ->where('status_code', 'published')
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->first();

            abort_if(
                !$publishedEntry,
                403,
                'You can mark attendance only for your active published timetable class.'
            );
        }
        $lookup = [
            'tenant_id' => $tenantId,
            'timetable_entry_id' => $data['timetable_entry_id'] ?? null,
            'academic_session_id' => $data['academic_session_id'],
            'academic_term_id' => $data['academic_term_id'] ?? null,
            'program_id' => $data['program_id'],
            'student_batch_id' => $data['student_batch_id'],
            'section_id' => $data['section_id'],
            'curriculum_subject_id' => $data['curriculum_subject_id'],
            'attendance_date' => $attendanceDate,
            'session_type' => $data['session_type'] ?? 'lecture',
        ];

        $existing = DB::table('attendance_sessions')
            ->where($lookup)
            ->whereNull('deleted_at')
            ->first();

        $course = DB::table('curriculum_subjects')
            ->where('id', $data['curriculum_subject_id'])
            ->first();

        $payload = [
            'subject_id' => $course->subject_id ?? null,
            'course_code' => $course->subject_code ?? null,
            'course_title' => $course->subject_name ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'topic' => $data['topic'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'status_code' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ];

        if ($existing) {
            abort_if($existing->status_code === 'locked', 423, 'Attendance session is locked.');

            DB::table('attendance_sessions')
                ->where('id', $existing->id)
                ->update($payload);

            return (int) $existing->id;
        }

        $payload = array_merge($lookup, $payload, [
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return (int) DB::table('attendance_sessions')->insertGetId($payload);
    }

    private function upsertRecord(int $tenantId, int $sessionId, array $record): void
    {
        foreach ([
            'student_id',
            'student_enrollment_id',
            'student_course_registration_id',
            'status_code',
        ] as $field) {
            abort_if(empty($record[$field]), 422, "{$field} is required in attendance record.");
        }

        $status = $record['status_code'];

        abort_if(
            !in_array($status, ['present', 'absent', 'late', 'leave', 'excused'], true),
            422,
            'Invalid attendance status.'
        );

        $lookup = [
            'attendance_session_id' => $sessionId,
            'student_course_registration_id' => $record['student_course_registration_id'],
        ];

        $payload = [
            'tenant_id' => $tenantId,
            'student_id' => $record['student_id'],
            'student_enrollment_id' => $record['student_enrollment_id'],
            'status_code' => $status,
            'marked_at' => now(),
            'marked_by' => auth()->id(),
            'remarks' => $record['remarks'] ?? null,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ];

        $existing = DB::table('attendance_records')
            ->where($lookup)
            ->first();

        if ($existing) {
            DB::table('attendance_records')
                ->where('id', $existing->id)
                ->update($payload);

            return;
        }

        $payload = array_merge($lookup, $payload, [
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        DB::table('attendance_records')->insert($payload);
    }

    private function isTeacher(): bool
    {
        return auth()->check() && auth()->user()?->user_type === 'teacher';
    }

    private function teacherContext(int $tenantId): array
    {
        $classes = $this->publishedTeacherClasses($tenantId);

        return [
            'mode' => 'teacher',
            'academic_sessions' => [],
            'programs' => [],
            'academic_terms' => [],
            'student_batches' => [],
            'sections' => [],
            'courses' => [],
            'published_classes' => $classes,
        ];
    }

    private function teacherFacultyId(int $tenantId): int
    {
        $facultyId = DB::table('faculty_members')
            ->where('tenant_id', $tenantId)
            ->where('user_id', auth()->id())
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->value('id');

        abort_if(!$facultyId, 403, 'Your teacher profile is not active or is not linked to this user account.');

        return (int) $facultyId;
    }

    private function publishedTeacherClasses(int $tenantId): array
    {
        $facultyId = $this->teacherFacultyId($tenantId);

        return DB::table('timetable_entries as te')
            ->join('course_offerings as co', 'co.id', '=', 'te.course_offering_id')
            ->leftJoin('curriculum_subjects as cs', 'cs.id', '=', 'co.curriculum_subject_id')
            ->leftJoin('student_batches as sb', 'sb.id', '=', 'co.student_batch_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'co.section_id')
            ->leftJoin('academic_teaching_groups as atg', 'atg.id', '=', 'co.academic_teaching_group_id')
            ->where('te.tenant_id', $tenantId)
            ->where('te.faculty_member_id', $facultyId)
            ->where('te.is_active', true)
            ->where('te.status_code', 'published')
            ->whereNull('te.deleted_at')
            ->whereIn('co.status_code', ['offered', 'allocated', 'scheduled'])
            ->select([
                'te.id as value',
                'te.id as timetable_entry_id',
                'te.day_of_week',
                'co.academic_session_id',
                'co.academic_term_id',
                'sb.program_id',
                'co.student_batch_id',
                'co.section_id',
                'co.academic_teaching_group_id',
                'co.curriculum_subject_id',
                'co.subject_type_code',
                'cs.subject_code as course_code',
                'cs.subject_name as course_title',
                'sec.code as section_code',
                'sec.name as section_name',
                'atg.group_code',
                'atg.group_name',
            ])
            ->orderBy('te.day_of_week')
            ->orderBy('te.id')
            ->get()
            ->map(function ($row) {
                $scope = $row->group_name
                    ? "{$row->group_code} - {$row->group_name}"
                    : "{$row->section_code} - {$row->section_name}";

                return [
                    'value' => $row->value,
                    'label' => trim("{$row->course_code} - {$row->course_title} | {$scope}"),
                    'meta' => (array) $row,
                ];
            })
            ->values()
            ->toArray();
    }

    private function teacherPublishedClass(int $tenantId, int $timetableEntryId): object
    {
        $facultyId = $this->teacherFacultyId($tenantId);

        $row = DB::table('timetable_entries as te')
            ->join('course_offerings as co', 'co.id', '=', 'te.course_offering_id')
            ->join('student_batches as sb', 'sb.id', '=', 'co.student_batch_id')
            ->where('te.tenant_id', $tenantId)
            ->where('te.id', $timetableEntryId)
            ->where('te.faculty_member_id', $facultyId)
            ->where('te.is_active', true)
            ->where('te.status_code', 'published')
            ->whereNull('te.deleted_at')
            ->whereIn('co.status_code', ['offered', 'allocated', 'scheduled'])
            ->select([
                'te.id',
                'te.day_of_week',
                'co.academic_session_id',
                'co.academic_term_id',
                'sb.program_id',
                'co.student_batch_id',
                'co.section_id',
                'co.academic_teaching_group_id',
                'co.curriculum_subject_id',
            ])
            ->first();

        abort_if(!$row, 403, 'This class is not one of your active published timetable classes.');

        return $row;
    }

    private function classScope(object $class): array
    {
        return [
            'academic_session_id' => $class->academic_session_id,
            'academic_term_id' => $class->academic_term_id,
            'program_id' => $class->program_id,
            'student_batch_id' => $class->student_batch_id,
            'section_id' => $class->section_id,
            'curriculum_subject_id' => $class->curriculum_subject_id,
        ];
    }

    private function academicSessions(int $tenantId): array
    {
        return DB::table('academic_sessions')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['active', 'planned'])
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->select('id as value', 'name as label', 'code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function programs(int $tenantId): array
    {
        return DB::table('programs')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->select('id as value', 'name as label', 'code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function academicTerms(int $tenantId, array $filters): array
    {
        $query = DB::table('academic_terms')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if (!empty($filters['program_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('program_id', $filters['program_id'])
                    ->orWhereNull('program_id');
            });
        }

        return $query
            ->orderBy('term_number')
            ->select('id as value', 'name as label', 'code', 'term_number')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function studentBatches(int $tenantId, array $filters): array
    {
        $query = DB::table('student_batches')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if (!empty($filters['academic_session_id'])) {
            $query->where('academic_session_id', $filters['academic_session_id']);
        }

        if (!empty($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        return $query
            ->orderBy('name')
            ->select('id as value', DB::raw("CONCAT(code, ' - ', name) as label"), 'code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function sections(int $tenantId, array $filters): array
    {
        $query = DB::table('sections')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if (Schema::hasColumn('sections', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (!empty($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        if (!empty($filters['academic_term_id'])) {
            $query->where('academic_term_id', $filters['academic_term_id']);
        }

        return $query
            ->orderBy('name')
            ->select('id as value', 'name as label', 'code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function registeredCourses(int $tenantId, array $filters): array
    {
        $query = DB::table('student_course_registrations')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['registered', 'approved', 'completed']);

        foreach ([
            'academic_session_id',
            'academic_term_id',
            'program_id',
            'student_batch_id',
            'section_id',
        ] as $field) {
            if (!empty($filters[$field]) && Schema::hasColumn('student_course_registrations', $field)) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query
            ->select([
                'curriculum_subject_id as value',
                DB::raw("MAX(course_title) as label"),
                DB::raw("MAX(course_code) as course_code"),
                DB::raw("MAX(credit_hours) as credit_hours"),
            ])
            ->groupBy('curriculum_subject_id')
            ->orderBy('course_code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function tenantId(): int
    {
        $user = auth()->user();

        return (int) ($user?->tenant_id ?? 0);
    }
}