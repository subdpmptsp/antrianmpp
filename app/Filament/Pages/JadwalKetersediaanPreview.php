<?php

namespace App\Filament\Pages;

use App\Models\CounterQueueScheduleOverride;
use App\Models\Holiday;
use App\Models\QueueOperatingSetting;
use Filament\Pages\Page;

class JadwalKetersediaanPreview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Jadwal & Ketersediaan';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $slug = 'jadwal-ketersediaan-preview';

    protected static ?string $title = 'Pratinjau Jadwal & Ketersediaan Antrean';

    protected static string $view = 'filament.pages.jadwal-ketersediaan-preview';

    public string $activeSection = 'ringkasan';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function selectSection(string $section): void
    {
        if (in_array($section, ['ringkasan', 'mingguan', 'tanggal', 'loket', 'lanjutan', 'log'], true)) {
            $this->activeSection = $section;
        }
    }

    /** @return array<int, array{day: int, is_open: bool, opens_at: ?string, closes_at: ?string}> */
    public function getWeeklyScheduleProperty(): array
    {
        $schedule = (array) (QueueOperatingSetting::query()->value('weekly_schedule') ?: []);
        $byDay = collect($schedule)->mapWithKeys(function (mixed $item): array {
            $row = (array) $item;

            return [(int) ($row['day'] ?? 0) => $row];
        });

        return collect(range(1, 7))->map(function (int $day) use ($byDay): array {
            $row = (array) $byDay->get($day, []);

            return [
                'day' => $day,
                'is_open' => (bool) ($row['is_open'] ?? false),
                'opens_at' => isset($row['opens_at']) ? substr((string) $row['opens_at'], 0, 5) : null,
                'closes_at' => isset($row['closes_at']) ? substr((string) $row['closes_at'], 0, 5) : null,
            ];
        })->all();
    }

    public function getTodayScheduleProperty(): array
    {
        $day = (int) now('Asia/Jakarta')->dayOfWeekIso;

        return collect($this->weeklySchedule)->firstWhere('day', $day) ?? [
            'day' => $day,
            'is_open' => false,
            'opens_at' => null,
            'closes_at' => null,
        ];
    }

    public function getUpcomingHolidaysProperty()
    {
        return Holiday::query()
            ->whereDate('date', '>=', now('Asia/Jakarta')->toDateString())
            ->orderBy('date')
            ->limit(5)
            ->get();
    }

    public function getCustomScheduleCountProperty(): int
    {
        return CounterQueueScheduleOverride::query()->count();
    }

    public function getCutoffMinutesProperty(): int
    {
        return (int) (QueueOperatingSetting::query()->value('cutoff_minutes') ?? 0);
    }

    public function dayName(int $day): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$day] ?? '-';
    }
}
