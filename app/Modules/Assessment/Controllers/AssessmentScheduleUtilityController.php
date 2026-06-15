<?php

namespace App\Modules\Assessment\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assessment\Services\AssessmentScheduleDateTimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentScheduleUtilityController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'timezone' => ['nullable', 'string'],
            'start_at' => ['nullable', 'string'],
            'end_at' => ['nullable', 'string'],
            'reporting_time' => ['nullable', 'string'],
        ]);

        $service = app(AssessmentScheduleDateTimeService::class);
        $timezone = $service->normalizeTimezone($validated['timezone'] ?? 'Asia/Karachi');

        $startUtc = $service->localInputToUtc($validated['start_at'] ?? null, $timezone);
        $endUtc = $service->localInputToUtc($validated['end_at'] ?? null, $timezone);
        $reportingUtc = $service->localInputToUtc($validated['reporting_time'] ?? null, $timezone);

        $now = $service->nowForSchedule($timezone);

        $isOpen = $service->isOpen($startUtc, $endUtc, $timezone);
        $reason = $service->openReason($startUtc, $endUtc, $timezone);

        return ApiResponse::success([
            'timezone' => $timezone,
            'now_local' => $now->format('Y-m-d H:i:s'),
            'start_local' => $startUtc ? $service->utcToScheduleTimezone($startUtc, $timezone)?->format('Y-m-d H:i:s') : null,
            'end_local' => $endUtc ? $service->utcToScheduleTimezone($endUtc, $timezone)?->format('Y-m-d H:i:s') : null,
            'reporting_local' => $reportingUtc ? $service->utcToScheduleTimezone($reportingUtc, $timezone)?->format('Y-m-d H:i:s') : null,
            'start_utc' => $startUtc?->format('Y-m-d H:i:s'),
            'end_utc' => $endUtc?->format('Y-m-d H:i:s'),
            'reporting_utc' => $reportingUtc?->format('Y-m-d H:i:s'),
            'is_open_now' => $isOpen,
            'reason' => $reason,
        ], 'Schedule preview generated successfully.');
    }
}