<?php

namespace App\Console\Commands;

use App\Models\CounterClosureRequest;
use App\Services\CounterClosureService;
use App\Services\WorkingCalendarService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoReopenCounters extends Command
{
    protected $signature = 'counters:auto-reopen {--dry-run : Tampilkan loket yang akan dibuka tanpa mengubah data}';

    protected $description = 'Membuka kembali loket yang dijadwalkan aktif kembali atau dibuka otomatis pada hari operasional berikutnya.';

    public function handle(WorkingCalendarService $calendar, CounterClosureService $closureService): int
    {
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $now = Carbon::now('Asia/Jakarta');
        $requests = CounterClosureRequest::query()
            ->with('counter.instansi')
            ->where('status', CounterClosureRequest::STATUS_APPROVED)
            ->where(function ($query) use ($today, $now): void {
                $query
                    ->where(function ($autoReopen) use ($today): void {
                        $autoReopen->where('auto_reopen', true)
                            ->where('reviewed_at', '<', $today);
                    })
                    ->orWhere(function ($scheduled) use ($now): void {
                        $scheduled->whereNotNull('scheduled_reopen_at')
                            ->where('scheduled_reopen_at', '<=', $now);
                    });
            })
            ->orderBy('reviewed_at')
            ->get();

        $reopened = 0;

        foreach ($requests as $request) {
            $counter = $request->counter;

            $isTimedReopen = $request->scheduled_reopen_at !== null;
            if (! $counter?->instansi || (! $isTimedReopen && ! $calendar->isWorkingDay($counter->instansi, $today))) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("Akan membuka {$counter->code_loket} ({$counter->name}).");
                $reopened++;

                continue;
            }

            if ($closureService->reopenAutomatically($request)) {
                $this->info("Dibuka otomatis: {$counter->code_loket} ({$counter->name}).");
                $reopened++;
            }
        }

        $clearedPauses = $this->option('dry-run') ? 0 : $closureService->clearExpiredServiceQueuePauses();

        $this->info("Selesai. {$reopened} loket diproses, {$clearedPauses} penutupan layanan berakhir.");

        return self::SUCCESS;
    }
}
