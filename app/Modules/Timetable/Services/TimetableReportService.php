<?php

namespace App\Modules\Timetable\Services;

use Illuminate\Support\Facades\DB;

class TimetableReportService
{
    public function context(): array
    {
        $tenantId = $this->tenantId();

        return [
            'calendar_periods' => DB::table('timetable_calendar_periods')
                ->where('tenant_id', $tenantId)
                ->where('status_code', 'active')
                ->whereNull('deleted_at')
                ->orderByDesc('is_default')
                ->orderByDesc('priority')
                ->get([
                    'id',
                    'period_code',
                    'period_name',
                    'academic_session_id',
                    'academic_term_id',
                    'start_date',
                    'end_date',
                ]),

            'faculties' => DB::table('faculties')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'code', 'name']),

            'departments' => DB::table('departments')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'faculty_id', 'code', 'name']),

            'teachers' => DB::table('faculty_members')
                ->where('tenant_id', $tenantId)
                ->where('status_code', 'active')
                ->whereNull('deleted_at')
                ->orderBy('full_name')
                ->get([
                    'id',
                    'faculty_id',
                    'department_id',
                    'employee_no',
                    'full_name',
                ]),

            'rooms' => DB::table('rooms')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('is_available_for_timetable', 1)
                ->whereNull('deleted_at')
                ->orderBy('code')
                ->get([
                    'id',
                    'campus_id',
                    'building_id',
                    'floor_id',
                    'code',
                    'name',
                    'room_type',
                    'capacity',
                ]),
        ];
    }

    public function master(array $filters = []): array
    {
        $rows = $this->baseEntries($filters)
            ->orderBy('te.day_of_week')
            ->orderBy('tes.sort_order')
            ->get();

        return [
            'summary' => [
                'published_entries' => $rows->pluck('timetable_entry_id')->unique()->count(),
                'teachers' => $rows->pluck('faculty_member_id')->filter()->unique()->count(),
                'rooms' => $rows->pluck('room_id')->filter()->unique()->count(),
                'departments' => $rows->pluck('department_id')->filter()->unique()->count(),
            ],
            'entries' => $this->groupEntries($rows),
        ];
    }

    public function roomUtilization(array $filters = []): array
    {
        $rows = $this->baseEntries($filters)->get();

        $entries = collect($this->groupEntries($rows));

        $rooms = $entries
            ->groupBy('room_id')
            ->map(function ($roomEntries) {
                $first = $roomEntries->first();

                return [
                    'room_id' => $first['room_id'],
                    'room_code' => $first['room_code'],
                    'room_name' => $first['room_name'],
                    'room_type' => $first['room_type'],
                    'capacity' => $first['room_capacity'],
                    'published_classes' => $roomEntries->count(),
                    'weekly_slot_count' => $roomEntries
                        ->sum(fn ($entry) => count($entry['slots'])),
                    'teachers_count' => $roomEntries
                        ->pluck('teacher_id')
                        ->filter()
                        ->unique()
                        ->count(),
                    'courses_count' => $roomEntries
                        ->pluck('course_offering_id')
                        ->unique()
                        ->count(),
                ];
            })
            ->values()
            ->sortBy('room_code')
            ->values()
            ->all();

        return [
            'summary' => [
                'rooms_used' => count($rooms),
                'weekly_slot_count' => collect($rooms)->sum('weekly_slot_count'),
                'published_classes' => collect($rooms)->sum('published_classes'),
            ],
            'rooms' => $rooms,
        ];
    }

    private function baseEntries(array $filters)
    {
        $tenantId = $this->tenantId();

        $query = DB::table('timetable_entries as te')
            ->join(
                'timetable_calendar_periods as tcp',
                'tcp.id',
                '=',
                'te.timetable_calendar_period_id'
            )
            ->join('course_offerings as co', 'co.id', '=', 'te.course_offering_id')
            ->join(
                'timetable_entry_slots as tes',
                'tes.timetable_entry_id',
                '=',
                'te.id'
            )
            ->join('timetable_slots as ts', 'ts.id', '=', 'tes.timetable_slot_id')
            ->leftJoin('faculty_members as fm', 'fm.id', '=', 'te.faculty_member_id')
            ->leftJoin('departments as d', 'd.id', '=', 'fm.department_id')
            ->leftJoin('faculties as f', 'f.id', '=', 'fm.faculty_id')
            ->leftJoin('rooms as r', 'r.id', '=', 'te.room_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'te.section_id')
            ->leftJoin(
                'academic_teaching_groups as atg',
                'atg.id',
                '=',
                'te.academic_teaching_group_id'
            )
            ->where('te.tenant_id', $tenantId)
            ->where('te.status_code', 'published')
            ->where('te.is_active', 1)
            ->whereNull('te.deleted_at');

        if (!empty($filters['timetable_calendar_period_id'])) {
            $query->where(
                'te.timetable_calendar_period_id',
                (int) $filters['timetable_calendar_period_id']
            );
        }

        if (!empty($filters['faculty_id'])) {
            $query->where('fm.faculty_id', (int) $filters['faculty_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('fm.department_id', (int) $filters['department_id']);
        }

        if (!empty($filters['faculty_member_id'])) {
            $query->where('te.faculty_member_id', (int) $filters['faculty_member_id']);
        }

        if (!empty($filters['room_id'])) {
            $query->where('te.room_id', (int) $filters['room_id']);
        }

        return $query->select([
            'te.id as timetable_entry_id',
            'te.course_offering_id',
            'te.faculty_member_id',
            'te.room_id',
            'te.day_of_week',

            'tcp.period_code',
            'tcp.period_name',

            'co.course_code',
            'co.course_title',
            'co.subject_type_code',

            'fm.employee_no',
            'fm.full_name as teacher_name',

            'd.id as department_id',
            'd.code as department_code',
            'd.name as department_name',

            'f.id as faculty_id',
            'f.code as faculty_code',
            'f.name as faculty_name',

            'r.code as room_code',
            'r.name as room_name',
            'r.room_type',
            'r.capacity as room_capacity',

            'sec.code as section_code',
            'sec.name as section_name',

            'atg.group_code',
            'atg.group_name',

            'ts.slot_code',
            'ts.start_time',
            'ts.end_time',
            'tes.sort_order',
        ]);
    }

    private function groupEntries($rows): array
    {
        $entries = [];

        foreach ($rows as $row) {
            $entryId = (int) $row->timetable_entry_id;

            if (!isset($entries[$entryId])) {
                $entries[$entryId] = [
                    'timetable_entry_id' => $entryId,
                    'course_offering_id' => (int) $row->course_offering_id,

                    'period_code' => $row->period_code,
                    'period_name' => $row->period_name,

                    'day_of_week' => (int) $row->day_of_week,
                    'day_label' => $this->dayLabel((int) $row->day_of_week),

                    'course_code' => $row->course_code,
                    'course_title' => $row->course_title,
                    'subject_type_code' => $row->subject_type_code,

                    'teacher_id' => $row->faculty_member_id
                        ? (int) $row->faculty_member_id
                        : null,
                    'teacher_name' => $row->teacher_name,
                    'employee_no' => $row->employee_no,

                    'faculty_id' => $row->faculty_id,
                    'faculty_name' => $row->faculty_name,

                    'department_id' => $row->department_id,
                    'department_name' => $row->department_name,

                    'room_id' => $row->room_id ? (int) $row->room_id : null,
                    'room_code' => $row->room_code,
                    'room_name' => $row->room_name,
                    'room_type' => $row->room_type,
                    'room_capacity' => $row->room_capacity,

                    'scope' => $row->group_code
                        ? trim($row->group_code . ' - ' . $row->group_name)
                        : trim(($row->section_code ?? '') . ' - ' . ($row->section_name ?? '')),

                    'start_time' => $row->start_time,
                    'end_time' => $row->end_time,
                    'slots' => [],
                ];
            }

            $entries[$entryId]['slots'][] = $row->slot_code;
            $entries[$entryId]['end_time'] = $row->end_time;
        }

        return array_values($entries);
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

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        abort_if(!$tenantId, 422, 'Active tenant could not be resolved.');

        return (int) $tenantId;
    }
}