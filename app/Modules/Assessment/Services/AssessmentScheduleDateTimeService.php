<?php

namespace App\Modules\Assessment\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class AssessmentScheduleDateTimeService
{
    public function normalizeTimezone(?string $timezone): string
    {
        $timezone = trim((string) $timezone);

        if ($timezone === '') {
            return config('app.timezone', 'Asia/Karachi');
        }

        /*
         | Backward compatibility with earlier bad data like:
         | Karchi, Karachi, karachi
         */
        $map = [
            'karachi' => 'Asia/Karachi',
            'karchi' => 'Asia/Karachi',
            'pakistan' => 'Asia/Karachi',
            'pkt' => 'Asia/Karachi',
        ];

        $key = strtolower($timezone);

        return $map[$key] ?? $timezone;
    }

    public function localInputToUtc(mixed $value, ?string $timezone): ?Carbon
    {
        if (!$value) {
            return null;
        }

        $timezone = $this->normalizeTimezone($timezone);

        /*
         | Frontend datetime-local usually sends:
         | 2026-06-06T13:15
         | or:
         | 2026-06-06 13:15:00
         |
         | Treat it as local schedule timezone, then convert to UTC.
         */
        return Carbon::parse($value, $timezone)->utc();
    }

    public function utcToScheduleTimezone(mixed $value, ?string $timezone): ?Carbon
    {
        if (!$value) {
            return null;
        }

        $timezone = $this->normalizeTimezone($timezone);

        if ($value instanceof CarbonInterface) {
            return $value->copy()->timezone($timezone);
        }

        return Carbon::parse($value)->timezone($timezone);
    }

    public function nowForSchedule(?string $timezone): Carbon
    {
        return now($this->normalizeTimezone($timezone));
    }

    public function isOpen(mixed $startAt, mixed $endAt, ?string $timezone): bool
    {
        $now = $this->nowForSchedule($timezone);
        $start = $this->utcToScheduleTimezone($startAt, $timezone);
        $end = $this->utcToScheduleTimezone($endAt, $timezone);

        if ($start && $now->lt($start)) {
            return false;
        }

        if ($end && $now->gt($end)) {
            return false;
        }

        return true;
    }

    public function openReason(mixed $startAt, mixed $endAt, ?string $timezone): ?string
    {
        $now = $this->nowForSchedule($timezone);
        $start = $this->utcToScheduleTimezone($startAt, $timezone);
        $end = $this->utcToScheduleTimezone($endAt, $timezone);

        if ($start && $now->lt($start)) {
            return 'Test schedule has not started yet.';
        }

        if ($end && $now->gt($end)) {
            return 'Test schedule has ended.';
        }

        return null;
    }

    public function formatForFrontend(mixed $value, ?string $timezone): ?string
    {
        $date = $this->utcToScheduleTimezone($value, $timezone);

        return $date?->format('Y-m-d\TH:i');
    }
}