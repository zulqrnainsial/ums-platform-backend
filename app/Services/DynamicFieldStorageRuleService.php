<?php

namespace App\Services;

use App\Models\DynamicFieldStorageRule;

class DynamicFieldStorageRuleService
{
    public function getRules(array $filters = [])
    {
        return DynamicFieldStorageRule::query()
            ->when(!empty($filters['module_code']), function ($query) use ($filters) {
                $query->where('module_code', $filters['module_code']);
            })
            ->when(!empty($filters['entity_key']), function ($query) use ($filters) {
                $query->where('entity_key', $filters['entity_key']);
            })
            ->when(!empty($filters['field_name']), function ($query) use ($filters) {
                $query->where('field_name', $filters['field_name']);
            })
            ->where('status_code', 'active')
            ->orderBy('module_code')
            ->orderBy('entity_key')
            ->orderBy('field_name')
            ->get();
    }

    public function getEntityRules(string $moduleCode, string $entityKey): array
    {
        return DynamicFieldStorageRule::query()
            ->where('module_code', $moduleCode)
            ->where('entity_key', $entityKey)
            ->where('status_code', 'active')
            ->get()
            ->keyBy('field_name')
            ->toArray();
    }

    public function resolveStorageMode(string $moduleCode, string $entityKey, string $fieldName): string
    {
        $rule = DynamicFieldStorageRule::query()
            ->where('module_code', $moduleCode)
            ->where('entity_key', $entityKey)
            ->where('field_name', $fieldName)
            ->where('status_code', 'active')
            ->first();

        if ($rule) {
            return $rule->storage_mode;
        }

        if (str_ends_with($fieldName, '_id')) {
            return 'id';
        }

        if (str_ends_with($fieldName, '_code')) {
            return 'code';
        }

        if (str_ends_with($fieldName, '_ids') || str_ends_with($fieldName, '_ids_json')) {
            return 'json_ids';
        }

        if (str_ends_with($fieldName, '_codes') || str_ends_with($fieldName, '_codes_json')) {
            return 'json_codes';
        }

        return 'raw';
    }

    public function normalizeValueForStorage(string $storageMode, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($storageMode) {
            'id' => is_numeric($value) ? (int) $value : null,

            'code' => is_scalar($value)
                ? trim((string) $value)
                : null,

            'json_ids' => collect(is_array($value) ? $value : [])
                ->filter(fn ($item) => is_numeric($item))
                ->map(fn ($item) => (int) $item)
                ->values()
                ->all(),

            'json_codes' => collect(is_array($value) ? $value : [])
                ->filter(fn ($item) => is_scalar($item))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->all(),

            default => $value,
        };
    }
}