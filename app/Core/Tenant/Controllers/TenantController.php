<?php

namespace App\Core\Tenant\Controllers;

use App\Core\Tenant\Models\Tenant;
use App\Core\Tenant\Requests\TenantRequest;
use App\Core\Tenant\Resources\TenantResource;
use App\Core\Tenant\Services\TenantService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tenants = $this->tenantService->paginate($request->all());

        return ApiResponse::success(
            TenantResource::collection($tenants->items()),
            'Tenants fetched successfully.',
            200,
            [
                'current_page' => $tenants->currentPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
                'last_page' => $tenants->lastPage(),
            ]
        );
    }

    public function options(): JsonResponse
    {
        $tenants = $this->tenantService->allActive()
            ->map(fn ($tenant) => [
                'label' => $tenant->name,
            'value' => $tenant->id,
            'code' => $tenant->code,
            ])->values();

        return ApiResponse::success(
            $tenants,
            'Tenant options fetched successfully.'
        );
    }

    public function store(TenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        return ApiResponse::success(
            new TenantResource($tenant),
            'Tenant created successfully.',
            201
        );
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return ApiResponse::success(
            new TenantResource($tenant),
            'Tenant fetched successfully.'
        );
    }

    public function update(TenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->update($tenant, $request->validated());

        return ApiResponse::success(
            new TenantResource($tenant),
            'Tenant updated successfully.'
        );
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->tenantService->delete($tenant);

        return ApiResponse::success(
            null,
            'Tenant deleted successfully.'
        );
    }

    public function activate(Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->activate($tenant);

        return ApiResponse::success(
            new TenantResource($tenant),
            'Tenant activated successfully.'
        );
    }

    public function deactivate(Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->deactivate($tenant);

        return ApiResponse::success(
            new TenantResource($tenant),
            'Tenant deactivated successfully.'
        );
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->suspend($tenant);

        return ApiResponse::success(
            new TenantResource($tenant),
            'Tenant suspended successfully.'
        );
    }

    public function assignModules(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'module_ids' => [
                'required',
                'array',
            ],
            'module_ids.*' => [
                'required',
                'integer',
                'exists:modules,id',
            ],
        ]);

        $tenant = $this->tenantService->assignModules(
            $tenant,
            $validated['module_ids']
        );

        return ApiResponse::success(
            $tenant,
            'Modules assigned to tenant successfully.'
        );
    }
}