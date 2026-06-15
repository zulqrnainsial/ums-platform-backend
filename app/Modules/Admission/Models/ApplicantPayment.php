<?php

namespace App\Modules\Admission\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantPayment extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_payments';

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'applicant_program_application_id',
        'applicant_fee_voucher_id',
        'payment_method_code',
        'payment_reference_no',
        'payment_date',
        'amount',
        'status_code',
        'payment_proof_document_id',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(
            ApplicantProgramApplication::class,
            'applicant_program_application_id'
        );
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(
            ApplicantFeeVoucher::class,
            'applicant_fee_voucher_id'
        );
    }

    public function paymentProofDocument(): BelongsTo
    {
        return $this->belongsTo(
            ApplicantDocument::class,
            'payment_proof_document_id'
        );
    }
}