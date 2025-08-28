<?php

namespace App\Filament\Pages;

use App\Models\Service;
use App\Models\Setting;
use App\Services\QueueService;
use App\Services\ThermalPrinterService;
use Filament\Pages\Page;

class QueueKiosk extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-printer';

    protected static string $view = 'filament.pages.queue-kiosk';

    protected static string $layout = 'filament.layouts.base-kiosk';

    protected static ?string $navigationLabel = 'Kiosk Cetak Antrian';

    protected static ?string $navigationGroup = 'Display Kiosk';

    public static function canAccess(): bool
    {
        return auth()->user()->role === 'admin';
    }

    protected ThermalPrinterService $thermalPrinterService;

    protected QueueService $queueService;

    public function __construct()
    {
        $this->thermalPrinterService = app(ThermalPrinterService::class);

        $this->queueService = app(QueueService::class);
    }

    public function getViewData(): array
    {
        return [
            'services' => Service::where('is_active', true)->get()
        ];
    }
    
    public function print($serviceId)
    {
        $setting = Setting::first();

        $newQueue = $this->queueService->addQueue($serviceId);

        $text = $this->thermalPrinterService->createText([
            ['text' => $setting->name, 'align' => 'center', 'style' => 'double-h'],
            ['text' => '-----------------', 'align' => 'center'],
            ['text' => 'NOMOR ANTRIAN', 'align' => 'center'],
            ['text' => ''],
            ['text' => $newQueue->number, 'align' => 'center', 'style' => 'double-all'],
            ['text' => ''],
            ['type' => 'qrcode', 'data' => route('queue.status', ['id' => generate_id($newQueue->id)]), 'size' => 5, 'align' => 'center'],
            ['text' => '-----------------', 'align' => 'center'],
            ['text' => 'Scan QR Code di atas', 'align' => 'center'],
            ['text' => 'untuk melihat status antrian', 'align' => 'center'],
            ['text' => '-----------------', 'align' => 'center'],
            ['text' => 'CS:'.$setting->phone, 'align' => 'center']
        ]);

        $this->dispatch("print-start", $text);
    }
}
