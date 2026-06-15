<?php

namespace App\Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionClosureReportController extends Controller
{
    public function admittedCandidates(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        foreach ([
            'admission_confirmations',
            'admission_merit_list_applicants',
            'admission_merit_lists',
            'applicants',
        ] as $table) {
            if (!Schema::hasTable($table)) {
                return response()->json([
                    'data' => [],
                    'message' => "{$table} table not found.",
                ]);
            }
        }

        $query = DB::table('admission_confirmations as c')
            ->leftJoin('admission_merit_list_applicants as mla', 'mla.id', '=', 'c.admission_merit_list_applicant_id')
            ->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'mla.admission_merit_list_id')
            ->leftJoin('applicants as a', 'a.id', '=', 'c.applicant_id');

        $studentJoined = false;

        if (Schema::hasTable('students')) {
            if (
                Schema::hasColumn('admission_confirmations', 'student_id')
                && Schema::hasColumn('students', 'id')
            ) {
                $query->leftJoin('students as s', 's.id', '=', 'c.student_id');
                $studentJoined = true;
            } elseif (
                Schema::hasColumn('students', 'applicant_id')
                && Schema::hasColumn('applicants', 'id')
            ) {
                $query->leftJoin('students as s', 's.applicant_id', '=', 'a.id');
                $studentJoined = true;
            }
        }
        $enrollmentJoined = false;

        if (Schema::hasTable('student_enrollments')) {
            if (
                Schema::hasColumn('admission_confirmations', 'student_enrollment_id')
                && Schema::hasColumn('student_enrollments', 'id')
            ) {
                $query->leftJoin('student_enrollments as se', 'se.id', '=', 'c.student_enrollment_id');
                $enrollmentJoined = true;
            }
        }
        if (Schema::hasTable('offered_programs')) {
            $query->leftJoin('offered_programs as op', 'op.id', '=', 'ml.offered_program_id');
        }

        if (Schema::hasTable('program_quota_seats')) {
            $query->leftJoin('program_quota_seats as pqs', 'pqs.id', '=', 'ml.program_quota_seat_id');
        }

        if (Schema::hasTable('admission_offer_fee_vouchers')) {
            $query->leftJoin('admission_offer_fee_vouchers as v', 'v.admission_merit_list_applicant_id', '=', 'mla.id');
        }

        if ($tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id')) {
            $query->where('c.tenant_id', $tenantId);
        }

        if ($request->filled('admission_session_id') && Schema::hasColumn('admission_merit_lists', 'admission_session_id')) {
            $query->where('ml.admission_session_id', (int) $request->query('admission_session_id'));
        }

        if ($request->filled('offered_program_id') && Schema::hasColumn('admission_merit_lists', 'offered_program_id')) {
            $query->where('ml.offered_program_id', (int) $request->query('offered_program_id'));
        }

        if ($request->filled('program_quota_seat_id') && Schema::hasColumn('admission_merit_lists', 'program_quota_seat_id')) {
            $query->where('ml.program_quota_seat_id', (int) $request->query('program_quota_seat_id'));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->query('q'));

