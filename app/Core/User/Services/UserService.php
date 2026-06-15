<?php

namespace App\Core\User\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserService
{
    public function paginate(array $filters, User $authUser): LengthAwarePaginator
    {
        $query = User::query()
            ->with('tenant')
            ->with('roles');

        if (!$authUser->isSuperAdmin()) {
            $query->where('tenant_id', $authUser->tenant_id)
                ->where('user_type', '!=', 'super_admin');
        }

        if ($authUser->isSuperAdmin() && !empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        return $query
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data, User $authUser): User
    {
        return DB::transaction(function () use ($data, $authUser) {
            if (!$authUser->can('user.create')) {
                abort(403, 'You are not allowed to create users.');
            }

            if (!$authUser->isSuperAdmin()) {
                $data['tenant_id'] = $authUser->tenant_id;
            }

            if (empty($data['tenant_id'])) {
                throw ValidationException::withMessages([
                    'tenant_id' => ['Tenant is required for tenant users.'],
                ]);
            }

            $this->ensureEmailUniqueInTenant(
                $data['email'],
                (int) $data['tenant_id']
            );

            $roles = $data['roles'] ?? [];

            unset($data['roles']);

            $data['password'] = Hash::make($data['password']);

            $user = User::create($data);

            if (!empty($roles)) {
                $allowedRoles = $this->filterAssignableRoles($roles, $authUser);
                $user->syncRoles($allowedRoles);
            }

            return $user->load(['tenant', 'roles']);
        });
    }

    public function update(User $user, array $data, User $authUser): User
    {
        return DB::transaction(function () use ($user, $data, $authUser) {
            if (!$authUser->can('user.update')) {
                abort(403, 'You are not allowed to update users.');
            }

            $this->guardUserModification($user, $authUser);

            if (!$authUser->isSuperAdmin()) {
                unset($data['tenant_id']);
            }

            $targetTenantId = $authUser->isSuperAdmin()
                ? (int) ($data['tenant_id'] ?? $user->tenant_id)
                : (int) $authUser->tenant_id;

            if (!empty($data['email']) && $data['email'] !== $user->email) {
                $this->ensureEmailUniqueInTenant(
                    $data['email'],
                    $targetTenantId,
                    $user->id
                );
            }

            $roles = $data['roles'] ?? null;

            unset($data['roles']);

            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            if (is_array($roles)) {
                if (!$authUser->can('user.assign_roles')) {
                    abort(403, 'You are not allowed to assign roles.');
                }

                $allowedRoles = $this->filterAssignableRoles($roles, $authUser);
                $user->syncRoles($allowedRoles);
            }

            return $user->refresh()->load(['tenant', 'roles']);
        });
    }

    public function activate(User $user, User $authUser): User
    {
        if (!$authUser->can('user.activate')) {
            abort(403, 'You are not allowed to activate users.');
        }

        $this->guardUserModification($user, $authUser);

        $user->update(['status' => 'active']);

        return $user->refresh()->load(['tenant', 'roles']);
    }

    public function deactivate(User $user, User $authUser): User
    {
        if (!$authUser->can('user.deactivate')) {
            abort(403, 'You are not allowed to deactivate users.');
        }

        $this->guardUserModification($user, $authUser);

        if ((int) $user->id === (int) $authUser->id) {
            abort(403, 'You cannot deactivate yourself.');
        }

        $user->update(['status' => 'inactive']);

        return $user->refresh()->load(['tenant', 'roles']);
    }

    private function guardUserModification(User $targetUser, User $authUser): void
    {
        if ($targetUser->isSuperAdmin() && !$authUser->isSuperAdmin()) {
            abort(403, 'You cannot modify Super Admin.');
        }

        if (!$authUser->isSuperAdmin()) {
            if ((int) $targetUser->tenant_id !== (int) $authUser->tenant_id) {
                abort(403, 'You cannot modify users of another tenant.');
            }

            if ($targetUser->isTenantAdmin()) {
                abort(403, 'Tenant Admin rights cannot be modified from tenant side.');
            }
        }
    }

    private function filterAssignableRoles(array $roles, User $authUser): array
    {
        if ($authUser->isSuperAdmin()) {
            return Role::query()
                ->whereIn('name', $roles)
                ->where('guard_name', 'web')
                ->where('name', '!=', 'Super Admin')
                ->pluck('name')
                ->toArray();
        }

        return Role::query()
            ->whereIn('name', $roles)
            ->where('guard_name', 'web')
            ->where(function ($q) use ($authUser) {
                $q->where('tenant_id', $authUser->tenant_id)
                    ->orWhere('role_level', 'template');
            })
            ->whereNotIn('name', [
                'Super Admin',
                'Tenant Admin',
            ])
            ->pluck('name')
            ->toArray();
    }

    private function ensureEmailUniqueInTenant(
        string $email,
        int $tenantId,
        ?int $ignoreUserId = null
    ): void {
        $exists = User::query()
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => ['This email already exists in this tenant.'],
            ]);
        }
    }
}