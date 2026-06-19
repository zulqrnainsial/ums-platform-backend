<?php

namespace App\Core\Menu\Services;

use App\Core\Menu\Models\Menu;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Menu::query()
            ->with(['parent', 'module']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('route', 'like', "%{$search}%")
                    ->orWhere('permission_name', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['module_id'])) {
            $query->where('module_id', $filters['module_id']);
        }

        if (array_key_exists('tenant_id', $filters) && $filters['tenant_id'] !== null && $filters['tenant_id'] !== '') {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        return $query
            ->orderBy('display_order')
            ->orderBy('title')
            ->paginate($perPage);
    }

    public function options(): Collection
{
    $menus = Menu::query()
        ->where('is_active', true)
        ->orderBy('display_order')
        ->orderBy('title')
        ->get();

    $children = $menus->groupBy(fn ($menu) => $menu->parent_id ?: 0);

    $build = function ($parentId = 0, string $prefix = '') use (&$build, $children) {
        return ($children[$parentId] ?? collect())
            ->flatMap(function ($menu) use (&$build, $prefix) {
                return collect([
                    (object) [
                        'id' => $menu->id,
                        'title' => $prefix . $menu->title,
                        'code' => $menu->code,
                        'parent_id' => $menu->parent_id,
                        'display_order' => $menu->display_order,
                    ],
                ])->merge(
                    $build($menu->id, $prefix . '— ')
                );
            });
    };

    return $build()->values();
}
public function nextDisplayOrder(?int $parentId = null): int
{
    return ((int) Menu::query()
        ->where('parent_id', $parentId)
        ->max('display_order')) + 1;
}
    public function tree(?User $user = null): array
    {
        $query = Menu::query()
            ->with([
                'module',
                'children' => function ($q) {
                    $q->where('is_active', true)
                        ->with(['module', 'children'])
                        ->orderBy('display_order')
                        ->orderBy('title');
                },
            ])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('title');

        $menus = $query->get();

        if ($user) {
            $menus = $menus->filter(fn ($menu) => $this->isMenuAllowedForUser($menu, $user));
        }

        return $menus
            ->map(fn ($menu) => $this->formatMenuTree($menu, $user))
            ->filter()
            ->values()
            ->toArray();
    }

    public function create(array $data): Menu
    {
        return DB::transaction(function () use ($data) {
            $this->validateParent($data);

            return Menu::create($data)
                ->load(['parent', 'module']);
        });
    }

    public function update(Menu $menu, array $data): Menu
    {
        return DB::transaction(function () use ($menu, $data) {
            if ($menu->is_system && isset($data['is_system']) && !$data['is_system']) {
                throw ValidationException::withMessages([
                    'is_system' => ['System menu cannot be converted to non-system.'],
                ]);
            }

            $this->validateParent($data, $menu);

            $menu->update($data);

            return $menu->refresh()->load(['parent', 'module']);
        });
    }

    public function delete(Menu $menu): bool
    {
        return DB::transaction(function () use ($menu) {
            if ($menu->is_system) {
                throw ValidationException::withMessages([
                    'menu' => ['System menu cannot be deleted.'],
                ]);
            }

            return $menu->delete();
        });
    }

    public function activate(Menu $menu): Menu
    {
        $menu->update([
            'is_active' => true,
        ]);

        return $menu->refresh()->load(['parent', 'module']);
    }

    public function deactivate(Menu $menu): Menu
    {
        if ($menu->is_system) {
            throw ValidationException::withMessages([
                'menu' => ['System menu cannot be deactivated.'],
            ]);
        }

        $menu->update([
            'is_active' => false,
        ]);

        return $menu->refresh()->load(['parent', 'module']);
    }

    private function validateParent(array $data, ?Menu $menu = null): void
    {
        if (empty($data['parent_id'])) {
            return;
        }

        if ($menu && (int) $data['parent_id'] === (int) $menu->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['Menu cannot be parent of itself.'],
            ]);
        }
    }

    private function isMenuAllowedForUser(Menu $menu, User $user): bool
{
    if ($user->isSuperAdmin()) {
        return true;
    }

    if (!$user->tenant_id || !$user->tenant) {
        return false;
    }

    if ($menu->tenant_id && (int) $menu->tenant_id !== (int) $user->tenant_id) {
        return false;
    }

    if ($menu->module_id) {
        $enabledModule = $user->tenant->modules()
            ->where('modules.id', $menu->module_id)
            ->wherePivot('is_enabled', true)
            ->exists();

        if (!$enabledModule) {
            return false;
        }
    }

    if ($menu->permission_name && !$user->can($menu->permission_name)) {
        return false;
    }

    return true;
}

    private function formatMenuTree(Menu $menu, ?User $user = null): ?array
    {
        if ($user && !$this->isMenuAllowedForUser($menu, $user)) {
            return null;
        }

        $children = $menu->children
            ->filter(fn ($child) => !$user || $this->isMenuAllowedForUser($child, $user))
            ->map(fn ($child) => $this->formatMenuTree($child, $user))
            ->filter()
            ->values()
            ->toArray();

        if (!$menu->route && empty($children)) {
            return null;
        }

        return [
            'id' => $menu->id,
            'parent_id' => $menu->parent_id,
            'module_id' => $menu->module_id,

            'title' => $menu->title,
            'code' => $menu->code,
            'route' => $menu->route,
            'icon' => $menu->icon,
            'permission_name' => $menu->permission_name,

            'is_system' => $menu->is_system,
            'is_active' => $menu->is_active,
            'display_order' => $menu->display_order,

            'children' => $children,
        ];
    }
}