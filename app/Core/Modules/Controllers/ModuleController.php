<?php

namespace App\Core\Modules\Controllers;

use App\Core\Modules\Models\Module;
use App\Core\Modules\Requests\AssignTenantModulesRequest;
use App\Core\Modules\Requests\ModuleRequest;
use App\Core\Modules\Resources\ModuleResource;
use App\Core\Modules\Resources\TenantModuleResource;
use App\Core\Modules\Services\ModuleService;
use App\Core\Tenant\Models\Tenant;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function __construct(
        private readonly ModuleService $moduleService
    ) {
    }

    public function index(Request $request): JsonResponse
{
    $modules = $this->moduleService->paginate(
        $request->all(),
        $request->user()
    );

    return ApiResponse::success(
        ModuleResource::collection($modules->items()),
        'Modules fetched successfully.',
        200,
        [
            'current_page' => $modules->currentPage(),
            'per_page' => $modules->perPage(),
            'total' => $modules->total(),
            'last_page' => $modules->lastPage(),
        ]
    );
}

    public function options(Request $request): JsonResponse
{
    $modules = $this->moduleService->allActive($request->user())
        ->map(fn ($module) => [
            'label' => $module->name,
            'value' => $module->id,
            'code' => $module->code,
            'is_core' => $module->is_core,
        ]);

    return ApiResponse::success(
        $modules,
        'Module options fetched successfully.'
    );
}
private function abortIfNotSuperAdmin(Request $request): void
{
    if (!$request->user()?->isSuperAdmin()) {
        abort(403, 'Only Super Admin can manage modules.');
    }
}
    public function store(ModuleRequest $request): JsonResponse
{
    $this->abortIfNotSuperAdmin($request);

    $module = $this->moduleService->create($request->validated());

    return ApiResponse::success(
        new ModuleResource($module),
        'Module created successfully.',
        201
    );
}

    public function show(Module $module): JsonResponse
    {
        return ApiResponse::success(
            new ModuleResource($module->load(['parent', 'dependencies'])),
            'Module fetched successfully.'
        );
    }

    public function update(ModuleRequest $request, Module $module): JsonResponse
{
    $this->abortIfNotSuperAdmin($request);

    $module = $this->moduleService->update($module, $request->validated());

    return ApiResponse::success(
        new ModuleResource($module),
        'Module updated successfully.'
    );
}

    public function destroy(Request $request, Module $module): JsonResponse
{
    $this->abortIfNotSuperAdmin($request);

    $this->moduleService->delete($module);

    return ApiResponse::success(
        null,
        'Module deleted successfully.'
    );
}

    public function activate(Request $request, Module $module): JsonResponse
{
    $this->abortIfNotSuperAdmin($request);

    $module = $this->moduleService->activate($module);

    return ApiResponse::success(
        new ModuleResource($module),
        'Module activated successfully.'
    );
}

    public function deactivate(Request $request, Module $module): JsonResponse
{
    $this->abortIfNotSuperAdmin($request);

    $module = $this->moduleService->deactivate($module);

    return ApiResponse::success(
        new ModuleResource($module),
        'Module deactivated successfully.'
    );
}

    public function tenantModules(Tenant $tenant): JsonResponse
    {
        $modules = $this->moduleService->getTenantModules($tenant);

        return ApiResponse::success(
            TenantModuleResource::collection($modules),
            'Tenant modules fetched successfully.'
        );
    }

    public function assignTenantModules(AssignTenantModulesRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant = $this->moduleService->assignModulesToTenant(
            $tenant,
            $request->validated()['module_ids']
        );

        return ApiResponse::success(
            [
                'tenant_id' => $tenant->id,
                'modules' => TenantModuleResource::collection($tenant->modules),
            ],
            'Modules assigned to tenant successfully.'
        );
    }
public function tenantModuleAssignmentOptions(Tenant $tenant): JsonResponse
{
    $modules = $this->moduleService->allModulesWithTenantAssignment($tenant)
        ->map(fn ($module) => [
            'label' => $module->name,
            'value' => $module->id,
            'code' => $module->code,
            'is_core' => $module->is_core,
            'is_assigned' => $module->is_assigned,
        ])
        ->values();

    return ApiResponse::success(
        $modules,
        'Tenant module assignment options fetched successfully.'
    );
}
    public function enableTenantModule(Tenant $tenant, Module $module): JsonResponse
    {
        $tenant = $this->moduleService->enableTenantModule($tenant, $module);

        return ApiResponse::success(
            [
                'tenant_id' => $tenant->id,
                'modules' => TenantModuleResource::collection($tenant->modules),
            ],
            'Module enabled successfully.'
        );
    }

    public function disableTenantModule(Tenant $tenant, Module $module): JsonResponse
    {
        $tenant = $this->moduleService->disableTenantModule($tenant, $module);

        return ApiResponse::success(
            [
                'tenant_id' => $tenant->id,
                'modules' => TenantModuleResource::collection($tenant->modules),
            ],
            'Module disabled successfully.'
        );
    }
}