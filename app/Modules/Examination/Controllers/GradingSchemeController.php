<?php

namespace App\Modules\Examination\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Examination\Services\GradingSchemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradingSchemeController extends Controller
{
    public function context(
        GradingSchemeService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->context(),
            'message' => 'Grading scheme context fetched successfully.',
        ]);
    }

    public function index(
        Request $request,
        GradingSchemeService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->schemes($request->all()),
            'message' => 'Grading schemes fetched successfully.',
        ]);
    }

    public function show(
        int $gradingScheme,
        GradingSchemeService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->schemeDetail($gradingScheme),
            'message' => 'Grading scheme fetched successfully.',
        ]);
    }

    public function store(
        Request $request,
        GradingSchemeService $service
    ): JsonResponse {
        $validated = $this->schemeRules($request, false);

        return response()->json([
            'data' => $service->createScheme($validated),
            'message' => 'Grading scheme created successfully.',
        ], 201);
    }

    public function update(
        int $gradingScheme,
        Request $request,
        GradingSchemeService $service
    ): JsonResponse {
        $validated = $this->schemeRules($request, true);

        return response()->json([
            'data' => $service->updateScheme($gradingScheme, $validated),
            'message' => 'Grading scheme updated successfully.',
        ]);
    }

    public function setStatus(
        int $gradingScheme,
        Request $request,
        GradingSchemeService $service
    ): JsonResponse {
        $validated = $request->validate([
            'status_code' => ['required', 'string', 'in:active,inactive'],
        ]);

        return response()->json([
            'data' => $service->setStatus(
                $gradingScheme,
                $validated['status_code']
            ),
            'message' => 'Grading scheme status updated successfully.',
        ]);
    }

    public function rows(
        int $gradingScheme,
        GradingSchemeService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->rows($gradingScheme),
            'message' => 'Grading scheme rows fetched successfully.',
        ]);
    }

    public function saveRows(
        int $gradingScheme,
        Request $request,
        GradingSchemeService $service
    ): JsonResponse {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],

            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.sort_order' => ['required', 'integer', 'min:1'],

            'rows.*.grade_letter' => ['required', 'string', 'max:20'],
            'rows.*.grade_point' => ['required', 'numeric', 'min:0', 'max:10'],
            'rows.*.is_pass' => ['nullable', 'boolean'],

            'rows.*.minimum_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.maximum_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'rows.*.minimum_percentile' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.maximum_percentile' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'rows.*.minimum_rank' => ['nullable', 'integer', 'min:1'],
            'rows.*.maximum_rank' => ['nullable', 'integer', 'min:1'],

            'rows.*.minimum_z_score' => ['nullable', 'numeric'],
            'rows.*.maximum_z_score' => ['nullable', 'numeric'],

            'rows.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'data' => $service->saveRows(
                $gradingScheme,
                $validated['rows']
            ),
            'message' => 'Grading scheme ready reckoner saved successfully.',
        ]);
    }

    private function schemeRules(
        Request $request,
        bool $isUpdate
    ): array {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'examination_rule_set_id' => ['nullable', 'integer'],

            'scheme_code' => [$required, 'string', 'max:100'],
            'scheme_name' => [$required, 'string', 'max:255'],

            'grading_method_code' => [
                $required,
                'string',
                'in:absolute,relative_percentile,relative_rank,relative_z_score,pass_fail',
            ],

            'is_default' => ['nullable', 'boolean'],
            'status_code' => ['nullable', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ]);
    }
}