<?php

namespace App\Modules\Timetable\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimetableValidationService
{
    /**
     * Validate a proposed manual timetable entry. This method is read-only:
     * it does not create timetable_entries or timetable_conflicts.
     */
    public function validate(array $data): array
    {
        $tenantId = $this->tenantId();
        $offering = $this->courseOffering($tenantId, (int) $data['course_offering_id']);
        $calendarPeriod = $this->calendarPeriod(
            $tenantId,
            (int) ($data['timetable_calendar_period_id'] ?? 0),
        );
        $slots = $this->slots($tenantId, $data['timetable_slot_ids'] ?? []);

        $this->assertCalendarPeriodMatchesOffering($calendarPeriod, $offering);
        $this->assertSlotsBelongToCalendarPeriod($slots, $calendarPeriod);

        $conflicts = [];
        $conflicts = array_merge($conflicts, $this->validateSlots($slots));

        $dayOfWeek = $slots->first()?->day_of_week;
        $facultyId = $this->resolveFacultyId($tenantId, $offering, $data);
        $room = $this->room($tenantId, $data['room_id'] ?? null);

        $conflicts = array_merge($conflicts, $this->validateTeacherAllocation($tenantId, $offering, $data, $facultyId));
        $conflicts = array_merge($conflicts, $this->validateRoom($offering, $room));

        if ($slots->isNotEmpty()) {
            $conflicts = array_merge($conflicts, $this->validateFacultyAvailability($tenantId, $facultyId, $slots));
            $conflicts = array_merge($conflicts, $this->validateExistingEntryConflicts(
                $tenantId,
                $offering,
                $facultyId,
                $room?->id,
                (int) $dayOfWeek,
                $slots,
                $data['ignore_timetable_entry_id'] ?? null,
            ));
            $conflicts = array_merge($conflicts, $this->validateWeeklyContactHours(
                $tenantId,
                $offering,
                $slots,
                $data['ignore_timetable_entry_id'] ?? null,
            ));
        }

        return [
            'valid' => collect($conflicts)->where('conflict_severity', 'error')->isEmpty(),
            'conflicts' => $conflicts,
            'resolved' => [
                'tenant_id' => $tenantId,
                'academic_session_id' => $offering->academic_session_id,
                'academic_term_id' => $offering->academic_term_id,
                'timetable_calendar_period_id' => (int) $calendarPeriod->id,
                'timetable_slot_set_id' => (int) $calendarPeriod->timetable_slot_set_id,
                'course_offering_id' => $offering->id,
                'course_teacher_allocation_id' => $data['course_teacher_allocation_id'] ?? null,
                'faculty_member_id' => $facultyId,
                'section_id' => $offering->section_id,
                'academic_teaching_group_id' => $offering->academic_teaching_group_id,
                'room_id' => $room?->id,
                'day_of_week' => $dayOfWeek,
                'scheduled_minutes' => (int) $slots->sum('duration_minutes'),
            ],
        ];
    }

    private function courseOffering(int $tenantId, int $id): object
    {
        $row = DB::table('course_offerings')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$row, 404, 'Course offering not found.');

