<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class DynamicOptionFormatter
{
    public static function format(object|array $row, array $config = []): array
    {
        $item = is_array($row) ? (object) $row : $row;

        $id = self::value($item, ['id']);
        $code = self::value($item, ['code', 'value_code', 'lookup_code', 'slug']);

        $label = self::value($item, [
            'label',
            'title',
            'name',
            'full_name',
            'program_name',
            'session_name',
            'description',
            'code',
        ]);

        $prefixCode = $config['prefix_code'] ?? true;

        if ($prefixCode && $code && $label && $code !== $label) {
            $label = $code . ' - ' . $label;
        }

        return [
            'label' => $label ?: (string) ($code ?: $id),
            'value' => $id,
            'id' => $id,
            'code' => $code,
            'name' => self::value($item, ['name', 'title', 'label', 'full_name', 'program_name', 'session_name']),
            'raw' => $item,
        ];
    }

    public static function lookup(object|array $row): array
    {
        $item = is_array($row) ? (object) $row : $row;

        $id = self::value($item, ['id']);
        $code = self::value($item, ['code', 'value', 'lookup_code']);
        $label = self::value($item, ['name', 'title', 'label', 'description', 'code']);

        return [
            'label' => $label ?: (string) $code,
            'value' => $code,
            'id' => $id,
            'code' => $code,
            'name' => $label,
            'raw' => $item,
        ];
    }

    private static function value(object $item, array $columns): mixed
    {
        foreach ($columns as $column) {
            if (property_exists($item, $column) && $item->{$column} !== null && $item->{$column} !== '') {
                return $item->{$column};
            }
        }

        return null;
    }

    public static function existingLabelColumn(string $table): ?string
    {
        foreach (['label', 'title', 'name', 'full_name', 'program_name', 'session_name', 'description', 'code'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    public static function existingCodeColumn(string $table): ?string
    {
        foreach (['code', 'value_code', 'lookup_code', 'slug'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}