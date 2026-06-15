<?php

namespace App\Core\Menu\Models;

use App\Core\Modules\Models\Module;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'tenant_id',
        'parent_id',
        'module_id',
        'title',
        'code',
        'route',
        'icon',
        'permission_name',
        'is_system',
        'is_active',
        'display_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')
            ->orderBy('display_order')
            ->orderBy('title');
    }

    public function activeChildren(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('title');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}