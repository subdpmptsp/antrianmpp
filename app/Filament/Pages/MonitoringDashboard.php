<?php

namespace App\Filament\Pages;

use App\Exports\RekapLayananExport;
use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Services\MonitoringRealtimeService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public string $analysisRange = 'today';

    public string $analysisZoneFilter = 'all';

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
                Forms\Components\DatePicker::make('from')->label('Dari Tanggal')->native(false)->closeOnDateSelection()->live()->afterStateUpdated(fn () => $this->syncReportDates())->required(),
                Forms\Components\DatePicker::make('to')->label('Sampai Tanggal')->native(false)->closeOnDateSelection()->live()->afterStateUpdated(fn () => $this->syncReportDates())->required(),
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

    public function syncReportDates(): void
    {
        $this->from = $this->data['from'] ?? now()->toDateString();
        $this->to = $this->data['to'] ?? now()->toDateString();
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
        return Excel::download(
            new RekapLayananExport($this->from, $this->to, 'all'),
            'rekap_layanan_'.Carbon::parse($this->from)->format('Y-m-d').'_sd_'.Carbon::parse($this->to)->format('Y-m-d').'.xlsx',
        );
    }

    public function exportDescription(): string
    {
        return sprintf(
            'Menampilkan seluruh layanan aktif pada rentang %s s.d. %s.',
            Carbon::parse($this->from)->translatedFormat('d F Y'),
            Carbon::parse($this->to)->translatedFormat('d F Y'),
        );
    }

    public function previewPdfUrl(): string
    {
        return route('preview.rekap-layanan-pdf', [
            'from' => $this->from,
            'to' => $this->to,
        ]);
    }

    public function getViewData(): array
    {
        $monitoring = app(MonitoringRealtimeService::class);
        $isRealtime = $this->activeTab === 'realtime';
        $isReport = $this->activeTab === 'report';

        return [
            'summary' => $isRealtime ? $monitoring->getSummary() : null,
            'services' => $isRealtime && filled($this->zoneFilter) ? $monitoring->getServices($this->zoneFilter, $this->search) : collect(),
            'zoneOptions' => $monitoring->getZoneOptions(),
            'queueAnalysis' => $isRealtime ? $this->getQueueAnalysis() : null,
            'rekapan' => $isReport ? $this->getRekapJumlahPemohon() : collect(),
        ];
    }

    /**
     * Ringkas pola kedatangan pemohon dalam interval 30 menit. Tiket yang
     * masih tahap cetak atau telah batal tidak dihitung karena belum menjadi
     * nomor antrean yang benar-benar diterbitkan.
     *
     * @return array{points: array<int, array{label: string, total: int}>, total: int, peak_label: ?string, peak_total: int, period_label: string}
     */
    protected function getQueueAnalysis(): array
    {
        $now = now('Asia/Jakarta');
        [$from, $to, $periodLabel] = match ($this->analysisRange) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Hari ini'],
            '7' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), '7 hari terakhir'],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), '30 hari terakhir'],
        };

        $start = $now->copy()->setTime(7, 30);
        $end = $now->copy()->setTime(15, 0);
        $slots = collect();

        for ($slot = $start->copy(); $slot->lessThanOrEqualTo($end); $slot->addMinutes(30)) {
            $slots->put($slot->format('H:i'), 0);
        }

        $zoneName = $this->analysisZoneFilter !== 'all'
            ? (string) config("tv.zones.{$this->analysisZoneFilter}.name", "ZONA {$this->analysisZoneFilter}")
            : null;

        $counts = DB::table('queues as q')
            ->join('services as s', 's.id', '=', 'q.service_id')
            ->join('instansis as i', 'i.instansi_id', '=', 's.instansi_id')
            ->whereBetween('q.created_at', [$from, $to])
            ->whereNotIn('q.status', [Queue::STATUS_PRINTING, Queue::STATUS_CANCELED])
            ->when($zoneName, fn ($query) => $query->where('i.zone', $zoneName))
            ->whereRaw("TIME(q.created_at) >= '07:30:00'")
            ->whereRaw("TIME(q.created_at) <= '15:00:00'")
            ->selectRaw('HOUR(q.created_at) as hour_slot, FLOOR(MINUTE(q.created_at) / 30) as half_hour, COUNT(*) as total')
            ->groupBy('hour_slot', 'half_hour')
            ->get();

        foreach ($counts as $count) {
            $label = sprintf('%02d:%02d', (int) $count->hour_slot, (int) $count->half_hour * 30);
            if ($slots->has($label)) {
                $slots->put($label, (int) $count->total);
            }
        }

        $peakTotal = (int) $slots->max();
        $peakLabel = $peakTotal > 0 ? (string) $slots->search($peakTotal, true) : null;

        return [
            'points' => $slots->map(fn (int $total, string $label): array => ['label' => $label, 'total' => $total])->values()->all(),
            'total' => (int) $slots->sum(),
            'peak_label' => $peakLabel,
            'peak_total' => $peakTotal,
            'period_label' => $periodLabel,
        ];
    }

    /** @return Collection<int, Instansi> */
    protected function getRekapJumlahPemohon(): Collection
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();

        return Instansi::query()
            ->where('is_active', true)
            ->where('is_archived', false)
            ->whereHas('services', fn ($query) => $query->where('is_active', true)->where('is_archived', false))
            ->with(['services' => function ($query) use ($from, $to): void {
                $query->where('is_active', true)->where('is_archived', false)->withCount([
                    'queues as total_pemohon' => fn ($queue) => $queue->whereBetween('created_at', [$from, $to]),
                ])->orderBy('prefix');
            }])
            ->orderBy('nama_instansi')
            ->get()
            ->map(function (Instansi $instansi) {
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
