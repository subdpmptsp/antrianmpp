<?php

namespace App\Filament\Pages;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Service;
use App\Services\KioskCatalogService;
use App\Services\QueueService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class QueueKiosk extends Page
{
    protected static string $view = 'filament.pages.queue-kiosk';

    protected static ?string $title = 'Cetak Antrian';

    protected static ?string $navigationLabel = 'Kiosk Cetak Antrian';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationIcon = 'heroicon-o-printer';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public $selectedCounter = null;        // key dari array $counters (1..5)

    public $selectedCounterDbId = null;    // <-- ID counter sebenarnya di DB

    public $selectedInstansi = null;

    public $selectedService = null;

    public $counters = [];

    public $instansis;

    public $services;

    public function mount(KioskCatalogService $catalog): void
    {
        $this->counters = $catalog->zones();
        $this->instansis = collect();
        $this->services = collect();
    }

    protected function getViewData(): array
    {
        return [
            'countersDb' => Counter::with(['instansi', 'service'])->get(),
        ];
    }

    public function selectCounter($arrayKey)
    {
        $this->selectedCounter = (int) $arrayKey;
        $this->selectedInstansi = null;
        $this->selectedService = null;
        $this->services = collect();

        if (! isset($this->counters[$this->selectedCounter])) {
            $this->dispatch('notify', type: 'error', message: 'Zona tidak ditemukan.');

            return;
        }

        $counterName = $this->counters[$this->selectedCounter]['name'];
        $this->selectedCounterDbId = $this->counters[$this->selectedCounter]['counter_id'];

        if (! $this->selectedCounterDbId) {
            $this->instansis = collect();
            $this->dispatch('notify', type: 'warning', message: "Counter '{$counterName}' tidak ditemukan.");

            return;
        }

        $this->instansis = Instansi::where('counter_id', $this->selectedCounterDbId)
            ->orderBy('nama_instansi')
            ->get();

        // Auto-select instansi jika hanya ada satu instansi di zona ini
        if ($this->instansis->count() === 1) {
            $singleInstansi = $this->instansis->first();
            $this->selectInstansi($singleInstansi->instansi_id);
        }
    }

    public function selectInstansi($instansiId)
    {
        $instansi = $this->instansis->firstWhere('instansi_id', (int) $instansiId);

        if (! $instansi) {
            $this->selectedInstansi = null;
            $this->services = collect();
            $this->dispatch('notify', type: 'error', message: 'Instansi tidak tersedia pada zona yang dipilih.');

            return;
        }

        $this->selectedInstansi = (int) $instansiId;
        $this->selectedService = null;

        $this->services = Service::where('instansi_id', $instansiId)
            ->where('is_active', true)
            ->orderBy('name') // ganti ke 'nama_service' jika kolommu itu
            ->get();
    }

    // MASIH DIPAKAI? Kalau ya, gunakan selectedCounterDbId agar akurat.
    public function selectInstansiByName($label)
    {
        if (! $this->selectedCounterDbId) {
            return;
        }

        $instansi = Instansi::where('counter_id', $this->selectedCounterDbId)
            ->whereRaw('LOWER(TRIM(nama_instansi)) = LOWER(TRIM(?))', [$label])
            ->first();

        if ($instansi) {
            $this->selectInstansi($instansi->instansi_id);
        } else {
            $this->selectedInstansi = null;
            $this->services = collect();
            $this->dispatch('notify', type: 'warning', message: "Instansi '{$label}' belum terdaftar di database.");
        }
    }

    public function selectService($serviceId)
    {
        $this->selectedService = $this->services->firstWhere('id', (int) $serviceId);

        // Auto-print struk when service is selected
        if ($this->selectedService) {
            $this->printStruk($serviceId);
        }
    }

    public function resetInstansi()
    {
        $this->selectedInstansi = null;
        $this->selectedService = null;
        $this->services = collect();
    }

    public function resetSelection()
    {
        $this->selectedCounter = null;
        $this->selectedCounterDbId = null;   // <-- reset juga
        $this->selectedInstansi = null;
        $this->selectedService = null;
        $this->instansis = collect();
        $this->services = collect();
    }

    public function printStruk($serviceId)
    {
        try {
            // Ambil data service
            $service = Service::with('instansi')->find($serviceId);
            if (
                ! $service
                || ! $service->is_active
                || ! $service->instansi
                || (int) $service->instansi->counter_id !== (int) $this->selectedCounterDbId
            ) {
                Log::error('Service not found for ID: '.$serviceId);
                $this->dispatch('notify', type: 'error', message: 'Layanan tidak tersedia pada zona yang dipilih!');

                return;
            }

            $queue = app(QueueService::class)->addQueue($service->id);
            $queueNumber = $queue->number;

            $pdfUrl = URL::temporarySignedRoute('struk.generate', now()->addMinutes(15), [
                'queue_id' => $queue->id,
            ]);

            return redirect($pdfUrl);

        } catch (\Exception $e) {
            Log::error('Gagal membuat tiket dari kiosk admin.', [
                'service_id' => $serviceId,
                'exception' => $e,
            ]);
            $this->dispatch('notify', type: 'error', message: 'Tiket gagal dibuat. Silakan coba kembali atau hubungi admin.');
        }
    }

    public function printBarcode($serviceId)
    {
        // Ambil data service
        $service = Service::with('instansi')->find($serviceId);
        if (
            ! $service
            || ! $service->is_active
            || ! $service->instansi
            || (int) $service->instansi->counter_id !== (int) $this->selectedCounterDbId
        ) {
            $this->dispatch('notify', type: 'error', message: 'Layanan tidak tersedia pada zona yang dipilih!');

            return;
        }

        $queue = app(QueueService::class)->addQueue($service->id);
        $queueNumber = $queue->number;

        // Redirect ke halaman barcode
        $barcodeUrl = route('barcode.show', [
            'queue_id' => $queue->id,
        ]);

        // Buka halaman barcode di tab baru
        $this->dispatch('open-barcode', url: $barcodeUrl);

        // Notifikasi sukses
        $this->dispatch('notify', type: 'success', message: "Barcode nomor {$queueNumber} berhasil dibuat!");
    }

    public function printTicket(Service $service)
    {
        $this->dispatch('notify', type: 'success', message: "Tiket untuk layanan {$service->name} berhasil dicetak!");
    }
}
