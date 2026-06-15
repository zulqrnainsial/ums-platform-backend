<?php

namespace Database\Seeders;

use App\Core\Dynamic\Models\DynamicAction;
use App\Core\Dynamic\Models\DynamicEntity;
use App\Core\Dynamic\Models\DynamicField;
use App\Core\Dynamic\Models\DynamicFilter;
use App\Modules\Subject\Models\Curriculum;
use App\Modules\Subject\Models\CurriculumSubject;
use App\Modules\Subject\Models\Subject;
use App\Modules\Subject\Models\SubjectGroup;
use App\Modules\Subject\Models\SubjectPrerequisite;
use App\Modules\Subject\Models\SubjectType;
use Illuminate\Database\Seeder;
use App\Modules\Subject\Models\CurriculumElectiveSubject;
class SubjectDynamicMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $this->subjectTypes();
        $this->subjectGroups();
        $this->subjects();
        $this->curriculums();
        $this->curriculumSubjects();
        $this->subjectPrerequisites();
        $this->curriculumElectiveSubjects();
    }

    private function subjectTypes(): void
    {
        $entity = $this->entity(
            'Subject',
            'Subject Type',
            'subject-types',
            'subject_types',
            SubjectType::class,
            'Subject Types',
            'Create and manage subject types.'
        );

        $this->codeName($entity);
        $this->number($entity, 'display_order', 'Display Order', false, true, false, 20, 0);
        $this->descriptionStatus($entity);
        $this->statusFilter($entity);
        $this->standardActions($entity, 'subject.type');
    }

    private function subjectGroups(): void
    {
        $entity = $this->entity(
            'Subject',
            'Subject Group',
            'subject-groups',
            'subject_groups',
            SubjectGroup::class,
            'Subject Groups',
            'Create and manage subject groups.'
        );

        $this->codeName($entity);
        $this->number($entity, 'display_order', 'Display Order', false, true, false, 20, 0);
        $this->descriptionStatus($entity);
        $this->statusFilter($entity);
        $this->standardActions($entity, 'subject.group');
    }
