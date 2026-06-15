<?php

namespace App\Core\Dynamic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Dynamic\MOdels\DynamicField;
use App\Core\Dynamic\MOdels\DynamicAction;
use App\Core\Dynamic\MOdels\DynamicFilter;
class DynamicEntity extends Model
{
    protected $fillable = [
        'module_name',
        'entity_name',
        'entity_code',
        'table_name',
        'model_class',
        'api_endpoint',
        'title',
        'subtitle',
        'is_tenant_scoped',
        'is_system',
        'is_active',
        'default_sort',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_tenant_scoped' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'default_sort' => 'array',
            'meta' => 'array',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DynamicField::class)
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(DynamicAction::class)
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function filters(): HasMany
    {
        return $this->hasMany(DynamicFilter::class)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id');
    }
}