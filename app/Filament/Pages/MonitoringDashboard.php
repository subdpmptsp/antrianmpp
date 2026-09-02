<?php

namespace App\Filament\Pages;

use App\Exports\RekapLayananExport;
use App\Models\Counter;
use App\Services\MonitoringRealtimeService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringDashboard extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Monitoring';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.monitoring-dashboard';

    public string $activeTab = 'realtime';
    public ?string $zoneFilter = null;
    public ?string $reportZoneFilter = null;
    public ?string $search = null;
    public ?string $from = null;
    public ?string $to = null;

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<int, int> */
    public array $expandedInstansiIds = [];

    public int $lastRefreshedAt = 0;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->from = now()->toDateString();
        $this->to = now()->toDateString();
        $this->lastRefreshedAt = now()->timestamp;
        $this->form->fill(['from' => $this->from, 'to' => $this->to]);
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\DatePicker::make('from')->label('Dari Tanggal')->required(),
                Forms\Components\DatePicker::make('to')->label('Sampai Tanggal')->required(),
            ]),
        ])->statePath('data');
    }

    public function selectTab(string $tab): void
    {
        if (in_array($tab, ['realtime', 'report'], true)) {
            $this->activeTab = $tab;
            $this->lastRefreshedAt = now()->timestamp;
        }
    }

    public function refreshData(): void
    {
        $this->lastRefreshedAt = now()->timestamp;
    }

    public function applyReportFilters(): void
    {
        if (! filled($this->reportZoneFilter)) {
            Notification::make()
                ->title('Pilih zona terlebih dahulu')
                ->body('Rekap rinci hanya dimuat untuk zona yang dipilih agar halaman tetap ringan.')
                ->warning()
                ->send();

            return;
        }

        $state = $this->form->getState();
        $this->from = $state['from'] ?? now()->toDateString();
        $this->to = $state['to'] ?? now()->toDateString();
        $this->expandedInstansiIds = [];
    }

    public function toggleInstansi(int $instansiId): void
    {
        if (in_array($instansiId, $this->expandedInstansiIds, true)) {
            $this->expandedInstansiIds = array_values(array_filter($this->expandedInstansiIds, fn (int $id): bool => $id !== $instansiId));
            return;
        }

        $this->expandedInstansiIds[] = $instansiId;
    }

    public function exportExcel()
    {
        if (! filled($this->reportZoneFilter)) {
            Notification::make()
                ->title('Pilih zona rekap terlebih dahulu')
                ->body('Pilih satu zona atau opsi Semua Zona sebelum mengekspor data.')
                ->warning()
                ->send();

            return null;
        }

        return Excel::download(
            new RekapLayananExport($this->from, $this->to, $this->reportZoneFilter),
            'rekap_layanan_'.Carbon::parse($this->from)->format('Y-m-d').'_sd_'.Carbon::parse($this->to)->format('Y-m-d').'.xlsx',
        );
    }

    public function exportDescription(): string
    {
        $zone = $this->reportZoneFilter && $this->reportZoneFilter !== 'all'
            ? (string) config("tv.zones.{$this->reportZoneFilter}.name", "ZONA {$this->reportZoneFilter}")
            : 'seluruh zona';

        return sprintf(
            'Mengekspor seluruh layanan aktif %s pada rentang %s s.d. %s.',
            $zone,
            Carbon::parse($this->from)->translatedFormat('d F Y'),
            Carbon::parse($this->to)->translatedFormat('d F Y'),
        );
    }

    public function getViewData(): array
    {
        $monitoring = app(MonitoringRealtimeService::class);
        $isRealtime = $this->activeTab === 'realtime';
        $isReport = $this->activeTab === 'report';

        return [
            'summary' => $isRealtime ? $monitoring->getSummary() : null,
            'zones' => $isRealtime ? $monitoring->getZones() : collect(),
            'services' => $isRealtime && filled($this->zoneFilter) ? $monitoring->getServices($this->zoneFilter, $this->search) : collect(),
            'zoneOptions' => $monitoring->getZoneOptions(),
            'rekapan' => $isReport && filled($this->reportZoneFilter) ? $this->getRekapJumlahPemohon() : collect(),
        ];
    }

    /** @return Collection<int, \App\Models\Instansi> */
    protected function getRekapJumlahPemohon(): Collection
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();

        return \App\Models\Instansi::query()
            ->whereIn('counter_id', $this->counterIdsForZone($this->reportZoneFilter))
            ->whereHas('services', fn ($query) => $query->where('is_active', true)->where('is_archived', false))
            ->with(['services' => function ($query) use ($from, $to): void {
                $query->where('is_active', true)->where('is_archived', false)->withCount([
                    'queues as total_pemohon' => fn ($queue) => $queue->whereBetween('created_at', [$from, $to]),
                ])->orderBy('prefix');
            }])
            ->orderBy('nama_instansi')
            ->get()
            ->map(function (\App\Models\Instansi $instansi) {
                $instansi->total_pemohon = $instansi->services->sum('total_pemohon');
                return $instansi;
            });
    }

    /** @return Collection<int, int> */
    protected function counterIdsForZone(?string $zoneId): Collection
    {
        if (! filled($zoneId)) {
            return collect();
        }

        if ($zoneId === 'all') {
            return Counter::withoutGlobalScopes()
                ->whereIn('name', collect(config('tv.zones', []))->pluck('name'))
                ->pluck('id');
        }

        $zoneName = (string) config("tv.zones.{$zoneId}.name", "ZONA {$zoneId}");
        return Counter::withoutGlobalScopes()->where('name', $zoneName)->pluck('id');
    }
}
