<?php

namespace App\Core\RBAC\Controllers;

use App\Core\RBAC\Requests\AssignPermissionsRequest;
use App\Core\RBAC\Requests\RoleRequest;
use App\Core\RBAC\Resources\RoleResource;
use App\Core\RBAC\Services\RBACService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RBACService $rbacService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $roles = $this->rbacService->paginateRoles($request->all(), $request->user());

        return ApiResponse::success(
            RoleResource::collection($roles->items()),
            'Roles fetched successfully.',
            200,
            [
                'current_page' => $roles->currentPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'last_page' => $roles->lastPage(),
            ]
        );
    }

    public function options(Request $request): JsonResponse
{
    $roles = $this->rbacService->allRoles($request->user())
        ->map(fn ($role) => [
            'label' => $role->name,
            'value' => $role->name,
            'role_level' => $role->role_level,
            'tenant_id' => $role->tenant_id,
        ])
        ->values();

    return ApiResponse::success(
        $roles,
        'Role options fetched successfully.'
    );
}

    public function store(RoleRequest $request): JsonResponse
    {
        $role = $this->rbacService->createRole($request->validated(), $request->user());

        return ApiResponse::success(
            new RoleResource($role),
            'Role created successfully.',
            201
        );
    }

    public function show(Role $role): JsonResponse
    {
        return ApiResponse::success(
            new RoleResource($role->load('permissions')),
            'Role fetched successfully.'
        );
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        $role = $this->rbacService->updateRole($role, $request->validated());

        return ApiResponse::success(
            new RoleResource($role),
            'Role updated successfully.'
        );
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->rbacService->deleteRole($role);

        return ApiResponse::success(
            null,
            'Role deleted successfully.'
        );
    }

    public function assignPermissions(AssignPermissionsRequest $request, Role $role): JsonResponse
    {
        $role = $this->rbacService->assignPermissions(
            $role,
            $request->validated()['permissions']
        );

        return ApiResponse::success(
            new RoleResource($role),
            'Permissions assigned successfully.'
        );
    }
}