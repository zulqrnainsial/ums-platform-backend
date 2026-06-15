<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionApplicantMeritScore extends Model
{

    protected $fillable = [
        'tenant_id',
        'applicant_id',
        'admission_application_id',
        'admission_session_id',
        'offered_program_id',
        'admission_preference_group_id',
        'program_quota_seat_id',
        'admission_merit_formula_id',
        'total_component_weight',
        'total_weighted_score',
        'bonus_score',
        'deduction_score',
        'final_merit_score',
        'is_eligible_for_merit',
        'failed_required_components_json',
        'calculation_snapshot_json',
        'status_code',
        'calculated_at',
        'calculated_by',
    ];

    protected $casts = [
        'total_component_weight' => 'decimal:2',
        'total_weighted_score' => 'decimal:4',
        'bonus_score' => 'decimal:4',
        'deduction_score' => 'decimal:4',
        'final_merit_score' => 'decimal:4',
        'is_eligible_for_merit' => 'boolean',
        'failed_required_components_json' => 'array',
        'calculation_snapshot_json' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function components()
    {
        return $this->hasMany(AdmissionApplicantMeritScoreComponent::class, 'admission_applicant_merit_score_id');
    }
    public function formula()
    {
        return $this->belongsTo(AdmissionMeritFormula::class, 'admission_merit_formula_id');
    }
}