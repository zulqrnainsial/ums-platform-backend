<?php

namespace App\Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EligibilityRuleType extends Model
{
    use SoftDeletes;

    protected $table = 'eligibility_rule_types';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'source_area',
        'source_collection',
        'source_field',
        'expected_value_type',
        'evaluator_key',
        'is_system',
        'is_active',
        'display_order',
        'description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function programEligibilityRules(): HasMany
    {
        return $this->hasMany(ProgramEligibilityRule::class);
    }
    
}