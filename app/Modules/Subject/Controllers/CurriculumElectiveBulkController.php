<?php

namespace App\Modules\Subject\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Subject\Requests\BulkAssignCurriculumElectivesRequest;
use App\Modules\Subject\Services\CurriculumElectiveBulkService;
use Illuminate\Http\JsonResponse;

class CurriculumElectiveBulkController extends Controller
{
    public function __construct(
        private readonly CurriculumElectiveBulkService $bulkService
    ) {
    }

    public function assign(BulkAssignCurriculumElectivesRequest $request): JsonResponse
    {
        $result = $this->bulkService->bulkAssign($request->validated());

        return ApiResponse::success(
            [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'total_selected' => $result['total_selected'],
            ],
            'Curriculum elective subjects assigned successfully.'
        );
    }
}