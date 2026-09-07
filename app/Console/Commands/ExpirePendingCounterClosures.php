<?php

namespace App\Console\Commands;

use App\Models\CounterClosureRequest;
use App\Services\CounterClosureService;
use App\Services\WorkingCalendarService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpirePendingCounterClosures extends Command
{
    protected $signature = 'counters:expire-pending-closures {--dry-run : Tampilkan pengajuan yang akan dikedaluwarsakan tanpa mengubah data}';

    protected $description = 'Mengakhiri pengajuan tutup loket yang belum ditinjau pada pukul 07.30 hari operasional berikutnya.';

    public function handle(WorkingCalendarService $calendar, CounterClosureService $closureService): int
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->copy()->startOfDay();
        $expiryTime = $today->copy()->setTime(7, 30);

        if ($now->lessThan($expiryTime)) {
            return self::SUCCESS;
        }

        $requests = CounterClosureRequest::query()
            ->with('counter.instansi')
            ->where('status', CounterClosureRequest::STATUS_PENDING)
            ->where('requested_at', '<', $today)
            ->orderBy('requested_at')
            ->get();

        $expired = 0;

        foreach ($requests as $request) {
            $instansi = $request->counter?->instansi;

            if (! $instansi || ! $calendar->isWorkingDay($instansi, $today)) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("Akan dikedaluwarsakan: {$request->counter->code_loket}.");
                $expired++;

                continue;
            }

            if ($closureService->expirePending($request)) {
                $this->info("Kedaluwarsa otomatis: {$request->counter->code_loket}.");
                $expired++;
            }
        }

        $this->info("Selesai. {$expired} pengajuan dikedaluwarsakan.");

        return self::SUCCESS;
    }
}
