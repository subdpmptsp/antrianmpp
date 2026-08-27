<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\MonitoringRealtimeService;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class MonitoringDashboardRealTime extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Monitoring Real-Time';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.monitoring-dashboard-real-time';

    protected static ?string $title = '';

    public ?string $zoneFilter = '';

    public ?string $search = '';

    public int $lastRefreshedAt = 0;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(MonitoringRealtimeService $service): void
    {
        $this->lastRefreshedAt = now()->timestamp;
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function refreshData(): void
    {
        $this->lastRefreshedAt = now()->timestamp;
    }

    public function getViewData(): array
    {
        $service = app(MonitoringRealtimeService::class);
        $setting = Setting::first();

        return [
            'summary' => $service->getSummary(),
            'zones' => $service->getZones(),
            'services' => $service->getServices(
                filled($this->zoneFilter) ? $this->zoneFilter : null,
                filled($this->search) ? $this->search : null,
            ),
            'zoneOptions' => $service->getZoneOptions(),
            'setting' => $setting,
            'exportUrl' => route('export.monitoring-realtime', array_filter([
                'zone_id' => $this->zoneFilter ?: null,
                'search' => $this->search ?: null,
            ])),
        ];
    }
}
