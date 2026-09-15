<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\QueueOperatingSetting;
use Carbon\Carbon;

class KioskPreOpeningService
{
    /** @return array{opens_at: Carbon}|null */
    public function active(?Carbon $at = null): ?array
    {
        $now = ($at ?: now())->copy()->setTimezone('Asia/Jakarta');

        if (Holiday::query()->whereDate('date', $now->toDateString())->exists()) {
            return null;
        }

        $schedule = (array) QueueOperatingSetting::query()->first()?->weekly_schedule;
        $today = collect($schedule)->first(
            fn (mixed $entry): bool => (int) (((array) $entry)['day'] ?? 0) === $now->isoWeekday()
        );
        $today = (array) $today;

        if (empty($today['is_open']) || ! is_string($today['opens_at'] ?? null)) {
            return null;
        }

        $value = substr($today['opens_at'], 0, 5);
        if (! preg_match('/^\d{2}:\d{2}$/', $value)) {
            return null;
        }

        $opensAt = Carbon::createFromFormat('Y-m-d H:i', $now->toDateString().' '.$value, 'Asia/Jakarta');

        return $now->lessThan($opensAt) ? ['opens_at' => $opensAt] : null;
    }
}
