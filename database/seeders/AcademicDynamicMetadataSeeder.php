<?php

namespace Database\Seeders;

use App\Core\Dynamic\Models\DynamicAction;
use App\Core\Dynamic\Models\DynamicEntity;
use App\Core\Dynamic\Models\DynamicField;
use App\Core\Dynamic\Models\DynamicFilter;
use App\Modules\Academic\Models\AcademicSession;
use App\Modules\Academic\Models\AcademicTerm;
use App\Modules\Academic\Models\Building;
use App\Modules\Academic\Models\Campus;
use App\Modules\Academic\Models\Department;
use App\Modules\Academic\Models\Faculty;
use App\Modules\Academic\Models\Floor;
use App\Modules\Academic\Models\Institute;
use App\Modules\Academic\Models\Program;
use App\Modules\Academic\Models\ProgramLevel;
use App\Modules\Academic\Models\Room;
use App\Modules\Academic\Models\Section;
use Illuminate\Database\Seeder;

class AcademicDynamicMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $this->campuses();
        $this->buildings();
        $this->floors();
        $this->rooms();
        $this->faculties();
        $this->institutes();
        $this->departments();
        $this->programLevels();
        $this->programs();
        $this->academicSessions();
        $this->academicTerms();
        $this->sections();
    }

    private function campuses(): void
    {
        $entity = $this->entity(
            'Academic',
            'Campus',
            'campuses',
            'campuses',
            Campus::class,
            'Campuses',
            'Create and manage campuses.'
        );

        $this->standardFields($entity, includeShortName: true);

        $this->addField($entity, 'phone', 'Phone', 'text', 'string', false, true, true, 20);
        $this->addField($entity, 'email', 'Email', 'email', 'email', false, true, true, 21);
        $this->addField($entity, 'address', 'Address', 'textarea', 'string', false, false, false, 22);
        $this->addField($entity, 'city', 'City', 'text', 'string', false, true, true, 23);
        $this->addField($entity, 'province', 'Province', 'text', 'string', false, false, true, 24);
        $this->addField($entity, 'country', 'Country', 'text', 'string', false, false, false, 25, default: 'Pakistan');

        $this->statusFilter($entity);
        $this->standardActions($entity, 'academic.campus');
    }

    private function buildings(): void
    {
        $entity = $this->entity(
            'Academic',
            'Building',
            'buildings',
            'buildings',
            Building::class,
            'Buildings',
            'Create and manage campus buildings.'
        );

        $this->selectField($entity, 'campus_id', 'Campus', '/dynamic-options/campuses', true, true, true, 1);
        $this->standardFields($entity);
        $this->addField($entity, 'total_floors', 'Total Floors', 'number', 'integer', false, true, false, 20, default: '0');
        $this->descriptionStatus($entity);

        $this->filter($entity, 'campus_id', 'Campus', 'select', '=', 1, '/dynamic-options/campuses');
        $this->statusFilter($entity, 2);
        $this->standardActions($entity, 'academic.building');
    }

    private function floors(): void
    {
        $entity = $this->entity(
            'Academic',
            'Floor',
            'floors',
            'floors',
            Floor::class,
            'Floors',
            'Create and manage building floors.'
        );

        $this->selectField($entity, 'campus_id', 'Campus', '/dynamic-options/campuses', true, false, true, 1);

$this->selectField(
    $entity,
    'building_id',
    'Building',
    '/dynamic-options/buildings',
    true,
    true,
    true,
    2,
    'campus_id',
    'campus_id'
);
        $this->standardFields($entity);
        $this->addField($entity, 'floor_number', 'Floor Number', 'number', 'integer', true, true, false, 20, default: '0');
        $this->descriptionStatus($entity);

        $this->filter($entity, 'campus_id', 'Campus', 'select', '=', 1, '/dynamic-options/campuses');
        $this->filter($entity, 'building_id', 'Building', 'select', '=', 2, '/dynamic-options/buildings');
        $this->statusFilter($entity, 3);
        $this->standardActions($entity, 'academic.floor');
    }

    private function rooms(): void
    {
        $entity = $this->entity(
            'Academic',
            'Room',
            'rooms',
            'rooms',
            Room::class,
            'Rooms',
            'Create and manage rooms, labs and halls.'
        );

        $this->selectField($entity, 'campus_id', 'Campus', '/dynamic-options/campuses', true, false, true, 1);

$this->selectField(
    $entity,
    'building_id',
    'Building',
    '/dynamic-options/buildings',
    false,
    false,
    true,
    2,
    'campus_id',
    'campus_id'
);

$this->selectField(
    $entity,
    'floor_id',
    'Floor',
    '/dynamic-options/floors',
    false,
    false,
    true,
    3,
    'building_id',
    'building_id'
);
        $this->standardFields($entity);

        $this->selectStaticField($entity, 'room_type', 'Room Type', [
            ['label' => 'Classroom', 'value' => 'classroom'],
            ['label' => 'Lab', 'value' => 'lab'],
            ['label' => 'Faculty Room', 'value' => 'faculty_room'],
            ['label' => 'Office', 'value' => 'office'],
            ['label' => 'Meeting Room', 'value' => 'meeting_room'],
            ['label' => 'Seminar Hall', 'value' => 'seminar_hall'],
            ['label' => 'Auditorium', 'value' => 'auditorium'],
            ['label' => 'Library', 'value' => 'library'],
            ['label' => 'Store', 'value' => 'store'],
            ['label' => 'Other', 'value' => 'other'],
        ], true, true, true, 20, 'classroom');

        $this->addField($entity, 'capacity', 'Capacity', 'number', 'integer', false, true, false, 21, default: '0');
        $this->addField($entity, 'is_available_for_timetable', 'Available For Timetable', 'switch', 'boolean', false, true, false, 22, default: '1');
        $this->descriptionStatus($entity);

        $this->filter($entity, 'campus_id', 'Campus', 'select', '=', 1, '/dynamic-options/campuses');
        $this->filter($entity, 'room_type', 'Room Type', 'select', '=', 2, null, [
            ['label' => 'Classroom', 'value' => 'classroom'],
            ['label' => 'Lab', 'value' => 'lab'],
            ['label' => 'Office', 'value' => 'office'],
        ]);
        $this->statusFilter($entity, 3);
        $this->standardActions($entity, 'academic.room');
    }

    private function faculties(): void
    {
        $entity = $this->entity(
            'Academic',
            'Faculty',
            'faculties',
            'faculties',
            Faculty::class,
            'Faculties',
            'Create and manage faculties.'
        );

        $this->standardFields($entity, includeShortName: true);
        $this->addField($entity, 'established_date', 'Established Date', 'date', 'date', false, false, false, 20);
        $this->descriptionStatus($entity);
        $this->statusFilter($entity);
        $this->standardActions($entity, 'academic.faculty');
    }

    private function institutes(): void
    {
        $entity = $this->entity(
            'Academic',
            'Institute',
            'institutes',
            'institutes',
            Institute::class,
            'Institutes',
            'Create and manage institutes.'
        );

        $this->selectField($entity, 'faculty_id', 'Faculty', '/dynamic-options/faculties', false, true, true, 1);
        $this->standardFields($entity, includeShortName: true);
        $this->addField($entity, 'established_date', 'Established Date', 'date', 'date', false, false, false, 20);
        $this->descriptionStatus($entity);

        $this->filter($entity, 'faculty_id', 'Faculty', 'select', '=', 1, '/dynamic-options/faculties');
        $this->statusFilter($entity, 2);
        $this->standardActions($entity, 'academic.institute');
    }

    private function departments(): void
    {
        $entity = $this->entity(
            'Academic',
            'Department',
            'departments',
            'departments',
            Department::class,
            'Departments',
            'Create and manage departments.'
        );

        $this->selectField($entity, 'faculty_id', 'Faculty', '/dynamic-options/faculties', false, false, true, 1);

$this->selectField(
    $entity,
    'institute_id',
    'Institute',
    '/dynamic-options/institutes',
    false,
    true,
    true,
    2,
    'faculty_id',
    'faculty_id'
);
        $this->standardFields($entity, includeShortName: true);
        $this->addField($entity, 'established_date', 'Established Date', 'date', 'date', false, false, false, 20);
        $this->descriptionStatus($entity);

        $this->filter($entity, 'faculty_id', 'Faculty', 'select', '=', 1, '/dynamic-options/faculties');
        $this->filter($entity, 'institute_id', 'Institute', 'select', '=', 2, '/dynamic-options/institutes');
        $this->statusFilter($entity, 3);
        $this->standardActions($entity, 'academic.department');
    }

    private function programLevels(): void
    {
        $entity = $this->entity(
            'Academic',
            'Program Level',
            'program-levels',
            'program_levels',
            ProgramLevel::class,
            'Program Levels',
            'Create and manage degree/program levels.'
        );

        $this->standardFields($entity);
        $this->addField($entity, 'display_order', 'Display Order', 'number', 'integer', true, true, false, 20, default: '0');
        $this->descriptionStatus($entity);
        $this->statusFilter($entity);
        $this->standardActions($entity, 'academic.program_level');
    }

    private function programs(): void
    {
        $entity = $this->entity(
            'Academic',
            'Program',
            'programs',
            'programs',
            Program::class,
            'Programs',
            'Create and manage academic programs.'
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

$this->selectField($entity, 'program_level_id', 'Program Level', '/dynamic-options/program-levels', false, true, true, 4);

        $this->standardFields($entity, includeShortName: true);

        $this->selectStaticField($entity, 'program_type', 'Program Type', [
            ['label' => 'Annual', 'value' => 'annual'],
            ['label' => 'Semester', 'value' => 'semester'],
            ['label' => 'Term', 'value' => 'term'],
            ['label' => 'Class Based', 'value' => 'class_based'],
            ['label' => 'Level Based', 'value' => 'level_based'],
        ], true, true, true, 20, 'semester');

        $this->addField($entity, 'duration_value', 'Duration Value', 'number', 'integer', true, false, false, 21, default: '4');

        $this->selectStaticField($entity, 'duration_unit', 'Duration Unit', [
            ['label' => 'Years', 'value' => 'years'],
            ['label' => 'Semesters', 'value' => 'semesters'],
            ['label' => 'Terms', 'value' => 'terms'],
            ['label' => 'Months', 'value' => 'months'],
        ], true, false, false, 22, 'years');

        $this->addField($entity, 'total_terms', 'Total Terms', 'number', 'integer', true, true, false, 23, default: '8');

        $this->descriptionStatus($entity);

        $this->filter($entity, 'department_id', 'Department', 'select', '=', 1, '/dynamic-options/departments');
        $this->filter($entity, 'program_level_id', 'Program Level', 'select', '=', 2, '/dynamic-options/program-levels');
        $this->statusFilter($entity, 3);
        $this->standardActions($entity, 'academic.program');
    }

    private function academicSessions(): void
    {
        $entity = $this->entity(
            'Academic',
            'Academic Session',
            'academic-sessions',
            'academic_sessions',
            AcademicSession::class,
            'Academic Sessions',
            'Create and manage academic sessions.'
        );

        $this->addField($entity, 'code', 'Code', 'text', 'string', true, true, true, 1, unique: true);
        $this->addField($entity, 'name', 'Name', 'text', 'string', true, true, true, 2);
        $this->addField($entity, 'start_date', 'Start Date', 'date', 'date', false, true, false, 3);
        $this->addField($entity, 'end_date', 'End Date', 'date', 'date', false, true, false, 4);
        $this->addField($entity, 'is_current', 'Current Session', 'switch', 'boolean', false, true, false, 5, default: '0');

        $this->selectStaticField($entity, 'status', 'Status', [
            ['label' => 'Planned', 'value' => 'planned'],
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Closed', 'value' => 'closed'],
            ['label' => 'Archived', 'value' => 'archived'],
        ], true, true, true, 6, 'planned');

        $this->addField($entity, 'description', 'Description', 'textarea', 'string', false, false, false, 7);

        $this->filter($entity, 'status', 'Status', 'select', '=', 1, null, [
            ['label' => 'Planned', 'value' => 'planned'],
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Closed', 'value' => 'closed'],
            ['label' => 'Archived', 'value' => 'archived'],
        ]);

        $this->standardActions($entity, 'academic.session');
    }

    private function academicTerms(): void
    {
        $entity = $this->entity(
            'Academic',
            'Academic Term',
            'academic-terms',
            'academic_terms',
            AcademicTerm::class,
            'Academic Terms',
            'Create and manage semesters, years, terms, classes or levels.'
        );

        $this->selectField($entity, 'program_id', 'Program', '/dynamic-options/programs', false, true, true, 1);
        $this->addField($entity, 'code', 'Code', 'text', 'string', true, true, true, 2, unique: true);
        $this->addField($entity, 'name', 'Name', 'text', 'string', true, true, true, 3);
        $this->addField($entity, 'term_number', 'Term Number', 'number', 'integer', true, true, false, 4, default: '1');

        $this->selectStaticField($entity, 'term_type', 'Term Type', [
            ['label' => 'Semester', 'value' => 'semester'],
            ['label' => 'Year', 'value' => 'year'],
            ['label' => 'Term', 'value' => 'term'],
            ['label' => 'Class', 'value' => 'class'],
            ['label' => 'Level', 'value' => 'level'],
        ], true, true, true, 5, 'semester');

        $this->descriptionStatus($entity);

        $this->filter($entity, 'program_id', 'Program', 'select', '=', 1, '/dynamic-options/programs');
        $this->statusFilter($entity, 2);
        $this->standardActions($entity, 'academic.term');
    }

    private function sections(): void
    {
        $entity = $this->entity(
            'Academic',
            'Section',
            'sections',
            'sections',
            Section::class,
            'Sections',
            'Create and manage class/program sections.'
        );

        $this->selectField($entity, 'program_id', 'Program', '/dynamic-options/programs', false, true, true, 1);

$this->selectField(
    $entity,
    'academic_term_id',
    'Academic Term',
    '/dynamic-options/academic-terms',
    false,
    true,
    true,
    2,
    'program_id',
    'program_id'
);
        $this->addField($entity, 'code', 'Code', 'text', 'string', true, true, true, 3, unique: true);
        $this->addField($entity, 'name', 'Name', 'text', 'string', true, true, true, 4);
        $this->addField($entity, 'capacity', 'Capacity', 'number', 'integer', false, true, false, 5, default: '0');

        $this->selectStaticField($entity, 'shift', 'Shift', [
            ['label' => 'Morning', 'value' => 'morning'],
            ['label' => 'Evening', 'value' => 'evening'],
            ['label' => 'Weekend', 'value' => 'weekend'],
            ['label' => 'Online', 'value' => 'online'],
            ['label' => 'Other', 'value' => 'other'],
        ], true, true, true, 6, 'morning');

        $this->descriptionStatus($entity);

        $this->filter($entity, 'program_id', 'Program', 'select', '=', 1, '/dynamic-options/programs');
        $this->filter($entity, 'academic_term_id', 'Academic Term', 'select', '=', 2, '/dynamic-options/academic-terms');
        $this->statusFilter($entity, 3);
        $this->standardActions($entity, 'academic.section');
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

    private function standardFields(DynamicEntity $entity, bool $includeShortName = false): void
    {
        $this->addField($entity, 'code', 'Code', 'text', 'string', true, true, true, 10, unique: true);
        $this->addField($entity, 'name', 'Name', 'text', 'string', true, true, true, 11);

        if ($includeShortName) {
            $this->addField($entity, 'short_name', 'Short Name', 'text', 'string', false, true, true, 12);
        }
    }

    private function descriptionStatus(DynamicEntity $entity): void
    {
        $this->addField($entity, 'description', 'Description', 'textarea', 'string', false, false, false, 90);

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
        ?string $default = null,
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