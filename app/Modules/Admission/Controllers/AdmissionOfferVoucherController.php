<?php

namespace App\Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionOfferVoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        if (!Schema::hasTable('admission_offer_fee_vouchers')) {
            return response()->json([
                'data' => [],
                'message' => 'Voucher table not found.',
            ]);
        }

        $query = DB::table('admission_offer_fee_vouchers as v')
            ->leftJoin('applicants as a', 'a.id', '=', 'v.applicant_id')
            ->leftJoin('admission_merit_list_applicants as mla', 'mla.id', '=', 'v.admission_merit_list_applicant_id')
            ->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'mla.admission_merit_list_id');

        if ($tenantId && Schema::hasColumn('admission_offer_fee_vouchers', 'tenant_id')) {
            $query->where('v.tenant_id', $tenantId);
        }

        if ($request->filled('applicant_id')) {
            $query->where('v.applicant_id', (int) $request->query('applicant_id'));
        }

        if ($request->filled('status_code')) {
            $query->where('v.status_code', $request->query('status_code'));
        }

        $items = $query
            ->select([
                'v.*',
                'a.applicant_no',
                DB::raw("CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, '')) as applicant_name"),
                'ml.list_no',
                'ml.title as merit_list_title',
            ])
            ->orderByDesc('v.id')
            ->limit(200)
            ->get();

        return response()->json([
            'data' => $items,
            'message' => 'Offer vouchers fetched successfully.',
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'admission_merit_list_applicant_id' => ['required', 'integer'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]);

        $tenantId = $request->user()?->tenant_id;
        $meritListApplicantId = (int) $request->input('admission_merit_list_applicant_id');

        if (!Schema::hasTable('admission_merit_list_applicants')) {
            return response()->json(['message' => 'Merit list applicant table not found.'], 422);
        }

        if (!Schema::hasTable('admission_offer_fee_vouchers')) {
            return response()->json(['message' => 'Voucher table not found.'], 422);
        }

        return DB::transaction(function () use ($request, $tenantId, $meritListApplicantId) {
            $offer = DB::table('admission_merit_list_applicants as mla')
                ->leftJoin('admission_merit_lists as ml', 'ml.id', '=', 'mla.admission_merit_list_id')
                ->leftJoin('offered_programs as op', 'op.id', '=', 'ml.offered_program_id')
                ->where('mla.id', $meritListApplicantId)
                ->when($tenantId, fn ($q) => $q->where('mla.tenant_id', $tenantId))
                ->select([
                    'mla.*',
                    'ml.admission_session_id',
                    'ml.offered_program_id',
                    'ml.program_quota_seat_id',
                    'op.admission_fee',
                    'op.application_fee',
                ])
                ->first();

            if (!$offer) {
                return response()->json(['message' => 'Offer record not found.'], 404);
            }

            if (($offer->selection_status_code ?? null) !== 'selected') {
                return response()->json(['message' => 'Voucher can only be generated for selected applicants.'], 422);
            }

            if (($offer->offer_status_code ?? null) !== 'accepted') {
                return response()->json(['message' => 'Voucher can only be generated after applicant accepts the offer.'], 422);
            }

            $existing = DB::table('admission_offer_fee_vouchers')
                ->where('admission_merit_list_applicant_id', $meritListApplicantId)
                ->when($tenantId && Schema::hasColumn('admission_offer_fee_vouchers', 'tenant_id'), fn ($q) => $q->where('tenant_id', $tenantId))
                ->first();

            if ($existing) {
                return response()->json([
                    'data' => $existing,
                    'message' => 'Voucher already generated.',
                ]);
            }

            $amount = $request->input('amount');

            if ($amount === null) {
                $amount = $offer->admission_fee ?? $offer->application_fee ?? 0;
            }

            $voucherNo = 'ADM-VCH-' . now()->format('YmdHis') . '-' . $offer->applicant_id;

            $payload = $this->filterColumns('admission_offer_fee_vouchers', [
                'tenant_id' => $tenantId,
                'voucher_no' => $voucherNo,
                'applicant_id' => $offer->applicant_id,
                'admission_merit_list_applicant_id' => $meritListApplicantId,
                'admission_merit_list_id' => $offer->admission_merit_list_id,
                'admission_session_id' => $offer->admission_session_id ?? null,
                'offered_program_id' => $offer->offered_program_id ?? null,
                'program_quota_seat_id' => $offer->program_quota_seat_id ?? null,
                'amount' => (float) $amount,
                'currency_code' => 'PKR',
                'due_date' => $request->input('due_date') ?: now()->addDays(7)->toDateString(),
                'status_code' => 'unpaid',
                'remarks' => 'Generated from accepted admission offer.',
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $voucherId = DB::table('admission_offer_fee_vouchers')->insertGetId($payload);

            if (Schema::hasColumn('admission_merit_list_applicants', 'voucher_status_code')) {
                DB::table('admission_merit_list_applicants')
                    ->where('id', $meritListApplicantId)
                    ->update($this->filterColumns('admission_merit_list_applicants', [
                        'voucher_status_code' => 'generated',
                        'updated_at' => now(),
                    ]));
            }

            $voucher = DB::table('admission_offer_fee_vouchers')->where('id', $voucherId)->first();

            return response()->json([
                'data' => $voucher,
                'message' => 'Admission fee voucher generated successfully.',
            ]);
        });
    }
public function verifyPayment(Request $request, int $voucherId): JsonResponse
{
    $tenantId = $request->user()?->tenant_id;

    return DB::transaction(function () use ($request, $tenantId, $voucherId) {
        $voucher = DB::table('admission_offer_fee_vouchers')
            ->where('id', $voucherId)
            ->when($tenantId && Schema::hasColumn('admission_offer_fee_vouchers', 'tenant_id'), fn ($q) => $q->where('tenant_id', $tenantId))
            ->first();

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found.'], 404);
        }

        if (($voucher->status_code ?? null) !== 'payment_submitted') {
            return response()->json([
                'message' => 'Only submitted payments can be verified.',
            ], 422);
        }

        DB::table('admission_offer_fee_vouchers')
            ->where('id', $voucherId)
            ->update($this->filterColumns('admission_offer_fee_vouchers', [
                'status_code' => 'paid',
                'verified_at' => now(),
                'verified_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
                'updated_at' => now(),
            ]));

        DB::table('admission_merit_list_applicants')
            ->where('id', $voucher->admission_merit_list_applicant_id)
            ->update($this->filterColumns('admission_merit_list_applicants', [
                'voucher_status_code' => 'paid',
                'updated_at' => now(),
            ]));

        $updated = DB::table('admission_offer_fee_vouchers')
            ->where('id', $voucherId)
            ->first();

        return response()->json([
            'data' => $updated,
            'message' => 'Payment verified successfully.',
        ]);
    });
}
    public function markPaid(Request $request, int $voucherId): JsonResponse
    {
        $request->validate([
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $tenantId = $request->user()?->tenant_id;

        if (!Schema::hasTable('admission_offer_fee_vouchers')) {
            return response()->json(['message' => 'Voucher table not found.'], 422);
        }

        return DB::transaction(function () use ($request, $tenantId, $voucherId) {
            $voucher = DB::table('admission_offer_fee_vouchers')
                ->where('id', $voucherId)
                ->when($tenantId && Schema::hasColumn('admission_offer_fee_vouchers', 'tenant_id'), fn ($q) => $q->where('tenant_id', $tenantId))
                ->first();

            if (!$voucher) {
                return response()->json(['message' => 'Voucher not found.'], 404);
            }

            if (($voucher->status_code ?? null) === 'paid') {
                return response()->json([
                    'data' => $voucher,
                    'message' => 'Voucher is already paid.',
                ]);
            }

            $paidAmount = $request->input('paid_amount', $voucher->amount ?? 0);

            DB::table('admission_offer_fee_vouchers')
                ->where('id', $voucherId)
                ->update($this->filterColumns('admission_offer_fee_vouchers', [
                    'status_code' => 'paid',
                    'paid_amount' => (float) $paidAmount,
                    'paid_at' => now(),
                    'payment_reference' => $request->input('payment_reference'),
                    'remarks' => $request->input('remarks', 'Marked paid by admin.'),
                    'updated_by' => $request->user()?->id,
                    'updated_at' => now(),
                ]));

            if (
                Schema::hasTable('admission_merit_list_applicants') &&
                Schema::hasColumn('admission_merit_list_applicants', 'voucher_status_code')
            ) {
                DB::table('admission_merit_list_applicants')
                    ->where('id', $voucher->admission_merit_list_applicant_id)
                    ->update($this->filterColumns('admission_merit_list_applicants', [
                        'voucher_status_code' => 'paid',
                        'updated_at' => now(),
                    ]));
            }

            $updated = DB::table('admission_offer_fee_vouchers')->where('id', $voucherId)->first();

            return response()->json([
                'data' => $updated,
                'message' => 'Voucher marked paid successfully.',
            ]);
        });
    }

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->toArray();
    }
}