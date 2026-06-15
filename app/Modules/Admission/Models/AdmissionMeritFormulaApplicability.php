<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionMeritFormulaApplicability extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'admission_merit_formula_id',
        'applicability_scope_code',
        'admission_session_id',
        'admission_preference_group_id',
        'offered_program_id',
        'program_quota_seat_id',
        'effective_from',
        'effective_to',
        'is_default',
        'priority',
        'status_code',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_default' => 'boolean',
        'priority' => 'integer',
    ];

    public function formula()
    {
        return $this->belongsTo(AdmissionMeritFormula::class, 'admission_merit_formula_id');
    }
}