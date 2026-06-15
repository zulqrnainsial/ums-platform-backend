<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Admission\Models\Applicant;
use App\Modules\Assessment\Models\AssessmentAttempt;
use App\Modules\Assessment\Models\AssessmentAttemptAnswer;
use App\Modules\Assessment\Models\AssessmentParticipant;
use App\Modules\Assessment\Models\AssessmentQuestion;
use Illuminate\Support\Facades\DB;
use App\Modules\Assessment\Services\AssessmentResultService;
use Illuminate\Support\Facades\Schema;
use App\Modules\Assessment\Services\AssessmentScheduleDateTimeService;
use App\Modules\Assessment\Models\AssessmentAttemptActivityLog;

class ApplicantAssessmentService
{
    public function myTests(): array
    {
        $applicant = $this->applicant();

        $participants = AssessmentParticipant::query()
            ->with([
                'assessment:id,tenant_id,code,title,purpose_code,mode_code,total_marks,passing_marks,duration_minutes,attempt_limit,shuffle_questions,shuffle_options,show_result_immediately,show_correct_answers,allow_review_before_submit,status_code',
                'schedule:id,assessment_id,schedule_code,title,start_at,end_at,reporting_time,timezone,mode_code,venue_name,status_code,instructions',
                'attempts:id,assessment_participant_id,attempt_no,started_at,submitted_at,status_code,final_marks,percentage',
                'result:id,assessment_participant_id,final_marks,percentage,is_passed,result_status_code,published_at',
            ])
            ->where('tenant_id', $applicant->tenant_id)
            ->where('participant_type_code', 'applicant')
            ->where('participant_id', $applicant->id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'assessment_id' => $participant->assessment_id,
                    'assessment_schedule_id' => $participant->assessment_schedule_id,
                    'roll_no' => $participant->roll_no,
                    'seat_no' => $participant->seat_no,
                    'attendance_status_code' => $participant->attendance_status_code,
                    'attempt_status_code' => $participant->attempt_status_code,
                    'result_status_code' => $participant->result_status_code,
                    'assigned_at' => $participant->assigned_at,
                    'started_at' => $participant->started_at,
                    'submitted_at' => $participant->submitted_at,
                    'assessment' => $participant->assessment,
                    'schedule' => $participant->schedule,
                    'latest_attempt' => $participant->attempts->sortByDesc('attempt_no')->first(),
                    'result' => $participant->result,
                    'can_start' => $this->canStart($participant),
                    'can_start_reason' => $this->canStartReason($participant),
                    'can_resume' => $participant->attempt_status_code === 'in_progress',
                    'can_view_result' => $participant->result?->result_status_code === 'published',
                ];
            })
            ->values()
            ->toArray();

        return [
            'applicant' => [
                'id' => $applicant->id,
                'applicant_no' => $applicant->applicant_no,
                'full_name' => $applicant->full_name,
            ],
            'tests' => $participants,
        ];
    }