            $query->where(function ($q) use ($search) {
                if (Schema::hasColumn('applicants', 'applicant_no')) {
                    $q->orWhere('a.applicant_no', 'like', "%{$search}%");
                }

                if (Schema::hasColumn('applicants', 'first_name')) {
                    $q->orWhere('a.first_name', 'like', "%{$search}%");
                }

                if (Schema::hasColumn('applicants', 'last_name')) {
                    $q->orWhere('a.last_name', 'like', "%{$search}%");
                }

                if (Schema::hasColumn('applicants', 'cnic_bform')) {
                    $q->orWhere('a.cnic_bform', 'like', "%{$search}%");
                }

                if (Schema::hasTable('students') && Schema::hasColumn('students', 'student_no')) {
                    $q->orWhere('s.student_no', 'like', "%{$search}%");
                }
            });
        }

        $select = [
            'c.id as confirmation_id',
            $this->col('c', 'confirmation_no', 'confirmation_no'),
            $this->col('c', 'status_code', 'confirmation_status_code'),
            $this->col('c', 'confirmed_at', 'confirmed_at'),
            $this->col('c', 'student_id', 'confirmation_student_id'),
            $this->col('c', 'department_id', 'confirmation_department_id'),
            $this->col('c', 'program_id', 'confirmation_program_id'),
            $this->col('c', 'transfer_status_code', 'transfer_status_code'),
            $this->col('c', 'transferred_at', 'transferred_at'),
            'mla.id as merit_list_applicant_id',
            $this->col('mla', 'merit_position', 'merit_position'),
            $this->col('mla', 'final_merit_score', 'final_merit_score'),
            $this->col('mla', 'selection_status_code', 'selection_status_code'),
            $this->col('mla', 'offer_status_code', 'offer_status_code'),
            $this->col('mla', 'voucher_status_code', 'voucher_status_code'),
            $this->col('mla', 'admission_confirmation_status_code', 'admission_confirmation_status_code'),

            'ml.id as admission_merit_list_id',
            $this->col('ml', 'list_no', 'merit_list_no'),
            $this->col('ml', 'title', 'merit_list_title'),
            $this->col('ml', 'admission_session_id', 'admission_session_id'),
            $this->col('ml', 'offered_program_id', 'offered_program_id'),
            $this->col('ml', 'program_quota_seat_id', 'program_quota_seat_id'),

            'a.id as applicant_id',
            $this->col('a', 'applicant_no', 'applicant_no'),
            $this->col('a', 'first_name', 'first_name'),
            $this->col('a', 'last_name', 'last_name'),
            $this->col('a', 'cnic_bform', 'cnic_bform'),
            $this->col('a', 'phone', 'phone'),
            $this->col('a', 'email', 'email'),
            $this->col('c', 'student_enrollment_id', 'student_enrollment_id'),
            $this->col('c', 'finalization_status_code', 'finalization_status_code'),
            $this->col('c', 'finalized_at', 'finalized_at'),
            $this->col('c', 'finalized_by', 'finalized_by'),
            DB::raw($this->nameExpression('a', 'applicant_name')),

            $studentJoined
                ? DB::raw('COALESCE(s.id, c.student_id) as student_id')
                : $this->col('c', 'student_id', 'student_id'),

            $studentJoined
                ? $this->tableCol('students', 's', 'student_no', 'student_no')
                : DB::raw('NULL as student_no'),

            $studentJoined
                ? $this->tableCol('students', 's', 'registration_no', 'registration_no')
                : DB::raw('NULL as registration_no'),

            $studentJoined
                ? $this->tableCol('students', 's', 'status_code', 'student_status_code')
                : DB::raw('NULL as student_status_code'),

            $enrollmentJoined && Schema::hasColumn('student_enrollments', 'enrollment_no')
                ? DB::raw('se.enrollment_no as enrollment_no')
                : DB::raw('NULL as enrollment_no'),
                
            $this->tableCol('offered_programs', 'op', 'code', 'offered_program_code'),
            $this->tableCol('offered_programs', 'op', 'title', 'offered_program_title'),
            $this->tableCol('offered_programs', 'op', 'name', 'offered_program_name'),
            $this->tableCol('offered_programs', 'op', 'department_id', 'department_id'),

            $this->tableCol('program_quota_seats', 'pqs', 'quota_code', 'quota_code'),
            $this->tableCol('program_quota_seats', 'pqs', 'quota_name', 'quota_name'),
            $this->tableCol('program_quota_seats', 'pqs', 'title', 'quota_title'),

            $this->tableCol('admission_offer_fee_vouchers', 'v', 'voucher_no', 'voucher_no'),
            $this->tableCol('admission_offer_fee_vouchers', 'v', 'amount', 'voucher_amount'),
            $this->tableCol('admission_offer_fee_vouchers', 'v', 'status_code', 'payment_status_code'),
            $this->tableCol('admission_offer_fee_vouchers', 'v', 'paid_at', 'paid_at'),
        ];

        $items = $query
            ->select($select)
            ->orderByDesc('c.id')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'data' => $items,
            'message' => 'Admitted candidates fetched successfully.',
        ]);
    }

    public function seatSummary(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        foreach (['admission_merit_lists', 'admission_merit_list_applicants'] as $table) {
            if (!Schema::hasTable($table)) {
                return response()->json([
                    'data' => [],
                    'message' => "{$table} table not found.",
                ]);
            }
        }

        $listsQuery = DB::table('admission_merit_lists as ml');

        if (Schema::hasTable('offered_programs')) {
            $listsQuery->leftJoin('offered_programs as op', 'op.id', '=', 'ml.offered_program_id');
        }

        if (Schema::hasTable('program_quota_seats')) {
            $listsQuery->leftJoin('program_quota_seats as pqs', 'pqs.id', '=', 'ml.program_quota_seat_id');
        }

        if ($tenantId && Schema::hasColumn('admission_merit_lists', 'tenant_id')) {
            $listsQuery->where('ml.tenant_id', $tenantId);
        }

        if ($request->filled('admission_session_id') && Schema::hasColumn('admission_merit_lists', 'admission_session_id')) {
            $listsQuery->where('ml.admission_session_id', (int) $request->query('admission_session_id'));
        }

        if ($request->filled('offered_program_id') && Schema::hasColumn('admission_merit_lists', 'offered_program_id')) {
            $listsQuery->where('ml.offered_program_id', (int) $request->query('offered_program_id'));
        }

        if ($request->filled('program_quota_seat_id') && Schema::hasColumn('admission_merit_lists', 'program_quota_seat_id')) {
            $listsQuery->where('ml.program_quota_seat_id', (int) $request->query('program_quota_seat_id'));
        }

        $select = [
            'ml.id as admission_merit_list_id',
            $this->col('ml', 'list_no', 'merit_list_no'),
            $this->col('ml', 'title', 'merit_list_title'),
            $this->col('ml', 'admission_session_id', 'admission_session_id'),
            $this->col('ml', 'offered_program_id', 'offered_program_id'),
            $this->col('ml', 'program_quota_seat_id', 'program_quota_seat_id'),
            $this->col('ml', 'available_seats', 'merit_list_available_seats'),
            $this->col('ml', 'total_candidates', 'total_candidates_on_list'),
            $this->col('ml', 'selected_candidates', 'selected_candidates_on_list'),

            $this->tableCol('offered_programs', 'op', 'code', 'offered_program_code'),
            $this->tableCol('offered_programs', 'op', 'title', 'offered_program_title'),
            $this->tableCol('offered_programs', 'op', 'name', 'offered_program_name'),
            $this->tableCol('offered_programs', 'op', 'department_id', 'department_id'),

            $this->tableCol('program_quota_seats', 'pqs', 'quota_code', 'quota_code'),
            $this->tableCol('program_quota_seats', 'pqs', 'quota_name', 'quota_name'),
            $this->tableCol('program_quota_seats', 'pqs', 'title', 'quota_title'),
            DB::raw($this->seatCapacityExpression()),
        ];

        $lists = $listsQuery
            ->select($select)
            ->orderByDesc('ml.id')
            ->get();

        $items = $lists->map(function ($list) use ($tenantId) {
            $participants = DB::table('admission_merit_list_applicants')
                ->where('admission_merit_list_id', $list->admission_merit_list_id);

            if ($tenantId && Schema::hasColumn('admission_merit_list_applicants', 'tenant_id')) {
                $participants->where('tenant_id', $tenantId);
            }

            $rows = $participants->get();

            $totalSeats = (int) ($list->total_seats ?? 0);

            if ($totalSeats <= 0) {
                $totalSeats = (int) ($list->merit_list_available_seats ?? 0);
            }

            $selected = $rows->where('selection_status_code', 'selected')->count();
            $waiting = $rows->where('selection_status_code', 'waiting')->count();
            $offered = $rows->where('offer_status_code', 'offered')->count();
            $accepted = $rows->where('offer_status_code', 'accepted')->count();
            $rejected = $rows->where('offer_status_code', 'rejected')->count();
            $expired = $rows->where('offer_status_code', 'expired')->count();

            $voucherGenerated = $rows->filter(fn ($row) => !empty($row->voucher_status_code))->count();
            $paymentSubmitted = $rows->where('voucher_status_code', 'payment_submitted')->count();
            $paid = $rows->where('voucher_status_code', 'paid')->count();

            $confirmed = $rows->where('admission_confirmation_status_code', 'confirmed')->count();

            if (Schema::hasTable('admission_confirmations')) {
                $confirmed = DB::table('admission_confirmations')
                    ->where('admission_merit_list_id', $list->admission_merit_list_id)
                    ->when($tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id'), fn ($q) => $q->where('tenant_id', $tenantId))
                    ->count();
            }

            return [
                'admission_merit_list_id' => $list->admission_merit_list_id,
                'merit_list_no' => $list->merit_list_no,
                'merit_list_title' => $list->merit_list_title,
                'admission_session_id' => $list->admission_session_id,
                'offered_program_id' => $list->offered_program_id,
                'program_quota_seat_id' => $list->program_quota_seat_id,

                'offered_program_code' => $list->offered_program_code,
                'offered_program_title' => $list->offered_program_title ?: $list->offered_program_name,
                'department_id' => $list->department_id,

                'quota_code' => $list->quota_code,
                'quota_name' => $list->quota_name ?: $list->quota_title,

                'total_seats' => $totalSeats,
                'total_candidates' => $rows->count(),
                'selected_candidates' => $selected,
                'waiting_candidates' => $waiting,
                'offered_candidates' => $offered,
                'accepted_candidates' => $accepted,
                'rejected_candidates' => $rejected,
                'expired_candidates' => $expired,
                'voucher_generated_candidates' => $voucherGenerated,
                'payment_submitted_candidates' => $paymentSubmitted,
                'paid_candidates' => $paid,
                'confirmed_candidates' => $confirmed,

                'remaining_after_confirmation' => max($totalSeats - $confirmed, 0),
                'available_after_selection' => max($totalSeats - $selected, 0),
                'available_after_acceptance' => max($totalSeats - $accepted, 0),
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'message' => 'Seat summary fetched successfully.',
        ]);
    }

    private function col(string $alias, string $column, string $as): mixed
    {
        $table = match ($alias) {
            'c' => 'admission_confirmations',
            'mla' => 'admission_merit_list_applicants',
            'ml' => 'admission_merit_lists',
            'a' => 'applicants',
            default => null,
        };

        if ($table && Schema::hasColumn($table, $column)) {
            return "{$alias}.{$column} as {$as}";
        }

        return DB::raw("NULL as {$as}");
    }

    private function tableCol(string $table, string $alias, string $column, string $as): mixed
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            return "{$alias}.{$column} as {$as}";
        }

        return DB::raw("NULL as {$as}");
    }

    private function nameExpression(string $alias, string $as): string
    {
        $first = Schema::hasColumn('applicants', 'first_name') ? "COALESCE({$alias}.first_name, '')" : "''";
        $last = Schema::hasColumn('applicants', 'last_name') ? "COALESCE({$alias}.last_name, '')" : "''";

        return "TRIM(CONCAT({$first}, ' ', {$last})) as {$as}";
    }

    private function seatCapacityExpression(): string
    {
        if (Schema::hasTable('program_quota_seats')) {
            foreach (['total_seats', 'seat_count', 'seats', 'allocated_seats', 'quota_seats'] as $column) {
                if (Schema::hasColumn('program_quota_seats', $column)) {
                    return "COALESCE(pqs.{$column}, 0) as total_seats";
                }
            }
        }

        if (Schema::hasColumn('admission_merit_lists', 'available_seats')) {
            return "COALESCE(ml.available_seats, 0) as total_seats";
        }

        return "0 as total_seats";
    }
}