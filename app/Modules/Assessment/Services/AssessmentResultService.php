<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\AssessmentAttempt;
use App\Modules\Assessment\Models\AssessmentAttemptAnswer;
use App\Modules\Assessment\Models\AssessmentResult;
use App\Modules\Assessment\Models\AssessmentSectionResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Modules\Assessment\Services\AssessmentAdmissionSyncService;

class AssessmentResultService
{
    public function generateForAttempt(int $attemptId): array
    {
        return DB::transaction(function () use ($attemptId) {
            $tenantId = $this->tenantId();

            $attempt = AssessmentAttempt::query()
                ->with([
                    'participant.assessment.sections',
                    'answers.assessmentQuestion.section',
                    'answers.assessmentQuestion.question.topic',
                    'answers.assessmentQuestion.question.subject',
                    'answers.assessmentQuestion.question.options',
                    'answers.assessmentQuestion.question.answerKeys',
                ])
                ->where('tenant_id', $tenantId)
                ->where('id', $attemptId)
                ->firstOrFail();

            $participant = $attempt->participant;
            $assessment = $participant->assessment;

            if (!in_array($attempt->status_code, ['submitted', 'auto_submitted', 'evaluated'], true)) {
                abort(422, 'Attempt must be submitted before result generation.');
            }

            $answers = $attempt->answers;

            foreach ($answers as $answer) {
                $this->markAnswer($answer);
            }

            $answers = AssessmentAttemptAnswer::query()
                ->with([
                    'assessmentQuestion.section',
                    'assessmentQuestion.question.topic',
                    'assessmentQuestion.question.subject',
                    'assessmentQuestion.question.options',
                    'assessmentQuestion.question.answerKeys',
                ])
                ->where('tenant_id', $tenantId)
                ->where('assessment_attempt_id', $attempt->id)
                ->get();

            $totalMarks = $answers->sum(fn ($answer) => (float) $answer->assessmentQuestion->marks);
            $obtainedMarks = $answers->sum(fn ($answer) => (float) $answer->marks_awarded);
            $negativeMarks = $answers->sum(fn ($answer) => (float) $answer->negative_marks_applied);
            $finalMarks = max(0, $obtainedMarks - $negativeMarks);
            $percentage = $totalMarks > 0 ? round(($finalMarks / $totalMarks) * 100, 2) : 0;
            $passingMarks = $assessment->passing_marks;
            $isPassed = $passingMarks === null ? true : $finalMarks >= (float) $passingMarks;

            $analysis = $this->buildAnalysis($answers);

            $result = AssessmentResult::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'assessment_participant_id' => $participant->id,
                    'assessment_attempt_id' => $attempt->id,
                ],
                [
                    'assessment_id' => $assessment->id,
                    'total_marks' => $totalMarks,
                    'obtained_marks' => $obtainedMarks,
                    'negative_marks' => $negativeMarks,
                    'final_marks' => $finalMarks,
                    'percentage' => $percentage,
                    'passing_marks' => $passingMarks,
                    'is_passed' => $isPassed,
                    'rank' => null,
                    'percentile' => null,
                    'grade_code' => null,
                    'strengths_json' => $analysis['strengths'],
                    'weaknesses_json' => $analysis['weaknesses'],
                    'analysis_json' => $analysis,
                    'result_status_code' => 'generated',
                    'generated_at' => now(),
                    'remarks' => null,
                ]
            );

            $this->generateSectionResults($result->id, $answers);

            $attempt->update([
                'obtained_marks' => $obtainedMarks,
                'negative_marks' => $negativeMarks,
                'final_marks' => $finalMarks,
                'percentage' => $percentage,
                'status_code' => 'evaluated',
            ]);

            $participant->update([
                'attempt_status_code' => 'evaluated',
                'result_status_code' => $isPassed ? 'pass' : 'fail',
                'evaluated_at' => now(),
                'obtained_marks' => $finalMarks,
                'percentage' => $percentage,
            ]);

            $syncPayload = null;

            if ($participant->participant_type_code === 'applicant') {
                $syncPayload = app(AssessmentAdmissionSyncService::class)
                    ->syncResultToApplicantTestResult($result->id);
            }

            return [
                'attempt' => $attempt->fresh(),
                'result' => $result->fresh(['sectionResults']),
                'analysis' => $analysis,
                'admission_sync' => $syncPayload,
            ];
        });
    }

    public function publishResult(int $resultId): AssessmentResult
{
    $result = AssessmentResult::query()
        ->where('tenant_id', $this->tenantId())
        ->where('id', $resultId)
        ->with('participant')
        ->firstOrFail();

    $result->update([
        'result_status_code' => 'published',
        'published_at' => now(),
    ]);

    $result->participant?->update([
        'result_status_code' => 'published',
    ]);

    if ($result->participant?->participant_type_code === 'applicant') {
        app(AssessmentAdmissionSyncService::class)
            ->syncResultToApplicantTestResult($result->id);
    }

    return $result->fresh();
}

    public function approveResult(int $resultId): AssessmentResult
    {
        $result = AssessmentResult::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $resultId)
            ->firstOrFail();

        $result->update([
            'result_status_code' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $result->fresh();
    }

    private function markAnswer(AssessmentAttemptAnswer $answer): void
    {
        $assessmentQuestion = $answer->assessmentQuestion;
        $question = $assessmentQuestion->question;

        $questionType = $question->question_type_code;
        $marks = (float) $assessmentQuestion->marks;
        $negativeMarks = (float) $assessmentQuestion->negative_marks;

        $isCorrect = null;
        $marksAwarded = 0.0;
        $negativeApplied = 0.0;

        if (in_array($questionType, ['mcq_single', 'true_false'], true)) {
            $isCorrect = $this->markSingleChoice($answer, $question->options);
        } elseif ($questionType === 'mcq_multiple') {
            $isCorrect = $this->markMultipleChoice($answer, $question->options);
        } elseif ($questionType === 'numeric') {
            $isCorrect = $this->markNumeric($answer, $question->answerKeys);
        } elseif (in_array($questionType, ['short_answer', 'fill_blank'], true)) {
            $isCorrect = $this->markTextAnswer($answer, $question->answerKeys);
        }

        if ($isCorrect === true) {
            $marksAwarded = $marks;
        } elseif ($isCorrect === false) {
            $negativeApplied = $negativeMarks;
        }

        $answer->update([
            'is_correct' => $isCorrect,
            'marks_awarded' => $marksAwarded,
            'negative_marks_applied' => $negativeApplied,
            'marked_at' => now(),
            'marking_remarks' => $isCorrect === null ? 'Manual marking required.' : null,
        ]);
    }

    private function markSingleChoice(AssessmentAttemptAnswer $answer, Collection $options): bool
    {
        $selected = collect($answer->selected_option_ids_json ?? [])
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        if (count($selected) !== 1) {
            return false;
        }

        $correct = $options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        return count($correct) === 1 && $selected[0] === $correct[0];
    }

    private function markMultipleChoice(AssessmentAttemptAnswer $answer, Collection $options): bool
    {
        $selected = collect($answer->selected_option_ids_json ?? [])
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->toArray();

        $correct = $options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->toArray();

        if (count($correct) === 0) {
            return false;
        }

        return $selected === $correct;
    }

    private function markNumeric(AssessmentAttemptAnswer $answer, Collection $answerKeys): bool
    {
        if ($answer->answer_number === null) {
            return false;
        }

        $given = (float) $answer->answer_number;

        foreach ($answerKeys as $key) {
            if ($key->answer_number === null) {
                continue;
            }

            $expected = (float) $key->answer_number;
            $tolerance = $key->numeric_tolerance !== null ? (float) $key->numeric_tolerance : 0.0;

            if (abs($given - $expected) <= $tolerance) {
                return true;
            }
        }

        return false;
    }

    private function markTextAnswer(AssessmentAttemptAnswer $answer, Collection $answerKeys): bool
    {
        $given = trim((string) $answer->answer_text);

        if ($given === '') {
            return false;
        }

        foreach ($answerKeys as $key) {
            $accepted = [];

            if ($key->answer_text) {
                $accepted[] = $key->answer_text;
            }

            if (is_array($key->accepted_variants_json)) {
                $accepted = array_merge($accepted, $key->accepted_variants_json);
            }

            foreach ($accepted as $value) {
                $expected = trim((string) $value);

                if ($key->case_sensitive) {
                    if ($given === $expected) {
                        return true;
                    }
                } else {
                    if (mb_strtolower($given) === mb_strtolower($expected)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function generateSectionResults(int $resultId, Collection $answers): void
    {
        $tenantId = $this->tenantId();

        AssessmentSectionResult::query()
            ->where('tenant_id', $tenantId)
            ->where('assessment_result_id', $resultId)
            ->delete();

        $grouped = $answers->groupBy(fn ($answer) => $answer->assessmentQuestion->assessment_section_id);

        foreach ($grouped as $sectionId => $sectionAnswers) {
            $totalMarks = $sectionAnswers->sum(fn ($answer) => (float) $answer->assessmentQuestion->marks);
            $obtainedMarks = $sectionAnswers->sum(fn ($answer) => (float) $answer->marks_awarded);
            $negativeMarks = $sectionAnswers->sum(fn ($answer) => (float) $answer->negative_marks_applied);
            $finalMarks = max(0, $obtainedMarks - $negativeMarks);
            $percentage = $totalMarks > 0 ? round(($finalMarks / $totalMarks) * 100, 2) : 0;

            $section = $sectionAnswers->first()->assessmentQuestion->section;

            AssessmentSectionResult::create([
                'tenant_id' => $tenantId,
                'assessment_result_id' => $resultId,
                'assessment_section_id' => $sectionId,
                'assessment_subject_id' => $section?->assessment_subject_id,
                'total_marks' => $totalMarks,
                'obtained_marks' => $obtainedMarks,
                'negative_marks' => $negativeMarks,
                'final_marks' => $finalMarks,
                'percentage' => $percentage,
                'is_passed' => $section?->passing_marks === null ? true : $finalMarks >= (float) $section->passing_marks,
                'topic_analysis_json' => $this->aggregate($sectionAnswers, 'topic'),
                'difficulty_analysis_json' => $this->aggregate($sectionAnswers, 'difficulty'),
                'question_type_analysis_json' => $this->aggregate($sectionAnswers, 'question_type'),
            ]);
        }
    }

    private function buildAnalysis(Collection $answers): array
    {
        $topicAnalysis = $this->aggregate($answers, 'topic');
        $difficultyAnalysis = $this->aggregate($answers, 'difficulty');
        $questionTypeAnalysis = $this->aggregate($answers, 'question_type');
        $subjectAnalysis = $this->aggregate($answers, 'subject');

        $strengths = collect($topicAnalysis)
            ->filter(fn ($item) => $item['percentage'] >= 70 && $item['attempted'] > 0)
            ->sortByDesc('percentage')
            ->values()
            ->take(5)
            ->toArray();

        $weaknesses = collect($topicAnalysis)
            ->filter(fn ($item) => $item['percentage'] < 50 && $item['attempted'] > 0)
            ->sortBy('percentage')
            ->values()
            ->take(5)
            ->toArray();

        return [
            'subject_analysis' => $subjectAnalysis,
            'topic_analysis' => $topicAnalysis,
            'difficulty_analysis' => $difficultyAnalysis,
            'question_type_analysis' => $questionTypeAnalysis,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
        ];
    }

    private function aggregate(Collection $answers, string $dimension): array
    {
        $grouped = $answers->groupBy(function ($answer) use ($dimension) {
            $question = $answer->assessmentQuestion->question;

            return match ($dimension) {
                'subject' => $question->subject?->name ?? 'Unclassified Subject',
                'topic' => $question->topic?->name ?? 'Unclassified Topic',
                'difficulty' => $question->difficulty_code ?? 'unclassified',
                'question_type' => $question->question_type_code ?? 'unclassified',
                default => 'unclassified',
            };
        });

        return $grouped->map(function ($items, $label) {
            $totalMarks = $items->sum(fn ($answer) => (float) $answer->assessmentQuestion->marks);
            $obtainedMarks = $items->sum(fn ($answer) => (float) $answer->marks_awarded);
            $negativeMarks = $items->sum(fn ($answer) => (float) $answer->negative_marks_applied);
            $finalMarks = max(0, $obtainedMarks - $negativeMarks);
            $percentage = $totalMarks > 0 ? round(($finalMarks / $totalMarks) * 100, 2) : 0;

            return [
                'label' => $label,
                'total_questions' => $items->count(),
                'attempted' => $items->filter(fn ($answer) => $this->isAttempted($answer))->count(),
                'correct' => $items->where('is_correct', true)->count(),
                'wrong' => $items->where('is_correct', false)->count(),
                'manual_required' => $items->filter(fn ($answer) => $answer->is_correct === null)->count(),
                'total_marks' => $totalMarks,
                'obtained_marks' => $obtainedMarks,
                'negative_marks' => $negativeMarks,
                'final_marks' => $finalMarks,
                'percentage' => $percentage,
            ];
        })->values()->toArray();
    }

    private function isAttempted(AssessmentAttemptAnswer $answer): bool
    {
        return !empty($answer->selected_option_ids_json)
            || $answer->answer_text !== null
            || $answer->answer_number !== null
            || $answer->uploaded_file_path !== null;
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