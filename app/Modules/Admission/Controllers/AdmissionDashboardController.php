<?php

namespace App\Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionDashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        $filters = [
            'admission_session_id' => $request->query('admission_session_id'),
            'offered_program_id' => $request->query('offered_program_id'),
            'program_quota_seat_id' => $request->query('program_quota_seat_id'),
        ];

        return response()->json([
            'data' => [
                'cards' => $this->cards($tenantId, $filters),
                'program_summary' => $this->programSummary($tenantId, $filters),
                'quota_summary' => $this->quotaSummary($tenantId, $filters),
                'offer_status_summary' => $this->offerStatusSummary($tenantId, $filters),
                'voucher_status_summary' => $this->voucherStatusSummary($tenantId, $filters),
                'confirmation_summary' => $this->confirmationSummary($tenantId, $filters),
                'daily_applications' => $this->dailyApplications($tenantId, $filters),
                'daily_confirmations' => $this->dailyConfirmations($tenantId, $filters),
                'seat_summary' => $this->seatSummary($tenantId, $filters),
            ],
            'message' => 'Admission dashboard summary fetched successfully.',
        ]);
    }

    private function cards(?int $tenantId, array $filters): array
    {
        return [
            'total_applicants' => $this->countTable('applicants', $tenantId),
            'completed_profiles' => $this->countWhere('applicants', $tenantId, [
                ['profile_status_code', '=', 'completed'],
            ]),
            'submitted_applications' => $this->countTable('admission_applications', $tenantId),
            'merit_calculated' => $this->countTable('admission_applicant_merit_scores', $tenantId),
            'merit_lists' => $this->countTable('admission_merit_lists', $tenantId),
            'selected_candidates' => $this->countWhere('admission_merit_list_applicants', $tenantId, [
                ['selection_status_code', '=', 'selected'],
            ]),
            'waiting_candidates' => $this->countWhere('admission_merit_list_applicants', $tenantId, [
                ['selection_status_code', '=', 'waiting'],
            ]),
            'offered_candidates' => $this->countWhere('admission_merit_list_applicants', $tenantId, [
                ['offer_status_code', '=', 'offered'],
            ]),
            'accepted_candidates' => $this->countWhere('admission_merit_list_applicants', $tenantId, [
                ['offer_status_code', '=', 'accepted'],
            ]),
            'payment_submitted' => $this->countWhere('admission_offer_fee_vouchers', $tenantId, [
                ['status_code', '=', 'payment_submitted'],
            ]),
            'paid_vouchers' => $this->countWhere('admission_offer_fee_vouchers', $tenantId, [
                ['status_code', '=', 'paid'],
            ]),
            'confirmed_admissions' => $this->countWhere('admission_confirmations', $tenantId, [
                ['status_code', '=', 'confirmed'],
            ]),
            'transferred_students' => $this->countWhere('admission_confirmations', $tenantId, [
                ['transfer_status_code', '=', 'transferred'],
            ]),
        ];
    }

    private function programSummary(?int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('admission_merit_lists') || !Schema::hasTable('admission_merit_list_applicants')) {
            return [];
        }

        $query = DB::table('admission_merit_lists as ml')
            ->leftJoin('admission_merit_list_applicants as mla', 'mla.admission_merit_list_id', '=', 'ml.id');

        if (Schema::hasTable('offered_programs')) {
            $query->leftJoin('offered_programs as op', 'op.id', '=', 'ml.offered_program_id');
        }

        $this->applyMeritListFilters($query, $tenantId, $filters, 'ml');

        return $query
            ->select([
                $this->tableColRaw('offered_programs', 'op', 'id', 'offered_program_id'),
                $this->tableColRaw('offered_programs', 'op', 'code', 'program_code'),
                $this->tableColRaw('offered_programs', 'op', 'title', 'program_title'),
                DB::raw('COUNT(mla.id) as total_candidates'),
                DB::raw("SUM(CASE WHEN mla.selection_status_code = 'selected' THEN 1 ELSE 0 END) as selected_candidates"),
                DB::raw("SUM(CASE WHEN mla.selection_status_code = 'waiting' THEN 1 ELSE 0 END) as waiting_candidates"),
                DB::raw("SUM(CASE WHEN mla.offer_status_code = 'accepted' THEN 1 ELSE 0 END) as accepted_candidates"),
                DB::raw("SUM(CASE WHEN mla.admission_confirmation_status_code = 'confirmed' THEN 1 ELSE 0 END) as confirmed_candidates"),
            ])
            ->groupBy([
                DB::raw(Schema::hasTable('offered_programs') ? 'op.id' : 'ml.offered_program_id'),
                DB::raw(Schema::hasTable('offered_programs') ? 'op.code' : 'ml.offered_program_id'),
                DB::raw(Schema::hasTable('offered_programs') && Schema::hasColumn('offered_programs', 'title') ? 'op.title' : 'ml.offered_program_id'),
            ])
            ->orderByDesc('confirmed_candidates')
            ->get()
            ->toArray();
    }

    private function quotaSummary(?int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('admission_merit_lists') || !Schema::hasTable('admission_merit_list_applicants')) {
            return [];
        }

        $query = DB::table('admission_merit_lists as ml')
            ->leftJoin('admission_merit_list_applicants as mla', 'mla.admission_merit_list_id', '=', 'ml.id');

        if (Schema::hasTable('program_quota_seats')) {
            $query->leftJoin('program_quota_seats as pqs', 'pqs.id', '=', 'ml.program_quota_seat_id');
        }

        $this->applyMeritListFilters($query, $tenantId, $filters, 'ml');

        return $query
            ->select([
                $this->tableColRaw('program_quota_seats', 'pqs', 'id', 'program_quota_seat_id'),
                $this->tableColRaw('program_quota_seats', 'pqs', 'quota_code', 'quota_code'),
                $this->tableColRaw('program_quota_seats', 'pqs', 'quota_name', 'quota_name'),
                DB::raw('COUNT(mla.id) as total_candidates'),
                DB::raw("SUM(CASE WHEN mla.selection_status_code = 'selected' THEN 1 ELSE 0 END) as selected_candidates"),
                DB::raw("SUM(CASE WHEN mla.selection_status_code = 'waiting' THEN 1 ELSE 0 END) as waiting_candidates"),
                DB::raw("SUM(CASE WHEN mla.offer_status_code = 'accepted' THEN 1 ELSE 0 END) as accepted_candidates"),
                DB::raw("SUM(CASE WHEN mla.admission_confirmation_status_code = 'confirmed' THEN 1 ELSE 0 END) as confirmed_candidates"),
            ])
            ->groupBy([
                DB::raw(Schema::hasTable('program_quota_seats') ? 'pqs.id' : 'ml.program_quota_seat_id'),
                DB::raw(Schema::hasTable('program_quota_seats') && Schema::hasColumn('program_quota_seats', 'quota_code') ? 'pqs.quota_code' : 'ml.program_quota_seat_id'),
                DB::raw(Schema::hasTable('program_quota_seats') && Schema::hasColumn('program_quota_seats', 'quota_name') ? 'pqs.quota_name' : 'ml.program_quota_seat_id'),
            ])
            ->orderByDesc('confirmed_candidates')
            ->get()
            ->toArray();
    }

    private function offerStatusSummary(?int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('admission_merit_list_applicants')) {
            return [];
        }

        $query = DB::table('admission_merit_list_applicants as mla');

        if (Schema::hasTable('admission_merit_lists')) {
            $query->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'mla.admission_merit_list_id');
            $this->applyMeritListFilters($query, $tenantId, $filters, 'ml');
        } elseif ($tenantId && Schema::hasColumn('admission_merit_list_applicants', 'tenant_id')) {
            $query->where('mla.tenant_id', $tenantId);
        }

        return $query
            ->select([
                DB::raw("COALESCE(mla.offer_status_code, 'not_offered') as status_code"),
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy(DB::raw("COALESCE(mla.offer_status_code, 'not_offered')"))
            ->get()
            ->toArray();
    }

    private function voucherStatusSummary(?int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('admission_offer_fee_vouchers')) {
            return [];
        }

        $query = DB::table('admission_offer_fee_vouchers as v');

        if (Schema::hasTable('admission_merit_list_applicants')) {
            $query->leftJoin('admission_merit_list_applicants as mla', 'mla.id', '=', 'v.admission_merit_list_applicant_id');
        }

        if (Schema::hasTable('admission_merit_lists')) {
            $query->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'mla.admission_merit_list_id');
            $this->applyMeritListFilters($query, $tenantId, $filters, 'ml');
        } elseif ($tenantId && Schema::hasColumn('admission_offer_fee_vouchers', 'tenant_id')) {
            $query->where('v.tenant_id', $tenantId);
        }

        return $query
            ->select([
                DB::raw("COALESCE(v.status_code, 'unknown') as status_code"),
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy(DB::raw("COALESCE(v.status_code, 'unknown')"))
            ->get()
            ->toArray();
    }

    private function confirmationSummary(?int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('admission_confirmations')) {
            return [];
        }

        $query = DB::table('admission_confirmations as c');

        if (Schema::hasTable('admission_merit_lists')) {
            $query->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'c.admission_merit_list_id');
            $this->applyMeritListFilters($query, $tenantId, $filters, 'ml');
        } elseif ($tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id')) {
            $query->where('c.tenant_id', $tenantId);
        }

        return $query
            ->select([
                DB::raw("COALESCE(c.status_code, 'unknown') as status_code"),
                DB::raw("COALESCE(c.transfer_status_code, 'not_transferred') as transfer_status_code"),
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy([
                DB::raw("COALESCE(c.status_code, 'unknown')"),
                DB::raw("COALESCE(c.transfer_status_code, 'not_transferred')"),
            ])
            ->get()
            ->toArray();
    }

    private function dailyApplications(?int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('admission_applications') || !Schema::hasColumn('admission_applications', 'created_at')) {
            return [];
        }

        $query = DB::table('admission_applications');

        if ($tenantId && Schema::hasColumn('admission_applications', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        return $query
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->limit(30)
            ->get()
            ->toArray();
    }

    private function dailyConfirmations(?int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('admission_confirmations') || !Schema::hasColumn('admission_confirmations', 'confirmed_at')) {
            return [];
        }

        $query = DB::table('admission_confirmations as c');

        if (Schema::hasTable('admission_merit_lists')) {
            $query->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'c.admission_merit_list_id');
            $this->applyMeritListFilters($query, $tenantId, $filters, 'ml');
        } elseif ($tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id')) {
            $query->where('c.tenant_id', $tenantId);
        }

        return $query
            ->whereNotNull('c.confirmed_at')
            ->select([
                DB::raw('DATE(c.confirmed_at) as date'),
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy(DB::raw('DATE(c.confirmed_at)'))
            ->orderBy('date')
            ->limit(30)
            ->get()
            ->toArray();
    }

    private function seatSummary(?int $tenantId, array $filters): array
    {
        if (!Schema::hasTable('admission_merit_lists') || !Schema::hasTable('admission_merit_list_applicants')) {
            return [];
        }

        $query = DB::table('admission_merit_lists as ml')
            ->leftJoin('admission_merit_list_applicants as mla', 'mla.admission_merit_list_id', '=', 'ml.id');

        if (Schema::hasTable('offered_programs')) {
            $query->leftJoin('offered_programs as op', 'op.id', '=', 'ml.offered_program_id');
        }

        if (Schema::hasTable('program_quota_seats')) {
            $query->leftJoin('program_quota_seats as pqs', 'pqs.id', '=', 'ml.program_quota_seat_id');
        }

        $this->applyMeritListFilters($query, $tenantId, $filters, 'ml');

        $rows = $query
            ->select([
                'ml.offered_program_id',
                'ml.program_quota_seat_id',
                $this->tableColRaw('offered_programs', 'op', 'code', 'offered_program_code'),
                $this->tableColRaw('offered_programs', 'op', 'title', 'offered_program_title'),
                $this->tableColRaw('program_quota_seats', 'pqs', 'quota_code', 'quota_code'),
                $this->tableColRaw('program_quota_seats', 'pqs', 'quota_name', 'quota_name'),
                DB::raw($this->seatCapacityExpression()),
                DB::raw('COUNT(mla.id) as total_candidates'),
                DB::raw("SUM(CASE WHEN mla.selection_status_code = 'selected' THEN 1 ELSE 0 END) as selected_candidates"),
                DB::raw("SUM(CASE WHEN mla.selection_status_code = 'waiting' THEN 1 ELSE 0 END) as waiting_candidates"),
                DB::raw("SUM(CASE WHEN mla.offer_status_code = 'accepted' THEN 1 ELSE 0 END) as accepted_candidates"),
                DB::raw("SUM(CASE WHEN mla.admission_confirmation_status_code = 'confirmed' THEN 1 ELSE 0 END) as confirmed_candidates"),
            ])
            ->groupBy([
                'ml.offered_program_id',
                'ml.program_quota_seat_id',
                DB::raw(Schema::hasTable('offered_programs') && Schema::hasColumn('offered_programs', 'code') ? 'op.code' : 'ml.offered_program_id'),
                DB::raw(Schema::hasTable('offered_programs') && Schema::hasColumn('offered_programs', 'title') ? 'op.title' : 'ml.offered_program_id'),
                DB::raw(Schema::hasTable('program_quota_seats') && Schema::hasColumn('program_quota_seats', 'quota_code') ? 'pqs.quota_code' : 'ml.program_quota_seat_id'),
                DB::raw(Schema::hasTable('program_quota_seats') && Schema::hasColumn('program_quota_seats', 'quota_name') ? 'pqs.quota_name' : 'ml.program_quota_seat_id'),
                DB::raw($this->seatCapacityGroupExpression()),
            ])
            ->get();

        return $rows->map(function ($row) {
            $totalSeats = (int) ($row->total_seats ?? 0);
            $confirmed = (int) ($row->confirmed_candidates ?? 0);

            return [
                'offered_program_id' => $row->offered_program_id,
                'program_quota_seat_id' => $row->program_quota_seat_id,
                'offered_program_code' => $row->offered_program_code,
                'offered_program_title' => $row->offered_program_title,
                'quota_code' => $row->quota_code,
                'quota_name' => $row->quota_name,
                'total_seats' => $totalSeats,
                'total_candidates' => (int) ($row->total_candidates ?? 0),
                'selected_candidates' => (int) ($row->selected_candidates ?? 0),
                'waiting_candidates' => (int) ($row->waiting_candidates ?? 0),
                'accepted_candidates' => (int) ($row->accepted_candidates ?? 0),
                'confirmed_candidates' => $confirmed,
                'remaining_after_confirmation' => max($totalSeats - $confirmed, 0),
            ];
        })->toArray();
    }

    private function applyMeritListFilters($query, ?int $tenantId, array $filters, string $alias = 'ml'): void
    {
        if ($tenantId && Schema::hasColumn('admission_merit_lists', 'tenant_id')) {
            $query->where("{$alias}.tenant_id", $tenantId);
        }

        if (!empty($filters['admission_session_id']) && Schema::hasColumn('admission_merit_lists', 'admission_session_id')) {
            $query->where("{$alias}.admission_session_id", (int) $filters['admission_session_id']);
        }

        if (!empty($filters['offered_program_id']) && Schema::hasColumn('admission_merit_lists', 'offered_program_id')) {
            $query->where("{$alias}.offered_program_id", (int) $filters['offered_program_id']);
        }

        if (!empty($filters['program_quota_seat_id']) && Schema::hasColumn('admission_merit_lists', 'program_quota_seat_id')) {
            $query->where("{$alias}.program_quota_seat_id", (int) $filters['program_quota_seat_id']);
        }
    }

    private function countTable(string $table, ?int $tenantId): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if ($tenantId && Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        return (int) $query->count();
    }

    private function countWhere(string $table, ?int $tenantId, array $conditions): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if ($tenantId && Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        foreach ($conditions as [$column, $operator, $value]) {
            if (Schema::hasColumn($table, $column)) {
                $query->where($column, $operator, $value);
            }
        }

        return (int) $query->count();
    }

    private function tableColRaw(string $table, string $alias, string $column, string $as): mixed
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            return DB::raw("{$alias}.{$column} as {$as}");
        }

        return DB::raw("NULL as {$as}");
    }

    private function seatCapacityExpression(): string
    {
        if (Schema::hasTable('program_quota_seats')) {
            foreach (['total_seats', 'seat_count', 'seats', 'allocated_seats', 'quota_seats'] as $column) {
                if (Schema::hasColumn('program_quota_seats', $column)) {
                    return "MAX(COALESCE(pqs.{$column}, 0)) as total_seats";
                }
            }
        }

        if (Schema::hasColumn('admission_merit_lists', 'available_seats')) {
            return "MAX(COALESCE(ml.available_seats, 0)) as total_seats";
        }

        return "0 as total_seats";
    }

    private function seatCapacityGroupExpression(): string
    {
        if (Schema::hasTable('program_quota_seats')) {
            foreach (['total_seats', 'seat_count', 'seats', 'allocated_seats', 'quota_seats'] as $column) {
                if (Schema::hasColumn('program_quota_seats', $column)) {
                    return "pqs.{$column}";
                }
            }
        }

        if (Schema::hasColumn('admission_merit_lists', 'available_seats')) {
            return "ml.available_seats";
        }

        return "ml.id";
    }
}