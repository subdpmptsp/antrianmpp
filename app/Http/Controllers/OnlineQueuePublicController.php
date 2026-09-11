<?php

namespace App\Http\Controllers;

use App\Models\OnlineQueueReservation;
use App\Models\OnlineQueueSession;
use App\Models\OnlineQueueSetting;
use App\Models\Holiday;
use App\Models\ServiceQueueDateOverride;
use App\Services\OnlineQueueService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OnlineQueuePublicController extends Controller
{
    public function index(): View
    {
        $settings = OnlineQueueSetting::current();

        return view('online-queue.registration', [
            'enabled' => $settings->global_enabled && $settings->regular_enabled,
            'slots' => $this->availableSlots($settings),
        ]);
    }

    public function store(Request $request, OnlineQueueService $onlineQueues): RedirectResponse
    {
        $key = 'online-queue-booking:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            throw ValidationException::withMessages(['booking' => 'Terlalu banyak percobaan. Silakan tunggu beberapa menit.']);
        }
        $data = $request->validate([
            'nik' => ['required', 'digits:16'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'min:9', 'max:32', 'regex:/^[0-9+\-\s]+$/'],
            'slot' => ['required', 'regex:/^\d+\|\d{4}-\d{2}-\d{2}$/'],
            'agreement' => ['accepted'],
        ]);
        [$sessionId, $serviceDate] = explode('|', $data['slot'], 2);
        try {
            $reservation = $onlineQueues->book(OnlineQueueSession::query()->findOrFail((int) $sessionId), [
                'nik' => $data['nik'], 'name' => $data['name'], 'phone' => $data['phone'], 'service_date' => $serviceDate,
            ]);
            RateLimiter::clear($key);
            $request->session()->put('online_queue_access_token', $reservation->access_token);

            return redirect()->route('online-queue.ticket', $reservation->access_token);
        } catch (ValidationException $exception) {
            RateLimiter::hit($key, 120);
            throw $exception;
        }
    }

    public function ticket(string $token): View
    {
        return view('online-queue.ticket', ['reservation' => $this->reservation($token)]);
    }

    public function lookup(): View
    {
        return view('online-queue.lookup');
    }

    public function find(Request $request): RedirectResponse
    {
        $key = 'online-queue-lookup:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return back()->withErrors(['booking_code' => 'Terlalu banyak percobaan. Coba kembali beberapa menit lagi.']);
        }
        $data = $request->validate(['booking_code' => ['required', 'string', 'max:20'], 'nik_last_four' => ['required', 'digits:4']]);
        $reservation = OnlineQueueReservation::query()
            ->where('booking_code', strtoupper(trim($data['booking_code'])))
            ->where('nik_last_four', $data['nik_last_four'])->first();
        if (! $reservation) {
            RateLimiter::hit($key, 120);
            return back()->withErrors(['booking_code' => 'Reservasi tidak ditemukan. Periksa kode booking dan 4 digit terakhir NIK.'])->withInput();
        }
        RateLimiter::clear($key);

        return redirect()->route('online-queue.ticket', $reservation->access_token);
    }

    public function cancel(string $token, OnlineQueueService $onlineQueues): RedirectResponse
    {
        $reservation = $this->reservation($token);
        if (! $onlineQueues->cancel($reservation)) {
            return back()->withErrors(['reservation' => 'Reservasi ini tidak dapat dibatalkan.']);
        }

        return redirect()->route('online-queue.ticket', $token)->with('status', 'Reservasi berhasil dibatalkan dan kuota dikembalikan.');
    }

    public function calendar(string $token)
    {
        $reservation = $this->reservation($token);
        $start = Carbon::parse($reservation->service_date->toDateString().' '.$reservation->session->starts_at, 'Asia/Jakarta');
        $end = Carbon::parse($reservation->service_date->toDateString().' '.$reservation->session->ends_at, 'Asia/Jakarta');
        $escape = static fn (string $value): string => str_replace(["\\", ";", ",", "\r", "\n"], ["\\\\", "\\;", "\\,", '', "\\n"], $value);
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//SIOLA Q//Online Queue//ID\r\nBEGIN:VEVENT\r\n".
            'UID:'.$reservation->access_token."@siola-q\r\nDTSTAMP:".now()->utc()->format('Ymd\THis\Z')."\r\n".
            'DTSTART:'.$start->utc()->format('Ymd\THis\Z')."\r\nDTEND:".$end->utc()->format('Ymd\THis\Z')."\r\n".
            'SUMMARY:'.$escape('Reservasi MPP SIOLA - '.$reservation->service->name)."\r\nDESCRIPTION:".$escape('Kode booking '.$reservation->booking_code)."\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        return response($ics, 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Content-Disposition' => 'attachment; filename="reservasi-'.$reservation->booking_code.'.ics"']);
    }

    private function reservation(string $token): OnlineQueueReservation
    {
        return OnlineQueueReservation::query()->with(['service.instansi', 'session', 'queue'])->where('access_token', $token)->firstOrFail();
    }

    private function availableSlots(OnlineQueueSetting $settings): array
    {
        if (! $settings->global_enabled || ! $settings->regular_enabled) return [];
        $sessions = OnlineQueueSession::query()->with('service.instansi')->where('status', OnlineQueueSession::STATUS_ACTIVE)
            ->whereHas('service', fn ($query) => $query->where('is_active', true)->where('is_archived', false))->orderBy('starts_at')->get();
        if ($settings->pilot_mode) {
            $pilotServiceIds = array_map('intval', $settings->pilot_service_ids ?? []);
            $sessions = $sessions->whereIn('service_id', $pilotServiceIds);
        }
        $slots = [];
        foreach (range(0, $settings->booking_window_days) as $offset) {
            $date = now('Asia/Jakarta')->startOfDay()->addDays($offset);
            if (Holiday::query()->whereDate('date', $date)->exists()) continue;
            foreach ($sessions->where('day_of_week', $date->isoWeekday()) as $session) {
                if (ServiceQueueDateOverride::query()->where('service_id', $session->service_id)->whereDate('date', $date)->where('is_closed', true)->exists()) continue;
                if ($date->isToday() && now('Asia/Jakarta')->greaterThanOrEqualTo(Carbon::parse($date->toDateString().' '.$session->ends_at, 'Asia/Jakarta'))) continue;
                $used = $session->reservations()->whereDate('service_date', $date)->whereIn('status', ['booked', 'checked_in'])->count();
                if ($used >= $session->quota) continue;
                $slots[] = ['value' => $session->id.'|'.$date->toDateString(), 'service' => $session->service->name,
                    'institution' => $session->service->instansi?->nama_instansi, 'date' => $date->translatedFormat('D, d M Y'),
                    'time' => substr($session->starts_at, 0, 5).'–'.substr($session->ends_at, 0, 5), 'remaining' => $session->quota - $used];
            }
        }

        return $slots;
    }
}
