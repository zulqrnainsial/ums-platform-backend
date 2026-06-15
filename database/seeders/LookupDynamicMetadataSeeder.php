<?php

namespace Database\Seeders;

use App\Core\Dynamic\Models\DynamicAction;
use App\Core\Dynamic\Models\DynamicEntity;
use App\Core\Dynamic\Models\DynamicField;
use App\Core\Dynamic\Models\DynamicFilter;
use App\Modules\Lookup\Models\LookupCategory;
use App\Modules\Lookup\Models\LookupValue;
use Illuminate\Database\Seeder;

class LookupDynamicMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $this->lookupCategories();
        $this->lookupValues();
    }

    private function lookupCategories(): void
    {
        $entity = $this->entity(
            'Settings',
            'Lookup Category',
            'lookup-categories',
            'lookup_categories',
            LookupCategory::class,
            'Lookup Categories',
            'Create and manage lookup categories.'
        );

        $this->text($entity, 'code', 'Code', true, true, true, 1, true);
        $this->text($entity, 'name', 'Name', true, true, true, 2);
        $this->textarea($entity, 'description', 'Description', false, false, false, 3);

        $this->switchField($entity, 'is_system', 'System Category', false, true, true, 4, false);
        $this->switchField($entity, 'is_tenant_editable', 'Tenant Editable', false, true, true, 5, true);

        $this->number($entity, 'display_order', 'Display Order', false, true, false, 6, 0);

        $this->status($entity, 99);
        $this->statusFilter($entity);

        $this->standardActions($entity, 'lookup.category');
    }

    private function lookupValues(): void
    {
        $entity = $this->entity(
            'Settings',
            'Lookup Value',
            'lookup-values',
            'lookup_values',
            LookupValue::class,
            'Lookup Values',
            'Create and manage lookup values.'
        );

        $this->selectField(
            $entity,
            'lookup_category_id',
            'Lookup Category',
            '/dynamic-options/lookup-categories',
            true,
            true,
            true,
            1,
            null,
            null,
            'category',
            'name'
        );

        $this->selectField(
            $entity,
            'parent_id',
            'Parent Value',
            '/dynamic-options/lookup-values',
            false,
            true,
            true,
            2,
            null,
            null,
            'parent',
            'name'
        );

        $this->text($entity, 'code', 'Code', true, true, true, 3, true);
        $this->text($entity, 'name', 'Name', true, true, true, 4);
        $this->text($entity, 'short_name', 'Short Name', false, true, true, 5);

        $this->textarea($entity, 'extra_json', 'Extra JSON', false, false, false, 6);

        $this->number($entity, 'display_order', 'Display Order', false, true, false, 7, 0);

        $this->status($entity, 99);
        $this->filter($entity, 'lookup_category_id', 'Lookup Category', 'select', '=', 1, '/dynamic-options/lookup-categories');
        $this->statusFilter($entity, 2);

        $this->standardActions($entity, 'lookup.value');
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
    $isGlobalLookupEntity = in_array($entityCode, [
        'lookup-categories',
        'lookup-values',
    ], true);

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

            // Lookup categories and seeded lookup values must be visible globally.
            'is_tenant_scoped' => !$isGlobalLookupEntity,

            'is_system' => $entityCode === 'lookup-categories',
            'is_active' => true,
            'default_sort' => ['field' => 'display_order', 'direction' => 'asc'],
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
        bool $unique = false
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
                'is_visible_in_form' => true,
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
                'data_type' => 'integer',
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

    private function status(DynamicEntity $entity, int $order): void
    {
        DynamicField::updateOrCreate(
            [
                'dynamic_entity_id' => $entity->id,
                'field_name' => 'status',
            ],
            [
                'label' => 'Status',
                'control_type' => 'select',
                'data_type' => 'string',
                'is_required' => true,
                'is_visible_in_table' => true,
                'is_visible_in_form' => true,
                'is_filterable' => true,
                'is_sortable' => true,
                'options_source_type' => 'static',
                'options_static_json' => [
                    ['label' => 'Active', 'value' => 'active'],
                    ['label' => 'Inactive', 'value' => 'inactive'],
                ],
                'display_order' => $order,
                'default_value' => 'active',
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