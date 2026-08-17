<?php

use App\Exports\MonitoringRealtimeExport;
use App\Exports\RekapLayananExport;
use App\Filament\Pages\AntrianSkckBerjalanPage;
use App\Filament\Pages\AntrianSkckPage;
use App\Filament\Pages\QueueStatus;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PublicQueueKioskController;
use App\Http\Controllers\QueuePrintController;
use App\Http\Controllers\StrukController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TvDisplayController;
use App\Models\Counter;
use App\Models\Service;
use App\Services\TvZoneResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('queue-status', QueueStatus::class)->name('queue.status');

Route::middleware(['auth', 'admin'])
    ->get('/exports/rekap-layanan', [ExportController::class, 'rekapLayanan'])
    ->name('export.rekap-layanan');

Route::middleware(['auth', 'admin'])->get('/exports/monitoring-realtime', function (Request $request) {
    return Excel::download(
        new MonitoringRealtimeExport(
            $request->string('instansi_id')->toString() ?: null,
            $request->string('search')->toString() ?: null,
        ),
        'monitoring-realtime-'.now()->format('Y-m-d-His').'.xlsx',
    );
})->name('export.monitoring-realtime');

Route::middleware(['auth', 'admin'])->get('/export/rekap-jumlah-pemohon', function (Request $request) {
    $from = $request->query('from', now()->toDateString());
    $to = $request->query('to', now()->toDateString());

    return Excel::download(new RekapLayananExport($from, $to), 'rekap_jumlah_pemohon.xlsx');
})->name('export.rekap-jumlah-pemohon');

Route::get('/antrian-skck-mpp', AntrianSkckPage::class);
Route::get('/antrian-skck-mpp/terdaftar', AntrianSkckBerjalanPage::class);
Route::get('/antrian-skck-mpp/{id}', [ExportController::class, 'cetakSkck'])
    ->middleware('signed')
    ->name('skck.ticket');

Route::get('/tampilan-tv', function () {
    return redirect()->route('tv.index');
});

// PDF tiket antrian
Route::get('/tickets/{queue}/pdf', [TicketController::class, 'queuePdf'])
    ->middleware('signed')
    ->name('tickets.pdf');

// PDF struk antrian
Route::get('/struk/generate', [StrukController::class, 'generateStruk'])
    ->middleware('signed')
    ->name('struk.generate');

Route::get('/tickets/{queue}/print', [StrukController::class, 'printTicket'])
    ->middleware('signed:relative')
    ->name('tickets.print');
Route::post('/tickets/{queue}/print/confirm', [QueuePrintController::class, 'confirm'])
    ->middleware(['signed:relative', 'throttle:30,1'])
    ->name('tickets.print.confirm');
Route::post('/tickets/{queue}/print/fail', [QueuePrintController::class, 'fail'])
    ->middleware(['signed:relative', 'throttle:30,1'])
    ->name('tickets.print.fail');

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/struk/preview', [StrukController::class, 'previewStruk'])->name('struk.preview');
    Route::get('/struk/test', function () {
        abort_if(app()->isProduction(), 404);
        $serviceId = Service::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
        abort_unless($serviceId, 404, 'Belum ada layanan aktif.');

        return view('pdf-preview', ['serviceId' => $serviceId]);
    })->name('struk.test');
    Route::get('/barcode/show', [BarcodeController::class, 'show'])->name('barcode.show');
});

// Barcode antrian
Route::get('/barcode/scan', [BarcodeController::class, 'scan'])
    ->middleware('signed')
    ->name('barcode.scan');

// TV Display dan Announcement
Route::get('/tv-display-legacy', function () {
    return redirect()->route('tv.index');
})->name('tv.display.legacy');

Route::get('/tv-display-enhanced', function () {
    return redirect()->route('tv.index');
})->name('tv.display.enhanced');

Route::get('/tv-display-optimized', function () {
    return redirect()->route('tv.index');
})->name('tv.display.optimized');
Route::get('/api/announcements/latest', [AnnouncementController::class, 'getLatestAnnouncement'])->name('api.announcements.latest');
Route::get('/api/tv-display/queue-status', [TvDisplayController::class, 'getQueueStatus'])->name('api.tv.queue-status');
Route::get('/api/tv-display/latest-announcement', [TvDisplayController::class, 'getLatestAnnouncement'])->name('api.tv.latest-announcement');

// TV Display Index (Public - No Auth Required)
Route::get('/tv-display', [TvDisplayController::class, 'index'])->name('tv.display');

// TV Display per Zona
Route::get('/tv-display/zona/{zoneId}', function ($zoneId) {
    $zoneCounter = Counter::where('id', $zoneId)->first();
    if (! $zoneCounter) {
        abort(404, 'Zone not found');
    }

    return view('tv-simple', [
        'zoneId' => $zoneId,
        'zoneName' => $zoneCounter->name,
    ]);
})->middleware('device:tv-zone-api')->name('tv.display.zone');

// TV Display Public Routes (Short URLs for easy access)
Route::get('/tv', [TvDisplayController::class, 'landing'])->name('tv.index');

// Redirect root to admin login
Route::get('/', function () {
    return redirect('/admin');
})->name('home');

// Simple TV Display (Direct access without login)
Route::get('/tv-simple', function () {
    $resolver = app(TvZoneResolver::class);
    $counter = $resolver->resolve(1);

    return view('tv-simple', [
        'zoneId' => $counter?->id ?? 0,
        'zoneName' => $counter?->name ?? $resolver->fallbackName(1),
    ]);
})->middleware('device:tv,1')->name('tv.simple');

$tvZones = array_keys(config('tv.zones', []));

foreach ($tvZones as $zoneNumber) {
    Route::get("/tv{$zoneNumber}", function () use ($zoneNumber) {
        $resolver = app(TvZoneResolver::class);
        $counter = $resolver->resolve($zoneNumber);

        return view('tv-simple', [
            'zoneId' => $counter?->id ?? 0,
            'zoneName' => $counter?->name ?? $resolver->fallbackName($zoneNumber),
        ]);
    })->middleware("device:tv,{$zoneNumber}")->name("tv.zona{$zoneNumber}");
}

// API untuk data per zona
Route::middleware('device:tv-zone-api')->group(function () {
    Route::get('/api/tv-display/zone/{zoneId}/services', [TvDisplayController::class, 'getZoneServices'])->name('api.tv.zone.services');
    Route::get('/api/tv-display/zone/{zoneId}/queues', [TvDisplayController::class, 'getZoneQueues'])->name('api.tv.zone.queues');
});

// Audio API
Route::get('/api/audio/announcement', [AudioController::class, 'getAnnouncementAudio'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('api.audio.announcement');
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/api/audio/list', [AudioController::class, 'getAudioList'])->name('api.audio.list');
    Route::post('/api/audio/upload', [AudioController::class, 'uploadAudio'])->name('api.audio.upload');
    Route::delete('/api/audio/delete', [AudioController::class, 'deleteAudio'])->name('api.audio.delete');
});

// Public Queue Kiosk (authorized device only when DEVICE_AUTH_ENABLED=true)
Route::get('/kiosk/cetak-antrian', [PublicQueueKioskController::class, 'index'])
    ->middleware('device:kiosk')
    ->name('public.queue-kiosk');
Route::post('/kiosk/cetak-antrian/select-service/{serviceId}', [PublicQueueKioskController::class, 'selectService'])
    ->middleware(['device:kiosk', 'throttle:30,1'])
    ->name('public.queue-kiosk.select-service');
