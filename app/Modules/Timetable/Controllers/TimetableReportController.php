<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Timetable\Services\TimetableReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimetableReportController extends Controller
{
    public function context(
        TimetableReportService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->context(),
            'message' => 'Timetable report context fetched successfully.',
        ]);
    }

    public function master(
        Request $request,
        TimetableReportService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->master($request->all()),
            'message' => 'Published timetable report fetched successfully.',
        ]);
    }

    public function roomUtilization(
        Request $request,
        TimetableReportService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->roomUtilization($request->all()),
            'message' => 'Room utilization report fetched successfully.',
        ]);
    }
}