private function canStartReason(AssessmentParticipant $participant): ?string
{
    if (!$participant->assessment) {
        return 'Assessment not found.';
    }

    if ($participant->assessment->mode_code !== 'online') {
        return 'Assessment mode is not online.';
    }

    if (!$participant->roll_no) {
        return 'Roll number has not been generated.';
    }

    if (!in_array($participant->attempt_status_code, ['not_started', 'in_progress'], true)) {
        return 'Attempt status does not allow starting this test.';
    }

    if ($participant->schedule) {
        if ($participant->schedule->status_code && !in_array($participant->schedule->status_code, ['active', 'published', 'scheduled'], true)) {
            return 'Schedule is not active.';
        }

        $reason = app(AssessmentScheduleDateTimeService::class)->openReason(
            $participant->schedule->start_at,
            $participant->schedule->end_at,
            $participant->schedule->timezone
        );

        if ($reason) {
            return $reason;
        }
    }

    return null;
}
    public function rollNoSlip(int $participantId): array
    {
        $applicant = $this->applicant();

        $participant = AssessmentParticipant::query()
            ->with(['assessment', 'schedule'])
            ->where('tenant_id', $applicant->tenant_id)
            ->where('participant_type_code', 'applicant')
            ->where('participant_id', $applicant->id)
            ->where('id', $participantId)
            ->firstOrFail();

        return [
            'participant' => $participant,
            'assessment' => $participant->assessment,
            'schedule' => $participant->schedule,
            'applicant' => $applicant,
            'instructions' => [
                'Bring original CNIC / B-Form.',
                'Bring printed roll no slip.',
                'Reach the center before reporting time.',
                'Mobile phones and electronic devices are not allowed unless permitted.',
            ],
        ];
    }

    public function startAttempt(int $participantId, string $ipAddress, ?string $userAgent): array
    {
        return DB::transaction(function () use ($participantId, $ipAddress, $userAgent) {
            $applicant = $this->applicant();

            $participant = AssessmentParticipant::query()
                ->with(['assessment', 'schedule'])
                ->where('tenant_id', $applicant->tenant_id)
                ->where('participant_type_code', 'applicant')
                ->where('participant_id', $applicant->id)
                ->where('id', $participantId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$this->canStart($participant) && $participant->attempt_status_code !== 'in_progress') {
                abort(422, 'This test is not available for attempt.');
            }

            $attempt = AssessmentAttempt::query()
                ->where('tenant_id', $applicant->tenant_id)
                ->where('assessment_participant_id', $participant->id)
                ->where('status_code', 'in_progress')
                ->orderByDesc('attempt_no')
                ->first();

            if (!$attempt) {
                $attemptNo = AssessmentAttempt::query()
                    ->where('tenant_id', $applicant->tenant_id)
                    ->where('assessment_participant_id', $participant->id)
                    ->max('attempt_no');

                $attempt = AssessmentAttempt::create([
                    'tenant_id' => $applicant->tenant_id,
                    'assessment_participant_id' => $participant->id,
                    'attempt_no' => ((int) $attemptNo) + 1,
                    'started_at' => now(),
                    'submitted_at' => null,
                    'auto_submitted_at' => null,
                    'duration_seconds' => null,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'tab_switch_count' => 0,
                    'warning_count' => 0,
                    'status_code' => 'in_progress',
                    'obtained_marks' => 0,
                    'negative_marks' => 0,
                    'final_marks' => 0,
                    'percentage' => 0,
                ]);
                $this->logAttemptActivity(
                    attempt: $attempt,
                    eventCode: 'attempt_started',
                    severityCode: 'info',
                    payload: [
                        'attempt_no' => $attempt->attempt_no,
                    ],
                    ipAddress: $ipAddress,
                    userAgent: $userAgent
                );
                $participant->update([
                    'attempt_status_code' => 'in_progress',
                    'started_at' => $participant->started_at ?: now(),
                ]);
                $this->logAttemptActivity(
                    attempt: $attempt,
                    eventCode: 'attempt_started',
                    severityCode: 'info',
                    payload: [
                        'attempt_no' => $attempt->attempt_no,
                    ],
                    ipAddress: $ipAddress,
                    userAgent: $userAgent
                );
            }

            return $this->attemptPayload($participant, $attempt);
        });
    }

    public function getAttempt(int $attemptId): array
    {
        $applicant = $this->applicant();

        $attempt = AssessmentAttempt::query()
            ->with(['participant.assessment', 'participant.schedule'])
            ->where('tenant_id', $applicant->tenant_id)
            ->where('id', $attemptId)
            ->firstOrFail();

        $participant = $attempt->participant;

        if (
            $participant->participant_type_code !== 'applicant' ||
            (int) $participant->participant_id !== (int) $applicant->id
        ) {
            abort(403, 'You are not allowed to access this attempt.');
        }

        return $this->attemptPayload($participant, $attempt);
    }

    public function saveAnswer(int $attemptId, array $data): array
    {
        return DB::transaction(function () use ($attemptId, $data) {
            $applicant = $this->applicant();

            $attempt = AssessmentAttempt::query()
                ->with(['participant'])
                ->where('tenant_id', $applicant->tenant_id)
                ->where('id', $attemptId)
                ->where('status_code', 'in_progress')
                ->firstOrFail();

            $participant = $attempt->participant;

            if (
                $participant->participant_type_code !== 'applicant' ||
                (int) $participant->participant_id !== (int) $applicant->id
            ) {
                abort(403, 'You are not allowed to save this answer.');
            }
            $this->assertAttemptNotExpired($participant, $attempt);
            $assessmentQuestion = AssessmentQuestion::query()
                ->where('tenant_id', $applicant->tenant_id)
                ->where('assessment_id', $participant->assessment_id)
                ->where('id', $data['assessment_question_id'])
                ->firstOrFail();

            $answer = AssessmentAttemptAnswer::updateOrCreate(
                [
                    'tenant_id' => $applicant->tenant_id,
                    'assessment_attempt_id' => $attempt->id,
                    'assessment_question_id' => $assessmentQuestion->id,
                ],
                [
                    'question_id' => $assessmentQuestion->question_id,
                    'selected_option_ids_json' => $data['selected_option_ids_json'] ?? null,
                    'answer_text' => $data['answer_text'] ?? null,
                    'answer_number' => $data['answer_number'] ?? null,
                    'uploaded_file_path' => $data['uploaded_file_path'] ?? null,
                    'is_correct' => null,
                    'marks_awarded' => 0,
                    'negative_marks_applied' => 0,
                    'manual_marks' => null,
                    'answered_at' => now(),
                    'time_spent_seconds' => $data['time_spent_seconds'] ?? null,
                ]
            );
$this->logAttemptActivity(
    attempt: $attempt,
    eventCode: 'answer_saved',
    severityCode: 'info',
    payload: [
        'assessment_question_id' => $assessmentQuestion->id,
        'question_id' => $assessmentQuestion->question_id,
    ],
    assessmentQuestionId: $assessmentQuestion->id,
    questionId: $assessmentQuestion->question_id
);
            return [
                'answer' => $answer,
                'answered_count' => $this->answeredCount($attempt->id),
            ];
        });
    }

    public function submitAttempt(int $attemptId): array
    {
        return DB::transaction(function () use ($attemptId) {
            $applicant = $this->applicant();

            $attempt = AssessmentAttempt::query()
                ->with(['participant'])
                ->where('tenant_id', $applicant->tenant_id)
                ->where('id', $attemptId)
                ->where('status_code', 'in_progress')
                ->lockForUpdate()
                ->firstOrFail();

            $participant = $attempt->participant;

            if (
                $participant->participant_type_code !== 'applicant' ||
                (int) $participant->participant_id !== (int) $applicant->id
            ) {
                abort(403, 'You are not allowed to submit this attempt.');
            }
            if ($this->isAttemptExpired($participant, $attempt)) {
                return $this->autoSubmitAttempt($participant, $attempt);
            }
            $submittedAt = now();
            $startedAt = $attempt->started_at;

            $durationSeconds = null;

            if ($startedAt) {
                $durationSeconds = $startedAt->diffInSeconds($submittedAt, false);

                if ($durationSeconds < 0) {
                    $durationSeconds = 0;
                }
            }

            $attempt->update([
                'submitted_at' => $submittedAt,
                'duration_seconds' => $durationSeconds,
                'status_code' => 'submitted',
            ]);

            $participant->update([
                'attempt_status_code' => 'submitted',
                'submitted_at' => $submittedAt,
            ]);
$this->logAttemptActivity(
    attempt: $attempt,
    eventCode: 'attempt_submitted',
    severityCode: 'info',
    payload: [
        'duration_seconds' => $durationSeconds,
    ]
);
            $resultPayload = app(AssessmentResultService::class)
                ->generateForAttempt($attempt->id);

            return [
                'attempt' => $attempt->fresh(),
                'result' => $resultPayload['result'] ?? null,
                'analysis' => $resultPayload['analysis'] ?? null,
                'message' => 'Attempt submitted and evaluated successfully.',
            ];
        });
    }
