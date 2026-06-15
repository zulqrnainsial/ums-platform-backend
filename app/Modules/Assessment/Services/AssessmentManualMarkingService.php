<?php

namespace App\Modules\Assessment\Services;

use Illuminate\Support\Facades\DB;

class AssessmentManualMarkingService
{
    public function pending(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('assessment_attempt_answers as aaa')
            ->leftJoin('assessment_attempts as aa', 'aa.id', '=', 'aaa.assessment_attempt_id')
            ->leftJoin('assessment_participants as ap', 'ap.id', '=', 'aa.assessment_participant_id')
            ->leftJoin('assessment_questions as aq', 'aq.id', '=', 'aaa.assessment_question_id')
            ->leftJoin('questions as q', 'q.id', '=', 'aq.question_id')
            ->leftJoin('assessments as a', 'a.id', '=', 'ap.assessment_id')
            ->leftJoin('assessment_schedules as s', 's.id', '=', 'ap.assessment_schedule_id')
            ->leftJoin('applicants as app', 'app.id', '=', 'ap.applicant_id')
            ->where('aaa.tenant_id', $tenantId)
            ->where(function ($q2) {
                $q2->whereNull('aaa.is_correct')
                    ->orWhereIn('q.question_type_code', [
                        'short_answer',
                        'long_answer',
                        'fill_blank',
                        'code',
                        'file_upload',
                    ]);
            })
            ->select([
                'aaa.id',
                'aaa.assessment_attempt_id',
                'aaa.assessment_question_id',
                'aaa.question_id',
                'aaa.answer_text',
                'aaa.answer_number',
                'aaa.selected_option_ids_json',
                'aaa.is_correct',
                'aaa.marks_awarded',
                'aaa.negative_marks_applied',
                'aaa.manual_marks',
                'aaa.marking_remarks',
                'aaa.answered_at',

                'aa.attempt_no',
                'aa.status_code as attempt_status_code',

                'ap.id as assessment_participant_id',
                'ap.roll_no',
                'ap.applicant_id',

                'a.id as assessment_id',
                'a.code as assessment_code',
                'a.title as assessment_title',

                's.id as assessment_schedule_id',
                's.schedule_code',
                's.title as schedule_title',

                'app.applicant_no',
                'app.full_name as applicant_name',
                'app.cnic_bform',

                'q.question_text',
                'q.question_html',
                'q.question_type_code',
                'q.difficulty_code',
                'q.cognitive_level_code',

                'aq.marks',
                'aq.negative_marks',
            ]);

        if (!empty($filters['assessment_id'])) {
            $query->where('ap.assessment_id', $filters['assessment_id']);
        }

        if (!empty($filters['assessment_schedule_id'])) {
            $query->where('ap.assessment_schedule_id', $filters['assessment_schedule_id']);
        }

        if (!empty($filters['question_type_code'])) {
            $query->where('q.question_type_code', $filters['question_type_code']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('ap.roll_no', 'like', "%{$search}%")
                    ->orWhere('app.applicant_no', 'like', "%{$search}%")
                    ->orWhere('app.full_name', 'like', "%{$search}%")
                    ->orWhere('app.cnic_bform', 'like', "%{$search}%")
                    ->orWhere('q.question_text', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('aaa.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function mark(int $answerId, array $payload): array
    {
        $tenantId = $this->tenantId();

        $answer = DB::table('assessment_attempt_answers')
            ->where('tenant_id', $tenantId)
            ->where('id', $answerId)
            ->first();

        if (!$answer) {
            abort(404, 'Answer not found.');
        }

        $marksAwarded = (float) ($payload['marks_awarded'] ?? 0);
        $negativeMarks = (float) ($payload['negative_marks_applied'] ?? 0);

        DB::table('assessment_attempt_answers')
            ->where('tenant_id', $tenantId)
            ->where('id', $answerId)
            ->update([
                'is_correct' => $payload['is_correct'] ?? null,
                'marks_awarded' => $marksAwarded,
                'negative_marks_applied' => $negativeMarks,
                'manual_marks' => $marksAwarded,
                'marking_remarks' => $payload['marking_remarks'] ?? null,
                'marked_at' => now(),
                'marked_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        $this->regenerateAttemptMarks((int) $answer->assessment_attempt_id);

        return [
            'answer_id' => $answerId,
            'assessment_attempt_id' => $answer->assessment_attempt_id,
        ];
    }

    private function regenerateAttemptMarks(int $attemptId): void
    {
        $tenantId = $this->tenantId();

        $totals = DB::table('assessment_attempt_answers')
            ->where('tenant_id', $tenantId)
            ->where('assessment_attempt_id', $attemptId)
            ->selectRaw('
                COALESCE(SUM(marks_awarded), 0) as obtained_marks,
                COALESCE(SUM(negative_marks_applied), 0) as negative_marks
            ')
            ->first();

        $obtained = (float) ($totals->obtained_marks ?? 0);
        $negative = (float) ($totals->negative_marks ?? 0);
        $final = max(0, $obtained - $negative);

        $attempt = DB::table('assessment_attempts')
            ->where('tenant_id', $tenantId)
            ->where('id', $attemptId)
            ->first();

        if (!$attempt) {
            return;
        }

        $participant = DB::table('assessment_participants')
            ->where('tenant_id', $tenantId)
            ->where('id', $attempt->assessment_participant_id)
            ->first();

        $assessment = $participant
            ? DB::table('assessments')->where('id', $participant->assessment_id)->first()
            : null;

        $totalMarks = (float) ($assessment->total_marks ?? 0);
        $percentage = $totalMarks > 0 ? round(($final / $totalMarks) * 100, 2) : 0;

        DB::table('assessment_attempts')
            ->where('tenant_id', $tenantId)
            ->where('id', $attemptId)
            ->update([
                'obtained_marks' => $obtained,
                'negative_marks' => $negative,
                'final_marks' => $final,
                'percentage' => $percentage,
                'status_code' => 'evaluated',
                'updated_at' => now(),
            ]);

        if ($participant) {
            DB::table('assessment_participants')
                ->where('tenant_id', $tenantId)
                ->where('id', $participant->id)
                ->update([
                    'attempt_status_code' => 'evaluated',
                    'updated_at' => now(),
                ]);
        }

        if (DB::getSchemaBuilder()->hasTable('assessment_results')) {
            DB::table('assessment_results')
                ->where('tenant_id', $tenantId)
                ->where('assessment_attempt_id', $attemptId)
                ->update([
                    'obtained_marks' => $obtained,
                    'negative_marks' => $negative,
                    'final_marks' => $final,
                    'percentage' => $percentage,
                    'is_passed' => $assessment && $final >= (float) ($assessment->passing_marks ?? 0),
                    'result_status_code' => 'generated',
                    'updated_at' => now(),
                ]);
        }
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