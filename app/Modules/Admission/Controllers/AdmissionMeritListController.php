<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\AdmissionMeritListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionMeritListController extends Controller
{
    public function __construct(
        private readonly AdmissionMeritListService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->list($request->all()),
            'Merit lists fetched successfully.'
        );
    }

    public function show(int $meritListId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->detail($meritListId),
            'Merit list detail fetched successfully.'
        );
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'list_no' => ['nullable', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:200'],
            'list_type_code' => ['nullable', 'string', 'max:80'],

            'admission_merit_formula_id' => ['nullable', 'integer'],
            'admission_session_id' => ['nullable', 'integer'],
            'admission_preference_group_id' => ['nullable', 'integer'],
            'offered_program_id' => ['nullable', 'integer'],
            'program_quota_seat_id' => ['nullable', 'integer'],

            'available_seats' => ['nullable', 'integer', 'min:0'],
        ]);

        return ApiResponse::success(
            $this->service->generate($validated),
            'Merit list generated successfully.'
        );
    }

    public function publish(int $meritListId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->publish($meritListId),
            'Merit list published successfully.'
        );
    }

    public function cancel(int $meritListId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->cancel($meritListId),
            'Merit list cancelled successfully.'
        );
    }
}