public function logActivity(
    int $attemptId,
    array $data,
    ?string $ipAddress = null,
    ?string $userAgent = null
): array {
    $applicant = $this->applicant();

    $attempt = AssessmentAttempt::query()
        ->with(['participant'])
        ->where('tenant_id', $applicant->tenant_id)
        ->where('id', $attemptId)
        ->firstOrFail();

    $participant = $attempt->participant;

    if (
        $participant->participant_type_code !== 'applicant' ||
        (int) $participant->participant_id !== (int) $applicant->id
    ) {
        abort(403, 'You are not allowed to log activity for this attempt.');
    }

    $eventCode = (string) ($data['event_code'] ?? '');

    $allowedEvents = [
        'question_viewed',
        'browser_focus_lost',
        'browser_focus_returned',
        'tab_hidden',
        'tab_visible',
        'fullscreen_left',
        'network_online',
        'network_offline',
        'heartbeat',
    ];

    if (!in_array($eventCode, $allowedEvents, true)) {
        abort(422, 'Invalid attempt activity event.');
    }

    $severityCode = match ($eventCode) {
        'browser_focus_lost',
        'tab_hidden',
        'fullscreen_left',
        'network_offline' => 'warning',
        default => 'info',
    };

    $log = $this->logAttemptActivity(
        attempt: $attempt,
        eventCode: $eventCode,
        severityCode: $severityCode,
        payload: $data['event_payload_json'] ?? [],
        assessmentQuestionId: $data['assessment_question_id'] ?? null,
        questionId: $data['question_id'] ?? null,
        ipAddress: $ipAddress,
        userAgent: $userAgent
    );

    if ($severityCode === 'warning') {
        $attempt->increment('warning_count');
    }

    return [
        'log' => $log,
        'warning_count' => $attempt->fresh()->warning_count,
    ];
}
    private function attemptPayload(AssessmentParticipant $participant, AssessmentAttempt $attempt): array
    {
        $assessment = $participant->assessment;

        $questions = AssessmentQuestion::query()
            ->with([
                'section:id,section_code,section_title,display_order',
                'question.options',
                'question:id,question_text,question_html,question_type_code,difficulty_code,cognitive_level_code,default_marks,default_negative_marks,default_time_seconds,explanation',
            ])
            ->where('tenant_id', $participant->tenant_id)
            ->where('assessment_id', $participant->assessment_id)
            ->where('status_code', 'active')
            ->orderBy('display_order')
            ->get();

        if ($assessment->shuffle_questions) {
            $questions = $questions->shuffle()->values();
        }

        $existingAnswers = AssessmentAttemptAnswer::query()
            ->where('tenant_id', $participant->tenant_id)
            ->where('assessment_attempt_id', $attempt->id)
            ->get()
            ->keyBy('assessment_question_id');

        $payloadQuestions = $questions->map(function ($assessmentQuestion) use ($assessment, $existingAnswers) {
            $question = $assessmentQuestion->question;
            $options = $question->options;

            if ($assessment->shuffle_options) {
                $options = $options->shuffle()->values();
            }

            $existingAnswer = $existingAnswers->get($assessmentQuestion->id);

            return [
                'assessment_question_id' => $assessmentQuestion->id,
                'assessment_section_id' => $assessmentQuestion->assessment_section_id,
                'section' => $assessmentQuestion->section,
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'question_html' => $question->question_html,
                'question_type_code' => $question->question_type_code,
                'difficulty_code' => $question->difficulty_code,
                'cognitive_level_code' => $question->cognitive_level_code,
                'marks' => $assessmentQuestion->marks,
                'negative_marks' => $assessmentQuestion->negative_marks,
                'time_seconds' => $assessmentQuestion->time_seconds,
                'options' => $options->map(fn ($option) => [
                    'id' => $option->id,
                    'option_key' => $option->option_key,
                    'option_text' => $option->option_text,
                    'option_html' => $option->option_html,
                    'display_order' => $option->display_order,
                ])->values(),
                'existing_answer' => $existingAnswer,
            ];
        })->values();

        $expiresAt = $this->attemptExpiresAt($participant, $attempt);
        $remainingSeconds = $this->remainingSeconds($participant, $attempt);

        return [
            'participant' => $participant,
            'assessment' => $assessment,
            'schedule' => $participant->schedule,
            'attempt' => $attempt,
            'questions' => $payloadQuestions,
            'answered_count' => $this->answeredCount($attempt->id),
            'total_questions' => $payloadQuestions->count(),
            'server_time' => now(),
            'started_at' => $attempt->started_at,
            'expires_at' => $expiresAt,
            'remaining_seconds' => $remainingSeconds,
            'duration_minutes' => $assessment?->duration_minutes,
        ];
    }

    private function canStart(AssessmentParticipant $participant): bool
    {
        if (!$participant->assessment) {
            return false;
        }

        if ($participant->assessment->mode_code !== 'online') {
            return false;
        }

        if (!$participant->roll_no) {
            return false;
        }

        if (!in_array($participant->attempt_status_code, ['not_started', 'in_progress'], true)) {
            return false;
        }

        if ($participant->schedule) {
            if ($participant->schedule->status_code && !in_array($participant->schedule->status_code, ['active', 'published', 'scheduled'], true)) {
                return false;
            }

            $isOpen = app(AssessmentScheduleDateTimeService::class)->isOpen(
                $participant->schedule->start_at,
                $participant->schedule->end_at,
                $participant->schedule->timezone
            );

            if (!$isOpen) {
                return false;
            }
        }

        return true;
    }

    private function answeredCount(int $attemptId): int
    {
        return AssessmentAttemptAnswer::query()
            ->where('assessment_attempt_id', $attemptId)
            ->count();
    }
