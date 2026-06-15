<?php

namespace App\Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionWaitingListController extends Controller
{
    public function generate(Request $request, int $meritListId): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        if (!Schema::hasTable('admission_merit_lists') || !Schema::hasTable('admission_merit_list_applicants')) {
            return response()->json([
                'message' => 'Merit list tables not found.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $tenantId, $meritListId) {
            $meritList = DB::table('admission_merit_lists')
                ->where('id', $meritListId)
                ->when(
                    $tenantId && Schema::hasColumn('admission_merit_lists', 'tenant_id'),
                    fn ($q) => $q->where('tenant_id', $tenantId)
                )
                ->first();

            if (!$meritList) {
                return response()->json([
                    'message' => 'Merit list not found.',
                ], 404);
            }

            $seatLimit = $this->resolveSeatLimit($meritList);

            if ($seatLimit <= 0) {
                return response()->json([
                    'message' => 'Seat limit is not defined for this merit list.',
                ], 422);
            }

            $rows = DB::table('admission_merit_list_applicants')
                ->where('admission_merit_list_id', $meritListId)
                ->when(
                    $tenantId && Schema::hasColumn('admission_merit_list_applicants', 'tenant_id'),
                    fn ($q) => $q->where('tenant_id', $tenantId)
                )
                ->where(function ($q) {
                    if (Schema::hasColumn('admission_merit_list_applicants', 'is_eligible_for_merit')) {
                        $q->where('is_eligible_for_merit', true)
                          ->orWhereNull('is_eligible_for_merit');
                    }
                })
                ->orderBy('merit_position')
                ->orderByDesc('final_merit_score')
                ->get();

            $selectedCount = 0;
            $waitingPosition = 0;

            foreach ($rows as $row) {
                $currentSelection = $row->selection_status_code ?? null;
                $currentOffer = $row->offer_status_code ?? null;

                /*
                 * Do not disturb already confirmed / accepted records.
                 */
                if (
                    ($row->admission_confirmation_status_code ?? null) === 'confirmed'
                    || in_array($currentOffer, ['accepted'], true)
                ) {
                    $selectedCount++;
                    continue;
                }

                if ($selectedCount < $seatLimit) {
                    $selectedCount++;

                    DB::table('admission_merit_list_applicants')
                        ->where('id', $row->id)
                        ->update($this->filterColumns('admission_merit_list_applicants', [
                            'selection_status_code' => 'selected',
                            'waiting_position' => null,
                            'waiting_status_code' => null,
                            'updated_at' => now(),
                        ]));

                    continue;
                }

                $waitingPosition++;

                DB::table('admission_merit_list_applicants')
                    ->where('id', $row->id)
                    ->update($this->filterColumns('admission_merit_list_applicants', [
                        'selection_status_code' => 'waiting',
                        'waiting_position' => $waitingPosition,
                        'waiting_status_code' => 'active',
                        'offer_status_code' => null,
                        'updated_at' => now(),
                    ]));

                $this->logMovement([
                    'tenant_id' => $tenantId,
                    'admission_merit_list_id' => $meritListId,
                    'to_merit_list_applicant_id' => $row->id,
                    'to_applicant_id' => $row->applicant_id ?? null,
                    'movement_type_code' => 'waiting_generated',
                    'from_selection_status_code' => $currentSelection,
                    'to_selection_status_code' => 'waiting',
                    'from_offer_status_code' => $currentOffer,
                    'to_offer_status_code' => null,
                    'waiting_position' => $waitingPosition,
                    'remarks' => 'Waiting list generated from merit list.',
                    'created_by' => $request->user()?->id,
                ]);
            }

            $this->updateMeritListWaitingCount($meritListId, $waitingPosition);

            return response()->json([
                'data' => [
                    'seat_limit' => $seatLimit,
                    'total_candidates' => $rows->count(),
                    'selected_count' => $selectedCount,
                    'waiting_count' => $waitingPosition,
                ],
                'message' => $waitingPosition > 0
                    ? 'Waiting list generated successfully.'
                    : 'No waiting candidates found because candidates are within available seats.',
            ]);
        });
    }

