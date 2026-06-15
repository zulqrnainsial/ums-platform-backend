<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\ApplicantDocument;
use App\Modules\Admission\Models\ApplicantExperience;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use App\Modules\Admission\Models\ApplicantPublication;
use App\Modules\Admission\Models\ApplicantQualification;
use App\Modules\Admission\Models\ApplicantResearchProfile;
use App\Modules\Admission\Models\ApplicantTestResult;
use App\Modules\Admission\Services\ApplicantApplicationChecklistService;
use App\Modules\Admission\Services\ApplicantApplicationService;
use App\Modules\Admission\Services\ApplicantFeeVoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Modules\Admission\Services\ApplicantEditLockService;
//use Illuminate\Validation\ValidationException;

class ApplicantSelfServiceController extends Controller
{
    public function __construct(
        private readonly ApplicantApplicationService $applicationService,
        private readonly ApplicantApplicationChecklistService $checklistService,
        private readonly ApplicantFeeVoucherService $feeVoucherService,
        private readonly ApplicantEditLockService $editLockService
    ) {
    }

    public function profile(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        return ApiResponse::success(
            $this->formatApplicant($applicant),
            'Applicant profile fetched successfully.'
        );
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
        $this->editLockService->assertCanEdit(
            applicantId: $applicant->id,
            tenantId: $applicant->tenant_id,
            area: 'personal_info'
        );
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'father_name' => ['required', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'cnic_bform' => ['nullable', 'string', 'max:50'],
            'passport_no' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],

            'nationality_id' => ['nullable', 'integer'],
            'religion_id' => ['nullable', 'integer'],
            'country_id' => ['nullable', 'integer'],
            'province_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],

            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
        ]);

        $validated['full_name'] = trim(
            ($validated['first_name'] ?? $applicant->first_name) . ' ' . ($validated['last_name'] ?? '')
        );

        $validated['profile_status_code'] = 'completed';
        $validated['updated_by'] = $request->user()->id;

        $safePayload = $this->filterColumns('applicants', $validated);

        $applicant->update($safePayload);

        return ApiResponse::success(
            $this->formatApplicant($applicant->fresh()),
            'Applicant profile updated successfully.'
        );
    }

    public function qualifications(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $rows = ApplicantQualification::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success($rows, 'Qualifications fetched successfully.');
    }

    public function saveQualification(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
        $this->editLockService->assertCanEdit(
            applicantId: $applicant->id,
            tenantId: $applicant->tenant_id,
            area: 'qualifications'
        );
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'qualification_level_id' => ['required', 'integer'],
            'education_board_id' => ['nullable', 'integer'],
            'external_institution_id' => ['nullable', 'integer'],
            'subject_group_id' => ['nullable', 'integer'],
            'degree_class_name' => ['nullable', 'string', 'max:150'],
            'roll_no' => ['nullable', 'string', 'max:100'],
            'registration_no' => ['nullable', 'string', 'max:100'],
            'passing_year' => ['nullable', 'string', 'max:20'],
            'result_status_code' => ['nullable', 'string', 'max:50'],
            'total_marks' => ['nullable', 'numeric'],
            'obtained_marks' => ['nullable', 'numeric'],
            'percentage' => ['nullable', 'numeric'],
            'cgpa' => ['nullable', 'numeric'],
            'cgpa_scale' => ['nullable', 'numeric'],
            'grade' => ['nullable', 'string', 'max:20'],
            'equivalence_required' => ['nullable', 'boolean'],
            'is_final_result' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $applicant->tenant_id;
        $validated['applicant_id'] = $applicant->id;
        $validated['result_status_code'] = $validated['result_status_code'] ?? 'passed';
        $validated['is_verified'] = false;
        $validated['updated_by'] = $request->user()->id;

        if (!empty($validated['id'])) {
            $row = ApplicantQualification::query()
                ->where('tenant_id', $applicant->tenant_id)
                ->where('applicant_id', $applicant->id)
                ->where('id', $validated['id'])
                ->firstOrFail();

            unset($validated['id']);
            $row->update($this->filterColumns('applicant_qualifications', $validated));

            return ApiResponse::success($row->fresh(), 'Qualification updated successfully.');
        }

        unset($validated['id']);
        $validated['created_by'] = $request->user()->id;

        $row = ApplicantQualification::create(
            $this->filterColumns('applicant_qualifications', $validated)
        );

        return ApiResponse::success($row, 'Qualification saved successfully.');
    }

    public function deleteQualification(Request $request, int $id): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
