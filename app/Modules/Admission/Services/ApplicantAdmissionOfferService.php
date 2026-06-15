<?php

namespace App\Modules\Admission\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplicantAdmissionOfferService
{
    public function myOffers(): array
    {
        $tenantId = $this->tenantId();
        $applicantId = $this->applicantId();

        $query = DB::table('admission_merit_list_applicants as mla')
            ->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'mla.admission_merit_list_id')
            ->leftJoin('admission_offer_fee_vouchers as v', 'v.admission_merit_list_applicant_id', '=', 'mla.id')
            ->leftJoin('admission_confirmations as c', 'c.admission_merit_list_applicant_id', '=', 'mla.id')
            ->where('mla.tenant_id', $tenantId)
            ->where('mla.applicant_id', $applicantId);

        if (Schema::hasColumn('admission_merit_lists', 'status_code')) {
            $query->whereIn('ml.status_code', ['published', 'generated']);
        }

        if (Schema::hasColumn('admission_merit_list_applicants', 'selection_status_code')) {
            $query->whereIn('mla.selection_status_code', ['selected', 'waiting', 'not_selected']);
        }

        $select = [
            'mla.id',
            'mla.admission_merit_list_id',
            'mla.applicant_id',
            'mla.admission_application_id',
            'mla.admission_applicant_merit_score_id',
            'mla.admission_session_id',
            'mla.admission_preference_group_id',
            'mla.offered_program_id',
            'mla.program_quota_seat_id',
            'mla.merit_position',
            'mla.preference_order',
            'mla.final_merit_score',
            'mla.is_eligible_for_merit',
            'mla.selection_status_code',
            'mla.offer_status_code',
            'mla.offer_generated_at',
            'mla.offer_expiry_at',
            'mla.accepted_at',
            'mla.rejected_at',
            'mla.expired_at',
            'mla.decision_remarks',
            'mla.moved_from_waiting_at',
            'v.id as voucher_id',
            'v.voucher_no',
            'v.amount as voucher_amount',
            'v.due_date as voucher_due_date',
            'v.status_code as voucher_status_code',
            'v.paid_amount as voucher_paid_amount',
            'v.paid_at as voucher_paid_at',

            'c.id as confirmation_id',
            'c.confirmation_no',
            'c.status_code as confirmation_status_code',
            'c.confirmed_at',
            'ml.list_no',
            'ml.title as merit_list_title',
            'ml.status_code as merit_list_status_code',
            'ml.list_type_code',
            'ml.published_at',
        ];

        if (Schema::hasTable('admission_sessions')) {
            $query->leftJoin('admission_sessions as s', 's.id', '=', 'mla.admission_session_id');
            $this->safeSelectLabel($select, 'admission_sessions', 's', 'admission_session_label');
        } else {
            $select[] = DB::raw('NULL as admission_session_label');
        }

        if (Schema::hasTable('admission_preference_groups')) {
            $query->leftJoin('admission_preference_groups as pg', 'pg.id', '=', 'mla.admission_preference_group_id');
            $this->safeSelectLabel($select, 'admission_preference_groups', 'pg', 'preference_group_label');
        } else {
            $select[] = DB::raw('NULL as preference_group_label');
        }

        if (Schema::hasTable('offered_programs')) {
            $query->leftJoin('offered_programs as op', 'op.id', '=', 'mla.offered_program_id');
            $this->safeSelectLabel($select, 'offered_programs', 'op', 'offered_program_label');
        } else {
            $select[] = DB::raw('NULL as offered_program_label');
        }

        if (Schema::hasTable('program_quota_seats')) {
            $query->leftJoin('program_quota_seats as qs', 'qs.id', '=', 'mla.program_quota_seat_id');
            $this->safeSelectLabel($select, 'program_quota_seats', 'qs', 'quota_label');
        } else {
            $select[] = DB::raw('NULL as quota_label');
        }

