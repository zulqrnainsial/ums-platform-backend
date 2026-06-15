<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionMeritMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLookups();
        $this->seedDynamicEntities();
    }

    private function seedLookups(): void
    {
        $this->lookupCategory('merit_formula_type', 'Merit Formula Type');
        $this->lookupValue('merit_formula_type', 'standard', 'Standard');
        $this->lookupValue('merit_formula_type', 'session_specific', 'Session Specific');
        $this->lookupValue('merit_formula_type', 'program_specific', 'Program Specific');
        $this->lookupValue('merit_formula_type', 'quota_specific', 'Quota Specific');
        $this->lookupValue('merit_formula_type', 'group_specific', 'Preference Group Specific');

        $this->lookupCategory('merit_component_type', 'Merit Component Type');
        $this->lookupValue('merit_component_type', 'qualification', 'Qualification');
        $this->lookupValue('merit_component_type', 'test', 'Admission Test');
        $this->lookupValue('merit_component_type', 'interview', 'Interview');
        $this->lookupValue('merit_component_type', 'manual', 'Manual Score');
        $this->lookupValue('merit_component_type', 'bonus', 'Bonus Marks');
        $this->lookupValue('merit_component_type', 'deduction', 'Deduction');

        $this->lookupCategory('merit_source_type', 'Merit Source Type');
        $this->lookupValue('merit_source_type', 'applicant_qualification', 'Applicant Qualification');
        $this->lookupValue('merit_source_type', 'applicant_test_result', 'Applicant Test Result');
        $this->lookupValue('merit_source_type', 'assessment_result', 'Internal Assessment Result');
        $this->lookupValue('merit_source_type', 'manual_entry', 'Manual Entry');
        $this->lookupValue('merit_source_type', 'fixed_bonus', 'Fixed Bonus');
        $this->lookupValue('merit_source_type', 'fixed_deduction', 'Fixed Deduction');

        $this->lookupCategory('merit_calculation_method', 'Merit Calculation Method');
        $this->lookupValue('merit_calculation_method', 'percentage_of_marks', 'Percentage of Marks');
        $this->lookupValue('merit_calculation_method', 'obtained_marks', 'Obtained Marks');
        $this->lookupValue('merit_calculation_method', 'normalized_marks', 'Normalized Marks');
        $this->lookupValue('merit_calculation_method', 'fixed_marks', 'Fixed Marks');
        $this->lookupValue('merit_calculation_method', 'best_of_tests', 'Best of Tests');
        $this->lookupValue('merit_calculation_method', 'latest_test', 'Latest Test');

        $this->lookupCategory('merit_applicability_scope', 'Merit Applicability Scope');
        $this->lookupValue('merit_applicability_scope', 'session', 'Admission Session');
        $this->lookupValue('merit_applicability_scope', 'preference_group', 'Preference Group');
        $this->lookupValue('merit_applicability_scope', 'offered_program', 'Offered Program');
        $this->lookupValue('merit_applicability_scope', 'quota', 'Quota / Seat Category');

        $this->lookupCategory('record_status', 'Record Status');
        $this->lookupValue('record_status', 'draft', 'Draft');
        $this->lookupValue('record_status', 'active', 'Active');
        $this->lookupValue('record_status', 'inactive', 'Inactive');
        $this->lookupValue('record_status', 'archived', 'Archived');
    }

    private function seedDynamicEntities(): void
    {
        /*
         | This seeder assumes your dynamic CRUD metadata tables are:
         | dynamic_entities
         | dynamic_fields
         |
         | If your project has different table names, apply the same fields
         | in your existing AdmissionDynamicMetadataSeeder.
         */
        if (!Schema::hasTable('dynamic_entities') || !Schema::hasTable('dynamic_fields')) {
            $this->command?->warn('dynamic_entities/dynamic_fields not found. Lookup data seeded only.');
            return;
        }

        $formulaEntityId = $this->entity([
            'entity_key' => 'admission-merit-formulas',
            'table_name' => 'admission_merit_formulas',
            'title' => 'Merit Formulas',
            'description' => 'Define admission merit formulas.',
            'module_code' => 'admission',
            'route_path' => '/crud/admission-merit-formulas',
            'permission_prefix' => 'admission.merit_formula',
            'is_active' => 1,
        ]);

        $this->field($formulaEntityId, [
            'field_name' => 'admission_session_id',
            'label' => 'Admission Session',
            'control_type' => 'select',
            'option_source' => 'dynamic',
            'option_endpoint' => '/dynamic-options/admission-sessions',
            'is_required' => false,
            'display_order' => 10,
            'show_in_list' => true,
        ]);

        $this->field($formulaEntityId, [
            'field_name' => 'code',
            'label' => 'Formula Code',
            'control_type' => 'text',
            'is_required' => true,
            'display_order' => 20,
            'show_in_list' => true,
        ]);

        $this->field($formulaEntityId, [
            'field_name' => 'title',
            'label' => 'Formula Title',
            'control_type' => 'text',
            'is_required' => true,
            'display_order' => 30,
            'show_in_list' => true,
        ]);

        $this->field($formulaEntityId, [
            'field_name' => 'formula_type_code',
            'label' => 'Formula Type',
            'control_type' => 'select',
            'option_source' => 'lookup',
            'option_category_code' => 'merit_formula_type',
            'value_field' => 'code',
            'label_field' => 'name',
            'default_value' => 'standard',
            'is_required' => true,
            'display_order' => 40,
            'show_in_list' => true,
        ]);

        $this->field($formulaEntityId, [
            'field_name' => 'total_weight',
            'label' => 'Total Weight',
            'control_type' => 'number',
            'default_value' => '100',
            'is_required' => true,
            'display_order' => 50,
            'show_in_list' => true,
        ]);

        $this->field($formulaEntityId, [
            'field_name' => 'passing_merit_score',
            'label' => 'Passing Merit Score',
            'control_type' => 'number',
            'is_required' => false,
            'display_order' => 60,
            'show_in_list' => false,
        ]);

        $this->field($formulaEntityId, [
            'field_name' => 'rounding_precision',
            'label' => 'Rounding Precision',
            'control_type' => 'number',
            'default_value' => '2',
            'is_required' => true,
            'display_order' => 70,
            'show_in_list' => false,
        ]);

        $this->field($formulaEntityId, [
            'field_name' => 'status_code',
            'label' => 'Status',
            'control_type' => 'select',
            'option_source' => 'lookup',
            'option_category_code' => 'record_status',
            'value_field' => 'code',
            'label_field' => 'name',
            'default_value' => 'active',
            'is_required' => true,
            'display_order' => 90,
            'show_in_list' => true,
        ]);

        $componentEntityId = $this->entity([
            'entity_key' => 'admission-merit-formula-components',
            'table_name' => 'admission_merit_formula_components',
            'title' => 'Merit Formula Components',
            'description' => 'Define formula components like qualification, test, interview and bonus.',
            'module_code' => 'admission',
            'route_path' => '/crud/admission-merit-formula-components',
            'permission_prefix' => 'admission.merit_formula_component',
            'is_active' => 1,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'admission_merit_formula_id',
            'label' => 'Merit Formula',
            'control_type' => 'select',
            'option_source' => 'dynamic',
            'option_endpoint' => '/dynamic-options/admission-merit-formulas',
            'is_required' => true,
            'display_order' => 10,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'code',
            'label' => 'Component Code',
            'control_type' => 'text',
            'is_required' => true,
            'display_order' => 20,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'title',
            'label' => 'Component Title',
            'control_type' => 'text',
            'is_required' => true,
            'display_order' => 30,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'component_type_code',
            'label' => 'Component Type',
            'control_type' => 'select',
            'option_source' => 'lookup',
            'option_category_code' => 'merit_component_type',
            'value_field' => 'code',
            'label_field' => 'name',
            'is_required' => true,
            'display_order' => 40,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'source_type_code',
            'label' => 'Source Type',
            'control_type' => 'select',
            'option_source' => 'lookup',
            'option_category_code' => 'merit_source_type',
            'value_field' => 'code',
            'label_field' => 'name',
            'is_required' => true,
            'display_order' => 50,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'source_key',
            'label' => 'Source Key',
            'control_type' => 'text',
            'is_required' => false,
            'display_order' => 60,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'calculation_method_code',
            'label' => 'Calculation Method',
            'control_type' => 'select',
            'option_source' => 'lookup',
            'option_category_code' => 'merit_calculation_method',
            'value_field' => 'code',
            'label_field' => 'name',
            'default_value' => 'percentage_of_marks',
            'is_required' => true,
            'display_order' => 70,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'weight',
            'label' => 'Weight',
            'control_type' => 'number',
            'default_value' => '0',
            'is_required' => true,
            'display_order' => 80,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'max_raw_marks',
            'label' => 'Max Raw Marks',
            'control_type' => 'number',
            'is_required' => false,
            'display_order' => 90,
            'show_in_list' => false,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'normalize_to',
            'label' => 'Normalize To',
            'control_type' => 'number',
            'default_value' => '100',
            'is_required' => true,
            'display_order' => 100,
            'show_in_list' => false,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'minimum_required_score',
            'label' => 'Minimum Required Score',
            'control_type' => 'number',
            'is_required' => false,
            'display_order' => 110,
            'show_in_list' => false,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'is_required',
            'label' => 'Required',
            'control_type' => 'switch',
            'default_value' => '0',
            'display_order' => 120,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'include_in_total',
            'label' => 'Include In Total',
            'control_type' => 'switch',
            'default_value' => '1',
            'display_order' => 130,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'display_order',
            'label' => 'Display Order',
            'control_type' => 'number',
            'default_value' => '1',
            'display_order' => 140,
            'show_in_list' => true,
        ]);

        $this->field($componentEntityId, [
            'field_name' => 'status_code',
            'label' => 'Status',
            'control_type' => 'select',
            'option_source' => 'lookup',
            'option_category_code' => 'record_status',
            'value_field' => 'code',
            'label_field' => 'name',
            'default_value' => 'active',
            'is_required' => true,
            'display_order' => 150,
            'show_in_list' => true,
        ]);

        $appEntityId = $this->entity([
            'entity_key' => 'admission-merit-formula-applicabilities',
            'table_name' => 'admission_merit_formula_applicabilities',
            'title' => 'Merit Formula Applicability',
            'description' => 'Assign merit formulas to sessions, preference groups, offered programs or quotas.',
            'module_code' => 'admission',
            'route_path' => '/crud/admission-merit-formula-applicabilities',
            'permission_prefix' => 'admission.merit_formula_applicability',
            'is_active' => 1,
        ]);

        $this->field($appEntityId, [
            'field_name' => 'admission_merit_formula_id',
            'label' => 'Merit Formula',
            'control_type' => 'select',
            'option_source' => 'dynamic',
            'option_endpoint' => '/dynamic-options/admission-merit-formulas',
            'is_required' => true,
            'display_order' => 10,
            'show_in_list' => true,
        ]);

        $this->field($appEntityId, [
            'field_name' => 'applicability_scope_code',
            'label' => 'Applicability Scope',
            'control_type' => 'select',
            'option_source' => 'lookup',
            'option_category_code' => 'merit_applicability_scope',
            'value_field' => 'code',
            'label_field' => 'name',
            'is_required' => true,
            'display_order' => 20,
            'show_in_list' => true,
        ]);

        $this->field($appEntityId, [
            'field_name' => 'admission_session_id',
            'label' => 'Admission Session',
            'control_type' => 'select',
            'option_source' => 'dynamic',
            'option_endpoint' => '/dynamic-options/admission-sessions',
            'is_required' => false,
            'display_order' => 30,
            'show_in_list' => true,
        ]);

        $this->field($appEntityId, [
            'field_name' => 'admission_preference_group_id',
            'label' => 'Preference Group',
            'control_type' => 'select',
            'option_source' => 'dynamic',
            'option_endpoint' => '/dynamic-options/admission-preference-groups',
            'is_required' => false,
            'display_order' => 40,
            'show_in_list' => true,
        ]);

        $this->field($appEntityId, [
            'field_name' => 'offered_program_id',
            'label' => 'Offered Program',
            'control_type' => 'select',
            'option_source' => 'dynamic',
            'option_endpoint' => '/dynamic-options/offered-programs',
            'is_required' => false,
            'display_order' => 50,
            'show_in_list' => true,
        ]);

        $this->field($appEntityId, [
            'field_name' => 'program_quota_seat_id',
            'label' => 'Quota / Seat Category',
            'control_type' => 'select',
            'option_source' => 'dynamic',
            'option_endpoint' => '/dynamic-options/program-quota-seats',
            'is_required' => false,
            'display_order' => 60,
            'show_in_list' => true,
        ]);

        $this->field($appEntityId, [
            'field_name' => 'is_default',
            'label' => 'Default Formula',
            'control_type' => 'switch',
            'default_value' => '0',
            'display_order' => 70,
            'show_in_list' => true,
        ]);

        $this->field($appEntityId, [
            'field_name' => 'priority',
            'label' => 'Priority',
            'control_type' => 'number',
            'default_value' => '100',
            'display_order' => 80,
            'show_in_list' => true,
        ]);

        $this->field($appEntityId, [
            'field_name' => 'status_code',
            'label' => 'Status',
            'control_type' => 'select',
            'option_source' => 'lookup',
            'option_category_code' => 'record_status',
            'value_field' => 'code',
            'label_field' => 'name',
            'default_value' => 'active',
            'is_required' => true,
            'display_order' => 90,
            'show_in_list' => true,
        ]);
    }

    private function lookupCategory(string $code, string $name): void
    {
        if (!Schema::hasTable('lookup_categories')) {
            return;
        }

        $payload = $this->filterColumns('lookup_categories', [
            'code' => $code,
            'name' => $name,
            'title' => $name,
            'description' => $name,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $where = $this->filterColumns('lookup_categories', ['code' => $code]);

        DB::table('lookup_categories')->updateOrInsert($where, $payload);
    }

    private function lookupValue(string $categoryCode, string $code, string $name): void
    {
        if (!Schema::hasTable('lookup_values')) {
            return;
        }

        $categoryId = null;

        if (Schema::hasTable('lookup_categories')) {
            $categoryId = DB::table('lookup_categories')
                ->where('code', $categoryCode)
                ->value('id');
        }

        $where = [
            'code' => $code,
        ];

        if (Schema::hasColumn('lookup_values', 'lookup_category_id')) {
            $where['lookup_category_id'] = $categoryId;
        }

        if (Schema::hasColumn('lookup_values', 'category_code')) {
            $where['category_code'] = $categoryCode;
        }

        $payload = [
            'lookup_category_id' => $categoryId,
            'category_code' => $categoryCode,
            'code' => $code,
            'name' => $name,
            'title' => $name,
            'label' => $name,
            'value' => $code,
            'is_active' => 1,
            'sort_order' => 1,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('lookup_values')->updateOrInsert(
            $this->filterColumns('lookup_values', $where),
            $this->filterColumns('lookup_values', $payload)
        );
    }

    private function entity(array $payload): int
    {
        $where = $this->filterColumns('dynamic_entities', [
            'entity_key' => $payload['entity_key'],
        ]);

        $payload = $this->filterColumns('dynamic_entities', array_merge($payload, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        DB::table('dynamic_entities')->updateOrInsert($where, $payload);

        return (int) DB::table('dynamic_entities')
            ->where($where)
            ->value('id');
    }

    private function field(int $entityId, array $payload): void
    {
        $where = $this->filterColumns('dynamic_fields', [
            'dynamic_entity_id' => $entityId,
            'field_name' => $payload['field_name'],
        ]);

        $payload = $this->filterColumns('dynamic_fields', array_merge($payload, [
            'dynamic_entity_id' => $entityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        DB::table('dynamic_fields')->updateOrInsert($where, $payload);
    }

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->toArray();
    }
}