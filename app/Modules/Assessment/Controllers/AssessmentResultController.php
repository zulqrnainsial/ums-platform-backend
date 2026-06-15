<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\AssessmentResultService;
use Illuminate\Http\JsonResponse;

class AssessmentResultController extends Controller
{
    public function __construct(
        private readonly AssessmentResultService $service
    ) {
    }

    public function generateForAttempt(int $attemptId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->generateForAttempt($attemptId),
            'Assessment result generated successfully.'
        );
    }

    public function approve(int $resultId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->approveResult($resultId),
            'Assessment result approved successfully.'
        );
    }

    public function publish(int $resultId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->publishResult($resultId),
            'Assessment result published successfully.'
        );
    }
}