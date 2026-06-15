<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\AssessmentResultAdminService;
use App\Modules\Assessment\Services\AssessmentResultService;
use App\Modules\Assessment\Services\AssessmentAdmissionSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentResultAdminController extends Controller
{
    public function __construct(
        private readonly AssessmentResultAdminService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->list($request->all()),
            'Assessment results fetched successfully.'
        );
    }

    public function detail(int $resultId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->detail($resultId),
            'Assessment result detail fetched successfully.'
        );
    }

    public function approve(int $resultId): JsonResponse
    {
        return ApiResponse::success(
            app(AssessmentResultService::class)->approveResult($resultId),
            'Assessment result approved successfully.'
        );
    }

    public function publish(int $resultId): JsonResponse
    {
        return ApiResponse::success(
            app(AssessmentResultService::class)->publishResult($resultId),
            'Assessment result published successfully.'
        );
    }

    public function syncToAdmission(int $resultId): JsonResponse
    {
        return ApiResponse::success(
            app(AssessmentAdmissionSyncService::class)->syncResultToApplicantTestResult($resultId),
            'Assessment result synced to admission successfully.'
        );
    }
}