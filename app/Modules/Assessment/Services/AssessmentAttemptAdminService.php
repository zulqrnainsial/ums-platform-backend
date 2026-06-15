<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\AssessmentAttempt;
use App\Modules\Assessment\Models\AssessmentAttemptAnswer;
use Illuminate\Support\Facades\DB;

class AssessmentAttemptAdminService
{
    public function list(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = AssessmentAttempt::query()
            ->from('assessment_attempts as aa')
            ->leftJoin('assessment_participants as ap', 'ap.id', '=', 'aa.assessment_participant_id')
            ->leftJoin('assessments as a', 'a.id', '=', 'ap.assessment_id')
            ->leftJoin('assessment_schedules as s', 's.id', '=', 'ap.assessment_schedule_id')
            ->leftJoin('applicants as app', 'app.id', '=', 'ap.applicant_id')
            ->where('aa.tenant_id', $tenantId)
            ->select([
                'aa.id',
                'aa.assessment_participant_id',
                'aa.attempt_no',
                'aa.started_at',
                'aa.submitted_at',
                'aa.duration_seconds',
                'aa.status_code',
                'aa.obtained_marks',
                'aa.negative_marks',
                'aa.final_marks',
                'aa.percentage',
                'aa.warning_count',
                'aa.tab_switch_count',
                'aa.auto_submitted_at',
                'ap.roll_no',
                'ap.seat_no',
                'ap.participant_type_code',
                'ap.participant_id',
                'ap.applicant_id',
                'ap.attendance_status_code',
                'ap.attempt_status_code',
                'ap.result_status_code',

                'a.id as assessment_id',
                'a.code as assessment_code',
                'a.title as assessment_title',
                'a.mode_code as assessment_mode_code',
                'a.total_marks as assessment_total_marks',

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
            $query->where('ap.assessment_id', $filters['assessment_id']);
        }

        if (!empty($filters['assessment_schedule_id'])) {
            $query->where('ap.assessment_schedule_id', $filters['assessment_schedule_id']);
        }

        if (!empty($filters['status_code'])) {
            $query->where('aa.status_code', $filters['status_code']);
        }

        if (!empty($filters['attempt_status_code'])) {
            $query->where('ap.attempt_status_code', $filters['attempt_status_code']);
        }

        if (!empty($filters['result_status_code'])) {
            $query->where('ap.result_status_code', $filters['result_status_code']);
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
            ->orderByDesc('aa.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function detail(int $attemptId): array
    {
        $tenantId = $this->tenantId();

        $attempt = AssessmentAttempt::query()
            ->with([
                'participant.assessment',
                'participant.schedule',
                'participant.result.sectionResults',
            ])
            ->where('tenant_id', $tenantId)
            ->where('id', $attemptId)
            ->firstOrFail();

        $participant = $attempt->participant;
        $assessment = $participant?->assessment;
        $schedule = $participant?->schedule;

        $applicant = null;

        if ($participant?->applicant_id) {
            $applicant = DB::table('applicants')
                ->where('tenant_id', $tenantId)
                ->where('id', $participant->applicant_id)
                ->first();
        }

        $answers = AssessmentAttemptAnswer::query()
            ->with([
                'assessmentQuestion.section',
                'assessmentQuestion.question.subject',
                'assessmentQuestion.question.topic',
                'assessmentQuestion.question.options',
                'assessmentQuestion.question.answerKeys',
            ])
            ->where('tenant_id', $tenantId)
            ->where('assessment_attempt_id', $attempt->id)
            ->orderBy('id')
            ->get()
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
                            'option_html' => $option->option_html,
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
                            'option_html' => $option->option_html,
                        ])
                        ->values()
                        ->toArray()
                    : [];

                $answerKeys = $question?->answerKeys
                    ? $question->answerKeys
                        ->map(fn ($key) => [
                            'answer_text' => $key->answer_text,
                            'answer_number' => $key->answer_number,
                            'accepted_variants_json' => $key->accepted_variants_json,
                            'numeric_tolerance' => $key->numeric_tolerance,
                            'case_sensitive' => $key->case_sensitive,
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

                    'selected_option_ids_json' => $answer->selected_option_ids_json,
                    'selected_options' => $selectedOptions,
                    'correct_options' => $correctOptions,
                    'answer_text' => $answer->answer_text,
                    'answer_number' => $answer->answer_number,
                    'answer_keys' => $answerKeys,

                    'is_correct' => $answer->is_correct,
                    'marks_awarded' => $answer->marks_awarded,
                    'negative_marks_applied' => $answer->negative_marks_applied,
                    'manual_marks' => $answer->manual_marks,
                    'marking_remarks' => $answer->marking_remarks,

                    'answered_at' => $answer->answered_at,
                    'time_spent_seconds' => $answer->time_spent_seconds,
                ];
            })
            ->values();

        return [
            'attempt' => $attempt,
            'participant' => $participant,
            'assessment' => $assessment,
            'schedule' => $schedule,
            'applicant' => $applicant,
            'result' => $participant?->result,
            'section_results' => $participant?->result?->sectionResults ?? [],
            'answers' => $answers,
        ];
    }
public function activityLogs(int $attemptId): array
{
    $tenantId = $this->tenantId();

    $attempt = AssessmentAttempt::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $attemptId)
        ->firstOrFail();

    return DB::table('assessment_attempt_activity_logs')
        ->where('tenant_id', $tenantId)
        ->where('assessment_attempt_id', $attempt->id)
        ->orderByDesc('occurred_at')
        ->orderByDesc('id')
        ->limit(300)
        ->get()
        ->map(fn ($row) => [
            'id' => $row->id,
            'event_code' => $row->event_code,
            'severity_code' => $row->severity_code,
            'assessment_question_id' => $row->assessment_question_id,
            'question_id' => $row->question_id,
            'event_payload_json' => $row->event_payload_json
                ? json_decode($row->event_payload_json, true)
                : null,
            'ip_address' => $row->ip_address,
            'occurred_at' => $row->occurred_at,
            'created_at' => $row->created_at,
        ])
        ->values()
        ->toArray();
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