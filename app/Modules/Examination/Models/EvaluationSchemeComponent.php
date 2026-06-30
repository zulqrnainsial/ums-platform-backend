<?php

namespace App\Modules\Examination\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationSchemeComponent extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'evaluation_scheme_components';

    protected $fillable = [
        'tenant_id',
        'evaluation_scheme_id',
        'component_code',
        'component_name',
        'component_type_code',
        'evaluation_part_code',
        'weightage_percentage',
        'is_mandatory',
        'requires_separate_pass',
        'sort_order',
        'status_code',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'weightage_percentage' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'requires_separate_pass' => 'boolean',
    ];

    public function evaluationScheme()
    {
        return $this->belongsTo(EvaluationScheme::class);
    }
    public function items()
    {
        return $this->hasMany(EvaluationSchemeComponentItem::class)
            ->orderBy('sort_order');
    }
}