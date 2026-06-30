<?php

namespace App\Modules\Examination\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradingScheme extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'grading_schemes';

    protected $fillable = [
        'tenant_id',
        'examination_rule_set_id',
        'scheme_code',
        'scheme_name',
        'grading_method_code',
        'is_default',
        'status_code',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function ruleSet()
    {
        return $this->belongsTo(ExaminationRuleSet::class, 'examination_rule_set_id');
    }

    public function rows()
    {
        return $this->hasMany(GradingSchemeRow::class)
            ->orderBy('sort_order');
    }
}