<?php

namespace App\Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionReportController extends Controller
{
    public function applicants(Request $request): JsonResponse
    {
        return $this->simpleReport($request, 'applicants', [
            'id',
            'applicant_no',
            'first_name',
            'last_name',
            'cnic_bform',
            'phone',
            'email',
            'profile_status_code',
            'status_code',
            'created_at',
        ]);
    }

    public function applications(Request $request): JsonResponse
    {
        return $this->simpleReport($request, 'admission_applications', [
            'id',
            'applicant_id',
            'admission_session_id',
            'offered_program_id',
            'program_quota_seat_id',
            'status_code',
            'submitted_at',
            'created_at',
        ]);
    }

    public function meritScores(Request $request): JsonResponse
    {
        return $this->simpleReport($request, 'admission_applicant_merit_scores', [
            'id',
            'applicant_id',
            'admission_session_id',
            'offered_program_id',
            'program_quota_seat_id',
            'admission_merit_formula_id',
            'final_merit_score',
            'is_eligible_for_merit',
            'status_code',
            'calculated_at',
        ]);
    }

    public function meritLists(Request $request): JsonResponse
    {
        return $this->simpleReport($request, 'admission_merit_lists', [
            'id',
            'list_no',
            'title',
            'admission_session_id',
            'offered_program_id',
            'program_quota_seat_id',
            'available_seats',
            'total_candidates',
            'selected_candidates',
            'waiting_candidates',
            'status_code',
            'published_at',
            'created_at',
        ]);
    }

    public function offers(Request $request): JsonResponse
    {
        return $this->simpleReport($request, 'admission_merit_list_applicants', [
            'id',
            'applicant_id',
            'admission_merit_list_id',
            'merit_position',
            'waiting_position',
            'final_merit_score',
            'selection_status_code',
            'waiting_status_code',
            'offer_status_code',
            'voucher_status_code',
            'admission_confirmation_status_code',
            'department_transfer_status_code',
            'offer_generated_at',
            'accepted_at',
            'rejected_at',
            'admission_confirmed_at',
        ]);
    }

    public function vouchers(Request $request): JsonResponse
    {
        return $this->simpleReport($request, 'admission_offer_fee_vouchers', [
            'id',
            'voucher_no',
            'applicant_id',
            'admission_merit_list_applicant_id',
            'amount',
            'due_date',
            'status_code',
            'paid_amount',
            'paid_at',
            'payment_reference',
            'payment_method_code',
            'verified_at',
            'created_at',
        ]);
    }

    public function confirmedAdmissions(Request $request): JsonResponse
    {
        return $this->simpleReport($request, 'admission_confirmations', [
            'id',
            'confirmation_no',
            'applicant_id',
            'student_id',
            'admission_merit_list_applicant_id',
            'admission_merit_list_id',
            'admission_session_id',
            'offered_program_id',
            'program_quota_seat_id',
            'department_id',
            'program_id',
            'status_code',
            'transfer_status_code',
            'confirmed_at',
            'transferred_at',
        ]);
    }

    public function seatSummary(Request $request): JsonResponse
    {
        $controller = app(AdmissionClosureReportController::class);
        return $controller->seatSummary($request);
    }

    public function waitingList(Request $request): JsonResponse
    {
        return $this->simpleReport($request, 'admission_merit_list_applicants', [
            'id',
            'applicant_id',
            'admission_merit_list_id',
            'merit_position',
            'waiting_position',
            'final_merit_score',
            'selection_status_code',
            'waiting_status_code',
            'promoted_from_waiting_at',
            'offer_status_code',
        ], [
            ['selection_status_code', '=', 'waiting'],
        ]);
    }

    private function simpleReport(
        Request $request,
        string $table,
        array $columns,
        array $fixedConditions = []
    ): JsonResponse {
        $tenantId = $request->user()?->tenant_id;

        if (!Schema::hasTable($table)) {
            return response()->json([
                'data' => [
                    'data' => [],
                    'total' => 0,
                ],
                'message' => "{$table} table not found.",
            ]);
        }

        $select = [];

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $select[] = $column;
            }
        }

        if (empty($select)) {
            $select[] = 'id';
        }

        $query = DB::table($table)->select($select);

        if ($tenantId && Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        foreach ($fixedConditions as [$column, $operator, $value]) {
            if (Schema::hasColumn($table, $column)) {
                $query->where($column, $operator, $value);
            }
        }

        if ($request->filled('status_code') && Schema::hasColumn($table, 'status_code')) {
            $query->where('status_code', $request->query('status_code'));
        }

        if ($request->filled('applicant_id') && Schema::hasColumn($table, 'applicant_id')) {
            $query->where('applicant_id', (int) $request->query('applicant_id'));
        }

        if ($request->filled('admission_session_id') && Schema::hasColumn($table, 'admission_session_id')) {
            $query->where('admission_session_id', (int) $request->query('admission_session_id'));
        }

        if ($request->filled('offered_program_id') && Schema::hasColumn($table, 'offered_program_id')) {
            $query->where('offered_program_id', (int) $request->query('offered_program_id'));
        }

        if ($request->filled('program_quota_seat_id') && Schema::hasColumn($table, 'program_quota_seat_id')) {
            $query->where('program_quota_seat_id', (int) $request->query('program_quota_seat_id'));
        }

        if ($request->filled('date_from') && Schema::hasColumn($table, 'created_at')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to') && Schema::hasColumn($table, 'created_at')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        if (Schema::hasColumn($table, 'id')) {
            $query->orderByDesc('id');
        }

        $items = $query->paginate((int) $request->query('per_page', 25));

        return response()->json([
            'data' => $items,
            'message' => 'Report fetched successfully.',
        ]);
    }
}