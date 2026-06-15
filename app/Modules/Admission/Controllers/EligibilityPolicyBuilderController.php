<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\EligibilityPolicyBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EligibilityPolicyBuilderController extends Controller
{
    public function __construct(
        private readonly EligibilityPolicyBuilderService $service
    ) {
    }

    public function lookups(): JsonResponse
    {
        return ApiResponse::success(
            $this->service->lookups(),
            'Eligibility builder lookups fetched successfully.'
        );
    }

    public function show(Request $request, int $offeredProgramId): JsonResponse
    {
        $quotaSeatId = $request->query('program_quota_seat_id');

        return ApiResponse::success(
            $this->service->getPolicy(
                offeredProgramId: $offeredProgramId,
                quotaSeatId: $quotaSeatId ? (int) $quotaSeatId : null
            ),
            'Eligibility policy fetched successfully.'
        );
    }

    public function save(Request $request, int $offeredProgramId): JsonResponse
    {
        $validated = $request->validate([
            'program_quota_seat_id' => ['nullable', 'integer', 'exists:program_quota_seats,id'],

            'qualifications' => ['nullable', 'array'],
            'qualifications.*.qualification_level_ids' => ['nullable', 'array'],
            'qualifications.*.qualification_level_ids.*' => ['nullable', 'integer'],
            
            'qualifications.*.subject_group_ids' => ['nullable', 'array'],
            'qualifications.*.subject_group_ids.*' => ['nullable', 'integer'],
            
            'qualifications.*.qualification_level_codes' => ['nullable', 'array'],
            'qualifications.*.subject_group_codes' => ['nullable', 'array'],
            
            'qualifications.*.minimum_percentage' => ['nullable', 'numeric'],
            'qualifications.*.minimum_marks' => ['nullable', 'numeric'],
            'qualifications.*.minimum_cgpa' => ['nullable', 'numeric'],
            'qualifications.*.required' => ['nullable', 'boolean'],

            'tests' => ['nullable', 'array'],
            'tests.required' => ['nullable', 'boolean'],
            'tests.accepted_assessment_ids' => ['nullable', 'array'],
            'tests.accepted_assessment_ids.*' => ['nullable', 'integer'],
            'tests.accepted_test_codes' => ['nullable', 'array'],
            
            'tests.minimum_percentage' => ['nullable', 'numeric'],
            'tests.minimum_marks' => ['nullable', 'numeric'],
            'tests.minimum_percentile' => ['nullable', 'numeric'],

            'documents' => ['nullable', 'array'],
            'documents.required_document_type_ids' => ['nullable', 'array'],
            'documents.required_document_type_ids.*' => ['nullable', 'integer'],
            'documents.required_document_codes' => ['nullable', 'array'],

            'age' => ['nullable', 'array'],
            'age.enabled' => ['nullable', 'boolean'],
            'age.operator' => ['nullable', 'string', 'in:<=,>=,<,>,='],
            'age.value' => ['nullable', 'numeric'],

            'gender' => ['nullable', 'array'],
            'gender.enabled' => ['nullable', 'boolean'],
            'gender.allowed_values' => ['nullable', 'array'],
            'gender.allowed_values.*' => ['nullable', 'string'],

            'domicile' => ['nullable', 'array'],
            'domicile.enabled' => ['nullable', 'boolean'],
            'domicile.province_ids' => ['nullable', 'array'],
            'domicile.province_ids.*' => ['nullable', 'integer'],
            'domicile.district_ids' => ['nullable', 'array'],
            'domicile.district_ids.*' => ['nullable', 'integer'],
        ]);

        $quotaSeatId = $validated['program_quota_seat_id'] ?? null;
        unset($validated['program_quota_seat_id']);

        return ApiResponse::success(
            $this->service->savePolicy(
                offeredProgramId: $offeredProgramId,
                policy: $validated,
                quotaSeatId: $quotaSeatId ? (int) $quotaSeatId : null
            ),
            'Eligibility policy saved successfully.'
        );
    }
}
