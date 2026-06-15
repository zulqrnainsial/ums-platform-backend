<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\AssessmentResult;
use Illuminate\Support\Facades\DB;

class AssessmentResultAdminService
{
    public function list(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('assessment_results as ar')
            ->leftJoin('assessment_participants as ap', 'ap.id', '=', 'ar.assessment_participant_id')
            ->leftJoin('assessment_attempts as aa', 'aa.id', '=', 'ar.assessment_attempt_id')
            ->leftJoin('assessments as a', 'a.id', '=', 'ar.assessment_id')
            ->leftJoin('assessment_schedules as s', 's.id', '=', 'ap.assessment_schedule_id')
            ->leftJoin('applicants as app', 'app.id', '=', 'ap.applicant_id')
            ->where('ar.tenant_id', $tenantId)
            ->select([
                'ar.id',
                'ar.assessment_id',
                'ar.assessment_participant_id',
                'ar.assessment_attempt_id',
                'ar.total_marks',
                'ar.obtained_marks',
                'ar.negative_marks',
                'ar.final_marks',
                'ar.percentage',
                'ar.passing_marks',
                'ar.is_passed',
                'ar.rank',
                'ar.percentile',
                'ar.grade_code',
                'ar.result_status_code',
                'ar.generated_at',
                'ar.approved_at',
                'ar.published_at',

                'ap.roll_no',
                'ap.seat_no',
                'ap.participant_type_code',
                'ap.participant_id',
                'ap.applicant_id',
                'ap.attendance_status_code',
                'ap.attempt_status_code',
                'ap.result_status_code as participant_result_status_code',

                'aa.attempt_no',
                'aa.started_at',
                'aa.submitted_at',
                'aa.duration_seconds',
                'aa.status_code as attempt_status_code',

                'a.code as assessment_code',
                'a.title as assessment_title',
                'a.mode_code as assessment_mode_code',
                'a.purpose_code as assessment_purpose_code',

                's.id as schedule_id',
                's.schedule_code',
                's.title as schedule_title',
                's.start_at as schedule_start_at',
                's.end_at as schedule_end_at',

                'app.applicant_no',
                'app.full_name as applicant_name',
                'app.cnic_bform',
                'app.email as applicant_email',
                'app.phone as applicant_phone',
            ]);

        if (!empty($filters['assessment_id'])) {
            $query->where('ar.assessment_id', $filters['assessment_id']);
        }

        if (!empty($filters['assessment_schedule_id'])) {
            $query->where('ap.assessment_schedule_id', $filters['assessment_schedule_id']);
        }

        if (!empty($filters['result_status_code'])) {
            $query->where('ar.result_status_code', $filters['result_status_code']);
        }

        if (array_key_exists('is_passed', $filters) && $filters['is_passed'] !== null && $filters['is_passed'] !== '') {
            $query->where('ar.is_passed', filter_var($filters['is_passed'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('ap.roll_no', 'like', "%{$search}%")
                    ->orWhere('app.applicant_no', 'like', "%{$search}%")
                    ->orWhere('app.full_name', 'like', "%{$search}%")
                    ->orWhere('app.cnic_bform', 'like', "%{$search}%")
                    ->orWhere('a.code', 'like', "%{$search}%")
                    ->orWhere('a.title', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('ar.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function detail(int $resultId): array
    {
        $tenantId = $this->tenantId();

        $result = AssessmentResult::query()
            ->with([
                'participant.assessment',
                'participant.schedule',
                'attempt.answers.assessmentQuestion.section',
                'attempt.answers.assessmentQuestion.question.subject',
                'attempt.answers.assessmentQuestion.question.topic',
                'attempt.answers.assessmentQuestion.question.options',
                'attempt.answers.assessmentQuestion.question.answerKeys',
                'sectionResults',
            ])
            ->where('tenant_id', $tenantId)
            ->where('id', $resultId)
            ->firstOrFail();

        $participant = $result->participant;
        $attempt = $result->attempt;
        $assessment = $participant?->assessment;
        $schedule = $participant?->schedule;

        $applicant = null;

        if ($participant?->applicant_id) {
            $applicant = DB::table('applicants')
                ->where('tenant_id', $tenantId)
                ->where('id', $participant->applicant_id)
                ->first();
        }

        $admissionTestResult = null;

        if (DB::getSchemaBuilder()->hasTable('applicant_test_results')) {
            $admissionTestResult = DB::table('applicant_test_results')
                ->where('tenant_id', $tenantId)
                ->where('assessment_result_id', $result->id)
                ->first();
        }

        $answers = collect($attempt?->answers ?? [])
            ->map(function ($answer) {
                $assessmentQuestion = $answer->assessmentQuestion;
                $question = $assessmentQuestion?->question;

                $selectedOptionIds = collect($answer->selected_option_ids_json ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->toArray();

                $selectedOptions = $question?->options
                    ? $question->options
                        ->whereIn('id', $selectedOptionIds)
                        ->map(fn ($option) => [
                            'id' => $option->id,
                            'option_key' => $option->option_key,
                            'option_text' => $option->option_text,
                        ])
                        ->values()
                        ->toArray()
                    : [];

                $correctOptions = $question?->options
                    ? $question->options
                        ->where('is_correct', true)
                        ->map(fn ($option) => [
                            'id' => $option->id,
                            'option_key' => $option->option_key,
                            'option_text' => $option->option_text,
                        ])
                        ->values()
                        ->toArray()
                    : [];

                return [
                    'id' => $answer->id,
                    'assessment_question_id' => $answer->assessment_question_id,
                    'question_id' => $answer->question_id,
                    'section' => $assessmentQuestion?->section,
                    'subject' => $question?->subject,
                    'topic' => $question?->topic,

                    'question_text' => $question?->question_text,
                    'question_html' => $question?->question_html,
                    'question_type_code' => $question?->question_type_code,
                    'difficulty_code' => $question?->difficulty_code,
                    'cognitive_level_code' => $question?->cognitive_level_code,

                    'marks' => $assessmentQuestion?->marks,
                    'negative_marks' => $assessmentQuestion?->negative_marks,

                    'selected_options' => $selectedOptions,
                    'correct_options' => $correctOptions,
                    'answer_text' => $answer->answer_text,
                    'answer_number' => $answer->answer_number,

                    'is_correct' => $answer->is_correct,
                    'marks_awarded' => $answer->marks_awarded,
                    'negative_marks_applied' => $answer->negative_marks_applied,
                    'manual_marks' => $answer->manual_marks,
                    'marking_remarks' => $answer->marking_remarks,
                ];
            })
            ->values();

        return [
            'result' => $result,
            'participant' => $participant,
            'attempt' => $attempt,
            'assessment' => $assessment,
            'schedule' => $schedule,
            'applicant' => $applicant,
            'section_results' => $result->sectionResults ?? [],
            'answers' => $answers,
            'analysis' => $result->analysis_json ?? [],
            'strengths' => $result->strengths_json ?? [],
            'weaknesses' => $result->weaknesses_json ?? [],
            'admission_test_result' => $admissionTestResult,
            'is_synced_to_admission' => $admissionTestResult !== null,
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