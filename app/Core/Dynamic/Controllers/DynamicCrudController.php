<?php

namespace App\Core\Dynamic\Controllers;

use App\Core\Dynamic\Services\DynamicCrudService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DynamicCrudController extends Controller
{
    public function __construct(
        private readonly DynamicCrudService $dynamicCrudService
    ) {
    }

    public function index(Request $request, string $entityCode): JsonResponse
    {
        $records = $this->dynamicCrudService->paginate(
            $entityCode,
            $request->all()
        );

        return ApiResponse::success(
            $records->items(),
            'Records fetched successfully.',
            200,
            [
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'last_page' => $records->lastPage(),
            ]
        );
    }

    public function show(string $entityCode, int|string $id): JsonResponse
    {
        return ApiResponse::success(
            $this->dynamicCrudService->find($entityCode, $id),
            'Record fetched successfully.'
        );
    }

    public function store(Request $request, string $entityCode): JsonResponse
    {
        return ApiResponse::success(
            $this->dynamicCrudService->create($entityCode, $request->all()),
            'Record created successfully.',
            201
        );
    }

    public function update(Request $request, string $entityCode, int|string $id): JsonResponse
    {
        return ApiResponse::success(
            $this->dynamicCrudService->update($entityCode, $id, $request->all()),
            'Record updated successfully.'
        );
    }

    public function destroy(string $entityCode, int|string $id): JsonResponse
    {
        $this->dynamicCrudService->delete($entityCode, $id);

        return ApiResponse::success(
            null,
            'Record deleted successfully.'
        );
    }
}