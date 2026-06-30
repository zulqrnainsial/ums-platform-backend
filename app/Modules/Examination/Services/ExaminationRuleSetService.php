<?php

namespace App\Modules\Examination\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExaminationRuleSetService
{
    public function context(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        return [
            'programs' => DB::table('programs')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get([
                    'id as value',
                    'code',
                    'name as label',
                    'department_id',
                    'faculty_id',
                ])
                ->map(fn ($row) => (array) $row)
                ->all(),

            'curriculums' => DB::table('curriculums')
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['active', 'draft'])
                ->whereNull('deleted_at')
                ->when(
                    !empty($filters['program_id']),
                    fn ($query) => $query->where('program_id', $filters['program_id'])
                )
                ->orderByDesc('is_current')
                ->orderBy('name')
                ->get([
                    'id as value',
                    'program_id',
                    'code',
                    'name as label',
                    'version',
                    'is_current',
                ])
                ->map(fn ($row) => (array) $row)
                ->all(),

            'student_batches' => DB::table('student_batches')
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['active', 'completed'])
                ->whereNull('deleted_at')
                ->when(
                    !empty($filters['program_id']),
                    fn ($query) => $query->where('program_id', $filters['program_id'])
                )
                ->when(
                    !empty($filters['curriculum_id']),
                    fn ($query) => $query->where('curriculum_id', $filters['curriculum_id'])
                )
                ->orderByDesc('id')
                ->get([
                    'id as value',
                    'program_id',
                    'curriculum_id',
                    'academic_session_id',
                    'code',
                    'name as label',
                    'status',
                ])
                ->map(fn ($row) => (array) $row)
                ->all(),

