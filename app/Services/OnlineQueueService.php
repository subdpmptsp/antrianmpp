<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\OnlineQueueReservation;
use App\Models\OnlineQueueAudit;
use App\Models\OnlineQueueSession;
use App\Models\OnlineQueueSetting;
use App\Models\Queue;
use App\Models\ServiceQueueDateOverride;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnlineQueueService
{
    public function __construct(
        private readonly QueueService $queues,
        private readonly ServiceQueueAvailabilityService $availability,
    ) {}

    /** @param array{name:string,nik:string,phone:string,service_date:string} $data */
    public function book(OnlineQueueSession $session, array $data): OnlineQueueReservation
    {
        $nik = preg_replace('/\D+/', '', $data['nik']) ?? '';
        if (strlen($nik) !== 16) {
            throw ValidationException::withMessages(['nik' => 'NIK wajib terdiri dari tepat 16 digit.']);
        }

        $date = Carbon::parse($data['service_date'], 'Asia/Jakarta')->startOfDay();
        $settings = OnlineQueueSetting::current();
        if (! $settings->global_enabled || ! $settings->regular_enabled) {
            throw ValidationException::withMessages(['service' => 'Pendaftaran antrean online belum diaktifkan.']);
        }

        try {
            return DB::transaction(function () use ($session, $data, $nik, $date, $settings): OnlineQueueReservation {
                $lockedSession = OnlineQueueSession::query()->with('service.instansi')->lockForUpdate()->findOrFail($session->id);
                $service = $lockedSession->service;

                if ($lockedSession->status !== OnlineQueueSession::STATUS_ACTIVE || ! $service || ! $service->is_active || $service->is_archived) {
                    throw ValidationException::withMessages(['service' => 'Layanan atau sesi ini belum aktif.']);
                }
                if ($settings->pilot_mode && ! in_array((int) $service->id, array_map('intval', $settings->pilot_service_ids ?? []), true)) {
                    throw ValidationException::withMessages(['service' => 'Layanan ini belum termasuk dalam pilot antrean online.']);
                }

                $today = now('Asia/Jakarta')->startOfDay();
                if ($date->lessThan($today) || $date->greaterThan($today->copy()->addDays($settings->booking_window_days))) {
                    throw ValidationException::withMessages(['service_date' => 'Tanggal berada di luar rentang reservasi yang diperbolehkan.']);
                }
                if ($date->isoWeekday() !== $lockedSession->day_of_week) {
                    throw ValidationException::withMessages(['service_date' => 'Sesi tidak tersedia pada tanggal yang dipilih.']);
                }
                if ($date->isToday()
                    && now('Asia/Jakarta')->greaterThanOrEqualTo(Carbon::parse($date->toDateString().' '.$lockedSession->ends_at, 'Asia/Jakarta'))) {
                    throw ValidationException::withMessages(['session' => 'Waktu sesi ini telah berakhir.']);
                }
                if (Holiday::query()->whereDate('date', $date->toDateString())->exists()
                    || ServiceQueueDateOverride::query()->where('service_id', $service->id)->whereDate('date', $date)->where('is_closed', true)->exists()) {
                    throw ValidationException::withMessages(['service_date' => 'Layanan ditutup pada tanggal yang dipilih.']);
                }

                $used = OnlineQueueReservation::query()
                    ->where('online_queue_session_id', $lockedSession->id)
                    ->whereDate('service_date', $date)
                    ->whereIn('status', [OnlineQueueReservation::STATUS_BOOKED, OnlineQueueReservation::STATUS_CHECKED_IN])
                    ->count();
                if ($used >= $lockedSession->quota) {
                    throw ValidationException::withMessages(['session' => 'Kuota sesi ini sudah penuh.']);
                }

                $nikHash = $this->nikHash($nik);
                $identityKey = hash('sha256', $service->id.'|'.$date->toDateString().'|'.$nikHash);

                return OnlineQueueReservation::create([
                    'online_queue_session_id' => $lockedSession->id,
                    'service_id' => $service->id,
                    'service_date' => $date,
                    'booking_code' => $this->uniqueBookingCode(),
                    'access_token' => Str::random(64),
                    'nik' => $nik,
                    'nik_hash' => $nikHash,
                    'nik_last_four' => substr($nik, -4),
                    'active_identity_key' => $identityKey,
                    'name' => trim($data['name']),
                    'phone' => preg_replace('/\s+/', '', $data['phone']),
                    'status' => OnlineQueueReservation::STATUS_BOOKED,
                ]);
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '19'], true)) {
                throw ValidationException::withMessages(['nik' => 'NIK ini sudah mempunyai reservasi aktif pada layanan dan tanggal yang sama.']);
            }
            throw $exception;
        }
    }

    public function checkIn(OnlineQueueReservation $reservation, ?int $actorUserId = null, string $method = 'kiosk_qr'): Queue
    {
        return DB::transaction(function () use ($reservation, $actorUserId, $method): Queue {
            $locked = OnlineQueueReservation::query()->with(['session', 'service'])->lockForUpdate()->findOrFail($reservation->id);
            if ($locked->status === OnlineQueueReservation::STATUS_CHECKED_IN) {
                return $locked->queue()->firstOrFail();
            }
            if ($locked->status !== OnlineQueueReservation::STATUS_BOOKED) {
                throw ValidationException::withMessages(['reservation' => 'Reservasi tidak dapat digunakan untuk check-in.']);
            }
            if (! $locked->service_date->isSameDay(now('Asia/Jakarta'))) {
                throw ValidationException::withMessages(['reservation' => 'Check-in hanya dapat dilakukan pada tanggal reservasi.']);
            }

            $now = now('Asia/Jakarta');
            $opensAt = Carbon::parse($locked->service_date->toDateString().' '.$locked->session->starts_at, 'Asia/Jakarta')
                ->subMinutes($locked->session->checkin_open_minutes);
            $closesAt = Carbon::parse($locked->service_date->toDateString().' '.$locked->session->ends_at, 'Asia/Jakarta')
                ->addMinutes($locked->session->checkin_grace_minutes);
            if ($now->lessThan($opensAt)) {
                throw ValidationException::withMessages(['reservation' => 'Check-in dibuka pukul '.$opensAt->format('H.i').' WIB.']);
            }
            if ($now->greaterThan($closesAt)) {
                throw ValidationException::withMessages(['reservation' => 'Batas waktu check-in telah berakhir.']);
            }

            $availability = $this->availability->evaluate($locked->service, $now);
            if (! $availability['available']) {
                throw ValidationException::withMessages(['reservation' => $availability['message']]);
            }

            $queue = $this->queues->addOnlineQueue($locked->service_id, $locked->id);
            $locked->update(['status' => OnlineQueueReservation::STATUS_CHECKED_IN, 'checked_in_at' => $now]);
            $this->recordAudit($locked->id, 'checked_in', $actorUserId, [
                'method' => $method,
                'queue_id' => $queue->id,
            ]);

            return $queue;
        });
    }

    public function cancel(OnlineQueueReservation $reservation, ?int $actorUserId = null, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($reservation, $actorUserId, $reason): bool {
            $updated = OnlineQueueReservation::query()->whereKey($reservation->id)
                ->where('status', OnlineQueueReservation::STATUS_BOOKED)
                ->update(['status' => OnlineQueueReservation::STATUS_CANCELED, 'active_identity_key' => null, 'canceled_at' => now()]) === 1;
            if ($updated) {
                $this->recordAudit($reservation->id, 'canceled', $actorUserId, [
                    'method' => $actorUserId ? 'admin' : 'public',
                    'reason' => $reason ? mb_substr($reason, 0, 500) : null,
                ]);
            }

            return $updated;
        });
    }

    public function expireNoShows(): int
    {
        $now = now('Asia/Jakarta');

        $candidates = OnlineQueueReservation::query()
            ->with('session')
            ->where('status', OnlineQueueReservation::STATUS_BOOKED)
            ->whereDate('service_date', '<=', $now->toDateString())
            ->get();
        $expired = 0;
        foreach ($candidates as $reservation) {
            $deadline = Carbon::parse(
                $reservation->service_date->toDateString().' '.$reservation->session->ends_at,
                'Asia/Jakarta',
            )->addMinutes($reservation->session->checkin_grace_minutes);
            if ($now->lessThanOrEqualTo($deadline)) {
                continue;
            }

            $updated = DB::transaction(function () use ($reservation): bool {
                $changed = OnlineQueueReservation::query()->whereKey($reservation->id)
                    ->where('status', OnlineQueueReservation::STATUS_BOOKED)
                    ->update([
                        'status' => OnlineQueueReservation::STATUS_EXPIRED,
                        'active_identity_key' => null,
                        'expired_at' => now(),
                    ]) === 1;
                if ($changed) {
                    $this->recordAudit($reservation->id, 'expired', null, ['method' => 'scheduler']);
                }

                return $changed;
            });
            $expired += $updated ? 1 : 0;
        }

        return $expired;
    }

    private function nikHash(string $nik): string
    {
        return hash_hmac('sha256', $nik, (string) config('app.key'));
    }

    private function uniqueBookingCode(): string
    {
        do {
            $code = 'RSV-'.Str::upper(Str::random(8));
        } while (OnlineQueueReservation::query()->where('booking_code', $code)->exists());

        return $code;
    }

    /** @param array<string, mixed> $metadata */
    private function recordAudit(int $reservationId, string $action, ?int $userId, array $metadata = []): void
    {
        OnlineQueueAudit::query()->create([
            'online_queue_reservation_id' => $reservationId,
            'user_id' => $userId,
            'action' => $action,
            'metadata' => array_filter($metadata, static fn ($value) => $value !== null && $value !== ''),
            'created_at' => now(),
        ]);
    }
}
