<?php

namespace App\Modules\Assessment\Services;

use Illuminate\Support\Facades\DB;

class AssessmentAnalyticsService
{
    public function dashboard(array $filters): array
    {
        $tenantId = $this->tenantId();

        $assessmentId = $filters['assessment_id'] ?? null;
        $scheduleId = $filters['assessment_schedule_id'] ?? null;

        $summary = $this->summary($tenantId, $assessmentId, $scheduleId);
        $topicAnalysis = $this->aggregateAnalysis($tenantId, $assessmentId, $scheduleId, 'topic_analysis_json');
        $difficultyAnalysis = $this->aggregateAnalysis($tenantId, $assessmentId, $scheduleId, 'difficulty_analysis_json');
        $questionTypeAnalysis = $this->aggregateAnalysis($tenantId, $assessmentId, $scheduleId, 'question_type_analysis_json');
        $applicantResults = $this->applicantResults($tenantId, $assessmentId, $scheduleId);

        return [
            'summary' => $summary,
            'topic_analysis' => $topicAnalysis,
            'difficulty_analysis' => $difficultyAnalysis,
            'question_type_analysis' => $questionTypeAnalysis,
            'applicant_results' => $applicantResults,
        ];
    }

    private function summary(int $tenantId, mixed $assessmentId, mixed $scheduleId): array
    {
        $participantQuery = DB::table('assessment_participants as ap')
            ->where('ap.tenant_id', $tenantId);

        $resultQuery = DB::table('assessment_results as ar')
            ->leftJoin('assessment_participants as ap', 'ap.id', '=', 'ar.assessment_participant_id')
            ->where('ar.tenant_id', $tenantId);

        $attemptQuery = DB::table('assessment_attempts as aa')
            ->leftJoin('assessment_participants as ap', 'ap.id', '=', 'aa.assessment_participant_id')
            ->where('aa.tenant_id', $tenantId);

        if ($assessmentId) {
            $participantQuery->where('ap.assessment_id', $assessmentId);
            $resultQuery->where('ar.assessment_id', $assessmentId);
            $attemptQuery->where('ap.assessment_id', $assessmentId);
        }

        if ($scheduleId) {
            $participantQuery->where('ap.assessment_schedule_id', $scheduleId);
            $resultQuery->where('ap.assessment_schedule_id', $scheduleId);
            $attemptQuery->where('ap.assessment_schedule_id', $scheduleId);
        }

        $participants = (clone $participantQuery)->count();
        $attempts = (clone $attemptQuery)->count();
        $results = (clone $resultQuery)->count();

        $passCount = (clone $resultQuery)->where('ar.is_passed', 1)->count();
        $failCount = (clone $resultQuery)->where('ar.is_passed', 0)->count();

        $marks = (clone $resultQuery)
            ->selectRaw('
                COALESCE(AVG(ar.final_marks), 0) as average_marks,
                COALESCE(MAX(ar.final_marks), 0) as highest_marks,
                COALESCE(MIN(ar.final_marks), 0) as lowest_marks,
                COALESCE(AVG(ar.percentage), 0) as average_percentage
            ')
            ->first();

        return [
            'participants' => $participants,
            'attempts' => $attempts,
            'results' => $results,
            'pass_count' => $passCount,
            'fail_count' => $failCount,
            'pending_count' => max(0, $participants - $results),
            'average_marks' => round((float) ($marks->average_marks ?? 0), 2),
            'highest_marks' => round((float) ($marks->highest_marks ?? 0), 2),
            'lowest_marks' => round((float) ($marks->lowest_marks ?? 0), 2),
            'average_percentage' => round((float) ($marks->average_percentage ?? 0), 2),
            'pass_percentage' => $results > 0 ? round(($passCount / $results) * 100, 2) : 0,
            'fail_percentage' => $results > 0 ? round(($failCount / $results) * 100, 2) : 0,
        ];
    }

    private function aggregateAnalysis(
        int $tenantId,
        mixed $assessmentId,
        mixed $scheduleId,
        string $jsonColumn
    ): array {
        $query = DB::table('assessment_section_results as asr')
            ->leftJoin('assessment_results as ar', 'ar.id', '=', 'asr.assessment_result_id')
            ->leftJoin('assessment_participants as ap', 'ap.id', '=', 'ar.assessment_participant_id')
            ->where('asr.tenant_id', $tenantId)
            ->whereNotNull("asr.$jsonColumn");

        if ($assessmentId) {
            $query->where('ar.assessment_id', $assessmentId);
        }

        if ($scheduleId) {
            $query->where('ap.assessment_schedule_id', $scheduleId);
        }

        $rows = $query
            ->select("asr.$jsonColumn")
            ->get();

        $bucket = [];

        foreach ($rows as $row) {
            $items = json_decode($row->{$jsonColumn}, true);

            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                $label = $item['label'] ?? 'Unclassified';

                if (!isset($bucket[$label])) {
                    $bucket[$label] = [
                        'label' => $label,
                        'total_questions' => 0,
                        'attempted' => 0,
                        'correct' => 0,
                        'wrong' => 0,
                        'manual_required' => 0,
                        'total_marks' => 0,
                        'obtained_marks' => 0,
                        'negative_marks' => 0,
                        'final_marks' => 0,
                        'percentage' => 0,
                    ];
                }

                $bucket[$label]['total_questions'] += (int) ($item['total_questions'] ?? 0);
                $bucket[$label]['attempted'] += (int) ($item['attempted'] ?? 0);
                $bucket[$label]['correct'] += (int) ($item['correct'] ?? 0);
                $bucket[$label]['wrong'] += (int) ($item['wrong'] ?? 0);
                $bucket[$label]['manual_required'] += (int) ($item['manual_required'] ?? 0);
                $bucket[$label]['total_marks'] += (float) ($item['total_marks'] ?? 0);
                $bucket[$label]['obtained_marks'] += (float) ($item['obtained_marks'] ?? 0);
                $bucket[$label]['negative_marks'] += (float) ($item['negative_marks'] ?? 0);
                $bucket[$label]['final_marks'] += (float) ($item['final_marks'] ?? 0);
            }
        }

        foreach ($bucket as $label => $item) {
            $bucket[$label]['percentage'] = $item['total_marks'] > 0
                ? round(($item['final_marks'] / $item['total_marks']) * 100, 2)
                : 0;

            $bucket[$label]['total_marks'] = round($bucket[$label]['total_marks'], 2);
            $bucket[$label]['obtained_marks'] = round($bucket[$label]['obtained_marks'], 2);
            $bucket[$label]['negative_marks'] = round($bucket[$label]['negative_marks'], 2);
            $bucket[$label]['final_marks'] = round($bucket[$label]['final_marks'], 2);
        }

        return collect($bucket)
            ->sortBy('percentage')
            ->values()
            ->toArray();
    }

