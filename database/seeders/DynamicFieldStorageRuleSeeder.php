<?php

namespace Database\Seeders;

use App\Models\DynamicFieldStorageRule;
use Illuminate\Database\Seeder;

class DynamicFieldStorageRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = array_merge(
            $this->admissionRules(),
            $this->applicantPortalRules(),
            $this->eligibilityRules(),
            $this->assessmentRules(),
            $this->meritRules(),
            $this->offerVoucherRules(),
            $this->platformRules()
        );

        foreach ($rules as $rule) {
            DynamicFieldStorageRule::updateOrCreate(
                [
                    'module_code' => $rule['module_code'],
                    'entity_key' => $rule['entity_key'],
                    'field_name' => $rule['field_name'],
                ],
                $rule
            );
        }
    }

    private function admissionRules(): array
    {
        return [
            $this->id('admission', 'admission_sessions', 'academic_session_id', 'Academic Session', 'academic-sessions'),
            $this->code('admission', 'admission_sessions', 'status_code', 'Status', 'statuses'),

            $this->id('admission', 'offered_programs', 'admission_session_id', 'Admission Session', 'admission-sessions'),
            $this->id('admission', 'offered_programs', 'program_id', 'Program', 'programs'),
            $this->id('admission', 'offered_programs', 'department_id', 'Department', 'departments'),
            $this->code('admission', 'offered_programs', 'status_code', 'Status', 'statuses'),

            $this->id('admission', 'program_quota_seats', 'offered_program_id', 'Offered Program', 'offered-programs'),
            $this->id('admission', 'program_quota_seats', 'quota_type_id', 'Quota Type', 'quota-types'),
            $this->code('admission', 'program_quota_seats', 'status_code', 'Status', 'statuses'),

            $this->id('admission', 'admission_preference_groups', 'admission_session_id', 'Admission Session', 'admission-sessions'),
            $this->code('admission', 'admission_preference_groups', 'status_code', 'Status', 'statuses'),

            $this->id('admission', 'preference_group_programs', 'admission_preference_group_id', 'Preference Group', 'admission-preference-groups'),
            $this->id('admission', 'preference_group_programs', 'offered_program_id', 'Offered Program', 'offered-programs'),
            $this->id('admission', 'preference_group_programs', 'program_quota_seat_id', 'Quota / Seat Category', 'program-quota-seats'),
            $this->code('admission', 'preference_group_programs', 'status_code', 'Status', 'statuses'),
        ];
    }

    private function applicantPortalRules(): array
    {
        return [
            $this->id('admission', 'applicant_qualifications', 'qualification_level_id', 'Qualification Level', 'qualification-levels'),
            $this->id('admission', 'applicant_qualifications', 'subject_group_id', 'Subject Group', 'subject-groups'),
            $this->raw('admission', 'applicant_qualifications', 'marks_obtained', 'Marks Obtained'),
            $this->raw('admission', 'applicant_qualifications', 'total_marks', 'Total Marks'),
            $this->raw('admission', 'applicant_qualifications', 'percentage', 'Percentage'),
            $this->code('admission', 'applicant_qualifications', 'status_code', 'Status', 'statuses'),

            $this->id('admission', 'applicant_documents', 'document_type_id', 'Document Type', 'document-types'),
            $this->code('admission', 'applicant_documents', 'status_code', 'Status', 'document-statuses'),

            $this->id('admission', 'applicant_test_results', 'assessment_id', 'Assessment / Test', 'assessments'),
            $this->raw('admission', 'applicant_test_results', 'obtained_marks', 'Obtained Marks'),
            $this->raw('admission', 'applicant_test_results', 'total_marks', 'Total Marks'),
            $this->raw('admission', 'applicant_test_results', 'percentage', 'Percentage'),
            $this->code('admission', 'applicant_test_results', 'status_code', 'Status', 'test-result-statuses'),
        ];
    }

    private function eligibilityRules(): array
    {
        return [
            $this->id('admission', 'program_eligibility_rules', 'admission_session_id', 'Admission Session', 'admission-sessions'),
            $this->id('admission', 'program_eligibility_rules', 'offered_program_id', 'Offered Program', 'offered-programs'),
            $this->id('admission', 'program_eligibility_rules', 'program_quota_seat_id', 'Quota / Seat Category', 'program-quota-seats'),
            $this->code('admission', 'program_eligibility_rules', 'status_code', 'Status', 'statuses'),

            $this->id('admission', 'eligibility_qualification_requirements', 'qualification_level_id', 'Qualification Level', 'qualification-levels'),
            $this->jsonIds('admission', 'eligibility_qualification_requirements', 'allowed_subject_group_ids', 'Allowed Subject Groups', 'subject-groups'),
            $this->raw('admission', 'eligibility_qualification_requirements', 'minimum_percentage', 'Minimum Percentage'),

            $this->jsonIds('admission', 'eligibility_test_policies', 'accepted_assessment_ids', 'Accepted Tests', 'assessments'),
            $this->raw('admission', 'eligibility_test_policies', 'minimum_percentage', 'Minimum Test Percentage'),
            $this->raw('admission', 'eligibility_test_policies', 'minimum_marks', 'Minimum Test Marks'),

            $this->jsonIds('admission', 'eligibility_document_requirements', 'required_document_type_ids', 'Required Documents', 'document-types'),
        ];
    }

    private function assessmentRules(): array
    {
        return [
            $this->code('assessment', 'assessments', 'purpose_code', 'Purpose', 'assessment-purposes'),
            $this->code('assessment', 'assessments', 'mode_code', 'Mode', 'assessment-modes'),
            $this->code('assessment', 'assessments', 'status_code', 'Status', 'statuses'),

            $this->id('assessment', 'assessment_sections', 'assessment_id', 'Assessment', 'assessments'),
            $this->id('assessment', 'assessment_sections', 'assessment_subject_id', 'Assessment Subject', 'assessment-subjects'),
            $this->code('assessment', 'assessment_sections', 'question_selection_mode_code', 'Question Selection Mode', 'question-selection-modes'),
            $this->code('assessment', 'assessment_sections', 'status_code', 'Status', 'statuses'),

            $this->id('assessment', 'assessment_questions', 'question_bank_id', 'Question Bank', 'question-banks'),
            $this->id('assessment', 'assessment_questions', 'assessment_subject_id', 'Assessment Subject', 'assessment-subjects'),
            $this->id('assessment', 'assessment_questions', 'assessment_topic_id', 'Assessment Topic', 'assessment-topics'),
            $this->code('assessment', 'assessment_questions', 'question_type_code', 'Question Type', 'assessment-question-types'),
            $this->code('assessment', 'assessment_questions', 'difficulty_code', 'Difficulty', 'assessment-difficulties'),
            $this->code('assessment', 'assessment_questions', 'cognitive_level_code', 'Cognitive Level', 'assessment-cognitive-levels'),
            $this->code('assessment', 'assessment_questions', 'status_code', 'Status', 'statuses'),

            $this->id('assessment', 'assessment_participants', 'assessment_id', 'Assessment', 'assessments'),
            $this->id('assessment', 'assessment_participants', 'assessment_schedule_id', 'Assessment Schedule', 'assessment-schedules'),
            $this->id('assessment', 'assessment_participants', 'applicant_id', 'Applicant', 'applicants'),
            $this->code('assessment', 'assessment_participants', 'status_code', 'Status', 'participant-statuses'),

            $this->id('assessment', 'assessment_results', 'assessment_id', 'Assessment', 'assessments'),
            $this->id('assessment', 'assessment_results', 'assessment_attempt_id', 'Assessment Attempt', 'assessment-attempts'),
            $this->code('assessment', 'assessment_results', 'result_status_code', 'Result Status', 'assessment-result-statuses'),
        ];
    }

    private function meritRules(): array
    {
        return [
            $this->code('admission', 'admission_merit_formulas', 'status_code', 'Status', 'statuses'),

            $this->id('admission', 'admission_merit_formula_components', 'admission_merit_formula_id', 'Merit Formula', 'admission-merit-formulas'),
            $this->code('admission', 'admission_merit_formula_components', 'component_type_code', 'Component Type', 'merit-component-types'),
            $this->code('admission', 'admission_merit_formula_components', 'source_type_code', 'Source Type', 'merit-source-types'),
            $this->code('admission', 'admission_merit_formula_components', 'calculation_method_code', 'Calculation Method', 'merit-calculation-methods'),
            $this->code('admission', 'admission_merit_formula_components', 'source_key', 'Source Key', 'merit-source-keys'),
            $this->code('admission', 'admission_merit_formula_components', 'status_code', 'Status', 'statuses'),

            $this->id('admission', 'admission_merit_formula_applicabilities', 'admission_merit_formula_id', 'Merit Formula', 'admission-merit-formulas'),
            $this->id('admission', 'admission_merit_formula_applicabilities', 'admission_session_id', 'Admission Session', 'admission-sessions'),
            $this->id('admission', 'admission_merit_formula_applicabilities', 'admission_preference_group_id', 'Preference Group', 'admission-preference-groups'),
            $this->id('admission', 'admission_merit_formula_applicabilities', 'offered_program_id', 'Offered Program', 'offered-programs'),
            $this->id('admission', 'admission_merit_formula_applicabilities', 'program_quota_seat_id', 'Quota / Seat Category', 'program-quota-seats'),
            $this->code('admission', 'admission_merit_formula_applicabilities', 'applicability_scope_code', 'Applicability Scope', 'merit-applicability-scopes'),
            $this->code('admission', 'admission_merit_formula_applicabilities', 'status_code', 'Status', 'statuses'),

            $this->id('admission', 'admission_applicant_merit_scores', 'admission_merit_formula_id', 'Merit Formula', 'admission-merit-formulas'),
            $this->id('admission', 'admission_applicant_merit_scores', 'applicant_id', 'Applicant', 'applicants'),
            $this->id('admission', 'admission_applicant_merit_scores', 'admission_session_id', 'Admission Session', 'admission-sessions'),
            $this->id('admission', 'admission_applicant_merit_scores', 'offered_program_id', 'Offered Program', 'offered-programs'),
            $this->id('admission', 'admission_applicant_merit_scores', 'program_quota_seat_id', 'Quota / Seat Category', 'program-quota-seats'),
            $this->code('admission', 'admission_applicant_merit_scores', 'status_code', 'Status', 'merit-score-statuses'),

            $this->code('admission', 'admission_merit_lists', 'status_code', 'Status', 'merit-list-statuses'),
            $this->code('admission', 'admission_merit_lists', 'list_type_code', 'List Type', 'merit-list-types'),

            $this->code('admission', 'admission_merit_list_applicants', 'selection_status_code', 'Selection Status', 'merit-selection-statuses'),
            $this->code('admission', 'admission_merit_list_applicants', 'offer_status_code', 'Offer Status', 'merit-offer-statuses'),
            $this->code('admission', 'admission_merit_list_applicants', 'admission_confirmation_status_code', 'Admission Confirmation Status', 'admission-confirmation-statuses'),
        ];
    }

    private function offerVoucherRules(): array
    {
        return [
            $this->code('admission', 'admission_offer_fee_vouchers', 'voucher_type_code', 'Voucher Type', 'voucher-types'),
            $this->code('admission', 'admission_offer_fee_vouchers', 'currency_code', 'Currency', 'currencies'),
            $this->code('admission', 'admission_offer_fee_vouchers', 'status_code', 'Voucher Status', 'voucher-statuses'),
            $this->code('admission', 'admission_offer_fee_vouchers', 'payment_method_code', 'Payment Method', 'payment-methods'),

            $this->code('admission', 'admission_confirmations', 'status_code', 'Confirmation Status', 'admission-confirmation-statuses'),
        ];
    }

    private function platformRules(): array
    {
        return [
            $this->code('platform', 'modules', 'code', 'Module Code', null),
            $this->code('platform', 'menus', 'code', 'Menu Code', null),
            $this->code('platform', 'menus', 'module_code', 'Module Code', null),
            $this->raw('platform', 'menus', 'route', 'Route'),
            $this->code('platform', 'permissions', 'name', 'Permission Name', null),
            $this->code('platform', 'roles', 'name', 'Role Name', null),
        ];
    }

    private function id(string $module, string $entity, string $field, string $label, ?string $source): array
    {
        return $this->rule($module, $entity, $field, $label, 'id', $source, 'master_data');
    }

    private function code(string $module, string $entity, string $field, string $label, ?string $source): array
    {
        return $this->rule($module, $entity, $field, $label, 'code', $source, 'system_enum');
    }

    private function raw(string $module, string $entity, string $field, string $label): array
    {
        return $this->rule($module, $entity, $field, $label, 'raw', null, 'user_input', false);
    }

    private function jsonIds(string $module, string $entity, string $field, string $label, ?string $source): array
    {
        return $this->rule($module, $entity, $field, $label, 'json_ids', $source, 'master_data');
    }

    private function jsonCodes(string $module, string $entity, string $field, string $label, ?string $source): array
    {
        return $this->rule($module, $entity, $field, $label, 'json_codes', $source, 'system_enum');
    }

    private function rule(
        string $module,
        string $entity,
        string $field,
        string $label,
        string $storageMode,
        ?string $source,
        string $category,
        bool $critical = true
    ): array {
        return [
            'module_code' => $module,
            'entity_key' => $entity,
            'field_name' => $field,
            'field_label' => $label,
            'storage_mode' => $storageMode,
            'option_source_key' => $source,
            'value_category' => $category,
            'is_business_critical' => $critical,
            'is_required_for_rules' => $critical,
            'is_system_locked' => true,
            'status_code' => 'active',
            'notes' => $this->notes($storageMode),
        ];
    }

    private function notes(string $storageMode): string
    {
        return match ($storageMode) {
            'id' => 'Store numeric database ID. Display label must not be stored.',
            'code' => 'Store stable business code. Do not store lookup row ID here.',
            'json_ids' => 'Store JSON array of numeric database IDs.',
            'json_codes' => 'Store JSON array of stable business codes.',
            default => 'Raw value. Do not use for business-critical matching unless explicitly allowed.',
        };
    }
}