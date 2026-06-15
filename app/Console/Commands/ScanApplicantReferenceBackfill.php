<?php

namespace App\Console\Commands;

use App\Models\DynamicBackfillMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScanApplicantReferenceBackfill extends Command
{
    protected $signature = 'ums:scan-applicant-reference-backfill
        {--create-suggestions : Create unapproved mapping suggestions}
        {--tenant= : Optional tenant_id filter}';

    protected $description = 'Scan applicant qualification/test/document records that need ID backfill.';

    public function handle(): int
    {
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $createSuggestions = (bool) $this->option('create-suggestions');

        $rows = [];

        $rows = array_merge($rows, $this->scanQualificationLevels($tenantId, $createSuggestions));
        $rows = array_merge($rows, $this->scanSubjectGroups($tenantId, $createSuggestions));
        $rows = array_merge($rows, $this->scanDocumentTypes($tenantId, $createSuggestions));
        $rows = array_merge($rows, $this->scanAssessmentTests($tenantId, $createSuggestions));

        if (empty($rows)) {
            $this->info('No applicant reference backfill issues found.');
            return self::SUCCESS;
        }

        $this->table(
            [
                'Area',
                'Source Table',
                'Source Column',
                'Source Value',
                'Target Column',
                'Records',
                'Mapping',
                'Message',
            ],
            $rows
        );

        if ($createSuggestions) {
            $this->warn('Unapproved mapping suggestions were created where exact target matches were found.');
            $this->warn('Review dynamic_backfill_mappings and set is_approved = 1 before applying.');
        }

        return self::SUCCESS;
    }

    private function scanQualificationLevels(?int $tenantId, bool $createSuggestions): array
    {
        if (!Schema::hasTable('applicant_qualifications')) {
            return [];
        }

        if (!Schema::hasColumn('applicant_qualifications', 'qualification_level_id')) {
            return [];
        }

        $candidateColumns = [
            'qualification_level_code',
            'qualification_level',
            'degree_class',
            'degree_name',
            'class_name',
        ];

        return $this->scanApplicantSourceColumns(
            area: 'Qualification Level',
            sourceTable: 'applicant_qualifications',
            sourceColumns: $candidateColumns,
            targetColumn: 'qualification_level_id',
            targetTableCandidates: ['qualification_levels', 'lookup_values'],
            targetCategoryHints: ['qualification_level', 'qualification-levels', 'qualification_levels'],
            tenantId: $tenantId,
            createSuggestions: $createSuggestions
        );
    }

    private function scanSubjectGroups(?int $tenantId, bool $createSuggestions): array
    {
        if (!Schema::hasTable('applicant_qualifications')) {
            return [];
        }

        if (!Schema::hasColumn('applicant_qualifications', 'subject_group_id')) {
            return [];
        }

        $candidateColumns = [
            'subject_group_code',
            'subject_group',
            'subject_group_name',
            'group_code',
            'group_name',
        ];

        return $this->scanApplicantSourceColumns(
            area: 'Subject Group',
            sourceTable: 'applicant_qualifications',
            sourceColumns: $candidateColumns,
            targetColumn: 'subject_group_id',
            targetTableCandidates: ['subject_groups', 'lookup_values'],
            targetCategoryHints: ['subject_group', 'subject-groups', 'subject_groups'],
            tenantId: $tenantId,
            createSuggestions: $createSuggestions
        );
    }

    private function scanDocumentTypes(?int $tenantId, bool $createSuggestions): array
    {
        if (!Schema::hasTable('applicant_documents')) {
            return [];
        }

        if (!Schema::hasColumn('applicant_documents', 'document_type_id')) {
            return [];
        }

        $candidateColumns = [
            'document_type_code',
            'document_type',
            'document_type_name',
            'type_code',
            'type_name',
            'title',
        ];

        return $this->scanApplicantSourceColumns(
            area: 'Document Type',
            sourceTable: 'applicant_documents',
            sourceColumns: $candidateColumns,
            targetColumn: 'document_type_id',
            targetTableCandidates: ['document_types', 'lookup_values'],
            targetCategoryHints: ['document_type', 'document-types', 'document_types'],
            tenantId: $tenantId,
            createSuggestions: $createSuggestions
        );
    }

    private function scanAssessmentTests(?int $tenantId, bool $createSuggestions): array
    {
        if (!Schema::hasTable('applicant_test_results')) {
            return [];
        }

        if (!Schema::hasColumn('applicant_test_results', 'assessment_id')) {
            return [];
        }

        $candidateColumns = [
            'assessment_code',
            'assessment_title',
            'test_code',
            'test_name',
            'title',
            'name',
        ];

        return $this->scanApplicantSourceColumns(
            area: 'Assessment/Test',
            sourceTable: 'applicant_test_results',
            sourceColumns: $candidateColumns,
            targetColumn: 'assessment_id',
            targetTableCandidates: ['assessments'],
            targetCategoryHints: [],
            tenantId: $tenantId,
            createSuggestions: $createSuggestions
        );
    }

    private function scanApplicantSourceColumns(
        string $area,
        string $sourceTable,
        array $sourceColumns,
        string $targetColumn,
        array $targetTableCandidates,
        array $targetCategoryHints,
        ?int $tenantId,
        bool $createSuggestions
    ): array {
        $rows = [];

        foreach ($sourceColumns as $sourceColumn) {
            if (!Schema::hasColumn($sourceTable, $sourceColumn)) {
                continue;
            }

            $query = DB::table($sourceTable)
                ->whereNull($targetColumn)
                ->whereNotNull($sourceColumn)
                ->where($sourceColumn, '<>', '');

            if ($tenantId && Schema::hasColumn($sourceTable, 'tenant_id')) {
                $query->where('tenant_id', $tenantId);
            }

            $groups = $query
                ->select($sourceColumn, DB::raw('COUNT(*) as total_records'))
                ->groupBy($sourceColumn)
                ->orderByDesc('total_records')
                ->get();

            foreach ($groups as $group) {
                $sourceValue = trim((string) $group->{$sourceColumn});

                if ($sourceValue === '') {
                    continue;
                }

                $target = $this->findTarget(
                    $sourceValue,
                    $targetTableCandidates,
                    $targetCategoryHints,
                    $tenantId
                );

                $mappingStatus = 'missing';

                if ($target) {
                    $mappingStatus = 'suggested';

                    if ($createSuggestions) {
                        $this->createMappingSuggestion(
                            $sourceTable,
                            $sourceColumn,
                            $sourceValue,
                            $target['table'],
                            $targetColumn,
                            $target['id'],
                            $target['label'],
                            $area
                        );
                    }
                }

                $approved = DynamicBackfillMapping::query()
                    ->where('source_table', $sourceTable)
                    ->where('source_column', $sourceColumn)
                    ->where('source_value', $sourceValue)
                    ->where('target_column', $targetColumn)
                    ->where('is_approved', true)
                    ->where('status_code', 'active')
                    ->exists();

                if ($approved) {
                    $mappingStatus = 'approved';
                }

                $rows[] = [
                    $area,
                    $sourceTable,
                    $sourceColumn,
                    $sourceValue,
                    $targetColumn,
                    $group->total_records,
                    $mappingStatus,
                    $target
                        ? "Matched target {$target['table']}#{$target['id']} {$target['label']}"
                        : 'No exact target found. Add mapping manually.',
                ];
            }
        }

        return $rows;
    }

    private function findTarget(
        string $sourceValue,
        array $targetTableCandidates,
        array $categoryHints,
        ?int $tenantId
    ): ?array {
        foreach ($targetTableCandidates as $targetTable) {
            if (!Schema::hasTable($targetTable)) {
                continue;
            }

            $query = DB::table($targetTable);

            if ($tenantId && Schema::hasColumn($targetTable, 'tenant_id')) {
                $query->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id');
                });
            }

            if ($targetTable === 'lookup_values') {
                $this->applyLookupCategoryFilter($query, $categoryHints);
            }

            $this->applyExactMatchFilter($query, $targetTable, $sourceValue);

            $target = $query->first();

            if ($target) {
                return [
                    'table' => $targetTable,
                    'id' => (int) $target->id,
                    'label' => $this->targetLabel($target),
                ];
            }
        }

        return null;
    }

    private function applyLookupCategoryFilter($query, array $categoryHints): void
    {
        if (empty($categoryHints)) {
            return;
        }

        if (Schema::hasColumn('lookup_values', 'category_code')) {
            $query->whereIn('category_code', $categoryHints);
            return;
        }

        if (Schema::hasColumn('lookup_values', 'lookup_category_code')) {
            $query->whereIn('lookup_category_code', $categoryHints);
            return;
        }

        if (Schema::hasColumn('lookup_values', 'type_code')) {
            $query->whereIn('type_code', $categoryHints);
            return;
        }

        if (
            Schema::hasColumn('lookup_values', 'lookup_category_id') &&
            Schema::hasTable('lookup_categories')
        ) {
            $query->whereIn('lookup_category_id', function ($sub) use ($categoryHints) {
                $sub->select('id')
                    ->from('lookup_categories');

                if (Schema::hasColumn('lookup_categories', 'code')) {
                    $sub->whereIn('code', $categoryHints);
                }
            });
        }
    }

    private function applyExactMatchFilter($query, string $table, string $sourceValue): void
    {
        $columns = [
            'code',
            'slug',
            'name',
            'title',
            'label',
            'full_name',
            'program_name',
            'session_name',
            'description',
        ];

        $query->where(function ($q) use ($table, $columns, $sourceValue) {
            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $q->orWhere($column, $sourceValue);
                }
            }
        });
    }

    private function targetLabel(object $target): string
    {
        foreach (['code', 'name', 'title', 'label', 'full_name', 'description'] as $column) {
            if (isset($target->{$column}) && $target->{$column} !== '') {
                return (string) $target->{$column};
            }
        }

        return '';
    }

    private function createMappingSuggestion(
        string $sourceTable,
        string $sourceColumn,
        string $sourceValue,
        string $targetTable,
        string $targetColumn,
        int $targetId,
        string $targetLabel,
        string $area
    ): void {
        DynamicBackfillMapping::query()->updateOrCreate(
            [
                'source_table' => $sourceTable,
                'source_column' => $sourceColumn,
                'source_value' => $sourceValue,
                'target_table' => $targetTable,
                'target_column' => $targetColumn,
            ],
            [
                'module_code' => 'admission',
                'target_id' => $targetId,
                'target_label' => $targetLabel,
                'is_approved' => false,
                'status_code' => 'active',
                'notes' => "Suggested mapping for {$area}. Review and approve before applying.",
                'created_by' => auth()->id(),
            ]
        );
    }
}