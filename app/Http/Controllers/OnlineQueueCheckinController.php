<?php

namespace App\Http\Controllers;

use App\Models\OnlineQueueCheckinChallenge;
use App\Models\OnlineQueueReservation;
use App\Models\OnlineQueueSession;
use App\Models\OnlineQueueSetting;
use App\Services\OnlineQueueService;
use App\Services\FridayPrayerBreakService;
use App\Services\KioskOperationalClosureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OnlineQueueCheckinController extends Controller
{
    public function kiosk(): View
    {
        $enabled = $this->enabled();
        $token = null;

        if ($enabled) {
            $token = Str::random(64);
            OnlineQueueCheckinChallenge::query()->create([
                'token_hash' => hash('sha256', $token),
                'station_code' => config('kiosk.online_checkin_station', 'KIOSK-01'),
                'expires_at' => now()->addSeconds(90),
            ]);
            OnlineQueueCheckinChallenge::query()
                ->whereNull('completed_at')->where('expires_at', '<', now()->subMinutes(10))->delete();
        }

        return view('online-queue.kiosk-checkin', compact('enabled', 'token'));
    }

    public function phone(string $token): View
    {
        $challenge = $this->challenge($token, allowCompleted: true);

        return view('online-queue.checkin', [
            'token' => $token,
            'challenge' => $challenge->load(['reservation.service.instansi', 'reservation.session', 'queue']),
        ]);
    }

    public function confirm(Request $request, string $token, OnlineQueueService $onlineQueues): RedirectResponse
    {
        $data = $request->validate([
            'booking_code' => ['required', 'string', 'max:20'],
            'nik_last_four' => ['required', 'digits:4'],
        ]);

        DB::transaction(function () use ($token, $data, $onlineQueues): void {
            $challenge = OnlineQueueCheckinChallenge::query()
                ->where('token_hash', hash('sha256', $token))->lockForUpdate()->firstOrFail();
            if ($challenge->completed_at) return;
            if ($challenge->expires_at->isPast()) {
                throw ValidationException::withMessages(['booking_code' => 'QR mesin sudah kedaluwarsa. Pindai QR terbaru pada layar kiosk.']);
            }

            $reservation = OnlineQueueReservation::query()
                ->where('booking_code', strtoupper(trim($data['booking_code'])))
                ->where('nik_last_four', $data['nik_last_four'])
                ->whereDate('service_date', today('Asia/Jakarta'))
                ->whereIn('status', [OnlineQueueReservation::STATUS_BOOKED, OnlineQueueReservation::STATUS_CHECKED_IN])
                ->first();
            if (! $reservation) {
                throw ValidationException::withMessages(['booking_code' => 'Reservasi hari ini tidak ditemukan. Periksa kode booking dan 4 digit terakhir NIK.']);
            }

            $queue = $onlineQueues->checkIn($reservation);
            $challenge->update([
                'online_queue_reservation_id' => $reservation->id,
                'queue_id' => $queue->id,
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('online-queue.checkin.phone', $token);
    }

    public function status(string $token): JsonResponse
    {
        $challenge = $this->challenge($token, allowCompleted: true);
        if (! $challenge->completed_at || ! $challenge->queue_id) {
            return response()->json(['status' => 'pending', 'expires_at' => $challenge->expires_at->toIso8601String()]);
        }

        $queue = $challenge->queue()->firstOrFail();
        $expiresAt = now()->addMinutes(2);

        return response()->json([
            'status' => 'completed',
            'number' => $queue->number,
            'print_url' => URL::temporarySignedRoute('tickets.print', $expiresAt, ['queue' => $queue], absolute: false),
            'confirm_url' => URL::temporarySignedRoute('tickets.print.confirm', $expiresAt, ['queue' => $queue], absolute: false),
            'fail_url' => URL::temporarySignedRoute('tickets.print.fail', $expiresAt, ['queue' => $queue], absolute: false),
        ]);
    }

    private function challenge(string $token, bool $allowCompleted = false): OnlineQueueCheckinChallenge
    {
        abort_unless($this->enabled(), 404);
        $challenge = OnlineQueueCheckinChallenge::query()->where('token_hash', hash('sha256', $token))->firstOrFail();
        if (! $allowCompleted && $challenge->completed_at) abort(410);
        if (! $challenge->completed_at && $challenge->expires_at->isPast()) abort(410, 'QR check-in telah kedaluwarsa.');

        return $challenge;
    }

    private function enabled(): bool
    {
        $settings = OnlineQueueSetting::current();

        if (! $settings->global_enabled || ! $settings->regular_enabled
            || app(FridayPrayerBreakService::class)->active()
            || app(KioskOperationalClosureService::class)->active()) {
            return false;
        }

        $sessions = OnlineQueueSession::query()
            ->where('status', OnlineQueueSession::STATUS_ACTIVE)
            ->whereHas('service', fn ($service) => $service
                ->where('is_active', true)
                ->where('is_archived', false));

        if ($settings->pilot_mode) {
            $sessions->whereIn('service_id', array_map('intval', $settings->pilot_service_ids ?? []));
        }

        return $sessions->exists();
    }
}
