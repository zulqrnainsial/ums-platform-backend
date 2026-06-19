<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\AttendanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function summary(Request $request, AttendanceReportService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->summary($request->all()),
            'message' => 'Attendance summary report fetched successfully.',
        ]);
    }

    public function studentCoursePercentages(Request $request, AttendanceReportService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->studentCoursePercentages($request->all()),
            'message' => 'Student attendance percentages fetched successfully.',
        ]);
    }

    public function defaulters(Request $request, AttendanceReportService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->defaulters($request->all()),
            'message' => 'Attendance defaulters fetched successfully.',
        ]);
    }
}