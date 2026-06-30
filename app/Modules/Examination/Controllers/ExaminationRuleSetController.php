<?php

namespace App\Modules\Examination\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Examination\Services\ExaminationRuleSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExaminationRuleSetController extends Controller
{
    public function context(
        Request $request,
        ExaminationRuleSetService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->context($request->all()),
            'message' => 'Examination rule set context fetched successfully.',
        ]);
    }

    public function index(
        Request $request,
        ExaminationRuleSetService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->ruleSets($request->all()),
            'message' => 'Examination rule sets fetched successfully.',
        ]);
    }

    public function show(
        int $ruleSet,
        ExaminationRuleSetService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->ruleSetDetail($ruleSet),
            'message' => 'Examination rule set fetched successfully.',
        ]);
    }

    public function store(
        Request $request,
        ExaminationRuleSetService $service
    ): JsonResponse {
        $validated = $this->ruleSetValidation($request, false);

        return response()->json([
            'data' => $service->createRuleSet($validated),
            'message' => 'Examination rule set created successfully.',
        ], 201);
    }

    public function update(
        int $ruleSet,
        Request $request,
        ExaminationRuleSetService $service
    ): JsonResponse {
        $validated = $this->ruleSetValidation($request, true);

        return response()->json([
            'data' => $service->updateRuleSet($ruleSet, $validated),
            'message' => 'Examination rule set updated successfully.',
        ]);
    }

    public function setStatus(
        int $ruleSet,
        Request $request,
        ExaminationRuleSetService $service
    ): JsonResponse {
        $validated = $request->validate([
            'status_code' => ['required', 'string', 'in:active,inactive'],
        ]);

        return response()->json([
            'data' => $service->setRuleSetStatus(
                $ruleSet,
                $validated['status_code']
            ),
            'message' => 'Examination rule set status updated successfully.',
        ]);
    }

    public function bindings(
        Request $request,
        ExaminationRuleSetService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->bindings($request->all()),
            'message' => 'Examination rule set bindings fetched successfully.',
        ]);
    }

    public function saveBinding(
        Request $request,
        ExaminationRuleSetService $service
    ): JsonResponse {
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'examination_rule_set_id' => ['required', 'integer'],

            'program_id' => ['nullable', 'integer'],
            'curriculum_id' => ['nullable', 'integer'],
            'student_batch_id' => ['nullable', 'integer'],

            'academic_session_id' => ['nullable', 'integer'],
            'academic_term_id' => ['nullable', 'integer'],

            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],

            'is_active' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'data' => $service->saveBinding($validated),
            'message' => 'Examination rule set binding saved successfully.',
        ]);
    }

    private function ruleSetValidation(
        Request $request,
        bool $isUpdate
    ): array {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'rule_set_code' => [$required, 'string', 'max:100'],
            'rule_set_name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'gpa_enabled' => ['nullable', 'boolean'],
            'obe_enabled' => ['nullable', 'boolean'],

            'grading_method_code' => [
                'nullable',
                'string',
                'in:absolute,relative,pass_fail',
            ],

            'marks_basis_code' => [
                'nullable',
                'string',
                'in:curriculum_subject_marks,credit_hour_based,fixed_marks,custom_marks',
            ],

            'marks_per_credit_hour' => ['nullable', 'numeric', 'min:0'],
            'fixed_total_marks' => ['nullable', 'numeric', 'min:0'],

            'theory_practical_evaluation_code' => [
                'nullable',
                'string',
                'in:combined,separate_pass_required',
            ],

            'subject_pass_basis_code' => [
                'nullable',
                'string',
                'in:marks,gpa,obe_attainment,marks_and_obe,gpa_and_obe',
            ],

            'minimum_subject_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_theory_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_practical_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'promotion_enabled' => ['nullable', 'boolean'],
            'probation_enabled' => ['nullable', 'boolean'],
            'detention_enabled' => ['nullable', 'boolean'],
            'drop_enabled' => ['nullable', 'boolean'],

            'minimum_semester_gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'minimum_cgpa' => ['nullable', 'numeric', 'min:0', 'max:4'],

            'maximum_failed_courses' => ['nullable', 'integer', 'min:0'],
            'maximum_attempts_per_subject' => ['nullable', 'integer', 'min:1'],
            'maximum_probation_terms' => ['nullable', 'integer', 'min:1'],

            're_registration_enabled' => ['nullable', 'boolean'],
            'improvement_enabled' => ['nullable', 'boolean'],
            'improvement_allowed_below_grade_point' => ['nullable', 'numeric', 'min:0', 'max:4'],

            'transcript_enabled' => ['nullable', 'boolean'],
            'include_obe_in_result_decision' => ['nullable', 'boolean'],

            'status_code' => ['nullable', 'string', 'in:active,inactive'],
        ]);
    }
}