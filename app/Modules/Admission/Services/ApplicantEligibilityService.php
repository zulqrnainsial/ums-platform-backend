<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\OfferedProgram;
use App\Modules\Admission\Models\ProgramQuotaSeat;

class ApplicantEligibilityService
{
    public function __construct(
        private readonly EligibilityRuleEvaluatorService $ruleEvaluator
    ) {
    }

    public function evaluateForProgram(
        int $applicantId,
        int $offeredProgramId,
        ?int $quotaSeatId = null
    ): array {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $applicantId)
            ->firstOrFail();

        $offeredProgram = OfferedProgram::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $offeredProgramId)
            ->firstOrFail();

        $quotaSeat = null;

        if ($quotaSeatId) {
            $quotaSeat = ProgramQuotaSeat::query()
                ->where('tenant_id', $tenantId)
                ->where('offered_program_id', $offeredProgram->id)
                ->where('id', $quotaSeatId)
                ->firstOrFail();
        }

        $result = $this->ruleEvaluator->evaluate($applicant, $offeredProgram, $quotaSeat);

        return [
            'applicant' => [
                'id' => $applicant->id,
                'applicant_no' => $applicant->applicant_no,
                'full_name' => $applicant->full_name,
            ],
            'offered_program' => [
                'id' => $offeredProgram->id,
                'code' => $offeredProgram->code,
                'title' => $offeredProgram->title,
            ],
            'quota' => $quotaSeat ? [
                'id' => $quotaSeat->id,
                'quota_code' => $quotaSeat->quota_code,
                'quota_name' => $quotaSeat->quota_name,
            ] : null,
            'result' => $result->toArray(),
        ];
    }

    public function eligibleProgramsForApplicant(int $applicantId): array
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $applicantId)
            ->firstOrFail();

        $offeredPrograms = OfferedProgram::query()
            ->where('tenant_id', $tenantId)
            ->where('is_published', true)
            ->where('status_code', 'open')
            ->with(['quotaSeats' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('display_order');
            }])
            ->orderBy('title')
            ->get();

        $eligible = [];
        $notEligible = [];

        foreach ($offeredPrograms as $offeredProgram) {
            $quotaResults = [];

            foreach ($offeredProgram->quotaSeats as $quotaSeat) {
                $evaluation = $this->ruleEvaluator->evaluate(
                    $applicant,
                    $offeredProgram,
                    $quotaSeat
                );

                $quotaPayload = [
                    'quota_id' => $quotaSeat->id,
                    'quota_code' => $quotaSeat->quota_code,
                    'quota_name' => $quotaSeat->quota_name,
                    'available_seats' => $quotaSeat->available_seats,
                    'eligible' => $evaluation->eligible,
                    'passed_rules' => $evaluation->passedRules,
                    'failed_rules' => $evaluation->failedRules,
                    'warning_rules' => $evaluation->warningRules,
                ];

                $quotaResults[] = $quotaPayload;
            }

            $eligibleQuotas = array_values(array_filter(
                $quotaResults,
                fn ($quota) => $quota['eligible'] === true
            ));

            $programPayload = [
                'offered_program_id' => $offeredProgram->id,
                'code' => $offeredProgram->code,
                'title' => $offeredProgram->title,
                'application_fee' => $offeredProgram->application_fee,
                'admission_fee' => $offeredProgram->admission_fee,
                'requires_test' => $offeredProgram->requires_test,
                'requires_interview' => $offeredProgram->requires_interview,
                'quota_results' => $quotaResults,
            ];

            if (count($eligibleQuotas) > 0) {
                $programPayload['eligible_quotas'] = $eligibleQuotas;
                $eligible[] = $programPayload;
            } else {
                $notEligible[] = $programPayload;
            }
        }

        return [
            'applicant' => [
                'id' => $applicant->id,
                'applicant_no' => $applicant->applicant_no,
                'full_name' => $applicant->full_name,
            ],
            'eligible_programs' => $eligible,
            'not_eligible_programs' => $notEligible,
        ];
    }
}