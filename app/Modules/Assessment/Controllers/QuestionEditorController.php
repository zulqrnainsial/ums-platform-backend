<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\QuestionEditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Assessment\Imports\QuestionExcelImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Modules\Assessment\Services\QuestionNlpSuggestionService;
class QuestionEditorController extends Controller
{
    public function __construct(
        private readonly QuestionEditorService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->list($request->all()),
            'Questions fetched successfully.'
        );
    }
public function qualityDashboard(Request $request): JsonResponse
{
    return ApiResponse::success(
        $this->service->qualityDashboard($request->all()),
        'Question bank quality dashboard fetched successfully.'
    );
}
    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(
            $this->service->show($id),
            'Question fetched successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        return ApiResponse::success(
            $this->service->save($validated),
            'Question created successfully.'
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $this->validatePayload($request);

        return ApiResponse::success(
            $this->service->save($validated, $id),
            'Question updated successfully.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::success(null, 'Question deleted successfully.');
    }

    public function bulkImport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.question_bank_code' => ['required', 'string'],
            'rows.*.question_text' => ['required', 'string'],
            'rows.*.question_type_code' => ['required', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->bulkImport($validated['rows']),
            'Questions imported successfully.'
        );
    }
public function importExcel(Request $request): JsonResponse
{
    $validated = $request->validate([
        'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
    ]);

    $import = new QuestionExcelImport();

    Excel::import($import, $validated['file']);

    return ApiResponse::success(
        $import->result(),
        'Question Excel file imported successfully.'
    );
}
public function suggestMetadata(
    Request $request,
    QuestionNlpSuggestionService $service
): JsonResponse {
    $validated = $request->validate([
        'question_text' => ['nullable', 'string'],
        'question_html' => ['nullable', 'string'],
    ]);

    if (empty($validated['question_text'] ?? null) && empty($validated['question_html'] ?? null)) {
        return ApiResponse::error(
            'Question text is required to suggest metadata.',
            422
        );
    }

    return ApiResponse::success(
        $service->suggest($validated),
        'Question metadata suggestion generated successfully.'
    );
}
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'question_bank_id' => ['required', 'integer'],
            'assessment_subject_id' => ['nullable', 'integer'],
            'assessment_topic_id' => ['nullable', 'integer'],

            'question_type_code' => ['required', 'string', 'max:80'],
            'difficulty_code' => ['nullable', 'string', 'max:80'],
            'bloom_level_code' => ['nullable', 'string', 'max:80'],
            'obe_level_code' => ['nullable', 'string', 'max:80'],
            'learning_outcome_code' => ['nullable', 'string', 'max:100'],
            'course_outcome_code' => ['nullable', 'string', 'max:100'],
            'metadata_json' => ['nullable', 'array'],

            'question_text' => ['required', 'string'],
            'question_html' => ['nullable', 'string'],

            'default_marks' => ['nullable', 'numeric'],
            'default_negative_marks' => ['nullable', 'numeric'],
            'default_time_seconds' => ['nullable', 'integer'],

            'explanation' => ['nullable', 'string'],
            'explanation_html' => ['nullable', 'string'],

            'approval_status_code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'external_ref_no' => ['nullable', 'string', 'max:100'],

            'options' => ['nullable', 'array'],
            'options.*.option_key' => ['nullable', 'string', 'max:20'],
            'options.*.option_text' => ['nullable', 'string'],
            'options.*.option_html' => ['nullable', 'string'],
            'options.*.is_correct' => ['nullable', 'boolean'],
            'options.*.correct_order' => ['nullable', 'integer'],
            'options.*.match_key' => ['nullable', 'string', 'max:80'],
            'options.*.correct_match_key' => ['nullable', 'string', 'max:80'],
            'options.*.marks_percentage' => ['nullable', 'numeric'],
            'options.*.display_order' => ['nullable', 'integer'],

            'answer_keys' => ['nullable', 'array'],
            'answer_keys.*.answer_text' => ['nullable', 'string'],
            'answer_keys.*.answer_number' => ['nullable', 'numeric'],
            'answer_keys.*.accepted_variants_json' => ['nullable', 'array'],
            'answer_keys.*.case_sensitive' => ['nullable', 'boolean'],
            'answer_keys.*.numeric_tolerance' => ['nullable', 'numeric'],
            'answer_keys.*.marks_percentage' => ['nullable', 'numeric'],
            'answer_keys.*.status_code' => ['nullable', 'string', 'max:50'],
        ]);
    }
}