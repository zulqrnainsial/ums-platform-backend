<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\ApplicantPayment;
use App\Modules\Admission\Services\ApplicantFeeVoucherService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApplicantPaymentVerificationService
{
    public function __construct(
        private readonly ApplicantFeeVoucherService $voucherService
    ) {
    }

    public function listPayments(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->tenantId();

        $query = ApplicantPayment::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'applicant',
                'voucher',
                'paymentProofDocument',
                'application.offeredProgram',
                'application.programQuotaSeat',
            ]);

        if (!empty($filters['status_code'])) {
            $query->where('status_code', $filters['status_code']);
        }

        if (!empty($filters['voucher_no'])) {
            $query->whereHas('voucher', function ($q) use ($filters) {
                $q->where('voucher_no', 'like', '%' . $filters['voucher_no'] . '%');
            });
        }

        if (!empty($filters['applicant_keyword'])) {
            $keyword = trim((string) $filters['applicant_keyword']);

            $query->whereHas('applicant', function ($q) use ($keyword) {
                $q->where('applicant_no', 'like', '%' . $keyword . '%')
                    ->orWhere('full_name', 'like', '%' . $keyword . '%')
                    ->orWhere('cnic_bform', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%');
            });
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('payment_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('payment_date', '<=', $filters['to_date']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(5, min($perPage, 100));

        return $query
            ->orderByRaw("FIELD(status_code, 'submitted', 'rejected', 'verified')")
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function paymentDetails(int $paymentId): array
    {
        $tenantId = $this->tenantId();

        $payment = ApplicantPayment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $paymentId)
            ->with([
                'applicant',
                'voucher.payments',
                'paymentProofDocument',
                'application.offeredProgram',
                'application.programQuotaSeat',
            ])
            ->firstOrFail();

        return $this->formatPayment($payment);
    }

    public function verify(int $paymentId, ?string $remarks = null): array
    {
        $payment = $this->voucherService->verifyPayment($paymentId, $remarks);

        return $this->paymentDetails($payment->id);
    }

    public function reject(int $paymentId, string $reason): array
    {
        $payment = $this->voucherService->rejectPayment($paymentId, $reason);

        return $this->paymentDetails($payment->id);
    }

    public function formatPayment(ApplicantPayment $payment): array
    {
        $applicant = $payment->applicant;
        $application = $payment->application;
        $voucher = $payment->voucher;
        $offeredProgram = $application?->offeredProgram;
        $quotaSeat = $application?->programQuotaSeat;
        $proofDocument = $payment->paymentProofDocument;

        return [
            'id' => $payment->id,
            'tenant_id' => $payment->tenant_id,
            'payment_method_code' => $payment->payment_method_code,
            'payment_reference_no' => $payment->payment_reference_no,
            'payment_date' => $payment->payment_date,
            'amount' => $payment->amount,
            'status_code' => $payment->status_code,
            'payment_proof_document_id' => $payment->payment_proof_document_id,
            'verified_at' => $payment->verified_at,
            'verified_by' => $payment->verified_by,
            'rejection_reason' => $payment->rejection_reason,
            'remarks' => $payment->remarks,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,

            'applicant' => $applicant ? [
                'id' => $applicant->id,
                'applicant_no' => $applicant->applicant_no,
                'full_name' => $applicant->full_name,
                'father_name' => $applicant->father_name,
                'cnic_bform' => $applicant->cnic_bform,
                'email' => $applicant->email,
                'phone' => $applicant->phone,
                'profile_status_code' => $applicant->profile_status_code,
                'applicant_status_code' => $applicant->applicant_status_code,
            ] : null,

            'application' => $application ? [
                'id' => $application->id,
                'application_no' => $application->application_no,
                'preference_order' => $application->preference_order,
                'application_status_code' => $application->application_status_code,
                'eligibility_status_code' => $application->eligibility_status_code,
                'document_status_code' => $application->document_status_code,
                'fee_status_code' => $application->fee_status_code,
                'test_status_code' => $application->test_status_code,
                'submitted_at' => $application->submitted_at,
            ] : null,

            'offered_program' => $offeredProgram ? [
                'id' => $offeredProgram->id,
                'code' => $offeredProgram->code ?? null,
                'title' => $offeredProgram->title ?? null,
                'shift_code' => $offeredProgram->shift_code ?? null,
                'application_fee' => $offeredProgram->application_fee ?? null,
                'admission_fee' => $offeredProgram->admission_fee ?? null,
            ] : null,

            'quota' => $quotaSeat ? [
                'id' => $quotaSeat->id,
                'quota_code' => $quotaSeat->quota_code ?? null,
                'quota_name' => $quotaSeat->quota_name ?? null,
                'available_seats' => $quotaSeat->available_seats ?? null,
            ] : null,

            'voucher' => $voucher ? [
                'id' => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
                'voucher_type_code' => $voucher->voucher_type_code,
                'issue_date' => $voucher->issue_date,
                'due_date' => $voucher->due_date,
                'amount' => $voucher->amount,
                'discount_amount' => $voucher->discount_amount,
                'fine_amount' => $voucher->fine_amount,
                'payable_amount' => $voucher->payable_amount,
                'paid_amount' => $voucher->paid_amount,
                'balance_amount' => $voucher->balance_amount,
                'status_code' => $voucher->status_code,
            ] : null,

            'proof_document' => $proofDocument ? [
                'id' => $proofDocument->id,
                'document_title' => $proofDocument->document_title,
                'original_file_name' => $proofDocument->original_file_name ?? null,
                'file_path' => $proofDocument->file_path ?? null,
                'download_url' => $proofDocument->download_url ?? null,
                'preview_url' => $proofDocument->preview_url ?? null,
                'verification_status_code' => $proofDocument->verification_status_code ?? null,
            ] : null,
        ];
    }

    public function formatPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())
                ->map(fn (ApplicantPayment $payment) => $this->formatPayment($payment))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
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
