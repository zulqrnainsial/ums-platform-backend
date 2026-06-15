<?php

namespace App\Core\Modules\Models;

use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'description',
        'icon',
        'is_core',
        'is_active',
        'display_order',
        'settings_schema',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'is_active' => 'boolean',
            'settings_schema' => 'array',
            'meta' => 'array',
            'display_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Module::class, 'parent_id')
            ->orderBy('display_order');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Module::class,
            'module_dependencies',
            'module_id',
            'depends_on_module_id'
        )->withTimestamps();
    }

    public function requiredBy(): BelongsToMany
    {
        return $this->belongsToMany(
            Module::class,
            'module_dependencies',
            'depends_on_module_id',
            'module_id'
        )->withTimestamps();
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules')
            ->withPivot([
                'is_enabled',
                'enabled_at',
                'disabled_at',
                'enabled_by',
                'disabled_by',
                'settings',
            ])
            ->withTimestamps();
    }
}