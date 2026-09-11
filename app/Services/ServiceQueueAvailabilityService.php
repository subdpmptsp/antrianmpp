<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Counter;
use App\Models\CounterClosureRequest;
use App\Models\Queue;
use App\Models\QueueOperatingSetting;
use App\Models\Service;
use App\Models\ServiceQueueDateOverride;
use Carbon\Carbon;

class ServiceQueueAvailabilityService
{
    /** @var array<int, true> */
    private array $recoveredServiceIds = [];

    private bool $hasClearedExpiredPauses = false;

    /** @return array{available: bool, message: string, code: string} */
    public function evaluate(Service $service, ?Carbon $at = null): array
    {
        $now = ($at ?: now())->setTimezone('Asia/Jakarta');

        // Pemulihan ini melengkapi scheduler pukul 00.00. Bila server dimatikan
        // pada malam hari, akses pertama ke kiosk tetap membatalkan penutupan
        // sementara yang sudah melewati hari operasionalnya.
        if (! isset($this->recoveredServiceIds[$service->id])) {
            $this->recoveredServiceIds[$service->id] = true;
            $closures = app(CounterClosureService::class);
            $recovered = $closures->recoverOverdueClosuresForService((int) $service->id);
            $clearedPauses = 0;

            if (! $this->hasClearedExpiredPauses) {
                $this->hasClearedExpiredPauses = true;
                $clearedPauses = $closures->clearExpiredServiceQueuePauses();
            }

            if ($recovered > 0 || $clearedPauses > 0) {
                $service->refresh();
            }
        }

        if (! $service->is_active || $service->is_archived) {
            return $this->closed('Layanan ini sedang tidak menerima nomor antrean.', 'service_closed');
        }

        if ($service->queue_override === 'force_closed'
            && (! $service->queue_override_until || $service->queue_override_until->greaterThanOrEqualTo($now))) {
            $until = $service->queue_override_until?->copy()->setTimezone('Asia/Jakarta');
            $message = 'Layanan ini sedang istirahat.';
            if ($until) {
                $message .= ' Pengambilan antrean dibuka kembali pukul '.$until->format('H.i').' WIB.';
            }

            return $this->closed($message, 'temporary_service_closed');
        }

        if (! $service->is_accepting_queues) {
            return $this->closed(
                $this->scheduledCounterReopenMessage($service, $now)
                    ?? 'Layanan ini sedang tidak menerima nomor antrean.',
                'service_closed',
            );
        }

        $service->loadMissing('instansi');
        if (! $service->instansi || ! $service->instansi->is_active || $service->instansi->is_archived) {
            return $this->closed('Instansi layanan ini sedang tidak aktif.', 'institution_closed');
        }

        $settings = QueueOperatingSetting::query()->first();
        $globalSchedule = (array) ($settings?->weekly_schedule ?? []);

        if ($break = app(FridayPrayerBreakService::class)->active($now)) {
            return $this->closed(
                'Jeda Istirahat Salat Jumat. Pengambilan nomor dibuka kembali pukul '.$break['ends_at']->format('H.i').' WIB.',
                'friday_prayer_break',
            );
        }

        $counters = Counter::withoutGlobalScopes()
            ->where('instansi_id', $service->instansi_id)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->where(function ($query) use ($service): void {
                $query->where('service_id', $service->id)
                    ->orWhereHas('additionalServices', fn ($additional) => $additional->whereKey($service->id));
            })
            ->with('queueScheduleOverride')
            ->get();

        if ($counters->isEmpty()) {
            return $this->closed('Belum ada loket aktif untuk layanan ini.', 'counter_unavailable');
        }

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

    /**
     * Bila seluruh layanan tertutup akibat pengajuan istirahat sementara,
     * tampilkan jam buka yang benar di kiosk. Penutupan manual tidak memiliki
     * jadwal ini, sehingga tetap memakai pesan umum agar tidak menyesatkan.
     */
    private function scheduledCounterReopenMessage(Service $service, Carbon $now): ?string
    {
        $counterIds = Counter::withoutGlobalScopes()
            ->where('service_id', $service->id)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->pluck('id');

        if ($counterIds->isEmpty()) {
            return null;
        }

        $reopenAt = CounterClosureRequest::query()
            ->whereIn('counter_id', $counterIds)
            ->where('status', CounterClosureRequest::STATUS_APPROVED)
            ->whereNotNull('scheduled_reopen_at')
            ->where('scheduled_reopen_at', '>=', $now)
            ->min('scheduled_reopen_at');

        if (! $reopenAt) {
            return null;
        }

        $until = Carbon::parse($reopenAt, 'Asia/Jakarta');

        return 'Layanan sedang istirahat. Pengambilan antrean akan dibuka kembali pukul '
            .$until->format('H.i').' WIB.';
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
