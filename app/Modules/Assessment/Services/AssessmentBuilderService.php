<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\Assessment;
use App\Modules\Assessment\Models\AssessmentQuestion;
use App\Modules\Assessment\Models\AssessmentSection;
use App\Modules\Assessment\Models\Question;
use Illuminate\Support\Facades\DB;

class AssessmentBuilderService
{
    public function show(int $assessmentId): array
    {
        $tenantId = $this->tenantId();

        $assessment = Assessment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $assessmentId)
            ->with([
                'category:id,name,code',
                'sections.subject:id,name,code',
                'sections.questions.question:id,question_text,question_type_code,difficulty_code,default_marks',
            ])
            ->firstOrFail();

        return [
            'assessment' => $assessment,
            'summary' => $this->summary($assessmentId),
        ];
    }

    public function createSection(int $assessmentId, array $data): AssessmentSection
    {
        $tenantId = $this->tenantId();

        $assessment = Assessment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $assessmentId)
            ->firstOrFail();

        return AssessmentSection::create([
            'tenant_id' => $tenantId,
            'assessment_id' => $assessment->id,
            'assessment_subject_id' => $data['assessment_subject_id'] ?? null,
            'section_code' => $data['section_code'],
            'section_title' => $data['section_title'],
            'instructions' => $data['instructions'] ?? null,
            'total_questions' => $data['total_questions'] ?? null,
            'total_marks' => $data['total_marks'] ?? 0,
            'passing_marks' => $data['passing_marks'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'question_selection_mode_code' => $data['question_selection_mode_code'] ?? 'manual',
            'shuffle_questions' => $data['shuffle_questions'] ?? false,
            'display_order' => $data['display_order'] ?? 0,
            'status_code' => $data['status_code'] ?? 'active',
        ]);
    }

    public function updateSection(int $sectionId, array $data): AssessmentSection
    {
        $section = AssessmentSection::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $sectionId)
            ->firstOrFail();

        $section->update([
            'assessment_subject_id' => $data['assessment_subject_id'] ?? null,
            'section_code' => $data['section_code'],
            'section_title' => $data['section_title'],
            'instructions' => $data['instructions'] ?? null,
            'total_questions' => $data['total_questions'] ?? null,
            'total_marks' => $data['total_marks'] ?? 0,
            'passing_marks' => $data['passing_marks'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'question_selection_mode_code' => $data['question_selection_mode_code'] ?? 'manual',
            'shuffle_questions' => $data['shuffle_questions'] ?? false,
            'display_order' => $data['display_order'] ?? 0,
            'status_code' => $data['status_code'] ?? 'active',
        ]);

        return $section->fresh(['subject']);
    }

    public function deleteSection(int $sectionId): void
    {
        DB::transaction(function () use ($sectionId) {
            $tenantId = $this->tenantId();

            $section = AssessmentSection::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $sectionId)
                ->firstOrFail();

            AssessmentQuestion::query()
                ->where('tenant_id', $tenantId)
                ->where('assessment_section_id', $section->id)
                ->delete();

            $section->delete();
        });
    }

    public function availableQuestions(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = Question::query()
            ->with(['bank:id,name,code', 'subject:id,name,code', 'topic:id,name,code'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        $this->applyQuestionFilters($query, $filters);

        return $query
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->toArray();
    }

    public function bulkAssignQuestions(int $sectionId, array $data): array
    {
        return DB::transaction(function () use ($sectionId, $data) {
            $tenantId = $this->tenantId();

            $section = AssessmentSection::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $sectionId)
                ->firstOrFail();

            $assessment = Assessment::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $section->assessment_id)
                ->firstOrFail();

            if (($data['overwrite_existing'] ?? false) === true) {
                AssessmentQuestion::query()
                    ->where('tenant_id', $tenantId)
                    ->where('assessment_section_id', $section->id)
                    ->delete();
            }

            $questionQuery = Question::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            $this->applyQuestionFilters($questionQuery, $data);

            if (($data['approved_only'] ?? false) === true) {
                $questionQuery->where('approval_status_code', 'approved');
            }

            $selectionMode = $data['selection_mode'] ?? 'latest';

            if ($selectionMode === 'random') {
                $questionQuery->inRandomOrder();
            } else {
                $questionQuery->orderByDesc('id');
            }

            $limit = (int) ($data['number_of_questions'] ?? 0);

            if ($limit > 0) {
                $questionQuery->limit($limit);
            }

            $questions = $questionQuery->get();

            $existingQuestionIds = AssessmentQuestion::query()
                ->where('tenant_id', $tenantId)
                ->where('assessment_id', $assessment->id)
                ->pluck('question_id')
                ->toArray();

            $nextOrder = AssessmentQuestion::query()
                ->where('tenant_id', $tenantId)
                ->where('assessment_id', $assessment->id)
                ->max('display_order');

            $displayOrder = ((int) $nextOrder) + 1;

            $assigned = 0;
            $skipped = 0;

            foreach ($questions as $question) {
                if (in_array($question->id, $existingQuestionIds, true)) {
                    $skipped++;
                    continue;
                }

                AssessmentQuestion::create([
                    'tenant_id' => $tenantId,
                    'assessment_id' => $assessment->id,
                    'assessment_section_id' => $section->id,
                    'question_id' => $question->id,
                    'marks' => $data['marks_per_question'] ?? $question->default_marks ?? 1,
                    'negative_marks' => $data['negative_marks_per_question'] ?? $question->default_negative_marks ?? 0,
                    'time_seconds' => $data['time_seconds_per_question'] ?? $question->default_time_seconds,
                    'display_order' => $displayOrder,
                    'is_mandatory' => $data['is_mandatory'] ?? true,
                    'status_code' => 'active',
                ]);

                $existingQuestionIds[] = $question->id;
                $displayOrder++;
                $assigned++;
            }

            $this->refreshSectionTotals($section->id);
            $this->refreshAssessmentTotals($assessment->id);

            return [
                'assigned' => $assigned,
                'skipped_existing' => $skipped,
                'section' => $section->fresh(['questions']),
                'summary' => $this->summary($assessment->id),
            ];
        });
    }

    public function removeAssessmentQuestion(int $assessmentQuestionId): array
    {
        return DB::transaction(function () use ($assessmentQuestionId) {
            $tenantId = $this->tenantId();

            $assessmentQuestion = AssessmentQuestion::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $assessmentQuestionId)
                ->firstOrFail();

            $assessmentId = $assessmentQuestion->assessment_id;
            $sectionId = $assessmentQuestion->assessment_section_id;

            $assessmentQuestion->delete();

            $this->refreshSectionTotals($sectionId);
            $this->refreshAssessmentTotals($assessmentId);

            return [
                'summary' => $this->summary($assessmentId),
            ];
        });
    }

    private function applyQuestionFilters($query, array $filters): void
{
    $questionBankIds = $this->normalizeArrayFilter(
        $filters['question_bank_ids'] ?? null,
        $filters['question_bank_id'] ?? null
    );

    if (!empty($questionBankIds)) {
        $query->whereIn('question_bank_id', $questionBankIds);
    }

    $subjectIds = $this->normalizeArrayFilter(
        $filters['assessment_subject_ids'] ?? null,
        $filters['assessment_subject_id'] ?? null
    );

    if (!empty($subjectIds)) {
        $query->whereIn('assessment_subject_id', $subjectIds);
    }

    $topicIds = $this->normalizeArrayFilter(
        $filters['assessment_topic_ids'] ?? null,
        $filters['assessment_topic_id'] ?? null
    );

    if (!empty($topicIds)) {
        $query->whereIn('assessment_topic_id', $topicIds);
    }

    $questionTypeCodes = $this->normalizeArrayFilter(
        $filters['question_type_codes'] ?? null,
        $filters['question_type_code'] ?? null
    );

    if (!empty($questionTypeCodes)) {
        $query->whereIn('question_type_code', $questionTypeCodes);
    }

    $difficultyCodes = $this->normalizeArrayFilter(
        $filters['difficulty_codes'] ?? null,
        $filters['difficulty_code'] ?? null
    );

    if (!empty($difficultyCodes)) {
        $query->whereIn('difficulty_code', $difficultyCodes);
    }

    $cognitiveLevelCodes = $this->normalizeArrayFilter(
        $filters['cognitive_level_codes'] ?? null,
        $filters['cognitive_level_code'] ?? null
    );

    if (!empty($cognitiveLevelCodes)) {
        $query->whereIn('cognitive_level_code', $cognitiveLevelCodes);
    }

    if (!empty($filters['search'])) {
        $search = $filters['search'];

        $query->where(function ($q) use ($search) {
            $q->where('question_text', 'like', "%{$search}%")
                ->orWhere('external_ref_no', 'like', "%{$search}%")
                ->orWhere('import_batch_no', 'like', "%{$search}%");
        });
    }
}
private function normalizeArrayFilter(mixed $arrayValue, mixed $singleValue): array
{
    if (is_array($arrayValue)) {
        return array_values(array_filter($arrayValue, fn ($value) => $value !== null && $value !== ''));
    }

    if ($singleValue !== null && $singleValue !== '') {
        return [$singleValue];
    }

    return [];
}
    private function refreshSectionTotals(int $sectionId): void
    {
        $section = AssessmentSection::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $sectionId)
            ->firstOrFail();

        $totalQuestions = AssessmentQuestion::query()
            ->where('tenant_id', $section->tenant_id)
            ->where('assessment_section_id', $section->id)
            ->count();

        $totalMarks = AssessmentQuestion::query()
            ->where('tenant_id', $section->tenant_id)
            ->where('assessment_section_id', $section->id)
            ->sum('marks');

        $section->update([
            'total_questions' => $totalQuestions,
            'total_marks' => $totalMarks,
        ]);
    }

    private function refreshAssessmentTotals(int $assessmentId): void
    {
        $assessment = Assessment::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $assessmentId)
            ->firstOrFail();

        $totalMarks = AssessmentSection::query()
            ->where('tenant_id', $assessment->tenant_id)
            ->where('assessment_id', $assessment->id)
            ->sum('total_marks');

        $assessment->update([
            'total_marks' => $totalMarks,
        ]);
    }

    private function summary(int $assessmentId): array
    {
        $tenantId = $this->tenantId();

        $sectionCount = AssessmentSection::query()
            ->where('tenant_id', $tenantId)
            ->where('assessment_id', $assessmentId)
            ->count();

        $questionCount = AssessmentQuestion::query()
            ->where('tenant_id', $tenantId)
            ->where('assessment_id', $assessmentId)
            ->count();

        $totalMarks = AssessmentQuestion::query()
            ->where('tenant_id', $tenantId)
            ->where('assessment_id', $assessmentId)
            ->sum('marks');

        return [
            'section_count' => $sectionCount,
            'question_count' => $questionCount,
            'total_marks' => (float) $totalMarks,
        ];
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