private function attemptExpiresAt(AssessmentParticipant $participant, AssessmentAttempt $attempt): mixed
{
    $assessment = $participant->assessment;

    if (!$attempt->started_at || !$assessment || !$assessment->duration_minutes) {
        return null;
    }

    return $attempt->started_at->copy()->addMinutes((int) $assessment->duration_minutes);
}

private function remainingSeconds(AssessmentParticipant $participant, AssessmentAttempt $attempt): ?int
{
    $expiresAt = $this->attemptExpiresAt($participant, $attempt);

    if (!$expiresAt) {
        return null;
    }

    return max(0, now()->diffInSeconds($expiresAt, false));
}

private function isAttemptExpired(AssessmentParticipant $participant, AssessmentAttempt $attempt): bool
{
    $remaining = $this->remainingSeconds($participant, $attempt);

    return $remaining !== null && $remaining <= 0;
}

private function assertAttemptNotExpired(AssessmentParticipant $participant, AssessmentAttempt $attempt): void
{
    if ($this->isAttemptExpired($participant, $attempt)) {
        $this->autoSubmitAttempt($participant, $attempt);

        abort(423, 'Test time is over. Attempt has been auto-submitted.');
    }
}

private function autoSubmitAttempt(AssessmentParticipant $participant, AssessmentAttempt $attempt): array
{
    if ($attempt->status_code !== 'in_progress') {
        return [
            'attempt' => $attempt->fresh(),
            'message' => 'Attempt is already submitted.',
        ];
    }

    $submittedAt = now();
    $durationSeconds = null;

    if ($attempt->started_at) {
        $durationSeconds = max(0, $attempt->started_at->diffInSeconds($submittedAt, false));
    }

    $attempt->update([
        'submitted_at' => $submittedAt,
        'auto_submitted_at' => $submittedAt,
        'duration_seconds' => $durationSeconds,
        'status_code' => 'auto_submitted',
    ]);

    $participant->update([
        'attempt_status_code' => 'submitted',
        'submitted_at' => $submittedAt,
    ]);

    $resultPayload = app(AssessmentResultService::class)
        ->generateForAttempt($attempt->id);

    return [
        'attempt' => $attempt->fresh(),
        'result' => $resultPayload['result'] ?? null,
        'analysis' => $resultPayload['analysis'] ?? null,
        'message' => 'Time expired. Attempt auto-submitted and evaluated.',
    ];
}
public function attemptReview(int $attemptId): array
{
    $applicant = $this->applicant();

    $attempt = AssessmentAttempt::query()
        ->with([
            'participant.assessment',
            'participant.schedule',
            'answers.assessmentQuestion.question.options',
            'answers.assessmentQuestion.question.answerKeys',
            'answers.assessmentQuestion.section',
        ])
        ->where('tenant_id', $applicant->tenant_id)
        ->where('id', $attemptId)
        ->firstOrFail();

    $participant = $attempt->participant;

    if (
        $participant->participant_type_code !== 'applicant' ||
        (int) $participant->participant_id !== (int) $applicant->id
    ) {
        abort(403, 'You are not allowed to view this attempt review.');
    }

    $assessment = $participant->assessment;
    $showCorrectAnswers = (bool) ($assessment?->show_correct_answers ?? false);

    $answers = $attempt->answers->map(function ($answer) use ($showCorrectAnswers) {
        $assessmentQuestion = $answer->assessmentQuestion;
        $question = $assessmentQuestion?->question;

        $options = $question?->options?->map(function ($option) use ($showCorrectAnswers) {
            return [
                'id' => $option->id,
                'option_key' => $option->option_key,
                'option_text' => $option->option_text,
                'option_html' => $option->option_html,
                'is_correct' => $showCorrectAnswers ? (bool) $option->is_correct : null,
            ];
        })->values() ?? collect();

        return [
            'answer_id' => $answer->id,
            'assessment_question_id' => $answer->assessment_question_id,
            'question_id' => $answer->question_id,
            'section' => $assessmentQuestion?->section,
            'question_text' => $question?->question_text,
            'question_html' => $question?->question_html,
            'question_type_code' => $question?->question_type_code,
            'selected_option_ids_json' => $answer->selected_option_ids_json,
            'answer_text' => $answer->answer_text,
            'answer_number' => $answer->answer_number,
            'is_correct' => $answer->is_correct,
            'marks_awarded' => $answer->marks_awarded,
            'negative_marks_applied' => $answer->negative_marks_applied,
            'manual_marks' => $answer->manual_marks,
            'marking_remarks' => $answer->marking_remarks,
            'options' => $options,
            'explanation' => $showCorrectAnswers ? $question?->explanation : null,
        ];
    })->values();

    return [
        'participant' => $participant,
        'assessment' => $assessment,
        'schedule' => $participant->schedule,
        'attempt' => $attempt,
        'result' => $participant->result ?? null,
        'show_correct_answers' => $showCorrectAnswers,
        'answers' => $answers,
    ];
}

