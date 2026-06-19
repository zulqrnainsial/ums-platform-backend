<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\AttendanceSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceSessionController extends Controller
{
    public function index(Request $request, AttendanceSessionService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->index($request->all()),
            'message' => 'Attendance sessions fetched successfully.',
        ]);
    }

    public function show(int $session, AttendanceSessionService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->show($session),
            'message' => 'Attendance session fetched successfully.',
        ]);
    }

    public function destroy(int $session, AttendanceSessionService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->destroy($session),
            'message' => 'Attendance session deleted successfully.',
        ]);
    }
}