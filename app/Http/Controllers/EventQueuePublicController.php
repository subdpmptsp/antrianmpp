<?php

namespace App\Http\Controllers;

use App\Models\EventQueue;
use App\Models\EventQueueParticipant;
use App\Services\EventQueueService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventQueuePublicController extends Controller
{
    public function registration(string $token): View
    {
        $event = $this->eventByPublicToken($token);

        return view('event-queue.registration', compact('event'));
    }

    public function register(Request $request, string $token, EventQueueService $service): RedirectResponse
    {
        $event = $this->eventByPublicToken($token);
        $key = 'event-registration:'.$event->id.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['event' => 'Terlalu banyak percobaan. Silakan tunggu beberapa menit.'])->withInput();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nik' => ['required', 'digits:16'],
            'phone' => ['required', 'string', 'min:9', 'max:32', 'regex:/^[0-9+\-\s]+$/'],
        ]);

        try {
            $participant = $service->register($event, $data);
            RateLimiter::clear($key);

            return redirect()->route('event.ticket', ['token' => $token, 'ticket' => $participant->qr_token]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            RateLimiter::hit($key, 120);
            throw $exception;
        }
    }

    public function ticket(string $token, string $ticket): View
    {
        $event = $this->eventByPublicToken($token);
        $participant = $event->participants()->where('qr_token', $ticket)->firstOrFail();

        return view('event-queue.ticket', compact('event', 'participant'));
    }

    public function downloadTicket(string $token, string $ticket)
    {
        $event = $this->eventByPublicToken($token);
        $participant = $event->participants()->where('qr_token', $ticket)->firstOrFail();

        return Pdf::loadView('event-queue.ticket-pdf', compact('event', 'participant'))
            ->setPaper('a5', 'portrait')
            ->download('tiket-event-'.$participant->ticket_number.'.pdf');
    }

    public function calendar(string $token, string $ticket)
    {
        $event = $this->eventByPublicToken($token);
        $participant = $event->participants()->where('qr_token', $ticket)->firstOrFail();
        $start = $event->starts_at ?: ($event->arrival_date ? Carbon::parse($event->arrival_date)->setTime(8, 0) : now());
        $end = $event->ends_at ?: $start->copy()->addHour();
        $escape = static fn (string $value): string => str_replace(["\\", ",", ";", "\n"], ["\\\\", "\\,", "\\;", "\\n"], $value);

        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//SIOLA Q//Event Queue//ID\r\nBEGIN:VEVENT\r\n".
            'UID:'.$participant->qr_token."@siola-q\r\n".
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z')."\r\n".
            'DTSTART:'.$start->utc()->format('Ymd\THis\Z')."\r\n".
            'DTEND:'.$end->utc()->format('Ymd\THis\Z')."\r\n".
            'SUMMARY:'.$escape($event->name.' — '.$participant->ticket_number)."\r\n".
            'DESCRIPTION:'.$escape('Tunjukkan QR tiket saat check-in.')."\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="event-'.$participant->ticket_number.'.ics"',
        ]);
    }

    public function lookup(string $token): View
    {
        $event = $this->eventByPublicToken($token);

        return view('event-queue.lookup', compact('event'));
    }

    public function findTicket(Request $request, string $token): RedirectResponse
    {
        $event = $this->eventByPublicToken($token);
        $data = $request->validate(['identifier' => ['required', 'string', 'max:32']]);
        $identifier = preg_replace('/\s+/', '', $data['identifier']);
        $participant = $event->participants()
            ->where(function ($query) use ($identifier): void {
                $query->where('nik', $identifier)->orWhere('phone', $identifier);
            })
            ->first();

        if (! $participant) {
            return back()->withErrors(['identifier' => 'Tiket tidak ditemukan. Periksa kembali NIK atau nomor WhatsApp.']);
        }

        return redirect()->route('event.ticket', ['token' => $token, 'ticket' => $participant->qr_token]);
    }

    public function tv(string $token): View
    {
        $event = EventQueue::query()->where('tv_token', $token)->firstOrFail();

        return view('event-queue.tv', compact('event'));
    }

    public function tvStatus(string $token): JsonResponse
    {
        $event = EventQueue::query()->where('tv_token', $token)->firstOrFail();
        $participants = $event->participants()
            ->where('status', '!=', EventQueueParticipant::STATUS_CANCELED)
            ->latest('id')
            ->limit(8)
            ->get();

        return response()->json([
            'event' => $event->name,
            'status' => $event->status,
            'registered' => $event->participants()->where('status', '!=', EventQueueParticipant::STATUS_CANCELED)->count(),
            'checked_in' => $event->participants()->whereIn('status', [EventQueueParticipant::STATUS_CHECKED_IN, EventQueueParticipant::STATUS_SERVING])->count(),
            'quota' => $event->daily_quota,
            'updated_at' => now()->format('H:i:s'),
            'participants' => $participants->map(fn (EventQueueParticipant $participant): array => [
                'ticket' => $participant->ticket_number,
                'name' => $event->mask_participant_names ? $participant->masked_name : $participant->name,
                'status' => $participant->status,
            ]),
        ]);
    }

    private function eventByPublicToken(string $token): EventQueue
    {
        return EventQueue::query()
            ->where('public_token', $token)
            ->where('public_link_enabled', true)
            ->firstOrFail();
    }
}
