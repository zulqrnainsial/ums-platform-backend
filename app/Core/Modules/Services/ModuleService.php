<?php

namespace App\Core\Modules\Services;

use App\Core\Modules\Models\Module;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\User;
class ModuleService
{
    public function paginate(array $filters = [], ?User $user = null): LengthAwarePaginator
{
    $query = Module::query()
        ->with(['parent', 'dependencies']);

    /*
    |--------------------------------------------------------------------------
    | Tenant module isolation
    |--------------------------------------------------------------------------
    | Super Admin can see all modules.
    | Tenant users can see only modules enabled for their tenant.
    */
    if ($user && !$user->isSuperAdmin()) {
        if (!$user->tenant_id) {
            $query->whereRaw('1 = 0');
        } else {
            $enabledModuleIds = $user->tenant?->modules()
                ->wherePivot('is_enabled', true)
                ->pluck('modules.id')
                ->toArray() ?? [];

            $query->whereIn('modules.id', $enabledModuleIds);
        }
    }

    if (!empty($filters['search'])) {
        $search = $filters['search'];

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
        $query->where('is_active', (bool) $filters['is_active']);
    }

    if (array_key_exists('is_core', $filters) && $filters['is_core'] !== null && $filters['is_core'] !== '') {
        $query->where('is_core', (bool) $filters['is_core']);
    }

    $perPage = (int) ($filters['per_page'] ?? 10);

    return $query
        ->orderBy('display_order')
        ->orderBy('name')
        ->paginate($perPage);
}

    public function allActive(?User $user = null): Collection
{
    $query = Module::query()
        ->where('is_active', true);

    if ($user && !$user->isSuperAdmin()) {
        if (!$user->tenant_id) {
            $query->whereRaw('1 = 0');
        } else {
            $enabledModuleIds = $user->tenant?->modules()
                ->wherePivot('is_enabled', true)
                ->pluck('modules.id')
                ->toArray() ?? [];

            $query->whereIn('modules.id', $enabledModuleIds);
        }
    }

    return $query
        ->orderBy('display_order')
        ->orderBy('name')
        ->get();
}

    public function create(array $data): Module
    {
        return DB::transaction(function () use ($data) {
            $dependencyIds = $data['dependency_ids'] ?? [];

            unset($data['dependency_ids']);

            $module = Module::create($data);

            $this->syncDependencies($module, $dependencyIds);

            return $module->load(['parent', 'dependencies']);
        });
    }

    public function update(Module $module, array $data): Module
    {
        return DB::transaction(function () use ($module, $data) {
            $dependencyIds = $data['dependency_ids'] ?? [];

            unset($data['dependency_ids']);

            if ($module->is_core && array_key_exists('is_core', $data) && !$data['is_core']) {
                throw ValidationException::withMessages([
                    'is_core' => ['Core module cannot be converted to non-core.'],
                ]);
            }

            $module->update($data);

            $this->syncDependencies($module, $dependencyIds);

            return $module->refresh()->load(['parent', 'dependencies']);
        });
    }

    public function delete(Module $module): bool
    {
        return DB::transaction(function () use ($module) {
            if ($module->is_core) {
                throw ValidationException::withMessages([
                    'module' => ['Core module cannot be deleted.'],
                ]);
            }

            if ($module->requiredBy()->exists()) {
                throw ValidationException::withMessages([
                    'module' => ['This module is required by another module and cannot be deleted.'],
                ]);
            }

            return $module->delete();
        });
    }

    public function activate(Module $module): Module
    {
        $module->update([
            'is_active' => true,
        ]);

        return $module->refresh()->load(['parent', 'dependencies']);
    }

    public function deactivate(Module $module): Module
    {
        if ($module->is_core) {
            throw ValidationException::withMessages([
                'module' => ['Core module cannot be deactivated.'],
            ]);
        }

        $module->update([
            'is_active' => false,
        ]);

        return $module->refresh()->load(['parent', 'dependencies']);
    }

    public function getTenantModules(Tenant $tenant): Collection
{
    return $tenant->modules()
        ->wherePivot('is_enabled', true)
        ->orderBy('display_order')
        ->orderBy('name')
        ->get();
}

