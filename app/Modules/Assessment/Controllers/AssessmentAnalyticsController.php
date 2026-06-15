<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\AssessmentAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentAnalyticsController extends Controller
{
    public function __construct(
        private readonly AssessmentAnalyticsService $service
    ) {
    }

    public function dashboard(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->dashboard($request->all()),
            'Assessment analytics fetched successfully.'
        );
    }
}