<?php

namespace App\Modules\Admission\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
class Applicant extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicants';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'applicant_no',
        'application_account_no',
        'first_name',
        'last_name',
        'full_name',
        'father_name',
        'mother_name',
        'cnic_bform',
        'passport_no',
        'date_of_birth',
        'gender',
        'nationality_id',
        'religion_id',
        'blood_group_id',
        'email',
        'phone',
        'alternate_phone',
        'current_address',
        'permanent_address',
        'country_id',
        'province_id',
        'city_id',
        'domicile_province_id',
        'domicile_district_id',
        'has_disability',
        'disability_type_id',
        'has_experience',
        'has_research_profile',
        'has_publications',
        'photo_path',
        'profile_status_code',
        'applicant_status_code',
        'profile_completed_at',
        'submitted_at',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'has_disability' => 'boolean',
            'has_experience' => 'boolean',
            'has_research_profile' => 'boolean',
            'has_publications' => 'boolean',
            'profile_completed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Applicant $applicant) {
            $applicant->full_name = trim(
                $applicant->first_name . ' ' . ($applicant->last_name ?? '')
            );
        });
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'nationality_id');
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'religion_id');
    }

    public function bloodGroup(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'blood_group_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'country_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'province_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'city_id');
    }

    public function domicileProvince(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'domicile_province_id');
    }

    public function domicileDistrict(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'domicile_district_id');
    }

    public function disabilityType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'disability_type_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ApplicantProgramApplication::class);
    }
public function qualifications(): HasMany
{
    return $this->hasMany(ApplicantQualification::class);
}

public function experiences(): HasMany
{
    return $this->hasMany(ApplicantExperience::class);
}

public function researchProfiles(): HasMany
{
    return $this->hasMany(ApplicantResearchProfile::class);
}

public function publications(): HasMany
{
    return $this->hasMany(ApplicantPublication::class);
}

public function documents(): HasMany
{
    return $this->hasMany(ApplicantDocument::class);
}

public function testResults(): HasMany
{
    return $this->hasMany(ApplicantTestResult::class);
}

public function profileStepStatuses(): HasMany
{
    return $this->hasMany(ApplicantProfileStepStatus::class);
}   
public function feeVouchers(): HasMany
{
    return $this->hasMany(ApplicantFeeVoucher::class);
}

public function payments(): HasMany
{
    return $this->hasMany(ApplicantPayment::class);
}
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
}