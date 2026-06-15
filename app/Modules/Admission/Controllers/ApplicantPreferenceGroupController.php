<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Services\ApplicantPreferenceGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Admission\Services\ApplicantEditLockService;
class ApplicantPreferenceGroupController extends Controller
{
    public function __construct(
        private readonly ApplicantPreferenceGroupService $service,
        private readonly ApplicantEditLockService $editLockService
    ) {
    }

    public function adminShow(Request $request, int $applicantId): JsonResponse
    {
        $admissionSessionId = $request->query('admission_session_id');

        return ApiResponse::success(
            $this->service->getGroupForApplicant(
                applicantId: $applicantId,
                admissionSessionId: $admissionSessionId ? (int) $admissionSessionId : null
            ),
            'Applicant preference group fetched successfully.'
        );
    }

    public function adminAddPreference(Request $request, int $applicantId): JsonResponse
    {
        $validated = $request->validate([
            'offered_program_id' => ['required', 'integer', 'exists:offered_programs,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->addPreference(
                applicantId: $applicantId,
                offeredProgramId: (int) $validated['offered_program_id'],
                remarks: $validated['remarks'] ?? null
            ),
            'Program preference added successfully.'
        );
    }

    public function adminReorder(Request $request, int $applicantId): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:applicant_program_applications,id'],
            'items.*.preference_order' => ['required', 'integer', 'min:1'],
        ]);

        return ApiResponse::success(
            $this->service->reorderPreferences($applicantId, $validated['items']),
            'Program preferences reordered successfully.'
        );
    }

    public function adminRemove(Request $request, int $applicantId, int $applicationId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->removePreference($applicantId, $applicationId),
            'Program preference removed successfully.'
        );
    }

    public function adminSubmit(Request $request, int $applicantId, int $groupId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->submitGroup($applicantId, $groupId),
            'Application preference group submitted successfully.'
        );
    }

    public function myShow(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
        
        $admissionSessionId = $request->query('admission_session_id');

        return ApiResponse::success(
            $this->service->getGroupForApplicant(
                applicantId: $applicant->id,
                admissionSessionId: $admissionSessionId ? (int) $admissionSessionId : null
            ),
            'Applicant preference group fetched successfully.'
        );
    }

    public function myAddPreference(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
$this->editLockService->assertCanEdit(
    applicantId: $applicant->id,
    tenantId: $applicant->tenant_id,
    area: 'preferences'
);
        $validated = $request->validate([
            'offered_program_id' => ['required', 'integer', 'exists:offered_programs,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->addPreference(
                applicantId: $applicant->id,
                offeredProgramId: (int) $validated['offered_program_id'],
                remarks: $validated['remarks'] ?? null
            ),
            'Program preference added successfully.'
        );
    }

    public function myReorder(Request $request): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
$this->editLockService->assertCanEdit(
    applicantId: $applicant->id,
    tenantId: $applicant->tenant_id,
    area: 'preferences'
);
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:applicant_program_applications,id'],
            'items.*.preference_order' => ['required', 'integer', 'min:1'],
        ]);

        return ApiResponse::success(
            $this->service->reorderPreferences($applicant->id, $validated['items']),
            'Program preferences reordered successfully.'
        );
    }

    public function myRemove(Request $request, int $applicationId): JsonResponse
    {
        $applicant = $this->currentApplicant($request);
$this->editLockService->assertCanEdit(
    applicantId: $applicant->id,
    tenantId: $applicant->tenant_id,
    area: 'preferences'
);
        return ApiResponse::success(
            $this->service->removePreference($applicant->id, $applicationId),
            'Program preference removed successfully.'
        );
    }

    public function mySubmit(Request $request, int $groupId): JsonResponse
    {
        $applicant = $this->currentApplicant($request);

        return ApiResponse::success(
            $this->service->submitGroup($applicant->id, $groupId),
            'Application preference group submitted successfully.'
        );
    }

    private function currentApplicant(Request $request): Applicant
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('Applicant')) {
            abort(403, 'Applicant account is required.');
        }

        if (!$user->tenant_id) {
            abort(403, 'Applicant tenant context is missing.');
        }

        return Applicant::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }
}
