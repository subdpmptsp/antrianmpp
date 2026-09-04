<?php

namespace App\Filament\Pages;

use App\Models\Counter;
use App\Models\CounterQueueOverrideLog;
use App\Models\CounterQueueScheduleOverride;
use App\Models\Holiday;
use App\Models\QueueOperatingSetting;
use App\Models\Service;
use App\Models\ServiceQueueDateOverride;
use App\Services\ServiceQueueAvailabilityService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class QueueOperatingSchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Jadwal Operasional Antrean';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.queue-operating-schedule';

    public string $activeTab = 'mingguan';
    /** @var array<int, array<string, mixed>> */
    public array $weeklySchedule = [];
    public int $cutoffMinutes = 30;
    public ?int $defaultDailyQuota = null;
    public ?int $selectedCounterId = null;
    public string $overrideMode = 'default';
    /** @var array<int, array<string, mixed>> */
    public array $overrideSchedule = [];
    public ?string $overrideReason = null;
    public ?string $overrideValidUntil = null;
    public ?string $holidayDate = null;
    public ?string $holidayName = null;
    public string $holidayType = 'national';
    public ?string $holidayNotes = null;
    public ?string $serviceClosureDate = null;
    public ?int $serviceClosureServiceId = null;
    public ?string $serviceClosureReason = null;
    public ?int $simulationCounterId = null;
    public ?string $simulationDate = null;
    public string $simulationTime = '08:00';
    /** @var array{available: bool, message: string, code: string}|null */
    public ?array $simulationResult = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public function mount(): void
    {
        $this->loadGlobalSettings();
        $firstCounter = $this->counters->first();
        $this->selectedCounterId = $firstCounter?->id;
        $this->simulationCounterId = $firstCounter?->id;
        $this->simulationDate = now('Asia/Jakarta')->toDateString();
        $this->holidayDate = now('Asia/Jakarta')->toDateString();
        $this->serviceClosureDate = now('Asia/Jakarta')->toDateString();
        $this->loadCounterOverride();
    }

    public function selectTab(string $tab): void
    {
        if (in_array($tab, ['mingguan', 'cutoff', 'loket', 'tanggal_khusus', 'log'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function updatedSelectedCounterId(): void
    {
        $this->loadCounterOverride();
    }

    public function saveGlobal(): void
    {
        foreach ($this->weeklySchedule as $index => $day) {
            $this->weeklySchedule[$index]['day'] = (int) ($day['day'] ?? $index + 1);
            $this->weeklySchedule[$index]['is_open'] = (bool) ($day['is_open'] ?? false);
            if ($this->weeklySchedule[$index]['is_open']) {
                $open = substr((string) ($day['opens_at'] ?? ''), 0, 5);
                $close = substr((string) ($day['closes_at'] ?? ''), 0, 5);
                if (! preg_match('/^\d{2}:\d{2}$/', $open) || ! preg_match('/^\d{2}:\d{2}$/', $close) || $open >= $close) {
                    Notification::make()->title('Jam '.$this->dayName((int) $this->weeklySchedule[$index]['day']).' belum valid.')->danger()->send();
                    return;
                }
                $this->weeklySchedule[$index]['opens_at'] = $open;
                $this->weeklySchedule[$index]['closes_at'] = $close;
            } else {
                $this->weeklySchedule[$index]['opens_at'] = null;
                $this->weeklySchedule[$index]['closes_at'] = null;
            }
        }

        QueueOperatingSetting::query()->firstOrCreate([], ['weekly_schedule' => $this->defaultSchedule()])
            ->update([
                'weekly_schedule' => array_values($this->weeklySchedule),
                'cutoff_minutes' => max(0, $this->cutoffMinutes),
                'default_daily_quota' => $this->defaultDailyQuota ?: null,
            ]);

        Notification::make()->title('Jadwal global tersimpan.')->success()->send();
    }

    public function saveCounterOverride(): void
    {
        $this->validate([
            'selectedCounterId' => ['required', 'exists:counters,id'],
            'overrideMode' => ['required', 'in:default,custom,force_open,force_closed'],
            'overrideReason' => [$this->overrideMode === 'default' ? 'nullable' : 'required', 'max:1000'],
            'overrideValidUntil' => ['nullable', 'date'],
        ]);

        $counter = Counter::withoutGlobalScopes()->findOrFail($this->selectedCounterId);
        $previous = $counter->queueScheduleOverride;
        $schedule = $this->overrideMode === 'custom' ? $this->normaliseSchedule($this->overrideSchedule) : null;

        if ($this->overrideMode === 'default') {
            $previous?->delete();
        } else {
            CounterQueueScheduleOverride::query()->updateOrCreate(
                ['counter_id' => $counter->id],
                [
                    'mode' => $this->overrideMode,
                    'weekly_schedule' => $schedule,
                    'reason' => $this->overrideReason,
                    'valid_until' => $this->overrideValidUntil,
                    'updated_by' => auth()->id(),
                ],
            );
        }

        CounterQueueOverrideLog::create([
            'counter_id' => $counter->id,
            'user_id' => auth()->id(),
            'action' => $this->overrideMode,
            'reason' => $this->overrideReason,
            'valid_until' => $this->overrideValidUntil,
            'snapshot' => ['previous' => $previous?->only(['mode', 'weekly_schedule', 'reason', 'valid_until'])],
        ]);

        $this->loadCounterOverride();
        Notification::make()->title('Pengaturan loket tersimpan dan dicatat pada log.')->success()->send();
    }

    public function addHoliday(): void
    {
        $this->validate([
            'holidayDate' => ['required', 'date'], 'holidayName' => ['required', 'max:255'],
            'holidayType' => ['required', 'in:national,collective,local'], 'holidayNotes' => ['nullable', 'max:1000'],
        ]);
        Holiday::query()->updateOrCreate(['date' => $this->holidayDate], [
            'name' => $this->holidayName, 'type' => $this->holidayType, 'notes' => $this->holidayNotes,
        ]);
        $this->holidayName = $this->holidayNotes = null;
        Notification::make()->title('Hari libur/penutupan global tersimpan.')->success()->send();
    }

    public function addServiceClosure(): void
    {
        $this->validate([
            'serviceClosureDate' => ['required', 'date'], 'serviceClosureServiceId' => ['required', 'exists:services,id'],
            'serviceClosureReason' => ['required', 'max:255'],
        ]);
        ServiceQueueDateOverride::query()->updateOrCreate([
            'service_id' => $this->serviceClosureServiceId, 'date' => $this->serviceClosureDate,
        ], ['is_closed' => true, 'reason' => $this->serviceClosureReason, 'created_by' => auth()->id()]);
        $this->serviceClosureReason = null;
        Notification::make()->title('Tanggal khusus layanan tersimpan.')->success()->send();
    }

    public function deleteHoliday(int $id): void
    {
        Holiday::query()->findOrFail($id)->delete();
        Notification::make()->title('Hari libur dihapus.')->success()->send();
    }

    public function deleteServiceClosure(int $id): void
    {
        ServiceQueueDateOverride::query()->findOrFail($id)->delete();
        Notification::make()->title('Tanggal khusus layanan dihapus.')->success()->send();
    }

    public function simulate(): void
    {
        $counter = Counter::withoutGlobalScopes()->with('service')->find($this->simulationCounterId);
        if (! $counter?->service || ! $this->simulationDate) {
            Notification::make()->title('Pilih loket yang memiliki layanan untuk simulasi.')->warning()->send();
            return;
        }
        $at = Carbon::createFromFormat('Y-m-d H:i', $this->simulationDate.' '.substr($this->simulationTime, 0, 5), 'Asia/Jakarta');
        $this->simulationResult = app(ServiceQueueAvailabilityService::class)->evaluate($counter->service, $at);
    }

    public function getCountersProperty()
    {
        return Counter::withoutGlobalScopes()->with(['instansi', 'service'])->where('is_archived', false)
            ->orderBy('code_loket')->get()->filter(fn (Counter $counter) => $counter->service)->values();
    }

    public function getServicesProperty()
    {
        return Service::query()->with('instansi')->where('is_archived', false)->orderBy('name')->get();
    }

    public function getHolidaysProperty()
    {
        return Holiday::query()->whereDate('date', '>=', now()->subMonth())->orderBy('date')->limit(20)->get();
    }

    public function getServiceClosuresProperty()
    {
        return ServiceQueueDateOverride::query()->with('service.instansi')->whereDate('date', '>=', now()->subMonth())->orderBy('date')->limit(20)->get();
    }

    public function getOverrideLogsProperty()
    {
        return CounterQueueOverrideLog::query()->with('counter')->latest()->limit(20)->get();
    }

    private function loadGlobalSettings(): void
    {
        $setting = QueueOperatingSetting::query()->firstOrCreate([], [
            'weekly_schedule' => $this->defaultSchedule(), 'cutoff_minutes' => 30,
        ]);
        $this->weeklySchedule = $this->normaliseSchedule((array) $setting->weekly_schedule);
        $this->cutoffMinutes = (int) $setting->cutoff_minutes;
        $this->defaultDailyQuota = $setting->default_daily_quota;
    }

    private function loadCounterOverride(): void
    {
        $counter = Counter::withoutGlobalScopes()->with('queueScheduleOverride')->find($this->selectedCounterId);
        $override = $counter?->queueScheduleOverride;
        $this->overrideMode = $override?->mode ?? 'default';
        $this->overrideSchedule = $this->normaliseSchedule((array) ($override?->weekly_schedule ?: $this->weeklySchedule));
        $this->overrideReason = $override?->reason;
        $this->overrideValidUntil = $override?->valid_until?->format('Y-m-d\\TH:i');
    }

    /** @param array<int, mixed> $schedule @return array<int, array<string, mixed>> */
    private function normaliseSchedule(array $schedule): array
    {
        $byDay = collect($schedule)->mapWithKeys(fn ($item) => [(int) ((array) $item)['day'] => (array) $item]);
        return collect(range(1, 7))->map(function (int $day) use ($byDay): array {
            $item = $byDay->get($day, []);
            return ['day' => $day, 'is_open' => (bool) ($item['is_open'] ?? false), 'opens_at' => $item['opens_at'] ?? '08:00', 'closes_at' => $item['closes_at'] ?? '16:00'];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function defaultSchedule(): array
    {
        return [
            ['day' => 1, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00'],
            ['day' => 2, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00'],
            ['day' => 3, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00'],
            ['day' => 4, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00'],
            ['day' => 5, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:30'],
            ['day' => 6, 'is_open' => false, 'opens_at' => null, 'closes_at' => null],
            ['day' => 7, 'is_open' => false, 'opens_at' => null, 'closes_at' => null],
        ];
    }

    public function dayName(int $day): string
    {
        return [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'][$day] ?? '-';
    }
}
