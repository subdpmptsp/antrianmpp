<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Exports\AttendanceRangeExport;
use App\Exports\AttendanceYearlyRecapExport;
use App\Filament\Resources\AttendanceResource;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ListAttendances extends Page
{
    use WithPagination;

    protected static string $resource = AttendanceResource::class;

    protected static string $view = 'filament.resources.attendance-resource.pages.list-attendances';

    protected static ?string $title = 'Kehadiran Petugas';

    public string $activeSection = 'today';

    public string $currentDate = '';

    public string $lastUpdatedAt = '';

    public string $todaySearch = '';

    public string $todayInstansi = '';

    public string $todayZone = 'all';

    public string $historyFrom = '';

    public string $historyUntil = '';

    public string $historySearch = '';

    public string $historyInstansi = '';

    public string $historyStatus = 'all';

    public int $recapYear;

    public int $recapMonth;

    public string $recapZone = 'all';

    public string $recapInstansi = '';

    public string $recapWorkPattern = 'all';

    public function mount(): void
    {
        abort_unless(AttendanceResource::canViewAny(), 403);
        $today = now();
        $this->currentDate = $today->toDateString();
        $this->lastUpdatedAt = $today->format('H:i');
        $this->historyFrom = $today->copy()->startOfMonth()->toDateString();
        $this->historyUntil = $today->toDateString();
        $this->recapYear = $today->year;
        $this->recapMonth = $today->month;
    }

    public function setSection(string $section): void
    {
        if (in_array($section, ['today', 'history', 'monthly'], true)) {
            $this->activeSection = $section;
        }
    }

    public function updatedRecapYear(int $year): void
    {
        if ($year === now()->year && $this->recapMonth > now()->month) {
            $this->recapMonth = now()->month;
        }
    }

    public function refreshDashboard(): void
    {
        $now = now();
        $this->currentDate = $now->toDateString();
        $this->lastUpdatedAt = $now->format('H:i');
    }

    public function selectTodayZone(string $zone): void
    {
        $this->todayZone = $zone === 'all' || array_key_exists((int) $zone, config('tv.zones', []))
            ? $zone
            : 'all';
    }

    public function applyHistoryFilters(): void
    {
        $this->validateHistoryRange(92);
        $this->resetPage('attendanceHistoryPage');
    }

    public function exportHistory()
    {
        $this->validateHistoryRange(366);

        return Excel::download(
            new AttendanceRangeExport(
                $this->historyFrom,
                $this->historyUntil,
                $this->historySearch,
                $this->historyInstansi !== '' ? (int) $this->historyInstansi : null,
                $this->historyStatus,
            ),
            "kehadiran_petugas_{$this->historyFrom}_{$this->historyUntil}.xlsx",
        );
    }

    public function exportYearlyRecap()
    {
        $this->validate(['recapYear' => ['required', 'integer', 'min:2020', 'max:'.now()->year]]);

        return Excel::download(
            new AttendanceYearlyRecapExport($this->recapYear),
            "rekap_kehadiran_{$this->recapYear}.xlsx",
        );
    }

    public function exportMonthlyRecap()
    {
        $this->validateMonthlyFilters();
        $from = Carbon::create($this->recapYear, $this->recapMonth, 1)->startOfMonth();
        $until = $from->copy()->endOfMonth()->min(now()->startOfDay());

        if ($from->greaterThan($until)) {
            throw ValidationException::withMessages([
                'recapMonth' => 'Bulan yang dipilih belum berjalan.',
            ]);
        }

        return Excel::download(
            new AttendanceRangeExport(
                $from->toDateString(),
                $until->toDateString(),
                '',
                $this->recapInstansi !== '' ? (int) $this->recapInstansi : null,
                'all',
                $this->recapZone !== 'all' ? $this->recapZone : null,
                $this->recapWorkPattern !== 'all' ? (int) $this->recapWorkPattern : null,
            ),
            sprintf('rekap_kehadiran_%04d_%02d.xlsx', $this->recapYear, $this->recapMonth),
        );
    }

    /** @return array<string, mixed> */
    public function getTodayOverview(): array
    {
        return app(AttendanceReportService::class)->todayOverview(
            Carbon::parse($this->currentDate),
            $this->todaySearch,
            $this->todayInstansi !== '' ? (int) $this->todayInstansi : null,
            $this->todayZone !== 'all' ? $this->todayZone : null,
        );
    }

    public function getHistoryPaginator(): LengthAwarePaginator
    {
        $from = Carbon::parse($this->historyFrom ?: now()->startOfMonth());
        $until = Carbon::parse($this->historyUntil ?: now());
        $pageName = 'attendanceHistoryPage';
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $perPage = 25;
        $rows = app(AttendanceReportService::class)->historyRows(
            $from,
            $until,
            $this->historySearch,
            $this->historyInstansi !== '' ? (int) $this->historyInstansi : null,
            $this->historyStatus,
        );

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => $pageName],
        );
    }

    /** @return array<string, mixed> */
    public function getMonthlyRecap(): array
    {
        return app(AttendanceReportService::class)->monthlyDashboard(
            $this->recapYear,
            $this->recapMonth,
            $this->recapInstansi !== '' ? (int) $this->recapInstansi : null,
            $this->recapZone !== 'all' ? $this->recapZone : null,
            $this->recapWorkPattern !== 'all' ? (int) $this->recapWorkPattern : null,
        );
    }

    /** @return array<string, mixed> */
    public function getYearlyRecap(): array
    {
        return app(AttendanceReportService::class)->monthlyRecap($this->recapYear);
    }

    /** @return array<string, string> */
    public function getZoneOptions(): array
    {
        return collect(config('tv.zones', []))
            ->mapWithKeys(fn (array $zone, int|string $id): array => [(string) $id => (string) ($zone['name'] ?? "ZONA {$id}")])
            ->all();
    }

    /** @return array<int, string> */
    public function getInstansiOptions(): array
    {
        return app(AttendanceReportService::class)->instansiOptions();
    }

    private function validateHistoryRange(int $maximumDays): void
    {
        $this->validate([
            'historyFrom' => ['required', 'date'],
            'historyUntil' => ['required', 'date', 'after_or_equal:historyFrom'],
            'historyStatus' => ['required', 'in:all,present,absent,unassigned'],
        ]);

        if (Carbon::parse($this->historyFrom)->diffInDays(Carbon::parse($this->historyUntil)) + 1 > $maximumDays) {
            throw ValidationException::withMessages([
                'historyUntil' => "Rentang maksimal {$maximumDays} hari.",
            ]);
        }
    }

    private function validateMonthlyFilters(): void
    {
        $this->validate([
            'recapYear' => ['required', 'integer', 'min:2020', 'max:'.now()->year],
            'recapMonth' => ['required', 'integer', 'between:1,12'],
            'recapZone' => ['required', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value !== 'all' && ! array_key_exists((int) $value, config('tv.zones', []))) {
                    $fail('Zona yang dipilih tidak valid.');
                }
            }],
            'recapInstansi' => ['nullable', 'integer', 'exists:instansis,instansi_id'],
            'recapWorkPattern' => ['required', 'in:all,5,6'],
        ]);
    }
}