        return $row;
    }


    private function calendarPeriod(int $tenantId, int $calendarPeriodId): object
    {
        abort_if($calendarPeriodId <= 0, 422, 'Timetable calendar period is required.');

        $period = DB::table('timetable_calendar_periods')
            ->where('tenant_id', $tenantId)
            ->where('id', $calendarPeriodId)
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$period, 404, 'Active timetable calendar period not found.');

        return $period;
    }

    private function assertCalendarPeriodMatchesOffering(object $calendarPeriod, object $offering): void
    {
        abort_if(
            (int) $calendarPeriod->academic_session_id !== (int) $offering->academic_session_id
            || (int) $calendarPeriod->academic_term_id !== (int) $offering->academic_term_id,
            422,
            'The selected timetable calendar period does not belong to the selected course offering academic session and term.'
        );
    }

    private function assertSlotsBelongToCalendarPeriod(Collection $slots, object $calendarPeriod): void
    {
        $invalidSlot = $slots->first(
            fn (object $slot) => (int) $slot->timetable_slot_set_id !== (int) $calendarPeriod->timetable_slot_set_id
        );

        abort_if(
            (bool) $invalidSlot,
            422,
            'One or more selected timetable slots do not belong to the selected timetable calendar period slot set.'
        );
    }

    private function slots(int $tenantId, array $slotIds): Collection
    {
        abort_if(empty($slotIds), 422, 'At least one timetable slot is required.');

        $slots = DB::table('timetable_slots')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', array_values(array_unique(array_map('intval', $slotIds))))
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->orderBy('start_time')
            ->get();

        abort_if($slots->count() !== count(array_unique($slotIds)), 422, 'One or more selected timetable slots are invalid.');

        return $slots;
    }

    private function room(int $tenantId, mixed $roomId): ?object
    {
        if (!$roomId) {
            return null;
        }

        $room = DB::table('rooms')
            ->where('tenant_id', $tenantId)
            ->where('id', $roomId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$room, 404, 'Room not found.');

        return $room;
    }

    private function resolveFacultyId(int $tenantId, object $offering, array $data): ?int
    {
        if (!empty($data['faculty_member_id'])) {
            return (int) $data['faculty_member_id'];
        }

        if (empty($data['course_teacher_allocation_id'])) {
            return null;
        }

        return DB::table('course_teacher_allocations')
            ->where('tenant_id', $tenantId)
            ->where('id', $data['course_teacher_allocation_id'])
            ->where('course_offering_id', $offering->id)
            ->whereNull('deleted_at')
            ->value('faculty_member_id');
    }

    private function validateSlots(Collection $slots): array
    {
        $conflicts = [];
        $days = $slots->pluck('day_of_week')->unique();
        $slotSets = $slots->pluck('timetable_slot_set_id')->unique();

        if ($days->count() !== 1) {
            $conflicts[] = $this->conflict('SLOTS_SPAN_MULTIPLE_DAYS', 'error', 'All selected slots must belong to the same day.');
        }

        if ($slotSets->count() !== 1) {
            $conflicts[] = $this->conflict('SLOTS_FROM_MULTIPLE_SETS', 'error', 'All selected slots must belong to the same slot set.');
        }

        foreach ($slots as $slot) {
            if (!$slot->is_teaching_slot || $slot->is_break) {
                $conflicts[] = $this->conflict('NON_TEACHING_SLOT_SELECTED', 'error', "{$slot->slot_code} is not a teaching slot.", ['timetable_slot_id' => $slot->id]);
            }
        }

        for ($i = 1; $i < $slots->count(); $i++) {
            if ((string) $slots[$i - 1]->end_time !== (string) $slots[$i]->start_time) {
                $conflicts[] = $this->conflict('SLOTS_NOT_CONSECUTIVE', 'error', 'Selected slots must be consecutive.');
                break;
            }
        }

        return $conflicts;
    }

    private function validateTeacherAllocation(int $tenantId, object $offering, array $data, ?int $facultyId): array
    {
        if (!$facultyId) {
            return [$this->conflict('TEACHER_ALLOCATION_MISSING', 'error', 'Select a valid teacher allocation before scheduling this offering.')];
        }

        if (empty($data['course_teacher_allocation_id'])) {
            return [$this->conflict('TEACHER_ALLOCATION_REFERENCE_MISSING', 'warning', 'A faculty member was selected directly; no course teacher allocation reference was supplied.')];
        }

        $allocation = DB::table('course_teacher_allocations')
            ->where('tenant_id', $tenantId)
            ->where('id', $data['course_teacher_allocation_id'])
            ->where('course_offering_id', $offering->id)
            ->where('faculty_member_id', $facultyId)
            ->whereNull('deleted_at')
            ->first();

        if (!$allocation) {
            return [$this->conflict('INVALID_TEACHER_ALLOCATION', 'error', 'The selected teacher allocation does not belong to this course offering.')];
        }

        if (in_array($allocation->allocation_status_code, ['conflicted', 'rejected', 'cancelled'], true)) {
            return [$this->conflict('TEACHER_ALLOCATION_NOT_SCHEDULABLE', 'error', 'The selected teacher allocation is not schedulable.', [
                'allocation_status_code' => $allocation->allocation_status_code,
            ])];
        }

        return [];
    }

    private function validateRoom(object $offering, ?object $room): array
    {
        if (!$room) {
            return [$this->conflict('ROOM_REQUIRED', 'error', 'Select a room before scheduling this offering.')];
        }

        $conflicts = [];
        if (!(bool) $room->is_available_for_timetable) {
            $conflicts[] = $this->conflict('ROOM_NOT_TIMETABLE_ACTIVE', 'error', 'Selected room is not available for timetable use.');
        }
        if (($offering->required_capacity ?? null) && (int) $room->capacity < (int) $offering->required_capacity) {
            $conflicts[] = $this->conflict('ROOM_CAPACITY_INSUFFICIENT', 'error', 'Selected room capacity is below the offering requirement.', [
                'required_capacity' => (int) $offering->required_capacity,
                'room_capacity' => (int) $room->capacity,
            ]);
        }
        if ((bool) ($offering->requires_lab ?? false) && $room->room_type !== 'lab') {
            $conflicts[] = $this->conflict('ROOM_LAB_REQUIREMENT_MISMATCH', 'error', 'This offering requires a lab room.');
        }
        if (!empty($offering->required_room_type_code)) {
            $required = $this->normalizeRoomType($offering->required_room_type_code);
            if ($required !== $room->room_type) {
                $conflicts[] = $this->conflict('ROOM_TYPE_MISMATCH', 'error', 'Selected room type does not match the offering requirement.', [
                    'required_room_type_code' => $offering->required_room_type_code,
                    'actual_room_type' => $room->room_type,
                ]);
            }
        }

        return $conflicts;
    }

    private function validateFacultyAvailability(int $tenantId, ?int $facultyId, Collection $slots): array
    {
        if (!$facultyId || !DB::getSchemaBuilder()->hasTable('faculty_availability')) {
            return [];
        }

        $start = $slots->min('start_time');
        $end = $slots->max('end_time');
        $day = $slots->first()->day_of_week;

        $blocked = DB::table('faculty_availability')
            ->where('tenant_id', $tenantId)
            ->where('faculty_member_id', $facultyId)
            ->where('day_of_week', $day)
            ->whereIn('availability_type', ['unavailable', 'restricted'])
            ->where('status_code', 'active')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        return $blocked
            ? [$this->conflict('FACULTY_AVAILABILITY_CONFLICT', 'error', 'Faculty is unavailable during one or more selected slots.')]
            : [];
    }

    private function validateExistingEntryConflicts(
        int $tenantId,
        object $offering,
        ?int $facultyId,
        ?int $roomId,
        int $dayOfWeek,
        Collection $slots,
        mixed $ignoreEntryId,
    ): array {
        $slotIds = $slots->pluck('id')->all();
        $query = DB::table('timetable_entries as te')
            ->join('timetable_entry_slots as tes', 'tes.timetable_entry_id', '=', 'te.id')
            ->where('te.tenant_id', $tenantId)
            ->where('te.academic_session_id', $offering->academic_session_id)
            ->where('te.academic_term_id', $offering->academic_term_id)
            ->where('te.day_of_week', $dayOfWeek)
            ->where('te.is_active', true)
            ->whereNull('te.deleted_at')
            ->whereIn('tes.timetable_slot_id', $slotIds);

        if ($ignoreEntryId) {
            $query->where('te.id', '!=', $ignoreEntryId);
        }

        $existing = $query->select('te.*', 'tes.timetable_slot_id')->get();
        $conflicts = [];

        foreach ($existing as $entry) {
            if ($facultyId && (int) $entry->faculty_member_id === $facultyId) {
                $conflicts[] = $this->conflict('TEACHER_SLOT_CONFLICT', 'error', 'Teacher is already assigned in one or more selected slots.', [
                    'conflicting_timetable_entry_id' => $entry->id,
                    'timetable_slot_id' => $entry->timetable_slot_id,
                ]);
            }
            if ($roomId && (int) $entry->room_id === $roomId) {
                $conflicts[] = $this->conflict('ROOM_SLOT_CONFLICT', 'error', 'Room is already occupied in one or more selected slots.', [
                    'conflicting_timetable_entry_id' => $entry->id,
                    'timetable_slot_id' => $entry->timetable_slot_id,
                ]);
            }
            if ($offering->section_id && (int) $entry->section_id === (int) $offering->section_id) {
                $conflicts[] = $this->conflict('SECTION_SLOT_CONFLICT', 'error', 'Section already has a timetable entry in one or more selected slots.', [
                    'conflicting_timetable_entry_id' => $entry->id,
                    'timetable_slot_id' => $entry->timetable_slot_id,
                ]);
            }
            if ($offering->academic_teaching_group_id && (int) $entry->academic_teaching_group_id === (int) $offering->academic_teaching_group_id) {
                $conflicts[] = $this->conflict('TEACHING_GROUP_SLOT_CONFLICT', 'error', 'Teaching group already has a timetable entry in one or more selected slots.', [
                    'conflicting_timetable_entry_id' => $entry->id,
                    'timetable_slot_id' => $entry->timetable_slot_id,
                ]);
            }
            if ($this->sectionGroupOverlap($tenantId, $offering, $entry)) {
                $conflicts[] = $this->conflict('SECTION_GROUP_STUDENT_CONFLICT', 'error', 'A theory section and one of its teaching groups overlap in the selected slots.', [
                    'conflicting_timetable_entry_id' => $entry->id,
                    'timetable_slot_id' => $entry->timetable_slot_id,
                ]);
            }
        }

        return $this->uniqueConflicts($conflicts);
    }

    private function validateWeeklyContactHours(int $tenantId, object $offering, Collection $slots, mixed $ignoreEntryId): array
    {
        if (!(float) $offering->contact_hours_per_week) {
            return [];
        }

        $query = DB::table('timetable_entries as te')
            ->join('timetable_entry_slots as tes', 'tes.timetable_entry_id', '=', 'te.id')
            ->join('timetable_slots as ts', 'ts.id', '=', 'tes.timetable_slot_id')
            ->where('te.tenant_id', $tenantId)
            ->where('te.course_offering_id', $offering->id)
            ->where('te.is_active', true)
            ->whereNull('te.deleted_at');

        if ($ignoreEntryId) {
            $query->where('te.id', '!=', $ignoreEntryId);
        }

        $scheduledMinutes = (int) $query->sum('ts.duration_minutes');
        $proposedMinutes = (int) $slots->sum('duration_minutes');
        $requiredMinutes = (int) round(((float) $offering->contact_hours_per_week) * 60);

        if (($scheduledMinutes + $proposedMinutes) <= $requiredMinutes) {
            return [];
        }

        return [$this->conflict('OFFERING_WEEKLY_HOURS_EXCEEDED', 'error', 'This entry would exceed the offering weekly contact hours.', [
            'scheduled_minutes' => $scheduledMinutes,
            'proposed_minutes' => $proposedMinutes,
            'required_minutes' => $requiredMinutes,
        ])];
    }

    private function sectionGroupOverlap(int $tenantId, object $offering, object $entry): bool
    {
        $proposedSection = $offering->section_id ? (int) $offering->section_id : null;
        $proposedGroup = $offering->academic_teaching_group_id ? (int) $offering->academic_teaching_group_id : null;
        $existingSection = $entry->section_id ? (int) $entry->section_id : null;
        $existingGroup = $entry->academic_teaching_group_id ? (int) $entry->academic_teaching_group_id : null;

        if ($proposedSection && $existingGroup) {
            return (int) DB::table('academic_teaching_groups')
                ->where('tenant_id', $tenantId)
                ->where('id', $existingGroup)
                ->value('section_id') === $proposedSection;
        }

        if ($proposedGroup && $existingSection) {
            return (int) DB::table('academic_teaching_groups')
                ->where('tenant_id', $tenantId)
                ->where('id', $proposedGroup)
                ->value('section_id') === $existingSection;
        }

        return false;
    }

    private function normalizeRoomType(string $type): string
    {
        return match ($type) {
            'computer_lab', 'science_lab', 'lab' => 'lab',
            'lecture_theater', 'classroom' => 'classroom',
            'seminar_room' => 'seminar_hall',
            default => $type,
        };
    }

    private function conflict(string $code, string $severity, string $message, array $context = []): array
    {
        return [
            'conflict_code' => $code,
            'conflict_severity' => $severity,
            'conflict_message' => $message,
            'conflict_context' => $context,
        ];
    }

    private function uniqueConflicts(array $conflicts): array
    {
        return collect($conflicts)
            ->unique(fn (array $conflict) => $conflict['conflict_code'].'|'.json_encode($conflict['conflict_context']))
            ->values()
            ->all();
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;
        abort_if(!$tenantId, 422, 'Active tenant could not be resolved.');

        return (int) $tenantId;
    }
}
