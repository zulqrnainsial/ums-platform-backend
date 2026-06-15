<?php

namespace App\Core\User\Controllers;

use App\Core\User\Requests\UserRequest;
use App\Core\User\Resources\UserResource;
use App\Core\User\Services\UserService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->can('user.view')) {
            abort(403, 'You are not allowed to view users.');
        }

        $users = $this->userService->paginate(
            $request->all(),
            $request->user()
        );

        return ApiResponse::success(
            UserResource::collection($users->items()),
            'Users fetched successfully.',
            200,
            [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        );
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->create(
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            new UserResource($user),
            'User created successfully.',
            201
        );
    }

    public function show(User $user, Request $request): JsonResponse
    {
        if (!$request->user()->can('user.view')) {
            abort(403, 'You are not allowed to view users.');
        }

        if (!$request->user()->isSuperAdmin() && (int) $user->tenant_id !== (int) $request->user()->tenant_id) {
            abort(403, 'You cannot view users of another tenant.');
        }

        return ApiResponse::success(
            new UserResource($user->load(['tenant', 'roles'])),
            'User fetched successfully.'
        );
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update(
            $user,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            new UserResource($user),
            'User updated successfully.'
        );
    }

    public function activate(User $user, Request $request): JsonResponse
    {
        $user = $this->userService->activate($user, $request->user());

        return ApiResponse::success(
            new UserResource($user),
            'User activated successfully.'
        );
    }

    public function deactivate(User $user, Request $request): JsonResponse
    {
        $user = $this->userService->deactivate($user, $request->user());

        return ApiResponse::success(
            new UserResource($user),
            'User deactivated successfully.'
        );
    }
}