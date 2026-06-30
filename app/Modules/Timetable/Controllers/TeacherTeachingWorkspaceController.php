<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Timetable\Services\TeacherTeachingWorkspaceService;
use Illuminate\Http\JsonResponse;

class TeacherTeachingWorkspaceController extends Controller
{
    public function dashboard(
        TeacherTeachingWorkspaceService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->dashboard(),
            'message' => 'Teacher workspace fetched successfully.',
        ]);
    }
}