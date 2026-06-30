<?php

namespace App\Modules\Examination\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationScheme extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'evaluation_schemes';

    protected $fillable = [
        'tenant_id',
        'examination_rule_set_id',
        'scheme_code',
        'scheme_name',
        'evaluation_mode_code',
        'total_weightage_percentage',
        'status_code',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_weightage_percentage' => 'decimal:2',
    ];

    public function ruleSet()
    {
        return $this->belongsTo(ExaminationRuleSet::class, 'examination_rule_set_id');
    }
    public function components()
    {
        return $this->hasMany(EvaluationSchemeComponent::class)
            ->orderBy('sort_order');
    }
}