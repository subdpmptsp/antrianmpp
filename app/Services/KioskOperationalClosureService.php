<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\QueueOperatingSetting;
use Carbon\Carbon;

class KioskOperationalClosureService
{
    /**
     * Returns the next global opening only after daily operation has ended,
     * on a closed day, or on a global holiday. Individual service closures
     * deliberately do not trigger this kiosk-wide modal.
     *
     * @return array{opens_at: Carbon}|null
     */
    public function active(?Carbon $at = null): ?array
    {
        $now = ($at ?: now())->copy()->setTimezone('Asia/Jakarta');
        $settings = QueueOperatingSetting::query()->first();
        $schedule = (array) ($settings?->weekly_schedule ?? []);
        $today = $this->day($schedule, $now->isoWeekday());

        $isHoliday = Holiday::query()->whereDate('date', $now->toDateString())->exists();
        $isClosedDay = ! $today || empty($today['is_open']);
        $cutoff = max(0, (int) ($settings?->cutoff_minutes ?? 30));
        $lastTicketAt = $today && ! empty($today['closes_at'])
            ? $this->atTime($now, $today['closes_at'])?->subMinutes($cutoff)
            : null;

        if (! $isHoliday && ! $isClosedDay && (! $lastTicketAt || $now->lessThan($lastTicketAt))) {
            return null;
        }

        return ['opens_at' => $this->nextOpening($now, $schedule)];
    }

    /** @param array<int, mixed> $schedule */
    private function nextOpening(Carbon $now, array $schedule): Carbon
    {
        for ($days = 1; $days <= 14; $days++) {
            $date = $now->copy()->startOfDay()->addDays($days);
            $day = $this->day($schedule, $date->isoWeekday());

            if (! $day || empty($day['is_open']) || Holiday::query()->whereDate('date', $date->toDateString())->exists()) {
                continue;
            }

            $opensAt = $this->atTime($date, $day['opens_at'] ?? null);
            if ($opensAt) {
                return $opensAt;
            }
        }

        // Fallback should only be reached for an invalid weekly schedule.
        return $now->copy()->startOfDay()->addDay()->setTime(7, 30);
    }

    /** @param array<int, mixed> $schedule @return array<string, mixed>|null */
    private function day(array $schedule, int $weekday): ?array
    {
        foreach ($schedule as $entry) {
            $entry = (array) $entry;
            if ((int) ($entry['day'] ?? 0) === $weekday) {
                return $entry;
            }
        }

        return null;
    }

    private function atTime(Carbon $date, mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{2}:\d{2}/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.substr($value, 0, 5), 'Asia/Jakarta');
    }
}
