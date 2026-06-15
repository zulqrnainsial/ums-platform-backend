<?php

namespace App\Core\Dynamic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicField extends Model
{
    protected $fillable = [
        'dynamic_entity_id',
        'field_name',
        'label',
        'control_type',
        'data_type',
        'placeholder',
        'help_text',
        'is_required',
        'is_visible_in_table',
        'is_visible_in_form',
        'is_visible_in_view',
        'is_filterable',
        'is_sortable',
        'is_readonly',
        'is_unique',
        'options_source_type',
        'options_source_url',
        'options_static_json',
        'validation_rules',
        'display_rules',
        'display_order',
        'table_width',
        'relation_name',
        'relation_label_field',
        'relation_value_field',
        'default_value',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_visible_in_table' => 'boolean',
            'is_visible_in_form' => 'boolean',
            'is_visible_in_view' => 'boolean',
            'is_filterable' => 'boolean',
            'is_sortable' => 'boolean',
            'is_readonly' => 'boolean',
            'is_unique' => 'boolean',
            'options_static_json' => 'array',
            'validation_rules' => 'array',
            'display_rules' => 'array',
            'display_order' => 'integer',
            'table_width' => 'integer',
            'meta' => 'array',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(DynamicEntity::class, 'dynamic_entity_id');
    }
}