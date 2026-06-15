<?php

namespace Database\Seeders;

use App\Core\Dynamic\Models\DynamicAction;
use App\Core\Dynamic\Models\DynamicEntity;
use App\Core\Dynamic\Models\DynamicField;
use App\Core\Dynamic\Models\DynamicFilter;
use App\Modules\Student\Models\Guardian;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentBatch;
use App\Modules\Student\Models\StudentDocument;
use App\Modules\Student\Models\StudentGuardian;
use App\Modules\Student\Models\StudentPreviousEducation;
use App\Modules\Student\Models\StudentStatusHistory;
use Illuminate\Database\Seeder;

class StudentDynamicMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $this->studentBatches();
        $this->students();
        $this->guardians();
        $this->studentGuardians();
        $this->studentPreviousEducations();
        $this->studentDocuments();
        $this->studentStatusHistories();
    }

    private function studentBatches(): void
    {
        $entity = $this->entity(
            'Students',
            'Student Batch',
            'student-batches',
            'student_batches',
            StudentBatch::class,
            'Student Batches',
            'Create and manage student academic batches.'
        );

        $this->selectField($entity, 'academic_session_id', 'Academic Session', '/dynamic-options/academic-sessions', false, true, true, 1, null, null, 'academicSession', 'name');
        $this->selectField($entity, 'faculty_id', 'Faculty', '/dynamic-options/faculties', false, true, true, 2, null, null, 'faculty', 'name');
        $this->selectField($entity, 'institute_id', 'Institute', '/dynamic-options/institutes', false, true, true, 3, 'faculty_id', 'faculty_id', 'institute', 'name');
        $this->selectField($entity, 'department_id', 'Department', '/dynamic-options/departments', false, true, true, 4, 'institute_id', 'institute_id', 'department', 'name');
        $this->selectField($entity, 'program_id', 'Program', '/dynamic-options/programs', false, true, true, 5, 'department_id', 'department_id', 'program', 'name');
        $this->selectField($entity, 'curriculum_id', 'Curriculum', '/dynamic-options/curriculums', false, true, true, 6, 'program_id', 'program_id', 'curriculum', 'name');

        $this->text($entity, 'code', 'Code', true, true, true, 7, true);
        $this->text($entity, 'name', 'Name', true, true, true, 8);

        $this->date($entity, 'start_date', 'Start Date', false, true, false, 9);
        $this->date($entity, 'expected_end_date', 'Expected End Date', false, true, false, 10);

        $this->number($entity, 'capacity', 'Capacity', false, true, false, 11, null);

        $this->selectStaticField($entity, 'shift', 'Shift', [
            ['label' => 'Morning', 'value' => 'morning'],
            ['label' => 'Evening', 'value' => 'evening'],
            ['label' => 'Weekend', 'value' => 'weekend'],
            ['label' => 'Online', 'value' => 'online'],
            ['label' => 'Other', 'value' => 'other'],
        ], false, true, true, 12, null);

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->selectStaticField($entity, 'status', 'Status', [
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
            ['label' => 'Completed', 'value' => 'completed'],
            ['label' => 'Archived', 'value' => 'archived'],
        ], true, true, true, 99, 'active');

        $this->filter($entity, 'academic_session_id', 'Academic Session', 'select', '=', 1, '/dynamic-options/academic-sessions');
        $this->filter($entity, 'program_id', 'Program', 'select', '=', 2, '/dynamic-options/programs');
        $this->filter($entity, 'status', 'Status', 'select', '=', 3, null, [
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
            ['label' => 'Completed', 'value' => 'completed'],
            ['label' => 'Archived', 'value' => 'archived'],
        ]);

        $this->standardActions($entity, 'student.batch');
    }

    private function students(): void
    {
        $entity = $this->entity(
            'Students',
            'Student',
            'students',
            'students',
            Student::class,
            'Students',
            'Create and manage student profiles.'
        );

        $this->text($entity, 'student_no', 'Student No', false, true, true, 1, true);
        $this->text($entity, 'admission_no', 'Admission No', false, true, true, 2, true);

        $this->text($entity, 'first_name', 'First Name', true, true, true, 3);
        $this->text($entity, 'last_name', 'Last Name', false, true, true, 4);
        $this->text($entity, 'full_name', 'Full Name', false, true, true, 5, false, false);

        $this->text($entity, 'father_name', 'Father Name', false, true, true, 6);
        $this->text($entity, 'mother_name', 'Mother Name', false, false, true, 7);

        $this->text($entity, 'cnic_bform', 'CNIC / B-Form', false, true, true, 8);
        $this->text($entity, 'passport_no', 'Passport No', false, false, true, 9);

        $this->date($entity, 'date_of_birth', 'Date of Birth', false, true, false, 10);

        $this->selectStaticField($entity, 'gender', 'Gender', [
            ['label' => 'Male', 'value' => 'male'],
            ['label' => 'Female', 'value' => 'female'],
            ['label' => 'Other', 'value' => 'other'],
        ], false, true, true, 11, null);

        $this->lookupField($entity, 'blood_group_id', 'Blood Group', 'BLOOD_GROUP', false, false, true, 12, 'bloodGroup');
        $this->lookupField($entity, 'religion_id', 'Religion', 'RELIGION', false, false, true, 13, 'religion');
        $this->lookupField($entity, 'nationality_id', 'Nationality', 'NATIONALITY', false, false, true, 14, 'nationality');

        $this->text($entity, 'phone', 'Phone', false, true, true, 15);
        $this->text($entity, 'alternate_phone', 'Alternate Phone', false, false, true, 16);
        $this->text($entity, 'email', 'Email', false, true, true, 17);

        $this->textarea($entity, 'current_address', 'Current Address', false, false, false, 18);
        $this->textarea($entity, 'permanent_address', 'Permanent Address', false, false, false, 19);

        $this->lookupField($entity, 'country_id', 'Country', 'COUNTRY', false, false, true, 20, 'country');
        $this->lookupField($entity, 'province_id', 'Province', 'PROVINCE', false, false, true, 21, 'province', 'country_id', 'parent_id');
        $this->lookupField($entity, 'city_id', 'City', 'CITY', false, true, true, 22, 'city', 'province_id', 'parent_id');

        $this->text($entity, 'photo_path', 'Photo Path', false, false, false, 23);

        $this->date($entity, 'admission_date', 'Admission Date', false, true, false, 24);

        $this->selectStaticField($entity, 'student_status', 'Student Status', [
            ['label' => 'Applicant', 'value' => 'applicant'],
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
            ['label' => 'Graduated', 'value' => 'graduated'],
            ['label' => 'Left', 'value' => 'left'],
            ['label' => 'Struck Off', 'value' => 'struck_off'],
            ['label' => 'Suspended', 'value' => 'suspended'],
            ['label' => 'Transferred', 'value' => 'transferred'],
        ], true, true, true, 25, 'active');

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->filter($entity, 'student_status', 'Student Status', 'select', '=', 1, null, [
            ['label' => 'Applicant', 'value' => 'applicant'],
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
            ['label' => 'Graduated', 'value' => 'graduated'],
            ['label' => 'Left', 'value' => 'left'],
            ['label' => 'Struck Off', 'value' => 'struck_off'],
            ['label' => 'Suspended', 'value' => 'suspended'],
            ['label' => 'Transferred', 'value' => 'transferred'],
        ]);

        $this->filter($entity, 'gender', 'Gender', 'select', '=', 2, null, [
            ['label' => 'Male', 'value' => 'male'],
            ['label' => 'Female', 'value' => 'female'],
            ['label' => 'Other', 'value' => 'other'],
        ]);

        $this->filter($entity, 'city_id', 'City', 'select', '=', 3, '/dynamic-options/lookups/CITY');

        $this->standardActions($entity, 'student.student');
    }

    private function guardians(): void
    {
        $entity = $this->entity(
            'Students',
            'Guardian',
            'guardians',
            'guardians',
            Guardian::class,
            'Guardians',
            'Create and manage student guardians.'
        );

        $this->text($entity, 'name', 'Name', true, true, true, 1);
        $this->text($entity, 'cnic', 'CNIC', false, true, true, 2, true);

        $this->text($entity, 'phone', 'Phone', false, true, true, 3);
        $this->text($entity, 'alternate_phone', 'Alternate Phone', false, false, true, 4);
        $this->text($entity, 'email', 'Email', false, true, true, 5);

        $this->text($entity, 'occupation', 'Occupation', false, true, true, 6);
        $this->number($entity, 'monthly_income', 'Monthly Income', false, false, false, 7, null);

        $this->textarea($entity, 'address', 'Address', false, false, false, 8);

        $this->lookupField($entity, 'country_id', 'Country', 'COUNTRY', false, false, true, 9, 'country');
        $this->lookupField($entity, 'province_id', 'Province', 'PROVINCE', false, false, true, 10, 'province', 'country_id', 'parent_id');
        $this->lookupField($entity, 'city_id', 'City', 'CITY', false, true, true, 11, 'city', 'province_id', 'parent_id');

        $this->activeInactiveStatus($entity, 99);
        $this->statusFilter($entity);

        $this->standardActions($entity, 'student.guardian');
    }

    private function studentGuardians(): void
    {
        $entity = $this->entity(
            'Students',
            'Student Guardian',
            'student-guardians',
            'student_guardians',
            StudentGuardian::class,
            'Student Guardians',
            'Attach guardians to students.'
        );

        $this->selectField($entity, 'student_id', 'Student', '/dynamic-options/students', true, true, true, 1, null, null, 'student', 'full_name');
        $this->selectField($entity, 'guardian_id', 'Guardian', '/dynamic-options/guardians', true, true, true, 2, null, null, 'guardian', 'name');

        $this->lookupField($entity, 'relationship_type_id', 'Relationship', 'RELATIONSHIP_TYPE', false, true, true, 3, 'relationshipType');

        $this->switchField($entity, 'is_primary', 'Primary Guardian', false, true, true, 4, false);
        $this->switchField($entity, 'is_emergency_contact', 'Emergency Contact', false, true, true, 5, false);
        $this->switchField($entity, 'can_pick_student', 'Can Pick Student', false, true, true, 6, false);

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->activeInactiveStatus($entity, 99);

        $this->filter($entity, 'student_id', 'Student', 'select', '=', 1, '/dynamic-options/students');
        $this->filter($entity, 'guardian_id', 'Guardian', 'select', '=', 2, '/dynamic-options/guardians');
        $this->statusFilter($entity, 3);

        $this->standardActions($entity, 'student.student_guardian');
    }

    private function studentPreviousEducations(): void
    {
        $entity = $this->entity(
            'Students',
            'Student Previous Education',
            'student-previous-educations',
            'student_previous_educations',
            StudentPreviousEducation::class,
            'Student Previous Educations',
            'Manage student previous academic records.'
        );

        $this->selectField($entity, 'student_id', 'Student', '/dynamic-options/students', true, true, true, 1, null, null, 'student', 'full_name');

        $this->lookupField($entity, 'qualification_level_id', 'Qualification Level', 'QUALIFICATION_LEVEL', false, true, true, 2, 'qualificationLevel');
        $this->lookupField($entity, 'education_board_id', 'Board / University', 'BOARD', false, true, true, 3, 'educationBoard');
        $this->lookupField($entity, 'external_institution_id', 'External Institution', 'EXTERNAL_INSTITUTION', false, true, true, 4, 'externalInstitution');

        $this->text($entity, 'degree_class_name', 'Degree / Class Name', false, true, true, 5);
        $this->text($entity, 'roll_no', 'Roll No', false, true, true, 6);
        $this->text($entity, 'registration_no', 'Registration No', false, false, true, 7);
        $this->text($entity, 'passing_year', 'Passing Year', false, true, true, 8);

        $this->number($entity, 'total_marks', 'Total Marks', false, true, false, 9, null);
        $this->number($entity, 'obtained_marks', 'Obtained Marks', false, true, false, 10, null);
        $this->number($entity, 'percentage', 'Percentage', false, true, false, 11, null);

        $this->text($entity, 'grade', 'Grade', false, true, true, 12);
        $this->text($entity, 'cgpa', 'CGPA', false, false, false, 13);

        $this->text($entity, 'document_path', 'Document Path', false, false, false, 14);

        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->activeInactiveStatus($entity, 99);

        $this->filter($entity, 'student_id', 'Student', 'select', '=', 1, '/dynamic-options/students');
        $this->filter($entity, 'qualification_level_id', 'Qualification Level', 'select', '=', 2, '/dynamic-options/lookups/QUALIFICATION_LEVEL');
        $this->filter($entity, 'education_board_id', 'Board / University', 'select', '=', 3, '/dynamic-options/lookups/BOARD');
        $this->statusFilter($entity, 4);

        $this->standardActions($entity, 'student.previous_education');
    }

    private function studentDocuments(): void
    {
        $entity = $this->entity(
            'Students',
            'Student Document',
            'student-documents',
            'student_documents',
            StudentDocument::class,
            'Student Documents',
            'Manage student documents and verification.'
        );

        $this->selectField($entity, 'student_id', 'Student', '/dynamic-options/students', true, true, true, 1, null, null, 'student', 'full_name');
        $this->lookupField($entity, 'document_type_id', 'Document Type', 'DOCUMENT_TYPE', false, true, true, 2, 'documentType');

        $this->text($entity, 'document_title', 'Document Title', true, true, true, 3);

        $this->text($entity, 'file_path', 'File Path', false, false, false, 4);
        $this->text($entity, 'file_name', 'File Name', false, true, true, 5);
        $this->text($entity, 'mime_type', 'MIME Type', false, false, false, 6);
        $this->number($entity, 'file_size', 'File Size', false, false, false, 7, null);

        $this->selectStaticField($entity, 'verification_status', 'Verification Status', [
            ['label' => 'Pending', 'value' => 'pending'],
            ['label' => 'Verified', 'value' => 'verified'],
            ['label' => 'Rejected', 'value' => 'rejected'],
        ], true, true, true, 8, 'pending');

        $this->dateTime($entity, 'verified_at', 'Verified At', false, false, false, 9);
        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 90);

        $this->activeInactiveStatus($entity, 99);

        $this->filter($entity, 'student_id', 'Student', 'select', '=', 1, '/dynamic-options/students');
        $this->filter($entity, 'document_type_id', 'Document Type', 'select', '=', 2, '/dynamic-options/lookups/DOCUMENT_TYPE');
        $this->filter($entity, 'verification_status', 'Verification Status', 'select', '=', 3, null, [
            ['label' => 'Pending', 'value' => 'pending'],
            ['label' => 'Verified', 'value' => 'verified'],
            ['label' => 'Rejected', 'value' => 'rejected'],
        ]);

        $this->standardActions($entity, 'student.document');
    }

    private function studentStatusHistories(): void
    {
        $entity = $this->entity(
            'Students',
            'Student Status History',
            'student-status-histories',
            'student_status_histories',
            StudentStatusHistory::class,
            'Student Status Histories',
            'View and manage student status changes.'
        );

        $this->selectField($entity, 'student_id', 'Student', '/dynamic-options/students', true, true, true, 1, null, null, 'student', 'full_name');

        $this->selectStaticField($entity, 'from_status', 'From Status', [
            ['label' => 'Applicant', 'value' => 'applicant'],
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
            ['label' => 'Graduated', 'value' => 'graduated'],
            ['label' => 'Left', 'value' => 'left'],
            ['label' => 'Struck Off', 'value' => 'struck_off'],
            ['label' => 'Suspended', 'value' => 'suspended'],
            ['label' => 'Transferred', 'value' => 'transferred'],
        ], false, true, true, 2, null);

        $this->selectStaticField($entity, 'to_status', 'To Status', [
            ['label' => 'Applicant', 'value' => 'applicant'],
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
            ['label' => 'Graduated', 'value' => 'graduated'],
            ['label' => 'Left', 'value' => 'left'],
            ['label' => 'Struck Off', 'value' => 'struck_off'],
            ['label' => 'Suspended', 'value' => 'suspended'],
            ['label' => 'Transferred', 'value' => 'transferred'],
        ], true, true, true, 3, 'active');

        $this->date($entity, 'effective_date', 'Effective Date', false, true, false, 4);
        $this->text($entity, 'reason', 'Reason', false, true, true, 5);
        $this->textarea($entity, 'remarks', 'Remarks', false, false, false, 6);

        $this->filter($entity, 'student_id', 'Student', 'select', '=', 1, '/dynamic-options/students');
        $this->filter($entity, 'to_status', 'To Status', 'select', '=', 2, null, [
            ['label' => 'Applicant', 'value' => 'applicant'],
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
            ['label' => 'Graduated', 'value' => 'graduated'],
            ['label' => 'Left', 'value' => 'left'],
            ['label' => 'Struck Off', 'value' => 'struck_off'],
            ['label' => 'Suspended', 'value' => 'suspended'],
            ['label' => 'Transferred', 'value' => 'transferred'],
        ]);

        $this->standardActions($entity, 'student.status_history');
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
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
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
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
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
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
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
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
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
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
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
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
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

    private function selectStaticField(
        DynamicEntity $entity,
        string $name,
        string $label,
        array $options,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        mixed $default
    ): void {
        DynamicField::updateOrCreate(
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
            [
                'label' => $label,
                'control_type' => 'select',
                'data_type' => 'string',
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'options_source_type' => 'static',
                'options_static_json' => $options,
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
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
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

    private function lookupField(
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
        $url = "/dynamic-options/lookups/{$category}";

        $this->selectField(
            $entity,
            $name,
            $label,
            $url,
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

    private function activeInactiveStatus(DynamicEntity $entity, int $order = 99): void
    {
        $this->selectStaticField($entity, 'status', 'Status', [
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
        ], true, true, true, $order, 'active');
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
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
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

    private function statusFilter(DynamicEntity $entity, int $order = 1): void
    {
        $this->filter($entity, 'status', 'Status', 'select', '=', $order, null, [
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
        ]);
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
                [
                    'dynamic_entity_id' => $entity->id,
                    'action_name' => $action['name'],
                ],
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