<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\ApplicantApplicationGroup;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use App\Modules\Admission\Models\OfferedProgram;
use Illuminate\Support\Facades\DB;

class ApplicantPreferenceGroupService
{
    public function __construct(
        private readonly EligibilityRuleEvaluatorService $eligibilityEvaluator
    ) {
    }

    public function getGroupForApplicant(
        int $applicantId,
        ?int $admissionSessionId = null
    ): array {
        $tenantId = $this->tenantId();

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $applicantId)
            ->firstOrFail();

        $groupQuery = ApplicantApplicationGroup::query()
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicant->id);

        if ($admissionSessionId) {
            $groupQuery->where('admission_session_id', $admissionSessionId);
        }

        $group = $groupQuery
            ->with([
                'admissionSession',
                'applications' => function ($query) {
                    $query->with(['offeredProgram', 'programQuotaSeat'])
                        ->orderBy('preference_order')
                        ->orderBy('id');
                },
            ])
            ->orderByDesc('id')
            ->first();

        return [
            'applicant' => [
                'id' => $applicant->id,
                'applicant_no' => $applicant->applicant_no,
                'full_name' => $applicant->full_name,
            ],
            'group' => $group ? $this->formatGroup($group) : null,
        ];
    }

    public function addPreference(
    int $applicantId,
    int $offeredProgramId,
    ?string $remarks = null
): array {
    return DB::transaction(function () use (
        $applicantId,
        $offeredProgramId,
        $remarks
    ) {
        $tenantId = $this->tenantId();

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $applicantId)
            ->firstOrFail();

        $offeredProgram = OfferedProgram::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $offeredProgramId)
            ->where('is_published', true)
            ->where('status_code', 'open')
            ->with(['quotaSeats' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('display_order');
            }])
            ->firstOrFail();

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

        $group = $this->firstOrCreateGroup(
            tenantId: $tenantId,
            applicant: $applicant,
            admissionSessionId: (int) $offeredProgram->admission_session_id
        );

        if (!in_array($group->status_code, ['draft', 'deficient'], true)) {
            abort(422, 'Preferences can be changed only while the application group is draft/deficient.');
        }

        $existing = ApplicantProgramApplication::query()
            ->where('tenant_id', $tenantId)
            ->where('applicant_application_group_id', $group->id)
            ->where('offered_program_id', $offeredProgram->id)
            ->whereNull('program_quota_seat_id')
            ->first();

        if ($existing) {
            return $this->getGroupForApplicant($applicant->id, $group->admission_session_id);
        }

        $nextPreference = ApplicantProgramApplication::query()
            ->where('tenant_id', $tenantId)
            ->where('applicant_application_group_id', $group->id)
            ->max('preference_order');

        $preferenceOrder = ((int) $nextPreference) + 1;

        ApplicantProgramApplication::create([
            'tenant_id' => $tenantId,
            'admission_session_id' => $offeredProgram->admission_session_id,
            'applicant_id' => $applicant->id,
            'applicant_application_group_id' => $group->id,
            'offered_program_id' => $offeredProgram->id,

            /*
             | Option A:
             | Preference is offered program only.
             | Quota/category will be assigned during merit allocation.
             */
            'program_quota_seat_id' => null,

            'application_no' => $this->generateApplicationNo(
                $tenantId,
                (int) $offeredProgram->admission_session_id
            ),
            'preference_order' => $preferenceOrder,

            'eligibility_status_code' => 'eligible',
            'eligibility_result_json' => $eligibleEvaluation->toArray(),
            'eligibility_remarks' => null,

            'application_status_code' => 'draft',
            'document_status_code' => 'pending',
            'fee_status_code' => $this->applicationFeeRequired($offeredProgram)
                ? 'unpaid'
                : 'not_required',
            'test_status_code' => $offeredProgram->requires_test ? 'required' : 'not_required',

            'merit_score' => null,
            'merit_rank' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'confirmed_at' => null,

            'remarks' => $remarks,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return $this->getGroupForApplicant($applicant->id, $group->admission_session_id);
    });
}

    public function reorderPreferences(int $applicantId, array $items): array
    {
        return DB::transaction(function () use ($applicantId, $items) {
            $tenantId = $this->tenantId();

            $applicant = Applicant::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $applicantId)
                ->firstOrFail();

            $applicationIds = collect($items)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (count($applicationIds) === 0) {
                abort(422, 'No preferences supplied for reorder.');
            }

            $applications = ApplicantProgramApplication::query()
                ->where('tenant_id', $tenantId)
                ->where('applicant_id', $applicant->id)
                ->whereIn('id', $applicationIds)
                ->get()
                ->keyBy('id');

            if ($applications->count() !== count($applicationIds)) {
                abort(403, 'One or more preferences do not belong to this applicant.');
            }

            $groupId = $applications->first()?->applicant_application_group_id;

            if (!$groupId) {
                abort(422, 'Application group was not found.');
            }

            $group = ApplicantApplicationGroup::query()
                ->where('tenant_id', $tenantId)
                ->where('applicant_id', $applicant->id)
                ->where('id', $groupId)
                ->firstOrFail();

            if (!in_array($group->status_code, ['draft', 'deficient'], true)) {
                abort(422, 'Preferences can be reordered only while the group is draft/deficient.');
            }

            $orders = collect($items)
                ->map(fn ($item) => (int) ($item['preference_order'] ?? 0))
                ->filter()
                ->values()
                ->all();

            if (count($orders) !== count(array_unique($orders))) {
                abort(422, 'Preference order must be unique.');
            }

            foreach ($items as $item) {
                $id = (int) $item['id'];
                $order = (int) $item['preference_order'];

                $applications[$id]->update([
                    'preference_order' => $order,
                    'updated_by' => auth()->id(),
                ]);
            }

            return $this->getGroupForApplicant($applicant->id, $group->admission_session_id);
        });
    }

    public function removePreference(int $applicantId, int $applicationId): array
    {
        return DB::transaction(function () use ($applicantId, $applicationId) {
            $tenantId = $this->tenantId();

            $application = ApplicantProgramApplication::query()
                ->where('tenant_id', $tenantId)
                ->where('applicant_id', $applicantId)
                ->where('id', $applicationId)
                ->firstOrFail();

            $groupId = $application->applicant_application_group_id;
            $admissionSessionId = $application->admission_session_id;

            $group = ApplicantApplicationGroup::query()
                ->where('tenant_id', $tenantId)
                ->where('applicant_id', $applicantId)
                ->where('id', $groupId)
                ->firstOrFail();

            if (!in_array($group->status_code, ['draft', 'deficient'], true)) {
                abort(422, 'Preferences can be removed only while the group is draft/deficient.');
            }

            $application->delete();

            $this->normalizePreferenceOrder($tenantId, $group->id);

            return $this->getGroupForApplicant($applicantId, $admissionSessionId);
        });
    }

    public function submitGroup(int $applicantId, int $groupId): array
    {
        return DB::transaction(function () use ($applicantId, $groupId) {
            $tenantId = $this->tenantId();

            $group = ApplicantApplicationGroup::query()
                ->where('tenant_id', $tenantId)
                ->where('applicant_id', $applicantId)
                ->where('id', $groupId)
                ->with('applications')
                ->firstOrFail();

            if (!in_array($group->status_code, ['draft', 'deficient'], true)) {
                abort(422, 'Only draft or deficient application groups can be submitted.');
            }

            if ($group->applications->count() === 0) {
                abort(422, 'At least one program preference is required before submission.');
            }

            $group->update([
                'status_code' => 'submitted',
                'submitted_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            ApplicantProgramApplication::query()
                ->where('tenant_id', $tenantId)
                ->where('applicant_application_group_id', $group->id)
                ->whereIn('application_status_code', ['draft', 'deficient'])
                ->update([
                    'application_status_code' => 'submitted',
                    'submitted_at' => now(),
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            return $this->getGroupForApplicant($applicantId, $group->admission_session_id);
        });
    }

    private function firstOrCreateGroup(
        int $tenantId,
        Applicant $applicant,
        int $admissionSessionId
    ): ApplicantApplicationGroup {
        $group = ApplicantApplicationGroup::query()
            ->where('tenant_id', $tenantId)
            ->where('applicant_id', $applicant->id)
            ->where('admission_session_id', $admissionSessionId)
            ->first();

        if ($group) {
            return $group;
        }

        return ApplicantApplicationGroup::create([
            'tenant_id' => $tenantId,
            'admission_session_id' => $admissionSessionId,
            'applicant_id' => $applicant->id,
            'application_group_no' => $this->generateGroupNo($tenantId, $admissionSessionId),
            'status_code' => 'draft',
            'submitted_at' => null,
            'remarks' => null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    private function normalizePreferenceOrder(int $tenantId, int $groupId): void
    {
        $applications = ApplicantProgramApplication::query()
            ->where('tenant_id', $tenantId)
            ->where('applicant_application_group_id', $groupId)
            ->orderBy('preference_order')
            ->orderBy('id')
            ->get();

        $order = 1;

        foreach ($applications as $application) {
            $application->update([
                'preference_order' => $order,
                'updated_by' => auth()->id(),
            ]);

            $order++;
        }
    }

    private function formatGroup(ApplicantApplicationGroup $group): array
    {
        return [
            'id' => $group->id,
            'tenant_id' => $group->tenant_id,
            'admission_session_id' => $group->admission_session_id,
            'admission_session_name' => $group->admissionSession?->name,
            'applicant_id' => $group->applicant_id,
            'application_group_no' => $group->application_group_no,
            'status_code' => $group->status_code,
            'submitted_at' => $group->submitted_at,
            'remarks' => $group->remarks,
            'applications' => $group->applications
                ->map(fn (ApplicantProgramApplication $application) => $this->formatApplication($application))
                ->values()
                ->toArray(),
        ];
    }

    private function formatApplication(ApplicantProgramApplication $application): array
    {
        return [
            'id' => $application->id,
            'application_no' => $application->application_no,
            'admission_session_id' => $application->admission_session_id,
            'applicant_id' => $application->applicant_id,
            'applicant_application_group_id' => $application->applicant_application_group_id,
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

    private function generateGroupNo(int $tenantId, int $admissionSessionId): string
    {
        $count = ApplicantApplicationGroup::query()
            ->where('tenant_id', $tenantId)
            ->where('admission_session_id', $admissionSessionId)
            ->count() + 1;

        return 'APPG-' . $admissionSessionId . '-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
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
