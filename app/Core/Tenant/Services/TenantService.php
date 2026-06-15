<?php

namespace App\Core\Tenant\Services;

use App\Core\Modules\Models\Module;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
class TenantService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Tenant::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['subscription_status'])) {
            $query->where('subscription_status', $filters['subscription_status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        return $query
            ->latest()
            ->paginate($perPage);
    }

    public function allActive(): Collection
    {
        return Tenant::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $adminName = $data['admin_name'] ?? null;
            $adminEmail = $data['admin_email'] ?? null;
            $adminPassword = $data['admin_password'] ?? null;
            $moduleIds = $data['module_ids'] ?? [];

            unset(
                $data['admin_name'],
                $data['admin_email'],
                $data['admin_password'],
                $data['module_ids']
            );

            $tenant = Tenant::create($data);

            $this->assignCoreModules($tenant);

            if (!empty($moduleIds)) {
                $this->assignModules($tenant, $moduleIds);
            }

            if ($adminName && $adminEmail && $adminPassword) {
                $this->createTenantAdminUser(
                    $tenant,
                    $adminName,
                    $adminEmail,
                    $adminPassword
                );
            }

            return $tenant->load(['modules', 'users']);
        });
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $tenant->update($data);

            return $tenant->refresh();
        });
    }
private function createTenantAdminUser(
    Tenant $tenant,
    string $adminName,
    string $adminEmail,
    string $adminPassword
): User {
    $existingUser = User::query()
        ->where('tenant_id', $tenant->id)
        ->where('email', $adminEmail)
        ->first();

    if ($existingUser) {
        return $existingUser;
    }

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => $adminName,
        'email' => $adminEmail,
        'password' => Hash::make($adminPassword),
        'user_type' => 'tenant_admin',
        'status' => 'active',
    ]);

    $tenantAdminRole = Role::query()
        ->where('name', 'Tenant Admin')
        ->where('guard_name', 'web')
        ->first();

    if ($tenantAdminRole) {
        $user->assignRole($tenantAdminRole);
    }

    return $user;
}
    public function delete(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            return $tenant->delete();
        });
    }

    public function activate(Tenant $tenant): Tenant
    {
        $tenant->update([
            'status' => 'active',
        ]);

        return $tenant->refresh();
    }

    public function deactivate(Tenant $tenant): Tenant
    {
        $tenant->update([
            'status' => 'inactive',
        ]);

        return $tenant->refresh();
    }

    public function suspend(Tenant $tenant): Tenant
    {
        $tenant->update([
            'status' => 'suspended',
        ]);

        return $tenant->refresh();
    }

    public function assignModules(Tenant $tenant, array $moduleIds): Tenant
    {
        return DB::transaction(function () use ($tenant, $moduleIds) {
            $syncData = [];

            foreach ($moduleIds as $moduleId) {
                $syncData[$moduleId] = [
                    'is_enabled' => true,
                    'enabled_at' => now(),
                    'enabled_by' => auth()->id(),
                ];
            }

            $tenant->modules()->sync($syncData);

            return $tenant->load('modules');
        });
    }

    private function assignCoreModules(Tenant $tenant): void
    {
        $coreModules = Module::query()
            ->where('is_core', true)
            ->where('is_active', true)
            ->pluck('id');

        $syncData = [];

        foreach ($coreModules as $moduleId) {
            $syncData[$moduleId] = [
                'is_enabled' => true,
                'enabled_at' => now(),
                'enabled_by' => auth()->id(),
            ];
        }

        if (!empty($syncData)) {
            $tenant->modules()->syncWithoutDetaching($syncData);
        }
    }
}