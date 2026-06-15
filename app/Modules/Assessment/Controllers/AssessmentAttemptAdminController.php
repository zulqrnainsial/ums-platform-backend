<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\AssessmentAttemptAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentAttemptAdminController extends Controller
{
    public function __construct(
        private readonly AssessmentAttemptAdminService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->list($request->all()),
            'Assessment attempts fetched successfully.'
        );
    }

    public function detail(int $attemptId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->detail($attemptId),
            'Assessment attempt detail fetched successfully.'
        );
    }
    public function activityLogs(int $attemptId): JsonResponse
{
    return ApiResponse::success(
        $this->service->activityLogs($attemptId),
        'Assessment attempt activity logs fetched successfully.'
    );
}
}