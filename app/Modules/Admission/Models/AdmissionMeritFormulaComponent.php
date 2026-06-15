<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionMeritFormulaComponent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'admission_merit_formula_id',
        'code',
        'title',
        'description',
        'component_type_code',
        'source_type_code',
        'source_key',
        'calculation_method_code',
        'weight',
        'max_raw_marks',
        'normalize_to',
        'minimum_required_score',
        'is_required',
        'include_in_total',
        'allow_bonus',
        'allow_negative',
        'conditions_json',
        'source_mapping_json',
        'display_order',
        'status_code',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_raw_marks' => 'decimal:2',
        'normalize_to' => 'decimal:2',
        'minimum_required_score' => 'decimal:2',
        'is_required' => 'boolean',
        'include_in_total' => 'boolean',
        'allow_bonus' => 'boolean',
        'allow_negative' => 'boolean',
        'conditions_json' => 'array',
        'source_mapping_json' => 'array',
        'display_order' => 'integer',
    ];

    public function formula()
    {
        return $this->belongsTo(AdmissionMeritFormula::class, 'admission_merit_formula_id');
    }
}