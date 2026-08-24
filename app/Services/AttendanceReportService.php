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
    public function todayOverview(Carbon $date, string $search = '', ?int $instansiId = null): array
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

            return [
                'user_id' => $operator->id,
                'name' => $operator->name,
                'instansi_id' => $instansi?->instansi_id,
                'instansi' => $instansi?->nama_instansi ?? 'Instansi belum ditentukan',
                'status' => $status,
                'check_in' => $attendance?->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
            ];
        });

        $expectedRows = $rows->whereIn('status', ['present', 'absent']);
        $expectedInstansiIds = $expectedRows->pluck('instansi_id')->filter()->unique();
        $representedInstansiIds = $expectedRows->where('status', 'present')->pluck('instansi_id')->filter()->unique();

        $filteredRows = $rows
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
    ): Collection {
        $operators = $this->activeOperators()
            ->when($instansiId, fn (Collection $items) => $items->filter(
                fn (User $user): bool => $this->resolveInstansi($user)?->instansi_id === $instansiId
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

                $rows->push([
                    'date' => $date->toDateString(),
                    'name' => $operator->name,
                    'instansi_id' => $instansi?->instansi_id,
                    'instansi' => $instansi?->nama_instansi ?? 'Instansi belum ditentukan',
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
