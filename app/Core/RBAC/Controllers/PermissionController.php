<?php

namespace App\Core\RBAC\Controllers;

use App\Core\RBAC\Resources\PermissionResource;
use App\Core\RBAC\Services\RBACService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
class PermissionController extends Controller
{
    public function __construct(
        private readonly RBACService $rbacService
    ) {
    }

    public function index(): JsonResponse
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            PermissionResource::collection($permissions),
            'Permissions fetched successfully.'
        );
    }

    public function grouped(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->rbacService->allPermissionsGrouped($request->user()),
            'Grouped permissions fetched successfully.'
        );
    }
}