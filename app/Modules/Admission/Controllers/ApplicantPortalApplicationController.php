<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use App\Modules\Admission\Services\ApplicantApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicantPortalApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicantApplicationService $service
    ) {
    }

    public function eligiblePrograms(int $applicantId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->eligiblePrograms($applicantId),
            'Eligible programs fetched successfully.'
        );
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'applicant_id' => ['required', 'integer', 'exists:applicants,id'],
            'offered_program_id' => ['required', 'integer', 'exists:offered_programs,id'],
            'program_quota_seat_id' => ['nullable', 'integer', 'exists:program_quota_seats,id'],
            'preference_order' => ['nullable', 'integer', 'min:1'],
            'submit_now' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ]);

        $application = $this->service->apply($validated);

        return ApiResponse::success(
            $this->service->formatApplication($application->fresh(['offeredProgram', 'programQuotaSeat'])),
            'Application created successfully.'
        );
    }

    public function submit(int $applicationId): JsonResponse
    {
        $application = $this->service->submit($applicationId);

        return ApiResponse::success(
            $this->service->formatApplication($application->fresh(['offeredProgram', 'programQuotaSeat'])),
            'Application submitted successfully.'
        );
    }

    public function applications(int $applicantId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->applicationsForApplicant($applicantId),
            'Applicant applications fetched successfully.'
        );
    }

    public function show(int $applicationId): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;

        $application = ApplicantProgramApplication::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $applicationId)
            ->with(['offeredProgram', 'programQuotaSeat'])
            ->firstOrFail();

        return ApiResponse::success(
            $this->service->formatApplication($application),
            'Application fetched successfully.'
        );
    }
}