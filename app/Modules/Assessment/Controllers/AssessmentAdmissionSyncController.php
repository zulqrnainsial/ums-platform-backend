<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\AssessmentAdmissionSyncService;
use Illuminate\Http\JsonResponse;

class AssessmentAdmissionSyncController extends Controller
{
    public function __construct(
        private readonly AssessmentAdmissionSyncService $service
    ) {
    }

    public function syncResult(int $assessmentResultId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->syncResultToApplicantTestResult($assessmentResultId),
            'Assessment result synced to admission test result successfully.'
        );
    }
}