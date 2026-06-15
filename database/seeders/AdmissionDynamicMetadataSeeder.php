<?php

namespace Database\Seeders;

use App\Core\Dynamic\Models\DynamicAction;
use App\Core\Dynamic\Models\DynamicEntity;
use App\Core\Dynamic\Models\DynamicField;
use App\Core\Dynamic\Models\DynamicFilter;
use App\Modules\Admission\Models\AdmissionSession;
use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use App\Modules\Admission\Models\EligibilityRuleType;
use App\Modules\Admission\Models\OfferedProgram;
use App\Modules\Admission\Models\AdmissionPreferenceGroup;
use App\Modules\Admission\Models\AdmissionPreferenceGroupProgram;
use App\Modules\Admission\Models\ProgramEligibilityRule;
use App\Modules\Admission\Models\ProgramQuotaSeat;
use Illuminate\Database\Seeder;

class AdmissionDynamicMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $this->admissionSessions();
        $this->offeredPrograms();
        $this->programQuotaSeats();
        $this->admissionPreferenceGroups();
        $this->admissionPreferenceGroupPrograms();
        $this->eligibilityRuleTypes();
        $this->programEligibilityRules();
        $this->applicants();
        $this->applicantProgramApplications();
    }

    private function admissionSessions(): void
    {
        $entity = $this->entity(
            'Admission',
            'Admission Session',
            'admission-sessions',
            'admission_sessions',
            AdmissionSession::class,
            'Admission Sessions',
            'Manage admission sessions and application timelines.'
        );

        $this->selectField($entity, 'academic_session_id', 'Academic Session', '/dynamic-options/academic-sessions', false, true, true, 1, null, null, 'academicSession', 'name');

        $this->text($entity, 'code', 'Code', true, true, true, 2, true);
        $this->text($entity, 'name', 'Name', true, true, true, 3);

        $this->date($entity, 'application_start_date', 'Application Start Date', false, true, false, 4);
        $this->date($entity, 'application_end_date', 'Application End Date', false, true, false, 5);
        $this->date($entity, 'document_submission_deadline', 'Document Submission Deadline', false, false, false, 6);
        $this->date($entity, 'test_start_date', 'Test Start Date', false, false, false, 7);
        $this->date($entity, 'test_end_date', 'Test End Date', false, false, false, 8);
        $this->date($entity, 'merit_list_start_date', 'Merit List Start Date', false, false, false, 9);

        $this->switchField($entity, 'is_current', 'Current Session', false, true, true, 10, false);

        $this->lookupCodeField($entity, 'admission_mode_code', 'Admission Mode', 'ADMISSION_MODE', true, true, true, 11, 'online');
        $this->lookupCodeField($entity, 'status_code', 'Status', 'ADMISSION_SESSION_STATUS', true, true, true, 12, 'draft');

        $this->textarea($entity, 'description', 'Description', false, false, false, 90);
        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 91);

        $this->filter($entity, 'academic_session_id', 'Academic Session', 'select', '=', 1, '/dynamic-options/academic-sessions');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 2, '/dynamic-options/lookups/ADMISSION_SESSION_STATUS');
        $this->filter($entity, 'admission_mode_code', 'Admission Mode', 'select', '=', 3, '/dynamic-options/lookups/ADMISSION_MODE');

        $this->standardActions($entity, 'admission.session');
    }

    private function offeredPrograms(): void
    {
        $entity = $this->entity(
            'Admission',
            'Offered Program',
            'offered-programs',
            'offered_programs',
            OfferedProgram::class,
            'Offered Programs',
            'Manage programs offered in admission sessions.'
        );

        $this->selectField($entity, 'admission_session_id', 'Admission Session', '/dynamic-options/admission-sessions', true, true, true, 1, null, null, 'admissionSession', 'name');
        $this->selectField($entity, 'academic_session_id', 'Academic Session', '/dynamic-options/academic-sessions', false, true, true, 2, null, null, 'academicSession', 'name');

        $this->selectField($entity, 'campus_id', 'Campus', '/dynamic-options/campuses', false, false, true, 3, null, null, 'campus', 'name');
        $this->selectField($entity, 'faculty_id', 'Faculty', '/dynamic-options/faculties', false, true, true, 4, null, null, 'faculty', 'name');
        $this->selectField($entity, 'institute_id', 'Institute', '/dynamic-options/institutes', false, true, true, 5, 'faculty_id', 'faculty_id', 'institute', 'name');
        $this->selectField($entity, 'department_id', 'Department', '/dynamic-options/departments', false, true, true, 6, 'institute_id', 'institute_id', 'department', 'name');
        $this->selectField($entity, 'program_level_id', 'Program Level', '/dynamic-options/program-levels', false, false, true, 7, null, null, 'programLevel', 'name');
        $this->selectField($entity, 'program_id', 'Program', '/dynamic-options/programs', true, true, true, 8, 'department_id', 'department_id', 'program', 'name');
        $this->selectField($entity, 'curriculum_id', 'Curriculum', '/dynamic-options/curriculums', false, true, true, 9, 'program_id', 'program_id', 'curriculum', 'name');
        $this->selectField($entity, 'student_batch_id', 'Student Batch', '/dynamic-options/student-batches', false, false, true, 10, 'program_id', 'program_id', 'studentBatch', 'name');

        $this->text($entity, 'code', 'Code', true, true, true, 11, true);
        $this->text($entity, 'title', 'Title', true, true, true, 12);

        $this->lookupIdField($entity, 'shift_id', 'Shift', 'PROGRAM_SHIFT', false, true, true, 13, 'shift');
        $this->lookupCodeField($entity, 'shift_code', 'Shift Code', 'PROGRAM_SHIFT', false, false, true, 14, null);

        $this->number($entity, 'application_fee', 'Application Fee', false, true, false, 15, 0);
        $this->number($entity, 'admission_fee', 'Admission Fee', false, true, false, 16, 0);

        $this->switchField($entity, 'requires_test', 'Requires Test', false, true, true, 17, false);
        $this->switchField($entity, 'requires_interview', 'Requires Interview', false, false, true, 18, false);
        $this->switchField($entity, 'requires_experience', 'Requires Experience', false, false, true, 19, false);
        $this->switchField($entity, 'requires_research_profile', 'Requires Research Profile', false, false, true, 20, false);
        $this->switchField($entity, 'is_published', 'Published', false, true, true, 21, false);

        $this->date($entity, 'application_start_date', 'Application Start Date', false, true, false, 22);
        $this->date($entity, 'application_end_date', 'Application End Date', false, true, false, 23);

        $this->lookupCodeField($entity, 'status_code', 'Status', 'OFFERED_PROGRAM_STATUS', true, true, true, 24, 'draft');

        $this->textarea($entity, 'description', 'Description', false, false, false, 90);
        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 91);

        $this->filter($entity, 'admission_session_id', 'Admission Session', 'select', '=', 1, '/dynamic-options/admission-sessions');
        $this->filter($entity, 'program_id', 'Program', 'select', '=', 2, '/dynamic-options/programs');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 3, '/dynamic-options/lookups/OFFERED_PROGRAM_STATUS');
        $this->filter($entity, 'is_published', 'Published', 'select', '=', 4, null, [
            ['label' => 'Yes', 'value' => true],
            ['label' => 'No', 'value' => false],
        ]);

        $this->standardActions($entity, 'admission.offered_program');
    }

    private function programQuotaSeats(): void
    {
        $entity = $this->entity(
            'Admission',
            'Program Quota Seat',
            'program-quota-seats',
            'program_quota_seats',
            ProgramQuotaSeat::class,
            'Program Quota Seats',
            'Manage quota-wise seats for offered programs.'
        );

        $this->selectField($entity, 'offered_program_id', 'Offered Program', '/dynamic-options/offered-programs', true, true, true, 1, null, null, 'offeredProgram', 'title');
        $this->lookupIdField($entity, 'quota_type_id', 'Quota Type', 'QUOTA_TYPE', true, true, true, 2, 'quotaType');

        $this->text($entity, 'quota_code', 'Quota Code', true, true, true, 3);
        $this->text($entity, 'quota_name', 'Quota Name', true, true, true, 4);

        $this->number($entity, 'allocated_seats', 'Allocated Seats', true, true, false, 5, 0);
        $this->number($entity, 'filled_seats', 'Filled Seats', false, true, false, 6, 0);
        $this->number($entity, 'available_seats', 'Available Seats', false, true, false, 7, 0);

        $this->number($entity, 'application_fee', 'Application Fee', false, false, false, 8, null);
        $this->number($entity, 'admission_fee', 'Admission Fee', false, false, false, 9, null);

        $this->switchField($entity, 'is_default', 'Default Quota', false, true, true, 10, false);
        $this->switchField($entity, 'is_active', 'Active', false, true, true, 11, true);

        $this->number($entity, 'display_order', 'Display Order', false, false, false, 12, 0);

        $this->textarea($entity, 'eligibility_notes', 'Eligibility Notes', false, false, false, 90);
        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 91);

        $this->filter($entity, 'offered_program_id', 'Offered Program', 'select', '=', 1, '/dynamic-options/offered-programs');
        $this->filter($entity, 'quota_type_id', 'Quota Type', 'select', '=', 2, '/dynamic-options/lookups/QUOTA_TYPE');
        $this->filter($entity, 'is_active', 'Active', 'select', '=', 3, null, [
            ['label' => 'Yes', 'value' => true],
            ['label' => 'No', 'value' => false],
        ]);

        $this->standardActions($entity, 'admission.quota_seat');
    }

    private function eligibilityRuleTypes(): void
    {
        $entity = $this->entity(
            'Admission',
            'Eligibility Rule Type',
            'eligibility-rule-types',
            'eligibility_rule_types',
            EligibilityRuleType::class,
            'Eligibility Rule Types',
            'System catalog of eligibility rule types.'
        );

        $this->text($entity, 'code', 'Code', true, true, true, 1, true);
        $this->text($entity, 'name', 'Name', true, true, true, 2);

        $this->text($entity, 'source_area', 'Source Area', true, true, true, 3);
        $this->text($entity, 'source_collection', 'Source Collection', false, true, true, 4);
        $this->text($entity, 'source_field', 'Source Field', false, true, true, 5);
        $this->text($entity, 'expected_value_type', 'Expected Value Type', true, true, true, 6);
        $this->text($entity, 'evaluator_key', 'Evaluator Key', true, true, true, 7);

        $this->switchField($entity, 'is_system', 'System', false, true, true, 8, true);
        $this->switchField($entity, 'is_active', 'Active', false, true, true, 9, true);

        $this->number($entity, 'display_order', 'Display Order', false, false, false, 10, 0);
        $this->textarea($entity, 'description', 'Description', false, false, false, 90);

        $this->filter($entity, 'source_area', 'Source Area', 'text', 'like', 1);
        $this->filter($entity, 'is_active', 'Active', 'select', '=', 2, null, [
            ['label' => 'Yes', 'value' => true],
            ['label' => 'No', 'value' => false],
        ]);

        $this->standardActions($entity, 'admission.eligibility_rule_type');
    }
