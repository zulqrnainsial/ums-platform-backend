<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\AdmissionMeritCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionMeritCalculationController extends Controller
{
    public function __construct(
        private readonly AdmissionMeritCalculationService $service
    ) {
    }

    public function calculateForApplicant(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'applicant_id' => ['required', 'integer'],
            'admission_merit_formula_id' => ['required', 'integer'],
            'admission_application_id' => ['nullable', 'integer'],
            'admission_session_id' => ['nullable', 'integer'],
            'offered_program_id' => ['nullable', 'integer'],
            'admission_preference_group_id' => ['nullable', 'integer'],
            'program_quota_seat_id' => ['nullable', 'integer'],
        ]);

        return ApiResponse::success(
            $this->service->calculateForApplicant($validated),
            'Applicant merit score calculated successfully.'
        );
    }

    public function bulkCalculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'applicant_ids' => ['nullable', 'array'],
            'applicant_ids.*' => ['integer'],
            'admission_merit_formula_id' => ['required', 'integer'],
            'admission_session_id' => ['nullable', 'integer'],
            'offered_program_id' => ['nullable', 'integer'],
            'admission_preference_group_id' => ['nullable', 'integer'],
            'program_quota_seat_id' => ['nullable', 'integer'],
            'applicant_status_code' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->bulkCalculate($validated),
            'Bulk merit calculation completed.'
        );
    }
}