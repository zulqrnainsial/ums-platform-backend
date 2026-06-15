<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\ApplicantEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicantEligibilityController extends Controller
{
    public function __construct(
        private readonly ApplicantEligibilityService $service
    ) {
    }

    public function evaluateProgram(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'applicant_id' => ['required', 'integer', 'exists:applicants,id'],
            'offered_program_id' => ['required', 'integer', 'exists:offered_programs,id'],
            'program_quota_seat_id' => ['nullable', 'integer', 'exists:program_quota_seats,id'],
        ]);

        $data = $this->service->evaluateForProgram(
            applicantId: (int) $validated['applicant_id'],
            offeredProgramId: (int) $validated['offered_program_id'],
            quotaSeatId: isset($validated['program_quota_seat_id'])
                ? (int) $validated['program_quota_seat_id']
                : null
        );

        return ApiResponse::success(
            $data,
            'Eligibility evaluated successfully.'
        );
    }

    public function eligiblePrograms(Request $request, int $applicantId): JsonResponse
    {
        $data = $this->service->eligibleProgramsForApplicant($applicantId);

        return ApiResponse::success(
            $data,
            'Eligible programs fetched successfully.'
        );
    }
}