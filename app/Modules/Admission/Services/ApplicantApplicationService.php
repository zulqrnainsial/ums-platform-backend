<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use App\Modules\Admission\Models\OfferedProgram;
use App\Modules\Admission\Models\ProgramQuotaSeat;
use App\Modules\Admission\Models\AdmissionPreferenceGroupProgram;
use Illuminate\Support\Facades\DB;

class ApplicantApplicationService
{
    public function __construct(
        private readonly EligibilityRuleEvaluatorService $eligibilityEvaluator
    ) {
    }

    public function eligiblePrograms(int $applicantId, ?int $preferenceGroupId = null): array
    {
        $tenantId = $this->tenantId();

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $applicantId)
            ->firstOrFail();

        $allowedOfferedProgramIds = null;

        if ($preferenceGroupId) {
            $allowedOfferedProgramIds = AdmissionPreferenceGroupProgram::query()
                ->where('tenant_id', $tenantId)
                ->where('admission_preference_group_id', $preferenceGroupId)
                ->where('status_code', 'active')
                ->pluck('offered_program_id')
                ->toArray();

            if (count($allowedOfferedProgramIds) === 0) {
                return [
                    'applicant' => [
                        'id' => $applicant->id,
                        'applicant_no' => $applicant->applicant_no,
                        'full_name' => $applicant->full_name,
                        'profile_status_code' => $applicant->profile_status_code,
                        'applicant_status_code' => $applicant->applicant_status_code,
                    ],
                    'eligible_programs' => [],
                    'not_eligible_programs' => [],
                    'preference_group_id' => $preferenceGroupId,
                ];
            }
        }

