<?php

namespace App\Modules\Timetable\Services;

use Illuminate\Support\Facades\DB;

class TimetableEntryService
{
    public function __construct(
        private readonly TimetableValidationService $validationService,
    ) {
    }

    public function save(array $data): array
    {
        $tenantId = $this->tenantId();

        $validation = $this->validationService->validate($data);
        $resolved = $validation['resolved'];

        $hasErrors = collect($validation['conflicts'])
            ->contains(fn (array $conflict) => $conflict['conflict_severity'] === 'error');

        $entryId = null;

        DB::transaction(function () use (
            $tenantId,
            $data,
            $validation,
            $resolved,
            $hasErrors,
            &$entryId
        ) {
            $entryId = DB::table('timetable_entries')->insertGetId([
                'tenant_id' => $tenantId,

                'academic_session_id' => $resolved['academic_session_id'],
                'academic_term_id' => $resolved['academic_term_id'],
                'timetable_calendar_period_id' => $resolved['timetable_calendar_period_id'],

                'course_offering_id' => $resolved['course_offering_id'],
                'course_teacher_allocation_id' => $resolved['course_teacher_allocation_id'],
                'faculty_member_id' => $resolved['faculty_member_id'],

                'section_id' => $resolved['section_id'],
                'academic_teaching_group_id' => $resolved['academic_teaching_group_id'],

                'room_id' => $resolved['room_id'],
                'day_of_week' => $resolved['day_of_week'],

                'entry_source_code' => $data['entry_source_code'] ?? 'manual',
                'status_code' => $hasErrors ? 'conflicted' : 'valid',

                /*
                 | Conflicted drafts remain visible in conflict reports,
                 | but must not participate in timetable occupancy checks.
                 */
                'is_active' => !$hasErrors,

                'remarks' => $data['remarks'] ?? null,

                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (array_values(array_unique($data['timetable_slot_ids'])) as $index => $slotId) {
                DB::table('timetable_entry_slots')->insert([
                    'tenant_id' => $tenantId,
                    'timetable_entry_id' => $entryId,
                    'timetable_slot_id' => (int) $slotId,
                    'sort_order' => $index + 1,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($validation['conflicts'] as $conflict) {
                $context = $conflict['conflict_context'] ?? [];

                DB::table('timetable_conflicts')->insert([
                    'tenant_id' => $tenantId,

                    'timetable_entry_id' => $entryId,
                    'conflicting_timetable_entry_id' => $context['conflicting_timetable_entry_id'] ?? null,

                    'course_offering_id' => $resolved['course_offering_id'],
                    'faculty_member_id' => $resolved['faculty_member_id'],
                    'room_id' => $resolved['room_id'],
                    'section_id' => $resolved['section_id'],
                    'academic_teaching_group_id' => $resolved['academic_teaching_group_id'],
                    'timetable_slot_id' => $context['timetable_slot_id'] ?? null,

                    'conflict_code' => $conflict['conflict_code'],
                    'conflict_severity' => $conflict['conflict_severity'],
                    'conflict_message' => $conflict['conflict_message'],
                    'conflict_context' => json_encode($context),

                    'status_code' => 'open',

                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return [
            'entry' => $this->entry($tenantId, $entryId),
            'validation' => $validation,
        ];
    }

    public function conflicts(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('timetable_conflicts as tc')
            ->leftJoin('timetable_entries as te', 'te.id', '=', 'tc.timetable_entry_id')
            ->leftJoin('course_offerings as co', 'co.id', '=', 'tc.course_offering_id')
            ->leftJoin('faculty_members as fm', 'fm.id', '=', 'tc.faculty_member_id')
            ->leftJoin('rooms as r', 'r.id', '=', 'tc.room_id')
            ->where('tc.tenant_id', $tenantId);

        foreach ([
            'timetable_entry_id',
            'course_offering_id',
            'faculty_member_id',
            'room_id',
            'conflict_code',
            'conflict_severity',
            'status_code',
        ] as $field) {
            if (!empty($filters[$field])) {
                $query->where("tc.$field", $filters[$field]);
            }
        }

        return $query
            ->select([
                'tc.*',
                'te.status_code as timetable_entry_status_code',
                'co.course_code',
                'co.course_title',
                'co.subject_type_code',
                'fm.full_name as faculty_name',
                'r.code as room_code',
                'r.name as room_name',
            ])
            ->orderByDesc('tc.id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->toArray();
    }



    /**
     * Reassign an active published timetable entry to another valid/approved
     * allocation for the same course offering. The day, room, slots and
     * student scope remain unchanged.
     */
    public function replacePublishedTeacher(
        int $entryId,
        int $courseTeacherAllocationId,
        ?string $remarks = null
    ): array {
        $tenantId = $this->tenantId();

        $entry = DB::table('timetable_entries')
            ->where('tenant_id', $tenantId)
            ->where('id', $entryId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$entry, 404, 'Timetable entry not found.');
        abort_if(
            $entry->status_code !== 'published' || !(bool) $entry->is_active,
            422,
            'Only active published timetable entries can have their teacher replaced.'
        );

        $allocation = DB::table('course_teacher_allocations as cta')
            ->join('faculty_members as fm', 'fm.id', '=', 'cta.faculty_member_id')
            ->where('cta.tenant_id', $tenantId)
            ->where('cta.id', $courseTeacherAllocationId)
            ->where('cta.course_offering_id', $entry->course_offering_id)
            ->whereIn('cta.allocation_status_code', ['valid', 'approved'])
            ->whereNull('cta.deleted_at')
            ->whereNull('fm.deleted_at')
            ->where('fm.status_code', 'active')
            ->select(['cta.*', 'fm.full_name as faculty_name'])
            ->first();

        abort_if(!$allocation, 422, 'Choose a valid active teacher allocation for this same course offering.');

        $slotIds = DB::table('timetable_entry_slots')
            ->where('tenant_id', $tenantId)
            ->where('timetable_entry_id', $entryId)
            ->orderBy('sort_order')
            ->pluck('timetable_slot_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        abort_if(empty($slotIds), 422, 'The timetable entry has no timetable slots.');

        $validation = $this->validationService->validate([
            'course_offering_id' => (int) $entry->course_offering_id,
            'course_teacher_allocation_id' => (int) $allocation->id,
            'faculty_member_id' => (int) $allocation->faculty_member_id,
            'room_id' => (int) $entry->room_id,
            'timetable_calendar_period_id' => (int) $entry->timetable_calendar_period_id,
            'timetable_slot_ids' => $slotIds,
            'ignore_timetable_entry_id' => $entryId,
        ]);

        $errors = collect($validation['conflicts'] ?? [])
            ->filter(fn ($conflict) => ($conflict['conflict_severity'] ?? 'error') === 'error')
            ->values();

        abort_if(
            $errors->isNotEmpty(),
            422,
            $errors->pluck('conflict_message')->implode(' ')
        );

        DB::transaction(function () use ($tenantId, $entry, $allocation, $remarks) {
            $note = trim(implode(' | ', array_filter([
                $entry->remarks,
                'Teacher reassigned from faculty #' . $entry->faculty_member_id . ' to faculty #' . $allocation->faculty_member_id,
                $remarks,
            ])));
        DB::table('timetable_entry_teacher_assignment_histories')->insert([
            'tenant_id' => $tenantId,

            'timetable_entry_id' => $entry->id,

            'old_faculty_member_id' => $entry->faculty_member_id,
            'new_faculty_member_id' => $allocation->faculty_member_id,

            'old_course_teacher_allocation_id' => $entry->course_teacher_allocation_id,
            'new_course_teacher_allocation_id' => $allocation->id,

            'change_type_code' => 'teacher_replaced',

            'reason' => $remarks ?: 'Teacher replaced after timetable publication.',

            'changed_by' => auth()->id(),
            'changed_at' => now(),

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('timetable_entries')
            ->where('tenant_id', $tenantId)
            ->where('id', $entry->id)
            ->update([
                'course_teacher_allocation_id' => $allocation->id,
                'faculty_member_id' => $allocation->faculty_member_id,
                'remarks' => $note ?: null,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);
        });

        return [
            'entry' => $this->entry($tenantId, $entryId),
            'validation' => $validation,
        ];
    }

    private function entry(int $tenantId, int $entryId): array
    {
        $entry = DB::table('timetable_entries as te')
            ->leftJoin('course_offerings as co', 'co.id', '=', 'te.course_offering_id')
            ->leftJoin('faculty_members as fm', 'fm.id', '=', 'te.faculty_member_id')
            ->leftJoin('rooms as r', 'r.id', '=', 'te.room_id')
            ->where('te.tenant_id', $tenantId)
            ->where('te.id', $entryId)
            ->select([
                'te.*',
                'co.course_code',
                'co.course_title',
                'co.subject_type_code',
                'fm.full_name as faculty_name',
                'r.code as room_code',
                'r.name as room_name',
            ])
            ->first();

        return (array) $entry;
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        abort_if(!$tenantId, 422, 'Active tenant could not be resolved.');

        return (int) $tenantId;
    }
    public function context(array $filters = []): array
{
    $tenantId = $this->tenantId();

    $courseOfferings = DB::table('course_offerings as co')
        ->leftJoin('sections as sec', 'sec.id', '=', 'co.section_id')
        ->leftJoin('academic_teaching_groups as atg', 'atg.id', '=', 'co.academic_teaching_group_id')
        ->where('co.tenant_id', $tenantId)
        ->whereNull('co.deleted_at')
        ->when(!empty($filters['academic_session_id']), fn ($q) => $q->where('co.academic_session_id', $filters['academic_session_id']))
        ->when(!empty($filters['academic_term_id']), fn ($q) => $q->where('co.academic_term_id', $filters['academic_term_id']))
        ->whereIn('co.status_code', ['offered', 'allocated', 'scheduled'])
        ->select([
            'co.id as value',
            'co.course_code',
            'co.course_title',
            'co.subject_type_code',
            'co.credit_hours',
            'co.contact_hours_per_week',
            'co.required_capacity',
            'co.required_room_type_code',
            'co.requires_lab',
            'co.requires_multimedia',
            'co.academic_session_id',
            'co.academic_term_id',
            'co.section_id',
            'co.academic_teaching_group_id',
            'sec.code as section_code',
            'sec.name as section_name',
            'atg.group_code',
            'atg.group_name',
        ])
        ->orderBy('co.course_code')
        ->get()
        ->map(function ($row) {
            $scope = $row->academic_teaching_group_id
                ? trim(($row->group_code ?? '') . ' - ' . ($row->group_name ?? ''))
                : trim(($row->section_code ?? '') . ' - ' . ($row->section_name ?? ''));

            return [
                'value' => $row->value,
                'label' => trim("{$row->course_code} - {$row->course_title} | {$row->subject_type_code} | {$scope}"),
                'meta' => (array) $row,
            ];
        })
        ->values()
        ->toArray();

    $teacherAllocations = DB::table('course_teacher_allocations as cta')
        ->join('faculty_members as fm', 'fm.id', '=', 'cta.faculty_member_id')
        ->join('course_offerings as co', 'co.id', '=', 'cta.course_offering_id')
        ->where('cta.tenant_id', $tenantId)
        ->whereNull('cta.deleted_at')
        ->whereIn('cta.allocation_status_code', ['valid', 'approved'])
        ->when(!empty($filters['academic_session_id']), fn ($q) => $q->where('co.academic_session_id', $filters['academic_session_id']))
        ->when(!empty($filters['academic_term_id']), fn ($q) => $q->where('co.academic_term_id', $filters['academic_term_id']))
        ->select([
            'cta.id as value',
            'cta.course_offering_id',
            'cta.faculty_member_id',
            'cta.allocation_role_code',
            'cta.allocated_credit_hours',
            'cta.allocated_contact_hours',
            'fm.full_name as faculty_name',
            'fm.employee_no',
        ])
        ->orderBy('fm.full_name')
        ->get()
        ->map(fn ($row) => [
            'value' => $row->value,
            'course_offering_id' => $row->course_offering_id,
            'faculty_member_id' => $row->faculty_member_id,
            'label' => trim(($row->employee_no ? "{$row->employee_no} - " : '') . "{$row->faculty_name} ({$row->allocation_role_code})"),
        ])
        ->values()
        ->toArray();

    $rooms = DB::table('rooms')
        ->where('tenant_id', $tenantId)
        ->where('status', 'active')
        ->where('is_available_for_timetable', true)
        ->whereNull('deleted_at')
        ->select([
            'id as value',
            'code',
            'name',
            'room_type',
            'capacity',
        ])
        ->orderBy('code')
        ->get()
        ->map(fn ($row) => [
            'value' => $row->value,
            'label' => "{$row->code} - {$row->name} ({$row->room_type}, {$row->capacity})",
            'meta' => (array) $row,
        ])
        ->values()
        ->toArray();

    $calendarPeriods = DB::table('timetable_calendar_periods as tcp')
        ->join('timetable_slot_sets as tss', 'tss.id', '=', 'tcp.timetable_slot_set_id')
        ->where('tcp.tenant_id', $tenantId)
        ->where('tcp.status_code', 'active')
        ->whereNull('tcp.deleted_at')
        ->when(!empty($filters['academic_session_id']), fn ($q) => $q->where('tcp.academic_session_id', $filters['academic_session_id']))
        ->when(!empty($filters['academic_term_id']), fn ($q) => $q->where('tcp.academic_term_id', $filters['academic_term_id']))
        ->select([
            'tcp.id as value',
            'tcp.academic_session_id',
            'tcp.academic_term_id',
            'tcp.timetable_slot_set_id',
            'tcp.period_code',
            'tcp.period_name',
            'tcp.start_date',
            'tcp.end_date',
            'tcp.is_default',
            'tss.slot_set_name',
        ])
        ->orderByDesc('tcp.priority')
        ->orderByDesc('tcp.is_default')
        ->get()
        ->map(fn ($row) => [
            'value' => $row->value,
            'academic_session_id' => $row->academic_session_id,
            'academic_term_id' => $row->academic_term_id,
            'timetable_slot_set_id' => $row->timetable_slot_set_id,
            'label' => "{$row->period_name} ({$row->slot_set_name})",
        ])
        ->values()
        ->toArray();

    return compact('courseOfferings', 'teacherAllocations', 'rooms', 'calendarPeriods');
}

public function slots(int $calendarPeriodId): array
{
    $tenantId = $this->tenantId();

    $period = DB::table('timetable_calendar_periods')
        ->where('tenant_id', $tenantId)
        ->where('id', $calendarPeriodId)
        ->where('status_code', 'active')
        ->whereNull('deleted_at')
        ->first();

    abort_if(!$period, 404, 'Timetable calendar period not found.');

    return DB::table('timetable_slots')
        ->where('tenant_id', $tenantId)
        ->where('timetable_slot_set_id', $period->timetable_slot_set_id)
        ->where('status_code', 'active')
        ->where('is_teaching_slot', true)
        ->where('is_break', false)
        ->whereNull('deleted_at')
        ->select([
            'id as value',
            'day_of_week',
            'slot_code',
            'slot_name',
            'start_time',
            'end_time',
            'duration_minutes',
            'sort_order',
        ])
        ->orderBy('day_of_week')
        ->orderBy('sort_order')
        ->get()
        ->map(fn ($row) => [
            'value' => $row->value,
            'day_of_week' => $row->day_of_week,
            'label' => "{$this->dayLabel($row->day_of_week)} | {$row->slot_code} | {$row->start_time} - {$row->end_time}",
            'meta' => (array) $row,
        ])
        ->values()
        ->toArray();
}

public function entries(array $filters = []): array
{
    $tenantId = $this->tenantId();

    return DB::table('timetable_entries as te')
        ->join('course_offerings as co', 'co.id', '=', 'te.course_offering_id')
        ->leftJoin('faculty_members as fm', 'fm.id', '=', 'te.faculty_member_id')
        ->leftJoin('rooms as r', 'r.id', '=', 'te.room_id')
        ->leftJoin('sections as sec', 'sec.id', '=', 'te.section_id')
        ->leftJoin('academic_teaching_groups as atg', 'atg.id', '=', 'te.academic_teaching_group_id')
        ->where('te.tenant_id', $tenantId)
        ->whereNull('te.deleted_at')
        ->when(!empty($filters['academic_session_id']), fn ($q) => $q->where('te.academic_session_id', $filters['academic_session_id']))
        ->when(!empty($filters['academic_term_id']), fn ($q) => $q->where('te.academic_term_id', $filters['academic_term_id']))
        ->select([
            'te.*',
            'co.course_code',
            'co.course_title',
            'co.subject_type_code',
            'fm.full_name as faculty_name',
            'r.code as room_code',
            'r.name as room_name',
            'sec.code as section_code',
            'sec.name as section_name',
            'atg.group_code',
            'atg.group_name',
        ])
        ->orderBy('te.day_of_week')
        ->orderByDesc('te.id')
        ->paginate((int) ($filters['per_page'] ?? 50))
        ->toArray();
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
public function weeklyGrid(array $filters = []): array
{
    $tenantId = $this->tenantId();

    $query = DB::table('timetable_entries as te')
        ->join('timetable_entry_slots as tes', 'tes.timetable_entry_id', '=', 'te.id')
        ->join('timetable_slots as ts', 'ts.id', '=', 'tes.timetable_slot_id')
        ->join('course_offerings as co', 'co.id', '=', 'te.course_offering_id')
        ->leftJoin('course_teacher_allocations as cta', 'cta.id', '=', 'te.course_teacher_allocation_id')
        ->leftJoin('faculty_members as fm', 'fm.id', '=', 'te.faculty_member_id')
        ->leftJoin('rooms as r', 'r.id', '=', 'te.room_id')
        ->leftJoin('sections as sec', 'sec.id', '=', 'te.section_id')
        ->leftJoin('academic_teaching_groups as atg', 'atg.id', '=', 'te.academic_teaching_group_id')
        ->where('te.tenant_id', $tenantId)
        ->whereNull('te.deleted_at')
        ->where('te.is_active', true)
        ->whereIn('te.status_code', ['valid', 'approved', 'published']);

    if (!empty($filters['academic_session_id'])) {
        $query->where('te.academic_session_id', $filters['academic_session_id']);
    }

    if (!empty($filters['academic_term_id'])) {
        $query->where('te.academic_term_id', $filters['academic_term_id']);
    }

    if (!empty($filters['faculty_member_id'])) {
        $query->where('te.faculty_member_id', $filters['faculty_member_id']);
    }

    if (!empty($filters['room_id'])) {
        $query->where('te.room_id', $filters['room_id']);
    }

    if (!empty($filters['section_id'])) {
        $query->where('te.section_id', $filters['section_id']);
    }

    if (!empty($filters['academic_teaching_group_id'])) {
        $query->where('te.academic_teaching_group_id', $filters['academic_teaching_group_id']);
    }

    if (!empty($filters['course_offering_id'])) {
        $query->where('te.course_offering_id', $filters['course_offering_id']);
    }

    $rows = $query
        ->select([
            'te.id as timetable_entry_id',
            'te.academic_session_id',
            'te.academic_term_id',
            'te.course_offering_id',
            'te.course_teacher_allocation_id',
            'te.faculty_member_id',
            'te.section_id',
            'te.academic_teaching_group_id',
            'te.room_id',
            'te.day_of_week',
            'te.status_code',
            'te.remarks',

            'tes.timetable_slot_id',
            'tes.sort_order as entry_slot_sort_order',

            'ts.slot_code',
            'ts.slot_name',
            'ts.start_time',
            'ts.end_time',
            'ts.duration_minutes',
            'ts.sort_order as timetable_slot_sort_order',

            'co.course_code',
            'co.course_title',
            'co.subject_type_code',
            'co.credit_hours',
            'co.contact_hours_per_week',

            'fm.full_name as faculty_name',
            'fm.employee_no',

            'r.code as room_code',
            'r.name as room_name',
            'r.room_type',
            'r.capacity as room_capacity',

            'sec.code as section_code',
            'sec.name as section_name',

            'atg.group_code',
            'atg.group_name',
            'atg.group_type_code',
        ])
        ->orderBy('te.day_of_week')
        ->orderBy('ts.sort_order')
        ->orderBy('co.course_code')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();

    $days = [
        1 => ['day_of_week' => 1, 'label' => 'Monday', 'entries' => []],
        2 => ['day_of_week' => 2, 'label' => 'Tuesday', 'entries' => []],
        3 => ['day_of_week' => 3, 'label' => 'Wednesday', 'entries' => []],
        4 => ['day_of_week' => 4, 'label' => 'Thursday', 'entries' => []],
        5 => ['day_of_week' => 5, 'label' => 'Friday', 'entries' => []],
        6 => ['day_of_week' => 6, 'label' => 'Saturday', 'entries' => []],
        7 => ['day_of_week' => 7, 'label' => 'Sunday', 'entries' => []],
    ];

    $entriesById = [];

    foreach ($rows as $row) {
        $entryId = $row['timetable_entry_id'];

        if (!isset($entriesById[$entryId])) {
            $entriesById[$entryId] = [
                'id' => $entryId,
                'day_of_week' => $row['day_of_week'],
                'course_offering_id' => $row['course_offering_id'],
                'course_code' => $row['course_code'],
                'course_title' => $row['course_title'],
                'subject_type_code' => $row['subject_type_code'],
                'faculty_member_id' => $row['faculty_member_id'],
                'faculty_name' => $row['faculty_name'],
                'employee_no' => $row['employee_no'],
                'room_id' => $row['room_id'],
                'room_code' => $row['room_code'],
                'room_name' => $row['room_name'],
                'room_type' => $row['room_type'],
                'section_id' => $row['section_id'],
                'section_code' => $row['section_code'],
                'section_name' => $row['section_name'],
                'academic_teaching_group_id' => $row['academic_teaching_group_id'],
                'group_code' => $row['group_code'],
                'group_name' => $row['group_name'],
                'group_type_code' => $row['group_type_code'],
                'status_code' => $row['status_code'],
                'remarks' => $row['remarks'],
                'slots' => [],
            ];
        }

        $entriesById[$entryId]['slots'][] = [
            'timetable_slot_id' => $row['timetable_slot_id'],
            'slot_code' => $row['slot_code'],
            'slot_name' => $row['slot_name'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'duration_minutes' => $row['duration_minutes'],
            'sort_order' => $row['timetable_slot_sort_order'],
        ];
    }

    foreach ($entriesById as $entry) {
        $entry['start_time'] = collect($entry['slots'])->min('start_time');
        $entry['end_time'] = collect($entry['slots'])->max('end_time');
        $entry['slot_count'] = count($entry['slots']);

        $days[(int) $entry['day_of_week']]['entries'][] = $entry;
    }

    return [
        'days' => array_values($days),
        'entries' => array_values($entriesById),
    ];
}
public function approveEntry(int $entryId, ?string $remarks = null): array
{
    $tenantId = $this->tenantId();

    $entry = DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->where('id', $entryId)
        ->whereNull('deleted_at')
        ->first();

    abort_if(!$entry, 404, 'Timetable entry not found.');

    abort_if(
        !in_array($entry->status_code, ['valid', 'approved'], true),
        422,
        'Only valid timetable entries can be approved.'
    );

    DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->where('id', $entryId)
        ->update([
            'status_code' => 'approved',
            'is_active' => true,
            'remarks' => $remarks ?? $entry->remarks,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

    return $this->entry($tenantId, $entryId);
}

public function publishEntry(int $entryId, ?string $remarks = null): array
{
    $tenantId = $this->tenantId();

    $entry = DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->where('id', $entryId)
        ->whereNull('deleted_at')
        ->first();

    abort_if(!$entry, 404, 'Timetable entry not found.');

    abort_if(
        $entry->status_code !== 'approved',
        422,
        'Only approved timetable entries can be published.'
    );

    DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->where('id', $entryId)
        ->update([
            'status_code' => 'published',
            'is_active' => true,
            'remarks' => $remarks ?? $entry->remarks,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

    return $this->entry($tenantId, $entryId);
}

public function cancelEntry(int $entryId, ?string $remarks = null): array
{
    $tenantId = $this->tenantId();

    $entry = DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->where('id', $entryId)
        ->whereNull('deleted_at')
        ->first();

    abort_if(!$entry, 404, 'Timetable entry not found.');

    DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->where('id', $entryId)
        ->update([
            'status_code' => 'cancelled',
            'is_active' => false,
            'remarks' => $remarks ?? $entry->remarks,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

    return $this->entry($tenantId, $entryId);
}

public function approveBatch(array $entryIds, ?string $remarks = null): array
{
    $tenantId = $this->tenantId();

    $entries = DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->whereIn('id', $entryIds)
        ->whereNull('deleted_at')
        ->get();

    abort_if($entries->count() !== count($entryIds), 422, 'One or more timetable entries are invalid.');

    $invalid = $entries
        ->filter(fn ($entry) => !in_array($entry->status_code, ['valid', 'approved'], true))
        ->pluck('id')
        ->values()
        ->toArray();

    abort_if(
        count($invalid) > 0,
        422,
        'Only valid timetable entries can be approved. Invalid entries: ' . implode(', ', $invalid)
    );

    DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->whereIn('id', $entryIds)
        ->update([
            'status_code' => 'approved',
            'is_active' => true,
            'remarks' => $remarks,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

    return [
        'approved_entry_ids' => $entryIds,
        'count' => count($entryIds),
    ];
}

public function publishBatch(array $entryIds, ?string $remarks = null): array
{
    $tenantId = $this->tenantId();

    $entries = DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->whereIn('id', $entryIds)
        ->whereNull('deleted_at')
        ->get();

    abort_if($entries->count() !== count($entryIds), 422, 'One or more timetable entries are invalid.');

    $invalid = $entries
        ->filter(fn ($entry) => $entry->status_code !== 'approved')
        ->pluck('id')
        ->values()
        ->toArray();

    abort_if(
        count($invalid) > 0,
        422,
        'Only approved timetable entries can be published. Invalid entries: ' . implode(', ', $invalid)
    );

    DB::table('timetable_entries')
        ->where('tenant_id', $tenantId)
        ->whereIn('id', $entryIds)
        ->update([
            'status_code' => 'published',
            'is_active' => true,
            'remarks' => $remarks,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

    return [
        'published_entry_ids' => $entryIds,
        'count' => count($entryIds),
    ];
}
}