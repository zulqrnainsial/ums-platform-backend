<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\AssessmentBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentBuilderController extends Controller
{
    public function __construct(
        private readonly AssessmentBuilderService $service
    ) {
    }

    public function show(int $assessmentId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->show($assessmentId),
            'Assessment builder data fetched successfully.'
        );
    }

    public function createSection(Request $request, int $assessmentId): JsonResponse
    {
        $validated = $this->validateSection($request);

        return ApiResponse::success(
            $this->service->createSection($assessmentId, $validated),
            'Assessment section created successfully.'
        );
    }

    public function updateSection(Request $request, int $sectionId): JsonResponse
    {
        $validated = $this->validateSection($request);

        return ApiResponse::success(
            $this->service->updateSection($sectionId, $validated),
            'Assessment section updated successfully.'
        );
    }

    public function deleteSection(int $sectionId): JsonResponse
    {
        $this->service->deleteSection($sectionId);

        return ApiResponse::success(null, 'Assessment section deleted successfully.');
    }

    public function availableQuestions(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->availableQuestions($request->all()),
            'Available questions fetched successfully.'
        );
    }

    public function bulkAssignQuestions(Request $request, int $sectionId): JsonResponse
    {
        $validated = $request->validate([
            'question_bank_ids' => ['nullable', 'array'],
            'question_bank_ids.*' => ['integer'],

            'assessment_subject_ids' => ['nullable', 'array'],
            'assessment_subject_ids.*' => ['integer'],

            'assessment_topic_ids' => ['nullable', 'array'],
            'assessment_topic_ids.*' => ['integer'],

            'question_type_codes' => ['nullable', 'array'],
            'question_type_codes.*' => ['string'],

            'difficulty_codes' => ['nullable', 'array'],
            'difficulty_codes.*' => ['string'],

            'cognitive_level_codes' => ['nullable', 'array'],
            'cognitive_level_codes.*' => ['string'],

            /*
            | Backward compatibility with old single-select payload.
            */
            'question_bank_id' => ['nullable', 'integer'],
            'assessment_subject_id' => ['nullable', 'integer'],
            'assessment_topic_id' => ['nullable', 'integer'],
            'question_type_code' => ['nullable', 'string'],
            'difficulty_code' => ['nullable', 'string'],
            'cognitive_level_code' => ['nullable', 'string'],

            'selection_mode' => ['nullable', 'string'],
            'number_of_questions' => ['nullable', 'integer', 'min:1'],
            'approved_only' => ['nullable', 'boolean'],
            'overwrite_existing' => ['nullable', 'boolean'],

            'marks_per_question' => ['nullable', 'numeric'],
            'negative_marks_per_question' => ['nullable', 'numeric'],
            'time_seconds_per_question' => ['nullable', 'integer'],
            'is_mandatory' => ['nullable', 'boolean'],
        ]);

        return ApiResponse::success(
            $this->service->bulkAssignQuestions($sectionId, $validated),
            'Questions assigned successfully.'
        );
    }

    public function removeAssessmentQuestion(int $assessmentQuestionId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->removeAssessmentQuestion($assessmentQuestionId),
            'Question removed from assessment successfully.'
        );
    }

    private function validateSection(Request $request): array
    {
        return $request->validate([
            'assessment_subject_id' => ['nullable', 'integer'],
            'section_code' => ['required', 'string', 'max:80'],
            'section_title' => ['required', 'string', 'max:150'],
            'instructions' => ['nullable', 'string'],
            'total_questions' => ['nullable', 'integer'],
            'total_marks' => ['nullable', 'numeric'],
            'passing_marks' => ['nullable', 'numeric'],
            'duration_minutes' => ['nullable', 'integer'],
            'question_selection_mode_code' => ['nullable', 'string'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer'],
            'status_code' => ['nullable', 'string'],
        ]);
    }
}