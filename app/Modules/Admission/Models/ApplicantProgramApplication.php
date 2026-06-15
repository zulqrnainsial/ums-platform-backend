<?php

namespace App\Modules\Admission\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantProgramApplication extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_program_applications';

    protected $fillable = [
        'tenant_id',
        'admission_session_id',
        'applicant_id',
        'applicant_application_group_id',
        'offered_program_id',
        'program_quota_seat_id',
        'application_no',
        'preference_order',
        'eligibility_status_code',
        'eligibility_result_json',
        'eligibility_remarks',
        'application_status_code',
        'document_status_code',
        'fee_status_code',
        'test_status_code',
        'merit_score',
        'merit_rank',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'confirmed_at',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'preference_order' => 'integer',
            'eligibility_result_json' => 'array',
            'merit_score' => 'decimal:4',
            'merit_rank' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function admissionSession(): BelongsTo
    {
        return $this->belongsTo(AdmissionSession::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function applicationGroup(): BelongsTo
    {
        return $this->belongsTo(
            ApplicantApplicationGroup::class,
            'applicant_application_group_id'
        );
    }

    public function offeredProgram(): BelongsTo
    {
        return $this->belongsTo(OfferedProgram::class);
    }

    public function programQuotaSeat(): BelongsTo
    {
        return $this->belongsTo(ProgramQuotaSeat::class);
    }

    public function feeVouchers(): HasMany
    {
        return $this->hasMany(
            ApplicantFeeVoucher::class,
            'applicant_program_application_id'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            ApplicantPayment::class,
            'applicant_program_application_id'
        );
    }
}
