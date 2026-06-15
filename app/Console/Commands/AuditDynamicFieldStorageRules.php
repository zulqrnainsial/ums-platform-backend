<?php

namespace App\Console\Commands;

use App\Models\DynamicFieldStorageRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditDynamicFieldStorageRules extends Command
{
    protected $signature = 'ums:audit-dynamic-field-storage {--fix-report}';

    protected $description = 'Audit dynamic field storage rules and detect ID/code storage mismatches.';

    public function handle(): int
    {
        $rules = DynamicFieldStorageRule::query()
            ->where('status_code', 'active')
            ->where('is_business_critical', true)
            ->orderBy('module_code')
            ->orderBy('entity_key')
            ->get();

        $issues = [];

        foreach ($rules as $rule) {
            $table = $rule->entity_key;
            $field = $rule->field_name;

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $field)) {
                continue;
            }

            if ($rule->storage_mode === 'code') {
                $badRows = DB::table($table)
                    ->whereNotNull($field)
                    ->where($field, '<>', '')
                    ->whereRaw("{$field} REGEXP '^[0-9]+$'")
                    ->limit(10)
                    ->pluck($field)
                    ->toArray();

                if (!empty($badRows)) {
                    $issues[] = [
                        'table' => $table,
                        'field' => $field,
                        'expected' => 'code',
                        'problem' => 'Numeric value found in _code/business-code field.',
                        'samples' => implode(', ', $badRows),
                    ];
                }
            }

            if ($rule->storage_mode === 'id') {
                $badRows = DB::table($table)
                    ->whereNotNull($field)
                    ->where($field, '<>', '')
                    ->whereRaw("{$field} REGEXP '[A-Za-z]'")
                    ->limit(10)
                    ->pluck($field)
                    ->toArray();

                if (!empty($badRows)) {
                    $issues[] = [
                        'table' => $table,
                        'field' => $field,
                        'expected' => 'id',
                        'problem' => 'Text value found in ID field.',
                        'samples' => implode(', ', $badRows),
                    ];
                }
            }
        }

        if (empty($issues)) {
            $this->info('No dynamic field storage mismatch found.');
            return self::SUCCESS;
        }

        $this->error('Dynamic field storage issues found:');

        $this->table(
            ['Table', 'Field', 'Expected', 'Problem', 'Samples'],
            collect($issues)->map(fn ($issue) => [
                $issue['table'],
                $issue['field'],
                $issue['expected'],
                $issue['problem'],
                $issue['samples'],
            ])->toArray()
        );

        return self::FAILURE;
    }
}