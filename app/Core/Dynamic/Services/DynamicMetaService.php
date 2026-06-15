<?php

namespace App\Core\Dynamic\Services;

use App\Core\Dynamic\Models\DynamicEntity;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DynamicMetaService
{
    public function getMetaByEntityCode(string $entityCode): array
    {
        $entity = DynamicEntity::query()
            ->with(['fields', 'actions', 'filters'])
            ->where('entity_code', $entityCode)
            ->where('is_active', true)
            ->first();

        if (!$entity) {
            throw new ModelNotFoundException("Dynamic entity '{$entityCode}' not found.");
        }

        return $this->formatMeta($entity);
    }

    public function formatMeta(DynamicEntity $entity): array
    {
        $fields = $entity->fields;

        $columns = $fields
            ->where('is_visible_in_table', true)
            ->map(fn ($field) => [
                'title' => $field->label,
                'dataIndex' => $field->field_name,
                'key' => $field->field_name,
                'data_type' => $field->data_type,
                'control_type' => $field->control_type,
                'sortable' => $field->is_sortable,
                'width' => $field->table_width,
                'relation_name' => $field->relation_name,
                'relation_label_field' => $field->relation_label_field,
                'meta' => $field->meta,
            ])
            ->values();

        $formFields = $fields
            ->where('is_visible_in_form', true)
            ->map(fn ($field) => [
                'name' => $field->field_name,
                'label' => $field->label,
                'control' => $field->control_type,
                'data_type' => $field->data_type,
                'placeholder' => $field->placeholder,
                'help_text' => $field->help_text,
                'required' => $field->is_required,
                'readonly' => $field->is_readonly,
                'unique' => $field->is_unique,
                'default_value' => $field->default_value,
                'options_source_type' => $field->options_source_type,
                'options_source_url' => $field->options_source_url,
                'options' => $field->options_static_json,
                'validation_rules' => $field->validation_rules,
                'display_rules' => $field->display_rules,
                'display_order' => $field->display_order,
                'meta' => $field->meta,
            ])
            ->values();

        $filters = $entity->filters
            ->map(fn ($filter) => [
                'field_name' => $filter->field_name,
                'label' => $filter->label,
                'control' => $filter->control_type,
                'operator' => $filter->operator,
                'placeholder' => $filter->placeholder,
                'options_source_type' => $filter->options_source_type,
                'options_source_url' => $filter->options_source_url,
                'options' => $filter->options_static_json,
                'display_order' => $filter->display_order,
                'meta' => $filter->meta,
            ])
            ->values();

        $actions = $entity->actions
            ->where('is_active', true)
            ->map(fn ($action) => [
                'name' => $action->action_name,
                'label' => $action->label,
                'action_type' => $action->action_type,
                'placement' => $action->placement,
                'permission_name' => $action->permission_name,
                'http_method' => $action->http_method,
                'api_endpoint' => $action->api_endpoint,
                'frontend_route' => $action->frontend_route,
                'icon' => $action->icon,
                'color' => $action->color,
                'confirmation_required' => $action->confirmation_required,
                'confirmation_title' => $action->confirmation_title,
                'confirmation_message' => $action->confirmation_message,
                'display_order' => $action->display_order,
                'visible_when' => $action->visible_when,
                'meta' => $action->meta,
            ])
            ->values();

        return [
            'id' => $entity->id,
            'module_name' => $entity->module_name,
            'entity_name' => $entity->entity_name,
            'entity_code' => $entity->entity_code,
            'table_name' => $entity->table_name,
            'api_endpoint' => $entity->api_endpoint,
            'title' => $entity->title,
            'subtitle' => $entity->subtitle,
            'is_tenant_scoped' => $entity->is_tenant_scoped,
            'is_system' => $entity->is_system,
            'default_sort' => $entity->default_sort,
            'columns' => $columns,
            'fields' => $formFields,
            'filters' => $filters,
            'actions' => $actions,
            'meta' => $entity->meta,
        ];
    }
}