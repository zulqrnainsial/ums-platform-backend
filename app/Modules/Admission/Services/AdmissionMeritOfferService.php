<?php

namespace App\Modules\Admission\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionMeritOfferService
{
    public function generateOffers(int $meritListId, array $data = []): array
    {
        return DB::transaction(function () use ($meritListId, $data) {
            $tenantId = $this->tenantId();

            $list = $this->findList($tenantId, $meritListId);

            $expiryAt = $data['offer_expiry_at'] ?? null;
            $remarks = $data['remarks'] ?? 'Offer generated from merit list.';

            $selectedApplicants = DB::table('admission_merit_list_applicants')
                ->where('tenant_id', $tenantId)
                ->where('admission_merit_list_id', $meritListId)
                ->where('selection_status_code', 'selected')
                ->whereIn('offer_status_code', ['pending'])
                ->orderBy('merit_position')
                ->get();

            $generated = 0;

            foreach ($selectedApplicants as $row) {
                DB::table('admission_merit_list_applicants')
                    ->where('tenant_id', $tenantId)
                    ->where('id', $row->id)
                    ->update($this->filterColumns('admission_merit_list_applicants', [
                        'offer_status_code' => 'offered',
                        'offer_generated_at' => now(),
                        'offer_expiry_at' => $expiryAt,
                        'decision_remarks' => $remarks,
                        'updated_at' => now(),
                    ]));

                $this->logMovement([
                    'tenant_id' => $tenantId,
                    'admission_merit_list_id' => $meritListId,
                    'to_merit_list_applicant_id' => $row->id,
                    'to_applicant_id' => $row->applicant_id,
                    'movement_type_code' => 'offer_generated',
                    'to_selection_status_code' => 'selected',
                    'from_offer_status_code' => $row->offer_status_code,
                    'to_offer_status_code' => 'offered',
                    'remarks' => $remarks,
                    'movement_snapshot_json' => [
                        'list_no' => $list->list_no ?? null,
                        'merit_position' => $row->merit_position ?? null,
                        'final_merit_score' => $row->final_merit_score ?? null,
                    ],
                ]);

                $generated++;
            }

            return [
                'generated' => $generated,
                'message' => $generated . ' offers generated.',
            ];
        });
    }

    public function acceptOffer(int $meritListApplicantId, array $data = []): array
    {
        return DB::transaction(function () use ($meritListApplicantId, $data) {
            $tenantId = $this->tenantId();

            $row = $this->findListApplicant($tenantId, $meritListApplicantId);

            if ($row->selection_status_code !== 'selected') {
                abort(422, 'Only selected applicants can accept an offer.');
            }

            if (!in_array($row->offer_status_code, ['offered', 'pending'], true)) {
                abort(422, 'Only pending/offered offer can be accepted.');
            }

            DB::table('admission_merit_list_applicants')
                ->where('tenant_id', $tenantId)
                ->where('id', $row->id)
                ->update($this->filterColumns('admission_merit_list_applicants', [
                    'offer_status_code' => 'accepted',
                    'accepted_at' => now(),
                    'decision_by' => auth()->id(),
                    'decision_remarks' => $data['remarks'] ?? 'Offer accepted.',
                    'updated_at' => now(),
                ]));

            $this->logMovement([
                'tenant_id' => $tenantId,
                'admission_merit_list_id' => $row->admission_merit_list_id,
                'to_merit_list_applicant_id' => $row->id,
                'to_applicant_id' => $row->applicant_id,
                'movement_type_code' => 'offer_accepted',
                'to_selection_status_code' => $row->selection_status_code,
                'from_offer_status_code' => $row->offer_status_code,
                'to_offer_status_code' => 'accepted',
                'remarks' => $data['remarks'] ?? 'Offer accepted.',
                'movement_snapshot_json' => [
                    'merit_position' => $row->merit_position,
                    'final_merit_score' => $row->final_merit_score,
                ],
            ]);

            return [
                'accepted' => true,
                'promoted_waiting_applicant' => null,
            ];
        });
    }

