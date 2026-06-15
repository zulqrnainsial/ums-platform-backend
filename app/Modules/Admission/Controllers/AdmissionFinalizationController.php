<?php

namespace App\Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\AdmissionFinalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdmissionFinalizationController extends Controller
{
    public function finalize(
        Request $request,
        int $confirmationId,
        AdmissionFinalizationService $service
    ): JsonResponse {
        $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $result = $service->finalize(
                confirmationId: $confirmationId,
                tenantId: $request->user()?->tenant_id,
                userId: $request->user()?->id,
                remarks: $request->input('remarks')
            );

            return response()->json([
                'data' => $result,
                'message' => 'Admission finalized and student enrollment created successfully.',
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}