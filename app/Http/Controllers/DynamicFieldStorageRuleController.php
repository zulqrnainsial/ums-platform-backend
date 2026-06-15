<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\DynamicFieldStorageRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DynamicFieldStorageRuleController extends Controller
{
    public function __construct(
        private readonly DynamicFieldStorageRuleService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->getRules($request->only([
                'module_code',
                'entity_key',
                'field_name',
            ])),
            'Dynamic field storage rules fetched successfully.'
        );
    }

    public function entity(string $moduleCode, string $entityKey): JsonResponse
    {
        return ApiResponse::success(
            $this->service->getEntityRules($moduleCode, $entityKey),
            'Entity field storage rules fetched successfully.'
        );
    }
}