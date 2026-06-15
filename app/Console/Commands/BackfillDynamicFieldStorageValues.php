<?php

namespace App\Console\Commands;

use App\Models\DynamicFieldStorageRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillDynamicFieldStorageValues extends Command
{
    protected $signature = 'ums:backfill-dynamic-field-storage
        {--module= : Limit by module_code}
        {--entity= : Limit by entity_key/table name}
        {--apply : Actually update data. Without this option it only reports.}
        {--limit=0 : Limit affected rows per field. 0 means no limit.}';

    protected $description = 'Backfill wrong ID/code values based on dynamic_field_storage_rules. Dry-run by default.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $module = $this->option('module');
        $entity = $this->option('entity');
        $limit = (int) $this->option('limit');

        if (!Schema::hasTable('dynamic_field_storage_rules')) {
            $this->error('dynamic_field_storage_rules table not found. Run Step 4X-STABILIZE-1 first.');
            return self::FAILURE;
        }

        $rules = DynamicFieldStorageRule::query()
            ->where('status_code', 'active')
            ->where('is_business_critical', true)
            ->when($module, fn ($q) => $q->where('module_code', $module))
            ->when($entity, fn ($q) => $q->where('entity_key', $entity))
            ->orderBy('module_code')
            ->orderBy('entity_key')
            ->orderBy('field_name')
            ->get();

        if ($rules->isEmpty()) {
            $this->warn('No active dynamic field storage rules found.');
            return self::SUCCESS;
        }

        $reportRows = [];
        $updatedTotal = 0;

        foreach ($rules as $rule) {
            $table = $rule->entity_key;
            $field = $rule->field_name;

            if (!Schema::hasTable($table)) {
                $reportRows[] = $this->reportRow(
                    $rule,
                    'table_missing',
                    0,
                    0,
                    'Table does not exist.'
                );
                continue;
            }

            if (!Schema::hasColumn($table, $field)) {
                $reportRows[] = $this->reportRow(
                    $rule,
                    'field_missing',
                    0,
                    0,
                    'Field does not exist.'
                );
                continue;
            }

            if ($rule->storage_mode === 'code') {
                $result = $this->handleCodeField($table, $field, $apply, $limit);

                $updatedTotal += $result['updated'];

                $reportRows[] = $this->reportRow(
                    $rule,
                    $result['status'],
                    $result['bad_count'],
                    $result['updated'],
                    $result['message']
                );

                continue;
            }

            if ($rule->storage_mode === 'id') {
                $result = $this->validateIdField($table, $field);

                $reportRows[] = $this->reportRow(
                    $rule,
                    $result['status'],
                    $result['bad_count'],
                    0,
                    $result['message']
                );

                continue;
            }

            if (in_array($rule->storage_mode, ['json_ids', 'json_codes'], true)) {
                $result = $this->validateJsonField($table, $field, $rule->storage_mode);

                $reportRows[] = $this->reportRow(
                    $rule,
                    $result['status'],
                    $result['bad_count'],
                    0,
                    $result['message']
                );

                continue;
            }
        }

        $this->line('');
        $this->info($apply ? 'Backfill mode: APPLY' : 'Backfill mode: DRY RUN');
        $this->line('');

        $this->table(
            [
                'Module',
                'Table',
                'Field',
                'Mode',
                'Status',
                'Bad Rows',
                'Updated',
                'Message',
            ],
            $reportRows
        );

        $this->line('');

        if ($apply) {
            $this->info("Total rows updated: {$updatedTotal}");
        } else {
            $this->warn('No data was changed. Run again with --apply to update fixable rows.');
        }

        return self::SUCCESS;
    }

    private function handleCodeField(string $table, string $field, bool $apply, int $limit): array
    {
        if (!Schema::hasTable('lookup_values')) {
            return [
                'status' => 'cannot_fix',
                'bad_count' => 0,
                'updated' => 0,
                'message' => 'lookup_values table not found.',
            ];
        }

        if (!Schema::hasColumn('lookup_values', 'id') || !Schema::hasColumn('lookup_values', 'code')) {
            return [
                'status' => 'cannot_fix',
                'bad_count' => 0,
                'updated' => 0,
                'message' => 'lookup_values.id/code columns not found.',
            ];
        }

        $badQuery = DB::table($table)
            ->whereNotNull($field)
            ->where($field, '<>', '')
            ->whereRaw("CAST({$field} AS CHAR) REGEXP '^[0-9]+$'");

        $badCount = (clone $badQuery)->count();

        if ($badCount === 0) {
            return [
                'status' => 'clean',
                'bad_count' => 0,
                'updated' => 0,
                'message' => 'No numeric values found in code field.',
            ];
        }

        $fixableRowsQuery = DB::table($table . ' as t')
            ->join('lookup_values as lv', DB::raw("CAST(t.{$field} AS UNSIGNED)"), '=', 'lv.id')
            ->whereNotNull("t.{$field}")
            ->where("t.{$field}", '<>', '')
            ->whereRaw("CAST(t.{$field} AS CHAR) REGEXP '^[0-9]+$'")
            ->select('t.id', "t.{$field} as old_value", 'lv.code as new_value');

        if ($limit > 0) {
            $fixableRowsQuery->limit($limit);
        }

        $fixableRows = $fixableRowsQuery->get();

        if ($fixableRows->isEmpty()) {
            return [
                'status' => 'unfixable',
                'bad_count' => $badCount,
                'updated' => 0,
                'message' => 'Numeric values found but no matching lookup_values.id found.',
            ];
        }

        if (!$apply) {
            $samples = $fixableRows
                ->take(5)
                ->map(fn ($row) => "{$row->old_value}→{$row->new_value}")
                ->implode(', ');

            return [
                'status' => 'fixable',
                'bad_count' => $badCount,
                'updated' => 0,
                'message' => "Fixable. Samples: {$samples}",
            ];
        }

        $updated = 0;

        DB::transaction(function () use ($table, $field, $fixableRows, &$updated) {
            foreach ($fixableRows as $row) {
                $payload = [
                    $field => $row->new_value,
                ];

                if (Schema::hasColumn($table, 'updated_at')) {
                    $payload['updated_at'] = now();
                }

                $updated += DB::table($table)
                    ->where('id', $row->id)
                    ->update($payload);
            }
        });

        return [
            'status' => 'fixed',
            'bad_count' => $badCount,
            'updated' => $updated,
            'message' => 'Numeric lookup IDs replaced with lookup codes.',
        ];
    }

    private function validateIdField(string $table, string $field): array
    {
        $badCount = DB::table($table)
            ->whereNotNull($field)
            ->where($field, '<>', '')
            ->whereRaw("CAST({$field} AS CHAR) REGEXP '[A-Za-z]'")
            ->count();

        if ($badCount === 0) {
            return [
                'status' => 'clean',
                'bad_count' => 0,
                'message' => 'ID field is numeric or null.',
            ];
        }

        return [
            'status' => 'needs_manual_review',
            'bad_count' => $badCount,
            'message' => 'Text found in ID field. Needs manual mapping.',
        ];
    }

    private function validateJsonField(string $table, string $field, string $storageMode): array
    {
        $rows = DB::table($table)
            ->whereNotNull($field)
            ->where($field, '<>', '')
            ->limit(500)
            ->get(['id', $field]);

        $badCount = 0;

        foreach ($rows as $row) {
            $value = $row->{$field};

            $decoded = json_decode((string) $value, true);

            if (!is_array($decoded)) {
                $badCount++;
                continue;
            }

            foreach ($decoded as $item) {
                if ($storageMode === 'json_ids' && !is_numeric($item)) {
                    $badCount++;
                    break;
                }

                if ($storageMode === 'json_codes' && (!is_string($item) || trim($item) === '')) {
                    $badCount++;
                    break;
                }
            }
        }

        if ($badCount === 0) {
            return [
                'status' => 'clean',
                'bad_count' => 0,
                'message' => 'JSON field format is valid.',
            ];
        }

        return [
            'status' => 'needs_manual_review',
            'bad_count' => $badCount,
            'message' => 'Invalid JSON storage format found.',
        ];
    }

    private function reportRow(DynamicFieldStorageRule $rule, string $status, int $badCount, int $updated, string $message): array
    {
        return [
            $rule->module_code,
            $rule->entity_key,
            $rule->field_name,
            $rule->storage_mode,
            $status,
            $badCount,
            $updated,
            $message,
        ];
    }
}