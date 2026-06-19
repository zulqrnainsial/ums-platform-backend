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

        return [
            'academic_sessions' => $this->academicSessions($tenantId),
            'programs' => $this->programs($tenantId),
            'academic_terms' => $this->academicTerms($tenantId, $filters),
            'student_batches' => $this->studentBatches($tenantId, $filters),
            'sections' => $this->sections($tenantId, $filters),
            'courses' => $this->registeredCourses($tenantId, $filters),
        ];
    }

    public function students(array $filters): array
    {
        $tenantId = $this->tenantId();

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
            $section = DB::table('sections')
                ->where('id', $filters['section_id'])
                ->first();

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

        $lookup = [
            'tenant_id' => $tenantId,
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