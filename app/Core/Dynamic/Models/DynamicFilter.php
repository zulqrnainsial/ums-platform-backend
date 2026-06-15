<?php

namespace App\Core\Dynamic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicFilter extends Model
{
    protected $fillable = [
        'dynamic_entity_id',
        'field_name',
        'label',
        'control_type',
        'operator',
        'placeholder',
        'options_source_type',
        'options_source_url',
        'options_static_json',
        'display_order',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'options_static_json' => 'array',
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(DynamicEntity::class, 'dynamic_entity_id');
    }
}