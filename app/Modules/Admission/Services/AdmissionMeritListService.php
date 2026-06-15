<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\AdmissionMeritList;
use App\Modules\Admission\Models\AdmissionMeritListApplicant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionMeritListService
{
    public function list(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = AdmissionMeritList::query()
            ->from('admission_merit_lists as ml')
            ->where('ml.tenant_id', $tenantId);

        $select = [
            'ml.id',
            'ml.tenant_id',
            'ml.admission_session_id',
            'ml.admission_preference_group_id',
            'ml.offered_program_id',
            'ml.program_quota_seat_id',
            'ml.admission_merit_formula_id',
            'ml.list_no',
            'ml.title',
            'ml.status_code',
            'ml.list_type_code',
            'ml.total_candidates',
            'ml.selected_candidates',
            'ml.waiting_candidates',
            'ml.available_seats',
            'ml.highest_merit_score',
            'ml.lowest_merit_score',
            'ml.generated_at',
            'ml.published_at',
        ];

        if (Schema::hasTable('admission_sessions')) {
            $query->leftJoin('admission_sessions as s', 's.id', '=', 'ml.admission_session_id');
            $this->safeSelectLabel($select, 'admission_sessions', 's', 'admission_session_label');
        } else {
            $select[] = DB::raw('NULL as admission_session_label');
        }

        if (Schema::hasTable('admission_preference_groups')) {
            $query->leftJoin('admission_preference_groups as pg', 'pg.id', '=', 'ml.admission_preference_group_id');
            $this->safeSelectLabel($select, 'admission_preference_groups', 'pg', 'preference_group_label');
        } else {
            $select[] = DB::raw('NULL as preference_group_label');
        }

        if (Schema::hasTable('offered_programs')) {
            $query->leftJoin('offered_programs as op', 'op.id', '=', 'ml.offered_program_id');
            $this->safeSelectLabel($select, 'offered_programs', 'op', 'offered_program_label');
        } else {
            $select[] = DB::raw('NULL as offered_program_label');
        }

        if (Schema::hasTable('program_quota_seats')) {
            $query->leftJoin('program_quota_seats as qs', 'qs.id', '=', 'ml.program_quota_seat_id');
            $this->safeSelectLabel($select, 'program_quota_seats', 'qs', 'quota_label');
        } else {
            $select[] = DB::raw('NULL as quota_label');
        }

        if (Schema::hasTable('admission_merit_formulas')) {
            $query->leftJoin('admission_merit_formulas as mf', 'mf.id', '=', 'ml.admission_merit_formula_id');
            $this->safeSelectLabel($select, 'admission_merit_formulas', 'mf', 'formula_label');
        } else {
            $select[] = DB::raw('NULL as formula_label');
        }

        $query->select($select);

        if (!empty($filters['admission_session_id'])) {
            $query->where('ml.admission_session_id', $filters['admission_session_id']);
        }

        if (!empty($filters['admission_preference_group_id'])) {
            $query->where('ml.admission_preference_group_id', $filters['admission_preference_group_id']);
        }

        if (!empty($filters['offered_program_id'])) {
            $query->where('ml.offered_program_id', $filters['offered_program_id']);
        }

        if (!empty($filters['program_quota_seat_id'])) {
            $query->where('ml.program_quota_seat_id', $filters['program_quota_seat_id']);
        }

        if (!empty($filters['status_code'])) {
            $query->where('ml.status_code', $filters['status_code']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('ml.list_no', 'like', "%{$search}%")
                    ->orWhere('ml.title', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('ml.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function detail(int $meritListId): array
    {
        $tenantId = $this->tenantId();

        $list = AdmissionMeritList::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $meritListId)
            ->firstOrFail();

        $applicantQuery = AdmissionMeritListApplicant::query()
            ->from('admission_merit_list_applicants as mla')
            ->leftJoin('admission_offer_fee_vouchers as v', 'v.admission_merit_list_applicant_id', '=', 'mla.id')
            ->leftJoin('admission_confirmations as c', 'c.admission_merit_list_applicant_id', '=', 'mla.id')
            ->where('mla.tenant_id', $tenantId)
            ->where('mla.admission_merit_list_id', $list->id);

        $select = [
            'mla.id',
            'mla.applicant_id',
            'mla.admission_application_id',
            'mla.admission_applicant_merit_score_id',
            'mla.merit_position',
            'mla.preference_order',
            'mla.final_merit_score',
            'mla.is_eligible_for_merit',
            'mla.selection_status_code',
            'mla.offer_status_code',
            'mla.offer_generated_at',
            'mla.offer_expiry_at',
            'mla.selection_reason_json',
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
        ];

        if (Schema::hasTable('applicants')) {
            $applicantQuery->leftJoin('applicants as app', 'app.id', '=', 'mla.applicant_id');

            if (Schema::hasColumn('applicants', 'applicant_no')) {
                $select[] = 'app.applicant_no';
            } else {
                $select[] = DB::raw('NULL as applicant_no');
            }

            $this->safeSelectLabel($select, 'applicants', 'app', 'applicant_label');

            if (Schema::hasColumn('applicants', 'cnic_bform')) {
                $select[] = 'app.cnic_bform';
            } else {
                $select[] = DB::raw('NULL as cnic_bform');
            }

            if (Schema::hasColumn('applicants', 'phone')) {
                $select[] = 'app.phone';
            } else {
                $select[] = DB::raw('NULL as phone');
            }
        } else {
            $select[] = DB::raw('NULL as applicant_no');
            $select[] = DB::raw('NULL as applicant_label');
            $select[] = DB::raw('NULL as cnic_bform');
            $select[] = DB::raw('NULL as phone');
        }

        $applicants = $applicantQuery
            ->select($select)
            ->orderBy('mla.merit_position')
            ->paginate(100)
            ->toArray();

        return [
            'list' => $list,
            'applicants' => $applicants,
        ];
    }

    public function generate(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $this->tenantId();

            $availableSeats = $this->resolveAvailableSeats($data);

            $scores = $this->eligibleScoresQuery($tenantId, $data)
                ->orderByDesc('ms.final_merit_score')
                ->orderBy('ms.id')
                ->get();

            if ($scores->isEmpty()) {
                abort(422, 'No calculated eligible merit scores found for selected filters.');
            }

            $listNo = $data['list_no'] ?? $this->nextListNo($tenantId, $data);

            $title = $data['title'] ?? $this->buildListTitle($listNo, $data);

            $list = AdmissionMeritList::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'admission_session_id' => $data['admission_session_id'] ?? null,
                    'admission_preference_group_id' => $data['admission_preference_group_id'] ?? null,
                    'offered_program_id' => $data['offered_program_id'] ?? null,
                    'program_quota_seat_id' => $data['program_quota_seat_id'] ?? null,
                    'list_no' => $listNo,
                ],
                [
                    'admission_merit_formula_id' => $data['admission_merit_formula_id'] ?? null,
                    'title' => $title,
                    'status_code' => 'generated',
                    'list_type_code' => $data['list_type_code'] ?? 'merit',
                    'available_seats' => $availableSeats,
                    'generation_filters_json' => $data,
                    'generated_at' => now(),
                    'generated_by' => auth()->id(),
                ]
            );

            AdmissionMeritListApplicant::query()
                ->where('tenant_id', $tenantId)
                ->where('admission_merit_list_id', $list->id)
                ->delete();

            $position = 1;
            $selected = 0;
            $waiting = 0;

            $highestScore = null;
            $lowestScore = null;

            foreach ($scores as $score) {
                $selectionStatus = 'waiting';

                if ($selected < $availableSeats) {
                    $selectionStatus = 'selected';
                    $selected++;
                } else {
                    $waiting++;
                }

                if ($highestScore === null) {
                    $highestScore = (float) $score->final_merit_score;
                }

                $lowestScore = (float) $score->final_merit_score;

                AdmissionMeritListApplicant::create([
                    'tenant_id' => $tenantId,
                    'admission_merit_list_id' => $list->id,

                    'applicant_id' => $score->applicant_id,
                    'admission_application_id' => $score->admission_application_id,
                    'admission_applicant_merit_score_id' => $score->id,

                    'admission_session_id' => $score->admission_session_id,
                    'admission_preference_group_id' => $score->admission_preference_group_id,
                    'offered_program_id' => $score->offered_program_id,
                    'program_quota_seat_id' => $score->program_quota_seat_id,

                    'merit_position' => $position,
                    'preference_order' => $this->resolvePreferenceOrder($score),

                    'final_merit_score' => $score->final_merit_score,
                    'is_eligible_for_merit' => $score->is_eligible_for_merit,

                    'selection_status_code' => $selectionStatus,
                    'offer_status_code' => 'pending',

                    'score_snapshot_json' => [
                        'merit_score_id' => $score->id,
                        'final_merit_score' => $score->final_merit_score,
                        'total_weighted_score' => $score->total_weighted_score,
                        'bonus_score' => $score->bonus_score,
                        'deduction_score' => $score->deduction_score,
                    ],

                    'selection_reason_json' => [
                        'reason' => $selectionStatus === 'selected'
                            ? 'Selected within available seats.'
                            : 'Waiting because available seats are filled.',
                        'available_seats' => $availableSeats,
                        'merit_position' => $position,
                    ],
                ]);

                $position++;
            }

            $list->update([
                'total_candidates' => $scores->count(),
                'selected_candidates' => $selected,
                'waiting_candidates' => $waiting,
                'highest_merit_score' => $highestScore,
                'lowest_merit_score' => $lowestScore,
                'generation_summary_json' => [
                    'total_candidates' => $scores->count(),
                    'selected_candidates' => $selected,
                    'waiting_candidates' => $waiting,
                    'available_seats' => $availableSeats,
                    'highest_merit_score' => $highestScore,
                    'lowest_merit_score' => $lowestScore,
                ],
            ]);

            return $this->detail($list->id);
        });
    }

    public function publish(int $meritListId): AdmissionMeritList
    {
        $list = AdmissionMeritList::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $meritListId)
            ->firstOrFail();

        $list->update([
            'status_code' => 'published',
            'published_at' => now(),
            'published_by' => auth()->id(),
        ]);

        return $list->fresh();
    }

    public function cancel(int $meritListId): AdmissionMeritList
    {
        $list = AdmissionMeritList::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $meritListId)
            ->firstOrFail();

        $list->update([
            'status_code' => 'cancelled',
        ]);

        return $list->fresh();
    }

    private function eligibleScoresQuery(int $tenantId, array $data)
    {
        $query = DB::table('admission_applicant_merit_scores as ms')
            ->where('ms.tenant_id', $tenantId)
            ->where('ms.is_eligible_for_merit', 1);

        if (Schema::hasColumn('admission_applicant_merit_scores', 'deleted_at')) {
            $query->whereNull('ms.deleted_at');
        }

        if (!empty($data['admission_merit_formula_id'])) {
            $query->where('ms.admission_merit_formula_id', $data['admission_merit_formula_id']);
        }

        if (!empty($data['admission_session_id'])) {
            $query->where('ms.admission_session_id', $data['admission_session_id']);
        }

        if (!empty($data['admission_preference_group_id'])) {
            $query->where('ms.admission_preference_group_id', $data['admission_preference_group_id']);
        }

        if (!empty($data['offered_program_id'])) {
            $query->where('ms.offered_program_id', $data['offered_program_id']);
        }

        if (!empty($data['program_quota_seat_id'])) {
            $query->where('ms.program_quota_seat_id', $data['program_quota_seat_id']);
        }

        return $query->select('ms.*');
    }

    private function resolveAvailableSeats(array $data): int
    {
        if (!empty($data['available_seats'])) {
            return max(0, (int) $data['available_seats']);
        }

        if (empty($data['program_quota_seat_id']) || !Schema::hasTable('program_quota_seats')) {
            return 0;
        }

        $quota = DB::table('program_quota_seats')
            ->where('id', $data['program_quota_seat_id'])
            ->first();

        if (!$quota) {
            return 0;
        }

        foreach (['seat_count', 'total_seats', 'available_seats', 'seats', 'quota_seats'] as $column) {
            if (property_exists($quota, $column) && $quota->{$column} !== null) {
                return max(0, (int) $quota->{$column});
            }
        }

        return 0;
    }

    private function resolvePreferenceOrder(object $score): ?int
    {
        if (!Schema::hasTable('applicant_program_applications')) {
            return null;
        }

        $query = DB::table('applicant_program_applications')
            ->where('tenant_id', $score->tenant_id)
            ->where('applicant_id', $score->applicant_id);

        if (!empty($score->admission_application_id) && Schema::hasColumn('applicant_program_applications', 'admission_application_id')) {
            $query->where('admission_application_id', $score->admission_application_id);
        }

        if (!empty($score->offered_program_id) && Schema::hasColumn('applicant_program_applications', 'offered_program_id')) {
            $query->where('offered_program_id', $score->offered_program_id);
        }

        $record = $query->first();

        if (!$record) {
            return null;
        }

        foreach (['preference_order', 'choice_order', 'display_order'] as $column) {
            if (property_exists($record, $column) && $record->{$column} !== null) {
                return (int) $record->{$column};
            }
        }

        return null;
    }

    private function nextListNo(int $tenantId, array $data): string
    {
        $count = AdmissionMeritList::query()
            ->where('tenant_id', $tenantId)
            ->when(!empty($data['admission_session_id']), fn ($q) => $q->where('admission_session_id', $data['admission_session_id']))
            ->count();

        return 'ML-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function buildListTitle(string $listNo, array $data): string
    {
        return $listNo . ' Merit List';
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

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $tenantId;
    }
}