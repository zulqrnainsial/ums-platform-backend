<?php

namespace App\Modules\Examination\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Examination\Services\EvaluationSchemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationSchemeController extends Controller
{
    public function context(EvaluationSchemeService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->context(),
            'message' => 'Evaluation scheme context fetched successfully.',
        ]);
    }

    public function index(Request $request, EvaluationSchemeService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->schemes($request->all()),
            'message' => 'Evaluation schemes fetched successfully.',
        ]);
    }

    public function show(int $evaluationScheme, EvaluationSchemeService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->detail($evaluationScheme),
            'message' => 'Evaluation scheme fetched successfully.',
        ]);
    }

    public function store(Request $request, EvaluationSchemeService $service): JsonResponse
    {
        $validated = $this->schemeRules($request, false);

        return response()->json([
            'data' => $service->createScheme($validated),
            'message' => 'Evaluation scheme created successfully.',
        ], 201);
    }

    public function update(
        int $evaluationScheme,
        Request $request,
        EvaluationSchemeService $service
    ): JsonResponse {
        $validated = $this->schemeRules($request, true);

        return response()->json([
            'data' => $service->updateScheme($evaluationScheme, $validated),
            'message' => 'Evaluation scheme updated successfully.',
        ]);
    }

    public function setStatus(
        int $evaluationScheme,
        Request $request,
        EvaluationSchemeService $service
    ): JsonResponse {
        $validated = $request->validate([
            'status_code' => ['required', 'string', 'in:active,inactive'],
        ]);

        return response()->json([
            'data' => $service->setStatus(
                $evaluationScheme,
                $validated['status_code']
            ),
            'message' => 'Evaluation scheme status updated successfully.',
        ]);
    }

    public function saveStructure(
        int $evaluationScheme,
        Request $request,
        EvaluationSchemeService $service
    ): JsonResponse {
        $validated = $request->validate([
            'components' => ['required', 'array', 'min:1'],

            'components.*.id' => ['nullable', 'integer'],
            'components.*.component_code' => ['required', 'string', 'max:100'],
            'components.*.component_name' => ['required', 'string', 'max:255'],
            'components.*.component_type_code' => [
                'required',
                'string',
                'in:sessional,midterm,final,practical,viva,project,internship,other',
            ],
            'components.*.evaluation_part_code' => [
                'required',
                'string',
                'in:theory,practical,combined',
            ],
            'components.*.weightage_percentage' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100',
            ],
            'components.*.is_mandatory' => ['nullable', 'boolean'],
            'components.*.requires_separate_pass' => ['nullable', 'boolean'],
            'components.*.sort_order' => ['required', 'integer', 'min:1'],
            'components.*.status_code' => ['nullable', 'string', 'in:active,inactive'],
            'components.*.remarks' => ['nullable', 'string'],

            'components.*.items' => ['nullable', 'array'],
            'components.*.items.*.id' => ['nullable', 'integer'],
            'components.*.items.*.item_code' => ['required', 'string', 'max:100'],
            'components.*.items.*.item_name' => ['required', 'string', 'max:255'],
            'components.*.items.*.item_type_code' => [
                'required',
                'string',
                'in:quiz,assignment,test,presentation,lab_task,lab_viva,project_task,other',
            ],
            'components.*.items.*.weightage_percentage' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100',
            ],
            'components.*.items.*.is_mandatory' => ['nullable', 'boolean'],
            'components.*.items.*.sort_order' => ['required', 'integer', 'min:1'],
            'components.*.items.*.status_code' => ['nullable', 'string', 'in:active,inactive'],
            'components.*.items.*.remarks' => ['nullable', 'string'],
        ]);

        return response()->json([
            'data' => $service->saveStructure(
                $evaluationScheme,
                $validated['components']
            ),
            'message' => 'Evaluation scheme structure saved successfully.',
        ]);
    }

    private function schemeRules(Request $request, bool $isUpdate): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'examination_rule_set_id' => ['nullable', 'integer'],
            'scheme_code' => [$required, 'string', 'max:100'],
            'scheme_name' => [$required, 'string', 'max:255'],
            'evaluation_mode_code' => [
                'nullable',
                'string',
                'in:combined,separate_theory_practical',
            ],
            'total_weightage_percentage' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'status_code' => ['nullable', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ]);
    }
}