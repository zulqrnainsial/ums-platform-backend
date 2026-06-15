<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\ApplicantPaymentVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicantPaymentVerificationController extends Controller
{
    public function __construct(
        private readonly ApplicantPaymentVerificationService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status_code' => ['nullable', 'string', 'max:50'],
            'voucher_no' => ['nullable', 'string', 'max:100'],
            'applicant_keyword' => ['nullable', 'string', 'max:150'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $paginator = $this->service->listPayments($validated);

        return ApiResponse::success(
            $this->service->formatPaginator($paginator),
            'Applicant payments fetched successfully.'
        );
    }

    public function show(int $paymentId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->paymentDetails($paymentId),
            'Applicant payment details fetched successfully.'
        );
    }

    public function verify(Request $request, int $paymentId): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->verify($paymentId, $validated['remarks'] ?? null),
            'Payment verified successfully.'
        );
    }

    public function reject(Request $request, int $paymentId): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->reject($paymentId, $validated['rejection_reason']),
            'Payment rejected successfully.'
        );
    }
}
