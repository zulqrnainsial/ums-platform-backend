<?php

namespace App\Modules\Examination\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GradingSchemeService
{
    public function context(): array
    {
        $tenantId = $this->tenantId();

        return [
            'rule_sets' => DB::table('examination_rule_sets')
                ->where('tenant_id', $tenantId)
                ->where('status_code', 'active')
                ->whereNull('deleted_at')
                ->orderBy('rule_set_name')
                ->get([
                    'id as value',
                    'rule_set_code',
                    'rule_set_name as label',
                    'grading_method_code',
                    'gpa_enabled',
                    'obe_enabled',
                ])
                ->map(fn ($row) => (array) $row)
                ->all(),

            'grading_methods' => [
                [
                    'value' => 'absolute',
                    'label' => 'Absolute Grading',
                ],
                [
                    'value' => 'relative_percentile',
                    'label' => 'Relative Grading by Percentile',
                ],
                [
                    'value' => 'relative_rank',
                    'label' => 'Relative Grading by Rank',
                ],
                [
                    'value' => 'relative_z_score',
                    'label' => 'Relative Grading by Z-Score',
                ],
                [
                    'value' => 'pass_fail',
                    'label' => 'Pass / Fail',
                ],
            ],
        ];
    }

    public function schemes(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('grading_schemes as gs')
            ->leftJoin(
                'examination_rule_sets as ers',
                'ers.id',
                '=',
                'gs.examination_rule_set_id'
            )
            ->where('gs.tenant_id', $tenantId)
            ->whereNull('gs.deleted_at');

        foreach ([
            'examination_rule_set_id',
            'grading_method_code',
            'status_code',
        ] as $field) {
            if (
                array_key_exists($field, $filters)
                && $filters[$field] !== null
                && $filters[$field] !== ''
            ) {
                $query->where("gs.$field", $filters[$field]);
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';

            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('gs.scheme_code', 'like', $search)
                    ->orWhere('gs.scheme_name', 'like', $search);
            });
        }

        return $query
            ->select([
                'gs.*',
                'ers.rule_set_code',
                'ers.rule_set_name',
                DB::raw('(
                    SELECT COUNT(*)
                    FROM grading_scheme_rows gsr
                    WHERE gsr.grading_scheme_id = gs.id
                ) as rows_count'),
            ])
            ->orderByDesc('gs.is_default')
            ->orderBy('gs.scheme_name')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function schemeDetail(int $schemeId): array
    {
        $tenantId = $this->tenantId();

        $scheme = DB::table('grading_schemes as gs')
            ->leftJoin(
                'examination_rule_sets as ers',
                'ers.id',
                '=',
                'gs.examination_rule_set_id'
            )
            ->where('gs.tenant_id', $tenantId)
            ->where('gs.id', $schemeId)
            ->whereNull('gs.deleted_at')
            ->select([
                'gs.*',
                'ers.rule_set_code',
                'ers.rule_set_name',
            ])
            ->first();

        abort_if(!$scheme, 404, 'Grading scheme not found.');

        return [
            'scheme' => (array) $scheme,
            'rows' => $this->rows($schemeId),
        ];
    }

