<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\AssessmentParticipantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentParticipantController extends Controller
{
    public function __construct(
        private readonly AssessmentParticipantService $service
    ) {
    }

    public function candidates(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->candidates($request->all()),
            'Assessment candidate applicants fetched successfully.'
        );
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->list($request->all()),
            'Assessment participants fetched successfully.'
        );
    }

    public function bulkAssignApplicants(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_id' => ['required', 'integer'],
            'assessment_schedule_id' => ['nullable', 'integer'],
            'applicant_ids' => ['required', 'array', 'min:1'],
            'applicant_ids.*' => ['integer'],
            'remarks' => ['nullable', 'string'],
            'import_batch_no' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->bulkAssignApplicants($validated),
            'Applicants assigned to assessment successfully.'
        );
    }

    public function generateRollNumbers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_id' => ['required', 'integer'],
            'assessment_schedule_id' => ['nullable', 'integer'],
        ]);

        return ApiResponse::success(
            $this->service->generateRollNumbers(
                (int) $validated['assessment_id'],
                isset($validated['assessment_schedule_id']) ? (int) $validated['assessment_schedule_id'] : null
            ),
            'Roll numbers generated successfully.'
        );
    }

    public function rollNoSlip(int $participantId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->rollNoSlip($participantId),
            'Roll no slip fetched successfully.'
        );
    }
}