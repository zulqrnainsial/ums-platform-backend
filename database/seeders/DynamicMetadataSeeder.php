<?php

namespace Database\Seeders;

use App\Core\Dynamic\Models\DynamicAction;
use App\Core\Dynamic\Models\DynamicEntity;
use App\Core\Dynamic\Models\DynamicField;
use App\Core\Dynamic\Models\DynamicFilter;
use App\Core\Menu\Models\Menu;
use App\Core\Modules\Models\Module;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DynamicMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTenants();
        $this->seedModules();
        $this->seedRoles();
        $this->seedMenus();
    }

    private function seedTenants(): void
    {
        $entity = DynamicEntity::updateOrCreate(
            ['entity_code' => 'tenants'],
            [
                'module_name' => 'Tenant Management',
                'entity_name' => 'Tenant',
                'table_name' => 'tenants',
                'model_class' => Tenant::class,
                'api_endpoint' => '/dynamic/crud/tenants',
                'title' => 'Tenant Management',
                'subtitle' => 'Create and manage tenant institutions.',
                'is_tenant_scoped' => false,
                'is_system' => true,
                'is_active' => true,
                'default_sort' => ['field' => 'created_at', 'direction' => 'desc'],
            ]
        );

        $this->fields($entity, [
            ['name' => 'name', 'label' => 'Tenant Name', 'control' => 'text', 'type' => 'string', 'required' => true, 'table' => true, 'filterable' => true, 'sortable' => true, 'order' => 1],
            ['name' => 'code', 'label' => 'Code', 'control' => 'text', 'type' => 'string', 'required' => true, 'table' => true, 'filterable' => true, 'sortable' => true, 'unique' => true, 'order' => 2],
            ['name' => 'email', 'label' => 'Email', 'control' => 'email', 'type' => 'email', 'required' => false, 'table' => true, 'filterable' => true, 'order' => 3],
            ['name' => 'phone', 'label' => 'Phone', 'control' => 'text', 'type' => 'string', 'required' => false, 'table' => true, 'filterable' => true, 'order' => 4],
            ['name' => 'timezone', 'label' => 'Timezone', 'control' => 'text', 'type' => 'string', 'required' => true, 'table' => false, 'default' => 'Asia/Karachi', 'order' => 5],
            ['name' => 'locale', 'label' => 'Locale', 'control' => 'select', 'type' => 'string', 'required' => true, 'table' => false, 'default' => 'en', 'order' => 6, 'options' => [
                ['label' => 'English', 'value' => 'en'],
                ['label' => 'Urdu', 'value' => 'ur'],
                ['label' => 'Arabic', 'value' => 'ar'],
            ]],
            ['name' => 'status', 'label' => 'Status', 'control' => 'select', 'type' => 'string', 'required' => true, 'table' => true, 'filterable' => true, 'default' => 'active', 'order' => 7, 'options' => [
                ['label' => 'Active', 'value' => 'active'],
                ['label' => 'Inactive', 'value' => 'inactive'],
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Suspended', 'value' => 'suspended'],
                ['label' => 'Archived', 'value' => 'archived'],
            ]],
            ['name' => 'subscription_status', 'label' => 'Subscription', 'control' => 'select', 'type' => 'string', 'required' => true, 'table' => true, 'filterable' => true, 'default' => 'trial', 'order' => 8, 'options' => [
                ['label' => 'Trial', 'value' => 'trial'],
                ['label' => 'Active', 'value' => 'active'],
                ['label' => 'Expired', 'value' => 'expired'],
                ['label' => 'Cancelled', 'value' => 'cancelled'],
                ['label' => 'Suspended', 'value' => 'suspended'],
            ]],
            ['name' => 'theme_color', 'label' => 'Theme Color', 'control' => 'text', 'type' => 'string', 'required' => false, 'table' => false, 'default' => '#1677ff', 'order' => 9],
            ['name' => 'subscription_start_date', 'label' => 'Subscription Start', 'control' => 'date', 'type' => 'date', 'required' => false, 'table' => false, 'order' => 10],
            ['name' => 'subscription_end_date', 'label' => 'Subscription End', 'control' => 'date', 'type' => 'date', 'required' => false, 'table' => false, 'order' => 11],
        ]);

        $this->filters($entity, [
            ['name' => 'status', 'label' => 'Status', 'control' => 'select', 'operator' => '=', 'order' => 1, 'options' => [
                ['label' => 'Active', 'value' => 'active'],
                ['label' => 'Inactive', 'value' => 'inactive'],
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Suspended', 'value' => 'suspended'],
            ]],
            ['name' => 'subscription_status', 'label' => 'Subscription', 'control' => 'select', 'operator' => '=', 'order' => 2, 'options' => [
                ['label' => 'Trial', 'value' => 'trial'],
                ['label' => 'Active', 'value' => 'active'],
                ['label' => 'Expired', 'value' => 'expired'],
            ]],
        ]);

        $this->actions($entity, 'tenant');
    }

    private function seedModules(): void
    {
        $entity = DynamicEntity::updateOrCreate(
            ['entity_code' => 'modules'],
            [
                'module_name' => 'Module Management',
                'entity_name' => 'Module',
                'table_name' => 'modules',
                'model_class' => Module::class,
                'api_endpoint' => '/dynamic/crud/modules',
                'title' => 'Module Management',
                'subtitle' => 'Create and manage system modules.',
                'is_tenant_scoped' => false,
                'is_system' => true,
                'is_active' => true,
                'default_sort' => ['field' => 'display_order', 'direction' => 'asc'],
            ]
        );

        $this->fields($entity, [
            ['name' => 'name', 'label' => 'Module Name', 'control' => 'text', 'type' => 'string', 'required' => true, 'table' => true, 'filterable' => true, 'sortable' => true, 'order' => 1],
            ['name' => 'code', 'label' => 'Code', 'control' => 'text', 'type' => 'string', 'required' => true, 'table' => true, 'filterable' => true, 'unique' => true, 'order' => 2],
            ['name' => 'description', 'label' => 'Description', 'control' => 'textarea', 'type' => 'string', 'required' => false, 'table' => false, 'order' => 3],
            ['name' => 'icon', 'label' => 'Icon', 'control' => 'text', 'type' => 'string', 'required' => false, 'table' => false, 'order' => 4],
            ['name' => 'is_core', 'label' => 'Core', 'control' => 'switch', 'type' => 'boolean', 'required' => false, 'table' => true, 'order' => 5],
            ['name' => 'is_active', 'label' => 'Active', 'control' => 'switch', 'type' => 'boolean', 'required' => false, 'table' => true, 'filterable' => true, 'default' => '1', 'order' => 6],
            ['name' => 'display_order', 'label' => 'Display Order', 'control' => 'number', 'type' => 'integer', 'required' => true, 'table' => true, 'sortable' => true, 'default' => '0', 'order' => 7],
        ]);

        $this->filters($entity, [
            ['name' => 'is_active', 'label' => 'Status', 'control' => 'select', 'operator' => '=', 'order' => 1, 'options' => [
                ['label' => 'Active', 'value' => true],
                ['label' => 'Inactive', 'value' => false],
            ]],
            ['name' => 'is_core', 'label' => 'Core', 'control' => 'select', 'operator' => '=', 'order' => 2, 'options' => [
                ['label' => 'Core', 'value' => true],
                ['label' => 'Optional', 'value' => false],
            ]],
        ]);

        $this->actions($entity, 'module');
    }

    private function seedRoles(): void
    {
        $entity = DynamicEntity::updateOrCreate(
            ['entity_code' => 'roles'],
            [
                'module_name' => 'RBAC',
                'entity_name' => 'Role',
                'table_name' => 'roles',
                'model_class' => Role::class,
                'api_endpoint' => '/dynamic/crud/roles',
                'title' => 'Roles',
                'subtitle' => 'Create and manage roles.',
                'is_tenant_scoped' => false,
                'is_system' => true,
                'is_active' => true,
                'default_sort' => ['field' => 'name', 'direction' => 'asc'],
            ]
        );

        $this->fields($entity, [
            ['name' => 'name', 'label' => 'Role Name', 'control' => 'text', 'type' => 'string', 'required' => true, 'table' => true, 'filterable' => true, 'sortable' => true, 'unique' => true, 'order' => 1],
            ['name' => 'guard_name', 'label' => 'Guard', 'control' => 'hidden', 'type' => 'string', 'required' => false, 'table' => true, 'default' => 'web', 'order' => 2],
        ]);

        $this->actions($entity, 'rbac.role');
    }

    private function seedMenus(): void
    {
        $entity = DynamicEntity::updateOrCreate(
            ['entity_code' => 'menus'],
            [
                'module_name' => 'Menu Management',
                'entity_name' => 'Menu',
                'table_name' => 'menus',
                'model_class' => Menu::class,
                'api_endpoint' => '/dynamic/crud/menus',
                'title' => 'Menu Management',
                'subtitle' => 'Create and manage dynamic menus.',
                'is_tenant_scoped' => false,
                'is_system' => true,
                'is_active' => true,
                'default_sort' => ['field' => 'display_order', 'direction' => 'asc'],
            ]
        );

        $this->fields($entity, [
            ['name' => 'title', 'label' => 'Title', 'control' => 'text', 'type' => 'string', 'required' => true, 'table' => true, 'filterable' => true, 'order' => 1],
            ['name' => 'code', 'label' => 'Code', 'control' => 'text', 'type' => 'string', 'required' => true, 'table' => true, 'filterable' => true, 'unique' => true, 'order' => 2],
            ['name' => 'route', 'label' => 'Route', 'control' => 'text', 'type' => 'string', 'required' => false, 'table' => true, 'order' => 3],
            ['name' => 'icon', 'label' => 'Icon', 'control' => 'text', 'type' => 'string', 'required' => false, 'table' => false, 'order' => 4],
            ['name' => 'permission_name', 'label' => 'Permission', 'control' => 'text', 'type' => 'string', 'required' => false, 'table' => true, 'order' => 5],
            ['name' => 'is_system', 'label' => 'System', 'control' => 'switch', 'type' => 'boolean', 'required' => false, 'table' => true, 'order' => 6],
            ['name' => 'is_active', 'label' => 'Active', 'control' => 'switch', 'type' => 'boolean', 'required' => false, 'table' => true, 'filterable' => true, 'default' => '1', 'order' => 7],
            ['name' => 'display_order', 'label' => 'Order', 'control' => 'number', 'type' => 'integer', 'required' => true, 'table' => true, 'sortable' => true, 'default' => '0', 'order' => 8],
        ]);

        $this->filters($entity, [
            ['name' => 'is_active', 'label' => 'Status', 'control' => 'select', 'operator' => '=', 'order' => 1, 'options' => [
                ['label' => 'Active', 'value' => true],
                ['label' => 'Inactive', 'value' => false],
            ]],
        ]);

        $this->actions($entity, 'menu');
    }

    private function fields(DynamicEntity $entity, array $fields): void
    {
        foreach ($fields as $field) {
            DynamicField::updateOrCreate(
                [
                    'dynamic_entity_id' => $entity->id,
                    'field_name' => $field['name'],
                ],
                [
                    'label' => $field['label'],
                    'control_type' => $field['control'],
                    'data_type' => $field['type'],
                    'placeholder' => $field['placeholder'] ?? null,
                    'is_required' => $field['required'] ?? false,
                    'is_visible_in_table' => $field['table'] ?? true,
                    'is_visible_in_form' => $field['form'] ?? true,
                    'is_filterable' => $field['filterable'] ?? false,
                    'is_sortable' => $field['sortable'] ?? false,
                    'is_unique' => $field['unique'] ?? false,
                    'options_source_type' => isset($field['options']) ? 'static' : null,
                    'options_static_json' => $field['options'] ?? null,
                    'display_order' => $field['order'] ?? 0,
                    'default_value' => $field['default'] ?? null,
                ]
            );
        }
    }

    private function filters(DynamicEntity $entity, array $filters): void
    {
        foreach ($filters as $filter) {
            DynamicFilter::updateOrCreate(
                [
                    'dynamic_entity_id' => $entity->id,
                    'field_name' => $filter['name'],
                ],
                [
                    'label' => $filter['label'],
                    'control_type' => $filter['control'],
                    'operator' => $filter['operator'],
                    'placeholder' => $filter['placeholder'] ?? null,
                    'options_source_type' => isset($filter['options']) ? 'static' : null,
                    'options_static_json' => $filter['options'] ?? null,
                    'display_order' => $filter['order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }
    }

    private function actions(DynamicEntity $entity, string $permissionPrefix): void
    {
        $actions = [
            [
                'name' => 'create',
                'label' => 'Create',
                'placement' => 'toolbar',
                'permission' => $permissionPrefix . '.create',
                'type' => 'modal',
                'order' => 1,
            ],
            [
                'name' => 'edit',
                'label' => 'Edit',
                'placement' => 'row',
                'permission' => $permissionPrefix . '.update',
                'type' => 'modal',
                'order' => 2,
            ],
            [
                'name' => 'delete',
                'label' => 'Delete',
                'placement' => 'row',
                'permission' => $permissionPrefix . '.delete',
                'type' => 'api',
                'method' => 'DELETE',
                'confirmation' => true,
                'order' => 3,
            ],
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