    public function assignModulesToTenant(Tenant $tenant, array $moduleIds): Tenant
{
    return DB::transaction(function () use ($tenant, $moduleIds) {
        $moduleIds = $this->includeRequiredDependencies($moduleIds);

        $coreModuleIds = Module::query()
            ->where('is_core', true)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $moduleIds = array_values(array_unique(array_merge($moduleIds, $coreModuleIds)));

        $currentModuleIds = $tenant->modules()
            ->pluck('modules.id')
            ->toArray();

        $syncData = [];

        foreach ($moduleIds as $moduleId) {
            $syncData[$moduleId] = [
                'is_enabled' => true,
                'enabled_at' => now(),
                'disabled_at' => null,
                'enabled_by' => auth()->id(),
                'disabled_by' => null,
            ];
        }

        /*
         |--------------------------------------------------------------------------
         | Important:
         | sync() here is safe because it is called on $tenant->modules().
         | It only affects this tenant's pivot rows.
         |--------------------------------------------------------------------------
         */
        $tenant->modules()->sync($syncData);

        return $tenant->load('modules');
    });
}

    public function enableTenantModule(Tenant $tenant, Module $module): Tenant
    {
        return DB::transaction(function () use ($tenant, $module) {
            $moduleIds = $this->includeRequiredDependencies([$module->id]);

            foreach ($moduleIds as $moduleId) {
                $tenant->modules()->syncWithoutDetaching([
                    $moduleId => [
                        'is_enabled' => true,
                        'enabled_at' => now(),
                        'disabled_at' => null,
                        'enabled_by' => auth()->id(),
                        'disabled_by' => null,
                    ],
                ]);
            }

            return $tenant->load('modules');
        });
    }

    public function disableTenantModule(Tenant $tenant, Module $module): Tenant
    {
        return DB::transaction(function () use ($tenant, $module) {
            if ($module->is_core) {
                throw ValidationException::withMessages([
                    'module' => ['Core module cannot be disabled for tenant.'],
                ]);
            }

            $enabledModules = $tenant->modules()
                ->wherePivot('is_enabled', true)
                ->pluck('modules.id')
                ->toArray();

            foreach ($enabledModules as $enabledModuleId) {
                $enabledModule = Module::query()->find($enabledModuleId);

                if (!$enabledModule || $enabledModule->id === $module->id) {
                    continue;
                }

                $dependsOnIds = $enabledModule->dependencies()->pluck('modules.id')->toArray();

                if (in_array($module->id, $dependsOnIds)) {
                    throw ValidationException::withMessages([
                        'module' => ["Cannot disable {$module->name}. It is required by {$enabledModule->name}."],
                    ]);
                }
            }

            $tenant->modules()->updateExistingPivot($module->id, [
                'is_enabled' => false,
                'disabled_at' => now(),
                'disabled_by' => auth()->id(),
            ]);

            return $tenant->load('modules');
        });
    }

    private function syncDependencies(Module $module, array $dependencyIds): void
    {
        $dependencyIds = array_values(array_unique(array_filter($dependencyIds)));

        if (in_array($module->id, $dependencyIds)) {
            throw ValidationException::withMessages([
                'dependency_ids' => ['A module cannot depend on itself.'],
            ]);
        }

        $module->dependencies()->sync($dependencyIds);
    }
public function allModulesWithTenantAssignment(Tenant $tenant): Collection
{
    $tenantModuleIds = $tenant->modules()
        ->wherePivot('is_enabled', true)
        ->pluck('modules.id')
        ->toArray();

    return Module::query()
        ->where('is_active', true)
        ->orderBy('display_order')
        ->orderBy('name')
        ->get()
        ->map(function ($module) use ($tenantModuleIds) {
            $module->is_assigned = in_array($module->id, $tenantModuleIds);
            return $module;
        });
}
    private function includeRequiredDependencies(array $moduleIds): array
    {
        $finalIds = array_values(array_unique($moduleIds));

        $changed = true;

        while ($changed) {
            $changed = false;

            $dependencies = Module::query()
                ->whereIn('id', $finalIds)
                ->with('dependencies')
                ->get()
                ->flatMap(fn ($module) => $module->dependencies->pluck('id'))
                ->unique()
                ->values()
                ->toArray();

            foreach ($dependencies as $dependencyId) {
                if (!in_array($dependencyId, $finalIds)) {
                    $finalIds[] = $dependencyId;
                    $changed = true;
                }
            }
        }

        return $finalIds;
    }
}