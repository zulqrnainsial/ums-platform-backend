<?php

namespace Database\Seeders;

use App\Core\Dynamic\Models\DynamicAction;
use App\Core\Dynamic\Models\DynamicEntity;
use App\Core\Dynamic\Models\DynamicField;
use App\Core\Dynamic\Models\DynamicFilter;
use App\Modules\Admission\Models\ApplicantDocument;
use App\Modules\Admission\Models\ApplicantExperience;
use App\Modules\Admission\Models\ApplicantProfileStepStatus;
use App\Modules\Admission\Models\ApplicantPublication;
use App\Modules\Admission\Models\ApplicantQualification;
use App\Modules\Admission\Models\ApplicantQualificationSubject;
use App\Modules\Admission\Models\ApplicantResearchProfile;
use App\Modules\Admission\Models\ApplicantTestResult;
use Illuminate\Database\Seeder;

class ApplicantProfileDynamicMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $this->applicantQualifications();
        $this->applicantQualificationSubjects();
        $this->applicantExperiences();
        $this->applicantResearchProfiles();
        $this->applicantPublications();
        $this->applicantDocuments();
        $this->applicantTestResults();
        $this->applicantProfileStepStatuses();
    }

    private function applicantQualifications(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant Qualification',
            'applicant-qualifications',
            'applicant_qualifications',
            ApplicantQualification::class,
            'Applicant Qualifications',
            'Manage applicant previous qualifications.'
        );

        $this->selectField($entity, 'applicant_id', 'Applicant', '/dynamic-options/applicants', true, true, true, 1, null, null, 'applicant', 'full_name');
        $this->lookupIdField($entity, 'qualification_level_id', 'Qualification Level', 'QUALIFICATION_LEVEL', true, true, true, 2, 'qualificationLevel');
        $this->lookupIdField($entity, 'education_board_id', 'Board / University', 'BOARD', false, true, true, 3, 'educationBoard');
        $this->lookupIdField($entity, 'external_institution_id', 'External Institution', 'EXTERNAL_INSTITUTION', false, false, true, 4, 'externalInstitution');
        $this->lookupIdField($entity, 'subject_group_id', 'Subject Group', 'SUBJECT_GROUP', false, true, true, 5, 'subjectGroup');

        $this->text($entity, 'degree_class_name', 'Degree / Class Name', false, true, true, 6);
        $this->text($entity, 'roll_no', 'Roll No', false, false, true, 7);
        $this->text($entity, 'registration_no', 'Registration No', false, false, true, 8);
        $this->text($entity, 'passing_year', 'Passing Year', false, true, true, 9);

        $this->lookupCodeField($entity, 'result_status_code', 'Result Status', 'RESULT_STATUS', true, true, true, 10, 'passed');

        $this->number($entity, 'total_marks', 'Total Marks', false, false, false, 11, null);
        $this->number($entity, 'obtained_marks', 'Obtained Marks', false, true, false, 12, null);
        $this->number($entity, 'percentage', 'Percentage', false, true, false, 13, null);
        $this->number($entity, 'cgpa', 'CGPA', false, true, false, 14, null);
        $this->number($entity, 'cgpa_scale', 'CGPA Scale', false, false, false, 15, null);

        $this->text($entity, 'grade', 'Grade', false, true, true, 16);

        $this->switchField($entity, 'equivalence_required', 'Equivalence Required', false, false, true, 17, false);
        $this->lookupCodeField($entity, 'equivalence_status_code', 'Equivalence Status', 'DOCUMENT_STATUS', false, false, true, 18, null);

        $this->switchField($entity, 'is_final_result', 'Final Result', false, false, true, 19, true);
        $this->switchField($entity, 'is_verified', 'Verified', false, true, true, 20, false);

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'applicant_id', 'Applicant', 'select', '=', 1, '/dynamic-options/applicants');
        $this->filter($entity, 'qualification_level_id', 'Qualification Level', 'select', '=', 2, '/dynamic-options/lookups/QUALIFICATION_LEVEL');
        $this->filter($entity, 'result_status_code', 'Result Status', 'select', '=', 3, '/dynamic-options/lookups/RESULT_STATUS');
        $this->filter($entity, 'is_verified', 'Verified', 'select', '=', 4, null, $this->yesNo());

        $this->standardActions($entity, 'admission.applicant_qualification');
    }

    private function applicantQualificationSubjects(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant Qualification Subject',
            'applicant-qualification-subjects',
            'applicant_qualification_subjects',
            ApplicantQualificationSubject::class,
            'Applicant Qualification Subjects',
            'Manage subject-wise marks for applicant qualifications.'
        );

        $this->selectField($entity, 'applicant_qualification_id', 'Applicant Qualification', '/dynamic-options/applicant-qualifications', true, true, true, 1, null, null, 'qualification', 'degree_class_name');
        $this->selectField($entity, 'subject_id', 'Subject', '/dynamic-options/subjects', false, false, true, 2, null, null, 'subject', 'name');

        $this->text($entity, 'subject_code', 'Subject Code', false, true, true, 3);
        $this->text($entity, 'subject_name', 'Subject Name', true, true, true, 4);

        $this->number($entity, 'total_marks', 'Total Marks', false, false, false, 5, null);
        $this->number($entity, 'obtained_marks', 'Obtained Marks', false, true, false, 6, null);
        $this->number($entity, 'percentage', 'Percentage', false, true, false, 7, null);

        $this->text($entity, 'grade', 'Grade', false, true, true, 8);
        $this->lookupCodeField($entity, 'result_status_code', 'Result Status', 'RESULT_STATUS', true, true, true, 9, 'passed');

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'applicant_qualification_id', 'Applicant Qualification', 'select', '=', 1, '/dynamic-options/applicant-qualifications');
        $this->filter($entity, 'result_status_code', 'Result Status', 'select', '=', 2, '/dynamic-options/lookups/RESULT_STATUS');

        $this->standardActions($entity, 'admission.applicant_qualification_subject');
    }

    private function applicantExperiences(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant Experience',
            'applicant-experiences',
            'applicant_experiences',
            ApplicantExperience::class,
            'Applicant Experiences',
            'Manage applicant work experience.'
        );

        $this->selectField($entity, 'applicant_id', 'Applicant', '/dynamic-options/applicants', true, true, true, 1, null, null, 'applicant', 'full_name');

        $this->text($entity, 'organization_name', 'Organization', true, true, true, 2);
        $this->text($entity, 'designation', 'Designation', false, true, true, 3);

        $this->lookupIdField($entity, 'employment_type_id', 'Employment Type', 'EMPLOYMENT_TYPE', false, true, true, 4, 'employmentType');
        $this->lookupIdField($entity, 'experience_area_id', 'Experience Area', 'EXPERIENCE_AREA', false, false, true, 5, 'experienceArea');

        $this->date($entity, 'from_date', 'From Date', false, true, false, 6);
        $this->date($entity, 'to_date', 'To Date', false, true, false, 7);
        $this->switchField($entity, 'currently_working', 'Currently Working', false, true, true, 8, false);
        $this->number($entity, 'total_months', 'Total Months', false, true, false, 9, 0);

        $this->lookupCodeField($entity, 'status_code', 'Status', 'EMPLOYMENT_STATUS', true, true, true, 10, 'active');

        $this->textarea($entity, 'job_description', 'Job Description', false, false, false, 80);
        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'applicant_id', 'Applicant', 'select', '=', 1, '/dynamic-options/applicants');
        $this->filter($entity, 'employment_type_id', 'Employment Type', 'select', '=', 2, '/dynamic-options/lookups/EMPLOYMENT_TYPE');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 3, '/dynamic-options/lookups/EMPLOYMENT_STATUS');

        $this->standardActions($entity, 'admission.applicant_experience');
    }

    private function applicantResearchProfiles(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant Research Profile',
            'applicant-research-profiles',
            'applicant_research_profiles',
            ApplicantResearchProfile::class,
            'Applicant Research Profiles',
            'Manage applicant research profile.'
        );

        $this->selectField($entity, 'applicant_id', 'Applicant', '/dynamic-options/applicants', true, true, true, 1, null, null, 'applicant', 'full_name');
        $this->lookupIdField($entity, 'research_area_id', 'Research Area', 'RESEARCH_AREA', false, true, true, 2, 'researchArea');

        $this->text($entity, 'proposed_research_title', 'Proposed Research Title', false, true, true, 3);
        $this->textarea($entity, 'statement_of_purpose', 'Statement of Purpose', false, false, false, 4);
        $this->textarea($entity, 'research_interests', 'Research Interests', false, false, false, 5);

        $this->text($entity, 'preferred_supervisor_name', 'Preferred Supervisor Name', false, false, true, 6);
        $this->text($entity, 'preferred_supervisor_email', 'Preferred Supervisor Email', false, false, true, 7);

        $this->lookupCodeField($entity, 'status_code', 'Status', 'PROFILE_STATUS', true, true, true, 8, 'draft');

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'applicant_id', 'Applicant', 'select', '=', 1, '/dynamic-options/applicants');
        $this->filter($entity, 'research_area_id', 'Research Area', 'select', '=', 2, '/dynamic-options/lookups/RESEARCH_AREA');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 3, '/dynamic-options/lookups/PROFILE_STATUS');

        $this->standardActions($entity, 'admission.applicant_research_profile');
    }

    private function applicantPublications(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant Publication',
            'applicant-publications',
            'applicant_publications',
            ApplicantPublication::class,
            'Applicant Publications',
            'Manage applicant publications.'
        );

        $this->selectField($entity, 'applicant_id', 'Applicant', '/dynamic-options/applicants', true, true, true, 1, null, null, 'applicant', 'full_name');
        $this->lookupIdField($entity, 'publication_type_id', 'Publication Type', 'PUBLICATION_TYPE', false, true, true, 2, 'publicationType');
        $this->lookupIdField($entity, 'indexing_type_id', 'Indexing Type', 'INDEXING_TYPE', false, true, true, 3, 'indexingType');

        $this->text($entity, 'title', 'Title', true, true, true, 4);
        $this->text($entity, 'journal_conference_name', 'Journal / Conference', false, true, true, 5);
        $this->text($entity, 'publisher', 'Publisher', false, false, true, 6);
        $this->text($entity, 'publication_year', 'Publication Year', false, true, true, 7);
        $this->text($entity, 'doi', 'DOI', false, false, true, 8);
        $this->text($entity, 'url', 'URL', false, false, true, 9);

        $this->lookupCodeField($entity, 'status_code', 'Status', 'PUBLICATION_STATUS', true, true, true, 10, 'claimed');
        $this->switchField($entity, 'is_verified', 'Verified', false, true, true, 11, false);

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'applicant_id', 'Applicant', 'select', '=', 1, '/dynamic-options/applicants');
        $this->filter($entity, 'publication_type_id', 'Publication Type', 'select', '=', 2, '/dynamic-options/lookups/PUBLICATION_TYPE');
        $this->filter($entity, 'is_verified', 'Verified', 'select', '=', 3, null, $this->yesNo());

        $this->standardActions($entity, 'admission.applicant_publication');
    }

    private function applicantDocuments(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant Document',
            'applicant-documents',
            'applicant_documents',
            ApplicantDocument::class,
            'Applicant Documents',
            'View and verify uploaded applicant documents.'
        );

        $this->selectField($entity, 'applicant_id', 'Applicant', '/dynamic-options/applicants', true, true, true, 1, null, null, 'applicant', 'full_name');
        $this->selectField($entity, 'applicant_program_application_id', 'Application', '/dynamic-options/applicant-program-applications', false, false, true, 2, 'applicant_id', 'applicant_id', 'application', 'application_no');
        $this->lookupIdField($entity, 'document_type_id', 'Document Type', 'DOCUMENT_TYPE', false, true, true, 3, 'documentType');

        $this->text($entity, 'document_title', 'Document Title', true, true, true, 4);

        $this->text($entity, 'original_file_name', 'File Name', false, true, true, 5, false, false);
        $this->text($entity, 'mime_type', 'MIME Type', false, false, false, 6, false, false);
        $this->number($entity, 'file_size', 'File Size', false, false, false, 7, null);

        $this->lookupCodeField($entity, 'verification_status_code', 'Verification Status', 'DOCUMENT_STATUS', true, true, true, 8, 'pending');

        $this->dateTime($entity, 'verified_at', 'Verified At', false, false, false, 9);
        $this->textarea($entity, 'rejection_reason', 'Rejection Reason', false, false, false, 10);
        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        /*
         | Important:
         | file_path, stored_file_name are intentionally not visible in form/table.
         | Upload will be through dedicated upload component/API.
         */

        $this->filter($entity, 'applicant_id', 'Applicant', 'select', '=', 1, '/dynamic-options/applicants');
        $this->filter($entity, 'document_type_id', 'Document Type', 'select', '=', 2, '/dynamic-options/lookups/DOCUMENT_TYPE');
        $this->filter($entity, 'verification_status_code', 'Verification Status', 'select', '=', 3, '/dynamic-options/lookups/DOCUMENT_STATUS');

        $this->standardActions($entity, 'admission.applicant_document');
    }

    private function applicantTestResults(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant Test Result',
            'applicant-test-results',
            'applicant_test_results',
            ApplicantTestResult::class,
            'Applicant Test Results',
            'Manage applicant external or tenant test results.'
        );

        $this->selectField($entity, 'applicant_id', 'Applicant', '/dynamic-options/applicants', true, true, true, 1, null, null, 'applicant', 'full_name');
        $this->selectField($entity, 'applicant_program_application_id', 'Application', '/dynamic-options/applicant-program-applications', false, false, true, 2, 'applicant_id', 'applicant_id', 'application', 'application_no');

        $this->lookupIdField($entity, 'test_type_id', 'Test Type', 'TEST_TYPE', false, true, true, 3, 'testType');

        $this->lookupCodeField($entity, 'test_source_code', 'Test Source', 'TEST_SOURCE', true, true, true, 4, 'external');

        $this->text($entity, 'test_code', 'Test Code', false, true, true, 5);
        $this->text($entity, 'test_name', 'Test Name', false, true, true, 6);
        $this->text($entity, 'roll_no', 'Roll No', false, true, true, 7);
        $this->date($entity, 'test_date', 'Test Date', false, true, false, 8);

        $this->number($entity, 'total_marks', 'Total Marks', false, false, false, 9, null);
        $this->number($entity, 'obtained_marks', 'Obtained Marks', false, true, false, 10, null);
        $this->number($entity, 'percentage', 'Percentage', false, true, false, 11, null);
        $this->number($entity, 'percentile', 'Percentile', false, false, false, 12, null);

        $this->lookupCodeField($entity, 'result_status_code', 'Result Status', 'RESULT_STATUS', true, true, true, 13, 'submitted');

        $this->switchField($entity, 'is_verified', 'Verified', false, true, true, 14, false);

        $this->selectField($entity, 'document_id', 'Document', '/dynamic-options/applicant-documents', false, false, true, 15, 'applicant_id', 'applicant_id', 'document', 'document_title');

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'applicant_id', 'Applicant', 'select', '=', 1, '/dynamic-options/applicants');
        $this->filter($entity, 'test_type_id', 'Test Type', 'select', '=', 2, '/dynamic-options/lookups/TEST_TYPE');
        $this->filter($entity, 'result_status_code', 'Result Status', 'select', '=', 3, '/dynamic-options/lookups/RESULT_STATUS');

        $this->standardActions($entity, 'admission.applicant_test_result');
    }

    private function applicantProfileStepStatuses(): void
    {
        $entity = $this->entity(
            'Admission',
            'Applicant Profile Step Status',
            'applicant-profile-step-statuses',
            'applicant_profile_step_statuses',
            ApplicantProfileStepStatus::class,
            'Applicant Profile Step Statuses',
            'Track applicant wizard step completion.'
        );

        $this->selectField($entity, 'applicant_id', 'Applicant', '/dynamic-options/applicants', true, true, true, 1, null, null, 'applicant', 'full_name');

        $this->text($entity, 'step_code', 'Step Code', true, true, true, 2);
        $this->text($entity, 'step_title', 'Step Title', true, true, true, 3);

        $this->lookupCodeField($entity, 'status_code', 'Status', 'PROFILE_STEP_STATUS', true, true, true, 4, 'pending');

        $this->number($entity, 'display_order', 'Display Order', false, true, false, 5, 0);

        $this->dateTime($entity, 'started_at', 'Started At', false, false, false, 6);
        $this->dateTime($entity, 'completed_at', 'Completed At', false, true, false, 7);
        $this->dateTime($entity, 'verified_at', 'Verified At', false, false, false, 8);

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'applicant_id', 'Applicant', 'select', '=', 1, '/dynamic-options/applicants');
        $this->filter($entity, 'status_code', 'Status', 'select', '=', 2, '/dynamic-options/lookups/PROFILE_STEP_STATUS');

        $this->standardActions($entity, 'admission.applicant_profile_step_status');
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
                'is_tenant_scoped' => true,
                'is_system' => false,
                'is_active' => true,
                'default_sort' => ['field' => 'id', 'direction' => 'desc'],
            ]
        );
    }

    private function text(DynamicEntity $entity, string $name, string $label, bool $required, bool $table, bool $filterable, int $order, bool $unique = false, bool $form = true): void
    {
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

    private function textarea(DynamicEntity $entity, string $name, string $label, bool $required, bool $table, bool $filterable, int $order): void
    {
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

    private function number(DynamicEntity $entity, string $name, string $label, bool $required, bool $table, bool $filterable, int $order, mixed $default): void
    {
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

    private function date(DynamicEntity $entity, string $name, string $label, bool $required, bool $table, bool $filterable, int $order): void
    {
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

    private function dateTime(DynamicEntity $entity, string $name, string $label, bool $required, bool $table, bool $filterable, int $order): void
    {
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

    private function switchField(DynamicEntity $entity, string $name, string $label, bool $required, bool $table, bool $filterable, int $order, bool $default): void
    {
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

    private function filter(DynamicEntity $entity, string $name, string $label, string $control, string $operator, int $order, ?string $url = null, ?array $options = null): void
    {
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
        foreach ([
            ['create', 'Create', 'toolbar', $permissionPrefix . '.create', 'modal', null, false, 1],
            ['edit', 'Edit', 'row', $permissionPrefix . '.update', 'modal', null, false, 2],
            ['delete', 'Delete', 'row', $permissionPrefix . '.delete', 'api', 'DELETE', true, 3],
        ] as [$name, $label, $placement, $permission, $type, $method, $confirm, $order]) {
            DynamicAction::updateOrCreate(
                ['dynamic_entity_id' => $entity->id, 'action_name' => $name],
                [
                    'label' => $label,
                    'placement' => $placement,
                    'permission_name' => $permission,
                    'action_type' => $type,
                    'http_method' => $method,
                    'confirmation_required' => $confirm,
                    'confirmation_title' => 'Are you sure?',
                    'confirmation_message' => 'This action cannot be undone.',
                    'is_active' => true,
                    'display_order' => $order,
                ]
            );
        }
    }

    private function yesNo(): array
    {
        return [
            ['label' => 'Yes', 'value' => true],
            ['label' => 'No', 'value' => false],
        ];
    }
}