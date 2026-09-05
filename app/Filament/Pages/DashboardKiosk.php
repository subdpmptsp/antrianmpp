<?php

namespace App\Filament\Pages;

use App\Models\Counter;
use App\Models\Setting;
use App\Services\AudioConfigurationService;
use Filament\Pages\Page;

class DashboardKiosk extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static string $view = 'filament.pages.dashboard-kiosk';

    protected static string $layout = 'filament.layouts.base-kiosk';

    protected static ?string $navigationLabel = 'Kiosk Ruang Tunggu';

    protected static ?string $navigationGroup = 'Operasional';

    public ?string $selectedZone = null;

    /** Kecepatan gulir daftar antrean dalam piksel per detik. */
    public int $scrollSpeed = 24;

    public function mount(): void
    {
        $zone = request()->string('zone')->toString();
        $available = collect(config('tv.zones', []))
            ->pluck('name')
            ->map(fn ($name): string => (string) $name);

        $this->selectedZone = $available->contains($zone) ? $zone : null;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public function getViewData(): array
    {
        $zones = collect(config('tv.zones', []))
            ->map(fn (array $zone, int $number): array => [
                'number' => $number,
                'name' => (string) ($zone['name'] ?? "ZONA {$number}"),
            ])
            ->values();

        $selectedZoneIsValid = $zones->contains('name', $this->selectedZone);

        $counters = Counter::query()
            ->withoutGlobalScopes()
            ->where('is_archived', false)
            ->when($selectedZoneIsValid, fn ($query) => $query->where('name', $this->selectedZone))
            ->when(! $selectedZoneIsValid, fn ($query) => $query->whereRaw('1 = 0'))
            ->with([
                'service', 
                'activeQueue.service', 
                'nextQueue.service',
                'queues' => function($query) {
                    $query->whereIn('status', ['called', 'serving'])
                        ->whereDate('created_at', now()->toDateString())
                        ->orderByRaw("CASE WHEN status = 'serving' THEN 1 WHEN status = 'called' THEN 2 END")
                        ->latest('called_at')
                        ->limit(1);
                }
            ])
            ->withCount([
                'queues as today_queue_count' => function ($query) {
                    $query->whereDate('created_at', now()->toDateString());
                },
                'queues as waiting_queue_count' => function ($query) {
                    $query->where('status', 'waiting')
                        ->whereDate('created_at', now()->toDateString());
                },
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $audioConfig = app(AudioConfigurationService::class)->get();

        return [
            'zones' => $zones,
            'selectedZoneIsValid' => $selectedZoneIsValid,
            'counters' => $counters,
            'setting' => Setting::first() ?? (object)[
                'name' => 'Mall Pelayanan Publik',
                'address' => 'Alamat belum diatur',
                'image' => null,
            ],
            'announcementOpeningAudioUrl' => $audioConfig['url'] ?? asset(config('audio.fallback.url', 'sounds/opening.mp3')),
            'ttsSettings' => $audioConfig['tts'] ?? [],
        ];
    }

    public function selectZone(string $zone): void
    {
        $available = collect(config('tv.zones', []))
            ->pluck('name')
            ->map(fn ($name): string => (string) $name);

        if ($available->contains($zone)) {
            $this->selectedZone = $zone;
        }
    }

    public function resetZone(): void
    {
        $this->selectedZone = null;
    }

    public function updatedScrollSpeed(int|string $speed): void
    {
        $this->scrollSpeed = max(8, min(60, (int) $speed));
    }

    /**
     * Method untuk refresh data real-time
     * Dipanggil oleh wire:poll untuk sinkronisasi dengan DashboardCallKiosk
     */
    public function refreshData()
    {
        // Hanya refresh data, tidak perlu logic tambahan
        // Livewire akan otomatis re-render dengan data terbaru
    }
    
    /**
     * Method legacy untuk backward compatibility
     * Sekarang hanya memanggil refreshData
     */
    public function callNextQueue()
    {
        // Method ini dipanggil oleh wire:poll
        // Sekarang hanya refresh data untuk sinkronisasi
        $this->refreshData();
    }
}
