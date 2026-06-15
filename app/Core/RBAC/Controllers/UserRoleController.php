<?php

namespace App\Core\RBAC\Controllers;

use App\Core\RBAC\Requests\AssignRolesRequest;
use App\Core\RBAC\Services\RBACService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly RBACService $rbacService
    ) {
    }

    public function assignRoles(AssignRolesRequest $request, User $user): JsonResponse
    {
        $user = $this->rbacService->assignRoles(
            $user,
            $request->validated()['roles']
        );

        return ApiResponse::success(
            [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
            'Roles assigned successfully.'
        );
    }
}