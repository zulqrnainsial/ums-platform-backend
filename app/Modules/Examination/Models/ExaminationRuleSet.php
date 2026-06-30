<?php

namespace App\Modules\Examination\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExaminationRuleSet extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'examination_rule_sets';

    protected $fillable = [
        'tenant_id',
        'rule_set_code',
        'rule_set_name',
        'description',
        'gpa_enabled',
        'obe_enabled',
        'grading_method_code',
        'marks_basis_code',
        'marks_per_credit_hour',
        'fixed_total_marks',
        'theory_practical_evaluation_code',
        'subject_pass_basis_code',
        'minimum_subject_percentage',
        'minimum_theory_percentage',
        'minimum_practical_percentage',
        'promotion_enabled',
        'probation_enabled',
        'detention_enabled',
        'drop_enabled',
        'minimum_semester_gpa',
        'minimum_cgpa',
        'maximum_failed_courses',
        'maximum_attempts_per_subject',
        'maximum_probation_terms',
        're_registration_enabled',
        'improvement_enabled',
        'improvement_allowed_below_grade_point',
        'transcript_enabled',
        'include_obe_in_result_decision',
        'status_code',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'gpa_enabled' => 'boolean',
        'obe_enabled' => 'boolean',
        'promotion_enabled' => 'boolean',
        'probation_enabled' => 'boolean',
        'detention_enabled' => 'boolean',
        'drop_enabled' => 'boolean',
        're_registration_enabled' => 'boolean',
        'improvement_enabled' => 'boolean',
        'transcript_enabled' => 'boolean',
        'include_obe_in_result_decision' => 'boolean',

        'marks_per_credit_hour' => 'decimal:2',
        'fixed_total_marks' => 'decimal:2',
        'minimum_subject_percentage' => 'decimal:2',
        'minimum_theory_percentage' => 'decimal:2',
        'minimum_practical_percentage' => 'decimal:2',
        'minimum_semester_gpa' => 'decimal:2',
        'minimum_cgpa' => 'decimal:2',
        'improvement_allowed_below_grade_point' => 'decimal:2',
    ];

    public function bindings()
    {
        return $this->hasMany(ExaminationRuleSetBinding::class);
    }

    public function gradingSchemes()
    {
        return $this->hasMany(GradingScheme::class);
    }

    public function evaluationSchemes()
    {
        return $this->hasMany(EvaluationScheme::class);
    }
    
}