private function logAttemptActivity(
    AssessmentAttempt $attempt,
    string $eventCode,
    string $severityCode = 'info',
    array $payload = [],
    ?int $assessmentQuestionId = null,
    ?int $questionId = null,
    ?string $ipAddress = null,
    ?string $userAgent = null
): AssessmentAttemptActivityLog {
    $participant = $attempt->participant ?: AssessmentParticipant::query()
        ->where('id', $attempt->assessment_participant_id)
        ->first();

    return AssessmentAttemptActivityLog::create([
        'tenant_id' => $attempt->tenant_id,
        'assessment_attempt_id' => $attempt->id,
        'assessment_participant_id' => $attempt->assessment_participant_id,
        'assessment_id' => $participant?->assessment_id,
        'assessment_schedule_id' => $participant?->assessment_schedule_id,
        'applicant_id' => $participant?->applicant_id ?: $participant?->participant_id,

        'event_code' => $eventCode,
        'severity_code' => $severityCode,
        'assessment_question_id' => $assessmentQuestionId,
        'question_id' => $questionId,
        'event_payload_json' => $payload,

        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'occurred_at' => now(),
    ]);
}
    private function applicant(): Applicant
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        /*
        | Case 1:
        | If users table has applicant_id, use it directly.
        */
        if (!empty($user->applicant_id)) {
            return Applicant::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $user->applicant_id)
                ->firstOrFail();
        }

        /*
        | Case 2:
        | If applicants table has user_id, resolve by logged-in user id.
        */
        if (Schema::hasColumn('applicants', 'user_id')) {
            $applicant = Applicant::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->first();

            if ($applicant) {
                return $applicant;
            }
        }

        /*
        | Case 3:
        | Fallback by email.
        | This matches your self-registration flow where user email
        | and applicant email are usually the same.
        */
        if (!empty($user->email) && Schema::hasColumn('applicants', 'email')) {
            $applicant = Applicant::query()
                ->where('tenant_id', $tenantId)
                ->where('email', $user->email)
                ->first();

            if ($applicant) {
                return $applicant;
            }
        }

        abort(403, 'Applicant context is required. Applicant record is not linked with logged-in user.');
    }
}