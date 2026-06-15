<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicantProgressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        $search = $request->query('search');
        $status = $request->query('status');
        $perPage = (int) ($request->query('per_page', 15));

        $qualificationCounts = DB::table('applicant_qualifications')
            ->select('applicant_id', DB::raw('COUNT(*) as qualification_count'))
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->groupBy('applicant_id');

        $documentCounts = DB::table('applicant_documents')
            ->select('applicant_id', DB::raw('COUNT(*) as document_count'))
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->groupBy('applicant_id');

        $testCounts = DB::table('applicant_test_results')
            ->select('applicant_id', DB::raw('COUNT(*) as test_count'))
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->groupBy('applicant_id');

        $applicationCounts = DB::table('applicant_program_applications')
            ->select(
                'applicant_id',
                DB::raw('COUNT(*) as application_count'),
                DB::raw("SUM(CASE WHEN fee_status_code = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw("SUM(CASE WHEN application_status_code IN ('submitted','final_submitted') THEN 1 ELSE 0 END) as submitted_count")
            )
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->groupBy('applicant_id');

        $preferenceCounts = DB::table('applicant_program_applications')
            ->select('applicant_id', DB::raw('COUNT(*) as preference_count'))
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereNotNull('applicant_application_group_id')
            ->groupBy('applicant_id');

        $query = DB::table('applicants as a')
            ->leftJoinSub($qualificationCounts, 'q', 'q.applicant_id', '=', 'a.id')
            ->leftJoinSub($documentCounts, 'd', 'd.applicant_id', '=', 'a.id')
            ->leftJoinSub($testCounts, 't', 't.applicant_id', '=', 'a.id')
            ->leftJoinSub($applicationCounts, 'ap', 'ap.applicant_id', '=', 'a.id')
            ->leftJoinSub($preferenceCounts, 'pr', 'pr.applicant_id', '=', 'a.id')
            ->where('a.tenant_id', $tenantId)
            ->whereNull('a.deleted_at')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('a.applicant_no', 'like', "%{$search}%")
                        ->orWhere('a.full_name', 'like', "%{$search}%")
                        ->orWhere('a.cnic_bform', 'like', "%{$search}%")
                        ->orWhere('a.phone', 'like', "%{$search}%")
                        ->orWhere('a.email', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('a.applicant_status_code', $status))
            ->select([
                'a.id',
                'a.applicant_no',
                'a.full_name',
                'a.father_name',
                'a.cnic_bform',
                'a.phone',
                'a.email',
                'a.profile_status_code',
                'a.applicant_status_code',
                DB::raw('COALESCE(q.qualification_count, 0) as qualification_count'),
                DB::raw('COALESCE(d.document_count, 0) as document_count'),
                DB::raw('COALESCE(t.test_count, 0) as test_count'),
                DB::raw('COALESCE(pr.preference_count, 0) as preference_count'),
                DB::raw('COALESCE(ap.application_count, 0) as application_count'),
                DB::raw('COALESCE(ap.submitted_count, 0) as submitted_count'),
                DB::raw('COALESCE(ap.paid_count, 0) as paid_count'),
            ])
            ->orderByDesc('a.id')
            ->paginate($perPage);

        $query->getCollection()->transform(function ($row) {
            $completed = 0;

            if ($row->profile_status_code === 'completed') {
                $completed++;
            }

            if ((int) $row->qualification_count > 0) {
                $completed++;
            }

            if ((int) $row->document_count > 0) {
                $completed++;
            }

            if ((int) $row->test_count > 0) {
                $completed++;
            }

            if ((int) $row->preference_count > 0) {
                $completed++;
            }

            if ((int) $row->submitted_count > 0) {
                $completed++;
            }

            $row->progress_percent = round(($completed / 6) * 100);
            $row->progress_summary = "{$completed}/6 steps";

            return $row;
        });

        return ApiResponse::success($query, 'Applicant progress fetched successfully.');
    }

    public function show(int $applicantId): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        $applicant = DB::table('applicants')
            ->where('tenant_id', $tenantId)
            ->where('id', $applicantId)
            ->whereNull('deleted_at')
            ->first();

        if (!$applicant) {
            abort(404, 'Applicant not found.');
        }

        $qualifications = DB::table('applicant_qualifications')
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicantId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        $documents = DB::table('applicant_documents')
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicantId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        $testResults = DB::table('applicant_test_results')
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicantId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        $applications = DB::table('applicant_program_applications as app')
            ->leftJoin('offered_programs as op', 'op.id', '=', 'app.offered_program_id')
            ->leftJoin('program_quota_seats as qs', 'qs.id', '=', 'app.program_quota_seat_id')
            ->where('app.tenant_id', $tenantId)
            ->where('app.applicant_id', $applicantId)
            ->whereNull('app.deleted_at')
            ->select([
                'app.id',
                'app.application_no',
                'app.applicant_application_group_id',
                'app.offered_program_id',
                'op.title as offered_program_title',
                'app.program_quota_seat_id',
                'qs.quota_name',
                'app.preference_order',
                'app.eligibility_status_code',
                'app.application_status_code',
                'app.document_status_code',
                'app.fee_status_code',
                'app.test_status_code',
                'app.submitted_at',
            ])
            ->orderBy('app.preference_order')
            ->orderByDesc('app.id')
            ->get();

        $vouchers = DB::table('applicant_fee_vouchers as v')
            ->leftJoin('applicant_program_applications as app', 'app.id', '=', 'v.applicant_program_application_id')
            ->where('v.tenant_id', $tenantId)
            ->where('app.applicant_id', $applicantId)
            ->whereNull('v.deleted_at')
            ->select([
                'v.id',
                'v.voucher_no',
                'v.applicant_program_application_id',
                'v.voucher_type_code',
                'v.issue_date',
                'v.due_date',
                'v.amount',
                'v.discount_amount',
                'v.fine_amount',
                'v.payable_amount',
                'v.paid_amount',
                'v.balance_amount',
                'v.status_code',
            ])
            ->orderByDesc('v.id')
            ->get();

            $payments = DB::table('applicant_payments as p')
                ->leftJoin('applicant_fee_vouchers as v', 'v.id', '=', 'p.applicant_fee_voucher_id')
                ->leftJoin('applicant_program_applications as app', 'app.id', '=', 'v.applicant_program_application_id')
                ->where('p.tenant_id', $tenantId)
                ->where('app.applicant_id', $applicantId)
                ->whereNull('p.deleted_at')
                ->select([
                    'p.id',
                    'p.applicant_fee_voucher_id',
                    'v.voucher_no',
                    'p.payment_method_code',
                    'p.payment_reference_no',
                    'p.payment_date',
                    'p.amount',
                    'p.status_code as payment_status_code',
                    'p.verified_at',
                    'p.verified_by',
                    'p.rejection_reason',
                    'p.remarks',
                ])
                ->orderByDesc('p.id')
                ->get();

        return ApiResponse::success([
            'applicant' => $applicant,
            'qualifications' => $qualifications,
            'documents' => $documents,
            'test_results' => $testResults,
            'applications' => $applications,
            'vouchers' => $vouchers,
            'payments' => $payments,
        ], 'Applicant progress detail fetched successfully.');
    }
}