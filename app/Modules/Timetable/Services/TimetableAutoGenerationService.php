<?php

namespace App\Modules\Timetable\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central, scope-based university timetable generator.
 *
 * Normal use: select session, term and optional organizational scope.
 * The service resolves offerings, allocated teachers, groups, eligible rooms and
 * active timetable slots, then creates only validation-clean timetable drafts.
 * Anything that cannot be placed is retained in generation diagnostics.
 */
class TimetableAutoGenerationService
{
    public function __construct(
        private readonly TimetableValidationService $validationService,
        private readonly TimetableEntryService $entryService,
    ) {
    }

    public function generate(array $input): array
    {
        $tenantId = $this->tenantId();
        $period = $this->calendarPeriod($tenantId, (int) $input['timetable_calendar_period_id']);

        abort_if(
            (int) $period->academic_session_id !== (int) $input['academic_session_id']
            || (int) $period->academic_term_id !== (int) $input['academic_term_id'],
            422,
            'The selected timetable calendar period does not belong to the selected academic session and term.'
        );

        $runId = DB::table('timetable_generation_runs')->insertGetId([
            'tenant_id' => $tenantId,
            'academic_session_id' => (int) $input['academic_session_id'],
            'academic_term_id' => (int) $input['academic_term_id'],
            'timetable_calendar_period_id' => (int) $period->id,
            'timetable_slot_set_id' => (int) $period->timetable_slot_set_id,
            'status_code' => 'running',
            'generation_scope_code' => $this->scopeCode($input),
            'generation_filters' => json_encode($this->scopeFilters($input)),
            'started_at' => now(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $slots = $this->activeTeachingSlots($tenantId, (int) $period->timetable_slot_set_id);
            abort_if($slots->isEmpty(), 422, 'No active teaching slots are configured for the selected timetable period.');

            $jobs = $this->generationJobs($tenantId, $input);
            $jobs = $this->prioritizeJobs($tenantId, $jobs);

            DB::table('timetable_generation_runs')
                ->where('id', $runId)
                ->update([
                    'total_offerings' => $jobs->count(),
                    'updated_at' => now(),
                ]);

            $stats = [
                'scheduled_offerings' => 0,
                'unscheduled_offerings' => 0,
                'generated_entries' => 0,
                'diagnostics' => 0,
            ];

            foreach ($jobs as $job) {
                $result = $this->scheduleJob(
                    $tenantId,
                    $runId,
                    (object) $job,
                    $period,
                    $slots,
                    $input,
                );

                $stats['scheduled_offerings'] += $result['scheduled_offerings'];
                $stats['unscheduled_offerings'] += $result['unscheduled_offerings'];
                $stats['generated_entries'] += $result['generated_entries'];
                $stats['diagnostics'] += $result['diagnostics'];
            }

            $finalStatus = $stats['unscheduled_offerings'] > 0
                ? 'completed_with_issues'
                : 'completed';

            DB::table('timetable_generation_runs')
                ->where('id', $runId)
                ->update([
                    'status_code' => $finalStatus,
                    'scheduled_offerings' => $stats['scheduled_offerings'],
                    'unscheduled_offerings' => $stats['unscheduled_offerings'],
                    'generated_entries' => $stats['generated_entries'],
                    'conflict_count' => $stats['diagnostics'],
                    'summary_note' => $this->summaryNote($stats),
                    'completed_at' => now(),
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            return $this->runSummary($tenantId, $runId);
        } catch (\Throwable $e) {
            DB::table('timetable_generation_runs')
                ->where('id', $runId)
                ->update([
                    'status_code' => 'failed',
                    'summary_note' => $e->getMessage(),
                    'completed_at' => now(),
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }

    public function runs(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        return DB::table('timetable_generation_runs')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->when(!empty($filters['academic_session_id']), fn ($q) => $q->where('academic_session_id', $filters['academic_session_id']))
            ->when(!empty($filters['academic_term_id']), fn ($q) => $q->where('academic_term_id', $filters['academic_term_id']))
            ->when(!empty($filters['status_code']), fn ($q) => $q->where('status_code', $filters['status_code']))
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 30))
            ->toArray();
    }

    public function runDetails(int $runId): array
    {
        $tenantId = $this->tenantId();
        return $this->runSummary($tenantId, $runId);
    }

    private function scheduleJob(
        int $tenantId,
        int $runId,
        object $job,
        object $period,
        Collection $allSlots,
        array $input,
    ): array {
        $runItemId = DB::table('timetable_generation_run_items')->insertGetId([
            'tenant_id' => $tenantId,
            'timetable_generation_run_id' => $runId,
            'course_offering_id' => $job->course_offering_id,
            'course_teacher_allocation_id' => $job->course_teacher_allocation_id,
            'faculty_member_id' => $job->faculty_member_id,
            'section_id' => $job->section_id,
            'academic_teaching_group_id' => $job->academic_teaching_group_id,
            'status_code' => 'pending',
            'required_minutes' => $job->required_minutes,
            'scheduled_minutes' => 0,
            'required_capacity' => $job->required_capacity,
            'required_room_type_code' => $job->required_room_type_code,
            'priority_score' => $job->priority_score,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($job->subject_type_code !== 'theory' && !$job->academic_teaching_group_id) {
            return $this->failJob(
                $tenantId,
                $runId,
                $runItemId,
                $job,
                'PRACTICAL_GROUP_NOT_DEFINED',
                'A practical or lab offering requires an active teaching group before automatic scheduling.',
                ['subject_type_code' => $job->subject_type_code]
            );
        }

        if (!$job->course_teacher_allocation_id || !$job->faculty_member_id) {
            return $this->failJob(
                $tenantId,
                $runId,
                $runItemId,
                $job,
                'NO_TEACHER_ALLOCATION',
                'No valid or approved teacher allocation exists for this course offering.'
            );
        }

        $rooms = $this->eligibleRooms($tenantId, $job, $input);
        if ($rooms->isEmpty()) {
            return $this->failJob(
                $tenantId,
                $runId,
                $runItemId,
                $job,
                'NO_ELIGIBLE_ROOM',
                'No timetable-active room satisfies this offering capacity, room type, or lab requirement.',
                [
                    'required_capacity' => $job->required_capacity,
                    'required_room_type_code' => $job->required_room_type_code,
                    'requires_lab' => (bool) $job->requires_lab,
                ]
            );
        }

        $alreadyScheduled = $this->scheduledMinutes($tenantId, $job->course_offering_id);
        $remainingMinutes = max(0, $job->required_minutes - $alreadyScheduled);

        if ($remainingMinutes === 0) {
            DB::table('timetable_generation_run_items')->where('id', $runItemId)->update([
                'status_code' => 'skipped',
                'scheduled_minutes' => $alreadyScheduled,
                'failure_code' => 'ALREADY_SCHEDULED',
                'failure_message' => 'Required weekly contact hours are already fulfilled by active timetable entries.',
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            return ['scheduled_offerings' => 0, 'unscheduled_offerings' => 0, 'generated_entries' => 0, 'diagnostics' => 0];
        }

        $chunks = $this->sessionChunks($job, $remainingMinutes, $allSlots);
        $created = 0;
        $scheduledMinutes = 0;
        $lastEntryId = null;

        foreach ($chunks as $chunkMinutes) {
            $scheduled = false;

            foreach ($this->slotSequences($allSlots, $chunkMinutes) as $sequence) {
                foreach ($rooms as $room) {
                    $payload = [
                        'course_offering_id' => $job->course_offering_id,
                        'course_teacher_allocation_id' => $job->course_teacher_allocation_id,
                        'faculty_member_id' => $job->faculty_member_id,
                        'room_id' => $room->id,
                        'timetable_calendar_period_id' => $period->id,
                        'timetable_slot_ids' => $sequence['slot_ids'],
                        'entry_source_code' => 'auto_generated',
                        'remarks' => "Central generation run #{$runId}",
                    ];

                    $validation = $this->validationService->validate($payload);
                    if (!$validation['valid']) {
                        continue;
                    }

                    $saved = $this->entryService->save($payload);
                    $lastEntryId = $saved['entry']['id'] ?? null;
                    $scheduledMinutes += $sequence['duration_minutes'];
                    $created++;
                    $scheduled = true;
                    break 2;
                }
            }

            if (!$scheduled) {
                break;
            }
        }

        if ($scheduledMinutes === $remainingMinutes) {
            DB::table('timetable_generation_run_items')->where('id', $runItemId)->update([
                'status_code' => 'scheduled',
                'scheduled_minutes' => $scheduledMinutes,
                'generated_timetable_entry_id' => $lastEntryId,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            return ['scheduled_offerings' => 1, 'unscheduled_offerings' => 0, 'generated_entries' => $created, 'diagnostics' => 0];
        }

        $status = $scheduledMinutes > 0 ? 'partially_scheduled' : 'unscheduled';
        $code = $scheduledMinutes > 0 ? 'WEEKLY_CONTACT_HOURS_UNFULFILLED' : 'SLOT_CAPACITY_EXHAUSTED';
        $message = $scheduledMinutes > 0
            ? 'Only part of the required weekly contact hours could be placed without violating hard constraints.'
            : 'No valid teacher-room-slot sequence is available for this offering.';

        DB::table('timetable_generation_run_items')->where('id', $runItemId)->update([
            'status_code' => $status,
            'scheduled_minutes' => $scheduledMinutes,
            'generated_timetable_entry_id' => $lastEntryId,
            'failure_code' => $code,
            'failure_message' => $message,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

        $this->diagnostic($tenantId, $runId, $runItemId, $job, $code, 'error', $message, [
            'required_minutes' => $remainingMinutes,
            'scheduled_minutes' => $scheduledMinutes,
            'remaining_minutes' => max(0, $remainingMinutes - $scheduledMinutes),
        ]);

        return [
            'scheduled_offerings' => $scheduledMinutes > 0 ? 1 : 0,
            'unscheduled_offerings' => 1,
            'generated_entries' => $created,
            'diagnostics' => 1,
        ];
    }

    private function generationJobs(int $tenantId, array $input): Collection
    {
        $query = DB::table('course_offerings as co')
            ->join('course_teacher_allocations as cta', function ($join) use ($tenantId) {
                $join->on('cta.course_offering_id', '=', 'co.id')
                    ->where('cta.tenant_id', '=', $tenantId)
                    ->whereNull('cta.deleted_at')
                    ->whereIn('cta.allocation_status_code', ['valid', 'approved']);
            })
            ->join('faculty_members as fm', function ($join) use ($tenantId) {
                $join->on('fm.id', '=', 'cta.faculty_member_id')
                    ->where('fm.tenant_id', '=', $tenantId)
                    ->whereNull('fm.deleted_at')
                    ->where('fm.status_code', '=', 'active');
            })
            ->where('co.tenant_id', $tenantId)
            ->whereNull('co.deleted_at')
            ->where('co.academic_session_id', $input['academic_session_id'])
            ->where('co.academic_term_id', $input['academic_term_id'])
            ->leftJoin('programs as p', 'p.id', '=', 'co.program_id')
            ->whereIn('co.status_code', ['offered', 'allocated', 'scheduled'])
            ->when(!empty($input['program_id']), fn ($q) => $q->where('co.program_id', $input['program_id']))
            ->when(!empty($input['student_batch_id']), fn ($q) => $q->where('co.student_batch_id', $input['student_batch_id']))
            ->when(!empty($input['section_id']), fn ($q) => $q->where('co.section_id', $input['section_id']))
            ->when(!empty($input['course_offering_ids']), fn ($q) => $q->whereIn('co.id', (array) $input['course_offering_ids']))
            ->when(!empty($input['faculty_id']), function ($q) use ($input) {
                $q->where('p.faculty_id', $input['faculty_id']);
            })
            ->when(!empty($input['department_id']), function ($q) use ($input) {
                $q->where('p.department_id', $input['department_id']);
            })
            ->when(!empty($input['program_level_id']), function ($q) use ($input) {
                $q->where('p.program_level_id', $input['program_level_id']);
            })
            ->select([
                'co.id as course_offering_id',
                'co.academic_session_id',
                'co.academic_term_id',
                'co.program_id',
                'co.student_batch_id',
                'co.section_id',
                'co.academic_teaching_group_id',
                'co.course_code',
                'co.course_title',
                'co.subject_type_code',
                'co.contact_hours_per_week',
                'co.required_sessions_per_week',
                'co.required_hours_per_session',
                'co.required_capacity',
                'co.required_room_type_code',
                'co.requires_lab',
                'co.requires_multimedia',
                'cta.id as course_teacher_allocation_id',
                'cta.faculty_member_id',
                'cta.allocation_role_code',
                'fm.full_name as faculty_name',
            ])
            ->get()
            ->map(function ($row) {
                $row = (array) $row;
                $row['required_minutes'] = (int) round(((float) $row['contact_hours_per_week']) * 60);
                $row['priority_score'] = $this->priorityScore((object) $row);
                return $row;
            });

        return $query;
    }

    private function prioritizeJobs(int $tenantId, Collection $jobs): Collection
    {
        return $jobs
            ->map(function (array $job) use ($tenantId) {
                $rooms = $this->eligibleRooms($tenantId, (object) $job, []);
                $job['priority_score'] += max(0, 100 - ($rooms->count() * 10));
                return $job;
            })
            ->sortByDesc('priority_score')
            ->values();
    }

    private function eligibleRooms(int $tenantId, object $job, array $input): Collection
    {
        $rooms = DB::table('rooms')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('is_available_for_timetable', true)
            ->whereNull('deleted_at')
            ->when(!empty($input['campus_id']) && Schema::hasColumn('rooms', 'campus_id'), fn ($q) => $q->where('campus_id', $input['campus_id']))
            ->when($job->required_capacity, fn ($q) => $q->where('capacity', '>=', $job->required_capacity))
            ->orderBy('capacity')
            ->orderBy('code')
            ->get();

        $requiredType = $this->normalizedRoomType($job->required_room_type_code);

        return $rooms->filter(function ($room) use ($job, $requiredType) {
            if ((bool) $job->requires_lab && $room->room_type !== 'lab') {
                return false;
            }

            return !$requiredType || $room->room_type === $requiredType;
        })->values();
    }

    private function activeTeachingSlots(int $tenantId, int $slotSetId): Collection
    {
        return DB::table('timetable_slots')
            ->where('tenant_id', $tenantId)
            ->where('timetable_slot_set_id', $slotSetId)
            ->where('status_code', 'active')
            ->where('is_teaching_slot', true)
            ->where('is_break', false)
            ->whereNull('deleted_at')
            ->orderBy('day_of_week')
            ->orderBy('sort_order')
            ->get();
    }

    private function slotSequences(Collection $slots, int $requiredMinutes): array
    {
        $sequences = [];

        foreach ($slots->groupBy('day_of_week') as $daySlots) {
            $daySlots = $daySlots->values();

            for ($start = 0; $start < $daySlots->count(); $start++) {
                $minutes = 0;
                $ids = [];
                $lastEnd = null;

                for ($cursor = $start; $cursor < $daySlots->count(); $cursor++) {
                    $slot = $daySlots[$cursor];

                    if ($lastEnd !== null && (string) $lastEnd !== (string) $slot->start_time) {
                        break;
                    }

                    $minutes += (int) $slot->duration_minutes;
                    $ids[] = (int) $slot->id;
                    $lastEnd = $slot->end_time;

                    if ($minutes === $requiredMinutes) {
                        $sequences[] = [
                            'day_of_week' => (int) $slot->day_of_week,
                            'slot_ids' => $ids,
                            'duration_minutes' => $minutes,
                        ];
                        break;
                    }

                    if ($minutes > $requiredMinutes) {
                        break;
                    }
                }
            }
        }

        return $sequences;
    }

    private function sessionChunks(object $job, int $remainingMinutes, Collection $slots): array
    {
        if ($remainingMinutes <= 0) {
            return [];
        }

        $minutesPerSession = (int) round(((float) ($job->required_hours_per_session ?? 0)) * 60);
        $sessionsPerWeek = (int) ($job->required_sessions_per_week ?? 0);

        if ($minutesPerSession > 0 && $sessionsPerWeek > 0) {
            $chunks = array_fill(0, $sessionsPerWeek, $minutesPerSession);
            $total = array_sum($chunks);
            if ($total === $remainingMinutes) {
                return $chunks;
            }
        }

        $minimumSlot = (int) $slots->min('duration_minutes');
        $isPractical = in_array(strtolower((string) $job->subject_type_code), ['practical', 'lab'], true);

        if ($isPractical && $remainingMinutes <= 180 && $remainingMinutes % max($minimumSlot, 1) === 0) {
            return [$remainingMinutes];
        }

        $chunks = [];
        while ($remainingMinutes > 0) {
            $chunk = min(60, $remainingMinutes);
            if ($chunk % max($minimumSlot, 1) !== 0) {
                $chunk = $minimumSlot;
            }
            $chunks[] = $chunk;
            $remainingMinutes -= $chunk;
        }

        return $chunks;
    }

    private function scheduledMinutes(int $tenantId, int $courseOfferingId): int
    {
        return (int) DB::table('timetable_entries as te')
            ->join('timetable_entry_slots as tes', 'tes.timetable_entry_id', '=', 'te.id')
            ->join('timetable_slots as ts', 'ts.id', '=', 'tes.timetable_slot_id')
            ->where('te.tenant_id', $tenantId)
            ->where('te.course_offering_id', $courseOfferingId)
            ->where('te.is_active', true)
            ->whereNull('te.deleted_at')
            ->sum('ts.duration_minutes');
    }

    private function failJob(
        int $tenantId,
        int $runId,
        int $runItemId,
        object $job,
        string $code,
        string $message,
        array $context = [],
    ): array {
        DB::table('timetable_generation_run_items')->where('id', $runItemId)->update([
            'status_code' => 'unscheduled',
            'failure_code' => $code,
            'failure_message' => $message,
            'diagnostic_context' => json_encode($context),
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

        $this->diagnostic($tenantId, $runId, $runItemId, $job, $code, 'error', $message, $context);

        return ['scheduled_offerings' => 0, 'unscheduled_offerings' => 1, 'generated_entries' => 0, 'diagnostics' => 1];
    }

    private function diagnostic(
        int $tenantId,
        int $runId,
        int $runItemId,
        object $job,
        string $code,
        string $severity,
        string $message,
        array $context = [],
    ): void {
        DB::table('timetable_generation_diagnostics')->insert([
            'tenant_id' => $tenantId,
            'timetable_generation_run_id' => $runId,
            'timetable_generation_run_item_id' => $runItemId,
            'course_offering_id' => $job->course_offering_id,
            'severity_code' => $severity,
            'diagnostic_code' => $code,
            'diagnostic_message' => $message,
            'diagnostic_context' => json_encode($context),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function calendarPeriod(int $tenantId, int $id): object
    {
        $period = DB::table('timetable_calendar_periods')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$period, 404, 'Active timetable calendar period not found.');

        return $period;
    }

    private function runSummary(int $tenantId, int $runId): array
    {
        $run = DB::table('timetable_generation_runs')
            ->where('tenant_id', $tenantId)
            ->where('id', $runId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$run, 404, 'Timetable generation run not found.');

        $items = DB::table('timetable_generation_run_items as ri')
            ->join('course_offerings as co', 'co.id', '=', 'ri.course_offering_id')
            ->leftJoin('faculty_members as fm', 'fm.id', '=', 'ri.faculty_member_id')
            ->where('ri.tenant_id', $tenantId)
            ->where('ri.timetable_generation_run_id', $runId)
            ->select([
                'ri.*',
                'co.course_code',
                'co.course_title',
                'co.subject_type_code',
                'fm.full_name as faculty_name',
            ])
            ->orderByDesc('ri.priority_score')
            ->get();

        $diagnostics = DB::table('timetable_generation_diagnostics')
            ->where('tenant_id', $tenantId)
            ->where('timetable_generation_run_id', $runId)
            ->orderByDesc('id')
            ->get();

        return [
            'run' => (array) $run,
            'items' => $items->map(fn ($row) => (array) $row)->all(),
            'diagnostics' => $diagnostics->map(fn ($row) => (array) $row)->all(),
        ];
    }

    private function priorityScore(object $job): int
    {
        $score = 0;
        $type = strtolower((string) $job->subject_type_code);

        if (in_array($type, ['practical', 'lab'], true)) {
            $score += 500;
        }
        if ((bool) $job->requires_lab) {
            $score += 250;
        }
        if ((int) ($job->required_capacity ?? 0) >= 100) {
            $score += 150;
        } elseif ((int) ($job->required_capacity ?? 0) >= 60) {
            $score += 100;
        }

        return $score;
    }

    private function normalizedRoomType(?string $roomType): ?string
    {
        if (!$roomType) {
            return null;
        }

        return match ($roomType) {
            'computer_lab', 'science_lab', 'lab' => 'lab',
            'lecture_theater', 'classroom' => 'classroom',
            'seminar_room' => 'seminar_hall',
            default => $roomType,
        };
    }

    private function scopeFilters(array $input): array
    {
        return collect([
            'campus_id' => $input['campus_id'] ?? null,
            'faculty_id' => $input['faculty_id'] ?? null,
            'department_id' => $input['department_id'] ?? null,
            'program_level_id' => $input['program_level_id'] ?? null,
            'program_id' => $input['program_id'] ?? null,
            'student_batch_id' => $input['student_batch_id'] ?? null,
            'section_id' => $input['section_id'] ?? null,
            'course_offering_ids' => $input['course_offering_ids'] ?? null,
        ])->filter(fn ($value) => $value !== null && $value !== '' && $value !== [])
            ->all();
    }

    private function scopeCode(array $input): string
    {
        foreach (['section_id', 'student_batch_id', 'program_id', 'department_id', 'faculty_id', 'campus_id'] as $field) {
            if (!empty($input[$field])) {
                return str_replace('_id', '', $field);
            }
        }

        return 'all_offerings';
    }

    private function summaryNote(array $stats): string
    {
        return sprintf(
            'Generated %d timetable entries. %d offering(s) fully scheduled; %d offering(s) require attention.',
            $stats['generated_entries'],
            $stats['scheduled_offerings'],
            $stats['unscheduled_offerings'],
        );
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;
        abort_if(!$tenantId, 422, 'Active tenant could not be resolved.');

        return (int) $tenantId;
    }
}
