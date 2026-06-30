<?php

namespace App\Modules\Examination\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EvaluationSchemeService
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
                    'theory_practical_evaluation_code',
                ])
                ->map(fn ($row) => (array) $row)
                ->all(),

            'evaluation_modes' => [
                [
                    'value' => 'combined',
                    'label' => 'Combined Theory and Practical',
                ],
                [
                    'value' => 'separate_theory_practical',
                    'label' => 'Separate Theory and Practical',
                ],
            ],

            'component_types' => [
                ['value' => 'sessional', 'label' => 'Sessional'],
                ['value' => 'midterm', 'label' => 'Midterm'],
                ['value' => 'final', 'label' => 'Final Term'],
                ['value' => 'practical', 'label' => 'Practical'],
                ['value' => 'viva', 'label' => 'Viva'],
                ['value' => 'project', 'label' => 'Project'],
                ['value' => 'internship', 'label' => 'Internship'],
                ['value' => 'other', 'label' => 'Other'],
            ],

            'evaluation_parts' => [
                ['value' => 'theory', 'label' => 'Theory'],
                ['value' => 'practical', 'label' => 'Practical'],
                ['value' => 'combined', 'label' => 'Combined'],
            ],

            'item_types' => [
                ['value' => 'quiz', 'label' => 'Quiz'],
                ['value' => 'assignment', 'label' => 'Assignment'],
                ['value' => 'test', 'label' => 'Class Test'],
                ['value' => 'presentation', 'label' => 'Presentation'],
                ['value' => 'lab_task', 'label' => 'Lab Task'],
                ['value' => 'lab_viva', 'label' => 'Lab Viva'],
                ['value' => 'project_task', 'label' => 'Project Task'],
                ['value' => 'other', 'label' => 'Other'],
            ],
        ];
    }

    public function schemes(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('evaluation_schemes as es')
            ->leftJoin(
                'examination_rule_sets as ers',
                'ers.id',
                '=',
                'es.examination_rule_set_id'
            )
            ->where('es.tenant_id', $tenantId)
            ->whereNull('es.deleted_at');

        foreach ([
            'examination_rule_set_id',
            'evaluation_mode_code',
            'status_code',
        ] as $field) {
            if (
                array_key_exists($field, $filters)
                && $filters[$field] !== null
                && $filters[$field] !== ''
            ) {
                $query->where("es.$field", $filters[$field]);
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';

            $query->where(function ($q) use ($search) {
                $q->where('es.scheme_code', 'like', $search)
                    ->orWhere('es.scheme_name', 'like', $search);
            });
        }

        return $query
            ->select([
                'es.*',
                'ers.rule_set_code',
                'ers.rule_set_name',
                DB::raw('(
                    SELECT COUNT(*)
                    FROM evaluation_scheme_components esc
                    WHERE esc.evaluation_scheme_id = es.id
                      AND esc.deleted_at IS NULL
                ) as components_count'),
            ])
            ->orderBy('es.scheme_name')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function detail(int $schemeId): array
    {
        $tenantId = $this->tenantId();

        $scheme = DB::table('evaluation_schemes as es')
            ->leftJoin(
                'examination_rule_sets as ers',
                'ers.id',
                '=',
                'es.examination_rule_set_id'
            )
            ->where('es.tenant_id', $tenantId)
            ->where('es.id', $schemeId)
            ->whereNull('es.deleted_at')
            ->select([
                'es.*',
                'ers.rule_set_code',
                'ers.rule_set_name',
            ])
            ->first();

        abort_if(!$scheme, 404, 'Evaluation scheme not found.');

        return [
            'scheme' => (array) $scheme,
            'components' => $this->components($tenantId, $schemeId),
        ];
    }

    public function createScheme(array $data): array
    {
        $tenantId = $this->tenantId();

        $this->validateRuleSet($tenantId, $data['examination_rule_set_id'] ?? null);

        $exists = DB::table('evaluation_schemes')
            ->where('tenant_id', $tenantId)
            ->where('scheme_code', $data['scheme_code'])
            ->whereNull('deleted_at')
            ->exists();

        abort_if($exists, 422, 'An evaluation scheme already exists with this code.');

        $id = DB::table('evaluation_schemes')->insertGetId(
            $this->onlyColumns('evaluation_schemes', array_merge($data, [
                'tenant_id' => $tenantId,
                'evaluation_mode_code' => $data['evaluation_mode_code'] ?? 'combined',
                'total_weightage_percentage' => $data['total_weightage_percentage'] ?? 100,
                'status_code' => $data['status_code'] ?? 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]))
        );

        return $this->detail($id);
    }

    public function updateScheme(int $schemeId, array $data): array
    {
        $tenantId = $this->tenantId();

        $existing = $this->scheme($tenantId, $schemeId);
        $merged = array_merge((array) $existing, $data);

        $this->validateRuleSet($tenantId, $merged['examination_rule_set_id'] ?? null);

        if (!empty($data['scheme_code']) && $data['scheme_code'] !== $existing->scheme_code) {
            $duplicate = DB::table('evaluation_schemes')
                ->where('tenant_id', $tenantId)
                ->where('scheme_code', $data['scheme_code'])
                ->where('id', '!=', $schemeId)
                ->whereNull('deleted_at')
                ->exists();

            abort_if($duplicate, 422, 'Another evaluation scheme already uses this code.');
        }

        DB::table('evaluation_schemes')
            ->where('tenant_id', $tenantId)
            ->where('id', $schemeId)
            ->update($this->onlyColumns('evaluation_schemes', array_merge($data, [
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ])));

        return $this->detail($schemeId);
    }

    public function setStatus(int $schemeId, string $statusCode): array
    {
        $tenantId = $this->tenantId();

        $this->scheme($tenantId, $schemeId);

        DB::table('evaluation_schemes')
            ->where('tenant_id', $tenantId)
            ->where('id', $schemeId)
            ->update([
                'status_code' => $statusCode,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return $this->detail($schemeId);
    }

    public function saveStructure(int $schemeId, array $components): array
    {
        $tenantId = $this->tenantId();
        $scheme = $this->scheme($tenantId, $schemeId);

        $this->validateStructure($scheme, $components);

        return DB::transaction(function () use ($tenantId, $schemeId, $components) {
            $submittedComponentIds = collect($components)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            $componentDeleteQuery = DB::table('evaluation_scheme_components')
                ->where('tenant_id', $tenantId)
                ->where('evaluation_scheme_id', $schemeId);

            if (!empty($submittedComponentIds)) {
                $componentDeleteQuery->whereNotIn('id', $submittedComponentIds);
            }

            $componentDeleteQuery->delete();

            foreach ($components as $component) {
                $componentPayload = $this->onlyColumns(
                    'evaluation_scheme_components',
                    array_merge($component, [
                        'tenant_id' => $tenantId,
                        'evaluation_scheme_id' => $schemeId,
                        'is_mandatory' => (bool) ($component['is_mandatory'] ?? true),
                        'requires_separate_pass' => (bool) ($component['requires_separate_pass'] ?? false),
                        'status_code' => $component['status_code'] ?? 'active',
                        'updated_by' => auth()->id(),
                        'updated_at' => now(),
                    ])
                );

                $componentId = $component['id'] ?? null;

                if ($componentId) {
                    unset($componentPayload['id']);

                    DB::table('evaluation_scheme_components')
                        ->where('tenant_id', $tenantId)
                        ->where('evaluation_scheme_id', $schemeId)
                        ->where('id', $componentId)
                        ->update($componentPayload);
                } else {
                    $componentPayload['created_by'] = auth()->id();
                    $componentPayload['created_at'] = now();

                    $componentId = DB::table('evaluation_scheme_components')
                        ->insertGetId($componentPayload);
                }

                $this->syncItems(
                    $tenantId,
                    $componentId,
                    $component['items'] ?? []
                );
            }

            return $this->detail($schemeId);
        });
    }

    private function syncItems(int $tenantId, int $componentId, array $items): void
    {
        $submittedItemIds = collect($items)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $itemDeleteQuery = DB::table('evaluation_scheme_component_items')
            ->where('tenant_id', $tenantId)
            ->where('evaluation_scheme_component_id', $componentId);

        if (!empty($submittedItemIds)) {
            $itemDeleteQuery->whereNotIn('id', $submittedItemIds);
        }

        $itemDeleteQuery->delete();

        foreach ($items as $item) {
            $payload = $this->onlyColumns(
                'evaluation_scheme_component_items',
                array_merge($item, [
                    'tenant_id' => $tenantId,
                    'evaluation_scheme_component_id' => $componentId,
                    'is_mandatory' => (bool) ($item['is_mandatory'] ?? true),
                    'status_code' => $item['status_code'] ?? 'active',
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ])
            );

            if (!empty($item['id'])) {
                unset($payload['id']);

                DB::table('evaluation_scheme_component_items')
                    ->where('tenant_id', $tenantId)
                    ->where('evaluation_scheme_component_id', $componentId)
                    ->where('id', $item['id'])
                    ->update($payload);

                continue;
            }

            $payload['created_by'] = auth()->id();
            $payload['created_at'] = now();

            DB::table('evaluation_scheme_component_items')->insert($payload);
        }
    }

    private function validateStructure(object $scheme, array $components): void
    {
        $componentCodes = collect($components)
            ->pluck('component_code')
            ->map(fn ($value) => strtoupper(trim((string) $value)));

        abort_if(
            $componentCodes->count() !== $componentCodes->unique()->count(),
            422,
            'Component codes must be unique.'
        );

        $sortOrders = collect($components)->pluck('sort_order');

        abort_if(
            $sortOrders->count() !== $sortOrders->unique()->count(),
            422,
            'Component sort order must be unique.'
        );

        $totalComponentWeightage = round(
            collect($components)->sum(
                fn ($component) => (float) $component['weightage_percentage']
            ),
            2
        );

        abort_if(
            $totalComponentWeightage !== (float) $scheme->total_weightage_percentage,
            422,
            "Component weightage must total exactly {$scheme->total_weightage_percentage}%."
        );

        foreach ($components as $component) {
            $items = $component['items'] ?? [];

            if (empty($items)) {
                continue;
            }

            $itemCodes = collect($items)
                ->pluck('item_code')
                ->map(fn ($value) => strtoupper(trim((string) $value)));

            abort_if(
                $itemCodes->count() !== $itemCodes->unique()->count(),
                422,
                "Item codes must be unique within component {$component['component_name']}."
            );

            $itemSortOrders = collect($items)->pluck('sort_order');

            abort_if(
                $itemSortOrders->count() !== $itemSortOrders->unique()->count(),
                422,
                "Item sort order must be unique within component {$component['component_name']}."
            );

            $itemWeightage = round(
                collect($items)->sum(
                    fn ($item) => (float) $item['weightage_percentage']
                ),
                2
            );

            abort_if(
                $itemWeightage !== 100.00,
                422,
                "Items under {$component['component_name']} must total exactly 100%."
            );

            if (
                $scheme->evaluation_mode_code === 'combined'
                && ($component['evaluation_part_code'] ?? 'combined') !== 'combined'
            ) {
                abort(
                    422,
                    'A combined evaluation scheme can only contain combined components.'
                );
            }
        }
    }

    private function components(int $tenantId, int $schemeId): array
    {
        $components = DB::table('evaluation_scheme_components')
            ->where('tenant_id', $tenantId)
            ->where('evaluation_scheme_id', $schemeId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        foreach ($components as &$component) {
            $component['items'] = DB::table('evaluation_scheme_component_items')
                ->where('tenant_id', $tenantId)
                ->where('evaluation_scheme_component_id', $component['id'])
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        return $components;
    }

    private function validateRuleSet(int $tenantId, ?int $ruleSetId): void
    {
        if (!$ruleSetId) {
            return;
        }

        $exists = DB::table('examination_rule_sets')
            ->where('tenant_id', $tenantId)
            ->where('id', $ruleSetId)
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->exists();

        abort_if(!$exists, 422, 'Select an active examination rule set.');
    }

    private function scheme(int $tenantId, int $schemeId): object
    {
        $scheme = DB::table('evaluation_schemes')
            ->where('tenant_id', $tenantId)
            ->where('id', $schemeId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$scheme, 404, 'Evaluation scheme not found.');

        return $scheme;
    }

    private function onlyColumns(string $table, array $payload): array
    {
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