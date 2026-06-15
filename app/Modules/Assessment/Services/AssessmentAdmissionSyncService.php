<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\AssessmentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssessmentAdmissionSyncService
{
    public function syncResultToApplicantTestResult(int $assessmentResultId): array
    {
        if (!Schema::hasTable('applicant_test_results')) {
            abort(500, 'applicant_test_results table does not exist.');
        }

        $result = AssessmentResult::query()
            ->with([
                'participant.assessment',
                'participant.schedule',
            ])
            ->where('id', $assessmentResultId)
            ->firstOrFail();

        $participant = $result->participant;

        if (!$participant) {
            abort(422, 'Assessment participant not found.');
        }

        if ($participant->participant_type_code !== 'applicant') {
            return [
                'synced' => false,
                'message' => 'Participant is not an applicant. Sync skipped.',
            ];
        }

        if (!$participant->applicant_id) {
            return [
                'synced' => false,
                'message' => 'Applicant reference not found on participant. Sync skipped.',
            ];
        }

        $assessment = $participant->assessment;

        if (!$assessment) {
            abort(422, 'Assessment not found.');
        }

        return DB::transaction(function () use ($result, $participant, $assessment) {
            $payload = $this->buildApplicantTestResultPayload($result, $participant, $assessment);

            $existingId = null;

            if (Schema::hasColumn('applicant_test_results', 'assessment_result_id')) {
                $existingId = DB::table('applicant_test_results')
                    ->where('tenant_id', $result->tenant_id)
                    ->where('assessment_result_id', $result->id)
                    ->value('id');
            }

            if (!$existingId) {
                $existingId = DB::table('applicant_test_results')
                    ->where('tenant_id', $result->tenant_id)
                    ->where('applicant_id', $participant->applicant_id)
                    ->where('assessment_id', $assessment->id)
                    ->value('id');
            }

            if ($existingId) {
                DB::table('applicant_test_results')
                    ->where('id', $existingId)
                    ->update($payload);

                return [
                    'synced' => true,
                    'action' => 'updated',
                    'applicant_test_result_id' => $existingId,
                ];
            }

            $payload['created_at'] = now();

            $newId = DB::table('applicant_test_results')->insertGetId($payload);

            return [
                'synced' => true,
                'action' => 'created',
                'applicant_test_result_id' => $newId,
            ];
        });
    }

    private function buildApplicantTestResultPayload(
        AssessmentResult $result,
        mixed $participant,
        mixed $assessment
    ): array {
        $payload = [
            'tenant_id' => $result->tenant_id,
            'applicant_id' => $participant->applicant_id,
            'updated_at' => now(),
        ];

        $this->putIfColumnExists($payload, 'assessment_id', $assessment->id);
        $this->putIfColumnExists($payload, 'assessment_participant_id', $participant->id);
        $this->putIfColumnExists($payload, 'assessment_attempt_id', $result->assessment_attempt_id);
        $this->putIfColumnExists($payload, 'assessment_result_id', $result->id);
        $this->putIfColumnExists($payload, 'result_source_code', 'internal_assessment');
        $this->putIfColumnExists($payload, 'synced_at', now());

        /*
         | Common applicant_test_results columns.
         | We check column existence because your table may already have
         | slightly different field names.
         */
        $this->putIfColumnExists($payload, 'test_code', $assessment->code ?? null);
        $this->putIfColumnExists($payload, 'test_name', $assessment->title ?? null);
        $this->putIfColumnExists($payload, 'test_type_code', 'internal_assessment');
        $this->putIfColumnExists($payload, 'roll_no', $participant->roll_no ?? null);

        $this->putIfColumnExists($payload, 'total_marks', $result->total_marks);
        $this->putIfColumnExists($payload, 'obtained_marks', $result->final_marks);
        $this->putIfColumnExists($payload, 'percentage', $result->percentage);

        $this->putIfColumnExists(
            $payload,
            'result_status_code',
            $result->is_passed ? 'pass' : 'fail'
        );

        $this->putIfColumnExists(
            $payload,
            'status_code',
            $result->result_status_code
        );

        $this->putIfColumnExists(
            $payload,
            'test_date',
            $participant->schedule?->start_at ?? $result->generated_at ?? now()
        );

        $this->putIfColumnExists(
            $payload,
            'remarks',
            'Synced from internal assessment result #' . $result->id
        );

        $this->putIfColumnExists($payload, 'created_by', auth()->id());
        $this->putIfColumnExists($payload, 'updated_by', auth()->id());

        return $payload;
    }

    private function putIfColumnExists(array &$payload, string $column, mixed $value): void
    {
        if (Schema::hasColumn('applicant_test_results', $column)) {
            $payload[$column] = $value;
        }
    }
}