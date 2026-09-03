<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Queue;
use App\Models\QueueOperatingSetting;
use App\Models\Service;
use App\Models\ServiceQueueDateOverride;
use Carbon\Carbon;

class ServiceQueueAvailabilityService
{
    /** @return array{available: bool, message: string, code: string} */
    public function evaluate(Service $service, ?Carbon $at = null): array
    {
        $now = ($at ?: now())->setTimezone('Asia/Jakarta');

        if (! $service->is_active || $service->is_archived || ! $service->is_accepting_queues) {
            return $this->closed('Layanan ini sedang tidak menerima nomor antrean.', 'service_closed');
        }

        $settings = QueueOperatingSetting::query()->first();
        $globalSchedule = (array) ($settings?->weekly_schedule ?? []);
        $counters = $service->counters()
            ->where('is_active', true)
            ->where('is_archived', false)
            ->with('queueScheduleOverride')
            ->get();

        $activeOverrides = $counters->map(fn ($counter) => $counter->queueScheduleOverride)
            ->filter(fn ($override) => $override && (! $override->valid_until || $override->valid_until->greaterThanOrEqualTo($now)));
        $forceOpen = $activeOverrides->firstWhere('mode', 'force_open');
        $allClosed = $counters->isNotEmpty() && $counters->every(function ($counter) use ($now): bool {
            $override = $counter->queueScheduleOverride;
            return $override && $override->mode === 'force_closed'
                && (! $override->valid_until || $override->valid_until->greaterThanOrEqualTo($now));
        });

        if ($allClosed) {
            return $this->closed('Seluruh loket layanan ini ditutup sementara oleh administrator.', 'manual_closed');
        }

        // Paksa buka merupakan pengecualian eksplisit, termasuk bila tanggal itu hari libur.
        if (! $forceOpen) {
            if (Holiday::query()->whereDate('date', $now->toDateString())->exists()) {
                return $this->closed('Pengambilan antrean tutup karena hari libur.', 'holiday');
            }

            $dateOverride = ServiceQueueDateOverride::query()
                ->where('service_id', $service->id)
                ->whereDate('date', $now->toDateString())
                ->first();

            if ($dateOverride?->is_closed) {
                return $this->closed('Pengambilan antrean ditutup untuk tanggal ini'.($dateOverride->reason ? ': '.$dateOverride->reason : '.'), 'date_closed');
            }

            $candidateSchedules = $counters->map(function ($counter) use ($globalSchedule, $now): array {
                $override = $counter->queueScheduleOverride;
                if ($override && $override->mode === 'custom' && is_array($override->weekly_schedule)) {
                    return $override->weekly_schedule;
                }

                return $globalSchedule;
            });

            if ($candidateSchedules->isEmpty()) {
                $candidateSchedules->push($globalSchedule);
            }

            $todaySchedules = $candidateSchedules->map(fn (array $schedule) => $this->daySchedule($schedule, $now->isoWeekday()));
            $openSchedules = $todaySchedules->filter(fn ($day) => is_array($day) && ! empty($day['is_open']));

            if ($openSchedules->isEmpty()) {
                return $this->closed('Layanan ini tidak membuka antrean hari ini.', 'day_closed');
            }

            $opensAt = $openSchedules->map(fn (array $day) => $this->atTime($now, $day['opens_at'] ?? null))->filter();
            if ($opensAt->isNotEmpty() && $now->lessThan($opensAt->min())) {
                return $this->closed('Antrean belum dibuka. Pengambilan nomor dimulai pukul '.$opensAt->min()->format('H.i').' WIB.', 'not_open');
            }

            $cutoff = max(0, (int) ($settings?->cutoff_minutes ?? 30));
            $lastTickets = $openSchedules->map(function (array $day) use ($now, $cutoff): ?Carbon {
                $closeAt = $this->atTime($now, $day['closes_at'] ?? null);
                return $closeAt?->copy()->subMinutes($cutoff);
            })->filter();
            if ($lastTickets->isNotEmpty() && $now->greaterThanOrEqualTo($lastTickets->max())) {
                return $this->closed('Pengambilan antrean hari ini telah ditutup pukul '.$lastTickets->max()->format('H.i').' WIB. Petugas tetap melayani nomor yang sudah terdaftar.', 'last_ticket_passed');
            }
        }

        if ($settings?->default_daily_quota !== null) {
            $issued = Queue::query()
                ->where('service_id', $service->id)
                ->whereDate('created_at', $now->toDateString())
                ->where('status', '!=', Queue::STATUS_CANCELED)
                ->count();

            if ($issued >= $settings->default_daily_quota) {
                return $this->closed('Kuota antrean hari ini sudah terpenuhi.', 'quota_reached');
            }
        }

        return ['available' => true, 'message' => 'Antrean tersedia.', 'code' => 'available'];
    }

    private function atTime(Carbon $date, mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') return null;

        return Carbon::createFromFormat('Y-m-d H:i', $date->toDateString().' '.substr($value, 0, 5), 'Asia/Jakarta');
    }

    /** @param array<int, mixed> $schedule */
    private function daySchedule(array $schedule, int $weekday): ?array
    {
        foreach ($schedule as $entry) {
            $entry = (array) $entry;
            if ((int) ($entry['day'] ?? 0) === $weekday) {
                return $entry;
            }
        }

        return null;
    }

    /** @return array{available: false, message: string, code: string} */
    private function closed(string $message, string $code): array
    {
        return ['available' => false, 'message' => $message, 'code' => $code];
    }
}
