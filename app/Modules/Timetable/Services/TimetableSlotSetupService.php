<?php

namespace App\Modules\Timetable\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TimetableSlotSetupService
{
    public function context(): array
    {
        $tenantId = $this->tenantId();

        return [
            'academic_sessions' => DB::table('academic_sessions')
                ->where('tenant_id', $tenantId)
                ->when(Schema::hasColumn('academic_sessions', 'status'), fn ($q) => $q->whereIn('status', ['active', 'planned']))
                ->select('id as value', 'name as label', 'code')
                ->orderBy('id')
                ->get()->map(fn ($row) => (array) $row)->toArray(),
            'academic_terms' => DB::table('academic_terms')
                ->where('tenant_id', $tenantId)
                ->when(Schema::hasColumn('academic_terms', 'status'), fn ($q) => $q->where('status', 'active'))
                ->select('id as value', 'name as label', 'code')
                ->orderBy('id')
                ->get()->map(fn ($row) => (array) $row)->toArray(),
            'slot_sets' => $this->slotSets(),
            'calendar_periods' => $this->calendarPeriods(),
            'days' => $this->days(),
        ];
    }

    public function slotSets(): array
    {
        return DB::table('timetable_slot_sets')
            ->where('tenant_id', $this->tenantId())
            ->whereNull('deleted_at')
            ->orderByDesc('is_default')
            ->orderBy('slot_set_name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    public function calendarPeriods(): array
    {
        $tenantId = $this->tenantId();

        return DB::table('timetable_calendar_periods as tcp')
            ->join('timetable_slot_sets as tss', 'tss.id', '=', 'tcp.timetable_slot_set_id')
            ->leftJoin('academic_sessions as acs', 'acs.id', '=', 'tcp.academic_session_id')
            ->leftJoin('academic_terms as act', 'act.id', '=', 'tcp.academic_term_id')
            ->where('tcp.tenant_id', $tenantId)
            ->whereNull('tcp.deleted_at')
            ->select([
                'tcp.*',
                'tss.slot_set_code', 'tss.slot_set_name',
                'acs.name as academic_session_name',
                'act.name as academic_term_name',
            ])
            ->orderByDesc('tcp.is_default')
            ->orderByDesc('tcp.priority')
            ->orderByDesc('tcp.id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    public function slots(int $slotSetId): array
    {
        return DB::table('timetable_slots')
            ->where('tenant_id', $this->tenantId())
            ->where('timetable_slot_set_id', $slotSetId)
            ->whereNull('deleted_at')
            ->orderBy('day_of_week')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    public function createSlotSet(array $data): array
    {
        $tenantId = $this->tenantId();
        $id = DB::transaction(function () use ($tenantId, $data) {
            if (!empty($data['is_default'])) {
                DB::table('timetable_slot_sets')->where('tenant_id', $tenantId)->whereNull('deleted_at')->update(['is_default' => false, 'updated_at' => now()]);
            }
            return DB::table('timetable_slot_sets')->insertGetId([
                'tenant_id' => $tenantId,
                'slot_set_code' => $data['slot_set_code'],
                'slot_set_name' => $data['slot_set_name'],
                'description' => $data['description'] ?? null,
                'is_default' => !empty($data['is_default']),
                'status_code' => $data['status_code'] ?? 'active',
                'created_by' => auth()->id(), 'updated_by' => auth()->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return (array) DB::table('timetable_slot_sets')->where('tenant_id', $tenantId)->where('id', $id)->first();
    }

    public function createCalendarPeriod(array $data): array
    {
        $tenantId = $this->tenantId();
        $id = DB::transaction(function () use ($tenantId, $data) {
            if (!empty($data['is_default'])) {
                DB::table('timetable_calendar_periods')
                    ->where('tenant_id', $tenantId)
                    ->where('academic_session_id', $data['academic_session_id'])
                    ->where('academic_term_id', $data['academic_term_id'])
                    ->whereNull('deleted_at')
                    ->update(['is_default' => false, 'updated_at' => now()]);
            }
            return DB::table('timetable_calendar_periods')->insertGetId([
                'tenant_id' => $tenantId,
                'academic_session_id' => $data['academic_session_id'],
                'academic_term_id' => $data['academic_term_id'],
                'timetable_slot_set_id' => $data['timetable_slot_set_id'],
                'period_code' => $data['period_code'],
                'period_name' => $data['period_name'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'priority' => $data['priority'] ?? 1,
                'is_default' => !empty($data['is_default']),
                'status_code' => $data['status_code'] ?? 'active',
                'created_by' => auth()->id(), 'updated_by' => auth()->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return (array) DB::table('timetable_calendar_periods')->where('tenant_id', $tenantId)->where('id', $id)->first();
    }

    public function createSlot(array $data): array
    {
        $tenantId = $this->tenantId();
        $minutes = $this->minutes($data['start_time'], $data['end_time']);
        abort_if($minutes <= 0, 422, 'Slot end time must be after start time.');

        $id = DB::table('timetable_slots')->insertGetId([
            'tenant_id' => $tenantId,
            'timetable_slot_set_id' => $data['timetable_slot_set_id'],
            'day_of_week' => $data['day_of_week'],
            'slot_code' => $data['slot_code'],
            'slot_name' => $data['slot_name'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $minutes,
            'sort_order' => $data['sort_order'],
            'is_teaching_slot' => !empty($data['is_teaching_slot']),
            'is_break' => !empty($data['is_break']),
            'status_code' => $data['status_code'] ?? 'active',
            'created_by' => auth()->id(), 'updated_by' => auth()->id(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return (array) DB::table('timetable_slots')->where('tenant_id', $tenantId)->where('id', $id)->first();
    }

    public function copyDay(int $slotSetId, int $fromDay, int $toDay): int
    {
        $tenantId = $this->tenantId();
        abort_if($fromDay === $toDay, 422, 'Source and target days must be different.');

        return DB::transaction(function () use ($tenantId, $slotSetId, $fromDay, $toDay) {
            DB::table('timetable_slots')->where('tenant_id', $tenantId)->where('timetable_slot_set_id', $slotSetId)->where('day_of_week', $toDay)->whereNull('deleted_at')->delete();
            $source = DB::table('timetable_slots')->where('tenant_id', $tenantId)->where('timetable_slot_set_id', $slotSetId)->where('day_of_week', $fromDay)->whereNull('deleted_at')->orderBy('sort_order')->get();
            foreach ($source as $slot) {
                DB::table('timetable_slots')->insert([
                    'tenant_id' => $tenantId, 'timetable_slot_set_id' => $slotSetId, 'day_of_week' => $toDay,
                    'slot_code' => $slot->slot_code, 'slot_name' => $slot->slot_name,
                    'start_time' => $slot->start_time, 'end_time' => $slot->end_time,
                    'duration_minutes' => $slot->duration_minutes, 'sort_order' => $slot->sort_order,
                    'is_teaching_slot' => $slot->is_teaching_slot, 'is_break' => $slot->is_break,
                    'status_code' => $slot->status_code, 'created_by' => auth()->id(), 'updated_by' => auth()->id(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return $source->count();
        });
    }

    private function days(): array
    {
        return collect([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'])
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->toArray();
    }

    private function minutes(string $start, string $end): int
    {
        return (int) ((strtotime($end) - strtotime($start)) / 60);
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;
        abort_if(!$tenantId, 422, 'Active tenant could not be resolved.');
        return (int) $tenantId;
    }
}