        $offeredPrograms = OfferedProgram::query()
            ->where('tenant_id', $tenantId)
            ->where('is_published', true)
            ->where('status_code', 'open')
            ->when($allowedOfferedProgramIds, fn ($query) => $query->whereIn('id', $allowedOfferedProgramIds))
            ->with([
                'admissionSession',
                'program',
                'department',
                'curriculum',
                'quotaSeats' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('display_order');
                },
            ])
            ->orderBy('title')
            ->get();

        $eligiblePrograms = [];
        $notEligiblePrograms = [];

        foreach ($offeredPrograms as $offeredProgram) {
            $quotaResults = [];

            foreach ($offeredProgram->quotaSeats as $quotaSeat) {
                $evaluation = $this->eligibilityEvaluator->evaluate(
                    $applicant,
                    $offeredProgram,
                    $quotaSeat
                );

                $quotaResults[] = [
                    'program_quota_seat_id' => $quotaSeat->id,
                    'quota_code' => $quotaSeat->quota_code,
                    'quota_name' => $quotaSeat->quota_name,
                    'allocated_seats' => $quotaSeat->allocated_seats,
                    'filled_seats' => $quotaSeat->filled_seats,
                    'available_seats' => $quotaSeat->available_seats,
                    'application_fee' => $quotaSeat->application_fee ?? $offeredProgram->application_fee,
                    'admission_fee' => $quotaSeat->admission_fee ?? $offeredProgram->admission_fee,
                    'eligible' => $evaluation->eligible,
                    'passed_rules' => $evaluation->passedRules,
                    'failed_rules' => $evaluation->failedRules,
                    'warning_rules' => $evaluation->warningRules,
                ];
            }

            $eligibleQuotas = array_values(array_filter(
                $quotaResults,
                fn (array $quota) => $quota['eligible'] === true && (int) $quota['available_seats'] > 0
            ));

            $programPayload = [
                'offered_program_id' => $offeredProgram->id,
                'admission_session_id' => $offeredProgram->admission_session_id,
                'admission_session_name' => $offeredProgram->admissionSession?->name,
                'program_id' => $offeredProgram->program_id,
                'program_name' => $offeredProgram->program?->name,
                'department_name' => $offeredProgram->department?->name,
                'curriculum_id' => $offeredProgram->curriculum_id,
                'curriculum_name' => $offeredProgram->curriculum?->name,
                'code' => $offeredProgram->code,
                'title' => $offeredProgram->title,
                'shift_code' => $offeredProgram->shift_code,
                'application_fee' => $offeredProgram->application_fee,
                'admission_fee' => $offeredProgram->admission_fee,
                'requires_test' => $offeredProgram->requires_test,
                'requires_interview' => $offeredProgram->requires_interview,
                'requires_experience' => $offeredProgram->requires_experience,
                'requires_research_profile' => $offeredProgram->requires_research_profile,
                'application_start_date' => $offeredProgram->application_start_date,
                'application_end_date' => $offeredProgram->application_end_date,
                'quota_results' => $quotaResults,
            ];

            if (count($eligibleQuotas) > 0) {
                $programPayload['eligible_quotas'] = $eligibleQuotas;
                $eligiblePrograms[] = $programPayload;
            } else {
                $notEligiblePrograms[] = $programPayload;
            }
        }

        return [
            'applicant' => [
                'id' => $applicant->id,
                'applicant_no' => $applicant->applicant_no,
                'full_name' => $applicant->full_name,
                'profile_status_code' => $applicant->profile_status_code,
                'applicant_status_code' => $applicant->applicant_status_code,
            ],
            'eligible_programs' => $eligiblePrograms,
            'not_eligible_programs' => $notEligiblePrograms,
            'preference_group_id' => $preferenceGroupId,
        ];
    }

    public function apply(array $data): ApplicantProgramApplication
{
    return DB::transaction(function () use ($data) {
        $tenantId = $this->tenantId();

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['applicant_id'])
            ->firstOrFail();

        $offeredProgram = OfferedProgram::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['offered_program_id'])
            ->where('is_published', true)
            ->where('status_code', 'open')
            ->with(['quotaSeats' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('display_order');
            }])
            ->firstOrFail();

        $existing = ApplicantProgramApplication::query()
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicant->id)
            ->where('offered_program_id', $offeredProgram->id)
            ->whereNull('program_quota_seat_id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $eligibleEvaluation = null;

        foreach ($offeredProgram->quotaSeats as $quotaSeat) {
            if ((int) $quotaSeat->available_seats <= 0) {
                continue;
            }

            $evaluation = $this->eligibilityEvaluator->evaluate(
                $applicant,
                $offeredProgram,
                $quotaSeat
            );

            if ($evaluation->eligible) {
                $eligibleEvaluation = $evaluation;
                break;
            }
        }

        if (!$eligibleEvaluation) {
            abort(422, 'Applicant is not eligible for this offered program.');
        }

        $applicationStatus = ($data['submit_now'] ?? false) ? 'submitted' : 'draft';

        return ApplicantProgramApplication::create([
            'tenant_id' => $tenantId,
            'admission_session_id' => $offeredProgram->admission_session_id,
            'applicant_id' => $applicant->id,
            'offered_program_id' => $offeredProgram->id,

            /*
             | Option A:
             | Applicant selects offered program only.
             | Quota/category will be decided later during merit allocation.
             */
            'program_quota_seat_id' => null,

            'application_no' => $this->generateApplicationNo(
                $tenantId,
                $offeredProgram->admission_session_id
            ),
            'preference_order' => $data['preference_order'] ?? 1,

            'eligibility_status_code' => 'eligible',
            'eligibility_result_json' => $eligibleEvaluation->toArray(),
            'eligibility_remarks' => null,

            'application_status_code' => $applicationStatus,
            'document_status_code' => 'pending',
            'fee_status_code' => $this->applicationFeeRequired($offeredProgram) ? 'unpaid' : 'not_required',
            'test_status_code' => $offeredProgram->requires_test ? 'required' : 'not_required',

            'merit_score' => null,
            'merit_rank' => null,
            'submitted_at' => $applicationStatus === 'submitted' ? now() : null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'confirmed_at' => null,

            'remarks' => $data['remarks'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    });
}

    public function submit(int $applicationId): ApplicantProgramApplication
    {
        return DB::transaction(function () use ($applicationId) {
            $tenantId = $this->tenantId();

            $application = ApplicantProgramApplication::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $applicationId)
                ->with(['applicant', 'offeredProgram', 'programQuotaSeat'])
                ->firstOrFail();

            if (!in_array($application->application_status_code, ['draft', 'deficient'], true)) {
                abort(422, 'Only draft or deficient applications can be submitted.');
            }

            $evaluation = null;

if ($application->programQuotaSeat) {
    $evaluation = $this->eligibilityEvaluator->evaluate(
        $application->applicant,
        $application->offeredProgram,
        $application->programQuotaSeat
    );
} else {
    $quotaSeats = ProgramQuotaSeat::query()
        ->where('tenant_id', $tenantId)
        ->where('offered_program_id', $application->offered_program_id)
        ->where('is_active', true)
        ->orderBy('display_order')
        ->get();

    foreach ($quotaSeats as $quotaSeat) {
        $quotaEvaluation = $this->eligibilityEvaluator->evaluate(
            $application->applicant,
            $application->offeredProgram,
            $quotaSeat
        );

        if ($quotaEvaluation->eligible) {
            $evaluation = $quotaEvaluation;
            break;
        }
    }
}

if (!$evaluation) {
    $application->update([
        'eligibility_status_code' => 'not_eligible',
        'eligibility_remarks' => 'Application cannot be submitted because eligibility check failed.',
        'updated_by' => auth()->id(),
    ]);

    abort(422, 'Application cannot be submitted because applicant is not eligible.');
}

            $application->update([
                'eligibility_status_code' => 'eligible',
                'eligibility_result_json' => $evaluation->toArray(),
                'eligibility_remarks' => null,
                'application_status_code' => 'submitted',
                'submitted_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            return $application->fresh();
        });
    }

    public function applicationsForApplicant(int $applicantId): array
    {
        $tenantId = $this->tenantId();

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $applicantId)
            ->firstOrFail();

        $applications = ApplicantProgramApplication::query()
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicant->id)
            ->with(['offeredProgram', 'programQuotaSeat'])
            ->orderBy('preference_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ApplicantProgramApplication $application) => $this->formatApplication($application))
            ->values()
            ->toArray();

        return [
            'applicant' => [
                'id' => $applicant->id,
                'applicant_no' => $applicant->applicant_no,
                'full_name' => $applicant->full_name,
            ],
            'applications' => $applications,
        ];
    }

    public function formatApplication(ApplicantProgramApplication $application): array
    {
        return [
            'id' => $application->id,
            'application_no' => $application->application_no,
            'admission_session_id' => $application->admission_session_id,
            'applicant_id' => $application->applicant_id,
            'offered_program_id' => $application->offered_program_id,
            'offered_program_title' => $application->offeredProgram?->title,
            'program_quota_seat_id' => $application->program_quota_seat_id,
            'quota_name' => $application->programQuotaSeat?->quota_name,
            'preference_order' => $application->preference_order,
            'eligibility_status_code' => $application->eligibility_status_code,
            'application_status_code' => $application->application_status_code,
            'document_status_code' => $application->document_status_code,
            'fee_status_code' => $application->fee_status_code,
            'test_status_code' => $application->test_status_code,
            'merit_score' => $application->merit_score,
            'merit_rank' => $application->merit_rank,
            'submitted_at' => $application->submitted_at,
            'remarks' => $application->remarks,
        ];
    }

    private function applicationFeeRequired(OfferedProgram $program): bool
{
    return (float) $program->application_fee > 0;
}

private function generateApplicationNo(int $tenantId, int $admissionSessionId): string
{
    $prefix = 'APP-' . $admissionSessionId . '-';

    $lastApplicationNo = ApplicantProgramApplication::withTrashed()
        ->where('tenant_id', $tenantId)
        ->where('admission_session_id', $admissionSessionId)
        ->where('application_no', 'like', $prefix . '%')
        ->orderByDesc('id')
        ->value('application_no');

    $nextNumber = 1;

    if ($lastApplicationNo) {
        $lastNumericPart = (int) str_replace($prefix, '', $lastApplicationNo);
        $nextNumber = $lastNumericPart + 1;
    }

    do {
        $applicationNo = $prefix . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

        $exists = ApplicantProgramApplication::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('application_no', $applicationNo)
            ->exists();

        $nextNumber++;
    } while ($exists);

    return $applicationNo;
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