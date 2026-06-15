<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\AdmissionMeritFormulaBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionMeritFormulaBuilderController extends Controller
{
    public function __construct(
        private readonly AdmissionMeritFormulaBuilderService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->index($request->all()),
            'Merit formulas fetched successfully.'
        );
    }

    public function show(int $formulaId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->show($formulaId),
            'Merit formula builder data fetched successfully.'
        );
    }
public function sourceCatalog(): JsonResponse
{
    return ApiResponse::success(
        $this->service->sourceCatalog(),
        'Merit formula source catalog fetched successfully.'
    );
}
    public function storeFormula(Request $request): JsonResponse
    {
        $validated = $this->validateFormula($request);

        return ApiResponse::success(
            $this->service->storeFormula($validated),
            'Merit formula created successfully.'
        );
    }

    public function updateFormula(Request $request, int $formulaId): JsonResponse
    {
        $validated = $this->validateFormula($request);

        return ApiResponse::success(
            $this->service->updateFormula($formulaId, $validated),
            'Merit formula updated successfully.'
        );
    }

    public function deleteFormula(int $formulaId): JsonResponse
    {
        $this->service->deleteFormula($formulaId);

        return ApiResponse::success(null, 'Merit formula deleted successfully.');
    }

    public function storeComponent(Request $request, int $formulaId): JsonResponse
    {
        $validated = $this->validateComponent($request);

        return ApiResponse::success(
            $this->service->storeComponent($formulaId, $validated),
            'Formula component created successfully.'
        );
    }

    public function updateComponent(Request $request, int $componentId): JsonResponse
    {
        $validated = $this->validateComponent($request);

        return ApiResponse::success(
            $this->service->updateComponent($componentId, $validated),
            'Formula component updated successfully.'
        );
    }

    public function deleteComponent(int $componentId): JsonResponse
    {
        $this->service->deleteComponent($componentId);

        return ApiResponse::success(null, 'Formula component deleted successfully.');
    }

    public function storeApplicability(Request $request, int $formulaId): JsonResponse
    {
        $validated = $this->validateApplicability($request);

        return ApiResponse::success(
            $this->service->storeApplicability($formulaId, $validated),
            'Formula applicability created successfully.'
        );
    }

    public function updateApplicability(Request $request, int $applicabilityId): JsonResponse
    {
        $validated = $this->validateApplicability($request);

        return ApiResponse::success(
            $this->service->updateApplicability($applicabilityId, $validated),
            'Formula applicability updated successfully.'
        );
    }

    public function deleteApplicability(int $applicabilityId): JsonResponse
    {
        $this->service->deleteApplicability($applicabilityId);

        return ApiResponse::success(null, 'Formula applicability deleted successfully.');
    }

    private function validateFormula(Request $request): array
    {
        return $request->validate([
            'admission_session_id' => ['nullable', 'integer'],
            'code' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],

            /*
             | Must be string code, not numeric lookup id.
             */
            'formula_type_code' => ['required', 'string', 'max:80'],

            'total_weight' => ['required', 'numeric', 'min:0'],
            'passing_merit_score' => ['nullable', 'numeric', 'min:0'],
            'rounding_precision' => ['nullable', 'integer', 'min:0', 'max:6'],
            'tie_breaker_json' => ['nullable', 'array'],
            'rules_json' => ['nullable', 'array'],
            'status_code' => ['required', 'string', 'max:80'],
        ]);
    }

    private function validateComponent(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],

            /*
             | These must be string codes, not numeric lookup ids.
             */
            'component_type_code' => ['required', 'string', 'max:80'],
            'source_type_code' => ['required', 'string', 'max:100'],
            'calculation_method_code' => ['required', 'string', 'max:100'],

            'source_key' => ['nullable', 'string', 'max:120'],
            'weight' => ['required', 'numeric', 'min:0'],
            'max_raw_marks' => ['nullable', 'numeric', 'min:0'],
            'normalize_to' => ['required', 'numeric', 'min:1'],
            'minimum_required_score' => ['nullable', 'numeric', 'min:0'],

            'is_required' => ['nullable', 'boolean'],
            'include_in_total' => ['nullable', 'boolean'],
            'allow_bonus' => ['nullable', 'boolean'],
            'allow_negative' => ['nullable', 'boolean'],

            'conditions_json' => ['nullable', 'array'],
            'source_mapping_json' => ['nullable', 'array'],

            'display_order' => ['nullable', 'integer', 'min:0'],
            'status_code' => ['required', 'string', 'max:80'],
        ]);
    }

    private function validateApplicability(Request $request): array
    {
        return $request->validate([
            /*
             | String code.
             */
            'applicability_scope_code' => ['required', 'string', 'max:100'],

            /*
             | FK IDs.
             */
            'admission_session_id' => ['nullable', 'integer'],
            'admission_preference_group_id' => ['nullable', 'integer'],
            'offered_program_id' => ['nullable', 'integer'],
            'program_quota_seat_id' => ['nullable', 'integer'],

            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date'],
            'is_default' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'status_code' => ['required', 'string', 'max:80'],
        ]);
    }
}