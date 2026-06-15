<?php

namespace App\Modules\Admission\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Admission\Services\AdmissionMeritOfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionMeritOfferController extends Controller
{
    public function __construct(
        private readonly AdmissionMeritOfferService $service
    ) {
    }

    public function generateOffers(Request $request, int $meritListId): JsonResponse
    {
        $validated = $request->validate([
            'offer_expiry_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->generateOffers($meritListId, $validated),
            'Merit offers generated successfully.'
        );
    }

    public function accept(Request $request, int $meritListApplicantId): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->service->acceptOffer($meritListApplicantId, $validated),
            'Offer accepted successfully.'
        );
    }

    public function reject(Request $request, int $meritListApplicantId): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
            'promote_waiting' => ['nullable', 'boolean'],
        ]);

        return ApiResponse::success(
            $this->service->rejectOffer($meritListApplicantId, $validated),
            'Offer rejected successfully.'
        );
    }

    public function expire(Request $request, int $meritListApplicantId): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
            'promote_waiting' => ['nullable', 'boolean'],
        ]);

        return ApiResponse::success(
            $this->service->expireOffer($meritListApplicantId, $validated),
            'Offer expired successfully.'
        );
    }

    public function movements(int $meritListId): JsonResponse
    {
        return ApiResponse::success(
            $this->service->movements($meritListId),
            'Offer movements fetched successfully.'
        );
    }
}