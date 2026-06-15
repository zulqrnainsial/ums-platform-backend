<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\AdmissionMeritFormula;
use App\Modules\Admission\Models\AdmissionMeritFormulaApplicability;
use App\Modules\Admission\Models\AdmissionMeritFormulaComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionMeritFormulaBuilderService
{
    public function sourceCatalog(): array
{
    return [
        'source_types' => [
            [
                'label' => 'Applicant Qualification',
                'value' => 'applicant_qualification',
                'description' => 'Use applicant qualification marks, percentage or CGPA.',
            ],
            [
                'label' => 'Applicant Manual Test Result',
                'value' => 'applicant_test_result',
                'description' => 'Use test result manually entered in applicant profile.',
            ],
            [
                'label' => 'Online Assessment Result',
                'value' => 'assessment_result',
                'description' => 'Use system-controlled online assessment result.',
            ],
            [
                'label' => 'Verified Document Bonus/Penalty',
                'value' => 'document_verified',
                'description' => 'Apply bonus or penalty only when selected document is verified.',
            ],
            [
                'label' => 'Fixed Bonus',
                'value' => 'fixed_bonus',
                'description' => 'Fixed bonus not tied to applicant field. Use carefully.',
            ],
            [
                'label' => 'Fixed Deduction',
                'value' => 'fixed_deduction',
                'description' => 'Fixed deduction not tied to applicant field. Use carefully.',
            ],
        ],

        'qualification_levels' => $this->lookupValuesAsIds('QUALIFICATION_LEVEL'),
        'subject_groups' => $this->lookupValuesAsIds('SUBJECT_GROUP'),
        'test_types' => $this->lookupValuesAsIds('TEST_TYPE'),
        'document_types' => $this->lookupValuesAsIds('DOCUMENT_TYPE'),
        'assessments' => $this->assessments(),

        'verification_statuses' => [
            ['label' => 'Verified', 'value' => 'verified'],
            ['label' => 'Submitted', 'value' => 'submitted'],
            ['label' => 'Pending', 'value' => 'pending'],
        ],

        'calculation_sources' => [
            ['label' => 'Percentage', 'value' => 'percentage'],
            ['label' => 'Obtained Marks', 'value' => 'obtained_marks'],
            ['label' => 'Normalized Marks', 'value' => 'normalized_marks'],
            ['label' => 'Fixed Marks', 'value' => 'fixed_marks'],
        ],
    ];
}
    public function index(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = AdmissionMeritFormula::query()
            ->where('tenant_id', $tenantId)
            ->withCount(['components', 'applicabilities']);

        if (!empty($filters['admission_session_id'])) {
            $query->where('admission_session_id', $filters['admission_session_id']);
        }

        if (!empty($filters['formula_type_code'])) {
            $query->where('formula_type_code', $filters['formula_type_code']);
        }

        if (!empty($filters['status_code'])) {
            $query->where('status_code', $filters['status_code']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function show(int $formulaId): array
    {
        $tenantId = $this->tenantId();

        $formula = AdmissionMeritFormula::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $formulaId)
            ->with([
                'components' => fn ($q) => $q->orderBy('display_order')->orderBy('id'),
                'applicabilities' => fn ($q) => $q->orderBy('priority')->orderBy('id'),
            ])
            ->firstOrFail();

        $componentWeight = $formula->components
            ->where('include_in_total', true)
            ->sum(fn ($item) => (float) $item->weight);

        return [
            'formula' => $formula,
            'summary' => [
                'component_count' => $formula->components->count(),
                'applicability_count' => $formula->applicabilities->count(),
                'configured_weight' => round($componentWeight, 2),
                'target_weight' => (float) $formula->total_weight,
                'weight_difference' => round(((float) $formula->total_weight) - $componentWeight, 2),
                'is_weight_valid' => round($componentWeight, 2) === round((float) $formula->total_weight, 2),
            ],
        ];
    }

    public function storeFormula(array $data): AdmissionMeritFormula
    {
        $tenantId = $this->tenantId();

        return AdmissionMeritFormula::create([
            'tenant_id' => $tenantId,
            'admission_session_id' => $data['admission_session_id'] ?? null,
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'formula_type_code' => $data['formula_type_code'] ?? 'standard',
            'total_weight' => $data['total_weight'] ?? 100,
            'passing_merit_score' => $data['passing_merit_score'] ?? null,
            'rounding_precision' => $data['rounding_precision'] ?? 2,
            'tie_breaker_json' => $data['tie_breaker_json'] ?? null,
            'rules_json' => $data['rules_json'] ?? null,
            'status_code' => $data['status_code'] ?? 'active',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    public function updateFormula(int $formulaId, array $data): AdmissionMeritFormula
    {
        $formula = AdmissionMeritFormula::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $formulaId)
            ->firstOrFail();

        $formula->update([
            'admission_session_id' => $data['admission_session_id'] ?? null,
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'formula_type_code' => $data['formula_type_code'] ?? 'standard',
            'total_weight' => $data['total_weight'] ?? 100,
            'passing_merit_score' => $data['passing_merit_score'] ?? null,
            'rounding_precision' => $data['rounding_precision'] ?? 2,
            'tie_breaker_json' => $data['tie_breaker_json'] ?? null,
            'rules_json' => $data['rules_json'] ?? null,
            'status_code' => $data['status_code'] ?? 'active',
            'updated_by' => auth()->id(),
        ]);

        return $formula->fresh();
    }

    public function deleteFormula(int $formulaId): void
    {
        DB::transaction(function () use ($formulaId) {
            $tenantId = $this->tenantId();

            $formula = AdmissionMeritFormula::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $formulaId)
                ->firstOrFail();

            $formula->delete();
        });
    }

    public function storeComponent(int $formulaId, array $data): AdmissionMeritFormulaComponent
    {
        $tenantId = $this->tenantId();

        $formula = AdmissionMeritFormula::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $formulaId)
            ->firstOrFail();
        $data = $this->normalizeComponentGovernance($data);
        return AdmissionMeritFormulaComponent::create([
            'tenant_id' => $tenantId,
            'admission_merit_formula_id' => $formula->id,
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,

            /*
             | These are string lookup codes, not numeric lookup IDs.
             */
            'component_type_code' => $data['component_type_code'],
            'source_type_code' => $data['source_type_code'],
            'source_key' => $data['source_key'] ?? null,
            'calculation_method_code' => $data['calculation_method_code'] ?? 'percentage_of_marks',

            'weight' => $data['weight'] ?? 0,
            'max_raw_marks' => $data['max_raw_marks'] ?? null,
            'normalize_to' => $data['normalize_to'] ?? 100,
            'minimum_required_score' => $data['minimum_required_score'] ?? null,

            'is_required' => $data['is_required'] ?? false,
            'include_in_total' => $data['include_in_total'] ?? true,
            'allow_bonus' => $data['allow_bonus'] ?? false,
            'allow_negative' => $data['allow_negative'] ?? false,

            'conditions_json' => $data['conditions_json'] ?? null,
            'source_mapping_json' => $data['source_mapping_json'] ?? null,

            'display_order' => $data['display_order'] ?? 1,
            'status_code' => $data['status_code'] ?? 'active',

            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    public function updateComponent(int $componentId, array $data): AdmissionMeritFormulaComponent
    {
        $component = AdmissionMeritFormulaComponent::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $componentId)
            ->firstOrFail();
$data = $this->normalizeComponentGovernance($data);
        $component->update([
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'component_type_code' => $data['component_type_code'],
            'source_type_code' => $data['source_type_code'],
            'source_key' => $data['source_key'] ?? null,
            'calculation_method_code' => $data['calculation_method_code'] ?? 'percentage_of_marks',
            'weight' => $data['weight'] ?? 0,
            'max_raw_marks' => $data['max_raw_marks'] ?? null,
            'normalize_to' => $data['normalize_to'] ?? 100,
            'minimum_required_score' => $data['minimum_required_score'] ?? null,
            'is_required' => $data['is_required'] ?? false,
            'include_in_total' => $data['include_in_total'] ?? true,
            'allow_bonus' => $data['allow_bonus'] ?? false,
            'allow_negative' => $data['allow_negative'] ?? false,
            'conditions_json' => $data['conditions_json'] ?? null,
            'source_mapping_json' => $data['source_mapping_json'] ?? null,
            'display_order' => $data['display_order'] ?? 1,
            'status_code' => $data['status_code'] ?? 'active',
            'updated_by' => auth()->id(),
        ]);

        return $component->fresh();
    }

    public function deleteComponent(int $componentId): void
    {
        AdmissionMeritFormulaComponent::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $componentId)
            ->firstOrFail()
            ->delete();
    }

    public function storeApplicability(int $formulaId, array $data): AdmissionMeritFormulaApplicability
    {
        $tenantId = $this->tenantId();

        $formula = AdmissionMeritFormula::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $formulaId)
            ->firstOrFail();

        return AdmissionMeritFormulaApplicability::create([
            'tenant_id' => $tenantId,
            'admission_merit_formula_id' => $formula->id,

            /*
             | String lookup code.
             */
            'applicability_scope_code' => $data['applicability_scope_code'],

            /*
             | FK IDs.
             */
            'admission_session_id' => $data['admission_session_id'] ?? null,
            'admission_preference_group_id' => $data['admission_preference_group_id'] ?? null,
            'offered_program_id' => $data['offered_program_id'] ?? null,
            'program_quota_seat_id' => $data['program_quota_seat_id'] ?? null,

            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_default' => $data['is_default'] ?? false,
            'priority' => $data['priority'] ?? 100,
            'status_code' => $data['status_code'] ?? 'active',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    public function updateApplicability(int $applicabilityId, array $data): AdmissionMeritFormulaApplicability
    {
        $applicability = AdmissionMeritFormulaApplicability::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $applicabilityId)
            ->firstOrFail();

        $applicability->update([
            'applicability_scope_code' => $data['applicability_scope_code'],
            'admission_session_id' => $data['admission_session_id'] ?? null,
            'admission_preference_group_id' => $data['admission_preference_group_id'] ?? null,
            'offered_program_id' => $data['offered_program_id'] ?? null,
            'program_quota_seat_id' => $data['program_quota_seat_id'] ?? null,
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_default' => $data['is_default'] ?? false,
            'priority' => $data['priority'] ?? 100,
            'status_code' => $data['status_code'] ?? 'active',
            'updated_by' => auth()->id(),
        ]);

        return $applicability->fresh();
    }

    public function deleteApplicability(int $applicabilityId): void
    {
        AdmissionMeritFormulaApplicability::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $applicabilityId)
            ->firstOrFail()
            ->delete();
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

    if (Schema::hasColumn('assessments', 'status_code')) {
        $query->whereIn('status_code', ['active', 'published']);
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
private function normalizeComponentGovernance(array $data): array
{
    $componentType = (string) ($data['component_type_code'] ?? '');

    $isBonus = in_array($componentType, ['bonus', 'fixed_bonus'], true)
        || (bool) ($data['allow_bonus'] ?? false);

    $isDeduction = in_array($componentType, ['penalty', 'deduction', 'fixed_deduction'], true)
        || (bool) ($data['allow_negative'] ?? false);

    if ($isBonus) {
        $data['allow_bonus'] = true;
        $data['allow_negative'] = false;
        $data['include_in_total'] = false;

        if (empty($data['calculation_method_code'])) {
            $data['calculation_method_code'] = 'fixed_marks';
        }
    }

    if ($isDeduction) {
        $data['allow_negative'] = true;
        $data['allow_bonus'] = false;
        $data['include_in_total'] = false;

        if (empty($data['calculation_method_code'])) {
            $data['calculation_method_code'] = 'fixed_marks';
        }
    }

    if (($data['source_type_code'] ?? null) === 'document_verified') {
        $mapping = $data['source_mapping_json'] ?? [];

        if (empty($mapping['document_type_id']) && empty($mapping['document_type_code'])) {
            abort(422, 'Document verified bonus/penalty requires a document type source.');
        }

        if (!$isBonus && !$isDeduction) {
            abort(422, 'Document verified source must be configured as bonus or penalty.');
        }
    }

    if (($data['source_type_code'] ?? null) === 'applicant_qualification') {
        $mapping = $data['source_mapping_json'] ?? [];

        if (empty($mapping['qualification_level_id']) && empty($mapping['qualification_level_code']) && empty($data['source_key'])) {
            abort(422, 'Qualification component requires a qualification level source.');
        }
    }

    if (($data['source_type_code'] ?? null) === 'assessment_result') {
        $mapping = $data['source_mapping_json'] ?? [];

        if (empty($mapping['assessment_id']) && empty($data['source_key'])) {
            abort(422, 'Assessment result component requires an assessment source.');
        }
    }

    return $data;
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