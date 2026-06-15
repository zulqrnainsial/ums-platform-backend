<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\ApplicantFeeVoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicantFeeVoucherController extends Controller
{
    public function __construct(
        private readonly ApplicantFeeVoucherService $service
    ) {
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'applicant_program_application_id' => [
                'required',
                'integer',
                'exists:applicant_program_applications,id',
            ],
            'voucher_type_code' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        $voucher = $this->service->generateForApplication(
            applicationId: (int) $validated['applicant_program_application_id'],
            voucherTypeCode: $validated['voucher_type_code'] ?? 'application_fee'
        );

        return ApiResponse::success(
            $this->service->formatVoucher($voucher->fresh(['payments'])),
            'Applicant fee voucher generated successfully.'
        );
    }

    public function vouchersForApplication(int $applicationId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->vouchersForApplication($applicationId),
            'Application fee vouchers fetched successfully.'
        );
    }

    public function submitPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'applicant_fee_voucher_id' => [
                'required',
                'integer',
                'exists:applicant_fee_vouchers,id',
            ],
            'payment_method_code' => ['required', 'string', 'max:50'],
            'payment_reference_no' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_proof_document_id' => [
                'nullable',
                'integer',
                'exists:applicant_documents,id',
            ],
            'remarks' => ['nullable', 'string'],
        ]);

        $payment = $this->service->submitPayment($validated);

        return ApiResponse::success(
            [
                'id' => $payment->id,
                'status_code' => $payment->status_code,
                'amount' => $payment->amount,
            ],
            'Payment submitted successfully.'
        );
    }

    public function verifyPayment(Request $request, int $paymentId): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        $payment = $this->service->verifyPayment(
            paymentId: $paymentId,
            remarks: $validated['remarks'] ?? null
        );

        return ApiResponse::success(
            [
                'id' => $payment->id,
                'status_code' => $payment->status_code,
                'verified_at' => $payment->verified_at,
            ],
            'Payment verified successfully.'
        );
    }

    public function rejectPayment(Request $request, int $paymentId): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $payment = $this->service->rejectPayment(
            paymentId: $paymentId,
            reason: $validated['rejection_reason']
        );

        return ApiResponse::success(
            [
                'id' => $payment->id,
                'status_code' => $payment->status_code,
                'rejection_reason' => $payment->rejection_reason,
            ],
            'Payment rejected successfully.'
        );
    }
}