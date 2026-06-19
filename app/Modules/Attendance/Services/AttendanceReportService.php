<?php

namespace App\Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;

class AttendanceReportService
{
    public function summary(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('attendance_records as ar')
            ->join('attendance_sessions as ats', 'ats.id', '=', 'ar.attendance_session_id')
            ->where('ar.tenant_id', $tenantId)
            ->whereNull('ats.deleted_at');

        $this->applySessionFilters($query, $filters);

        $rows = $query
            ->select([
                'ats.academic_session_id',
                'ats.academic_term_id',
                'ats.program_id',
                'ats.student_batch_id',
                'ats.section_id',
                'ats.curriculum_subject_id',
                'ats.course_code',
                'ats.course_title',
                DB::raw('COUNT(ar.id) as total_records'),
                DB::raw("SUM(CASE WHEN ar.status_code = 'present' THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN ar.status_code = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN ar.status_code = 'late' THEN 1 ELSE 0 END) as late_count"),
                DB::raw("SUM(CASE WHEN ar.status_code IN ('leave', 'excused') THEN 1 ELSE 0 END) as leave_count"),
            ])
            ->groupBy([
                'ats.academic_session_id',
                'ats.academic_term_id',
                'ats.program_id',
                'ats.student_batch_id',
                'ats.section_id',
                'ats.curriculum_subject_id',
                'ats.course_code',
                'ats.course_title',
            ])
            ->orderBy('ats.course_code')
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total_records;
                $attended = (int) $row->present_count + (int) $row->late_count + (int) $row->leave_count;

                return [
                    'academic_session_id' => $row->academic_session_id,
                    'academic_term_id' => $row->academic_term_id,
                    'program_id' => $row->program_id,
                    'student_batch_id' => $row->student_batch_id,
                    'section_id' => $row->section_id,
                    'curriculum_subject_id' => $row->curriculum_subject_id,
                    'course_code' => $row->course_code,
                    'course_title' => $row->course_title,
                    'total_records' => $total,
                    'present_count' => (int) $row->present_count,
                    'absent_count' => (int) $row->absent_count,
                    'late_count' => (int) $row->late_count,
                    'leave_count' => (int) $row->leave_count,
                    'attendance_percentage' => $total > 0 ? round(($attended / $total) * 100, 2) : 0,
                ];
            })
            ->values()
            ->toArray();

        return [
            'summary' => [
                'courses' => count($rows),
                'total_records' => collect($rows)->sum('total_records'),
                'present' => collect($rows)->sum('present_count'),
                'absent' => collect($rows)->sum('absent_count'),
                'late' => collect($rows)->sum('late_count'),
                'leave' => collect($rows)->sum('leave_count'),
            ],
            'rows' => $rows,
        ];
    }

    public function studentCoursePercentages(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('attendance_records as ar')
            ->join('attendance_sessions as ats', 'ats.id', '=', 'ar.attendance_session_id')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->leftJoin('student_enrollments as se', 'se.id', '=', 'ar.student_enrollment_id')
            ->where('ar.tenant_id', $tenantId)
            ->whereNull('ats.deleted_at');

        $this->applySessionFilters($query, $filters);

        return $query
            ->select([
                'ar.student_id',
                's.student_no',
                's.full_name as student_name',
                'se.roll_no',
                'se.registration_no',
                'ats.curriculum_subject_id',
                'ats.course_code',
                'ats.course_title',
                DB::raw('COUNT(ar.id) as total_classes'),
                DB::raw("SUM(CASE WHEN ar.status_code = 'present' THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN ar.status_code = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN ar.status_code = 'late' THEN 1 ELSE 0 END) as late_count"),
                DB::raw("SUM(CASE WHEN ar.status_code IN ('leave', 'excused') THEN 1 ELSE 0 END) as leave_count"),
            ])
            ->groupBy([
                'ar.student_id',
                's.student_no',
                's.full_name',
                'se.roll_no',
                'se.registration_no',
                'ats.curriculum_subject_id',
                'ats.course_code',
                'ats.course_title',
            ])
            ->orderBy('ats.course_code')
            ->orderBy('se.roll_sequence_no')
            ->orderBy('s.full_name')
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total_classes;
                $attended = (int) $row->present_count + (int) $row->late_count + (int) $row->leave_count;

                return [
                    'student_id' => $row->student_id,
                    'student_no' => $row->student_no,
                    'student_name' => $row->student_name,
                    'roll_no' => $row->roll_no,
                    'registration_no' => $row->registration_no,
                    'curriculum_subject_id' => $row->curriculum_subject_id,
                    'course_code' => $row->course_code,
                    'course_title' => $row->course_title,
                    'total_classes' => $total,
                    'present_count' => (int) $row->present_count,
                    'absent_count' => (int) $row->absent_count,
                    'late_count' => (int) $row->late_count,
                    'leave_count' => (int) $row->leave_count,
                    'attendance_percentage' => $total > 0 ? round(($attended / $total) * 100, 2) : 0,
                ];
            })
            ->values()
            ->toArray();
    }

    public function defaulters(array $filters = []): array
    {
        $minimumPercentage = (float) ($filters['minimum_percentage'] ?? 75);

        return collect($this->studentCoursePercentages($filters))
            ->filter(fn ($row) => (float) $row['attendance_percentage'] < $minimumPercentage)
            ->values()
            ->map(function ($row) use ($minimumPercentage) {
                $row['minimum_percentage'] = $minimumPercentage;
                $row['shortfall_percentage'] = round($minimumPercentage - (float) $row['attendance_percentage'], 2);

                return $row;
            })
            ->toArray();
    }

    private function applySessionFilters($query, array $filters): void
    {
        foreach ([
            'academic_session_id',
            'academic_term_id',
            'program_id',
            'student_batch_id',
            'section_id',
            'curriculum_subject_id',
        ] as $field) {
            if (!empty($filters[$field])) {
                $query->where("ats.$field", $filters[$field]);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('ats.attendance_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('ats.attendance_date', '<=', $filters['date_to']);
        }
    }

    private function tenantId(): int
    {
        $user = auth()->user();

        return (int) ($user?->tenant_id ?? 0);
    }
}