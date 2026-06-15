<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\ApplicantAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicantAssessmentController extends Controller
{
    public function __construct(
        private readonly ApplicantAssessmentService $service
    ) {
    }

    public function myTests(): JsonResponse
    {
        return ApiResponse::success(
            $this->service->myTests(),
            'My tests fetched successfully.'
        );
    }

    public function rollNoSlip(int $participantId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->rollNoSlip($participantId),
            'Roll no slip fetched successfully.'
        );
    }

    public function startAttempt(Request $request, int $participantId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->startAttempt(
                $participantId,
                $request->ip(),
                $request->userAgent()
            ),
            'Attempt started successfully.'
        );
    }

    public function getAttempt(int $attemptId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->getAttempt($attemptId),
            'Attempt fetched successfully.'
        );
    }

    public function saveAnswer(Request $request, int $attemptId): JsonResponse
    {
        $validated = $request->validate([
            'assessment_question_id' => ['required', 'integer'],
            'selected_option_ids_json' => ['nullable', 'array'],
            'answer_text' => ['nullable', 'string'],
            'answer_number' => ['nullable', 'numeric'],
            'uploaded_file_path' => ['nullable', 'string'],
            'time_spent_seconds' => ['nullable', 'integer'],
        ]);

        return ApiResponse::success(
            $this->service->saveAnswer($attemptId, $validated),
            'Answer saved successfully.'
        );
    }

    public function submitAttempt(int $attemptId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->submitAttempt($attemptId),
            'Attempt submitted successfully.'
        );
    }
    public function attemptReview(int $attemptId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->attemptReview($attemptId),
            'Attempt review fetched successfully.'
        );
    }
    public function logActivity(Request $request, int $attemptId): JsonResponse
{
    $validated = $request->validate([
        'event_code' => ['required', 'string', 'max:80'],
        'assessment_question_id' => ['nullable', 'integer'],
        'question_id' => ['nullable', 'integer'],
        'event_payload_json' => ['nullable', 'array'],
    ]);

    return ApiResponse::success(
        $this->service->logActivity(
            $attemptId,
            $validated,
            $request->ip(),
            $request->userAgent()
        ),
        'Attempt activity logged successfully.'
    );
}
}