<?php

namespace App\Core\Dynamic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicAction extends Model
{
    protected $fillable = [
        'dynamic_entity_id',
        'action_name',
        'label',
        'action_type',
        'placement',
        'permission_name',
        'http_method',
        'api_endpoint',
        'frontend_route',
        'icon',
        'color',
        'confirmation_required',
        'confirmation_title',
        'confirmation_message',
        'is_active',
        'display_order',
        'visible_when',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'confirmation_required' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'visible_when' => 'array',
            'meta' => 'array',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(DynamicEntity::class, 'dynamic_entity_id');
    }
}