<?php

namespace App\Modules\Admission\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantResearchProfile extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_research_profiles';

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'research_area_id',
        'proposed_research_title',
        'statement_of_purpose',
        'research_interests',
        'preferred_supervisor_name',
        'preferred_supervisor_email',
        'status_code',
        'remarks',
        'created_by',
        'updated_by',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function researchArea(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'research_area_id');
    }
}