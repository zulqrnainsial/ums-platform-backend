<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\ApplicantApplicationChecklistService;
use Illuminate\Http\JsonResponse;

class ApplicantApplicationChecklistController extends Controller
{
    public function __construct(
        private readonly ApplicantApplicationChecklistService $service
    ) {
    }

    public function checklist(int $applicationId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->checklist($applicationId),
            'Application checklist fetched successfully.'
        );
    }

    public function validateFinalSubmission(int $applicationId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->validateFinalSubmission($applicationId),
            'Final submission validation completed.'
        );
    }

    public function finalSubmit(int $applicationId): JsonResponse
    {
        $application = $this->service->finalSubmit($applicationId);

        return ApiResponse::success(
            [
                'id' => $application->id,
                'application_no' => $application->application_no,
                'application_status_code' => $application->application_status_code,
                'eligibility_status_code' => $application->eligibility_status_code,
                'document_status_code' => $application->document_status_code,
                'fee_status_code' => $application->fee_status_code,
                'test_status_code' => $application->test_status_code,
                'submitted_at' => $application->submitted_at,
            ],
            'Application finally submitted successfully.'
        );
    }
}