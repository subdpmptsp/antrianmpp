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

    protected $description = 'Membuka kembali loket yang disetujui tutup otomatis pada hari operasional berikutnya.';

    public function handle(WorkingCalendarService $calendar, CounterClosureService $closureService): int
    {
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $requests = CounterClosureRequest::query()
            ->with('counter.instansi')
            ->where('status', CounterClosureRequest::STATUS_APPROVED)
            ->where('auto_reopen', true)
            ->where('reviewed_at', '<', $today)
            ->orderBy('reviewed_at')
            ->get();

        $reopened = 0;

        foreach ($requests as $request) {
            $counter = $request->counter;

            if (! $counter?->instansi || ! $calendar->isWorkingDay($counter->instansi, $today)) {
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

        $this->info("Selesai. {$reopened} loket diproses.");

        return self::SUCCESS;
    }
}
