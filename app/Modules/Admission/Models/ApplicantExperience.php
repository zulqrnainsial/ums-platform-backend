<?php

namespace App\Modules\Admission\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantExperience extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_experiences';

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'organization_name',
        'designation',
        'employment_type_id',
        'experience_area_id',
        'from_date',
        'to_date',
        'currently_working',
        'total_months',
        'status_code',
        'job_description',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'currently_working' => 'boolean',
            'total_months' => 'integer',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'employment_type_id');
    }

    public function experienceArea(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'experience_area_id');
    }
}