<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Instansi;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AttendanceReportService
{
    public function __construct(private readonly WorkingCalendarService $calendar) {}

    /**
     * @return Collection<int, User>
     */
    public function activeOperators(): Collection
    {
        return User::query()
            ->where('role', User::ROLE_OPERATOR)
            ->where('is_active', true)
            ->with(['service.instansi', 'counter.instansi'])
            ->orderBy('name')
            ->get();
    }

    public function resolveInstansi(User $user): ?Instansi
    {
        return $user->service?->instansi ?? $user->counter?->instansi;
    }

    /**
     * @return array<string, mixed>
     */
    public function todayOverview(Carbon $date, string $search = '', ?int $instansiId = null, ?string $zoneId = null): array
    {
        $operators = $this->activeOperators();
        $attendances = Attendance::query()
            ->whereDate('date', $date)
            ->get()
            ->keyBy('user_id');

        $rows = $operators->map(function (User $operator) use ($attendances, $date): array {
            $instansi = $this->resolveInstansi($operator);
            $attendance = $attendances->get($operator->id);
            $isWorkingDay = $instansi ? $this->calendar->isWorkingDay($instansi, $date) : true;

            $status = match (true) {
                $attendance !== null => 'present',
                $instansi === null => 'unassigned',
                ! $isWorkingDay => 'off',
                default => 'absent',
            };

            $zoneId = collect(config('tv.zones', []))
                ->search(fn (array $zone): bool => ($zone['name'] ?? null) === $instansi?->zone);

            return [
                'user_id' => $operator->id,
                'name' => $operator->name,
                'instansi_id' => $instansi?->instansi_id,
                'instansi' => $instansi?->nama_instansi ?? 'Instansi belum ditentukan',
                'status' => $status,
                'check_in' => $attendance?->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
                'zone_id' => $zoneId === false ? null : (string) $zoneId,
            ];
        });

        $zoneRows = filled($zoneId)
            ? $rows->where('zone_id', (string) $zoneId)->values()
            : $rows;
        $expectedRows = $zoneRows->whereIn('status', ['present', 'absent']);
        $expectedInstansiIds = $expectedRows->pluck('instansi_id')->filter()->unique();
        $representedInstansiIds = $expectedRows->where('status', 'present')->pluck('instansi_id')->filter()->unique();

        $filteredRows = $zoneRows
            ->when($instansiId, fn (Collection $items) => $items->where('instansi_id', $instansiId))
            ->when(trim($search) !== '', function (Collection $items) use ($search): Collection {
                $needle = mb_strtolower(trim($search));

                return $items->filter(fn (array $row): bool => str_contains(mb_strtolower($row['name']), $needle)
                    || str_contains(mb_strtolower($row['instansi']), $needle));
            });

        return [
            'date' => $date,
            'total_operators' => $expectedRows->count(),
            'present_operators' => $expectedRows->where('status', 'present')->count(),
            'absent_operators' => $expectedRows->where('status', 'absent')->count(),
            'represented_instansis' => $representedInstansiIds->count(),
            'unrepresented_instansis' => $expectedInstansiIds->diff($representedInstansiIds)->count(),
            'attendance_percentage' => $expectedRows->count() > 0
                ? (int) round(($expectedRows->where('status', 'present')->count() / $expectedRows->count()) * 100)
                : 0,
            'zone_label' => filled($zoneId)
                ? (string) config("tv.zones.{$zoneId}.name", "ZONA {$zoneId}")
                : 'Semua Zona',
            'zones' => collect(config('tv.zones', []))->map(function (array $zone, int|string $id) use ($rows): array {
                $zoneExpected = $rows->where('zone_id', (string) $id)->whereIn('status', ['present', 'absent']);
                $present = $zoneExpected->where('status', 'present')->count();
                $total = $zoneExpected->count();

                return [
                    'id' => (string) $id,
                    'name' => (string) ($zone['name'] ?? "ZONA {$id}"),
                    'total_operators' => $total,
                    'present_operators' => $present,
                    'absent_operators' => $zoneExpected->where('status', 'absent')->count(),
                    'attendance_percentage' => $total > 0 ? (int) round(($present / $total) * 100) : 0,
                ];
            })->values(),
            'absent' => $filteredRows->where('status', 'absent')->values(),
            'present' => $filteredRows->where('status', 'present')->values(),
            'off' => $filteredRows->where('status', 'off')->values(),
            'unassigned' => $filteredRows->where('status', 'unassigned')->values(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function historyRows(
        Carbon $from,
        Carbon $until,
        string $search = '',
        ?int $instansiId = null,
        string $status = 'all',
        ?string $zoneId = null,
        ?int $workDaysPerWeek = null,
    ): Collection {
        $operators = $this->activeOperators()
            ->when($instansiId, fn (Collection $items) => $items->filter(
                fn (User $user): bool => $this->resolveInstansi($user)?->instansi_id === $instansiId
            ))
            ->when(filled($zoneId), fn (Collection $items) => $items->filter(
                fn (User $user): bool => (string) $this->resolveInstansi($user)?->zone_number === (string) $zoneId
            ))
            ->when(in_array($workDaysPerWeek, [5, 6], true), fn (Collection $items) => $items->filter(
                fn (User $user): bool => (int) $this->resolveInstansi($user)?->work_days_per_week === $workDaysPerWeek
            ));

        $attendances = Attendance::query()
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->whereIn('user_id', $operators->pluck('id'))
            ->get()
            ->keyBy(fn (Attendance $attendance): string => $attendance->user_id.'|'.$attendance->date->toDateString());

        $rows = collect();

        foreach (CarbonPeriod::create($from, $until) as $date) {
            foreach ($operators as $operator) {
                $instansi = $this->resolveInstansi($operator);
                $attendance = $attendances->get($operator->id.'|'.$date->toDateString());
                $isWorkingDay = $instansi ? $this->calendar->isWorkingDay($instansi, $date) : true;

                if (! $isWorkingDay && ! $attendance) {
                    continue;
                }

                $rowStatus = match (true) {
                    $attendance !== null => 'present',
                    $instansi === null => 'unassigned',
                    default => 'absent',
                };
                $resolvedZoneId = $instansi?->zone_number;

                $rows->push([
                    'date' => $date->toDateString(),
                    'name' => $operator->name,
                    'instansi_id' => $instansi?->instansi_id,
                    'instansi' => $instansi?->nama_instansi ?? 'Instansi belum ditentukan',
                    'zone_id' => $resolvedZoneId !== null ? (string) $resolvedZoneId : null,
                    'zone' => $resolvedZoneId !== null
                        ? (string) config("tv.zones.{$resolvedZoneId}.name", $instansi?->zone ?? "ZONA {$resolvedZoneId}")
                        : ($instansi?->zone ?? '-'),
                    'status' => $rowStatus,
                    'check_in' => $attendance?->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
                ]);
            }
        }

        return $rows
            ->when($status !== 'all', fn (Collection $items) => $items->where('status', $status))
            ->when(trim($search) !== '', function (Collection $items) use ($search): Collection {
                $needle = mb_strtolower(trim($search));

                return $items->filter(fn (array $row): bool => str_contains(mb_strtolower($row['name']), $needle)
                    || str_contains(mb_strtolower($row['instansi']), $needle));
            })
            ->sortByDesc(fn (array $row): string => $row['date'].' '.$row['check_in'])
            ->values();
    }

    /**
     * Data visual untuk satu bulan: kehadiran harian, pembanding bulan lalu,
     * peringkat instansi yang tidak terwakili, dan pola absen petugas.
     *
     * @return array<string, mixed>
     */
    public function monthlyDashboard(
        int $year,
        int $month,
        ?int $instansiId = null,
        ?string $zoneId = null,
        ?int $workDaysPerWeek = null,
    ): array
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();
        $today = now()->startOfDay();
        $analysisEnd = $monthEnd->min($today);
        $previousStart = $monthStart->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth()->startOfDay();

        $operators = $this->activeOperators()
            ->filter(function (User $operator) use ($instansiId, $zoneId, $workDaysPerWeek): bool {
                $instansi = $this->resolveInstansi($operator);

                if (! $instansi) {
                    return false;
                }

                if ($instansiId && (int) $instansi->instansi_id !== $instansiId) {
                    return false;
                }

                if (in_array($workDaysPerWeek, [5, 6], true) && (int) $instansi->work_days_per_week !== $workDaysPerWeek) {
                    return false;
                }

                return ! filled($zoneId) || (string) $instansi->zone_number === (string) $zoneId;
            })
            ->values();

        $attendanceByOperatorAndDate = Attendance::query()
            ->whereIn('user_id', $operators->pluck('id'))
            ->whereBetween('date', [$previousStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn (Attendance $attendance): string => $attendance->user_id.'|'.$attendance->date->toDateString());

        $isExpected = function (User $operator, Carbon $date): bool {
            $instansi = $this->resolveInstansi($operator);

            return $instansi !== null
                && Carbon::parse($operator->created_at)->startOfDay()->lessThanOrEqualTo($date)
                && $this->calendar->isWorkingDay($instansi, $date);
        };
        $hasAttendance = fn (User $operator, Carbon $date): bool => $attendanceByOperatorAndDate
            ->has($operator->id.'|'.$date->toDateString());

        $days = collect();
        foreach (range(1, $monthEnd->day) as $day) {
            $date = $monthStart->copy()->day($day);
            $isFuture = $date->greaterThan($today);
            $expectedOperators = $isFuture
                ? collect()
                : $operators->filter(fn (User $operator): bool => $isExpected($operator, $date));
            $present = $expectedOperators->filter(fn (User $operator): bool => $hasAttendance($operator, $date))->count();

            $previousDate = $day <= $previousEnd->day ? $previousStart->copy()->day($day) : null;
            $previousPresent = $previousDate
                ? $operators
                    ->filter(fn (User $operator): bool => $isExpected($operator, $previousDate))
                    ->filter(fn (User $operator): bool => $hasAttendance($operator, $previousDate))
                    ->count()
                : null;

            $days->push([
                'day' => $day,
                'label' => sprintf('%02d', $day),
                'present' => $isFuture ? null : $present,
                'absent' => $isFuture ? null : max($expectedOperators->count() - $present, 0),
                'previous_present' => $previousPresent,
                'is_future' => $isFuture,
            ]);
        }

        $ranking = $operators
            ->groupBy(fn (User $operator): int => (int) $this->resolveInstansi($operator)->instansi_id)
            ->map(function (Collection $institutionOperators) use ($monthStart, $analysisEnd, $isExpected, $hasAttendance): array {
                /** @var User $firstOperator */
                $firstOperator = $institutionOperators->first();
                $instansi = $this->resolveInstansi($firstOperator);
                $workingDates = $monthStart->greaterThan($analysisEnd)
                    ? collect()
                    : collect(CarbonPeriod::create($monthStart, $analysisEnd))
                        ->filter(fn (Carbon $date): bool => $institutionOperators->contains(
                            fn (User $operator): bool => $isExpected($operator, $date)
                        ));
                $unrepresentedDays = $workingDates->filter(fn (Carbon $date): bool => ! $institutionOperators->contains(
                    fn (User $operator): bool => $isExpected($operator, $date) && $hasAttendance($operator, $date)
                ))->count();

                return [
                    'instansi_id' => $instansi->instansi_id,
                    'name' => $instansi->nama_instansi,
                    'absent_days' => $unrepresentedDays,
                    'working_days' => $workingDates->count(),
                    'percentage' => $workingDates->count() > 0
                        ? (int) round(($unrepresentedDays / $workingDates->count()) * 100)
                        : 0,
                ];
            })
            ->filter(fn (array $row): bool => $row['absent_days'] > 0)
            ->sortByDesc('absent_days')
            ->take(5)
            ->values();

        $repeatedAbsences = $operators
            ->map(function (User $operator) use ($monthStart, $analysisEnd, $isExpected, $hasAttendance): array {
                $instansi = $this->resolveInstansi($operator);
                $absentDates = $monthStart->greaterThan($analysisEnd)
                    ? collect()
                    : collect(CarbonPeriod::create($monthStart, $analysisEnd))
                        ->filter(fn (Carbon $date): bool => $isExpected($operator, $date) && ! $hasAttendance($operator, $date))
                        ->values();
                $weekdayPattern = $absentDates
                    ->groupBy(fn (Carbon $date): string => $date->translatedFormat('l'))
                    ->map->count()
                    ->sortDesc();
                $dominantWeekday = $weekdayPattern->keys()->first();
                $dominantCount = (int) ($weekdayPattern->first() ?? 0);

                return [
                    'user_id' => $operator->id,
                    'name' => $operator->name,
                    'instansi' => $instansi?->nama_instansi ?? '-',
                    'zone' => $instansi?->zone ?? '-',
                    'dates' => $absentDates->map(fn (Carbon $date): string => $date->format('d'))->all(),
                    'total' => $absentDates->count(),
                    'pattern' => $dominantCount >= 3 ? 'Sering hari '.$dominantWeekday : 'Berulang dalam bulan',
                ];
            })
            ->filter(fn (array $row): bool => $row['total'] >= 2)
            ->sortByDesc('total')
            ->take(10)
            ->values();

        return [
            'year' => $year,
            'month' => $month,
            'month_label' => $monthStart->translatedFormat('F Y'),
            'previous_month_label' => $previousStart->translatedFormat('F Y'),
            'days' => $days,
            'ranking' => $ranking,
            'repeated_absences' => $repeatedAbsences,
            'total_present' => $days->sum(fn (array $day): int => (int) ($day['present'] ?? 0)),
            'total_absent' => $days->sum(fn (array $day): int => (int) ($day['absent'] ?? 0)),
            'total_expected' => $days->sum(fn (array $day): int => (int) ($day['present'] ?? 0) + (int) ($day['absent'] ?? 0)),
            'has_attendance_data' => $days->contains(fn (array $day): bool => (int) ($day['present'] ?? 0) > 0),
            'work_pattern_label' => match ($workDaysPerWeek) {
                5 => '5 hari kerja (Senin–Jumat)',
                6 => '6 hari kerja (Senin–Sabtu)',
                default => 'sesuai jadwal kerja masing-masing instansi',
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function monthlyRecap(int $year): array
    {
        return Cache::remember('attendance:monthly-recap:'.$year, now()->addMinutes(5), function () use ($year): array {
            $operators = $this->activeOperators();
            $instansis = $operators
                ->map(fn (User $user) => $this->resolveInstansi($user))
                ->filter()
                ->unique('instansi_id')
                ->sortBy('nama_instansi')
                ->values();

            $attendanceDays = Attendance::query()
                ->selectRaw('instansi_id, date, COUNT(*) as total_present')
                ->whereNotNull('instansi_id')
                ->whereYear('date', $year)
                ->groupBy('instansi_id', 'date')
                ->get()
                ->groupBy('instansi_id');

            $today = now()->startOfDay();
            $data = $instansis->map(function (Instansi $instansi) use ($year, $attendanceDays, $today): array {
                $presentDates = $attendanceDays->get($instansi->instansi_id, collect())
                    ->pluck('date')
                    ->map(fn ($date) => Carbon::parse($date)->toDateString())
                    ->flip();
                $months = [];

                foreach (range(1, 12) as $month) {
                    $monthStart = Carbon::create($year, $month, 1)->startOfDay();

                    if ($monthStart->greaterThan($today)) {
                        $months[$month] = null;

                        continue;
                    }

                    $monthEnd = $monthStart->copy()->endOfMonth()->min($today);
                    $workingDates = $this->calendar->workingDates($instansi, $monthStart, $monthEnd);
                    $daysPresent = $workingDates->filter(
                        fn (Carbon $date): bool => $presentDates->has($date->toDateString())
                    )->count();
                    $totalDays = $workingDates->count();

                    $months[$month] = [
                        'percentage' => $totalDays > 0 ? (int) round(($daysPresent / $totalDays) * 100) : 0,
                        'days_present' => $daysPresent,
                        'total_days' => $totalDays,
                    ];
                }

                return [
                    'instansi_id' => $instansi->instansi_id,
                    'nama_instansi' => $instansi->nama_instansi,
                    'work_days_per_week' => $instansi->work_days_per_week,
                    'months' => $months,
                ];
            });

            return [
                'year' => $year,
                'months' => [
                    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
                    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
                ],
                'instansis' => $data,
            ];
        });
    }

    /**
     * @return array<int, string>
     */
    public function instansiOptions(): array
    {
        return $this->activeOperators()
            ->map(fn (User $user) => $this->resolveInstansi($user))
            ->filter()
            ->unique('instansi_id')
            ->sortBy('nama_instansi')
            ->pluck('nama_instansi', 'instansi_id')
            ->all();
    }
}
