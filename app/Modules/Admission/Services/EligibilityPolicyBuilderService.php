<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\EligibilityRuleType;
use App\Modules\Admission\Models\OfferedProgram;
use App\Modules\Admission\Models\ProgramEligibilityRule;
use App\Modules\Admission\Models\ProgramQuotaSeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EligibilityPolicyBuilderService
{
    public function getPolicy(int $offeredProgramId, ?int $quotaSeatId = null): array
    {
        $tenantId = $this->tenantId();

        $offeredProgram = OfferedProgram::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $offeredProgramId)
            ->firstOrFail();

        $quotaSeat = null;

        if ($quotaSeatId) {
            $quotaSeat = ProgramQuotaSeat::query()
                ->where('tenant_id', $tenantId)
                ->where('offered_program_id', $offeredProgram->id)
                ->where('id', $quotaSeatId)
                ->firstOrFail();
        }

        $rules = ProgramEligibilityRule::query()
            ->where('tenant_id', $tenantId)
            ->where('offered_program_id', $offeredProgram->id)
            ->when(
                $quotaSeatId,
                fn ($query) => $query->where('program_quota_seat_id', $quotaSeatId),
                fn ($query) => $query->whereNull('program_quota_seat_id')
            )
            ->where('is_active', true)
            ->get()
            ->keyBy(fn ($rule) => strtoupper((string) $rule->rule_code));

        return [
            'offered_program' => $this->formatOfferedProgram($offeredProgram),
            'quota_options' => $this->quotaOptions($offeredProgram->id),
            'quota' => $quotaSeat ? $this->formatQuotaSeat($quotaSeat) : null,
            'policy' => [
                'program_quota_seat_id' => $quotaSeatId,
                'qualifications' => $this->extractQualifications($rules),
                'tests' => $this->extractTests($rules),
                'documents' => $this->extractDocuments($rules),
                'age' => $this->extractAge($rules),
                'gender' => $this->extractGender($rules),
                'domicile' => $this->extractDomicile($rules),
            ],
        ];
    }

    public function savePolicy(int $offeredProgramId, array $policy, ?int $quotaSeatId = null): array
    {
        return DB::transaction(function () use ($offeredProgramId, $policy, $quotaSeatId) {
            $tenantId = $this->tenantId();

            $offeredProgram = OfferedProgram::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $offeredProgramId)
                ->firstOrFail();

            if ($quotaSeatId) {
                ProgramQuotaSeat::query()
                    ->where('tenant_id', $tenantId)
                    ->where('offered_program_id', $offeredProgram->id)
                    ->where('id', $quotaSeatId)
                    ->firstOrFail();
            }

            $this->upsertQualificationRule($tenantId, $offeredProgram, $policy, $quotaSeatId);
            $this->upsertTestRules($tenantId, $offeredProgram, $policy, $quotaSeatId);
            $this->upsertDocumentRule($tenantId, $offeredProgram, $policy, $quotaSeatId);
            $this->upsertAgeRule($tenantId, $offeredProgram, $policy, $quotaSeatId);
            $this->upsertGenderRule($tenantId, $offeredProgram, $policy, $quotaSeatId);
            $this->upsertDomicileRule($tenantId, $offeredProgram, $policy, $quotaSeatId);

            return $this->getPolicy($offeredProgramId, $quotaSeatId);
        });
    }

    public function lookups(): array
{
    return [
        'offered_programs' => $this->offeredPrograms(),
        'qualification_levels' => $this->lookupValues('QUALIFICATION_LEVEL'),
        'subject_groups' => $this->lookupValues('SUBJECT_GROUP'),

        // This is required for accepted_assessment_ids display.
        'assessments' => $this->assessments(),

        // Keep this only for old compatibility.
        'test_types' => $this->lookupValues('TEST_TYPE'),

        'document_types' => $this->lookupValues('DOCUMENT_TYPE'),
        'genders' => [
            ['label' => 'Male', 'value' => 'male'],
            ['label' => 'Female', 'value' => 'female'],
            ['label' => 'Other', 'value' => 'other'],
        ],
        'domicile_provinces' => $this->lookupValues('PROVINCE'),
        'domicile_districts' => $this->lookupValues('CITY'),
    ];
}

    private function upsertQualificationRule(
    int $tenantId,
    OfferedProgram $offeredProgram,
    array $policy,
    ?int $quotaSeatId
): void {
    $qualifications = array_values(array_filter(
        array_map(function ($item) {
            $qualificationLevelIds = $this->normalizeIdArray(
                $item['qualification_level_ids'] ?? []
            );

            $subjectGroupIds = $this->normalizeIdArray(
                $item['subject_group_ids'] ?? []
            );

            $qualificationLevelCodes = $this->lookupCodesByIds($qualificationLevelIds);

            if (count($qualificationLevelCodes) === 0) {
                $qualificationLevelCodes = $this->normalizeCodeArray(
                    $item['qualification_level_codes'] ?? []
                );
            }

            $subjectGroupCodes = $this->lookupCodesByIds($subjectGroupIds);

            if (count($subjectGroupCodes) === 0) {
                $subjectGroupCodes = $this->normalizeCodeArray(
                    $item['subject_group_codes'] ?? []
                );
            }

            return [
                'qualification_level_ids' => $qualificationLevelIds,
                'qualification_level_codes' => $qualificationLevelCodes,

                'subject_group_ids' => $subjectGroupIds,
                'subject_group_codes' => $subjectGroupCodes,

                'minimum_percentage' => $item['minimum_percentage'] ?? null,
                'minimum_marks' => $item['minimum_marks'] ?? null,
                'minimum_cgpa' => $item['minimum_cgpa'] ?? null,
                'required' => (bool) ($item['required'] ?? true),
            ];
        }, $policy['qualifications'] ?? []),
        function ($item) {
            return count($item['qualification_level_ids']) > 0
                || count($item['qualification_level_codes']) > 0
                || count($item['subject_group_ids']) > 0
                || count($item['subject_group_codes']) > 0
                || ($item['minimum_percentage'] ?? null) !== null
                || ($item['minimum_marks'] ?? null) !== null
                || ($item['minimum_cgpa'] ?? null) !== null;
        }
    ));

    if (count($qualifications) === 0) {
        $this->deactivateRule($tenantId, $offeredProgram->id, $quotaSeatId, 'QUALIFICATION_REQUIRED');
        return;
    }

    $this->upsertRule(
        tenantId: $tenantId,
        offeredProgramId: $offeredProgram->id,
        quotaSeatId: $quotaSeatId,
        ruleCode: 'QUALIFICATION_REQUIRED',
        ruleTitle: 'Qualification Requirements',
        ruleGroup: 'qualification',
        evaluatorKey: 'qualification_exists',
        sourceField: null,
        operator: 'required',
        valueNumber: null,
        valueText: null,
        valueJson: [
            'required_qualifications' => $qualifications,
        ],
        failureMessage: 'Required qualification criteria not fulfilled.',
        displayOrder: 10
    );
}

    private function upsertTestRules(
    int $tenantId,
    OfferedProgram $offeredProgram,
    array $policy,
    ?int $quotaSeatId
): void {
    $tests = $policy['tests'] ?? [];

    $required = (bool) ($tests['required'] ?? false);

    $acceptedAssessmentIds = $this->normalizeIdArray(
        $tests['accepted_assessment_ids'] ?? []
    );

    $acceptedAssessmentCodes = $this->assessmentCodesByIds($acceptedAssessmentIds);

    $acceptedTestCodes = $this->normalizeCodeArray(
        $tests['accepted_test_codes'] ?? []
    );

    if (count($acceptedTestCodes) === 0) {
        $acceptedTestCodes = $acceptedAssessmentCodes;
    }

    $minimumPercentage = $tests['minimum_percentage'] ?? null;
    $minimumMarks = $tests['minimum_marks'] ?? null;
    $minimumPercentile = $tests['minimum_percentile'] ?? null;

    if (!$required && count($acceptedAssessmentIds) === 0 && count($acceptedTestCodes) === 0) {
        $this->deactivateRule($tenantId, $offeredProgram->id, $quotaSeatId, 'ADMISSION_TEST_REQUIRED');
        $this->deactivateRule($tenantId, $offeredProgram->id, $quotaSeatId, 'ADMISSION_TEST_MINIMUM');
        return;
    }

    if ($required) {
        $this->upsertRule(
            tenantId: $tenantId,
            offeredProgramId: $offeredProgram->id,
            quotaSeatId: $quotaSeatId,
            ruleCode: 'ADMISSION_TEST_REQUIRED',
            ruleTitle: 'Admission Test Required',
            ruleGroup: 'test',
            evaluatorKey: 'test_exists',
            sourceField: null,
            operator: 'required',
            valueNumber: null,
            valueText: implode(',', $acceptedTestCodes),
            valueJson: [
                'accepted_assessment_ids' => $acceptedAssessmentIds,
                'accepted_test_codes' => $acceptedTestCodes,
            ],
            failureMessage: 'Admission test is required for this program.',
            displayOrder: 20
        );
    } else {
        $this->deactivateRule($tenantId, $offeredProgram->id, $quotaSeatId, 'ADMISSION_TEST_REQUIRED');
    }

    if ($minimumPercentage !== null || $minimumMarks !== null || $minimumPercentile !== null) {
        $this->upsertRule(
            tenantId: $tenantId,
            offeredProgramId: $offeredProgram->id,
            quotaSeatId: $quotaSeatId,
            ruleCode: 'ADMISSION_TEST_MINIMUM',
            ruleTitle: 'Admission Test Minimum Score',
            ruleGroup: 'test',
            evaluatorKey: 'test_numeric_compare',
            sourceField: $minimumMarks !== null ? 'obtained_marks' : ($minimumPercentile !== null ? 'percentile' : 'percentage'),
            operator: '>=',
            valueNumber: $minimumMarks ?? $minimumPercentile ?? $minimumPercentage,
            valueText: implode(',', $acceptedTestCodes),
            valueJson: [
                'accepted_assessment_ids' => $acceptedAssessmentIds,
                'accepted_test_codes' => $acceptedTestCodes,
                'minimum_percentage' => $minimumPercentage,
                'minimum_marks' => $minimumMarks,
                'minimum_percentile' => $minimumPercentile,
            ],
            failureMessage: 'Minimum admission test score is not fulfilled.',
            displayOrder: 21
        );
    } else {
        $this->deactivateRule($tenantId, $offeredProgram->id, $quotaSeatId, 'ADMISSION_TEST_MINIMUM');
    }
}

    private function upsertDocumentRule(
    int $tenantId,
    OfferedProgram $offeredProgram,
    array $policy,
    ?int $quotaSeatId
): void {
    $documents = $policy['documents'] ?? [];

    $requiredDocumentTypeIds = $this->normalizeIdArray(
        $documents['required_document_type_ids'] ?? []
    );

    $requiredDocumentCodes = $this->lookupCodesByIds($requiredDocumentTypeIds);

    if (count($requiredDocumentCodes) === 0) {
        $requiredDocumentCodes = $this->normalizeCodeArray(
            $documents['required_document_codes'] ?? []
        );
    }

    if (count($requiredDocumentTypeIds) === 0 && count($requiredDocumentCodes) === 0) {
        $this->deactivateRule($tenantId, $offeredProgram->id, $quotaSeatId, 'DOCUMENTS_REQUIRED');
        return;
    }

    $this->upsertRule(
        tenantId: $tenantId,
        offeredProgramId: $offeredProgram->id,
        quotaSeatId: $quotaSeatId,
        ruleCode: 'DOCUMENTS_REQUIRED',
        ruleTitle: 'Required Documents',
        ruleGroup: 'document',
        evaluatorKey: 'document_exists',
        sourceField: null,
        operator: 'all_of',
        valueNumber: null,
        valueText: implode(',', $requiredDocumentCodes),
        valueJson: [
            'required_document_type_ids' => $requiredDocumentTypeIds,
            'required_document_codes' => $requiredDocumentCodes,
        ],
        failureMessage: 'Required documents are not uploaded.',
        displayOrder: 30
    );
}

    private function upsertAgeRule(int $tenantId, OfferedProgram $offeredProgram, array $policy, ?int $quotaSeatId): void
    {
        $age = $policy['age'] ?? [];

        if (!($age['enabled'] ?? false) || ($age['value'] ?? null) === null) {
            $this->deactivateRule($tenantId, $offeredProgram->id, $quotaSeatId, 'AGE_RULE');
            return;
        }

        $operator = $age['operator'] ?? '<=';
        $value = $age['value'];

        $this->upsertRule(
            tenantId: $tenantId,
            offeredProgramId: $offeredProgram->id,
            quotaSeatId: $quotaSeatId,
            ruleCode: 'AGE_RULE',
            ruleTitle: 'Age Rule',
            ruleGroup: 'applicant',
            evaluatorKey: 'age_compare',
            sourceArea: 'applicant',
            sourceField: 'date_of_birth',
            operator: $operator,
            valueNumber: $value,
            valueText: null,
            valueJson: ['operator' => $operator, 'value' => $value],
            failureMessage: 'Age criteria is not fulfilled.',
            displayOrder: 40
        );
    }

    private function upsertGenderRule(int $tenantId, OfferedProgram $offeredProgram, array $policy, ?int $quotaSeatId): void
    {
        $gender = $policy['gender'] ?? [];
        $allowed = array_values(array_filter($gender['allowed_values'] ?? []));

        if (!($gender['enabled'] ?? false) || count($allowed) === 0) {
            $this->deactivateRule($tenantId, $offeredProgram->id, $quotaSeatId, 'GENDER_RULE');
            return;
        }

        $this->upsertRule(
            tenantId: $tenantId,
            offeredProgramId: $offeredProgram->id,
            quotaSeatId: $quotaSeatId,
            ruleCode: 'GENDER_RULE',
            ruleTitle: 'Gender Rule',
            ruleGroup: 'applicant',
            evaluatorKey: 'applicant_field_match',
            sourceArea: 'applicant',
            sourceField: 'gender',
            operator: 'in',
            valueNumber: null,
            valueText: implode(',', $allowed),
            valueJson: ['values' => $allowed],
            failureMessage: 'Gender criteria is not fulfilled.',
            displayOrder: 50
        );
    }

    private function upsertDomicileRule(int $tenantId, OfferedProgram $offeredProgram, array $policy, ?int $quotaSeatId): void
    {
        $domicile = $policy['domicile'] ?? [];
        $provinceIds = array_values(array_filter($domicile['province_ids'] ?? []));
        $districtIds = array_values(array_filter($domicile['district_ids'] ?? []));

        if (!($domicile['enabled'] ?? false) || (count($provinceIds) === 0 && count($districtIds) === 0)) {
            $this->deactivateRule($tenantId, $offeredProgram->id, $quotaSeatId, 'DOMICILE_RULE');
            return;
        }

        $this->upsertRule(
            tenantId: $tenantId,
            offeredProgramId: $offeredProgram->id,
            quotaSeatId: $quotaSeatId,
            ruleCode: 'DOMICILE_RULE',
            ruleTitle: 'Domicile Rule',
            ruleGroup: 'applicant',
            evaluatorKey: 'applicant_lookup_match',
            sourceArea: 'applicant',
            sourceField: count($districtIds) > 0 ? 'domicile_district_id' : 'domicile_province_id',
            operator: 'in',
            valueNumber: null,
            valueText: null,
            valueJson: [
                'province_ids' => $provinceIds,
                'district_ids' => $districtIds,
                'lookup_ids' => count($districtIds) > 0 ? $districtIds : $provinceIds,
            ],
            failureMessage: 'Domicile criteria is not fulfilled.',
            displayOrder: 60
        );
    }

    private function upsertRule(
    int $tenantId,
    int $offeredProgramId,
    ?int $quotaSeatId,
    string $ruleCode,
    string $ruleTitle,
    string $ruleGroup,
    string $evaluatorKey,
    ?string $sourceArea,
    ?string $sourceField,
    string $operator,
    ?float $valueNumber,
    ?string $valueText,
    ?array $valueJson,
    string $failureMessage,
    int $displayOrder
): ProgramEligibilityRule {
        $ruleType = $this->ruleType($ruleCode, $ruleTitle, $evaluatorKey, $sourceArea, $sourceField);

        $match = [
            'tenant_id' => $tenantId,
            'offered_program_id' => $offeredProgramId,
            'program_quota_seat_id' => $quotaSeatId,
            'rule_code' => $ruleCode,
        ];

        $payload = [
            'tenant_id' => $tenantId,
            'offered_program_id' => $offeredProgramId,
            'program_quota_seat_id' => $quotaSeatId,
            'eligibility_rule_type_id' => $ruleType->id,
            'rule_code' => $ruleCode,
            'rule_title' => $ruleTitle,
            'rule_group' => $ruleGroup,
            'operator' => $operator,
            'value_number' => $valueNumber,
            'value_text' => $valueText,
            'value_json' => json_encode($valueJson),
            'failure_message' => $failureMessage,
            'is_mandatory' => true,
            'is_active' => true,
            'display_order' => $displayOrder,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ];

        $existingQuery = DB::table('program_eligibility_rules')
            ->where('tenant_id', $tenantId)
            ->where('offered_program_id', $offeredProgramId)
            ->where('rule_code', $ruleCode);

        $quotaSeatId
            ? $existingQuery->where('program_quota_seat_id', $quotaSeatId)
            : $existingQuery->whereNull('program_quota_seat_id');

        $existing = $existingQuery->first();

        if ($existing) {
            DB::table('program_eligibility_rules')
                ->where('id', $existing->id)
                ->update($this->filterColumns('program_eligibility_rules', $payload));

            return ProgramEligibilityRule::query()->findOrFail($existing->id);
        }

        $payload['created_by'] = auth()->id();
        $payload['created_at'] = now();

        $id = DB::table('program_eligibility_rules')->insertGetId(
            $this->filterColumns('program_eligibility_rules', $payload)
        );

        return ProgramEligibilityRule::query()->findOrFail($id);
    }

    private function deactivateRule(int $tenantId, int $offeredProgramId, ?int $quotaSeatId, string $ruleCode): void
    {
        $query = ProgramEligibilityRule::query()
            ->where('tenant_id', $tenantId)
            ->where('offered_program_id', $offeredProgramId)
            ->where('rule_code', $ruleCode);

        $quotaSeatId
            ? $query->where('program_quota_seat_id', $quotaSeatId)
            : $query->whereNull('program_quota_seat_id');

        $query->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);
    }

    private function ruleType(string $code, string $title, string $evaluatorKey, ?string $sourceArea, ?string $sourceField): EligibilityRuleType
    {
        $now = now();

        $payload = [
            'code' => $code,
            'name' => $title,
            'source_area' => $sourceArea,
            'evaluator_key' => $evaluatorKey,
            'source_field' => $sourceField,
            'description' => $title,
            'is_active' => true,
            'updated_at' => $now,
        ];

        $existing = DB::table('eligibility_rule_types')
            ->where('code', $code)
            ->first();

        if ($existing) {
            DB::table('eligibility_rule_types')
                ->where('id', $existing->id)
                ->update($this->filterColumns('eligibility_rule_types', $payload));

            return EligibilityRuleType::query()->findOrFail($existing->id);
        }

        $payload['created_at'] = $now;

        $id = DB::table('eligibility_rule_types')->insertGetId(
            $this->filterColumns('eligibility_rule_types', $payload)
        );

        return EligibilityRuleType::query()->findOrFail($id);
    }

    private function extractQualifications($rules): array
    {
        $json = $this->ruleJson($rules->get('QUALIFICATION_REQUIRED'));
        return $json['required_qualifications'] ?? [];
    }

    private function extractTests($rules): array
{
    $requiredRule = $rules->get('ADMISSION_TEST_REQUIRED');
    $minimumRule = $rules->get('ADMISSION_TEST_MINIMUM');

    $requiredJson = $this->ruleJson($requiredRule);
    $minimumJson = $this->ruleJson($minimumRule);

    return [
        'required' => (bool) $requiredRule,
        'accepted_assessment_ids' => $this->normalizeIdArray(
            $requiredJson['accepted_assessment_ids']
                ?? $minimumJson['accepted_assessment_ids']
                ?? []
        ),
        'accepted_test_codes' => $requiredJson['accepted_test_codes']
            ?? $minimumJson['accepted_test_codes']
            ?? [],
        'minimum_percentage' => $minimumJson['minimum_percentage'] ?? null,
        'minimum_marks' => $minimumJson['minimum_marks'] ?? null,
        'minimum_percentile' => $minimumJson['minimum_percentile'] ?? null,
    ];
}

    private function extractDocuments($rules): array
{
    $json = $this->ruleJson($rules->get('DOCUMENTS_REQUIRED'));

    return [
        'required_document_type_ids' => $this->normalizeIdArray(
            $json['required_document_type_ids'] ?? []
        ),
        'required_document_codes' => $json['required_document_codes'] ?? [],
    ];
}

    private function extractAge($rules): array
    {
        $rule = $rules->get('AGE_RULE');
        return [
            'enabled' => (bool) $rule,
            'operator' => $rule?->operator ?? '<=',
            'value' => $rule?->value_number,
        ];
    }

    private function extractGender($rules): array
    {
        $json = $this->ruleJson($rules->get('GENDER_RULE'));
        return [
            'enabled' => (bool) $rules->get('GENDER_RULE'),
            'allowed_values' => $json['values'] ?? [],
        ];
    }

    private function extractDomicile($rules): array
    {
        $json = $this->ruleJson($rules->get('DOMICILE_RULE'));
        return [
            'enabled' => (bool) $rules->get('DOMICILE_RULE'),
            'province_ids' => $json['province_ids'] ?? [],
            'district_ids' => $json['district_ids'] ?? [],
        ];
    }

    

    private function offeredPrograms(): array
    {
        $tenantId = $this->tenantId();

        return OfferedProgram::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('title')
            ->get()
            ->map(fn ($item) => [
                'label' => ($item->code ? $item->code . ' - ' : '') . $item->title,
                'value' => $item->id,
                'id' => $item->id,
                'code' => $item->code,
                'title' => $item->title,
            ])
            ->values()
            ->toArray();
    }

    private function quotaOptions(int $offeredProgramId): array
    {
        $tenantId = $this->tenantId();

        $query = ProgramQuotaSeat::query()
            ->where('tenant_id', $tenantId)
            ->where('offered_program_id', $offeredProgramId);

        if (Schema::hasColumn('program_quota_seats', 'status_code')) {
            $query->where('status_code', 'active');
        }

        if (Schema::hasColumn('program_quota_seats', 'display_order')) {
            $query->orderBy('display_order');
        }

        return $query
            ->orderBy('quota_name')
            ->get()
            ->map(fn ($item) => $this->formatQuotaSeat($item) + [
                'label' => $item->quota_name . ' (' . ($item->available_seats ?? 0) . ' seats)',
                'value' => $item->id,
            ])
            ->values()
            ->toArray();
    }

    private function lookupValues(string $categoryCode): array
    {
        return DB::table('lookup_values')
            ->join('lookup_categories', 'lookup_categories.id', '=', 'lookup_values.lookup_category_id')
            ->where('lookup_categories.code', $categoryCode)
            ->where(function ($query) {
                $query->whereNull('lookup_values.tenant_id')
                    ->orWhere('lookup_values.tenant_id', $this->tenantId());
            })
            ->where('lookup_values.status', 'active')
            ->select('lookup_values.id', 'lookup_values.code', 'lookup_values.name')
            ->when(Schema::hasColumn('lookup_values', 'display_order'), fn ($query) => $query->orderBy('lookup_values.display_order'))
            ->orderBy('lookup_values.name')
            ->get()
            ->map(fn ($item) => [
                'label' => $item->name,
                'value' => strtoupper((string) $item->code),
                'id' => $item->id,
                'code' => strtoupper((string) $item->code),
                'name' => $item->name,
            ])
            ->values()
            ->toArray();
    }

    
    
    private function lookupValuesAsIds(string $categoryCode): array
{
    if (!Schema::hasTable('lookup_values') || !Schema::hasTable('lookup_categories')) {
        return [];
    }

    return DB::table('lookup_values')
        ->join('lookup_categories', 'lookup_categories.id', '=', 'lookup_values.lookup_category_id')
        ->where('lookup_categories.code', $categoryCode)
        ->when(Schema::hasColumn('lookup_values', 'status'), function ($q) {
            $q->where('lookup_values.status', 'active');
        })
        ->select(
            'lookup_values.id',
            'lookup_values.code',
            'lookup_values.name'
        )
        ->orderBy('lookup_values.display_order')
        ->orderBy('lookup_values.name')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->name,
            'value' => (int) $item->id,
            'id' => (int) $item->id,
            'code' => strtoupper((string) $item->code),
            'name' => $item->name,
        ])
        ->values()
        ->toArray();
}
private function assessments(): array
{
    if (!Schema::hasTable('assessments')) {
        return [];
    }

    $codeExpression = Schema::hasColumn('assessments', 'code')
        ? 'code'
        : 'CONCAT("ASM-", id)';

    if (Schema::hasColumn('assessments', 'title')) {
        $titleExpression = 'title';
    } elseif (Schema::hasColumn('assessments', 'assessment_title')) {
        $titleExpression = 'assessment_title';
    } else {
        $titleExpression = 'CONCAT("Assessment #", id)';
    }

    $query = DB::table('assessments')
        ->where('tenant_id', $this->tenantId());

    if (Schema::hasColumn('assessments', 'deleted_at')) {
        $query->whereNull('deleted_at');
    }

    return $query
        ->select([
            'id',
            DB::raw($codeExpression . ' as code'),
            DB::raw($titleExpression . ' as title'),
        ])
        ->orderBy('id')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->code . ' - ' . $item->title,
            'value' => (int) $item->id,
            'id' => (int) $item->id,
            'code' => strtoupper((string) $item->code),
            'title' => $item->title,
        ])
        ->values()
        ->toArray();
}
    private function formatOfferedProgram(OfferedProgram $item): array
    {
        return [
            'id' => $item->id,
            'code' => $item->code ?? null,
            'title' => $item->title ?? $item->name ?? null,
            'admission_session_id' => $item->admission_session_id ?? null,
            'program_id' => $item->program_id ?? null,
            'student_batch_id' => $item->student_batch_id ?? null,
            'shift_code' => $item->shift_code ?? null,
            'application_start_date' => $item->application_start_date ?? null,
            'application_end_date' => $item->application_end_date ?? null,
            'application_fee' => $item->application_fee ?? null,
            'admission_fee' => $item->admission_fee ?? null,
            'status_code' => $item->status_code ?? null,
            'is_published' => (bool) ($item->is_published ?? false),
        ];
    }

    private function formatQuotaSeat(ProgramQuotaSeat $item): array
    {
        return [
            'id' => $item->id,
            'quota_code' => $item->quota_code ?? null,
            'quota_name' => $item->quota_name ?? null,
            'total_seats' => $item->total_seats ?? null,
            'available_seats' => $item->available_seats ?? null,
            'status_code' => $item->status_code ?? null,
        ];
    }
