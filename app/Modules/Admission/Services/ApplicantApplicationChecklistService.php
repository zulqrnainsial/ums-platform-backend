<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\ApplicantDocument;
use App\Modules\Admission\Models\ApplicantExperience;
use App\Modules\Admission\Models\ApplicantFeeVoucher;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use App\Modules\Admission\Models\ApplicantPublication;
use App\Modules\Admission\Models\ApplicantQualification;
use App\Modules\Admission\Models\ApplicantResearchProfile;
use App\Modules\Admission\Models\ApplicantTestResult;
use Illuminate\Support\Facades\DB;

class ApplicantApplicationChecklistService
{
    public function __construct(
        private readonly EligibilityRuleEvaluatorService $eligibilityEvaluator
    ) {
    }

    public function checklist(int $applicationId): array
    {
        $application = $this->application($applicationId);

        $items = [
            $this->profileItem($application),
            $this->qualificationItem($application),
            $this->eligibilityItem($application),
            $this->testItem($application),
            $this->experienceItem($application),
            $this->researchProfileItem($application),
            $this->publicationItem($application),
            $this->documentItem($application),
            $this->applicationFeeItem($application),
        ];

        $requiredItems = array_values(array_filter(
            $items,
            fn (array $item) => $item['required'] === true
        ));

        $blockingItems = array_values(array_filter(
            $requiredItems,
            fn (array $item) => $item['passed'] === false
        ));

        return [
            'application' => $this->applicationPayload($application),
            'can_submit' => count($blockingItems) === 0,
            'total_required' => count($requiredItems),
            'total_passed' => count($requiredItems) - count($blockingItems),
            'blocking_items' => $blockingItems,
            'items' => $items,
        ];
    }

    public function validateFinalSubmission(int $applicationId): array
    {
        return $this->checklist($applicationId);
    }

    public function finalSubmit(int $applicationId): ApplicantProgramApplication
    {
        return DB::transaction(function () use ($applicationId) {
            $application = $this->application($applicationId);

            if (!in_array($application->application_status_code, ['draft', 'submitted', 'deficient'], true)) {
                abort(422, 'Only draft, submitted, or deficient applications can be finally submitted.');
            }

            $checklist = $this->checklist($applicationId);

            if (!$checklist['can_submit']) {
                abort(422, 'Application cannot be finally submitted. Checklist is incomplete.');
            }

            $application->update([
                'application_status_code' => 'under_review',
                'eligibility_status_code' => 'eligible',
                'document_status_code' => $this->documentStatus($application),
                'fee_status_code' => $this->feeStatus($application),
                'test_status_code' => $this->testStatus($application),
                'submitted_at' => $application->submitted_at ?: now(),
                'eligibility_remarks' => null,
                'updated_by' => auth()->id(),
            ]);

            return $application->fresh([
                'applicant',
                'offeredProgram',
                'programQuotaSeat',
            ]);
        });
    }

