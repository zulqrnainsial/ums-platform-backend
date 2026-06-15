<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\AssessmentSubject;
use App\Modules\Assessment\Models\AssessmentTopic;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\QuestionAnswerKey;
use App\Modules\Assessment\Models\QuestionBank;
use App\Modules\Assessment\Models\QuestionOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class QuestionEditorService
{
    public function list(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = Question::query()
            ->with(['bank:id,name', 'subject:id,name', 'topic:id,name'])
            ->where('tenant_id', $tenantId);

        if (!empty($filters['question_bank_id'])) {
            $query->where('question_bank_id', $filters['question_bank_id']);
        }

        if (!empty($filters['assessment_subject_id'])) {
            $query->where('assessment_subject_id', $filters['assessment_subject_id']);
        }

        if (!empty($filters['assessment_topic_id'])) {
            $query->where('assessment_topic_id', $filters['assessment_topic_id']);
        }

        if (!empty($filters['question_type_code'])) {
            $query->where('question_type_code', $filters['question_type_code']);
        }

        if (!empty($filters['difficulty_code'])) {
            $query->where('difficulty_code', $filters['difficulty_code']);
        }

        if (!empty($filters['approval_status_code'])) {
            $query->where('approval_status_code', $filters['approval_status_code']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('question_text', 'like', "%{$search}%")
                    ->orWhere('external_ref_no', 'like', "%{$search}%")
                    ->orWhere('import_batch_no', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->toArray();
    }
public function qualityDashboard(array $filters): array
{
    $tenantId = $this->tenantId();

    $bankColumn = $this->questionBankColumn();
    $subjectColumn = $this->questionSubjectColumn();
    $topicColumn = $this->questionTopicColumn();

    $base = DB::table('questions as q')
        ->where('q.tenant_id', $tenantId);

    if ($bankColumn && !empty($filters['question_bank_id'])) {
    $base->where("q.{$bankColumn}", $filters['question_bank_id']);
}

if ($subjectColumn && !empty($filters['assessment_subject_id'])) {
    $base->where("q.{$subjectColumn}", $filters['assessment_subject_id']);
}

if ($topicColumn && !empty($filters['assessment_topic_id'])) {
    $base->where("q.{$topicColumn}", $filters['assessment_topic_id']);
}

    if (!empty($filters['difficulty_code'])) {
        $base->where('q.difficulty_code', $filters['difficulty_code']);
    }

    if (!empty($filters['bloom_level_code'])) {
        $base->where('q.bloom_level_code', $filters['bloom_level_code']);
    }

    $totalQuestions = (clone $base)->count();

    $missingAnswerKey = (clone $base)
        ->leftJoin('question_answer_keys as ak', 'ak.question_id', '=', 'q.id')
        ->whereNull('ak.id')
        ->distinct('q.id')
        ->count('q.id');

    $missingOptions = (clone $base)
        ->leftJoin('question_options as qo', 'qo.question_id', '=', 'q.id')
        ->whereIn('q.question_type_code', ['mcq_single', 'mcq_multiple', 'true_false'])
        ->whereNull('qo.id')
        ->distinct('q.id')
        ->count('q.id');

    $missingExplanation = (clone $base)
        ->where(function ($q) {
            $q->whereNull('q.explanation')
                ->orWhere('q.explanation', '');
        })
        ->where(function ($q) {
            $q->whereNull('q.explanation_html')
                ->orWhere('q.explanation_html', '');
        })
        ->count();

    $missingDifficulty = (clone $base)
        ->where(function ($q) {
            $q->whereNull('q.difficulty_code')
                ->orWhere('q.difficulty_code', '');
        })
        ->count();

    $missingBloom = (clone $base)
        ->where(function ($q) {
            $q->whereNull('q.bloom_level_code')
                ->orWhere('q.bloom_level_code', '');
        })
        ->count();

    $missingTopic = $topicColumn
    ? (clone $base)->whereNull("q.{$topicColumn}")->count()
    : 0;

    $draftQuestions = (clone $base)
        ->where('q.approval_status_code', 'draft')
        ->count();

    $inactiveQuestions = (clone $base)
        ->where(function ($q) {
            $q->whereNull('q.is_active')
                ->orWhere('q.is_active', false);
        })
        ->count();

    return [
        'summary' => [
            'total_questions' => $totalQuestions,
            'active_questions' => (clone $base)->where('q.is_active', true)->count(),
            'draft_questions' => $draftQuestions,
            'inactive_questions' => $inactiveQuestions,

            'missing_answer_key' => $missingAnswerKey,
            'missing_options' => $missingOptions,
            'missing_explanation' => $missingExplanation,
            'missing_difficulty' => $missingDifficulty,
            'missing_bloom' => $missingBloom,
            'missing_topic' => $missingTopic,

            'quality_score' => $this->qualityScore(
                total: $totalQuestions,
                issues: $missingAnswerKey
                    + $missingOptions
                    + $missingExplanation
                    + $missingDifficulty
                    + $missingBloom
                    + $missingTopic
            ),
        ],

        'by_question_bank' => $bankColumn
    ? $this->groupCount($base, "q.{$bankColumn}", 'qb.name', 'question_banks as qb', 'qb.id', "q.{$bankColumn}")
    : [],

'by_subject' => $subjectColumn
    ? $this->groupCount($base, "q.{$subjectColumn}", 's.name', 'assessment_subjects as s', 's.id', "q.{$subjectColumn}")
    : [],

'by_topic' => $topicColumn
    ? $this->groupCount($base, "q.{$topicColumn}", 't.name', 'assessment_topics as t', 't.id', "q.{$topicColumn}")
    : [],

        'by_question_type' => $this->simpleGroupCount($base, 'q.question_type_code'),

        'by_difficulty' => $this->simpleGroupCount($base, 'q.difficulty_code'),

        'by_cognitive_level' => $this->simpleGroupCount($base, 'q.cognitive_level_code'),

        'by_bloom_level' => $this->simpleGroupCount($base, 'q.bloom_level_code'),

        'quality_issues' => $this->qualityIssueRows($base),
    ];
}
    public function show(int $id): Question
    {
        return Question::query()
            ->with(['options', 'answerKeys', 'bank', 'subject', 'topic'])
            ->where('tenant_id', $this->tenantId())
            ->where('id', $id)
            ->firstOrFail();
    }

    public function save(array $data, ?int $id = null): Question
    {
        return DB::transaction(function () use ($data, $id) {
            $tenantId = $this->tenantId();

            $questionPayload = [
                'tenant_id' => $tenantId,
                'question_bank_id' => $data['question_bank_id'],
                'assessment_subject_id' => $data['assessment_subject_id'] ?? null,
                'assessment_topic_id' => $data['assessment_topic_id'] ?? null,
                'question_type_code' => $data['question_type_code'],
                'difficulty_code' => $data['difficulty_code'] ?? null,
                'cognitive_level_code' => $data['cognitive_level_code'] ?? null,
                'bloom_level_code' => $data['bloom_level_code'] ?? null,
                'obe_level_code' => $data['obe_level_code'] ?? null,
                'learning_outcome_code' => $data['learning_outcome_code'] ?? null,
                'course_outcome_code' => $data['course_outcome_code'] ?? null,
                'metadata_json' => $data['metadata_json'] ?? null,
                'question_text' => $data['question_text'],
                'question_html' => $data['question_html'] ?? null,
                'default_marks' => $data['default_marks'] ?? 1,
                'default_negative_marks' => $data['default_negative_marks'] ?? 0,
                'default_time_seconds' => $data['default_time_seconds'] ?? null,
                'explanation' => $data['explanation'] ?? null,
                'explanation_html' => $data['explanation_html'] ?? null,
                'approval_status_code' => $data['approval_status_code'] ?? 'draft',
                'is_active' => $data['is_active'] ?? true,
                'source_code' => $data['source_code'] ?? 'manual',
                'external_ref_no' => $data['external_ref_no'] ?? null,
                'updated_by' => auth()->id(),
            ];

            if ($id) {
                $question = Question::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $id)
                    ->firstOrFail();

                $question->update($questionPayload);
            } else {
                $questionPayload['created_by'] = auth()->id();
                $question = Question::create($questionPayload);
            }

            $this->syncOptions($question, $data['options'] ?? []);
            $this->syncAnswerKeys($question, $data['answer_keys'] ?? []);

            return $question->fresh(['options', 'answerKeys', 'bank', 'subject', 'topic']);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $question = Question::query()
                ->where('tenant_id', $this->tenantId())
                ->where('id', $id)
                ->firstOrFail();

            $question->options()->delete();
            $question->answerKeys()->delete();
            $question->delete();
        });
    }

    public function bulkImport(array $rows): array
    {
        $tenantId = $this->tenantId();
        $batchNo = 'QB-IMPORT-' . now()->format('YmdHis');

        $created = 0;
        $failed = [];

        DB::transaction(function () use ($rows, $tenantId, $batchNo, &$created, &$failed) {
            foreach ($rows as $index => $row) {
                try {
                    $bank = $this->findBank($tenantId, $row['question_bank_code'] ?? null);
                    $subject = $this->findSubject($tenantId, $row['subject_code'] ?? null);
                    $topic = $this->findTopic($tenantId, $subject?->id, $row['topic_code'] ?? null);

                    $question = Question::create([
                        'tenant_id' => $tenantId,
                        'question_bank_id' => $bank->id,
                        'assessment_subject_id' => $subject?->id,
                        'assessment_topic_id' => $topic?->id,
                        'question_type_code' => $row['question_type_code'] ?? 'mcq_single',
                        'difficulty_code' => $row['difficulty_code'] ?? 'medium',
                        'cognitive_level_code' => $row['cognitive_level_code'] ?? 'understanding',
                        'question_text' => $row['question_text'],
                        'default_marks' => $row['marks'] ?? 1,
                        'default_negative_marks' => $row['negative_marks'] ?? 0,
                        'default_time_seconds' => $row['time_seconds'] ?? null,
                        'explanation' => $row['explanation'] ?? null,
                        'approval_status_code' => 'draft',
                        'is_active' => true,
                        'source_code' => 'bulk_import',
                        'import_batch_no' => $batchNo,
                        'external_ref_no' => $row['external_ref_no'] ?? null,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);

                    $this->createImportedOptions($question, $row);
                    $this->createImportedAnswerKey($question, $row);

                    $created++;
                } catch (\Throwable $e) {
                    $failed[] = [
                        'row' => $row['_excel_row_no'] ?? ($index + 1),
                        'question_text' => $row['question_text'] ?? null,
                        'external_ref_no' => $row['external_ref_no'] ?? null,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        });

        return [
            'import_batch_no' => $batchNo,
            'created' => $created,
            'failed_count' => count($failed),
            'failed' => $failed,
        ];
    }

    private function syncOptions(Question $question, array $options): void
    {
        $question->options()->delete();

        foreach ($options as $index => $option) {
            if (empty($option['option_text']) && empty($option['option_html'])) {
                continue;
            }

            QuestionOption::create([
                'tenant_id' => $question->tenant_id,
                'question_id' => $question->id,
                'option_key' => $option['option_key'] ?? chr(65 + $index),
                'option_text' => $option['option_text'] ?? null,
                'option_html' => $option['option_html'] ?? null,
                'is_correct' => $option['is_correct'] ?? false,
                'correct_order' => $option['correct_order'] ?? null,
                'match_key' => $option['match_key'] ?? null,
                'correct_match_key' => $option['correct_match_key'] ?? null,
                'marks_percentage' => $option['marks_percentage'] ?? null,
                'display_order' => $option['display_order'] ?? ($index + 1),
                'source_code' => 'manual',
            ]);
        }
    }

    private function syncAnswerKeys(Question $question, array $answerKeys): void
    {
        $question->answerKeys()->delete();

        foreach ($answerKeys as $answerKey) {
            if (
                empty($answerKey['answer_text']) &&
                !isset($answerKey['answer_number']) &&
                empty($answerKey['accepted_variants_json'])
            ) {
                continue;
            }

            QuestionAnswerKey::create([
                'tenant_id' => $question->tenant_id,
                'question_id' => $question->id,
                'answer_text' => $answerKey['answer_text'] ?? null,
                'answer_number' => $answerKey['answer_number'] ?? null,
                'accepted_variants_json' => $answerKey['accepted_variants_json'] ?? null,
                'case_sensitive' => $answerKey['case_sensitive'] ?? false,
                'numeric_tolerance' => $answerKey['numeric_tolerance'] ?? null,
                'marks_percentage' => $answerKey['marks_percentage'] ?? null,
                'status_code' => $answerKey['status_code'] ?? 'active',
            ]);
        }
    }

    private function createImportedOptions(Question $question, array $row): void
    {
        $correctOptions = array_map(
            fn ($value) => strtoupper(trim($value)),
            explode(',', $row['correct_options'] ?? '')
        );

        $optionMap = [
            'A' => $row['option_a'] ?? null,
            'B' => $row['option_b'] ?? null,
            'C' => $row['option_c'] ?? null,
            'D' => $row['option_d'] ?? null,
            'E' => $row['option_e'] ?? null,
        ];

        foreach ($optionMap as $key => $text) {
            if (!$text) {
                continue;
            }

            QuestionOption::create([
                'tenant_id' => $question->tenant_id,
                'question_id' => $question->id,
                'option_key' => $key,
                'option_text' => $text,
                'is_correct' => in_array($key, $correctOptions, true),
                'display_order' => ord($key) - 64,
                'source_code' => 'bulk_import',
                'import_batch_no' => $question->import_batch_no,
            ]);
        }
    }

    private function createImportedAnswerKey(Question $question, array $row): void
    {
        if (!empty($row['answer_text']) || isset($row['answer_number'])) {
            QuestionAnswerKey::create([
                'tenant_id' => $question->tenant_id,
                'question_id' => $question->id,
                'answer_text' => $row['answer_text'] ?? null,
                'answer_number' => $row['answer_number'] ?? null,
                'case_sensitive' => false,
                'status_code' => 'active',
            ]);
        }
    }

    private function findBank(int $tenantId, ?string $code): QuestionBank
    {
        if (!$code) {
            abort(422, 'question_bank_code is required.');
        }

        return QuestionBank::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function findSubject(int $tenantId, ?string $code): ?AssessmentSubject
    {
        if (!$code) {
            return null;
        }

        return AssessmentSubject::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    private function findTopic(int $tenantId, ?int $subjectId, ?string $code): ?AssessmentTopic
    {
        if (!$code || !$subjectId) {
            return null;
        }

        return AssessmentTopic::query()
            ->where('tenant_id', $tenantId)
            ->where('assessment_subject_id', $subjectId)
            ->where('code', $code)
            ->first();
    }
private function qualityScore(int $total, int $issues): float
{
    if ($total <= 0) {
        return 0;
    }

    $maxIssuePoints = $total * 6;

    if ($maxIssuePoints <= 0) {
        return 0;
    }

    $score = 100 - (($issues / $maxIssuePoints) * 100);

    return round(max(0, min(100, $score)), 2);
}

private function simpleGroupCount($baseQuery, string $column): array
{
    return (clone $baseQuery)
        ->select([
            DB::raw("COALESCE({$column}, 'Not Set') as label"),
            DB::raw('COUNT(*) as total'),
        ])
        ->groupBy(DB::raw("COALESCE({$column}, 'Not Set')"))
        ->orderByDesc('total')
        ->get()
        ->map(fn ($row) => [
            'label' => $row->label,
            'total' => (int) $row->total,
        ])
        ->values()
        ->toArray();
}

private function groupCount(
    $baseQuery,
    string $idColumn,
    string $labelColumn,
    string $joinTable,
    string $joinLeft,
    string $joinRight
): array {
    return (clone $baseQuery)
        ->leftJoin($joinTable, $joinLeft, '=', $joinRight)
        ->select([
            DB::raw("COALESCE({$labelColumn}, 'Not Set') as label"),
            DB::raw("{$idColumn} as value"),
            DB::raw('COUNT(*) as total'),
        ])
        ->groupBy($idColumn, $labelColumn)
        ->orderByDesc('total')
        ->get()
        ->map(fn ($row) => [
            'label' => $row->label,
            'value' => $row->value,
            'total' => (int) $row->total,
        ])
        ->values()
        ->toArray();
}

private function qualityIssueRows($baseQuery): array
{
    $bankColumn = $this->questionBankColumn();
    $subjectColumn = $this->questionSubjectColumn();
    $topicColumn = $this->questionTopicColumn();

    $query = (clone $baseQuery);

    if ($bankColumn) {
        $query->leftJoin('question_banks as qb', 'qb.id', '=', "q.{$bankColumn}");
    }

    if ($subjectColumn) {
        $query->leftJoin('assessment_subjects as s', 's.id', '=', "q.{$subjectColumn}");
    }

    if ($topicColumn) {
        $query->leftJoin('assessment_topics as t', 't.id', '=', "q.{$topicColumn}");
    }

    $rows = $query
        ->leftJoin('question_options as qo', 'qo.question_id', '=', 'q.id')
        ->leftJoin('question_answer_keys as ak', 'ak.question_id', '=', 'q.id')
        ->select([
            'q.id',
            'q.question_text',
            'q.question_type_code',
            'q.difficulty_code',
            'q.cognitive_level_code',
            'q.bloom_level_code',
            'q.approval_status_code',
            'q.is_active',

            DB::raw($bankColumn ? 'qb.name as question_bank_name' : 'NULL as question_bank_name'),
            DB::raw($subjectColumn ? 's.name as subject_name' : 'NULL as subject_name'),
            DB::raw($topicColumn ? 't.name as topic_name' : 'NULL as topic_name'),

            DB::raw('COUNT(DISTINCT qo.id) as option_count'),
            DB::raw('COUNT(DISTINCT ak.id) as answer_key_count'),

            DB::raw("
                CASE
                    WHEN q.explanation IS NULL OR q.explanation = ''
                    THEN 0
                    ELSE 1
                END as has_plain_explanation
            "),

            DB::raw("
                CASE
                    WHEN q.explanation_html IS NULL OR q.explanation_html = ''
                    THEN 0
                    ELSE 1
                END as has_html_explanation
            "),
        ])
        ->groupBy([
            'q.id',
            'q.question_text',
            'q.question_type_code',
            'q.difficulty_code',
            'q.cognitive_level_code',
            'q.bloom_level_code',
            'q.approval_status_code',
            'q.is_active',
            'q.explanation',
            'q.explanation_html',
        ])
        ->when($bankColumn, function ($q) {
            $q->groupBy('qb.name');
        })
        ->when($subjectColumn, function ($q) {
            $q->groupBy('s.name');
        })
        ->when($topicColumn, function ($q) {
            $q->groupBy('t.name');
        })
        ->orderByDesc('q.id')
        ->limit(300)
        ->get();

    return $rows
        ->map(function ($row) {
            $issues = [];

            if ((int) $row->answer_key_count === 0) {
                $issues[] = 'Missing answer key';
            }

            if (
                in_array($row->question_type_code, ['mcq_single', 'mcq_multiple', 'true_false'], true)
                && (int) $row->option_count === 0
            ) {
                $issues[] = 'Missing options';
            }

            if (!$row->difficulty_code) {
                $issues[] = 'Missing difficulty';
            }

            if (!$row->bloom_level_code) {
                $issues[] = 'Missing Bloom level';
            }

            if (!$row->topic_name) {
                $issues[] = 'Missing topic';
            }

            if (!(int) $row->has_plain_explanation && !(int) $row->has_html_explanation) {
                $issues[] = 'Missing explanation';
            }

            return [
                'id' => $row->id,
                'question_text' => $row->question_text,
                'question_type_code' => $row->question_type_code,
                'difficulty_code' => $row->difficulty_code,
                'cognitive_level_code' => $row->cognitive_level_code,
                'bloom_level_code' => $row->bloom_level_code,
                'approval_status_code' => $row->approval_status_code,
                'is_active' => (bool) $row->is_active,
                'question_bank_name' => $row->question_bank_name,
                'subject_name' => $row->subject_name,
                'topic_name' => $row->topic_name,
                'option_count' => (int) $row->option_count,
                'answer_key_count' => (int) $row->answer_key_count,
                'issues' => $issues,
            ];
        })
        ->filter(fn ($row) => count($row['issues']) > 0)
        ->take(100)
        ->values()
        ->toArray();
}
private function questionSubjectColumn(): ?string
{
    if (Schema::hasColumn('questions', 'assessment_subject_id')) {
        return 'assessment_subject_id';
    }

    if (Schema::hasColumn('questions', 'subject_id')) {
        return 'subject_id';
    }

    return null;
}

private function questionTopicColumn(): ?string
{
    if (Schema::hasColumn('questions', 'assessment_topic_id')) {
        return 'assessment_topic_id';
    }

    if (Schema::hasColumn('questions', 'topic_id')) {
        return 'topic_id';
    }

    return null;
}

private function questionBankColumn(): ?string
{
    if (Schema::hasColumn('questions', 'question_bank_id')) {
        return 'question_bank_id';
    }

    return null;
}
    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $tenantId;
    }
}