<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\AdmissionApplicantMeritScore;
use App\Modules\Admission\Models\AdmissionApplicantMeritScoreComponent;
use App\Modules\Admission\Models\AdmissionMeritFormula;
use App\Modules\Admission\Models\AdmissionMeritFormulaComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionMeritCalculationService
{
    public function calculateForApplicant(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $this->tenantId();

            $applicantId = (int) $data['applicant_id'];
            $formulaId = (int) $data['admission_merit_formula_id'];

            $formula = AdmissionMeritFormula::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $formulaId)
                ->with(['components' => fn ($q) => $q->where('status_code', 'active')->orderBy('display_order')])
                ->firstOrFail();

            $componentResults = [];
            $failedRequired = [];

            $totalWeight = 0.0;
            $totalWeightedScore = 0.0;
            $bonusScore = 0.0;
            $deductionScore = 0.0;

            foreach ($formula->components as $component) {
                $componentResult = $this->calculateComponent($tenantId, $applicantId, $component);

                $componentResults[] = $componentResult;

                $isBonusComponent = (bool) $component->allow_bonus
                    || in_array($component->component_type_code, ['bonus', 'fixed_bonus'], true);

                $isDeductionComponent = (bool) $component->allow_negative
                    || in_array($component->component_type_code, ['penalty', 'deduction', 'fixed_deduction'], true);

                if ($componentResult['include_in_total'] && !$isBonusComponent && !$isDeductionComponent) {
                    $totalWeight += (float) $componentResult['component_weight'];
                    $totalWeightedScore += (float) $componentResult['weighted_score'];
                }

                if ($isBonusComponent) {
                    $bonusScore += (float) $componentResult['weighted_score'];
                }

                if ($isDeductionComponent) {
                    $deductionScore += abs((float) $componentResult['weighted_score']);
                }

                if ($component->is_required && !$componentResult['is_component_passed']) {
                    $failedRequired[] = [
                        'component_code' => $component->code,
                        'component_title' => $component->title,
                        'reason' => $componentResult['failure_reason'] ?? 'Required component failed or missing.',
                    ];
                }
            }

            $finalMeritScore = $totalWeightedScore + $bonusScore - $deductionScore;
            $precision = (int) ($formula->rounding_precision ?? 2);

            $finalMeritScore = round(max(0, $finalMeritScore), $precision);
            $totalWeightedScore = round($totalWeightedScore, $precision);
            $bonusScore = round($bonusScore, $precision);
            $deductionScore = round($deductionScore, $precision);

            $isEligible = count($failedRequired) === 0;

            if ($formula->passing_merit_score !== null && $finalMeritScore < (float) $formula->passing_merit_score) {
                $isEligible = false;
                $failedRequired[] = [
                    'component_code' => 'PASSING_MERIT_SCORE',
                    'component_title' => 'Passing Merit Score',
                    'reason' => 'Final merit score is below required passing merit score.',
                ];
            }

            $score = AdmissionApplicantMeritScore::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'applicant_id' => $applicantId,
                    'admission_application_id' => $data['admission_application_id'] ?? null,
                    'offered_program_id' => $data['offered_program_id'] ?? null,
                    'program_quota_seat_id' => $data['program_quota_seat_id'] ?? null,
                    'admission_merit_formula_id' => $formula->id,
                ],
                [
                    'admission_session_id' => $data['admission_session_id'] ?? $formula->admission_session_id,
                    'admission_preference_group_id' => $data['admission_preference_group_id'] ?? null,
                    'total_component_weight' => $totalWeight,
                    'total_weighted_score' => $totalWeightedScore,
                    'bonus_score' => $bonusScore,
                    'deduction_score' => $deductionScore,
                    'final_merit_score' => $finalMeritScore,
                    'is_eligible_for_merit' => $isEligible,
                    'failed_required_components_json' => $failedRequired,
                    'calculation_snapshot_json' => [
                        'formula' => [
                            'id' => $formula->id,
                            'code' => $formula->code,
                            'title' => $formula->title,
                            'total_weight' => $formula->total_weight,
                        ],
                        'components' => $componentResults,
                    ],
                    'status_code' => 'calculated',
                    'calculated_at' => now(),
                    'calculated_by' => auth()->id(),
                ]
            );

            AdmissionApplicantMeritScoreComponent::query()
                ->where('tenant_id', $tenantId)
                ->where('admission_applicant_merit_score_id', $score->id)
                ->delete();

            foreach ($componentResults as $result) {
                AdmissionApplicantMeritScoreComponent::create([
                    'tenant_id' => $tenantId,
                    'admission_applicant_merit_score_id' => $score->id,
                    'admission_merit_formula_component_id' => $result['admission_merit_formula_component_id'],
                    'component_code' => $result['component_code'],
                    'component_title' => $result['component_title'],
                    'component_type_code' => $result['component_type_code'],
                    'source_type_code' => $result['source_type_code'],
                    'source_key' => $result['source_key'],
                    'calculation_method_code' => $result['calculation_method_code'],
                    'raw_obtained_marks' => $result['raw_obtained_marks'],
                    'raw_total_marks' => $result['raw_total_marks'],
                    'raw_percentage' => $result['raw_percentage'],
                    'normalized_score' => $result['normalized_score'],
                    'component_weight' => $result['component_weight'],
                    'weighted_score' => $result['weighted_score'],
                    'is_required' => $result['is_required'],
                    'is_component_passed' => $result['is_component_passed'],
                    'include_in_total' => $result['include_in_total'],
                    'source_record_json' => $result['source_record_json'],
                    'calculation_detail_json' => $result['calculation_detail_json'],
                    'status_code' => 'calculated',
                ]);
            }

            return [
                'score' => $score->fresh(['components']),
                'components' => $componentResults,
            ];
        });
    }

    public function bulkCalculate(array $data): array
    {
        $tenantId = $this->tenantId();

        $applicantIds = $data['applicant_ids'] ?? [];

        if (empty($applicantIds) && Schema::hasTable('applicants')) {
            $query = DB::table('applicants')
                ->where('tenant_id', $tenantId);

            if (!empty($data['applicant_status_code']) && Schema::hasColumn('applicants', 'applicant_status_code')) {
                $query->where('applicant_status_code', $data['applicant_status_code']);
            }

            $applicantIds = $query->pluck('id')->toArray();
        }

        $calculated = 0;
        $failed = 0;
        $errors = [];

        foreach ($applicantIds as $applicantId) {
            try {
                $this->calculateForApplicant(array_merge($data, [
                    'applicant_id' => $applicantId,
                ]));

                $calculated++;
            } catch (\Throwable $e) {
                $failed++;

                $errors[] = [
                    'applicant_id' => $applicantId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'calculated' => $calculated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    private function calculateComponent(
        int $tenantId,
        int $applicantId,
        AdmissionMeritFormulaComponent $component
    ): array {
        $source = $this->resolveSource($tenantId, $applicantId, $component);

        $rawObtained = $source['obtained_marks'];
        $rawTotal = $source['total_marks'];
        $rawPercentage = $source['percentage'];

        $normalizedScore = $this->normalizeScore($component, $rawObtained, $rawTotal, $rawPercentage);
        $isBonusComponent = (bool) $component->allow_bonus
            || in_array($component->component_type_code, ['bonus', 'fixed_bonus'], true);

        $isDeductionComponent = (bool) $component->allow_negative
            || in_array($component->component_type_code, ['penalty', 'deduction', 'fixed_deduction'], true);

        $weightedScore = ($isBonusComponent || $isDeductionComponent)
            ? $normalizedScore
            : (
                $component->include_in_total
                    ? ($normalizedScore * ((float) $component->weight / 100))
                    : 0
            );

        $minimumRequired = $component->minimum_required_score;
        $isPassed = true;
        $failureReason = null;

        if (!$source['found']) {
            $isPassed = !$component->is_required;
            $failureReason = 'Source data not found.';
        }

        if ($minimumRequired !== null && $normalizedScore < (float) $minimumRequired) {
            $isPassed = false;
            $failureReason = 'Component score is below minimum required score.';
        }

        return [
            'admission_merit_formula_component_id' => $component->id,
            'component_code' => $component->code,
            'component_title' => $component->title,
            'component_type_code' => $component->component_type_code,
            'source_type_code' => $component->source_type_code,
            'source_key' => $component->source_key,
            'calculation_method_code' => $component->calculation_method_code,

            'raw_obtained_marks' => $rawObtained,
            'raw_total_marks' => $rawTotal,
            'raw_percentage' => $rawPercentage,

            'normalized_score' => round($normalizedScore, 4),
            'component_weight' => (float) $component->weight,
            'weighted_score' => round($weightedScore, 4),

            'is_required' => (bool) $component->is_required,
            'is_component_passed' => $isPassed,
            'include_in_total' => (bool) $component->include_in_total,

            'source_record_json' => $source['record'],
            'calculation_detail_json' => [
                'method' => $component->calculation_method_code,
                'normalize_to' => $component->normalize_to,
                'weight' => $component->weight,
                'failure_reason' => $failureReason,
            ],

            'failure_reason' => $failureReason,
        ];
    }

    private function resolveSource(
        int $tenantId,
        int $applicantId,
        AdmissionMeritFormulaComponent $component
    ): array {
        return match ($component->source_type_code) {
            'applicant_qualification' => $this->resolveQualificationSource($tenantId, $applicantId, $component),
            'applicant_test_result' => $this->resolveApplicantTestResultSource($tenantId, $applicantId, $component),
            'assessment_result' => $this->resolveAssessmentResultSource($tenantId, $applicantId, $component),
            'document_verified' => $this->resolveDocumentVerifiedSource($tenantId, $applicantId, $component),
            'manual_entry' => $this->emptySource(),
            'fixed_bonus' => $this->fixedSource((float) $component->weight, 100),
            'fixed_deduction' => $this->fixedSource((float) $component->weight, 100),
            default => $this->emptySource(),
        };
    }
private function componentMapping(AdmissionMeritFormulaComponent $component): array
{
    $mapping = $component->source_mapping_json ?? [];

    if (is_array($mapping)) {
        return $mapping;
    }

    $decoded = json_decode((string) $mapping, true);

    return is_array($decoded) ? $decoded : [];
}
    private function resolveQualificationSource(
        int $tenantId,
        int $applicantId,
        AdmissionMeritFormulaComponent $component
    ): array {
        if (!Schema::hasTable('applicant_qualifications')) {
            return $this->emptySource();
        }

        $query = DB::table('applicant_qualifications')
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicantId);

        $mapping = $this->componentMapping($component);

        if (!empty($mapping['qualification_level_id']) && Schema::hasColumn('applicant_qualifications', 'qualification_level_id')) {
            $query->where('qualification_level_id', (int) $mapping['qualification_level_id']);
        }

        if (!empty($mapping['subject_group_id']) && Schema::hasColumn('applicant_qualifications', 'subject_group_id')) {
            $query->where('subject_group_id', (int) $mapping['subject_group_id']);
        }

        $sourceKey = $component->source_key;

        if ($sourceKey) {
            if (Schema::hasColumn('applicant_qualifications', 'qualification_level_code')) {
                $query->where('qualification_level_code', $sourceKey);
            } elseif (Schema::hasColumn('applicant_qualifications', 'level_code')) {
                $query->where('level_code', $sourceKey);
            } elseif (Schema::hasColumn('applicant_qualifications', 'qualification_type_code')) {
                $query->where('qualification_type_code', $sourceKey);
            }
        }

        $record = $query->orderByDesc('id')->first();

        if (!$record) {
            return $this->emptySource();
        }

        $obtained = $this->firstAvailableNumeric($record, ['obtained_marks', 'marks_obtained', 'total_obtained']);
        $total = $this->firstAvailableNumeric($record, ['total_marks', 'max_marks', 'marks_total']);
        $percentage = $this->firstAvailableNumeric($record, ['percentage', 'marks_percentage']);

        if ($percentage === null && $obtained !== null && $total && $total > 0) {
            $percentage = ($obtained / $total) * 100;
        }

        return $this->sourcePayload($record, $obtained, $total, $percentage);
    }

    private function resolveApplicantTestResultSource(
        int $tenantId,
        int $applicantId,
        AdmissionMeritFormulaComponent $component
    ): array {
        if (!Schema::hasTable('applicant_test_results')) {
            return $this->emptySource();
        }

        $query = DB::table('applicant_test_results')
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicantId);

        if ($component->source_key && Schema::hasColumn('applicant_test_results', 'test_type_code')) {
            $query->where(function ($q) use ($component) {
                $q->where('test_type_code', $component->source_key)
                    ->orWhere('test_code', $component->source_key);
            });
        }

        $record = $query->orderByDesc('id')->first();

        if (!$record) {
            return $this->emptySource();
        }

        $obtained = $this->firstAvailableNumeric($record, ['obtained_marks', 'final_marks', 'score']);
        $total = $this->firstAvailableNumeric($record, ['total_marks', 'max_marks']);
        $percentage = $this->firstAvailableNumeric($record, ['percentage']);

        if ($percentage === null && $obtained !== null && $total && $total > 0) {
            $percentage = ($obtained / $total) * 100;
        }

        return $this->sourcePayload($record, $obtained, $total, $percentage);
    }

    private function resolveAssessmentResultSource(
        int $tenantId,
        int $applicantId,
        AdmissionMeritFormulaComponent $component
    ): array {
        if (!Schema::hasTable('assessment_results')) {
            return $this->emptySource();
        }

        $query = DB::table('assessment_results as ar')
            ->leftJoin('assessment_participants as ap', 'ap.id', '=', 'ar.assessment_participant_id')
            ->where('ar.tenant_id', $tenantId)
            ->where('ap.applicant_id', $applicantId);
$mapping = $this->componentMapping($component);

if (!empty($mapping['assessment_id'])) {
    if (Schema::hasColumn('assessment_participants', 'assessment_id')) {
        $query->where('ap.assessment_id', (int) $mapping['assessment_id']);
    } elseif (Schema::hasColumn('assessment_results', 'assessment_id')) {
        $query->where('ar.assessment_id', (int) $mapping['assessment_id']);
    }
}

if ($component->source_key) {
    if (Schema::hasColumn('assessment_participants', 'assessment_code')) {
        $query->where('ap.assessment_code', $component->source_key);
    }
}
        $record = $query
            ->select([
                'ar.*',
                'ap.applicant_id',
                'ap.roll_no',
            ])
            ->orderByDesc('ar.id')
            ->first();

        if (!$record) {
            return $this->emptySource();
        }

        $obtained = (float) ($record->final_marks ?? 0);
        $total = (float) ($record->total_marks ?? 0);
        $percentage = $record->percentage !== null ? (float) $record->percentage : null;

        if ($percentage === null && $total > 0) {
            $percentage = ($obtained / $total) * 100;
        }

        return $this->sourcePayload($record, $obtained, $total, $percentage);
    }
private function resolveDocumentVerifiedSource(
    int $tenantId,
    int $applicantId,
    AdmissionMeritFormulaComponent $component
): array {
    if (!Schema::hasTable('applicant_documents')) {
        return $this->emptySource();
    }

    $mapping = $this->componentMapping($component);
    $conditions = $component->conditions_json ?? [];

    if (!is_array($conditions)) {
        $conditions = json_decode((string) $conditions, true) ?: [];
    }

    $requiredStatus = $conditions['verification_status_code']
        ?? $mapping['verification_status_code']
        ?? 'verified';

    $query = DB::table('applicant_documents')
        ->where('tenant_id', $tenantId)
        ->where('applicant_id', $applicantId);

    if (!empty($mapping['document_type_id']) && Schema::hasColumn('applicant_documents', 'document_type_id')) {
        $query->where('document_type_id', (int) $mapping['document_type_id']);
    }

    if (!empty($mapping['document_type_code']) && Schema::hasColumn('applicant_documents', 'document_type_code')) {
        $query->where('document_type_code', $mapping['document_type_code']);
    }

    if (Schema::hasColumn('applicant_documents', 'verification_status_code')) {
        $query->where('verification_status_code', $requiredStatus);
    }

    $record = $query->orderByDesc('id')->first();

    if (!$record) {
        return $this->emptySource();
    }

    $marks = (float) ($mapping['marks'] ?? $component->weight ?? 0);

    return $this->sourcePayload($record, $marks, 100, $marks);
}
    private function normalizeScore(
    AdmissionMeritFormulaComponent $component,
    ?float $obtained,
    ?float $total,
    ?float $percentage
): float {
    return match ($component->calculation_method_code) {
        /*
        |--------------------------------------------------------------------------
        | Percentage based components
        |--------------------------------------------------------------------------
        | For merit formulas like:
        | Matric 30%, Intermediate 50%, Test 20%
        |
        | normalized_score must be the raw percentage.
        | weighted_score will apply the component weight later.
        */
        'percentage_of_marks' => $percentage !== null ? (float) $percentage : 0,

        /*
        |--------------------------------------------------------------------------
        | Raw obtained marks
        |--------------------------------------------------------------------------
        | Use this only when the formula component is directly based on obtained marks.
        */
        'obtained_marks' => $obtained ?? 0,

        /*
        |--------------------------------------------------------------------------
        | Normalized marks
        |--------------------------------------------------------------------------
        | Use normalize_to only for real normalized-mark based formulas.
        | Example: obtained/total converted to 10, 20, 50, or 100.
        */
        'normalized_marks' => ($obtained !== null && $total && $total > 0)
            ? ($obtained / $total) * (float) ($component->normalize_to ?: 100)
            : 0,

        /*
        |--------------------------------------------------------------------------
        | Fixed marks
        |--------------------------------------------------------------------------
        */
        'fixed_marks' => $obtained ?? 0,

        /*
        |--------------------------------------------------------------------------
        | Test percentage methods
        |--------------------------------------------------------------------------
        | These should also return raw percentage.
        | Weight is applied once in calculateComponent().
        */
        'best_of_tests' => $percentage !== null ? (float) $percentage : 0,
        'latest_test' => $percentage !== null ? (float) $percentage : 0,

        default => 0,
    };
}

    private function firstAvailableNumeric(object $record, array $columns): ?float
    {
        foreach ($columns as $column) {
            if (property_exists($record, $column) && $record->{$column} !== null && $record->{$column} !== '') {
                return (float) $record->{$column};
            }
        }

        return null;
    }

    private function sourcePayload(?object $record, ?float $obtained, ?float $total, ?float $percentage): array
    {
        return [
            'found' => $record !== null,
            'obtained_marks' => $obtained,
            'total_marks' => $total,
            'percentage' => $percentage !== null ? round($percentage, 4) : null,
            'record' => $record ? json_decode(json_encode($record), true) : null,
        ];
    }

    private function emptySource(): array
    {
        return [
            'found' => false,
            'obtained_marks' => null,
            'total_marks' => null,
            'percentage' => null,
            'record' => null,
        ];
    }

    private function fixedSource(float $marks, float $total): array
    {
        return [
            'found' => true,
            'obtained_marks' => $marks,
            'total_marks' => $total,
            'percentage' => $total > 0 ? ($marks / $total) * 100 : 0,
            'record' => [
                'fixed_marks' => $marks,
                'total' => $total,
            ],
        ];
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