            'academic_sessions' => DB::table('academic_sessions')
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['planned', 'active', 'closed'])
                ->whereNull('deleted_at')
                ->orderByDesc('is_current')
                ->orderByDesc('id')
                ->get([
                    'id as value',
                    'code',
                    'name as label',
                    'status',
                    'is_current',
                ])
                ->map(fn ($row) => (array) $row)
                ->all(),

            'academic_terms' => DB::table('academic_terms')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->when(
                    !empty($filters['program_id']),
                    fn ($query) => $query->where('program_id', $filters['program_id'])
                )
                ->orderBy('term_number')
                ->get([
                    'id as value',
                    'program_id',
                    'code',
                    'name as label',
                    'term_number',
                    'term_type',
                ])
                ->map(fn ($row) => (array) $row)
                ->all(),

            'grading_methods' => [
                ['value' => 'absolute', 'label' => 'Absolute Grading'],
                ['value' => 'relative', 'label' => 'Relative Grading / Ready Reckoner'],
                ['value' => 'pass_fail', 'label' => 'Pass / Fail'],
            ],

            'marks_bases' => [
                ['value' => 'curriculum_subject_marks', 'label' => 'Use Curriculum Subject Marks'],
                ['value' => 'credit_hour_based', 'label' => 'Credit-Hour Based Marks'],
                ['value' => 'fixed_marks', 'label' => 'Fixed Marks Per Subject'],
                ['value' => 'custom_marks', 'label' => 'Custom Evaluation Scheme Marks'],
            ],

            'theory_practical_evaluation_modes' => [
                ['value' => 'combined', 'label' => 'Combined Theory and Practical Evaluation'],
                ['value' => 'separate_pass_required', 'label' => 'Theory and Practical Must Pass Separately'],
            ],

            'subject_pass_bases' => [
                ['value' => 'marks', 'label' => 'Marks / Percentage'],
                ['value' => 'gpa', 'label' => 'Grade Point'],
                ['value' => 'obe_attainment', 'label' => 'OBE Attainment'],
                ['value' => 'marks_and_obe', 'label' => 'Marks and OBE Attainment'],
                ['value' => 'gpa_and_obe', 'label' => 'GPA and OBE Attainment'],
            ],
        ];
    }

    public function ruleSets(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('examination_rule_sets as ers')
            ->where('ers.tenant_id', $tenantId)
            ->whereNull('ers.deleted_at');

        if (!empty($filters['status_code'])) {
            $query->where('ers.status_code', $filters['status_code']);
        }

        if (!empty($filters['grading_method_code'])) {
            $query->where(
                'ers.grading_method_code',
                $filters['grading_method_code']
            );
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';

            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('ers.rule_set_code', 'like', $search)
                    ->orWhere('ers.rule_set_name', 'like', $search);
            });
        }

        return $query
            ->select([
                'ers.*',
                DB::raw('(
                    SELECT COUNT(*)
                    FROM examination_rule_set_bindings ersb
                    WHERE ersb.examination_rule_set_id = ers.id
                      AND ersb.deleted_at IS NULL
                ) as bindings_count'),
            ])
            ->orderByDesc('ers.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function ruleSetDetail(int $ruleSetId): array
    {
        $tenantId = $this->tenantId();

        $ruleSet = DB::table('examination_rule_sets')
            ->where('tenant_id', $tenantId)
            ->where('id', $ruleSetId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$ruleSet, 404, 'Examination rule set not found.');

        return [
            'rule_set' => (array) $ruleSet,
            'bindings' => $this->bindingRows($tenantId, $ruleSetId),
            'grading_schemes' => DB::table('grading_schemes')
                ->where('tenant_id', $tenantId)
                ->where('examination_rule_set_id', $ruleSetId)
                ->whereNull('deleted_at')
                ->orderByDesc('is_default')
                ->orderBy('scheme_name')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'evaluation_schemes' => DB::table('evaluation_schemes')
                ->where('tenant_id', $tenantId)
                ->where('examination_rule_set_id', $ruleSetId)
                ->whereNull('deleted_at')
                ->orderBy('scheme_name')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
        ];
    }

    public function createRuleSet(array $data): array
    {
        $tenantId = $this->tenantId();

        $this->validateRuleSetBusinessRules($data);

        $exists = DB::table('examination_rule_sets')
            ->where('tenant_id', $tenantId)
            ->where('rule_set_code', $data['rule_set_code'])
            ->whereNull('deleted_at')
            ->exists();

        abort_if(
            $exists,
            422,
            'An examination rule set already exists with this code.'
        );

        $payload = $this->ruleSetPayload($data, true);

        $id = DB::table('examination_rule_sets')->insertGetId($payload);

        return $this->ruleSetDetail($id);
    }

    public function updateRuleSet(int $ruleSetId, array $data): array
    {
        $tenantId = $this->tenantId();

        $existing = DB::table('examination_rule_sets')
            ->where('tenant_id', $tenantId)
            ->where('id', $ruleSetId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$existing, 404, 'Examination rule set not found.');

        $merged = array_merge((array) $existing, $data);

        $this->validateRuleSetBusinessRules($merged);

        if (!empty($data['rule_set_code'])) {
            $duplicate = DB::table('examination_rule_sets')
                ->where('tenant_id', $tenantId)
                ->where('rule_set_code', $data['rule_set_code'])
                ->where('id', '!=', $ruleSetId)
                ->whereNull('deleted_at')
                ->exists();

            abort_if(
                $duplicate,
                422,
                'Another examination rule set already uses this code.'
            );
        }

        DB::table('examination_rule_sets')
            ->where('tenant_id', $tenantId)
            ->where('id', $ruleSetId)
            ->update($this->ruleSetPayload($data, false));

        return $this->ruleSetDetail($ruleSetId);
    }

    public function setRuleSetStatus(
        int $ruleSetId,
        string $statusCode
    ): array {
        $tenantId = $this->tenantId();

        $exists = DB::table('examination_rule_sets')
            ->where('tenant_id', $tenantId)
            ->where('id', $ruleSetId)
            ->whereNull('deleted_at')
            ->exists();

        abort_if(!$exists, 404, 'Examination rule set not found.');

        DB::table('examination_rule_sets')
            ->where('tenant_id', $tenantId)
            ->where('id', $ruleSetId)
            ->update([
                'status_code' => $statusCode,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return $this->ruleSetDetail($ruleSetId);
    }

    public function bindings(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('examination_rule_set_bindings as ersb')
            ->join(
                'examination_rule_sets as ers',
                'ers.id',
                '=',
                'ersb.examination_rule_set_id'
            )
            ->leftJoin('programs as p', 'p.id', '=', 'ersb.program_id')
            ->leftJoin('curriculums as c', 'c.id', '=', 'ersb.curriculum_id')
            ->leftJoin('student_batches as sb', 'sb.id', '=', 'ersb.student_batch_id')
            ->leftJoin('academic_sessions as acs', 'acs.id', '=', 'ersb.academic_session_id')
            ->leftJoin('academic_terms as act', 'act.id', '=', 'ersb.academic_term_id')
            ->where('ersb.tenant_id', $tenantId)
            ->whereNull('ersb.deleted_at');

        foreach ([
            'examination_rule_set_id',
            'program_id',
            'curriculum_id',
            'student_batch_id',
            'academic_session_id',
            'academic_term_id',
            'is_active',
        ] as $field) {
            if ($filters[$field] ?? null !== null && $filters[$field] !== '') {
                $query->where("ersb.$field", $filters[$field]);
            }
        }

        return $query
            ->select([
                'ersb.*',
                'ers.rule_set_code',
                'ers.rule_set_name',
                'p.code as program_code',
                'p.name as program_name',
                'c.code as curriculum_code',
                'c.name as curriculum_name',
                'sb.code as student_batch_code',
                'sb.name as student_batch_name',
                'acs.code as academic_session_code',
                'acs.name as academic_session_name',
                'act.code as academic_term_code',
                'act.name as academic_term_name',
            ])
            ->orderByDesc('ersb.is_active')
            ->orderByDesc('ersb.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function saveBinding(array $data): array
    {
        $tenantId = $this->tenantId();

        $this->assertRuleSetExists(
            $tenantId,
            (int) $data['examination_rule_set_id']
        );

        $this->validateBindingScope($tenantId, $data);

        $bindingId = $data['id'] ?? null;

        $existing = $bindingId
            ? DB::table('examination_rule_set_bindings')
                ->where('tenant_id', $tenantId)
                ->where('id', $bindingId)
                ->whereNull('deleted_at')
                ->first()
            : null;

        if ($bindingId) {
            abort_if(!$existing, 404, 'Examination rule set binding not found.');
        }

        $payload = $this->onlyColumns(
            'examination_rule_set_bindings',
            array_merge($data, [
                'tenant_id' => $tenantId,
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : true,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ])
        );

        unset($payload['id']);

        if ($existing) {
            DB::table('examination_rule_set_bindings')
                ->where('tenant_id', $tenantId)
                ->where('id', $existing->id)
                ->update($payload);

            return $this->bindingDetail((int) $existing->id);
        }

        $payload['created_by'] = auth()->id();
        $payload['created_at'] = now();

        $id = DB::table('examination_rule_set_bindings')
            ->insertGetId($payload);

        return $this->bindingDetail($id);
    }

    private function ruleSetPayload(array $data, bool $isCreate): array
    {
        $defaults = [
            'gpa_enabled' => true,
            'obe_enabled' => false,
            'grading_method_code' => 'absolute',
            'marks_basis_code' => 'curriculum_subject_marks',
            'theory_practical_evaluation_code' => 'combined',
            'subject_pass_basis_code' => 'marks',
            'promotion_enabled' => true,
            'probation_enabled' => true,
            'detention_enabled' => true,
            'drop_enabled' => true,
            're_registration_enabled' => true,
            'improvement_enabled' => true,
            'transcript_enabled' => true,
            'include_obe_in_result_decision' => false,
            'status_code' => 'active',
        ];

        $payload = $this->onlyColumns(
            'examination_rule_sets',
            array_merge(
                $isCreate ? $defaults : [],
                $data,
                [
                    'tenant_id' => $this->tenantId(),
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]
            )
        );

        if ($isCreate) {
            $payload['created_by'] = auth()->id();
            $payload['created_at'] = now();
        }

        return $payload;
    }

    private function validateRuleSetBusinessRules(array $data): void
    {
        $gradingMethod = $data['grading_method_code'] ?? 'absolute';
        $marksBasis = $data['marks_basis_code'] ?? 'curriculum_subject_marks';
        $passBasis = $data['subject_pass_basis_code'] ?? 'marks';
        $separateRule = ($data['theory_practical_evaluation_code'] ?? 'combined')
            === 'separate_pass_required';

        abort_if(
            $gradingMethod === 'relative'
                && empty($data['gpa_enabled']),
            422,
            'Relative grading requires GPA to be enabled.'
        );

        abort_if(
            $marksBasis === 'credit_hour_based'
                && empty($data['marks_per_credit_hour']),
            422,
            'Marks per credit hour is required for credit-hour based marks.'
        );

        abort_if(
            $marksBasis === 'fixed_marks'
                && empty($data['fixed_total_marks']),
            422,
            'Fixed total marks is required for fixed-marks evaluation.'
        );

        abort_if(
            in_array($passBasis, ['obe_attainment', 'marks_and_obe', 'gpa_and_obe'], true)
                && empty($data['obe_enabled']),
            422,
            'OBE must be enabled when the subject pass basis includes OBE attainment.'
        );

        abort_if(
            $separateRule
                && (
                    !array_key_exists('minimum_theory_percentage', $data)
                    || !array_key_exists('minimum_practical_percentage', $data)
                    || $data['minimum_theory_percentage'] === null
                    || $data['minimum_practical_percentage'] === null
                ),
            422,
            'Theory and practical minimum percentages are required when separate passing is enabled.'
        );

        abort_if(
            !empty($data['improvement_enabled'])
                && (
                    !array_key_exists('improvement_allowed_below_grade_point', $data)
                    || $data['improvement_allowed_below_grade_point'] === null
                ),
            422,
            'Improvement grade-point threshold is required when improvement is enabled.'
        );
    }

    private function validateBindingScope(
        int $tenantId,
        array $data
    ): void {
        foreach ([
            'program_id' => 'programs',
            'curriculum_id' => 'curriculums',
            'student_batch_id' => 'student_batches',
            'academic_session_id' => 'academic_sessions',
            'academic_term_id' => 'academic_terms',
        ] as $field => $table) {
            if (empty($data[$field])) {
                continue;
            }

            $exists = DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where('id', $data[$field])
                ->whereNull('deleted_at')
                ->exists();

            abort_if(
                !$exists,
                422,
                "{$field} is invalid for the active tenant."
            );
        }

        if (!empty($data['curriculum_id']) && !empty($data['program_id'])) {
            $matches = DB::table('curriculums')
                ->where('tenant_id', $tenantId)
                ->where('id', $data['curriculum_id'])
                ->where('program_id', $data['program_id'])
                ->whereNull('deleted_at')
                ->exists();

            abort_if(
                !$matches,
                422,
                'Selected curriculum does not belong to the selected program.'
            );
        }

        if (!empty($data['student_batch_id'])) {
            $batch = DB::table('student_batches')
                ->where('tenant_id', $tenantId)
                ->where('id', $data['student_batch_id'])
                ->whereNull('deleted_at')
                ->first();

            abort_if(!$batch, 422, 'Selected student batch is invalid.');

            if (
                !empty($data['program_id'])
                && (int) $batch->program_id !== (int) $data['program_id']
            ) {
                abort(422, 'Selected student batch does not belong to the selected program.');
            }

            if (
                !empty($data['curriculum_id'])
                && (int) $batch->curriculum_id !== (int) $data['curriculum_id']
            ) {
                abort(422, 'Selected student batch does not belong to the selected curriculum.');
            }
        }

        if (
            !empty($data['academic_term_id'])
            && !empty($data['program_id'])
        ) {
            $termMatches = DB::table('academic_terms')
                ->where('tenant_id', $tenantId)
                ->where('id', $data['academic_term_id'])
                ->where('program_id', $data['program_id'])
                ->whereNull('deleted_at')
                ->exists();

            abort_if(
                !$termMatches,
                422,
                'Selected academic term does not belong to the selected program.'
            );
        }
    }

    private function assertRuleSetExists(
        int $tenantId,
        int $ruleSetId
    ): void {
        $exists = DB::table('examination_rule_sets')
            ->where('tenant_id', $tenantId)
            ->where('id', $ruleSetId)
            ->where('status_code', 'active')
            ->whereNull('deleted_at')
            ->exists();

        abort_if(
            !$exists,
            422,
            'Select an active examination rule set.'
        );
    }

    private function bindingRows(
        int $tenantId,
        int $ruleSetId
    ): array {
        return DB::table('examination_rule_set_bindings as ersb')
            ->leftJoin('programs as p', 'p.id', '=', 'ersb.program_id')
            ->leftJoin('curriculums as c', 'c.id', '=', 'ersb.curriculum_id')
            ->leftJoin('student_batches as sb', 'sb.id', '=', 'ersb.student_batch_id')
            ->leftJoin('academic_sessions as acs', 'acs.id', '=', 'ersb.academic_session_id')
            ->leftJoin('academic_terms as act', 'act.id', '=', 'ersb.academic_term_id')
            ->where('ersb.tenant_id', $tenantId)
            ->where('ersb.examination_rule_set_id', $ruleSetId)
            ->whereNull('ersb.deleted_at')
            ->select([
                'ersb.*',
                'p.code as program_code',
                'p.name as program_name',
                'c.code as curriculum_code',
                'c.name as curriculum_name',
                'sb.code as student_batch_code',
                'sb.name as student_batch_name',
                'acs.code as academic_session_code',
                'acs.name as academic_session_name',
                'act.code as academic_term_code',
                'act.name as academic_term_name',
            ])
            ->orderByDesc('ersb.is_active')
            ->orderByDesc('ersb.id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function bindingDetail(int $bindingId): array
    {
        $tenantId = $this->tenantId();

        $row = $this->bindingRowsQuery($tenantId)
            ->where('ersb.id', $bindingId)
            ->first();

        abort_if(!$row, 404, 'Examination rule set binding not found.');

        return (array) $row;
    }

    private function bindingRowsQuery(int $tenantId)
    {
        return DB::table('examination_rule_set_bindings as ersb')
            ->join(
                'examination_rule_sets as ers',
                'ers.id',
                '=',
                'ersb.examination_rule_set_id'
            )
            ->leftJoin('programs as p', 'p.id', '=', 'ersb.program_id')
            ->leftJoin('curriculums as c', 'c.id', '=', 'ersb.curriculum_id')
            ->leftJoin('student_batches as sb', 'sb.id', '=', 'ersb.student_batch_id')
            ->leftJoin('academic_sessions as acs', 'acs.id', '=', 'ersb.academic_session_id')
            ->leftJoin('academic_terms as act', 'act.id', '=', 'ersb.academic_term_id')
            ->where('ersb.tenant_id', $tenantId)
            ->whereNull('ersb.deleted_at')
            ->select([
                'ersb.*',
                'ers.rule_set_code',
                'ers.rule_set_name',
                'p.code as program_code',
                'p.name as program_name',
                'c.code as curriculum_code',
                'c.name as curriculum_name',
                'sb.code as student_batch_code',
                'sb.name as student_batch_name',
                'acs.code as academic_session_code',
                'acs.name as academic_session_name',
                'act.code as academic_term_code',
                'act.name as academic_term_name',
            ]);
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