private function admissionPreferenceGroups(): void
{
    $entity = $this->entity(
        'Admission',
        'Admission Preference Group',
        'admission-preference-groups',
        'admission_preference_groups',
        AdmissionPreferenceGroup::class,
        'Preference Groups',
        'Group offered programs so applicants can apply with ordered preferences.'
    );

    $this->selectField($entity, 'admission_session_id', 'Admission Session', '/dynamic-options/admission-sessions', true, true, true, 1, null, null, 'admissionSession', 'name');

    $this->text($entity, 'code', 'Code', true, true, true, 2, true);
    $this->text($entity, 'name', 'Name', true, true, true, 3);

    $this->number($entity, 'min_preferences', 'Minimum Preferences', true, true, false, 4, 1);
    $this->number($entity, 'max_preferences', 'Maximum Preferences', false, true, false, 5, null);

    $this->switchField($entity, 'is_default', 'Default Group', false, true, true, 6, false);

    $this->lookupCodeField($entity, 'status_code', 'Status', 'STATUS', true, true, true, 7, 'active');

    $this->number($entity, 'display_order', 'Display Order', false, false, false, 8, 0);

    $this->textarea($entity, 'description', 'Description', false, false, false, 90);

    $this->filter($entity, 'admission_session_id', 'Admission Session', 'select', '=', 1, '/dynamic-options/admission-sessions');
    $this->filter($entity, 'status_code', 'Status', 'select', '=', 2, '/dynamic-options/lookups/STATUS');

    $this->standardActions($entity, 'admission.preference_group');
}
private function admissionPreferenceGroupPrograms(): void
{
    $entity = $this->entity(
        'Admission',
        'Admission Preference Group Program',
        'admission-preference-group-programs',
        'admission_preference_group_programs',
        AdmissionPreferenceGroupProgram::class,
        'Preference Group Programs',
        'Attach offered programs to an admission preference group.'
    );

    $this->selectField(
        $entity,
        'admission_preference_group_id',
        'Preference Group',
        '/dynamic-options/admission-preference-groups',
        true,
        true,
        true,
        1,
        null,
        null,
        'preferenceGroup',
        'name'
    );

    $this->selectField(
        $entity,
        'offered_program_id',
        'Offered Program',
        '/dynamic-options/offered-programs',
        true,
        true,
        true,
        2,
        null,
        null,
        'offeredProgram',
        'title'
    );

    $this->number($entity, 'display_order', 'Display Order', false, true, false, 3, 0);
    $this->lookupCodeField($entity, 'status_code', 'Status', 'STATUS', true, true, true, 4, 'active');

    $this->filter($entity, 'admission_preference_group_id', 'Preference Group', 'select', '=', 1, '/dynamic-options/admission-preference-groups');
    $this->filter($entity, 'offered_program_id', 'Offered Program', 'select', '=', 2, '/dynamic-options/offered-programs');
    $this->filter($entity, 'status_code', 'Status', 'select', '=', 3, '/dynamic-options/lookups/STATUS');

    $this->standardActions($entity, 'admission.preference_group_program');
}
    private function programEligibilityRules(): void
    {
        $entity = $this->entity(
            'Admission',
            'Program Eligibility Rule',
            'program-eligibility-rules',
            'program_eligibility_rules',
            ProgramEligibilityRule::class,
            'Program Eligibility Rules',
            'Configure eligibility rules for offered programs and quotas.'
        );

        $this->selectField($entity, 'offered_program_id', 'Offered Program', '/dynamic-options/offered-programs', true, true, true, 1, null, null, 'offeredProgram', 'title');
        $this->selectField($entity, 'program_quota_seat_id', 'Quota Seat', '/dynamic-options/program-quota-seats', false, true, true, 2, 'offered_program_id', 'offered_program_id', 'programQuotaSeat', 'quota_name');
        $this->selectField($entity, 'eligibility_rule_type_id', 'Rule Type', '/dynamic-options/eligibility-rule-types', true, true, true, 3, null, null, 'eligibilityRuleType', 'name');

        $this->text($entity, 'rule_code', 'Rule Code', true, true, true, 4);
        $this->text($entity, 'rule_group', 'Rule Group', false, true, true, 5);
        $this->text($entity, 'rule_title', 'Rule Title', true, true, true, 6);
        $this->text($entity, 'operator', 'Operator', true, true, true, 7);

        $this->textarea($entity, 'value_text', 'Value Text', false, false, false, 8);
        $this->number($entity, 'value_number', 'Value Number', false, true, false, 9, null);
        $this->date($entity, 'value_date', 'Value Date', false, false, false, 10);
        $this->lookupIdField($entity, 'value_lookup_id', 'Value Lookup', 'QUOTA_TYPE', false, false, true, 11, 'valueLookup');

        $this->selectField($entity, 'target_qualification_level_id', 'Target Qualification Level', '/dynamic-options/lookups/QUALIFICATION_LEVEL', false, true, true, 12, null, null, 'targetQualificationLevel', 'name');
        $this->selectField($entity, 'target_subject_group_id', 'Target Subject Group', '/dynamic-options/lookups/SUBJECT_GROUP', false, false, true, 13, null, null, 'targetSubjectGroup', 'name');
        $this->selectField($entity, 'target_document_type_id', 'Target Document Type', '/dynamic-options/lookups/DOCUMENT_TYPE', false, false, true, 14, null, null, 'targetDocumentType', 'name');

        $this->text($entity, 'target_test_code', 'Target Test Code', false, false, true, 15);

        $this->switchField($entity, 'is_mandatory', 'Mandatory', false, true, true, 16, true);
        $this->switchField($entity, 'is_active', 'Active', false, true, true, 17, true);

        $this->text($entity, 'failure_message', 'Failure Message', false, false, true, 18);
        $this->textarea($entity, 'description', 'Description', false, false, false, 19);
        $this->number($entity, 'display_order', 'Display Order', false, false, false, 20, 0);

        $this->filter($entity, 'offered_program_id', 'Offered Program', 'select', '=', 1, '/dynamic-options/offered-programs');
        $this->filter($entity, 'eligibility_rule_type_id', 'Rule Type', 'select', '=', 2, '/dynamic-options/eligibility-rule-types');
        $this->filter($entity, 'is_active', 'Active', 'select', '=', 3, null, [
            ['label' => 'Yes', 'value' => true],
            ['label' => 'No', 'value' => false],
        ]);

        $this->standardActions($entity, 'admission.eligibility_rule');
    }

    private function applicants(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant',
            'applicants',
            'applicants',
            Applicant::class,
            'Applicants',
            'Manage admission applicants.'
        );

        $this->text($entity, 'applicant_no', 'Applicant No', false, true, true, 1, true);
        $this->text($entity, 'application_account_no', 'Application Account No', false, false, true, 2);

        $this->text($entity, 'first_name', 'First Name', true, true, true, 3);
        $this->text($entity, 'last_name', 'Last Name', false, true, true, 4);
        $this->text($entity, 'full_name', 'Full Name', false, true, true, 5, false, false);

        $this->text($entity, 'father_name', 'Father Name', false, true, true, 6);
        $this->text($entity, 'mother_name', 'Mother Name', false, false, true, 7);

        $this->text($entity, 'cnic_bform', 'CNIC / B-Form', false, true, true, 8, true);
        $this->text($entity, 'passport_no', 'Passport No', false, false, true, 9);

        $this->date($entity, 'date_of_birth', 'Date of Birth', false, true, false, 10);
        $this->text($entity, 'gender', 'Gender', false, true, true, 11);

        $this->lookupIdField($entity, 'nationality_id', 'Nationality', 'NATIONALITY', false, true, true, 12, 'nationality');
        $this->lookupIdField($entity, 'religion_id', 'Religion', 'RELIGION', false, false, true, 13, 'religion');
        $this->lookupIdField($entity, 'blood_group_id', 'Blood Group', 'BLOOD_GROUP', false, false, true, 14, 'bloodGroup');

        $this->text($entity, 'email', 'Email', false, true, true, 15, true);
        $this->text($entity, 'phone', 'Phone', false, true, true, 16);
        $this->text($entity, 'alternate_phone', 'Alternate Phone', false, false, true, 17);

        $this->textarea($entity, 'current_address', 'Current Address', false, false, false, 18);
        $this->textarea($entity, 'permanent_address', 'Permanent Address', false, false, false, 19);

        $this->lookupIdField($entity, 'country_id', 'Country', 'COUNTRY', false, false, true, 20, 'country');
        $this->lookupIdField($entity, 'province_id', 'Province', 'PROVINCE', false, false, true, 21, 'province', 'country_id', 'parent_id');
        $this->lookupIdField($entity, 'city_id', 'City', 'CITY', false, true, true, 22, 'city', 'province_id', 'parent_id');

        $this->lookupIdField($entity, 'domicile_province_id', 'Domicile Province', 'PROVINCE', false, false, true, 23, 'domicileProvince');
        $this->lookupIdField($entity, 'domicile_district_id', 'Domicile District', 'CITY', false, false, true, 24, 'domicileDistrict', 'domicile_province_id', 'parent_id');

        $this->switchField($entity, 'has_disability', 'Has Disability', false, true, true, 25, false);
        $this->lookupIdField($entity, 'disability_type_id', 'Disability Type', 'DISABILITY_TYPE', false, false, true, 26, 'disabilityType');

        $this->switchField($entity, 'has_experience', 'Has Experience', false, true, true, 27, false);
        $this->switchField($entity, 'has_research_profile', 'Has Research Profile', false, true, true, 28, false);
        $this->switchField($entity, 'has_publications', 'Has Publications', false, true, true, 29, false);

        $this->text($entity, 'photo_path', 'Photo Path', false, false, false, 30);

        $this->lookupCodeField($entity, 'profile_status_code', 'Profile Status', 'PROFILE_STATUS', true, true, true, 31, 'draft');
        $this->lookupCodeField($entity, 'applicant_status_code', 'Applicant Status', 'APPLICANT_STATUS', true, true, true, 32, 'active');

        $this->dateTime($entity, 'profile_completed_at', 'Profile Completed At', false, false, false, 33);
        $this->dateTime($entity, 'submitted_at', 'Submitted At', false, true, false, 34);

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'profile_status_code', 'Profile Status', 'select', '=', 1, '/dynamic-options/lookups/PROFILE_STATUS');
        $this->filter($entity, 'applicant_status_code', 'Applicant Status', 'select', '=', 2, '/dynamic-options/lookups/APPLICANT_STATUS');
        $this->filter($entity, 'gender', 'Gender', 'text', 'like', 3);
        $this->filter($entity, 'city_id', 'City', 'select', '=', 4, '/dynamic-options/lookups/CITY');

        $this->standardActions($entity, 'admission.applicant');
    }

    private function applicantProgramApplications(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant Program Application',
            'applicant-program-applications',
            'applicant_program_applications',
            ApplicantProgramApplication::class,
            'Applicant Program Applications',
            'Manage applicant applications to offered programs.'
        );

        $this->selectField($entity, 'admission_session_id', 'Admission Session', '/dynamic-options/admission-sessions', true, true, true, 1, null, null, 'admissionSession', 'name');
        $this->selectField($entity, 'applicant_id', 'Applicant', '/dynamic-options/applicants', true, true, true, 2, null, null, 'applicant', 'full_name');
        $this->selectField($entity, 'offered_program_id', 'Offered Program', '/dynamic-options/offered-programs', true, true, true, 3, 'admission_session_id', 'admission_session_id', 'offeredProgram', 'title');
        $this->selectField($entity, 'program_quota_seat_id', 'Quota Seat', '/dynamic-options/program-quota-seats', true, true, true, 4, 'offered_program_id', 'offered_program_id', 'programQuotaSeat', 'quota_name');

        $this->text($entity, 'application_no', 'Application No', false, true, true, 5, true);
        $this->number($entity, 'preference_order', 'Preference Order', false, true, false, 6, 1);

        $this->lookupCodeField($entity, 'eligibility_status_code', 'Eligibility Status', 'ELIGIBILITY_STATUS', true, true, true, 7, 'pending');
        $this->textarea($entity, 'eligibility_remarks', 'Eligibility Remarks', false, false, false, 8);

        $this->lookupCodeField($entity, 'application_status_code', 'Application Status', 'APPLICATION_STATUS', true, true, true, 9, 'draft');
        $this->lookupCodeField($entity, 'document_status_code', 'Document Status', 'DOCUMENT_STATUS', true, true, true, 10, 'pending');
        $this->lookupCodeField($entity, 'fee_status_code', 'Fee Status', 'FEE_STATUS', true, true, true, 11, 'unpaid');
        $this->lookupCodeField($entity, 'test_status_code', 'Test Status', 'TEST_STATUS', true, true, true, 12, 'not_required');

        $this->number($entity, 'merit_score', 'Merit Score', false, true, false, 13, null);
        $this->number($entity, 'merit_rank', 'Merit Rank', false, true, false, 14, null);

        $this->dateTime($entity, 'submitted_at', 'Submitted At', false, true, false, 15);
        $this->dateTime($entity, 'reviewed_at', 'Reviewed At', false, false, false, 16);
        $this->dateTime($entity, 'confirmed_at', 'Confirmed At', false, false, false, 17);

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'admission_session_id', 'Admission Session', 'select', '=', 1, '/dynamic-options/admission-sessions');
        $this->filter($entity, 'offered_program_id', 'Offered Program', 'select', '=', 2, '/dynamic-options/offered-programs');
        $this->filter($entity, 'application_status_code', 'Application Status', 'select', '=', 3, '/dynamic-options/lookups/APPLICATION_STATUS');
        $this->filter($entity, 'eligibility_status_code', 'Eligibility Status', 'select', '=', 4, '/dynamic-options/lookups/ELIGIBILITY_STATUS');

        $this->standardActions($entity, 'admission.application');
    }

    private function entity(
        string $module,
        string $entityName,
        string $entityCode,
        string $tableName,
        string $modelClass,
        string $title,
        string $subtitle
    ): DynamicEntity {
        return DynamicEntity::updateOrCreate(
            ['entity_code' => $entityCode],
            [
                'module_name' => $module,
                'entity_name' => $entityName,
                'table_name' => $tableName,
                'model_class' => $modelClass,
                'api_endpoint' => "/dynamic/crud/{$entityCode}",
                'title' => $title,
                'subtitle' => $subtitle,
                'is_tenant_scoped' => !in_array($entityCode, ['eligibility-rule-types'], true),
                'is_system' => in_array($entityCode, ['eligibility-rule-types'], true),
                'is_active' => true,
                'default_sort' => ['field' => 'id', 'direction' => 'desc'],
            ]
        );
    }

    private function text(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        bool $unique = false,
        bool $form = true
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'text',
                'data_type' => 'string',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => $form,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'is_unique' => $unique,
                'display_order' => $order,
            ]
        );
    }

    private function textarea(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'textarea',
                'data_type' => 'string',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => false,
                'display_order' => $order,
            ]
        );
    }

    private function number(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        mixed $default
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'number',
                'data_type' => 'decimal',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'display_order' => $order,
                'default_value' => $default,
            ]
        );
    }

    private function date(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'date',
                'data_type' => 'date',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'display_order' => $order,
            ]
        );
    }

    private function dateTime(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'datetime',
                'data_type' => 'datetime',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'display_order' => $order,
            ]
        );
    }

    private function switchField(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        bool $default
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'switch',
                'data_type' => 'boolean',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'display_order' => $order,
                'default_value' => $default,
            ]
        );
    }

    private function selectField(
        DynamicEntity $entity,
        string $name,
        string $label,
        string $url,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        ?string $dependsOn = null,
        ?string $dependencyParam = null,
        ?string $relationName = null,
        ?string $displayColumn = null
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'select',
                'data_type' => 'integer',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => false,
                'options_source_type' => 'api',
                'options_source_url' => $url,
                'display_order' => $order,
                'meta' => [
                    'depends_on' => $dependsOn,
                    'dependency_param' => $dependencyParam ?? $dependsOn,
                    'clear_on_parent_change' => true,
                    'relation_name' => $relationName,
                    'display_column' => $displayColumn ?? 'name',
                ],
            ]
        );
    }

    private function lookupIdField(
        DynamicEntity $entity,
        string $name,
        string $label,
        string $category,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        string $relationName,
        ?string $dependsOn = null,
        ?string $dependencyParam = null
    ): void {
        $this->selectField(
            $entity,
            $name,
            $label,
            "/dynamic-options/lookups/{$category}",
            $required,
            $table,
            $filterable,
            $order,
            $dependsOn,
            $dependencyParam,
            $relationName,
            'name'
        );
    }

    private function lookupCodeField(
        DynamicEntity $entity,
        string $name,
        string $label,
        string $category,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        mixed $default
    ): void {
        DynamicField::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => 'select',
                'data_type' => 'string',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'options_source_type' => 'api',
                'options_source_url' => "/dynamic-options/lookups/{$category}",
                'display_order' => $order,
                'default_value' => $default,
                'meta' => [
                    'value_column' => 'code',
                    'display_column' => 'name',
                ],
            ]
        );
    }

    private function filter(
        DynamicEntity $entity,
        string $name,
        string $label,
        string $control,
        string $operator,
        int $order,
        ?string $url = null,
        ?array $options = null
    ): void {
        DynamicFilter::updateOrCreate(
            ['dynamic_entity_id' => $entity->id, 'field_name' => $name],
            [
                'label' => $label,
                'control_type' => $control,
                'operator' => $operator,
                'options_source_type' => $url ? 'api' : ($options ? 'static' : null),
                'options_source_url' => $url,
                'options_static_json' => $options,
                'display_order' => $order,
                'is_active' => true,
            ]
        );
    }

    private function standardActions(DynamicEntity $entity, string $permissionPrefix): void
    {
        $actions = [
            ['name' => 'create', 'label' => 'Create', 'placement' => 'toolbar', 'permission' => $permissionPrefix . '.create', 'type' => 'modal', 'order' => 1],
            ['name' => 'edit', 'label' => 'Edit', 'placement' => 'row', 'permission' => $permissionPrefix . '.update', 'type' => 'modal', 'order' => 2],
            ['name' => 'delete', 'label' => 'Delete', 'placement' => 'row', 'permission' => $permissionPrefix . '.delete', 'type' => 'api', 'method' => 'DELETE', 'confirmation' => true, 'order' => 3],
        ];

        foreach ($actions as $action) {
            DynamicAction::updateOrCreate(
                ['dynamic_entity_id' => $entity->id, 'action_name' => $action['name']],
                [
                    'label' => $action['label'],
                    'placement' => $action['placement'],
                    'permission_name' => $action['permission'],
                    'action_type' => $action['type'],
                    'http_method' => $action['method'] ?? null,
                    'confirmation_required' => $action['confirmation'] ?? false,
                    'confirmation_title' => 'Are you sure?',
                    'confirmation_message' => 'This action cannot be undone.',
                    'is_active' => true,
                    'display_order' => $action['order'],
                ]
            );
        }
    }
}