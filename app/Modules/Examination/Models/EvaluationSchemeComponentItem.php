<?php

namespace App\Modules\Examination\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationSchemeComponentItem extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'evaluation_scheme_component_items';

    protected $fillable = [
        'tenant_id',
        'evaluation_scheme_component_id',
        'item_code',
        'item_name',
        'item_type_code',
        'weightage_percentage',
        'is_mandatory',
        'sort_order',
        'status_code',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'weightage_percentage' => 'decimal:2',
        'is_mandatory' => 'boolean',
    ];

    public function component()
    {
        return $this->belongsTo(EvaluationSchemeComponent::class, 'evaluation_scheme_component_id');
    }
    
}