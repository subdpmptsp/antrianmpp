<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Instansi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class WorkingCalendarService
{
    /** @var array<string, true> */
    private array $holidayDates = [];

    /** @var array<int, true> */
    private array $loadedYears = [];

    public function isWorkingDay(Instansi $instansi, Carbon $date): bool
    {
        $workDays = in_array($instansi->work_days_per_week, [5, 6], true)
            ? $instansi->work_days_per_week
            : 5;

        if ($date->isoWeekday() > $workDays) {
            return false;
        }

        $this->loadHolidays($date, $date);

        return ! isset($this->holidayDates[$date->toDateString()]);
    }

    /**
     * @return Collection<int, Carbon>
     */
    public function workingDates(Instansi $instansi, Carbon $from, Carbon $until): Collection
    {
        if ($from->greaterThan($until)) {
            return collect();
        }

        $this->loadHolidays($from, $until);
        $workDays = in_array($instansi->work_days_per_week, [5, 6], true)
            ? $instansi->work_days_per_week
            : 5;

        return collect(CarbonPeriod::create($from->copy()->startOfDay(), $until->copy()->startOfDay()))
            ->filter(fn (Carbon $date): bool => $date->isoWeekday() <= $workDays
                && ! isset($this->holidayDates[$date->toDateString()]))
            ->values();
    }

    private function loadHolidays(Carbon $from, Carbon $until): void
    {
        $years = range($from->year, $until->year);
        $missingYears = array_values(array_filter($years, fn (int $year): bool => ! isset($this->loadedYears[$year])));

        if ($missingYears === []) {
            return;
        }

        Holiday::query()
            ->whereBetween('date', [
                Carbon::create(min($missingYears), 1, 1)->toDateString(),
                Carbon::create(max($missingYears), 12, 31)->toDateString(),
            ])
            ->pluck('date')
            ->each(function ($date): void {
                $key = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
                $this->holidayDates[$key] = true;
            });

        foreach ($missingYears as $year) {
            $this->loadedYears[$year] = true;
        }
    }
}
