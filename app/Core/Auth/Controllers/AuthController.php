<?php

namespace App\Core\Auth\Controllers;

use App\Core\Auth\Requests\LoginRequest;
use App\Core\Auth\Resources\AuthUserResource;
use App\Core\Auth\Services\AuthService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Core\Menu\Services\MenuService;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly MenuService $menuService
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated(),
            $request
        );

        return ApiResponse::success([
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'user' => new AuthUserResource($result['user']),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ], 'Login successful.');
    }
public function menus(Request $request): JsonResponse
{
    return ApiResponse::success(
        $this->menuService->tree($request->user()),
        'User menus fetched successfully.'
    );
}
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenant');

        return ApiResponse::success([
            'user' => new AuthUserResource($user),
            'roles' => method_exists($user, 'getRoleNames')
                ? $user->getRoleNames()
                : [],
            'permissions' => method_exists($user, 'getAllPermissions')
                ? $user->getAllPermissions()->pluck('name')->values()
                : [],
        ], 'Authenticated user fetched successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(
            null,
            'Logout successful.'
        );
    }

    public function logoutAllDevices(Request $request): JsonResponse
    {
        $this->authService->logoutAllDevices($request->user());

        return ApiResponse::success(
            null,
            'Logged out from all devices successfully.'
        );
    }
}