$this->editLockService->assertCanEdit(
    applicantId: $applicant->id,
    tenantId: $applicant->tenant_id,
    area: 'qualifications'
);
        $row = ApplicantQualification::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->where('id', $id)
            ->firstOrFail();

        $row->delete();

        return ApiResponse::success(null, 'Qualification deleted successfully.');
    }

    public function experiences(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $rows = ApplicantExperience::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success($rows, 'Experiences fetched successfully.');
    }

    public function saveExperience(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
$this->editLockService->assertCanEdit(
    applicantId: $applicant->id,
    tenantId: $applicant->tenant_id,
    area: 'test_results'
);
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'organization_name' => ['required', 'string', 'max:200'],
            'designation' => ['nullable', 'string', 'max:150'],
            'employment_type_id' => ['nullable', 'integer'],
            'experience_area_id' => ['nullable', 'integer'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'currently_working' => ['nullable', 'boolean'],
            'total_months' => ['nullable', 'integer'],
            'status_code' => ['nullable', 'string', 'max:50'],
            'job_description' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $applicant->tenant_id;
        $validated['applicant_id'] = $applicant->id;
        $validated['status_code'] = $validated['status_code'] ?? 'active';
        $validated['updated_by'] = $request->user()->id;

        if (!empty($validated['id'])) {
            $row = ApplicantExperience::query()
                ->where('tenant_id', $applicant->tenant_id)
                ->where('applicant_id', $applicant->id)
                ->where('id', $validated['id'])
                ->firstOrFail();

            unset($validated['id']);
            $row->update($this->filterColumns('applicant_experiences', $validated));

            return ApiResponse::success($row->fresh(), 'Experience updated successfully.');
        }

        unset($validated['id']);
        $validated['created_by'] = $request->user()->id;

        $row = ApplicantExperience::create(
            $this->filterColumns('applicant_experiences', $validated)
        );

        return ApiResponse::success($row, 'Experience saved successfully.');
    }

    public function deleteExperience(Request $request, int $id): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
$this->editLockService->assertCanEdit(
    applicantId: $applicant->id,
    tenantId: $applicant->tenant_id,
    area: 'test_results'
);
        $row = ApplicantExperience::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->where('id', $id)
            ->firstOrFail();

        $row->delete();

        return ApiResponse::success(null, 'Experience deleted successfully.');
    }

    public function researchProfile(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $row = ApplicantResearchProfile::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->first();

        return ApiResponse::success($row, 'Research profile fetched successfully.');
    }

    public function saveResearchProfile(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $validated = $request->validate([
            'research_area_id' => ['nullable', 'integer'],
            'proposed_research_title' => ['nullable', 'string', 'max:255'],
            'statement_of_purpose' => ['nullable', 'string'],
            'research_interests' => ['nullable', 'string'],
            'preferred_supervisor_name' => ['nullable', 'string', 'max:150'],
            'preferred_supervisor_email' => ['nullable', 'email', 'max:150'],
            'status_code' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        $payload = $validated;
        $payload['tenant_id'] = $applicant->tenant_id;
        $payload['applicant_id'] = $applicant->id;
        $payload['status_code'] = $payload['status_code'] ?? 'completed';
        $payload['updated_by'] = $request->user()->id;

        $row = ApplicantResearchProfile::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->first();

        if ($row) {
            $row->update($this->filterColumns('applicant_research_profiles', $payload));
        } else {
            $payload['created_by'] = $request->user()->id;
            $row = ApplicantResearchProfile::create(
                $this->filterColumns('applicant_research_profiles', $payload)
            );
        }

        return ApiResponse::success($row->fresh(), 'Research profile saved successfully.');
    }

    public function publications(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $rows = ApplicantPublication::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success($rows, 'Publications fetched successfully.');
    }

    public function savePublication(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'publication_type_id' => ['nullable', 'integer'],
            'indexing_type_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'journal_conference_name' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'string', 'max:20'],
            'doi' => ['nullable', 'string', 'max:150'],
            'url' => ['nullable', 'string', 'max:255'],
            'status_code' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $applicant->tenant_id;
        $validated['applicant_id'] = $applicant->id;
        $validated['status_code'] = $validated['status_code'] ?? 'claimed';
        $validated['is_verified'] = false;
        $validated['updated_by'] = $request->user()->id;

        if (!empty($validated['id'])) {
            $row = ApplicantPublication::query()
                ->where('tenant_id', $applicant->tenant_id)
                ->where('applicant_id', $applicant->id)
                ->where('id', $validated['id'])
                ->firstOrFail();

            unset($validated['id']);
            $row->update($this->filterColumns('applicant_publications', $validated));

            return ApiResponse::success($row->fresh(), 'Publication updated successfully.');
        }

        unset($validated['id']);
        $validated['created_by'] = $request->user()->id;

        $row = ApplicantPublication::create(
            $this->filterColumns('applicant_publications', $validated)
        );

        return ApiResponse::success($row, 'Publication saved successfully.');
    }

    public function deletePublication(Request $request, int $id): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $row = ApplicantPublication::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->where('id', $id)
            ->firstOrFail();

        $row->delete();

        return ApiResponse::success(null, 'Publication deleted successfully.');
    }

    public function testResults(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $rows = ApplicantTestResult::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success($rows, 'Test results fetched successfully.');
    }

    public function saveTestResult(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
$this->editLockService->assertCanEdit(
    applicantId: $applicant->id,
    tenantId: $applicant->tenant_id,
    area: 'test_results'
);
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'test_type_id' => ['nullable', 'integer'],
            'test_source_code' => ['nullable', 'string', 'max:50'],
            'test_code' => ['required', 'string', 'max:100'],
            'test_name' => ['nullable', 'string', 'max:150'],
            'roll_no' => ['nullable', 'string', 'max:100'],
            'test_date' => ['nullable', 'date'],
            'total_marks' => ['nullable', 'numeric'],
            'obtained_marks' => ['nullable', 'numeric'],
            'percentage' => ['nullable', 'numeric'],
            'percentile' => ['nullable', 'numeric'],
            'result_status_code' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $applicant->tenant_id;
        $validated['applicant_id'] = $applicant->id;
        $validated['test_source_code'] = $validated['test_source_code'] ?? 'external';
        $validated['result_status_code'] = $validated['result_status_code'] ?? 'submitted';
        $validated['is_verified'] = false;
        $validated['updated_by'] = $request->user()->id;

        if (!empty($validated['id'])) {
            $row = ApplicantTestResult::query()
                ->where('tenant_id', $applicant->tenant_id)
                ->where('applicant_id', $applicant->id)
                ->where('id', $validated['id'])
                ->firstOrFail();

            unset($validated['id']);
            $row->update($this->filterColumns('applicant_test_results', $validated));

            return ApiResponse::success($row->fresh(), 'Test result updated successfully.');
        }

        unset($validated['id']);
        $validated['created_by'] = $request->user()->id;

        $row = ApplicantTestResult::create(
            $this->filterColumns('applicant_test_results', $validated)
        );

        return ApiResponse::success($row, 'Test result saved successfully.');
    }

    public function deleteTestResult(Request $request, int $id): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
$this->editLockService->assertCanEdit(
    applicantId: $applicant->id,
    tenantId: $applicant->tenant_id,
    area: 'test_results'
);
        $row = ApplicantTestResult::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->where('id', $id)
            ->firstOrFail();

        $row->delete();

        return ApiResponse::success(null, 'Test result deleted successfully.');
    }

    public function documents(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $rows = ApplicantDocument::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success($rows, 'Documents fetched successfully.');
    }

    public function eligiblePrograms(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $preferenceGroupId = $request->query('preference_group_id');

        return ApiResponse::success(
            $this->applicationService->eligiblePrograms(
                $applicant->id,
                $preferenceGroupId ? (int) $preferenceGroupId : null
            ),
            'Eligible programs fetched successfully.'
        );
    }

    public function applications(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        return ApiResponse::success(
            $this->applicationService->applicationsForApplicant($applicant->id),
            'Applicant applications fetched successfully.'
        );
    }

    public function apply(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
$this->editLockService->assertCanEdit(
    applicantId: $applicant->id,
    tenantId: $applicant->tenant_id,
    area: 'preferences'
);
        $validated = $request->validate([
            'offered_program_id' => ['required', 'integer', 'exists:offered_programs,id'],
            'program_quota_seat_id' => ['nullable', 'integer', 'exists:program_quota_seats,id'],
            'preference_order' => ['nullable', 'integer', 'min:1'],
            'submit_now' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['applicant_id'] = $applicant->id;

        $application = $this->applicationService->apply($validated);

        return ApiResponse::success(
            $this->applicationService->formatApplication($application->fresh(['offeredProgram', 'programQuotaSeat'])),
            'Application created successfully.'
        );
    }

    public function checklist(Request $request, int $applicationId): JsonResponse
    {
        $this->assertApplicationBelongsToApplicant($request, $applicationId);

        return ApiResponse::success(
            $this->checklistService->checklist($applicationId),
            'Application checklist fetched successfully.'
        );
    }

    public function finalSubmit(Request $request, int $applicationId): JsonResponse
    {
        $this->assertApplicationBelongsToApplicant($request, $applicationId);

        $application = $this->checklistService->finalSubmit($applicationId);

        return ApiResponse::success([
            'id' => $application->id,
            'application_no' => $application->application_no,
            'application_status_code' => $application->application_status_code,
            'eligibility_status_code' => $application->eligibility_status_code,
            'document_status_code' => $application->document_status_code,
            'fee_status_code' => $application->fee_status_code,
            'test_status_code' => $application->test_status_code,
            'submitted_at' => $application->submitted_at,
        ], 'Application finally submitted successfully.');
    }

    public function generateVoucher(Request $request, int $applicationId): JsonResponse
    {
        $this->assertApplicationBelongsToApplicant($request, $applicationId);

        $voucher = $this->feeVoucherService->generateForApplication($applicationId, 'application_fee');

        return ApiResponse::success(
            $this->feeVoucherService->formatVoucher($voucher->fresh(['payments'])),
            'Applicant fee voucher generated successfully.'
        );
    }

    public function vouchers(Request $request, int $applicationId): JsonResponse
    {
        $this->assertApplicationBelongsToApplicant($request, $applicationId);

        return ApiResponse::success(
            $this->feeVoucherService->vouchersForApplication($applicationId),
            'Application fee vouchers fetched successfully.'
        );
    }

    public function submitPayment(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        $validated = $request->validate([
            'applicant_fee_voucher_id' => ['required', 'integer', 'exists:applicant_fee_vouchers,id'],
            'payment_method_code' => ['required', 'string', 'max:50'],
            'payment_reference_no' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_proof_document_id' => ['nullable', 'integer', 'exists:applicant_documents,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        $voucher = DB::table('applicant_fee_vouchers')
            ->where('id', $validated['applicant_fee_voucher_id'])
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->first();

        if (!$voucher) {
            abort(403, 'This voucher does not belong to the logged-in applicant.');
        }

        if (!empty($validated['payment_proof_document_id'])) {
            $documentBelongs = ApplicantDocument::query()
                ->where('tenant_id', $applicant->tenant_id)
                ->where('applicant_id', $applicant->id)
                ->where('id', $validated['payment_proof_document_id'])
                ->exists();

            if (!$documentBelongs) {
                abort(403, 'This payment proof document does not belong to the logged-in applicant.');
            }
        }

        $payment = $this->feeVoucherService->submitPayment($validated);

        return ApiResponse::success([
            'id' => $payment->id,
            'status_code' => $payment->status_code,
            'amount' => $payment->amount,
        ], 'Payment submitted successfully.');
    }

    private function currentApplicant(Request $request): Applicant
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('Applicant')) {
            abort(403, 'Applicant account is required.');
        }

        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            abort(403, 'Applicant tenant context is missing.');
        }

        return Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    private function assertApplicationBelongsToApplicant(Request $request, int $applicationId): ApplicantProgramApplication
    {
        $applicant = $this->currentApplicant($request);

        return ApplicantProgramApplication::query()
            ->where('tenant_id', $applicant->tenant_id)
            ->where('applicant_id', $applicant->id)
            ->where('id', $applicationId)
            ->firstOrFail();
    }

    private function formatApplicant(Applicant $applicant): array
    {
        return [
            'id' => $applicant->id,
            'tenant_id' => $applicant->tenant_id,
            'user_id' => $applicant->user_id,
            'applicant_no' => $applicant->applicant_no,
            'first_name' => $applicant->first_name,
            'last_name' => $applicant->last_name,
            'full_name' => $applicant->full_name,
            'father_name' => $applicant->father_name,
            'mother_name' => $applicant->mother_name,
            'cnic_bform' => $applicant->cnic_bform,
            'passport_no' => $applicant->passport_no,
            'date_of_birth' => $applicant->date_of_birth,
            'gender' => $applicant->gender,
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'nationality_id' => $applicant->nationality_id,
            'religion_id' => $applicant->religion_id,
            'country_id' => $applicant->country_id,
            'province_id' => $applicant->province_id,
            'city_id' => $applicant->city_id,
            'current_address' => $applicant->current_address,
            'permanent_address' => $applicant->permanent_address,
            'profile_status_code' => $applicant->profile_status_code,
            'applicant_status_code' => $applicant->applicant_status_code,
        ];
    }

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->toArray();
    }
}