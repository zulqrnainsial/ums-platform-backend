<?php

namespace App\Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Services\ApplicantEditLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicantEditLockController extends Controller
{
    public function me(Request $request, ApplicantEditLockService $service): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            abort(403, 'Applicant tenant context is missing.');
        }

        $applicant = Applicant::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'data' => $service->getLockStatus(
                applicantId: (int) $applicant->id,
                tenantId: (int) $tenantId
            ),
        ]);
    }

    public function show(Request $request, int $applicantId, ApplicantEditLockService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->getLockStatus(
                applicantId: $applicantId,
                tenantId: $request->user()?->tenant_id
            ),
        ]);
    }
}