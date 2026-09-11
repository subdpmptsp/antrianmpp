<?php

namespace App\Services;

use App\Models\QueueOperatingSetting;
use Carbon\Carbon;

class FridayPrayerBreakService
{
    /**
     * @return array{starts_at: Carbon, ends_at: Carbon}|null
     */
    public function active(?Carbon $at = null): ?array
    {
        $now = ($at ?: now())->copy()->setTimezone('Asia/Jakarta');

        if ($now->isoWeekday() !== Carbon::FRIDAY) {
            return null;
        }

        $friday = collect((array) QueueOperatingSetting::query()->value('weekly_schedule'))
            ->first(fn (mixed $entry): bool => (int) (((array) $entry)['day'] ?? 0) === Carbon::FRIDAY);
        $friday = (array) $friday;
        $startsAt = $this->atTime($now, $friday['break_starts_at'] ?? null);
        $endsAt = $this->atTime($now, $friday['break_ends_at'] ?? null);

        if (! $startsAt || ! $endsAt || $startsAt->greaterThanOrEqualTo($endsAt)) {
            return null;
        }

        if ($now->lessThan($startsAt) || $now->greaterThanOrEqualTo($endsAt)) {
            return null;
        }

        return ['starts_at' => $startsAt, 'ends_at' => $endsAt];
    }

    private function atTime(Carbon $date, mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{2}:\d{2}/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.substr($value, 0, 5), 'Asia/Jakarta');
    }
}