    private function application(int $applicationId): ApplicantProgramApplication
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        return ApplicantProgramApplication::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $applicationId)
            ->with([
                'applicant',
                'offeredProgram',
                'programQuotaSeat',
            ])
            ->firstOrFail();
    }

    private function profileItem(ApplicantProgramApplication $application): array
    {
        $applicant = $application->applicant;

        $missing = [];

        if (!$applicant->first_name) {
            $missing[] = 'First name is required.';
        }

        if (!$applicant->father_name) {
            $missing[] = 'Father name is required.';
        }

        if (!$applicant->cnic_bform && !$applicant->passport_no) {
            $missing[] = 'CNIC/B-Form or passport number is required.';
        }

        if (!$applicant->date_of_birth) {
            $missing[] = 'Date of birth is required.';
        }

        if (!$applicant->gender) {
            $missing[] = 'Gender is required.';
        }

        if (!$applicant->phone) {
            $missing[] = 'Phone number is required.';
        }

        if (!$applicant->country_id) {
            $missing[] = 'Country is required.';
        }

        if (!$applicant->province_id) {
            $missing[] = 'Province is required.';
        }

        if (!$applicant->city_id) {
            $missing[] = 'City is required.';
        }

        return $this->item(
            code: 'profile',
            title: 'Personal and Demographic Profile',
            required: true,
            passed: count($missing) === 0,
            message: count($missing) === 0
                ? 'Applicant profile is complete.'
                : 'Applicant profile is incomplete.',
            details: $missing
        );
    }

    private function qualificationItem(ApplicantProgramApplication $application): array
    {
        $count = ApplicantQualification::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_id', $application->applicant_id)
            ->where('result_status_code', 'passed')
            ->count();

        return $this->item(
            code: 'qualifications',
            title: 'Previous Qualifications',
            required: true,
            passed: $count > 0,
            message: $count > 0
                ? 'Previous qualification information exists.'
                : 'At least one previous qualification is required.',
            details: [
                'qualification_count' => $count,
            ]
        );
    }

    private function eligibilityItem(ApplicantProgramApplication $application): array
    {
        $evaluation = $this->eligibilityEvaluator->evaluate(
            $application->applicant,
            $application->offeredProgram,
            $application->programQuotaSeat
        );

        return $this->item(
            code: 'eligibility',
            title: 'Eligibility Check',
            required: true,
            passed: $evaluation->eligible,
            message: $evaluation->eligible
                ? 'Applicant is eligible for selected program/quota.'
                : 'Applicant is not eligible for selected program/quota.',
            details: $evaluation->toArray()
        );
    }

    private function testItem(ApplicantProgramApplication $application): array
    {
        $required = (bool) $application->offeredProgram?->requires_test;

        if (!$required) {
            return $this->item(
                code: 'test_result',
                title: 'Admission Test / External Test',
                required: false,
                passed: true,
                message: 'Test is not required for this program.',
                details: []
            );
        }

        $exists = ApplicantTestResult::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_id', $application->applicant_id)
            ->whereIn('result_status_code', ['submitted', 'verified', 'passed'])
            ->exists();

        return $this->item(
            code: 'test_result',
            title: 'Admission Test / External Test',
            required: true,
            passed: $exists,
            message: $exists
                ? 'Test result exists.'
                : 'Test result is required.',
            details: []
        );
    }

    private function experienceItem(ApplicantProgramApplication $application): array
    {
        $required = (bool) $application->offeredProgram?->requires_experience;

        if (!$required) {
            return $this->item(
                code: 'experience',
                title: 'Experience',
                required: false,
                passed: true,
                message: 'Experience is not required for this program.',
                details: []
            );
        }

        $months = ApplicantExperience::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_id', $application->applicant_id)
            ->whereIn('status_code', ['active', 'verified'])
            ->sum('total_months');

        return $this->item(
            code: 'experience',
            title: 'Experience',
            required: true,
            passed: $months > 0,
            message: $months > 0
                ? 'Experience information exists.'
                : 'Experience information is required.',
            details: [
                'total_months' => $months,
            ]
        );
    }

    private function researchProfileItem(ApplicantProgramApplication $application): array
    {
        $required = (bool) $application->offeredProgram?->requires_research_profile;

        if (!$required) {
            return $this->item(
                code: 'research_profile',
                title: 'Research Profile',
                required: false,
                passed: true,
                message: 'Research profile is not required for this program.',
                details: []
            );
        }

        $exists = ApplicantResearchProfile::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_id', $application->applicant_id)
            ->whereIn('status_code', ['completed', 'submitted', 'verified'])
            ->exists();

        return $this->item(
            code: 'research_profile',
            title: 'Research Profile',
            required: true,
            passed: $exists,
            message: $exists
                ? 'Research profile exists.'
                : 'Research profile is required.',
            details: []
        );
    }

    private function publicationItem(ApplicantProgramApplication $application): array
    {
        /*
         | Publication is not forced directly from offered_programs yet.
         | It can still be required through eligibility rule PUBLICATION_REQUIRED.
         */
        $count = ApplicantPublication::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_id', $application->applicant_id)
            ->count();

        return $this->item(
            code: 'publications',
            title: 'Publications',
            required: false,
            passed: true,
            message: $count > 0
                ? 'Publication information exists.'
                : 'Publication information is optional.',
            details: [
                'publication_count' => $count,
            ]
        );
    }

    private function documentItem(ApplicantProgramApplication $application): array
    {
        /*
         | For now, at least one uploaded document is required.
         | In next document-checklist step, this will become document-type based.
         */
        $uploadedCount = ApplicantDocument::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_id', $application->applicant_id)
            ->whereNotNull('file_path')
            ->whereIn('verification_status_code', ['pending', 'submitted', 'verified'])
            ->count();

        return $this->item(
            code: 'documents',
            title: 'Documents',
            required: true,
            passed: $uploadedCount > 0,
            message: $uploadedCount > 0
                ? 'Applicant has uploaded document(s).'
                : 'At least one applicant document must be uploaded.',
            details: [
                'uploaded_document_count' => $uploadedCount,
            ]
        );
    }

    private function applicationFeeItem(ApplicantProgramApplication $application): array
    {
        $feeRequired = $application->fee_status_code !== 'not_required';

        if (!$feeRequired) {
            return $this->item(
                code: 'application_fee',
                title: 'Application Fee',
                required: false,
                passed: true,
                message: 'Application fee is not required.',
                details: []
            );
        }

        $voucher = ApplicantFeeVoucher::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_program_application_id', $application->id)
            ->where('voucher_type_code', 'application_fee')
            ->orderByDesc('id')
            ->first();

        if (!$voucher) {
            return $this->item(
                code: 'application_fee',
                title: 'Application Fee',
                required: true,
                passed: false,
                message: 'Application fee voucher has not been generated.',
                details: []
            );
        }

        $passed = in_array($voucher->status_code, ['paid', 'verified'], true);

        return $this->item(
            code: 'application_fee',
            title: 'Application Fee',
            required: true,
            passed: $passed,
            message: $passed
                ? 'Application fee is paid/verified.'
                : 'Application fee is not verified yet.',
            details: [
                'voucher_id' => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
                'status_code' => $voucher->status_code,
                'payable_amount' => $voucher->payable_amount,
                'paid_amount' => $voucher->paid_amount,
                'balance_amount' => $voucher->balance_amount,
            ]
        );
    }

    private function documentStatus(ApplicantProgramApplication $application): string
    {
        $hasRejected = ApplicantDocument::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_id', $application->applicant_id)
            ->where('verification_status_code', 'rejected')
            ->exists();

        if ($hasRejected) {
            return 'rejected';
        }

        $hasUploaded = ApplicantDocument::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_id', $application->applicant_id)
            ->whereNotNull('file_path')
            ->exists();

        return $hasUploaded ? 'submitted' : 'pending';
    }

    private function feeStatus(ApplicantProgramApplication $application): string
    {
        $voucher = ApplicantFeeVoucher::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_program_application_id', $application->id)
            ->where('voucher_type_code', 'application_fee')
            ->orderByDesc('id')
            ->first();

        return $voucher?->status_code ?? $application->fee_status_code;
    }

    private function testStatus(ApplicantProgramApplication $application): string
    {
        if (!$application->offeredProgram?->requires_test) {
            return 'not_required';
        }

        $exists = ApplicantTestResult::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('applicant_id', $application->applicant_id)
            ->whereIn('result_status_code', ['submitted', 'verified', 'passed'])
            ->exists();

        return $exists ? 'submitted' : 'required';
    }

    private function applicationPayload(ApplicantProgramApplication $application): array
    {
        return [
            'id' => $application->id,
            'application_no' => $application->application_no,
            'applicant_id' => $application->applicant_id,
            'applicant_no' => $application->applicant?->applicant_no,
            'applicant_name' => $application->applicant?->full_name,
            'offered_program_id' => $application->offered_program_id,
            'offered_program_title' => $application->offeredProgram?->title,
            'program_quota_seat_id' => $application->program_quota_seat_id,
            'quota_name' => $application->programQuotaSeat?->quota_name,
            'application_status_code' => $application->application_status_code,
            'eligibility_status_code' => $application->eligibility_status_code,
            'document_status_code' => $application->document_status_code,
            'fee_status_code' => $application->fee_status_code,
            'test_status_code' => $application->test_status_code,
        ];
    }

    private function item(
        string $code,
        string $title,
        bool $required,
        bool $passed,
        string $message,
        array $details
    ): array {
        return [
            'code' => $code,
            'title' => $title,
            'required' => $required,
            'passed' => $passed,
            'message' => $message,
            'details' => $details,
        ];
    }
}