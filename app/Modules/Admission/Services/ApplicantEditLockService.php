<?php

namespace App\Modules\Admission\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplicantEditLockService
{
    public function getLockStatus(int $applicantId, ?int $tenantId = null): array
    {
        $hasSubmittedApplication = $this->hasSubmittedApplication($applicantId, $tenantId);
        $hasMeritCalculated = $this->hasMeritCalculated($applicantId, $tenantId);
        $hasOffer = $this->hasOffer($applicantId, $tenantId);
        $hasAcceptedOffer = $this->hasAcceptedOffer($applicantId, $tenantId);
        $hasConfirmedAdmission = $this->hasConfirmedAdmission($applicantId, $tenantId);
        $hasFinalizedAdmission = $this->hasFinalizedAdmission($applicantId, $tenantId);

        $lockAdmissionData = $hasSubmittedApplication
            || $hasMeritCalculated
            || $hasOffer
            || $hasAcceptedOffer
            || $hasConfirmedAdmission
            || $hasFinalizedAdmission;

        return [
            'applicant_id' => $applicantId,

            'has_submitted_application' => $hasSubmittedApplication,
            'has_merit_calculated' => $hasMeritCalculated,
            'has_offer' => $hasOffer,
            'has_accepted_offer' => $hasAcceptedOffer,
            'has_confirmed_admission' => $hasConfirmedAdmission,
            'has_finalized_admission' => $hasFinalizedAdmission,

            'locks' => [
                'personal_info' => $lockAdmissionData,
                'qualifications' => $lockAdmissionData,
                'test_results' => $lockAdmissionData,
                'documents' => $lockAdmissionData,
                'eligible_programs' => $lockAdmissionData,
                'preferences' => $lockAdmissionData,
                'applications' => $hasSubmittedApplication || $hasMeritCalculated || $hasOffer || $hasConfirmedAdmission,
                'offer_response' => $hasConfirmedAdmission || $hasFinalizedAdmission,
                'payment_submission' => $hasConfirmedAdmission || $hasFinalizedAdmission,
            ],

            'editable' => [
                'contact_info' => !$hasFinalizedAdmission,
                'current_address' => !$hasFinalizedAdmission,
                'remarks' => !$hasFinalizedAdmission,
            ],

            'message' => $this->message(
                $hasSubmittedApplication,
                $hasMeritCalculated,
                $hasOffer,
                $hasAcceptedOffer,
                $hasConfirmedAdmission,
                $hasFinalizedAdmission
            ),
        ];
    }

    public function assertCanEdit(int $applicantId, ?int $tenantId, string $area): void
    {
        $status = $this->getLockStatus($applicantId, $tenantId);

        $locked = (bool) data_get($status, "locks.{$area}", false);

        if ($locked) {
            abort(response()->json([
                'message' => $status['message'] ?: 'This section is locked because the application has already been submitted.',
                'lock_status' => $status,
            ], 423));
        }
    }

