<?php

namespace App\Console\Commands;

use App\Models\DynamicBackfillMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplyApplicantReferenceBackfill extends Command
{
    protected $signature = 'ums:apply-applicant-reference-backfill
        {--apply : Actually update data. Without this option it only reports.}
        {--tenant= : Optional tenant_id filter}
        {--source-table= : Optional source table filter}';

    protected $description = 'Apply approved applicant reference ID backfill mappings. Dry-run by default.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $sourceTableFilter = $this->option('source-table');

        $mappings = DynamicBackfillMapping::query()
            ->where('is_approved', true)
            ->where('status_code', 'active')
            ->when($sourceTableFilter, fn ($q) => $q->where('source_table', $sourceTableFilter))
            ->orderBy('source_table')
            ->orderBy('source_column')
            ->get();

        if ($mappings->isEmpty()) {
            $this->warn('No approved backfill mappings found.');
            return self::SUCCESS;
        }

        $report = [];
        $updatedTotal = 0;

        foreach ($mappings as $mapping) {
            $result = $this->applyMapping($mapping, $apply, $tenantId);
            $updatedTotal += $result['updated'];

            $report[] = [
                $mapping->source_table,
                $mapping->source_column,
                $mapping->source_value,
                $mapping->target_column,
                $mapping->target_id,
                $result['matched'],
                $result['updated'],
                $result['message'],
            ];
        }

        $this->info($apply ? 'Applicant reference backfill mode: APPLY' : 'Applicant reference backfill mode: DRY RUN');

        $this->table(
            [
                'Source Table',
                'Source Column',
                'Source Value',
                'Target Column',
                'Target ID',
                'Matched',
                'Updated',
                'Message',
            ],
            $report
        );

        if ($apply) {
            $this->info("Total applicant rows updated: {$updatedTotal}");
        } else {
            $this->warn('No data was changed. Add --apply after reviewing the report.');
        }

        return self::SUCCESS;
    }

    private function applyMapping(DynamicBackfillMapping $mapping, bool $apply, ?int $tenantId): array
    {
        if (!Schema::hasTable($mapping->source_table)) {
            return [
                'matched' => 0,
                'updated' => 0,
                'message' => 'Source table does not exist.',
            ];
        }

        if (!Schema::hasColumn($mapping->source_table, $mapping->source_column)) {
            return [
                'matched' => 0,
                'updated' => 0,
                'message' => 'Source column does not exist.',
            ];
        }

        if (!Schema::hasColumn($mapping->source_table, $mapping->target_column)) {
            return [
                'matched' => 0,
                'updated' => 0,
                'message' => 'Target column does not exist.',
            ];
        }

        $query = DB::table($mapping->source_table)
            ->whereNull($mapping->target_column)
            ->where($mapping->source_column, $mapping->source_value);

        if ($tenantId && Schema::hasColumn($mapping->source_table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        $matched = (clone $query)->count();

        if ($matched === 0) {
            return [
                'matched' => 0,
                'updated' => 0,
                'message' => 'No rows need update.',
            ];
        }

        if (!$apply) {
            return [
                'matched' => $matched,
                'updated' => 0,
                'message' => 'Dry run only.',
            ];
        }

        $payload = [
            $mapping->target_column => $mapping->target_id,
        ];

        if (Schema::hasColumn($mapping->source_table, 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $updated = $query->update($payload);

        return [
            'matched' => $matched,
            'updated' => $updated,
            'message' => 'Approved mapping applied.',
        ];
    }
}