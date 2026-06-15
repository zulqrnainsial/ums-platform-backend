<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\ApplicantDocument;
use App\Modules\Admission\Models\ApplicantExperience;
//use App\Modules\Admission\Models\ApplicantProgramApplication;
use App\Modules\Admission\Models\ApplicantPublication;
use App\Modules\Admission\Models\ApplicantQualification;
use App\Modules\Admission\Models\ApplicantResearchProfile;
use App\Modules\Admission\Models\ApplicantTestResult;
use App\Modules\Admission\Models\OfferedProgram;
use App\Modules\Admission\Models\ProgramEligibilityRule;
use App\Modules\Admission\Models\ProgramQuotaSeat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EligibilityRuleEvaluatorService
{
    public function evaluate(
        Applicant $applicant,
        OfferedProgram $offeredProgram,
        ?ProgramQuotaSeat $quotaSeat = null
    ): EligibilityEvaluationResult {
        $result = new EligibilityEvaluationResult();

        $rules = ProgramEligibilityRule::query()
            ->with(['eligibilityRuleType'])
            ->where('tenant_id', $applicant->tenant_id)
            ->where('offered_program_id', $offeredProgram->id)
            ->where('is_active', true)
            ->where(function ($query) use ($quotaSeat) {
                $query->whereNull('program_quota_seat_id');

                if ($quotaSeat) {
                    $query->orWhere('program_quota_seat_id', $quotaSeat->id);
                }
            })
            ->orderBy('display_order')
            ->get();

        foreach ($rules as $rule) {
            $passed = $this->evaluateRule($applicant, $rule);

            $payload = $this->rulePayload($rule, $passed);

            if ($passed) {
                $result->addPassed($payload);
                continue;
            }

            if ($rule->is_mandatory) {
                $result->addFailed($payload);
            } else {
                $result->addWarning($payload);
            }
        }

        return $result;
    }

    private function evaluateRule(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        $evaluatorKey = $rule->eligibilityRuleType?->evaluator_key;

        return match ($evaluatorKey) {
            /*
            | Structured policy-builder rules.
            | These read value_json created by EligibilityPolicyBuilderService.
            */
            'qualification_exists' => $this->qualificationRequired($applicant, $rule),
            'test_exists' => $this->admissionTestRequired($applicant, $rule),
            'test_numeric_compare' => $this->admissionTestMinimum($applicant, $rule),
            'document_exists' => $this->requiredDocumentsUploaded($applicant, $rule),

            /*
            | Backward-compatible / advanced rules.
            */
            'qualification_numeric_compare' => $this->qualificationNumericCompare($applicant, $rule),
            'qualification_lookup_match' => $this->qualificationLookupMatch($applicant, $rule),

            'age_compare' => $this->ageCompare($applicant, $rule),
            'applicant_field_match' => $this->applicantFieldMatch($applicant, $rule),
            'applicant_lookup_match' => $this->applicantLookupMatch($applicant, $rule),

            'experience_compare' => $this->experienceCompare($applicant, $rule),
            'research_profile_exists' => $this->researchProfileExists($applicant, $rule),
            'publication_exists' => $this->publicationExists($applicant, $rule),

            default => false,
        };
    }

    private function qualificationExists(Applicant $applicant, ProgramEligibilityRule $rule): bool
{
    $groups = $this->jsonArray($rule, 'required_qualifications');

    if (count($groups) > 0) {
        foreach ($groups as $group) {
            $required = $group['required'] ?? true;

            if (!$required) {
                continue;
            }

            if (!$this->qualificationGroupMatchedByIds($applicant, $group)) {
                return false;
            }
        }

        return true;
    }

    if (!$rule->target_qualification_level_id) {
        return false;
    }

    return ApplicantQualification::query()
        ->where('tenant_id', $applicant->tenant_id)
        ->where('applicant_id', $applicant->id)
        ->where('qualification_level_id', $rule->target_qualification_level_id)
        ->whereIn('result_status_code', ['passed', 'completed', 'verified'])
        ->exists();
}

    private function qualificationNumericCompare(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        if (!$rule->target_qualification_level_id) {
            return false;
        }

        $field = $rule->eligibilityRuleType?->source_field;

        if (!in_array($field, ['percentage', 'obtained_marks', 'cgpa'], true)) {
            return false;
        }

        $qualification = ApplicantQualification::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->where('qualification_level_id', $rule->target_qualification_level_id)
            ->where('result_status_code', 'passed')
            ->orderByDesc('percentage')
            ->first();

        if (!$qualification) {
            return false;
        }

        $actualValue = $qualification->{$field};

        if ($actualValue === null || $rule->value_number === null) {
            return false;
        }

        return $this->compareNumber(
            (float) $actualValue,
            $rule->operator,
            (float) $rule->value_number
        );
    }

    private function qualificationLookupMatch(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        if (!$rule->target_qualification_level_id) {
            return false;
        }

        $field = $rule->eligibilityRuleType?->source_field;

        if (!in_array($field, ['subject_group_id', 'qualification_level_id'], true)) {
            return false;
        }

        $query = ApplicantQualification::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->where('qualification_level_id', $rule->target_qualification_level_id)
            ->where('result_status_code', 'passed');

        if ($rule->value_lookup_id) {
            $query->where($field, $rule->value_lookup_id);
        }

        if ($rule->value_json && is_array($rule->value_json)) {
            $allowed = $rule->value_json['lookup_ids'] ?? [];

            if (is_array($allowed) && count($allowed) > 0) {
                $query->whereIn($field, $allowed);
            }
        }

        return $query->exists();
    }

    private function ageCompare(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        if (!$applicant->date_of_birth || $rule->value_number === null) {
            return false;
        }

        $age = Carbon::parse($applicant->date_of_birth)->age;

        return $this->compareNumber(
            $age,
            $rule->operator,
            (float) $rule->value_number
        );
    }

    private function applicantFieldMatch(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        $field = $rule->eligibilityRuleType?->source_field;

        if (!$field || !in_array($field, ['gender'], true)) {
            return false;
        }

        $actualValue = $applicant->{$field};

        if ($actualValue === null) {
            return false;
        }

        if ($rule->operator === 'in') {
            $allowed = $this->normalizeListValue($rule);

            return in_array($actualValue, $allowed, true);
        }

        return $this->compareString($actualValue, $rule->operator, $rule->value_text);
    }

    private function applicantLookupMatch(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        $field = $rule->eligibilityRuleType?->source_field;

        $allowedFields = [
            'nationality_id',
            'domicile_district_id',
            'domicile_province_id',
            'country_id',
            'province_id',
            'city_id',
            'religion_id',
        ];

        if (!$field || !in_array($field, $allowedFields, true)) {
            return false;
        }

        $actualValue = $applicant->{$field};

        if (!$actualValue) {
            return false;
        }

        if ($rule->operator === 'in') {
            $allowed = $this->normalizeLookupIdList($rule);

            return in_array((int) $actualValue, $allowed, true);
        }

        if ($rule->value_lookup_id) {
            return $this->compareNumber(
                (int) $actualValue,
                $rule->operator,
                (int) $rule->value_lookup_id
            );
        }

        return false;
    }

    private function testExists(Applicant $applicant, ProgramEligibilityRule $rule): bool
{
    $acceptedAssessmentIds = $this->acceptedAssessmentIds($rule);

    $query = ApplicantTestResult::query()
        ->where('tenant_id', $applicant->tenant_id)
        ->where('applicant_id', $applicant->id)
        ->whereIn('result_status_code', ['submitted', 'verified', 'passed', 'pass']);

    if (count($acceptedAssessmentIds) > 0) {
        $query->whereIn('assessment_id', $acceptedAssessmentIds);
    } elseif ($rule->target_test_code) {
        $query->where('test_code', $rule->target_test_code);
    } elseif ($rule->value_text) {
        $query->where('test_code', $rule->value_text);
    }

    return $query->exists();
}

    private function testNumericCompare(Applicant $applicant, ProgramEligibilityRule $rule): bool
{
    $field = $rule->eligibilityRuleType?->source_field;

    if (!in_array($field, ['obtained_marks', 'percentage', 'percentile'], true)) {
        $field = 'percentage';
    }

    $acceptedAssessmentIds = $this->acceptedAssessmentIds($rule);

    $query = ApplicantTestResult::query()
        ->where('tenant_id', $applicant->tenant_id)
        ->where('applicant_id', $applicant->id)
        ->whereIn('result_status_code', ['submitted', 'verified', 'passed', 'pass']);

    if (count($acceptedAssessmentIds) > 0) {
        $query->whereIn('assessment_id', $acceptedAssessmentIds);
    } elseif ($rule->target_test_code) {
        $query->where('test_code', $rule->target_test_code);
    } elseif ($rule->value_text) {
        $query->where('test_code', $rule->value_text);
    }

    $testResult = $query
        ->orderByDesc($field)
        ->first();

    if (!$testResult || $testResult->{$field} === null || $rule->value_number === null) {
        return false;
    }

    return $this->compareNumber(
        (float) $testResult->{$field},
        $rule->operator,
        (float) $rule->value_number
    );
}

    private function documentExists(Applicant $applicant, ProgramEligibilityRule $rule): bool
{
    $requiredDocumentTypeIds = $this->requiredDocumentTypeIds($rule);

    if (count($requiredDocumentTypeIds) === 0 && $rule->target_document_type_id) {
        $requiredDocumentTypeIds = [(int) $rule->target_document_type_id];
    }

    if (count($requiredDocumentTypeIds) === 0) {
        return ApplicantDocument::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->whereNotNull('file_path')
            ->whereIn('verification_status_code', ['pending', 'submitted', 'verified'])
            ->exists();
    }

    foreach ($requiredDocumentTypeIds as $documentTypeId) {
        $exists = ApplicantDocument::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->where('document_type_id', $documentTypeId)
            ->whereNotNull('file_path')
            ->whereIn('verification_status_code', ['pending', 'submitted', 'verified'])
            ->exists();

        if (!$exists) {
            return false;
        }
    }

    return true;
}

    private function experienceCompare(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        $totalMonths = ApplicantExperience::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->whereIn('status_code', ['active', 'verified'])
            ->sum('total_months');

        if ($rule->value_number === null) {
            return $totalMonths > 0;
        }

        return $this->compareNumber(
            (float) $totalMonths,
            $rule->operator,
            (float) $rule->value_number
        );
    }

    private function researchProfileExists(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        return ApplicantResearchProfile::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->whereIn('status_code', ['completed', 'submitted', 'verified'])
            ->exists();
    }

    private function publicationExists(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        return ApplicantPublication::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->whereIn('status_code', ['claimed', 'submitted', 'verified'])
            ->exists();
    }

    private function compareNumber(float|int $actual, string $operator, float|int $expected): bool
    {
        return match ($operator) {
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            default => false,
        };
    }

    private function compareString(?string $actual, string $operator, ?string $expected): bool
    {
        if ($actual === null || $expected === null) {
            return false;
        }

        return match ($operator) {
            '=' => $actual === $expected,
            '!=' => $actual !== $expected,
            default => false,
        };
    }

    private function normalizeListValue(ProgramEligibilityRule $rule): array
    {
        if ($rule->value_json && is_array($rule->value_json)) {
            $values = $rule->value_json['values'] ?? [];

            if (is_array($values)) {
                return $values;
            }
        }

        if ($rule->value_text) {
            return array_map('trim', explode(',', $rule->value_text));
        }

        return [];
    }

    private function normalizeLookupIdList(ProgramEligibilityRule $rule): array
    {
        if ($rule->value_json && is_array($rule->value_json)) {
            $values = $rule->value_json['lookup_ids'] ?? [];

            if (is_array($values)) {
                return array_map('intval', $values);
            }
        }

        if ($rule->value_lookup_id) {
            return [(int) $rule->value_lookup_id];
        }

        return [];
    }
private function qualificationGroupMatchedByIds(Applicant $applicant, array $group): bool
{
    $qualificationLevelIds = $this->normalizeIdArray(
        $group['qualification_level_ids'] ?? []
    );

    $subjectGroupIds = $this->normalizeIdArray(
        $group['subject_group_ids'] ?? []
    );

    $minimumPercentage = $group['minimum_percentage'] ?? null;
    $minimumMarks = $group['minimum_marks'] ?? null;
    $minimumCgpa = $group['minimum_cgpa'] ?? null;

    $query = ApplicantQualification::query()
        ->where('tenant_id', $applicant->tenant_id)
        ->where('applicant_id', $applicant->id)
        ->whereIn('result_status_code', ['passed', 'completed', 'verified']);

    if (count($qualificationLevelIds) > 0) {
        $query->whereIn('qualification_level_id', $qualificationLevelIds);
    }

    if (count($subjectGroupIds) > 0) {
        $query->whereIn('subject_group_id', $subjectGroupIds);
    }

    $qualifications = $query->get();

    foreach ($qualifications as $qualification) {
        if ($minimumPercentage !== null) {
            if ($qualification->percentage === null || (float) $qualification->percentage < (float) $minimumPercentage) {
                continue;
            }
        }

        if ($minimumMarks !== null) {
            if ($qualification->obtained_marks === null || (float) $qualification->obtained_marks < (float) $minimumMarks) {
                continue;
            }
        }

        if ($minimumCgpa !== null) {
            if ($qualification->cgpa === null || (float) $qualification->cgpa < (float) $minimumCgpa) {
                continue;
            }
        }

        return true;
    }

    return false;
}

private function acceptedAssessmentIds(ProgramEligibilityRule $rule): array
{
    $ids = $this->normalizeIdArray(
        $this->jsonArray($rule, 'accepted_assessment_ids')
    );

    if (count($ids) > 0) {
        return $ids;
    }

    if ($rule->value_text) {
        return $this->normalizeIdArray(
            array_map('trim', explode(',', $rule->value_text))
        );
    }

    return [];
}

private function requiredDocumentTypeIds(ProgramEligibilityRule $rule): array
{
    return $this->normalizeIdArray(
        $this->jsonArray($rule, 'required_document_type_ids')
    );
}

private function normalizeIdArray(array $values): array
{
    return array_values(array_unique(array_filter(array_map(
        fn ($value) => is_numeric($value) ? (int) $value : null,
        $values
    ))));
}
    private function rulePayload(ProgramEligibilityRule $rule, bool $passed): array
    {
        return [
            'rule_id' => $rule->id,
            'rule_code' => $rule->rule_code,
            'rule_title' => $rule->rule_title,
            'rule_group' => $rule->rule_group,
            'rule_type_code' => $rule->eligibilityRuleType?->code,
            'evaluator_key' => $rule->eligibilityRuleType?->evaluator_key,
            'operator' => $rule->operator,
            'is_mandatory' => $rule->is_mandatory,
            'passed' => $passed,
            'message' => $passed
                ? 'Rule passed.'
                : ($rule->failure_message ?: 'Eligibility rule failed.'),
        ];
    }
    private function qualificationRequired(Applicant $applicant, ProgramEligibilityRule $rule): bool
    {
        $groups = $this->jsonArray($rule, 'required_qualifications');

        /*
        | Backward compatibility:
        | If old rule has only single qualification_level_id / subject_group_id style values,
        | we still evaluate it.
        */
        if (count($groups) === 0) {
            $groups = [[
                'qualification_level_codes' => $this->jsonArray($rule, 'qualification_level_codes'),
                'subject_group_codes' => $this->jsonArray($rule, 'subject_group_codes'),
                'minimum_percentage' => $rule->value_number,
                'required' => true,
            ]];
        }

        /*
        | If no structured requirement exists, simply require at least one passed qualification.
        */
        if (count($groups) === 0) {
            return ApplicantQualification::query()
                ->where('tenant_id', $applicant->tenant_id)
                ->where('applicant_id', $applicant->id)
                ->whereIn('result_status_code', ['passed', 'completed', 'verified'])
                ->exists();
        }

        foreach ($groups as $group) {
            $required = $group['required'] ?? true;

            if (!$required) {
                continue;
            }

            $matched = $this->qualificationGroupMatched($applicant, $group);

            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    private function qualificationGroupMatched(Applicant $applicant, array $group): bool
{
    $qualificationLevelCodes = $this->normalizeCodeArray(
        $group['qualification_level_codes'] ?? $group['qualification_levels'] ?? []
    );

    $subjectGroupCodes = $this->normalizeCodeArray(
        $group['subject_group_codes'] ?? $group['subject_groups'] ?? []
    );

    $minimumPercentage = $group['minimum_percentage'] ?? null;
    $minimumMarks = $group['minimum_marks'] ?? null;
    $minimumCgpa = $group['minimum_cgpa'] ?? null;

    $qualifications = ApplicantQualification::query()
        ->where('tenant_id', $applicant->tenant_id)
        ->where('applicant_id', $applicant->id)
        ->whereIn('result_status_code', ['passed', 'completed', 'verified'])
        ->get();

    foreach ($qualifications as $qualification) {
        $levelCode = $this->lookupCodeById($qualification->qualification_level_id);
        $subjectGroupCode = $this->lookupCodeById($qualification->subject_group_id);

        if (count($qualificationLevelCodes) > 0 && !in_array($levelCode, $qualificationLevelCodes, true)) {
            continue;
        }

        if (count($subjectGroupCodes) > 0 && !in_array($subjectGroupCode, $subjectGroupCodes, true)) {
            continue;
        }

        if ($minimumPercentage !== null) {
            if ($qualification->percentage === null || (float) $qualification->percentage < (float) $minimumPercentage) {
                continue;
            }
        }

        if ($minimumMarks !== null) {
            if ($qualification->obtained_marks === null || (float) $qualification->obtained_marks < (float) $minimumMarks) {
                continue;
            }
        }

        if ($minimumCgpa !== null) {
            if ($qualification->cgpa === null || (float) $qualification->cgpa < (float) $minimumCgpa) {
                continue;
            }
        }

        return true;
    }

    return false;
}
private function admissionTestRequired(Applicant $applicant, ProgramEligibilityRule $rule): bool
{
    $acceptedTestCodes = $this->acceptedTestCodes($rule);

    $query = ApplicantTestResult::query()
        ->where('tenant_id', $applicant->tenant_id)
        ->where('applicant_id', $applicant->id)
        ->whereIn('result_status_code', ['submitted', 'verified', 'passed']);

    if (count($acceptedTestCodes) > 0) {
        $query->whereIn('test_code', $acceptedTestCodes);
    }

    return $query->exists();
}

private function admissionTestMinimum(Applicant $applicant, ProgramEligibilityRule $rule): bool
{
    $acceptedTestCodes = $this->acceptedTestCodes($rule);

    $minimumMarks = $this->jsonValue($rule, 'minimum_marks');
    $minimumPercentage = $this->jsonValue($rule, 'minimum_percentage');
    $minimumPercentile = $this->jsonValue($rule, 'minimum_percentile');

    /*
     | Backward compatibility.
     | If old rule uses value_number, treat it according to source_field/operator.
     */
    if ($minimumMarks === null && $minimumPercentage === null && $minimumPercentile === null) {
        $sourceField = $rule->eligibilityRuleType?->source_field ?: 'percentage';

        if ($sourceField === 'obtained_marks') {
            $minimumMarks = $rule->value_number;
        } elseif ($sourceField === 'percentile') {
            $minimumPercentile = $rule->value_number;
        } else {
            $minimumPercentage = $rule->value_number;
        }
    }

    $query = ApplicantTestResult::query()
        ->where('tenant_id', $applicant->tenant_id)
        ->where('applicant_id', $applicant->id)
        ->whereIn('result_status_code', ['submitted', 'verified', 'passed']);

    if (count($acceptedTestCodes) > 0) {
        $query->whereIn('test_code', $acceptedTestCodes);
    }

    $tests = $query->get();

    foreach ($tests as $test) {
        if ($minimumMarks !== null) {
            if ($test->obtained_marks === null || (float) $test->obtained_marks < (float) $minimumMarks) {
                continue;
            }
        }

        if ($minimumPercentage !== null) {
            if ($test->percentage === null || (float) $test->percentage < (float) $minimumPercentage) {
                continue;
            }
        }

        if ($minimumPercentile !== null) {
            if ($test->percentile === null || (float) $test->percentile < (float) $minimumPercentile) {
                continue;
            }
        }

        return true;
    }

    return false;
}

private function requiredDocumentsUploaded(Applicant $applicant, ProgramEligibilityRule $rule): bool
{
    $requiredDocumentCodes = $this->normalizeCodeArray(
        $this->jsonArray($rule, 'required_document_codes')
    );
    $requiredDocumentTypeIds = array_values(array_filter(array_map(
        fn ($value) => (int) $value,
        $this->jsonArray($rule, 'required_document_type_ids')
    )));
    /*
     | Backward compatibility:
     | If value_text contains comma-separated document codes.
     */
    if (count($requiredDocumentCodes) === 0 && !empty($rule->value_text)) {
        $requiredDocumentCodes = $this->normalizeCodeArray(
            explode(',', $rule->value_text)
        );
    }

    /*
     | If no document codes are configured, require at least one uploaded document.
     */
    if (count($requiredDocumentCodes) === 0) {
        return ApplicantDocument::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->whereNotNull('file_path')
            ->whereIn('verification_status_code', ['pending', 'submitted', 'verified'])
            ->exists();
    }
    if (count($requiredDocumentTypeIds) > 0) {
        foreach ($requiredDocumentTypeIds as $documentTypeId) {
            $exists = ApplicantDocument::query()
                ->where('tenant_id', $applicant->tenant_id)
                ->where('applicant_id', $applicant->id)
                ->where('document_type_id', $documentTypeId)
                ->whereNotNull('file_path')
                ->whereIn('verification_status_code', ['pending', 'submitted', 'verified'])
                ->exists();

            if (!$exists) {
                return false;
            }
        }

        return true;
    }
    foreach ($requiredDocumentCodes as $requiredCode) {
        $exists = ApplicantDocument::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->whereNotNull('file_path')
            ->whereIn('verification_status_code', ['pending', 'submitted', 'verified'])
            ->where(function ($query) use ($requiredCode) {
                $query->whereIn('document_type_id', function ($subQuery) use ($requiredCode) {
                    $subQuery->select('id')
                        ->from('lookup_values')
                        ->whereRaw('UPPER(code) = ?', [$requiredCode]);
                });

                /*
                 | Fallback if document title is used before proper type selection.
                 */
                $query->orWhereRaw('UPPER(document_title) LIKE ?', ['%' . $requiredCode . '%']);
            })
            ->exists();

        if (!$exists) {
            return false;
        }
    }

    return true;
}

private function acceptedTestCodes(ProgramEligibilityRule $rule): array
{
    $codes = [];

    if (!empty($rule->target_test_code)) {
        $codes[] = $rule->target_test_code;
    }

    if (!empty($rule->value_text)) {
        $codes = array_merge($codes, explode(',', $rule->value_text));
    }

    $jsonCodes = $this->jsonArray($rule, 'accepted_test_codes');

    if (count($jsonCodes) === 0) {
        $jsonCodes = $this->jsonArray($rule, 'test_codes');
    }

    $codes = array_merge($codes, $jsonCodes);

    return $this->normalizeCodeArray($codes);
}

private function jsonValue(ProgramEligibilityRule $rule, string $key): mixed
{
    $json = $rule->value_json;

    if (is_string($json)) {
        $json = json_decode($json, true);
    }

    if (!is_array($json)) {
        return null;
    }

    return $json[$key] ?? null;
}

private function jsonArray(ProgramEligibilityRule $rule, string $key): array
{
    $value = $this->jsonValue($rule, $key);

    if ($value === null) {
        return [];
    }

    if (is_array($value)) {
        return $value;
    }

    if (is_string($value)) {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    return [];
}

private function normalizeCodeArray(array $codes): array
{
    return array_values(array_unique(array_filter(array_map(function ($code) {
        return strtoupper(trim((string) $code));
    }, $codes))));
}

private function lookupCodeById(null|int|string $id): ?string
{
    if (!$id) {
        return null;
    }

    $code = DB::table('lookup_values')
        ->where('id', $id)
        ->value('code');

    return $code ? strtoupper(trim((string) $code)) : null;
}
}