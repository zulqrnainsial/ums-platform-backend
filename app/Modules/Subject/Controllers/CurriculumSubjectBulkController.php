<?php

namespace App\Modules\Subject\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Subject\Requests\BulkAssignCurriculumSubjectsRequest;
use App\Modules\Subject\Services\CurriculumSubjectBulkService;
use Illuminate\Http\JsonResponse;

class CurriculumSubjectBulkController extends Controller
{
    public function __construct(
        private readonly CurriculumSubjectBulkService $bulkService
    ) {
    }

    public function assign(BulkAssignCurriculumSubjectsRequest $request): JsonResponse
    {
        $result = $this->bulkService->bulkAssign($request->validated());

        return ApiResponse::success(
            [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'total_selected' => $result['total_selected'],
            ],
            'Curriculum subjects assigned successfully.'
        );
    }
}