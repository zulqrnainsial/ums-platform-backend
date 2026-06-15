<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\ApplicantFeeVoucher;
use App\Modules\Admission\Models\ApplicantPayment;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use Illuminate\Support\Facades\DB;

class ApplicantFeeVoucherService
{
    public function generateForApplication(
        int $applicationId,
        string $voucherTypeCode = 'application_fee'
    ): ApplicantFeeVoucher {
        return DB::transaction(function () use ($applicationId, $voucherTypeCode) {
            $tenantId = $this->tenantId();

            $application = ApplicantProgramApplication::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $applicationId)
                ->with(['offeredProgram', 'programQuotaSeat'])
                ->firstOrFail();

            $existing = ApplicantFeeVoucher::query()
                ->where('tenant_id', $tenantId)
                ->where('applicant_program_application_id', $application->id)
                ->where('voucher_type_code', $voucherTypeCode)
                ->first();

            if ($existing) {
                return $existing;
            }

            $amount = $this->resolveAmount($application, $voucherTypeCode);

            $voucher = ApplicantFeeVoucher::create([
                'tenant_id' => $tenantId,
                'applicant_id' => $application->applicant_id,
                'applicant_program_application_id' => $application->id,
                'voucher_type_code' => $voucherTypeCode,
                'voucher_no' => $this->generateVoucherNo($tenantId, $application->admission_session_id),
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'amount' => $amount,
                'discount_amount' => 0,
                'fine_amount' => 0,
                'paid_amount' => 0,
                'status_code' => $amount > 0 ? 'unpaid' : 'paid',
                'is_locked' => false,
                'description' => $this->voucherDescription($voucherTypeCode),
                'remarks' => null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $application->update([
                'fee_status_code' => $amount > 0 ? 'unpaid' : 'not_required',
                'updated_by' => auth()->id(),
            ]);

            return $voucher->fresh();
        });
    }

    public function submitPayment(array $data): ApplicantPayment
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $this->tenantId();

            $voucher = ApplicantFeeVoucher::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['applicant_fee_voucher_id'])
                ->with(['application'])
                ->firstOrFail();

            if (in_array($voucher->status_code, ['verified', 'cancelled', 'expired'], true)) {
                abort(422, 'Payment cannot be submitted for this voucher status.');
            }

            $payment = ApplicantPayment::create([
                'tenant_id' => $tenantId,
                'applicant_id' => $voucher->applicant_id,
                'applicant_program_application_id' => $voucher->applicant_program_application_id,
                'applicant_fee_voucher_id' => $voucher->id,
                'payment_method_code' => $data['payment_method_code'],
                'payment_reference_no' => $data['payment_reference_no'] ?? null,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $data['amount'],
                'status_code' => 'submitted',
                'payment_proof_document_id' => $data['payment_proof_document_id'] ?? null,
                'verified_at' => null,
                'verified_by' => null,
                'rejection_reason' => null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->refreshVoucherPaymentStatus($voucher);

            return $payment->fresh();
        });
    }

    public function verifyPayment(int $paymentId, ?string $remarks = null): ApplicantPayment
    {
        return DB::transaction(function () use ($paymentId, $remarks) {
            $tenantId = $this->tenantId();

            $payment = ApplicantPayment::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $paymentId)
                ->with(['voucher.application'])
                ->firstOrFail();

            if ($payment->status_code !== 'submitted') {
                abort(422, 'Only submitted payments can be verified.');
            }

            $payment->update([
                'status_code' => 'verified',
                'verified_at' => now(),
                'verified_by' => auth()->id(),
                'remarks' => $remarks ?? $payment->remarks,
                'updated_by' => auth()->id(),
            ]);

            $this->refreshVoucherPaymentStatus($payment->voucher);

            return $payment->fresh();
        });
    }

    public function rejectPayment(int $paymentId, string $reason): ApplicantPayment
    {
        return DB::transaction(function () use ($paymentId, $reason) {
            $tenantId = $this->tenantId();

            $payment = ApplicantPayment::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $paymentId)
                ->with(['voucher.application'])
                ->firstOrFail();

            if ($payment->status_code !== 'submitted') {
                abort(422, 'Only submitted payments can be rejected.');
            }

            $payment->update([
                'status_code' => 'rejected',
                'rejection_reason' => $reason,
                'updated_by' => auth()->id(),
            ]);

            $this->refreshVoucherPaymentStatus($payment->voucher);

            return $payment->fresh();
        });
    }

    public function vouchersForApplication(int $applicationId): array
    {
        $tenantId = $this->tenantId();

        return ApplicantFeeVoucher::query()
            ->where('tenant_id', $tenantId)
            ->where('applicant_program_application_id', $applicationId)
            ->with(['payments'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (ApplicantFeeVoucher $voucher) => $this->formatVoucher($voucher))
            ->values()
            ->toArray();
    }

    public function formatVoucher(ApplicantFeeVoucher $voucher): array
    {
        return [
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
            'is_locked' => $voucher->is_locked,
            'payments' => $voucher->payments?->map(fn (ApplicantPayment $payment) => [
                'id' => $payment->id,
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
            ])->values() ?? [],
        ];
    }

    private function refreshVoucherPaymentStatus(ApplicantFeeVoucher $voucher): void
    {
        $verifiedPaid = ApplicantPayment::query()
            ->where('tenant_id', $voucher->tenant_id)
            ->where('applicant_fee_voucher_id', $voucher->id)
            ->where('status_code', 'verified')
            ->sum('amount');

        $voucher->paid_amount = $verifiedPaid;

        if ((float) $verifiedPaid <= 0) {
            $voucher->status_code = 'unpaid';
        } elseif ((float) $verifiedPaid < (float) $voucher->payable_amount) {
            $voucher->status_code = 'partially_paid';
        } else {
            $voucher->status_code = 'verified';
        }

        $voucher->updated_by = auth()->id();
        $voucher->save();

        $application = $voucher->application;

        if ($application) {
            $application->update([
                'fee_status_code' => $voucher->status_code,
                'updated_by' => auth()->id(),
            ]);
        }
    }

    private function resolveAmount(
        ApplicantProgramApplication $application,
        string $voucherTypeCode
    ): float {
        if ($voucherTypeCode === 'application_fee') {
            return (float) (
                $application->programQuotaSeat?->application_fee
                ?? $application->offeredProgram?->application_fee
                ?? 0
            );
        }

        if ($voucherTypeCode === 'admission_fee') {
            return (float) (
                $application->programQuotaSeat?->admission_fee
                ?? $application->offeredProgram?->admission_fee
                ?? 0
            );
        }

        return 0;
    }

    private function voucherDescription(string $voucherTypeCode): string
    {
        return match ($voucherTypeCode) {
            'application_fee' => 'Application processing fee voucher.',
            'admission_fee' => 'Admission confirmation fee voucher.',
            default => 'Applicant fee voucher.',
        };
    }

    private function generateVoucherNo(int $tenantId, int $admissionSessionId): string
    {
        $count = ApplicantFeeVoucher::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('application', function ($query) use ($admissionSessionId) {
                $query->where('admission_session_id', $admissionSessionId);
            })
            ->count() + 1;

        return 'VCH-' . $admissionSessionId . '-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
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