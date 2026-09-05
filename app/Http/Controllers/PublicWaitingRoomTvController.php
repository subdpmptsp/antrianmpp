<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Setting;
use App\Services\AudioConfigurationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicWaitingRoomTvController extends Controller
{
    public function __invoke(Request $request): View
    {
        $zones = collect(config('tv.zones', []))
            ->map(fn (array $zone, int $number): array => [
                'number' => $number,
                'name' => (string) ($zone['name'] ?? "ZONA {$number}"),
            ])
            ->values();
        $requestedZone = $request->string('zone')->toString();
        $selectedZone = $zones->contains('name', $requestedZone) ? $requestedZone : null;

        $counters = Counter::query()
            ->withoutGlobalScopes()
            ->where('is_archived', false)
            ->when($selectedZone, fn ($query) => $query->where('name', $selectedZone))
            ->when(! $selectedZone, fn ($query) => $query->whereRaw('1 = 0'))
            ->with([
                'service',
                'activeQueue.service',
                'nextQueue.service',
            ])
            ->withCount([
                'queues as today_queue_count' => fn ($query) => $query->whereDate('created_at', now()->toDateString()),
                'queues as waiting_queue_count' => fn ($query) => $query
                    ->where('status', 'waiting')
                    ->whereDate('created_at', now()->toDateString()),
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $audioConfig = app(AudioConfigurationService::class)->get();

        return view('public.tv-waiting-room', [
            'zones' => $zones,
            'selectedZone' => $selectedZone,
            'selectedZoneIsValid' => $selectedZone !== null,
            'counters' => $counters,
            'setting' => Setting::first() ?? (object) [
                'name' => 'Mall Pelayanan Publik',
                'address' => 'Alamat belum diatur',
                'image' => null,
            ],
            'announcementOpeningAudioUrl' => $audioConfig['url'] ?? asset(config('audio.fallback.url', 'sounds/opening.mp3')),
            'ttsSettings' => $audioConfig['tts'] ?? [],
        ]);
    }
}
