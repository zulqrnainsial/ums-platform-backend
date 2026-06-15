<?php

namespace App\Core\Dynamic\Controllers;

use App\Core\Dynamic\Services\DynamicMetaService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DynamicMetaController extends Controller
{
    public function __construct(
        private readonly DynamicMetaService $dynamicMetaService
    ) {
    }

    public function show(string $entityCode): JsonResponse
    {
        return ApiResponse::success(
            $this->dynamicMetaService->getMetaByEntityCode($entityCode),
            'Dynamic metadata fetched successfully.'
        );
    }
}