    public function promoteNext(Request $request, int $meritListId): JsonResponse
    {
        $request->validate([
            'vacated_merit_list_applicant_id' => ['nullable', 'integer'],
            'remarks' => ['nullable', 'string'],
        ]);

        $tenantId = $request->user()?->tenant_id;

        if (!Schema::hasTable('admission_merit_lists') || !Schema::hasTable('admission_merit_list_applicants')) {
            return response()->json([
                'message' => 'Merit list tables not found.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $tenantId, $meritListId) {
            $meritList = DB::table('admission_merit_lists')
                ->where('id', $meritListId)
                ->when(
                    $tenantId && Schema::hasColumn('admission_merit_lists', 'tenant_id'),
                    fn ($q) => $q->where('tenant_id', $tenantId)
                )
                ->first();

            if (!$meritList) {
                return response()->json([
                    'message' => 'Merit list not found.',
                ], 404);
            }

            $vacated = null;

            if ($request->filled('vacated_merit_list_applicant_id')) {
                $vacated = DB::table('admission_merit_list_applicants')
                    ->where('id', (int) $request->input('vacated_merit_list_applicant_id'))
                    ->where('admission_merit_list_id', $meritListId)
                    ->first();
            }

            $nextWaiting = DB::table('admission_merit_list_applicants')
                ->where('admission_merit_list_id', $meritListId)
                ->when(
                    $tenantId && Schema::hasColumn('admission_merit_list_applicants', 'tenant_id'),
                    fn ($q) => $q->where('tenant_id', $tenantId)
                )
                ->where('selection_status_code', 'waiting')
                ->where(function ($q) {
                    if (Schema::hasColumn('admission_merit_list_applicants', 'waiting_status_code')) {
                        $q->where('waiting_status_code', 'active')
                          ->orWhereNull('waiting_status_code');
                    }
                })
                ->orderByRaw('COALESCE(waiting_position, 999999) ASC')
                ->orderBy('merit_position')
                ->first();

            if (!$nextWaiting) {
                return response()->json([
                    'message' => 'No active waiting candidate found.',
                ], 422);
            }

            DB::table('admission_merit_list_applicants')
                ->where('id', $nextWaiting->id)
                ->update($this->filterColumns('admission_merit_list_applicants', [
                    'selection_status_code' => 'selected',
                    'offer_status_code' => 'offered',
                    'offer_generated_at' => now(),
                    'waiting_status_code' => 'promoted',
                    'promoted_from_waiting_at' => now(),
                    'promoted_from_waiting_by' => $request->user()?->id,
                    'updated_at' => now(),
                ]));

            $this->logMovement([
                'tenant_id' => $tenantId,
                'admission_merit_list_id' => $meritListId,
                'from_merit_list_applicant_id' => $vacated?->id,
                'to_merit_list_applicant_id' => $nextWaiting->id,
                'from_applicant_id' => $vacated?->applicant_id,
                'to_applicant_id' => $nextWaiting->applicant_id,
                'movement_type_code' => 'waiting_promoted',
                'from_selection_status_code' => $vacated?->selection_status_code,
                'to_selection_status_code' => 'selected',
                'from_offer_status_code' => $vacated?->offer_status_code,
                'to_offer_status_code' => 'offered',
                'waiting_position' => $nextWaiting->waiting_position ?? null,
                'remarks' => $request->input('remarks', 'Next waiting candidate promoted.'),
                'created_by' => $request->user()?->id,
            ]);

            $this->resequenceWaitingPositions($tenantId, $meritListId);

            $promoted = DB::table('admission_merit_list_applicants')
                ->where('id', $nextWaiting->id)
                ->first();

            return response()->json([
                'data' => $promoted,
                'message' => 'Next waiting candidate promoted successfully.',
            ]);
        });
    }

    public function movements(Request $request, int $meritListId): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        if (!Schema::hasTable('admission_waiting_list_movements')) {
            return response()->json([
                'data' => [],
                'message' => 'Waiting list movement table not found.',
            ]);
        }

        $items = DB::table('admission_waiting_list_movements as m')
            ->leftJoin('applicants as from_app', 'from_app.id', '=', 'm.from_applicant_id')
            ->leftJoin('applicants as to_app', 'to_app.id', '=', 'm.to_applicant_id')
            ->where('m.admission_merit_list_id', $meritListId)
            ->when(
                $tenantId && Schema::hasColumn('admission_waiting_list_movements', 'tenant_id'),
                fn ($q) => $q->where('m.tenant_id', $tenantId)
            )
            ->select([
                'm.*',
                'from_app.applicant_no as from_applicant_no',
                DB::raw("TRIM(CONCAT(COALESCE(from_app.first_name, ''), ' ', COALESCE(from_app.last_name, ''))) as from_applicant_name"),
                'to_app.applicant_no as to_applicant_no',
                DB::raw("TRIM(CONCAT(COALESCE(to_app.first_name, ''), ' ', COALESCE(to_app.last_name, ''))) as to_applicant_name"),
            ])
            ->orderByDesc('m.id')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'data' => $items,
            'message' => 'Waiting list movements fetched successfully.',
        ]);
    }

    private function resolveSeatLimit(object $meritList): int
    {
        foreach (['available_seats', 'seats', 'total_seats'] as $column) {
            if (Schema::hasColumn('admission_merit_lists', $column) && isset($meritList->{$column})) {
                $value = (int) $meritList->{$column};

                if ($value > 0) {
                    return $value;
                }
            }
        }

        if (
            Schema::hasTable('program_quota_seats')
            && Schema::hasColumn('admission_merit_lists', 'program_quota_seat_id')
            && !empty($meritList->program_quota_seat_id)
        ) {
            $quota = DB::table('program_quota_seats')
                ->where('id', $meritList->program_quota_seat_id)
                ->first();

            if ($quota) {
                foreach (['total_seats', 'seat_count', 'seats', 'allocated_seats', 'quota_seats'] as $column) {
                    if (Schema::hasColumn('program_quota_seats', $column) && isset($quota->{$column})) {
                        $value = (int) $quota->{$column};

                        if ($value > 0) {
                            return $value;
                        }
                    }
                }
            }
        }

        return 0;
    }

    private function resequenceWaitingPositions(?int $tenantId, int $meritListId): void
    {
        if (!Schema::hasColumn('admission_merit_list_applicants', 'waiting_position')) {
            return;
        }

        $waitingRows = DB::table('admission_merit_list_applicants')
            ->where('admission_merit_list_id', $meritListId)
            ->when(
                $tenantId && Schema::hasColumn('admission_merit_list_applicants', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            )
            ->where('selection_status_code', 'waiting')
            ->where(function ($q) {
                if (Schema::hasColumn('admission_merit_list_applicants', 'waiting_status_code')) {
                    $q->where('waiting_status_code', 'active')
                      ->orWhereNull('waiting_status_code');
                }
            })
            ->orderByRaw('COALESCE(waiting_position, 999999) ASC')
            ->orderBy('merit_position')
            ->get();

        $position = 0;

        foreach ($waitingRows as $row) {
            $position++;

            DB::table('admission_merit_list_applicants')
                ->where('id', $row->id)
                ->update($this->filterColumns('admission_merit_list_applicants', [
                    'waiting_position' => $position,
                    'updated_at' => now(),
                ]));
        }

        $this->updateMeritListWaitingCount($meritListId, $position);
    }

    private function updateMeritListWaitingCount(int $meritListId, int $waitingCount): void
    {
        if (!Schema::hasTable('admission_merit_lists')) {
            return;
        }

        DB::table('admission_merit_lists')
            ->where('id', $meritListId)
            ->update($this->filterColumns('admission_merit_lists', [
                'waiting_candidates' => $waitingCount,
                'updated_at' => now(),
            ]));
    }

    private function logMovement(array $payload): void
    {
        if (!Schema::hasTable('admission_waiting_list_movements')) {
            return;
        }

        DB::table('admission_waiting_list_movements')
            ->insert($this->filterColumns('admission_waiting_list_movements', array_merge($payload, [
                'created_at' => now(),
                'updated_at' => now(),
            ])));
    }

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->toArray();
    }
}