private function normalizeIdArray(array $values): array
{
    return array_values(array_unique(array_filter(array_map(
        fn ($value) => (int) $value,
        $values
    ))));
}

private function lookupCodesByIds(array $ids): array
{
    if (count($ids) === 0 || !Schema::hasTable('lookup_values')) {
        return [];
    }

    return DB::table('lookup_values')
        ->whereIn('id', $ids)
        ->pluck('code')
        ->map(fn ($code) => strtoupper(trim((string) $code)))
        ->filter()
        ->unique()
        ->values()
        ->toArray();
}

private function assessmentCodesByIds(array $ids): array
{
    if (count($ids) === 0 || !Schema::hasTable('assessments')) {
        return [];
    }

    return DB::table('assessments')
        ->where('tenant_id', $this->tenantId())
        ->whereIn('id', $ids)
        ->pluck('code')
        ->map(fn ($code) => strtoupper(trim((string) $code)))
        ->filter()
        ->unique()
        ->values()
        ->toArray();
}

private function ruleJson(?ProgramEligibilityRule $rule): array
{
    if (!$rule || !$rule->value_json) {
        return [];
    }

    if (is_array($rule->value_json)) {
        return $rule->value_json;
    }

    $decoded = json_decode((string) $rule->value_json, true);

    return is_array($decoded) ? $decoded : [];
}

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->toArray();
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $tenantId;
    }
}
