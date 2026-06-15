<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\DynamicFieldStorageRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DynamicFieldStorageValidationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $module = $request->query('module_code');
        $entity = $request->query('entity_key');

        $rules = DynamicFieldStorageRule::query()
            ->where('status_code', 'active')
            ->where('is_business_critical', true)
            ->when($module, fn ($q) => $q->where('module_code', $module))
            ->when($entity, fn ($q) => $q->where('entity_key', $entity))
            ->orderBy('module_code')
            ->orderBy('entity_key')
            ->orderBy('field_name')
            ->get();

        $rows = [];

        foreach ($rules as $rule) {
            $rows[] = $this->validateRule($rule);
        }

        $summary = [
            'total_rules' => count($rows),
            'clean' => collect($rows)->where('status', 'clean')->count(),
            'fixable' => collect($rows)->where('status', 'fixable')->count(),
            'needs_manual_review' => collect($rows)->where('status', 'needs_manual_review')->count(),
            'missing' => collect($rows)->filter(fn ($row) => in_array($row['status'], ['table_missing', 'field_missing'], true))->count(),
        ];

        return ApiResponse::success([
            'summary' => $summary,
            'rows' => $rows,
        ], 'Dynamic field storage validation report fetched successfully.');
    }

    private function validateRule(DynamicFieldStorageRule $rule): array
    {
        $table = $rule->entity_key;
        $field = $rule->field_name;

        if (!Schema::hasTable($table)) {
            return $this->row($rule, 'table_missing', 0, 0, 'Table does not exist.');
        }

        if (!Schema::hasColumn($table, $field)) {
            return $this->row($rule, 'field_missing', 0, 0, 'Field does not exist.');
        }

        if ($rule->storage_mode === 'code') {
            return $this->validateCode($rule);
        }

        if ($rule->storage_mode === 'id') {
            return $this->validateId($rule);
        }

        if (in_array($rule->storage_mode, ['json_ids', 'json_codes'], true)) {
            return $this->validateJson($rule);
        }

        return $this->row($rule, 'clean', 0, 0, 'Raw/user-input field.');
    }

    private function validateCode(DynamicFieldStorageRule $rule): array
    {
        $table = $rule->entity_key;
        $field = $rule->field_name;

        $badCount = DB::table($table)
            ->whereNotNull($field)
            ->where($field, '<>', '')
            ->whereRaw("CAST({$field} AS CHAR) REGEXP '^[0-9]+$'")
            ->count();

        if ($badCount === 0) {
            return $this->row($rule, 'clean', 0, 0, 'Code field is clean.');
        }

        $fixableCount = 0;

        if (
            Schema::hasTable('lookup_values') &&
            Schema::hasColumn('lookup_values', 'id') &&
            Schema::hasColumn('lookup_values', 'code')
        ) {
            $fixableCount = DB::table($table . ' as t')
                ->join('lookup_values as lv', DB::raw("CAST(t.{$field} AS UNSIGNED)"), '=', 'lv.id')
                ->whereNotNull("t.{$field}")
                ->where("t.{$field}", '<>', '')
                ->whereRaw("CAST(t.{$field} AS CHAR) REGEXP '^[0-9]+$'")
                ->count();
        }

        if ($fixableCount > 0) {
            return $this->row(
                $rule,
                'fixable',
                $badCount,
                $fixableCount,
                'Numeric lookup IDs found in code field. Can be backfilled from lookup_values.'
            );
        }

        return $this->row(
            $rule,
            'needs_manual_review',
            $badCount,
            0,
            'Numeric values found but no lookup_values mapping found.'
        );
    }

    private function validateId(DynamicFieldStorageRule $rule): array
    {
        $table = $rule->entity_key;
        $field = $rule->field_name;

        $badCount = DB::table($table)
            ->whereNotNull($field)
            ->where($field, '<>', '')
            ->whereRaw("CAST({$field} AS CHAR) REGEXP '[A-Za-z]'")
            ->count();

        if ($badCount === 0) {
            return $this->row($rule, 'clean', 0, 0, 'ID field is clean.');
        }

        return $this->row(
            $rule,
            'needs_manual_review',
            $badCount,
            0,
            'Text value found in ID field.'
        );
    }

    private function validateJson(DynamicFieldStorageRule $rule): array
    {
        $table = $rule->entity_key;
        $field = $rule->field_name;

        $records = DB::table($table)
            ->whereNotNull($field)
            ->where($field, '<>', '')
            ->limit(500)
            ->get(['id', $field]);

        $badCount = 0;

        foreach ($records as $record) {
            $decoded = json_decode((string) $record->{$field}, true);

            if (!is_array($decoded)) {
                $badCount++;
                continue;
            }

            foreach ($decoded as $item) {
                if ($rule->storage_mode === 'json_ids' && !is_numeric($item)) {
                    $badCount++;
                    break;
                }

                if ($rule->storage_mode === 'json_codes' && (!is_string($item) || trim($item) === '')) {
                    $badCount++;
                    break;
                }
            }
        }

        if ($badCount === 0) {
            return $this->row($rule, 'clean', 0, 0, 'JSON field is clean.');
        }

        return $this->row(
            $rule,
            'needs_manual_review',
            $badCount,
            0,
            'Invalid JSON format/value type found.'
        );
    }

    private function row(
        DynamicFieldStorageRule $rule,
        string $status,
        int $badCount,
        int $fixableCount,
        string $message
    ): array {
        return [
            'module_code' => $rule->module_code,
            'entity_key' => $rule->entity_key,
            'field_name' => $rule->field_name,
            'field_label' => $rule->field_label,
            'storage_mode' => $rule->storage_mode,
            'option_source_key' => $rule->option_source_key,
            'status' => $status,
            'bad_count' => $badCount,
            'fixable_count' => $fixableCount,
            'message' => $message,
        ];
    }
}