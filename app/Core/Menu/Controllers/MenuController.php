<?php

namespace App\Core\Menu\Controllers;

use App\Core\Menu\Models\Menu;
use App\Core\Menu\Requests\MenuRequest;
use App\Core\Menu\Resources\MenuResource;
use App\Core\Menu\Services\MenuService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct(
        private readonly MenuService $menuService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $menus = $this->menuService->paginate($request->all());

        return ApiResponse::success(
            MenuResource::collection($menus->items()),
            'Menus fetched successfully.',
            200,
            [
                'current_page' => $menus->currentPage(),
                'per_page' => $menus->perPage(),
                'total' => $menus->total(),
                'last_page' => $menus->lastPage(),
            ]
        );
    }

    public function options(): JsonResponse
    {
        $menus = $this->menuService->options()
            ->map(fn ($menu) => [
                'label' => $menu->title,
                'value' => $menu->id,
                'code' => $menu->code,
            ]);

        return ApiResponse::success(
            $menus,
            'Menu options fetched successfully.'
        );
    }

    public function tree(): JsonResponse
    {
        return ApiResponse::success(
            $this->menuService->tree(),
            'Menu tree fetched successfully.'
        );
    }

    public function myMenus(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->menuService->tree($request->user()),
            'User menus fetched successfully.'
        );
    }

    public function store(MenuRequest $request): JsonResponse
    {
        $menu = $this->menuService->create($request->validated());

        return ApiResponse::success(
            new MenuResource($menu),
            'Menu created successfully.',
            201
        );
    }

    public function show(Menu $menu): JsonResponse
    {
        return ApiResponse::success(
            new MenuResource($menu->load(['parent', 'module', 'children'])),
            'Menu fetched successfully.'
        );
    }

    public function update(MenuRequest $request, Menu $menu): JsonResponse
    {
        $menu = $this->menuService->update($menu, $request->validated());

        return ApiResponse::success(
            new MenuResource($menu),
            'Menu updated successfully.'
        );
    }

    public function destroy(Menu $menu): JsonResponse
    {
        $this->menuService->delete($menu);

        return ApiResponse::success(
            null,
            'Menu deleted successfully.'
        );
    }

    public function activate(Menu $menu): JsonResponse
    {
        $menu = $this->menuService->activate($menu);

        return ApiResponse::success(
            new MenuResource($menu),
            'Menu activated successfully.'
        );
    }

    public function deactivate(Menu $menu): JsonResponse
    {
        $menu = $this->menuService->deactivate($menu);

        return ApiResponse::success(
            new MenuResource($menu),
            'Menu deactivated successfully.'
        );
    }
}