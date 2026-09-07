<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\QueueService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily attendance reset at midnight
Schedule::command('attendance:reset-daily')
    ->dailyAt('00:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Membuka kembali loket/layanan yang dijadwalkan selesai istirahat, sekaligus
// menangani pembukaan otomatis pada hari operasional berikutnya.
Schedule::command('counters:auto-reopen')
    ->everyMinute()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Pengajuan tutup loket yang belum ditinjau tidak boleh menggantung sampai
// menghambat petugas di hari operasional berikutnya.
Schedule::command('counters:expire-pending-closures')
    ->everyMinute()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

Schedule::command('audio:cleanup-generated --days=7')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

Schedule::call(fn () => app(QueueService::class)->expireStalePrintReservations())
    ->name('expire-stale-kiosk-print-reservations')
    ->everyMinute()
    ->withoutOverlapping();