    private function hasSubmittedApplication(int $applicantId, ?int $tenantId): bool
{
    if (Schema::hasTable('applicant_application_groups')) {
        $groupQuery = DB::table('applicant_application_groups')
            ->where('applicant_id', $applicantId)
            ->when(
                $tenantId && Schema::hasColumn('applicant_application_groups', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            );

        if (Schema::hasColumn('applicant_application_groups', 'status_code')) {
            $groupQuery->whereIn('status_code', [
                'submitted',
                'under_review',
                'merit_processed',
                'offered',
                'accepted',
                'confirmed',
                'final_submitted',
                'approved',
                'active',
            ]);
        }

        if ($groupQuery->exists()) {
            return true;
        }
    }

    if (Schema::hasTable('applicant_program_applications')) {
        $applicationQuery = DB::table('applicant_program_applications')
            ->where('applicant_id', $applicantId)
            ->when(
                $tenantId && Schema::hasColumn('applicant_program_applications', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            );

        if (Schema::hasColumn('applicant_program_applications', 'application_status_code')) {
            $applicationQuery->whereIn('application_status_code', [
                'submitted',
                'under_review',
                'merit_processed',
                'offered',
                'accepted',
                'confirmed',
                'final_submitted',
                'approved',
                'active',
            ]);
        }

        if ($applicationQuery->exists()) {
            return true;
        }
    }

    return false;
}

    private function hasMeritCalculated(int $applicantId, ?int $tenantId): bool
    {
        if (!Schema::hasTable('admission_applicant_merit_scores')) {
            return false;
        }

        return DB::table('admission_applicant_merit_scores')
            ->where('applicant_id', $applicantId)
            ->when(
                $tenantId && Schema::hasColumn('admission_applicant_merit_scores', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            )
            ->exists();
    }

    private function hasOffer(int $applicantId, ?int $tenantId): bool
    {
        if (!Schema::hasTable('admission_merit_list_applicants')) {
            return false;
        }

        return DB::table('admission_merit_list_applicants')
            ->where('applicant_id', $applicantId)
            ->when(
                $tenantId && Schema::hasColumn('admission_merit_list_applicants', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            )
            ->when(Schema::hasColumn('admission_merit_list_applicants', 'offer_status_code'), function ($q) {
                $q->whereIn('offer_status_code', ['offered', 'accepted']);
            })
            ->exists();
    }

    private function hasAcceptedOffer(int $applicantId, ?int $tenantId): bool
    {
        if (!Schema::hasTable('admission_merit_list_applicants')) {
            return false;
        }

        return DB::table('admission_merit_list_applicants')
            ->where('applicant_id', $applicantId)
            ->when(
                $tenantId && Schema::hasColumn('admission_merit_list_applicants', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            )
            ->when(Schema::hasColumn('admission_merit_list_applicants', 'offer_status_code'), function ($q) {
                $q->where('offer_status_code', 'accepted');
            })
            ->exists();
    }

    private function hasConfirmedAdmission(int $applicantId, ?int $tenantId): bool
    {
        if (!Schema::hasTable('admission_confirmations')) {
            return false;
        }

        return DB::table('admission_confirmations')
            ->where('applicant_id', $applicantId)
            ->when(
                $tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            )
            ->when(Schema::hasColumn('admission_confirmations', 'status_code'), function ($q) {
                $q->where('status_code', 'confirmed');
            })
            ->exists();
    }

    private function hasFinalizedAdmission(int $applicantId, ?int $tenantId): bool
    {
        if (!Schema::hasTable('admission_confirmations')) {
            return false;
        }

        return DB::table('admission_confirmations')
            ->where('applicant_id', $applicantId)
            ->when(
                $tenantId && Schema::hasColumn('admission_confirmations', 'tenant_id'),
                fn ($q) => $q->where('tenant_id', $tenantId)
            )
            ->when(Schema::hasColumn('admission_confirmations', 'finalization_status_code'), function ($q) {
                $q->where('finalization_status_code', 'finalized');
            })
            ->exists();
    }

    private function message(
        bool $hasSubmittedApplication,
        bool $hasMeritCalculated,
        bool $hasOffer,
        bool $hasAcceptedOffer,
        bool $hasConfirmedAdmission,
        bool $hasFinalizedAdmission
    ): string {
        if ($hasFinalizedAdmission) {
            return 'Your admission has been finalized. Admission information is now locked.';
        }

        if ($hasConfirmedAdmission) {
            return 'Your admission has been confirmed. Admission information is now locked.';
        }

        if ($hasAcceptedOffer) {
            return 'You have accepted an admission offer. Merit-affecting information is now locked.';
        }

        if ($hasOffer) {
            return 'An admission offer has been generated. Merit-affecting information is now locked.';
        }

        if ($hasMeritCalculated) {
            return 'Your merit has been calculated. Merit-affecting information is now locked.';
        }

        if ($hasSubmittedApplication) {
            return 'Your application has been submitted. Required admission information is now locked.';
        }

        return '';
    }
}