        $offers = $query
            ->select($select)
            ->orderByRaw("
                CASE 
                    WHEN mla.offer_status_code = 'offered' THEN 1
                    WHEN mla.offer_status_code = 'accepted' THEN 2
                    WHEN mla.selection_status_code = 'waiting' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('mla.merit_position')
            ->get();

        return [
            'offers' => $offers,
            'summary' => [
                'total' => $offers->count(),
                'offered' => $offers->where('offer_status_code', 'offered')->count(),
                'accepted' => $offers->where('offer_status_code', 'accepted')->count(),
                'rejected' => $offers->where('offer_status_code', 'rejected')->count(),
                'waiting' => $offers->where('selection_status_code', 'waiting')->count(),
            ],
        ];
    }

    public function accept(int $meritListApplicantId, array $data = []): array
    {
        return DB::transaction(function () use ($meritListApplicantId, $data) {
            $tenantId = $this->tenantId();
            $applicantId = $this->applicantId();

            $offer = $this->findOwnOffer($tenantId, $applicantId, $meritListApplicantId);

            if ($offer->selection_status_code !== 'selected') {
                abort(422, 'Only selected offers can be accepted.');
            }

            if ($offer->offer_status_code !== 'offered') {
                abort(422, 'Only offered admission can be accepted.');
            }

            if (!empty($offer->offer_expiry_at) && now()->greaterThan($offer->offer_expiry_at)) {
                abort(422, 'This offer has expired.');
            }

            DB::table('admission_merit_list_applicants')
                ->where('tenant_id', $tenantId)
                ->where('id', $offer->id)
                ->update($this->filterColumns('admission_merit_list_applicants', [
                    'offer_status_code' => 'accepted',
                    'accepted_at' => now(),
                    'decision_by' => auth()->id(),
                    'decision_remarks' => $data['remarks'] ?? 'Accepted by applicant.',
                    'updated_at' => now(),
                ]));

            $this->logMovement([
                'tenant_id' => $tenantId,
                'admission_merit_list_id' => $offer->admission_merit_list_id,
                'to_merit_list_applicant_id' => $offer->id,
                'to_applicant_id' => $offer->applicant_id,
                'movement_type_code' => 'offer_accepted',
                'to_selection_status_code' => 'selected',
                'from_offer_status_code' => $offer->offer_status_code,
                'to_offer_status_code' => 'accepted',
                'remarks' => $data['remarks'] ?? 'Accepted by applicant.',
                'movement_snapshot_json' => [
                    'source' => 'applicant_portal',
                    'merit_position' => $offer->merit_position,
                    'final_merit_score' => $offer->final_merit_score,
                ],
            ]);

            return [
                'accepted' => true,
                'offer_id' => $offer->id,
            ];
        });
    }

