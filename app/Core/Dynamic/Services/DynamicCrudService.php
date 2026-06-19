<?php

namespace App\Core\Dynamic\Services;

use App\Core\Dynamic\Models\DynamicEntity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Modules\Subject\Models\Subject;

class DynamicCrudService
{
    public function paginate(string $entityCode, array $filters = []): LengthAwarePaginator
    {
        $entity = $this->findEntity($entityCode);
        $modelClass = $this->resolveModelClass($entity);

        $query = $modelClass::query();
        $this->applyDisplayRelations($query, $entity, $modelClass);
        $this->applyEntitySpecificScopes($query, $entity);
        if ($entity->is_tenant_scoped && auth()->check() && auth()->user()->tenant_id) {
            if (Schema::hasColumn($entity->table_name, 'tenant_id')) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        }

        $this->applySearch($query, $entity, $filters);
        $this->applyFilters($query, $entity, $filters);
        $this->applySorting($query, $entity, $filters);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($record) use ($entity) {
            return $this->appendDisplayValues($record, $entity);
        });

        return $paginator;
    }
    private function guardEntityMutation(DynamicEntity $entity): void
{
    $user = auth()->user();

    if (!$user) {
        abort(401, 'Unauthenticated.');
    }

    if ($entity->entity_code === 'modules' && !$user->isSuperAdmin()) {
        abort(403, 'Only Super Admin can manage modules.');
    }
}
private function applyEntitySpecificScopes($query, DynamicEntity $entity): void
{
    $user = auth()->user();

    if (!$user) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Modules entity special rule
    |--------------------------------------------------------------------------
    | Super Admin sees all modules.
    | Tenant users see only modules enabled for their own tenant.
    |--------------------------------------------------------------------------
    */
    if ($entity->entity_code === 'modules' && !$user->isSuperAdmin()) {
        if (!$user->tenant_id) {
            $query->whereRaw('1 = 0');
            return;
        }

        $enabledModuleIds = $user->tenant?->modules()
            ->wherePivot('is_enabled', true)
            ->pluck('modules.id')
            ->toArray() ?? [];

        if (empty($enabledModuleIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('id', $enabledModuleIds);
    }
}
    public function find(string $entityCode, int|string $id): Model
    {
        $entity = $this->findEntity($entityCode);
        $modelClass = $this->resolveModelClass($entity);

        $query = $modelClass::query();

        if ($entity->is_tenant_scoped && auth()->check() && auth()->user()->tenant_id) {
            if (Schema::hasColumn($entity->table_name, 'tenant_id')) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        }

        return $query->findOrFail($id);
    }

    public function create(string $entityCode, array $data): Model
    {
        $entity = $this->findEntity($entityCode);
        $this->guardEntityMutation($entity);
        $modelClass = $this->resolveModelClass($entity);

        $data = $this->sanitizeData($entity, $data);
        $data = $this->applyCurriculumSubjectDefaults($entity, $data);
        $this->validateData($entity, $data);
        if ($entity->entity_code === 'tenants') {
            return app(\App\Core\Tenant\Services\TenantService::class)->create($data);
        }
        return DB::transaction(function () use ($modelClass, $entity, $data) {
            if ($entity->is_tenant_scoped && auth()->check() && auth()->user()->tenant_id) {
                if (Schema::hasColumn($entity->table_name, 'tenant_id')) {
                    $data['tenant_id'] = auth()->user()->tenant_id;
                }
            }

            if (auth()->check() && Schema::hasColumn($entity->table_name, 'created_by')) {
                $data['created_by'] = auth()->id();
            }

            if (auth()->check() && Schema::hasColumn($entity->table_name, 'updated_by')) {
                $data['updated_by'] = auth()->id();
            }

            return $modelClass::create($data);
        });
    }

    public function update(string $entityCode, int|string $id, array $data): Model
    {
        $entity = $this->findEntity($entityCode);
        $this->guardEntityMutation($entity);
        $record = $this->find($entityCode, $id);

        $data = $this->sanitizeData($entity, $data);
        $data = $this->applyCurriculumSubjectDefaults($entity, $data);
        $this->validateData($entity, $data, $record->getKey());

        return DB::transaction(function () use ($record, $entity, $data) {
            if (auth()->check() && Schema::hasColumn($entity->table_name, 'updated_by')) {
                $data['updated_by'] = auth()->id();
            }

            $record->update($data);

            return $record->refresh();
        });
    }

    public function delete(string $entityCode, int|string $id): bool
    {
        $entity = $this->findEntity($entityCode);

        $this->guardEntityMutation($entity);

        $record = $this->find($entityCode, $id);

        return DB::transaction(function () use ($record) {
            return $record->delete();
        });
    }

    private function findEntity(string $entityCode): DynamicEntity
    {
        $entity = DynamicEntity::query()
            ->with(['fields', 'filters', 'actions'])
            ->where('entity_code', $entityCode)
            ->where('is_active', true)
            ->first();

        if (!$entity) {
            throw new ModelNotFoundException("Entity '{$entityCode}' not found.");
        }

        return $entity;
    }

    private function resolveModelClass(DynamicEntity $entity): string
    {
        if (!$entity->model_class || !class_exists($entity->model_class)) {
            throw ValidationException::withMessages([
                'entity' => ["Model class not found for entity '{$entity->entity_code}'."],
            ]);
        }

        return $entity->model_class;
    }

    private function sanitizeData(DynamicEntity $entity, array $data): array
    {
        $allowedFields = $entity->fields
            ->where('is_visible_in_form', true)
            ->where('is_readonly', false)
            ->pluck('field_name')
            ->toArray();

        return collect($data)
            ->only($allowedFields)
            ->toArray();
    }

    private function validateData(DynamicEntity $entity, array $data, int|string|null $ignoreId = null): void
    {
        $rules = [];

        foreach ($entity->fields as $field) {
            if (!$field->is_visible_in_form || $field->is_readonly) {
                continue;
            }

            $fieldRules = [];

            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($field->data_type === 'integer') {
                $fieldRules[] = 'integer';
            }

            if ($field->data_type === 'decimal') {
                $fieldRules[] = 'numeric';
            }

            if ($field->data_type === 'date') {
                $fieldRules[] = 'date';
            }

            if ($field->data_type === 'email') {
                $fieldRules[] = 'email';
            }

            if ($field->is_unique) {
                $uniqueRule = 'unique:' . $entity->table_name . ',' . $field->field_name;

                if ($ignoreId) {
                    $uniqueRule .= ',' . $ignoreId;
                }

                $fieldRules[] = $uniqueRule;
            }

            if (!empty($field->validation_rules)) {
                foreach ($field->validation_rules as $rule) {
                    $fieldRules[] = $rule;
                }
            }

            $rules[$field->field_name] = $fieldRules;
        }

        validator($data, $rules)->validate();
    }

    private function applySearch($query, DynamicEntity $entity, array $filters): void
    {
        if (empty($filters['search'])) {
            return;
        }

        $searchableFields = $entity->fields
            ->where('is_filterable', true)
            ->whereIn('data_type', ['string', 'email'])
            ->pluck('field_name')
            ->toArray();

        if (empty($searchableFields)) {
            return;
        }

        $search = $filters['search'];

        $query->where(function ($q) use ($searchableFields, $search) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    private function applyFilters($query, DynamicEntity $entity, array $filters): void
    {
        foreach ($entity->filters as $filter) {
            $fieldName = $filter->field_name;

            if (!array_key_exists($fieldName, $filters)) {
                continue;
            }

            $value = $filters[$fieldName];

            if ($value === null || $value === '') {
                continue;
            }

            match ($filter->operator) {
                'like' => $query->where($fieldName, 'like', "%{$value}%"),
                '>=' => $query->where($fieldName, '>=', $value),
                '<=' => $query->where($fieldName, '<=', $value),
                '>' => $query->where($fieldName, '>', $value),
                '<' => $query->where($fieldName, '<', $value),
                default => $query->where($fieldName, $value),
            };
        }
    }

    private function applySorting($query, DynamicEntity $entity, array $filters): void
    {
        $sortField = $filters['sort_field'] ?? null;
        $sortOrder = $filters['sort_order'] ?? null;

        $sortableFields = $entity->fields
            ->where('is_sortable', true)
            ->pluck('field_name')
            ->toArray();

        if ($sortField && in_array($sortField, $sortableFields)) {
            $query->orderBy($sortField, $sortOrder === 'descend' ? 'desc' : 'asc');
            return;
        }

        $defaultSort = $entity->default_sort;

        if ($defaultSort && isset($defaultSort['field'])) {
            $query->orderBy(
                $defaultSort['field'],
                $defaultSort['direction'] ?? 'asc'
            );
            return;
        }

        if (Schema::hasColumn($entity->table_name, 'created_at')) {
            $query->latest();
        }
    }
    private function applyDisplayRelations($query, DynamicEntity $entity, string $modelClass): void
{
    $relations = $this->displayRelations($entity, $modelClass);

    if (!empty($relations)) {
        $query->with($relations);
    }
}

private function appendDisplayValues(Model $record, DynamicEntity $entity): Model
{
    $fields = $entity->fields()
        ->where('control_type', 'select')
        ->get();

    foreach ($fields as $field) {
        $fieldName = $field->field_name;

        if (!str_ends_with($fieldName, '_id')) {
            continue;
        }

        $meta = $field->meta ?? [];

        $relationName = $meta['relation_name']
            ?? $this->relationNameFromField($fieldName);

        $displayColumn = $meta['display_column'] ?? 'name';

        $displayKey = $fieldName . '_display';

        $displayValue = null;

        if ($record->relationLoaded($relationName) && $record->{$relationName}) {
            $displayValue = $record->{$relationName}->{$displayColumn}
                ?? $record->{$relationName}->name
                ?? $record->{$relationName}->title
                ?? $record->{$relationName}->code
                ?? null;
        }

        $record->setAttribute($displayKey, $displayValue ?: $record->{$fieldName});
    }

    return $record;
}

private function displayRelations(DynamicEntity $entity, string $modelClass): array
{
    $model = new $modelClass();

    return $entity->fields()
        ->where('control_type', 'select')
        ->get()
        ->map(function ($field) use ($model) {
            $fieldName = $field->field_name;

            if (!str_ends_with($fieldName, '_id')) {
                return null;
            }

            $meta = $field->meta ?? [];

            $relationName = $meta['relation_name']
                ?? $this->relationNameFromField($fieldName);

            return method_exists($model, $relationName)
                ? $relationName
                : null;
        })
        ->filter()
        ->unique()
        ->values()
        ->toArray();
}
private function applyCurriculumSubjectDefaults(DynamicEntity $entity, array $data): array
{
    if ($entity->entity_code !== 'curriculum-subjects') {
        return $data;
    }

    if (empty($data['subject_id'])) {
        return $data;
    }

    $subject = Subject::query()
        ->where('id', $data['subject_id'])
        ->when(auth()->user()?->tenant_id, function ($q) {
            $q->where('tenant_id', auth()->user()->tenant_id);
        })
        ->first();

    if (!$subject) {
        return $data;
    }

    $defaults = [
        'subject_code' => $subject->code,
        'subject_name' => $subject->name,
        'subject_nature' => $subject->subject_nature,
        'credit_hours' => $subject->credit_hours,
        'theory_hours' => $subject->theory_hours,
        'practical_hours' => $subject->practical_hours,
        'tutorial_hours' => $subject->tutorial_hours,
        'total_marks' => $subject->total_marks,
        'passing_marks' => $subject->passing_marks,
        'is_compulsory' => $subject->is_compulsory,
        'is_credit_subject' => $subject->is_credit_subject,
    ];

    foreach ($defaults as $key => $value) {
        if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            $data[$key] = $value;
        }
    }

    return $data;
}
private function relationNameFromField(string $fieldName): string
{
    $name = Str::beforeLast($fieldName, '_id');

    return Str::camel($name);
}
}