    public function rejectOffer(int $meritListApplicantId, array $data = []): array
    {
        return DB::transaction(function () use ($meritListApplicantId, $data) {
            $tenantId = $this->tenantId();

            $row = $this->findListApplicant($tenantId, $meritListApplicantId);

            if ($row->selection_status_code !== 'selected') {
                abort(422, 'Only selected applicants can reject an offer.');
            }

            if (!in_array($row->offer_status_code, ['offered', 'pending'], true)) {
                abort(422, 'Only pending/offered offer can be rejected.');
            }

            $remarks = $data['remarks'] ?? 'Offer rejected.';

            DB::table('admission_merit_list_applicants')
                ->where('tenant_id', $tenantId)
                ->where('id', $row->id)
                ->update($this->filterColumns('admission_merit_list_applicants', [
                    'selection_status_code' => 'not_selected',
                    'offer_status_code' => 'rejected',
                    'rejected_at' => now(),
                    'decision_by' => auth()->id(),
                    'decision_remarks' => $remarks,
                    'updated_at' => now(),
                ]));

            $this->logMovement([
                'tenant_id' => $tenantId,
                'admission_merit_list_id' => $row->admission_merit_list_id,
                'from_merit_list_applicant_id' => $row->id,
                'from_applicant_id' => $row->applicant_id,
                'movement_type_code' => 'offer_rejected',
                'from_selection_status_code' => 'selected',
                'to_selection_status_code' => 'not_selected',
                'from_offer_status_code' => $row->offer_status_code,
                'to_offer_status_code' => 'rejected',
                'remarks' => $remarks,
                'movement_snapshot_json' => [
                    'merit_position' => $row->merit_position,
                    'final_merit_score' => $row->final_merit_score,
                ],
            ]);

            $promoted = null;

            if (($data['promote_waiting'] ?? true) === true) {
                $promoted = $this->promoteNextWaitingApplicant(
                    $tenantId,
                    (int) $row->admission_merit_list_id,
                    (int) $row->id,
                    (int) $row->applicant_id
                );
            }

            $this->refreshListCounters($tenantId, (int) $row->admission_merit_list_id);

            return [
                'rejected' => true,
                'promoted_waiting_applicant' => $promoted,
            ];
        });
    }

    public function expireOffer(int $meritListApplicantId, array $data = []): array
    {
        return DB::transaction(function () use ($meritListApplicantId, $data) {
            $tenantId = $this->tenantId();

            $row = $this->findListApplicant($tenantId, $meritListApplicantId);

            if ($row->selection_status_code !== 'selected') {
                abort(422, 'Only selected applicants can be expired.');
            }

            if (!in_array($row->offer_status_code, ['offered', 'pending'], true)) {
                abort(422, 'Only pending/offered offer can be expired.');
            }

            $remarks = $data['remarks'] ?? 'Offer expired.';

            DB::table('admission_merit_list_applicants')
                ->where('tenant_id', $tenantId)
                ->where('id', $row->id)
                ->update($this->filterColumns('admission_merit_list_applicants', [
                    'selection_status_code' => 'not_selected',
                    'offer_status_code' => 'expired',
                    'expired_at' => now(),
                    'decision_by' => auth()->id(),
                    'decision_remarks' => $remarks,
                    'updated_at' => now(),
                ]));

            $this->logMovement([
                'tenant_id' => $tenantId,
                'admission_merit_list_id' => $row->admission_merit_list_id,
                'from_merit_list_applicant_id' => $row->id,
                'from_applicant_id' => $row->applicant_id,
                'movement_type_code' => 'offer_expired',
                'from_selection_status_code' => 'selected',
                'to_selection_status_code' => 'not_selected',
                'from_offer_status_code' => $row->offer_status_code,
                'to_offer_status_code' => 'expired',
                'remarks' => $remarks,
                'movement_snapshot_json' => [
                    'merit_position' => $row->merit_position,
                    'final_merit_score' => $row->final_merit_score,
                ],
            ]);

            $promoted = null;

            if (($data['promote_waiting'] ?? true) === true) {
                $promoted = $this->promoteNextWaitingApplicant(
                    $tenantId,
                    (int) $row->admission_merit_list_id,
                    (int) $row->id,
                    (int) $row->applicant_id
                );
            }

            $this->refreshListCounters($tenantId, (int) $row->admission_merit_list_id);

            return [
                'expired' => true,
                'promoted_waiting_applicant' => $promoted,
            ];
        });
    }

    public function movements(int $meritListId): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('admission_merit_offer_movements as mov')
            ->where('mov.tenant_id', $tenantId)
            ->where('mov.admission_merit_list_id', $meritListId)
            ->orderByDesc('mov.id');

