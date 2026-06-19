<?php

namespace App\Core\RBAC\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Core\RBAC\Services\PermissionModuleResolver;
class RBACService
{
    public function __construct(
    private readonly PermissionModuleResolver $permissionModuleResolver
    ) {
    }
    public function paginateRoles(array $filters = [], ?User $user = null): LengthAwarePaginator
{
    $query = Role::query()
        ->withCount('permissions')
        ->with('permissions')
        ->where('guard_name', 'web');

    if ($user && !$user->isSuperAdmin()) {
        $query->where(function ($q) use ($user) {
            $q->where('tenant_id', $user->tenant_id)
              ->orWhere('role_level', 'template')
              ->orWhere('role_level', 'tenant_admin');
        });

        $query->where('name', '!=', 'Super Admin');
    }

    if (!empty($filters['search'])) {
        $search = $filters['search'];

        $query->where('name', 'like', "%{$search}%");
    }

    $perPage = (int) ($filters['per_page'] ?? 10);

    return $query
        ->orderByRaw("
            CASE 
                WHEN role_level = 'tenant_admin' THEN 1
                WHEN role_level = 'template' THEN 2
                WHEN role_level = 'tenant' THEN 3
                ELSE 4
            END
        ")
        ->orderBy('name')
        ->paginate($perPage);
}
    public function allRoles(?User $user = null): Collection
{
    $query = Role::query()
        ->where('guard_name', 'web');

    if ($user && !$user->isSuperAdmin()) {
        $query->where(function ($q) use ($user) {
            $q->where('tenant_id', $user->tenant_id)
              ->orWhere('role_level', 'template');
        });

        $query->whereNotIn('name', [
            'Super Admin',
            'Tenant Admin',
        ]);
    }

    return $query
        ->orderBy('name')
        ->get();
}
public function generatePermissions(array $data): array
{
    $module = str($data['module'])
        ->lower()
        ->replace([' ', '-'], '_')
        ->toString();

    $resource = str($data['resource'])
        ->lower()
        ->replace([' ', '-'], '_')
        ->toString();

    $actions = collect($data['actions'] ?? [])
        ->filter()
        ->map(fn ($action) => str($action)->lower()->replace([' ', '-'], '_')->toString())
        ->unique()
        ->values();

    abort_if($actions->isEmpty(), 422, 'At least one action is required.');

    $created = [];

    foreach ($actions as $action) {
        $name = "{$module}.{$resource}.{$action}";

        $permission = Permission::findOrCreate($name, 'web');

        $created[] = [
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
        ];
    }

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    return $created;
}
    public function allPermissionsGrouped(?User $user = null): array
    {
        $query = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name');

        $permissions = $query->get();

        if ($user && !$user->isSuperAdmin()) {
            $enabledModuleCodes = $user->tenant?->modules()
                ->wherePivot('is_enabled', true)
                ->pluck('modules.code')
                ->toArray() ?? [];

            $permissions = $permissions->filter(function ($permission) use ($enabledModuleCodes) {
                $moduleCode = $this->permissionModuleResolver->resolve($permission->name);

                return in_array($moduleCode, $enabledModuleCodes);
            });
        }

        return $permissions
            ->groupBy(fn ($permission) => $this->permissionModuleResolver->resolve($permission->name))
            ->map(function ($items, $module) {
                return [
                    'module' => $module,
                    'permissions' => $items->map(fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                    ])->values(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function createRole(array $data, ?User $user = null): Role
{
    return DB::transaction(function () use ($data, $user) {
        $tenantId = null;
        $roleLevel = 'system';
        $isSystem = false;

        if ($user && !$user->isSuperAdmin()) {
            $tenantId = $user->tenant_id;
            $roleLevel = 'tenant';
            $isSystem = false;
        }

        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'guard_name' => 'web',
            'role_level' => $roleLevel,
            'is_system' => $isSystem,
        ]);

        if (!empty($data['permissions'])) {
            $permissions = $this->filterPermissionsAllowedForUser(
                $data['permissions'],
                $user
            );

            $role->syncPermissions($permissions);
        }

        return $role->load('permissions');
    });
}

private function filterPermissionsAllowedForUser(array $permissions, ?User $user): array
{
    if (!$user || $user->isSuperAdmin()) {
        return $permissions;
    }

    $enabledModuleCodes = $user->tenant?->modules()
        ->wherePivot('is_enabled', true)
        ->pluck('modules.code')
        ->toArray() ?? [];

    $resolver = app(PermissionModuleResolver::class);

    return collect($permissions)
        ->filter(function ($permission) use ($enabledModuleCodes, $resolver) {
            $moduleCode = $resolver->resolve($permission);

            return in_array($moduleCode, $enabledModuleCodes);
        })
        ->values()
        ->toArray();
}
    public function updateRole(Role $role, array $data): Role
    {
        if ($user && !$user->isSuperAdmin()) {
            if ($role->name === 'Super Admin' || $role->role_level === 'system') {
                abort(403, 'You cannot modify system roles.');
            }

            if ($role->name === 'Tenant Admin' || $role->role_level === 'tenant_admin') {
                abort(403, 'You cannot modify Tenant Admin role.');
            }

            if ($role->tenant_id && (int) $role->tenant_id !== (int) $user->tenant_id) {
                abort(403, 'You cannot modify roles of another tenant.');
            }
        }
        return DB::transaction(function () use ($role, $data) {
            $role->update([
                'name' => $data['name'],
            ]);

            if (array_key_exists('permissions', $data)) {
                $role->syncPermissions($data['permissions'] ?? []);
            }

            return $role->refresh()->load('permissions');
        });
    }

    public function deleteRole(Role $role): bool
    {
        return DB::transaction(function () use ($role) {
            if (in_array($role->name, ['Super Admin', 'Tenant Admin'])) {
                abort(422, 'Core system roles cannot be deleted.');
            }

            return $role->delete();
        });
    }

    public function assignPermissions(Role $role, array $permissions): Role
    {
        return DB::transaction(function () use ($role, $permissions) {
            $role->syncPermissions($permissions);

            return $role->refresh()->load('permissions');
        });
    }

    public function assignRoles(User $user, array $roles): User
    {
        return DB::transaction(function () use ($user, $roles) {
            $user->syncRoles($roles);

            return $user->refresh()->load('roles');
        });
    }
}