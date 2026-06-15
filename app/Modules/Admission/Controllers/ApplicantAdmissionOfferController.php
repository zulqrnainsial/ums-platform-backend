<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\ApplicantAdmissionOfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplicantAdmissionOfferController extends Controller
{
    public function __construct(
        private readonly ApplicantAdmissionOfferService $service
    ) {
    }

    public function myOffers(): JsonResponse
    {
        return ApiResponse::success(
            $this->service->myOffers(),
            'Applicant admission offers fetched successfully.'
        );
    }

    public function accept(Request $request, int $meritListApplicantId): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->accept($meritListApplicantId, $validated),
            'Admission offer accepted successfully.'
        );
    }

    public function reject(Request $request, int $meritListApplicantId): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->reject($meritListApplicantId, $validated),
            'Admission offer rejected successfully.'
        );
    }
    private function filterColumns(string $table, array $payload): array
{
    return collect($payload)
        ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
        ->toArray();
}
    public function submitPayment(Request $request, int $meritListApplicantId): JsonResponse
{
    $request->validate([
        'payment_reference' => ['required', 'string', 'max:150'],
        'paid_amount' => ['required', 'numeric', 'min:1'],
        'paid_at' => ['nullable', 'date'],
        'payment_method_code' => ['nullable', 'string', 'max:50'],
        'remarks' => ['nullable', 'string'],
    ]);

    $user = $request->user();

    $applicant = DB::table('applicants')
        ->where('user_id', $user->id)
        ->first();

    if (!$applicant) {
        return response()->json([
            'message' => 'Applicant context not found.',
        ], 422);
    }

    $offer = DB::table('admission_merit_list_applicants')
        ->where('id', $meritListApplicantId)
        ->where('tenant_id', $applicant->tenant_id)
        ->where('applicant_id', $applicant->id)
        ->first();

    if (!$offer) {
        return response()->json([
            'message' => 'Admission offer not found.',
        ], 404);
    }

    if (($offer->offer_status_code ?? null) !== 'accepted') {
        return response()->json([
            'message' => 'Payment can only be submitted after accepting the offer.',
        ], 422);
    }

    $voucher = DB::table('admission_offer_fee_vouchers')
        ->where('admission_merit_list_applicant_id', $meritListApplicantId)
        ->where('tenant_id', $applicant->tenant_id)
        ->orderByDesc('id')
        ->first();

    if (!$voucher) {
        return response()->json([
            'message' => 'Admission fee voucher not found.',
        ], 404);
    }

    if (($voucher->status_code ?? null) === 'paid') {
        return response()->json([
            'message' => 'This voucher is already paid and verified.',
        ], 422);
    }

    DB::table('admission_offer_fee_vouchers')
        ->where('id', $voucher->id)
        ->update($this->filterColumns('admission_offer_fee_vouchers', [
            'status_code' => 'payment_submitted',
            'paid_amount' => (float) $request->input('paid_amount'),
            'paid_at' => $request->input('paid_at') ?: now(),
            'payment_reference' => $request->input('payment_reference'),
            'payment_method_code' => $request->input('payment_method_code'),
            'remarks' => $request->input('remarks', 'Payment submitted by applicant.'),
            'updated_at' => now(),
        ]));

    DB::table('admission_merit_list_applicants')
        ->where('id', $meritListApplicantId)
        ->update($this->filterColumns('admission_merit_list_applicants', [
            'voucher_status_code' => 'payment_submitted',
            'updated_at' => now(),
        ]));

    return response()->json([
        'message' => 'Payment submitted successfully. Please wait for admin verification.',
    ]);
}
}