        return $query->paginate(50)->toArray();
    }

    private function promoteNextWaitingApplicant(
        int $tenantId,
        int $meritListId,
        int $sourceListApplicantId,
        int $sourceApplicantId
    ): ?array {
        $waiting = DB::table('admission_merit_list_applicants')
            ->where('tenant_id', $tenantId)
            ->where('admission_merit_list_id', $meritListId)
            ->where('selection_status_code', 'waiting')
            ->where('offer_status_code', 'pending')
            ->orderBy('merit_position')
            ->first();

        if (!$waiting) {
            return null;
        }

        DB::table('admission_merit_list_applicants')
            ->where('tenant_id', $tenantId)
            ->where('id', $waiting->id)
            ->update($this->filterColumns('admission_merit_list_applicants', [
                'selection_status_code' => 'selected',
                'offer_status_code' => 'offered',
                'offer_generated_at' => now(),
                'moved_from_waiting_at' => now(),
                'movement_source_applicant_id' => $sourceApplicantId,
                'decision_remarks' => 'Promoted from waiting list after seat became vacant.',
                'updated_at' => now(),
            ]));

        $this->logMovement([
            'tenant_id' => $tenantId,
            'admission_merit_list_id' => $meritListId,
            'from_merit_list_applicant_id' => $sourceListApplicantId,
            'from_applicant_id' => $sourceApplicantId,
            'to_merit_list_applicant_id' => $waiting->id,
            'to_applicant_id' => $waiting->applicant_id,
            'movement_type_code' => 'waiting_promoted',
            'from_selection_status_code' => 'waiting',
            'to_selection_status_code' => 'selected',
            'from_offer_status_code' => 'pending',
            'to_offer_status_code' => 'offered',
            'remarks' => 'Next waiting applicant promoted to selected.',
            'movement_snapshot_json' => [
                'promoted_merit_position' => $waiting->merit_position,
                'promoted_final_merit_score' => $waiting->final_merit_score,
                'vacated_by_applicant_id' => $sourceApplicantId,
            ],
        ]);

        return [
            'id' => $waiting->id,
            'applicant_id' => $waiting->applicant_id,
            'merit_position' => $waiting->merit_position,
            'final_merit_score' => $waiting->final_merit_score,
        ];
    }

    private function refreshListCounters(int $tenantId, int $meritListId): void
    {
        $selected = DB::table('admission_merit_list_applicants')
            ->where('tenant_id', $tenantId)
            ->where('admission_merit_list_id', $meritListId)
            ->where('selection_status_code', 'selected')
            ->count();

        $waiting = DB::table('admission_merit_list_applicants')
            ->where('tenant_id', $tenantId)
            ->where('admission_merit_list_id', $meritListId)
            ->where('selection_status_code', 'waiting')
            ->count();

        $total = DB::table('admission_merit_list_applicants')
            ->where('tenant_id', $tenantId)
            ->where('admission_merit_list_id', $meritListId)
            ->count();

        DB::table('admission_merit_lists')
            ->where('tenant_id', $tenantId)
            ->where('id', $meritListId)
            ->update($this->filterColumns('admission_merit_lists', [
                'selected_candidates' => $selected,
                'waiting_candidates' => $waiting,
                'total_candidates' => $total,
                'updated_at' => now(),
            ]));
    }

    private function findList(int $tenantId, int $meritListId): object
    {
        $list = DB::table('admission_merit_lists')
            ->where('tenant_id', $tenantId)
            ->where('id', $meritListId)
            ->first();

        if (!$list) {
            abort(404, 'Merit list not found.');
        }

        return $list;
    }

    private function findListApplicant(int $tenantId, int $meritListApplicantId): object
    {
        $row = DB::table('admission_merit_list_applicants')
            ->where('tenant_id', $tenantId)
            ->where('id', $meritListApplicantId)
            ->first();

        if (!$row) {
            abort(404, 'Merit list applicant not found.');
        }

        return $row;
    }

    private function logMovement(array $data): void
    {
        if (!Schema::hasTable('admission_merit_offer_movements')) {
            return;
        }

        $payload = $this->filterColumns('admission_merit_offer_movements', [
            ...$data,
            'movement_snapshot_json' => isset($data['movement_snapshot_json'])
                ? json_encode($data['movement_snapshot_json'])
                : null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('admission_merit_offer_movements')->insert($payload);
    }

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->toArray();
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $tenantId;
    }
}