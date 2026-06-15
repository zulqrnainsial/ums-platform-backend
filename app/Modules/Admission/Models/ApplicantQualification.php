<?php

namespace App\Modules\Admission\Models;

use App\Modules\Lookup\Models\LookupValue;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantQualification extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'applicant_qualifications';

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'qualification_level_id',
        'education_board_id',
        'external_institution_id',
        'subject_group_id',
        'degree_class_name',
        'roll_no',
        'registration_no',
        'passing_year',
        'result_status_code',
        'total_marks',
        'obtained_marks',
        'percentage',
        'cgpa',
        'cgpa_scale',
        'grade',
        'equivalence_required',
        'equivalence_status_code',
        'is_final_result',
        'is_verified',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_marks' => 'decimal:2',
            'obtained_marks' => 'decimal:2',
            'percentage' => 'decimal:2',
            'cgpa' => 'decimal:2',
            'cgpa_scale' => 'decimal:2',
            'equivalence_required' => 'boolean',
            'is_final_result' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function qualificationLevel(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'qualification_level_id');
    }

    public function educationBoard(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'education_board_id');
    }

    public function externalInstitution(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'external_institution_id');
    }

    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'subject_group_id');
    }
    
    public function subjects(): HasMany
    {
        return $this->hasMany(ApplicantQualificationSubject::class);
    }
}