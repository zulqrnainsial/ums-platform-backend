<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionMeritFormula extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'admission_session_id',
        'code',
        'title',
        'description',
        'formula_type_code',
        'total_weight',
        'passing_merit_score',
        'rounding_precision',
        'tie_breaker_json',
        'rules_json',
        'status_code',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_weight' => 'decimal:2',
        'passing_merit_score' => 'decimal:2',
        'rounding_precision' => 'integer',
        'tie_breaker_json' => 'array',
        'rules_json' => 'array',
    ];

    public function components()
    {
        return $this->hasMany(AdmissionMeritFormulaComponent::class, 'admission_merit_formula_id');
    }
    
    public function applicabilities()
    {
        return $this->hasMany(AdmissionMeritFormulaApplicability::class, 'admission_merit_formula_id');
    }
}