private function curriculumElectiveSubjects(): void
{
    $entity = $this->entity(
        'Subject',
        'Curriculum Elective Subject',
        'curriculum-elective-subjects',
        'curriculum_elective_subjects',
        CurriculumElectiveSubject::class,
        'Curriculum Elective Subjects',
        'Define elective subject pools for curriculum elective groups.'
    );

    $this->selectField($entity, 'curriculum_id', 'Curriculum', '/dynamic-options/curriculums', true, true, true, 1);

    $this->selectField(
        $entity,
        'program_id',
        'Program',
        '/dynamic-options/programs',
        true,
        true,
        true,
        2
    );

    $this->selectField(
        $entity,
        'academic_term_id',
        'Academic Term',
        '/dynamic-options/academic-terms',
        false,
        true,
        true,
        3,
        'program_id',
        'program_id'
    );

    $this->addField($entity, 'elective_group_code', 'Elective Group Code', 'text', 'string', true, true, true, 4);
    $this->addField($entity, 'elective_group_name', 'Elective Group Name', 'text', 'string', false, true, true, 5);

    $this->selectField($entity, 'subject_id', 'Subject', '/dynamic-options/subjects', true, true, true, 6);

    $this->number($entity, 'display_order', 'Display Order', false, true, false, 7, 0);

    $this->addField($entity, 'remarks', 'Remarks', 'textarea', 'string', false, false, false, 90);
    $this->activeInactiveStatus($entity);

    $this->filter($entity, 'curriculum_id', 'Curriculum', 'select', '=', 1, '/dynamic-options/curriculums');
    $this->filter($entity, 'program_id', 'Program', 'select', '=', 2, '/dynamic-options/programs');
    $this->filter($entity, 'academic_term_id', 'Academic Term', 'select', '=', 3, '/dynamic-options/academic-terms');
    $this->statusFilter($entity, 4);

    $this->standardActions($entity, 'subject.curriculum_elective_subject');
}
    private function subjects(): void
    {
        $entity = $this->entity(
            'Subject',
            'Subject',
            'subjects',
            'subjects',
            Subject::class,
            'Subjects / Courses',
            'Create and manage subjects and courses.'
        );

        $this->selectField($entity, 'subject_type_id', 'Subject Type', '/dynamic-options/subject-types', false, true, true, 1);
        $this->selectField($entity, 'subject_group_id', 'Subject Group', '/dynamic-options/subject-groups', false, true, true, 2);

        $this->addField($entity, 'code', 'Code', 'text', 'string', true, true, true, 3, null, true);
        $this->addField($entity, 'name', 'Name', 'text', 'string', true, true, true, 4);
        $this->addField($entity, 'short_name', 'Short Name', 'text', 'string', false, true, true, 5);

        $this->number($entity, 'credit_hours', 'Credit Hours', false, true, false, 10, 0);
        $this->number($entity, 'theory_hours', 'Theory Hours', false, false, false, 11, 0);
        $this->number($entity, 'practical_hours', 'Practical Hours', false, false, false, 12, 0);
        $this->number($entity, 'tutorial_hours', 'Tutorial Hours', false, false, false, 13, 0);

        $this->selectStaticField($entity, 'subject_nature', 'Subject Nature', [
            ['label' => 'Theory', 'value' => 'theory'],
            ['label' => 'Practical', 'value' => 'practical'],
            ['label' => 'Theory + Practical', 'value' => 'theory_practical'],
            ['label' => 'Viva', 'value' => 'viva'],
            ['label' => 'Project', 'value' => 'project'],
            ['label' => 'Internship', 'value' => 'internship'],
            ['label' => 'Other', 'value' => 'other'],
        ], true, true, true, 14, 'theory');

        $this->selectStaticField($entity, 'grading_method', 'Grading Method', [
            ['label' => 'Marks', 'value' => 'marks'],
            ['label' => 'Grade', 'value' => 'grade'],
            ['label' => 'Pass / Fail', 'value' => 'pass_fail'],
            ['label' => 'Attendance Only', 'value' => 'attendance_only'],
        ], true, true, true, 15, 'marks');

        $this->number($entity, 'total_marks', 'Total Marks', true, true, false, 16, 100);
        $this->number($entity, 'passing_marks', 'Passing Marks', true, true, false, 17, 40);

        $this->switchField($entity, 'is_credit_subject', 'Credit Subject', false, true, false, 18, true);
        $this->switchField($entity, 'is_compulsory', 'Compulsory', false, true, false, 19, true);

        $this->descriptionStatus($entity);

        $this->filter($entity, 'subject_type_id', 'Subject Type', 'select', '=', 1, '/dynamic-options/subject-types');
        $this->filter($entity, 'subject_group_id', 'Subject Group', 'select', '=', 2, '/dynamic-options/subject-groups');
        $this->statusFilter($entity, 3);

        $this->standardActions($entity, 'subject.subject');
    }

    private function curriculums(): void
    {
        $entity = $this->entity(
            'Subject',
            'Curriculum',
            'curriculums',
            'curriculums',
            Curriculum::class,
            'Curriculums',
            'Create and manage program curriculums.'
        );

        $this->selectField($entity, 'faculty_id', 'Faculty', '/dynamic-options/faculties', false, false, true, 1);

        $this->selectField(
            $entity,
            'institute_id',
            'Institute',
            '/dynamic-options/institutes',
            false,
            false,
            true,
            2,
            'faculty_id',
            'faculty_id'
        );

        $this->selectField(
            $entity,
            'department_id',
            'Department',
            '/dynamic-options/departments',
            false,
            true,
            true,
            3,
            'institute_id',
            'institute_id'
        );

        $this->selectField(
            $entity,
            'program_id',
            'Program',
            '/dynamic-options/programs',
            true,
            true,
            true,
            4,
            'department_id',
            'department_id'
        );

        $this->addField($entity, 'code', 'Code', 'text', 'string', true, true, true, 5, null, true);
        $this->addField($entity, 'name', 'Name', 'text', 'string', true, true, true, 6);
        $this->addField($entity, 'version', 'Version', 'text', 'string', false, true, false, 7);

        $this->dateField($entity, 'effective_from', 'Effective From', false, true, false, 8);
        $this->dateField($entity, 'effective_to', 'Effective To', false, false, false, 9);

        $this->switchField($entity, 'is_current', 'Current Curriculum', false, true, true, 10, false);

        $this->selectStaticField($entity, 'status', 'Status', [
            ['label' => 'Draft', 'value' => 'draft'],
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
            ['label' => 'Archived', 'value' => 'archived'],
        ], true, true, true, 11, 'draft');

        $this->addField($entity, 'description', 'Description', 'textarea', 'string', false, false, false, 90);

        $this->filter($entity, 'program_id', 'Program', 'select', '=', 1, '/dynamic-options/programs');
        $this->standardActions($entity, 'subject.curriculum');
    }

    private function curriculumSubjects(): void
    {
        $entity = $this->entity(
            'Subject',
            'Curriculum Subject',
            'curriculum-subjects',
            'curriculum_subjects',
            CurriculumSubject::class,
            'Curriculum Subjects',
            'Attach subjects to curriculum terms.'
        );

        $this->selectField($entity, 'curriculum_id', 'Curriculum', '/dynamic-options/curriculums', true, true, true, 1);

        $this->selectField(
            $entity,
            'program_id',
            'Program',
            '/dynamic-options/programs',
            true,
            true,
            true,
            2
        );

        $this->selectField(
            $entity,
            'academic_term_id',
            'Academic Term',
            '/dynamic-options/academic-terms',
            false,
            true,
            true,
            3,
            'program_id',
            'program_id'
        );

        $this->selectField($entity, 'subject_id', 'Subject', '/dynamic-options/subjects', false, true, true, 4);

        $this->selectStaticField($entity, 'curriculum_subject_type', 'Curriculum Subject Type', [
            ['label' => 'Regular Subject', 'value' => 'regular'],
            ['label' => 'Elective Placeholder', 'value' => 'elective_placeholder'],
        ], true, true, true, 5, 'regular');

        $this->addField($entity, 'elective_group_code', 'Elective Group Code', 'text', 'string', false, true, true, 6);
        $this->addField($entity, 'elective_group_name', 'Elective Group Name', 'text', 'string', false, false, true, 7);
        $this->number($entity, 'elective_required_count', 'Elective Required Count', false, false, false, 8, null);

        $this->addField($entity, 'subject_code', 'Subject Code Override', 'text', 'string', false, true, false, 5);
        $this->addField($entity, 'subject_name', 'Subject Name Override', 'text', 'string', false, true, false, 6);
$this->selectStaticField($entity, 'subject_nature', 'Subject Nature', [
    ['label' => 'Theory', 'value' => 'theory'],
    ['label' => 'Practical', 'value' => 'practical'],
    ['label' => 'Theory + Practical', 'value' => 'theory_practical'],
    ['label' => 'Viva', 'value' => 'viva'],
    ['label' => 'Project', 'value' => 'project'],
    ['label' => 'Internship', 'value' => 'internship'],
    ['label' => 'Other', 'value' => 'other'],
], true, true, true, 7, 'theory');
        $this->number($entity, 'term_number', 'Term Number', true, true, false, 7, 1);
        $this->number($entity, 'credit_hours', 'Credit Hours', false, true, false, 8, 0);
        $this->number($entity, 'theory_hours', 'Theory Hours', false, false, false, 9, 0);
        $this->number($entity, 'practical_hours', 'Practical Hours', false, false, false, 10, 0);
        $this->number($entity, 'tutorial_hours', 'Tutorial Hours', false, false, false, 11, 0);

        $this->number($entity, 'total_marks', 'Total Marks', true, true, false, 12, 100);
        $this->number($entity, 'passing_marks', 'Passing Marks', true, false, false, 13, 40);

        $this->switchField($entity, 'is_compulsory', 'Compulsory', false, true, false, 14, true);
        $this->switchField($entity, 'is_credit_subject', 'Credit Subject', false, true, false, 15, true);

        $this->number($entity, 'display_order', 'Display Order', false, false, false, 16, 0);

        $this->addField($entity, 'remarks', 'Remarks', 'textarea', 'string', false, false, false, 90);
        $this->activeInactiveStatus($entity);

        $this->filter($entity, 'curriculum_id', 'Curriculum', 'select', '=', 1, '/dynamic-options/curriculums');
        $this->filter($entity, 'program_id', 'Program', 'select', '=', 2, '/dynamic-options/programs');
        $this->filter($entity, 'academic_term_id', 'Academic Term', 'select', '=', 3, '/dynamic-options/academic-terms');
        $this->statusFilter($entity, 4);

        $this->standardActions($entity, 'subject.curriculum_subject');
    }

    private function subjectPrerequisites(): void
    {
        $entity = $this->entity(
            'Subject',
            'Subject Prerequisite',
            'subject-prerequisites',
            'subject_prerequisites',
            SubjectPrerequisite::class,
            'Subject Prerequisites',
            'Define subject prerequisite rules.'
        );

        $this->selectField($entity, 'subject_id', 'Subject', '/dynamic-options/subjects', true, true, true, 1);
        $this->selectField($entity, 'prerequisite_subject_id', 'Prerequisite Subject', '/dynamic-options/subjects', true, true, true, 2);

        $this->selectStaticField($entity, 'requirement_type', 'Requirement Type', [
            ['label' => 'Must Pass', 'value' => 'must_pass'],
            ['label' => 'Must Study', 'value' => 'must_study'],
            ['label' => 'Recommended', 'value' => 'recommended'],
        ], true, true, true, 3, 'must_pass');

        $this->number($entity, 'minimum_marks', 'Minimum Marks', false, true, false, 4, null);
        $this->addField($entity, 'minimum_grade', 'Minimum Grade', 'text', 'string', false, true, false, 5);

        $this->addField($entity, 'remarks', 'Remarks', 'textarea', 'string', false, false, false, 90);
        $this->activeInactiveStatus($entity);

        $this->filter($entity, 'subject_id', 'Subject', 'select', '=', 1, '/dynamic-options/subjects');
        $this->statusFilter($entity, 2);

        $this->standardActions($entity, 'subject.prerequisite');
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
                'default_sort' => ['field' => 'created_at', 'direction' => 'desc'],
            ]
        );
    }

    private function codeName(DynamicEntity $entity): void
    {
        $this->addField($entity, 'code', 'Code', 'text', 'string', true, true, true, 1, null, true);
        $this->addField($entity, 'name', 'Name', 'text', 'string', true, true, true, 2);
    }

    private function descriptionStatus(DynamicEntity $entity): void
    {
        $this->addField($entity, 'description', 'Description', 'textarea', 'string', false, false, false, 90);
        $this->activeInactiveStatus($entity);
    }

    private function activeInactiveStatus(DynamicEntity $entity): void
    {
        $this->selectStaticField($entity, 'status', 'Status', [
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Inactive', 'value' => 'inactive'],
        ], true, true, true, 99, 'active');
    }

    private function addField(
        DynamicEntity $entity,
        string $name,
        string $label,
        string $control,
        string $type,
        bool $required,
        bool $table,
        bool $filterable,
        int $order,
        mixed $default = null,
        bool $unique = false
    ): void {
        DynamicField::updateOrCreate(
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => $name,
            ],
            [
                'label' => $label,
                'control_type' => $control,
                'data_type' => $type,
                'is_required' => $required,
                'is_visible_in_table' => $table,
                'is_visible_in_form' => true,
                'is_filterable' => $filterable,
                'is_sortable' => $table,
                'is_unique' => $unique,
                'display_order' => $order,
                'default_value' => $default,
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
        $this->addField($entity, $name, $label, 'number', 'decimal', $required, $table, $filterable, $order, $default);
    }

    private function dateField(
        DynamicEntity $entity,
        string $name,
        string $label,
        bool $required,
        bool $table,
        bool $filterable,
        int $order
    ): void {
        $this->addField($entity, $name, $label, 'date', 'date', $required, $table, $filterable, $order);
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
        $this->addField($entity, $name, $label, 'switch', 'boolean', $required, $table, $filterable, $order, $default);
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
        ?string $dependencyParam = null
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
                ],
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
        ?string $default = null
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
                'is_sortable' => true,
                'options_source_type' => 'static',
                'options_static_json' => $options,
                'display_order' => $order,
                'default_value' => $default,
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