    public function createScheme(array $data): array
    {
        $tenantId = $this->tenantId();

        $this->validateRuleSetLink(
            $tenantId,
            $data['examination_rule_set_id'] ?? null,
            $data['grading_method_code']
        );

        $exists = DB::table('grading_schemes')
            ->where('tenant_id', $tenantId)
            ->where('scheme_code', $data['scheme_code'])
            ->whereNull('deleted_at')
            ->exists();

        abort_if(
            $exists,
            422,
            'A grading scheme already exists with this code.'
        );

        return DB::transaction(function () use ($tenantId, $data) {
            $payload = $this->onlyColumns(
                'grading_schemes',
                array_merge($data, [
                    'tenant_id' => $tenantId,
                    'is_default' => (bool) ($data['is_default'] ?? false),
                    'status_code' => $data['status_code'] ?? 'active',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );

            if ($payload['is_default']) {
                $this->clearDefault(
                    $tenantId,
                    $payload['examination_rule_set_id'] ?? null
                );
            }

            $id = DB::table('grading_schemes')
                ->insertGetId($payload);

            return $this->schemeDetail($id);
        });
    }

    public function updateScheme(int $schemeId, array $data): array
    {
        $tenantId = $this->tenantId();

        $existing = $this->scheme($tenantId, $schemeId);

        $merged = array_merge((array) $existing, $data);

        $this->validateRuleSetLink(
            $tenantId,
            $merged['examination_rule_set_id'] ?? null,
            $merged['grading_method_code']
        );

        if (
            !empty($data['scheme_code'])
            && $data['scheme_code'] !== $existing->scheme_code
        ) {
            $duplicate = DB::table('grading_schemes')
                ->where('tenant_id', $tenantId)
                ->where('scheme_code', $data['scheme_code'])
                ->where('id', '!=', $schemeId)
                ->whereNull('deleted_at')
                ->exists();

            abort_if(
                $duplicate,
                422,
                'Another grading scheme already uses this code.'
            );
        }

        if (
            !empty($data['grading_method_code'])
            && $data['grading_method_code'] !== $existing->grading_method_code
        ) {
            $hasRows = DB::table('grading_scheme_rows')
                ->where('grading_scheme_id', $schemeId)
                ->exists();

            abort_if(
                $hasRows,
                422,
                'Cannot change grading method after ready reckoner rows exist. Create a new scheme instead.'
            );
        }

        return DB::transaction(function () use (
            $tenantId,
            $schemeId,
            $existing,
            $data,
            $merged
        ) {
            $payload = $this->onlyColumns(
                'grading_schemes',
                array_merge($data, [
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ])
            );

            if (
                array_key_exists('is_default', $data)
                && $data['is_default']
            ) {
                $this->clearDefault(
                    $tenantId,
                    $merged['examination_rule_set_id'] ?? null,
                    $schemeId
                );
            }

            DB::table('grading_schemes')
                ->where('tenant_id', $tenantId)
                ->where('id', $schemeId)
                ->update($payload);

            return $this->schemeDetail($schemeId);
        });
    }

    public function setStatus(
        int $schemeId,
        string $statusCode
    ): array {
        $tenantId = $this->tenantId();

        $scheme = $this->scheme($tenantId, $schemeId);

        abort_if(
            $scheme->is_default && $statusCode !== 'active',
            422,
            'A default grading scheme cannot be deactivated. Set another active scheme as default first.'
        );

        DB::table('grading_schemes')
            ->where('tenant_id', $tenantId)
            ->where('id', $schemeId)
            ->update([
                'status_code' => $statusCode,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return $this->schemeDetail($schemeId);
    }

    public function rows(int $schemeId): array
    {
        $tenantId = $this->tenantId();

        $this->scheme($tenantId, $schemeId);

        return DB::table('grading_scheme_rows')
            ->where('tenant_id', $tenantId)
            ->where('grading_scheme_id', $schemeId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function saveRows(int $schemeId, array $rows): array
    {
        $tenantId = $this->tenantId();
        $scheme = $this->scheme($tenantId, $schemeId);

        $this->validateRows(
            $scheme->grading_method_code,
            $rows
        );

        return DB::transaction(function () use (
            $tenantId,
            $schemeId,
            $rows
        ) {
            $submittedIds = collect($rows)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!empty($submittedIds)) {
                $invalidIds = DB::table('grading_scheme_rows')
                    ->where('tenant_id', $tenantId)
                    ->where('grading_scheme_id', $schemeId)
                    ->whereIn('id', $submittedIds)
                    ->count();

                abort_if(
                    $invalidIds !== count($submittedIds),
                    422,
                    'One or more grading rows do not belong to this scheme.'
                );
            }

            $deleteQuery = DB::table('grading_scheme_rows')
                ->where('tenant_id', $tenantId)
                ->where('grading_scheme_id', $schemeId);

            if (!empty($submittedIds)) {
                $deleteQuery->whereNotIn('id', $submittedIds);
            }

            $deleteQuery->delete();

            foreach ($rows as $row) {
                $payload = $this->onlyColumns(
                    'grading_scheme_rows',
                    array_merge($row, [
                        'tenant_id' => $tenantId,
                        'grading_scheme_id' => $schemeId,
                        'is_pass' => (bool) ($row['is_pass'] ?? true),
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );

                if (!empty($row['id'])) {
                    unset($payload['id']);
                    unset($payload['created_by']);
                    unset($payload['created_at']);

                    DB::table('grading_scheme_rows')
                        ->where('tenant_id', $tenantId)
                        ->where('grading_scheme_id', $schemeId)
                        ->where('id', $row['id'])
                        ->update($payload);

                    continue;
                }

                DB::table('grading_scheme_rows')
                    ->insert($payload);
            }

            return $this->schemeDetail($schemeId);
        });
    }

    private function validateRuleSetLink(
        int $tenantId,
        ?int $ruleSetId,
        string $gradingMethod
    ): void {
        if (!$ruleSetId) {
            return;
        }

        $ruleSet = DB::table('examination_rule_sets')
            ->where('tenant_id', $tenantId)
            ->where('id', $ruleSetId)
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$ruleSet, 422, 'Select a valid active examination rule set.');

        $isRelativeScheme = str_starts_with(
            $gradingMethod,
            'relative_'
        );

        abort_if(
            $isRelativeScheme
                && $ruleSet->grading_method_code !== 'relative',
            422,
            'A relative ready reckoner can only be attached to a rule set configured for relative grading.'
        );

        abort_if(
            $gradingMethod === 'absolute'
                && $ruleSet->grading_method_code !== 'absolute',
            422,
            'An absolute grading scheme can only be attached to a rule set configured for absolute grading.'
        );

        abort_if(
            $gradingMethod === 'pass_fail'
                && $ruleSet->grading_method_code !== 'pass_fail',
            422,
            'A pass/fail scheme can only be attached to a pass/fail rule set.'
        );
    }

    private function validateRows(
    string $gradingMethod,
    array $rows
): void {
    $sortOrders = collect($rows)->pluck('sort_order');

    abort_if(
        $sortOrders->count() !== $sortOrders->unique()->count(),
        422,
        'Ready reckoner sort order must be unique.'
    );

    $gradePointKeys = collect($rows)
        ->map(function (array $row) {
            return strtoupper(trim((string) ($row['grade_letter'] ?? '')))
                . '|'
                . number_format((float) ($row['grade_point'] ?? 0), 2, '.', '');
        });

    abort_if(
        $gradePointKeys->count() !== $gradePointKeys->unique()->count(),
        422,
        'The same grade letter and grade point combination cannot appear more than once.'
    );

    $ranges = [];

    foreach ($rows as $row) {
        $gradeLetter = strtoupper(trim((string) ($row['grade_letter'] ?? '')));
        $gradePoint = $row['grade_point'] ?? null;

        abort_if(
            $gradeLetter === '',
            422,
            'Grade letter is required.'
        );

        abort_if(
            $gradePoint === null,
            422,
            "Grade point is required for grade {$gradeLetter}."
        );

        $range = match ($gradingMethod) {
            'absolute', 'pass_fail' => $this->numericRange(
                $row['minimum_percentage'] ?? null,
                $row['maximum_percentage'] ?? null,
                "Percentage range is required for grade {$gradeLetter}."
            ),

            'relative_percentile' => $this->numericRange(
                $row['minimum_percentile'] ?? null,
                $row['maximum_percentile'] ?? null,
                "Percentile range is required for grade {$gradeLetter}."
            ),

            'relative_rank' => $this->integerRange(
                $row['minimum_rank'] ?? null,
                $row['maximum_rank'] ?? null,
                "Rank range is required for grade {$gradeLetter}."
            ),

            'relative_z_score' => $this->numericRange(
                $row['minimum_z_score'] ?? null,
                $row['maximum_z_score'] ?? null,
                "Z-score range is required for grade {$gradeLetter}."
            ),

            default => abort(422, 'Unsupported grading method.'),
        };

        $ranges[] = [
            'minimum' => $range['minimum'],
            'maximum' => $range['maximum'],
            'grade_letter' => $gradeLetter,
            'grade_point' => (float) $gradePoint,
        ];
    }

    usort(
        $ranges,
        fn ($left, $right) => $left['minimum'] <=> $right['minimum']
    );

    for ($index = 1; $index < count($ranges); $index++) {
        $previous = $ranges[$index - 1];
        $current = $ranges[$index];

        abort_if(
            $current['minimum'] <= $previous['maximum'],
            422,
            "Ready reckoner ranges overlap between "
                . "{$previous['grade_letter']} ({$previous['grade_point']}) "
                . "and {$current['grade_letter']} ({$current['grade_point']})."
        );
    }
}

    private function numericRange(
        mixed $minimum,
        mixed $maximum,
        string $message
    ): array {
        abort_if(
            $minimum === null || $maximum === null,
            422,
            $message
        );

        $minimum = (float) $minimum;
        $maximum = (float) $maximum;

        abort_if(
            $minimum > $maximum,
            422,
            'A ready reckoner minimum value cannot exceed its maximum value.'
        );

        return compact('minimum', 'maximum');
    }

    private function integerRange(
        mixed $minimum,
        mixed $maximum,
        string $message
    ): array {
        abort_if(
            $minimum === null || $maximum === null,
            422,
            $message
        );

        $minimum = (int) $minimum;
        $maximum = (int) $maximum;

        abort_if(
            $minimum > $maximum,
            422,
            'A ready reckoner minimum rank cannot exceed its maximum rank.'
        );

        return compact('minimum', 'maximum');
    }

    private function clearDefault(
        int $tenantId,
        ?int $ruleSetId,
        ?int $ignoreSchemeId = null
    ): void {
        $query = DB::table('grading_schemes')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        if ($ruleSetId) {
            $query->where(
                'examination_rule_set_id',
                $ruleSetId
            );
        } else {
            $query->whereNull('examination_rule_set_id');
        }

        if ($ignoreSchemeId) {
            $query->where('id', '!=', $ignoreSchemeId);
        }

        $query->update([
            'is_default' => false,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);
    }

    private function scheme(
        int $tenantId,
        int $schemeId
    ): object {
        $scheme = DB::table('grading_schemes')
            ->where('tenant_id', $tenantId)
            ->where('id', $schemeId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$scheme, 404, 'Grading scheme not found.');

        return $scheme;
    }

    private function onlyColumns(
        string $table,
        array $payload
    ): array {
        $columns = Schema::getColumnListing($table);

        return array_filter(
            $payload,
            fn ($value, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        abort_if(!$tenantId, 422, 'Active tenant could not be resolved.');

        return (int) $tenantId;
    }
}