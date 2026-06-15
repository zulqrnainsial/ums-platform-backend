<?php

namespace App\Modules\Admission\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantFeeVoucher extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_fee_vouchers';

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'applicant_program_application_id',
        'voucher_type_code',
        'voucher_no',
        'issue_date',
        'due_date',
        'amount',
        'discount_amount',
        'fine_amount',
        'payable_amount',
        'paid_amount',
        'balance_amount',
        'status_code',
        'is_locked',
        'description',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'fine_amount' => 'decimal:2',
            'payable_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'is_locked' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ApplicantFeeVoucher $voucher) {
            $voucher->payable_amount = max(
                0,
                (float) $voucher->amount
                - (float) $voucher->discount_amount
                + (float) $voucher->fine_amount
            );

            $voucher->balance_amount = max(
                0,
                (float) $voucher->payable_amount - (float) $voucher->paid_amount
            );
        });
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
    
    public function payments(): HasMany
    {
        return $this->hasMany(ApplicantPayment::class);
    }
}