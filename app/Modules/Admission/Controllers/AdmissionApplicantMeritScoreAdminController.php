<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\AdmissionApplicantMeritScoreAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionApplicantMeritScoreAdminController extends Controller
{
    public function __construct(
        private readonly AdmissionApplicantMeritScoreAdminService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->list($request->all()),
            'Applicant merit scores fetched successfully.'
        );
    }

    public function detail(int $scoreId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->detail($scoreId),
            'Applicant merit score detail fetched successfully.'
        );
    }
}