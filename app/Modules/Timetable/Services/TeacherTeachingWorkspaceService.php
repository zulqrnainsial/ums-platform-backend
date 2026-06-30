<?php

namespace App\Modules\Timetable\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherTeachingWorkspaceService
{
    public function dashboard(): array
    {
        $tenantId = $this->tenantId();
        $facultyId = $this->facultyId($tenantId);

        $activeClasses = $this->activeClasses($tenantId, $facultyId);

        return [
            'faculty_member_id' => $facultyId,
            'today_classes' => array_values(array_filter(
                $activeClasses,
                fn (array $row) => $row['day_of_week'] === now()->isoWeekday()
            )),
            'weekly_classes' => $activeClasses,
            'active_subjects' => $this->activeSubjects($tenantId, $facultyId),
            'archived_subjects' => $this->archivedSubjects($tenantId, $facultyId),
        ];
    }

    private function activeClasses(int $tenantId, int $facultyId): array
    {
        $rows = DB::table('timetable_entries as te')
            ->join('timetable_entry_slots as tes', 'tes.timetable_entry_id', '=', 'te.id')
            ->join('timetable_slots as ts', 'ts.id', '=', 'tes.timetable_slot_id')
            ->join('course_offerings as co', 'co.id', '=', 'te.course_offering_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'te.section_id')
            ->leftJoin('academic_teaching_groups as atg', 'atg.id', '=', 'te.academic_teaching_group_id')
            ->leftJoin('rooms as r', 'r.id', '=', 'te.room_id')
            ->where('te.tenant_id', $tenantId)
            ->where('te.faculty_member_id', $facultyId)
            ->where('te.status_code', 'published')
            ->where('te.is_active', true)
            ->whereNull('te.deleted_at')
            ->select([
                'te.id as timetable_entry_id',
                'te.day_of_week',
                'te.academic_session_id',
                'te.academic_term_id',
                'co.course_code',
                'co.course_title',
                'co.subject_type_code',
                'sec.code as section_code',
                'sec.name as section_name',
                'atg.group_code',
                'atg.group_name',
                'r.code as room_code',
                'r.name as room_name',
                'ts.slot_code',
                'ts.start_time',
                'ts.end_time',
                'tes.sort_order',
            ])
            ->orderBy('te.day_of_week')
            ->orderBy('tes.sort_order')
            ->get();

        $entries = [];

        foreach ($rows as $row) {
            $entryId = (int) $row->timetable_entry_id;

            if (!isset($entries[$entryId])) {
                $entries[$entryId] = [
                    'timetable_entry_id' => $entryId,
                    'academic_session_id' => $row->academic_session_id,
                    'academic_term_id' => $row->academic_term_id,
                    'day_of_week' => (int) $row->day_of_week,
                    'day_label' => $this->dayLabel((int) $row->day_of_week),
                    'course_code' => $row->course_code,
                    'course_title' => $row->course_title,
                    'subject_type_code' => $row->subject_type_code,
                    'scope' => $row->group_code
                        ? trim($row->group_code . ' - ' . $row->group_name)
                        : trim(($row->section_code ?? '') . ' - ' . ($row->section_name ?? '')),
                    'room' => trim(($row->room_code ?? '') . ' - ' . ($row->room_name ?? '')),
                    'slots' => [],
                    'start_time' => $row->start_time,
                    'end_time' => $row->end_time,
                ];
            }

            $entries[$entryId]['slots'][] = $row->slot_code;
            $entries[$entryId]['end_time'] = $row->end_time;
        }

        return array_values($entries);
    }

    private function activeSubjects(int $tenantId, int $facultyId): array
    {
        return DB::table('timetable_entries as te')
            ->join('course_offerings as co', 'co.id', '=', 'te.course_offering_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'te.section_id')
            ->leftJoin('academic_teaching_groups as atg', 'atg.id', '=', 'te.academic_teaching_group_id')
            ->where('te.tenant_id', $tenantId)
            ->where('te.faculty_member_id', $facultyId)
            ->where('te.status_code', 'published')
            ->where('te.is_active', true)
            ->whereNull('te.deleted_at')
            ->select([
                'te.id as timetable_entry_id',
                'te.academic_session_id',
                'te.academic_term_id',
                'co.course_code',
                'co.course_title',
                'co.subject_type_code',
                'sec.code as section_code',
                'sec.name as section_name',
                'atg.group_code',
                'atg.group_name',
            ])
            ->orderBy('co.course_code')
            ->get()
            ->map(fn ($row) => $this->subjectRow($tenantId, $row, 'active'))
            ->values()
            ->all();
    }

    private function archivedSubjects(int $tenantId, int $facultyId): array
    {
        if (!Schema::hasTable('timetable_entry_teacher_assignment_histories')) {
            return [];
        }

        return DB::table('timetable_entry_teacher_assignment_histories as h')
            ->join('timetable_entries as te', 'te.id', '=', 'h.timetable_entry_id')
            ->join('course_offerings as co', 'co.id', '=', 'te.course_offering_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'te.section_id')
            ->leftJoin('academic_teaching_groups as atg', 'atg.id', '=', 'te.academic_teaching_group_id')
            ->where('h.tenant_id', $tenantId)
            ->where('h.old_faculty_member_id', $facultyId)
            ->select([
                'te.id as timetable_entry_id',
                'te.academic_session_id',
                'te.academic_term_id',
                'co.course_code',
                'co.course_title',
                'co.subject_type_code',
                'sec.code as section_code',
                'sec.name as section_name',
                'atg.group_code',
                'atg.group_name',
                'h.changed_at as archived_at',
                'h.reason as archive_reason',
            ])
            ->orderByDesc('h.changed_at')
            ->get()
            ->map(fn ($row) => $this->subjectRow($tenantId, $row, 'archived'))
            ->values()
            ->all();
    }

    private function subjectRow(int $tenantId, object $row, string $state): array
    {
        $stats = $this->attendanceStats($tenantId, (int) $row->timetable_entry_id);

        return [
            'timetable_entry_id' => (int) $row->timetable_entry_id,
            'state' => $state,
            'academic_session_id' => $row->academic_session_id,
            'academic_term_id' => $row->academic_term_id,
            'course_code' => $row->course_code,
            'course_title' => $row->course_title,
            'subject_type_code' => $row->subject_type_code,
            'scope' => $row->group_code
                ? trim($row->group_code . ' - ' . $row->group_name)
                : trim(($row->section_code ?? '') . ' - ' . ($row->section_name ?? '')),
            'sessions_count' => $stats['sessions_count'],
            'attendance_percentage' => $stats['attendance_percentage'],
            'archived_at' => $row->archived_at ?? null,
            'archive_reason' => $row->archive_reason ?? null,
        ];
    }

    private function attendanceStats(int $tenantId, int $timetableEntryId): array
    {
        if (
            !Schema::hasTable('attendance_sessions') ||
            !Schema::hasColumn('attendance_sessions', 'timetable_entry_id')
        ) {
            return [
                'sessions_count' => 0,
                'attendance_percentage' => 0,
            ];
        }

        $row = DB::table('attendance_sessions as ats')
            ->leftJoin('attendance_records as ar', 'ar.attendance_session_id', '=', 'ats.id')
            ->where('ats.tenant_id', $tenantId)
            ->where('ats.timetable_entry_id', $timetableEntryId)
            ->whereNull('ats.deleted_at')
            ->selectRaw("
                COUNT(DISTINCT ats.id) as sessions_count,
                COUNT(ar.id) as total_records,
                SUM(
                    CASE
                        WHEN ar.status_code IN ('present', 'late', 'leave', 'excused')
                        THEN 1
                        ELSE 0
                    END
                ) as attended_records
            ")
            ->first();

        $total = (int) ($row->total_records ?? 0);
        $attended = (int) ($row->attended_records ?? 0);

        return [
            'sessions_count' => (int) ($row->sessions_count ?? 0),
            'attendance_percentage' => $total > 0
                ? round(($attended / $total) * 100, 2)
                : 0,
        ];
    }

    private function facultyId(int $tenantId): int
    {
        $facultyId = DB::table('faculty_members')
            ->where('tenant_id', $tenantId)
            ->where('user_id', auth()->id())
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->value('id');

        abort_if(!$facultyId, 403, 'No active faculty profile is linked to this user.');

        return (int) $facultyId;
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        abort_if(!$tenantId, 422, 'Active tenant could not be resolved.');

        return (int) $tenantId;
    }

    private function dayLabel(int $day): string
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ][$day] ?? 'Unknown';
    }
}