    public function reject(int $meritListApplicantId, array $data = []): array
    {
        return DB::transaction(function () use ($meritListApplicantId, $data) {
            $tenantId = $this->tenantId();
            $applicantId = $this->applicantId();

            $offer = $this->findOwnOffer($tenantId, $applicantId, $meritListApplicantId);

            if ($offer->selection_status_code !== 'selected') {
                abort(422, 'Only selected offers can be rejected.');
            }

            if ($offer->offer_status_code !== 'offered') {
                abort(422, 'Only offered admission can be rejected.');
            }

            $remarks = $data['remarks'] ?? 'Rejected by applicant.';

            DB::table('admission_merit_list_applicants')
                ->where('tenant_id', $tenantId)
                ->where('id', $offer->id)
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
                'admission_merit_list_id' => $offer->admission_merit_list_id,
                'from_merit_list_applicant_id' => $offer->id,
                'from_applicant_id' => $offer->applicant_id,
                'movement_type_code' => 'offer_rejected',
                'from_selection_status_code' => 'selected',
                'to_selection_status_code' => 'not_selected',
                'from_offer_status_code' => $offer->offer_status_code,
                'to_offer_status_code' => 'rejected',
                'remarks' => $remarks,
                'movement_snapshot_json' => [
                    'source' => 'applicant_portal',
                    'merit_position' => $offer->merit_position,
                    'final_merit_score' => $offer->final_merit_score,
                ],
            ]);

            $promoted = $this->promoteNextWaitingApplicant(
                $tenantId,
                (int) $offer->admission_merit_list_id,
                (int) $offer->id,
                (int) $offer->applicant_id
            );

            $this->refreshListCounters($tenantId, (int) $offer->admission_merit_list_id);

            return [
                'rejected' => true,
                'offer_id' => $offer->id,
                'promoted_waiting_applicant' => $promoted,
            ];
        });
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
                'decision_remarks' => 'Promoted from waiting list after applicant rejected offer.',
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
            'remarks' => 'Next waiting applicant promoted after rejection.',
            'movement_snapshot_json' => [
                'source' => 'applicant_portal',
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

    private function findOwnOffer(int $tenantId, int $applicantId, int $meritListApplicantId): object
    {
        $offer = DB::table('admission_merit_list_applicants')
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicantId)
            ->where('id', $meritListApplicantId)
            ->first();

        if (!$offer) {
            abort(404, 'Admission offer not found.');
        }

        return $offer;
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

    private function safeSelectLabel(array &$select, string $table, string $alias, string $as): void
    {
        if (Schema::hasColumn($table, 'code') && Schema::hasColumn($table, 'title')) {
            $select[] = DB::raw("CONCAT({$alias}.code, ' - ', {$alias}.title) as {$as}");
            return;
        }

        if (Schema::hasColumn($table, 'code') && Schema::hasColumn($table, 'name')) {
            $select[] = DB::raw("CONCAT({$alias}.code, ' - ', {$alias}.name) as {$as}");
            return;
        }

        if (Schema::hasColumn($table, 'code') && Schema::hasColumn($table, 'session_name')) {
            $select[] = DB::raw("CONCAT({$alias}.code, ' - ', {$alias}.session_name) as {$as}");
            return;
        }

        if (Schema::hasColumn($table, 'code') && Schema::hasColumn($table, 'program_name')) {
            $select[] = DB::raw("CONCAT({$alias}.code, ' - ', {$alias}.program_name) as {$as}");
            return;
        }

        if (Schema::hasColumn($table, 'title')) {
            $select[] = DB::raw("{$alias}.title as {$as}");
            return;
        }

        if (Schema::hasColumn($table, 'name')) {
            $select[] = DB::raw("{$alias}.name as {$as}");
            return;
        }

        if (Schema::hasColumn($table, 'session_name')) {
            $select[] = DB::raw("{$alias}.session_name as {$as}");
            return;
        }

        if (Schema::hasColumn($table, 'program_name')) {
            $select[] = DB::raw("{$alias}.program_name as {$as}");
            return;
        }

        if (Schema::hasColumn($table, 'code')) {
            $select[] = DB::raw("{$alias}.code as {$as}");
            return;
        }

        $select[] = DB::raw("NULL as {$as}");
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

    private function applicantId(): int
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Applicant login is required.');
        }

        if (!empty($user->applicant_id)) {
            return (int) $user->applicant_id;
        }

        if (Schema::hasTable('applicants')) {
            $query = DB::table('applicants')
                ->where('tenant_id', $this->tenantId());

            if (Schema::hasColumn('applicants', 'user_id')) {
                $record = (clone $query)->where('user_id', $user->id)->first();

                if ($record) {
                    return (int) $record->id;
                }
            }

            if (Schema::hasColumn('applicants', 'email') && !empty($user->email)) {
                $record = (clone $query)->where('email', $user->email)->first();

                if ($record) {
                    return (int) $record->id;
                }
            }
        }

        abort(403, 'Applicant context is required.');
    }
}