    private function applicantResults(int $tenantId, mixed $assessmentId, mixed $scheduleId): array
    {
        $query = DB::table('assessment_results as ar')
            ->leftJoin('assessment_participants as ap', 'ap.id', '=', 'ar.assessment_participant_id')
            ->leftJoin('assessment_attempts as aa', 'aa.id', '=', 'ar.assessment_attempt_id')
            ->leftJoin('assessments as a', 'a.id', '=', 'ar.assessment_id')
            ->leftJoin('assessment_schedules as s', 's.id', '=', 'ap.assessment_schedule_id')
            ->leftJoin('applicants as app', 'app.id', '=', 'ap.applicant_id')
            ->where('ar.tenant_id', $tenantId);

        if ($assessmentId) {
            $query->where('ar.assessment_id', $assessmentId);
        }

        if ($scheduleId) {
            $query->where('ap.assessment_schedule_id', $scheduleId);
        }

        return $query
            ->select([
                'ar.id',
                'ar.total_marks',
                'ar.obtained_marks',
                'ar.negative_marks',
                'ar.final_marks',
                'ar.percentage',
                'ar.is_passed',
                'ar.result_status_code',

                'ap.roll_no',
                'aa.attempt_no',

                'a.code as assessment_code',
                'a.title as assessment_title',

                's.schedule_code',
                's.title as schedule_title',

                'app.applicant_no',
                'app.full_name as applicant_name',
                'app.cnic_bform',
            ])
            ->orderByDesc('ar.percentage')
            ->limit(100)
            ->get()
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