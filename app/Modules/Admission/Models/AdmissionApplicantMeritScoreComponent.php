<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionApplicantMeritScoreComponent extends Model
{

    protected $fillable = [
        'tenant_id',
        'admission_applicant_merit_score_id',
        'admission_merit_formula_component_id',
        'component_code',
        'component_title',
        'component_type_code',
        'source_type_code',
        'source_key',
        'calculation_method_code',
        'raw_obtained_marks',
        'raw_total_marks',
        'raw_percentage',
        'normalized_score',
        'component_weight',
        'weighted_score',
        'is_required',
        'is_component_passed',
        'include_in_total',
        'source_record_json',
        'calculation_detail_json',
        'status_code',
    ];

    protected $casts = [
        'raw_obtained_marks' => 'decimal:4',
        'raw_total_marks' => 'decimal:4',
        'raw_percentage' => 'decimal:4',
        'normalized_score' => 'decimal:4',
        'component_weight' => 'decimal:4',
        'weighted_score' => 'decimal:4',
        'is_required' => 'boolean',
        'is_component_passed' => 'boolean',
        'include_in_total' => 'boolean',
        'source_record_json' => 'array',
        'calculation_detail_json' => 'array',
    ];

    public function meritScore()
    {
        return $this->belongsTo(AdmissionApplicantMeritScore::class, 'admission_applicant_merit_score_id');
    }

    public function formulaComponent()
    {
        return $this->belongsTo(AdmissionMeritFormulaComponent::class, 'admission_merit_formula_component_id');
    }
}