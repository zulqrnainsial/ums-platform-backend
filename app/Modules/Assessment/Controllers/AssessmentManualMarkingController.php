<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\AssessmentManualMarkingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentManualMarkingController extends Controller
{
    public function __construct(
        private readonly AssessmentManualMarkingService $service
    ) {
    }

    public function pending(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->pending($request->all()),
            'Manual marking answers fetched successfully.'
        );
    }

    public function mark(Request $request, int $answerId): JsonResponse
    {
        $validated = $request->validate([
            'is_correct' => ['nullable', 'boolean'],
            'marks_awarded' => ['required', 'numeric', 'min:0'],
            'negative_marks_applied' => ['nullable', 'numeric', 'min:0'],
            'marking_remarks' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->mark($answerId, $validated),
            'Answer marked successfully.'
        );
    }
}