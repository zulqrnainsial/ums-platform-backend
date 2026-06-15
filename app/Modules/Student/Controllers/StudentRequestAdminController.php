<?php

namespace App\Modules\Student\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Student\Services\StudentRequestAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentRequestAdminController extends Controller
{
    public function index(Request $request, StudentRequestAdminService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->index($request->all()),
            'Student requests fetched successfully.'
        );
    }

    public function show(int $requestId, StudentRequestAdminService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->show($requestId),
            'Student request detail fetched successfully.'
        );
    }

    public function decide(
        Request $request,
        int $requestId,
        StudentRequestAdminService $service
    ): JsonResponse {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'max:50'],
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
            'apply_changes' => ['nullable', 'boolean'],
        ]);

        return ApiResponse::success(
            $service->decide($requestId, $validated),
            'Student request decision saved successfully.'
        );
    }
}