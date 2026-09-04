<?php

namespace App\Filament\Pages;

use App\Exceptions\QueueUnavailableException;
use App\Models\Queue;
use App\Models\Service;
use App\Services\KioskCatalogService;
use App\Services\QueueService;
use App\Services\ServiceQueueAvailabilityService;
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

    public $selectedInstansi = null;

    public $instansis;

    public $services;

    public $popularInstansis;

    public $otherInstansis;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(KioskCatalogService $catalog): void
    {
        $this->instansis = $catalog->rankedInstitutions();
        $institutionColumns = $catalog->splitInstitutions($this->instansis);
        $this->popularInstansis = $institutionColumns['popular'];
        $this->otherInstansis = $institutionColumns['others'];
        $this->services = collect();
    }

    public function selectInstansi(int $instansiId): void
    {
        $institution = $this->instansis->firstWhere('instansi_id', $instansiId);

        if (! $institution) {
            $this->dispatch('kiosk-print-error', message: 'Instansi tidak tersedia.');

            return;
        }

        $this->selectedInstansi = $instansiId;
        $services = Service::query()
            ->where('instansi_id', $instansiId)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->whereHas('instansi', fn ($query) => $query
                ->where('is_active', true)
                ->where('is_archived', false)
                ->whereHas('counters', fn ($counter) => $counter
                    ->where('is_active', true)
                    ->where('is_archived', false)))
            ->orderBy('name')
            ->get();

        $this->services = app(KioskCatalogService::class)
            ->withDisdukcapilConsultationQueueCounts($services);
        $availability = app(ServiceQueueAvailabilityService::class);
        $this->services->each(function (Service $service) use ($availability): void {
            $state = $availability->evaluate($service);
            $service->setAttribute('queue_available', $state['available']);
            $service->setAttribute('queue_unavailable_message', $state['message']);
        });
    }

    public function selectService(int $serviceId, QueueService $queueService): void
    {
        $service = $this->services->firstWhere('id', $serviceId);

        if (! $service
            || $service->is_archived
            || ! $service->is_accepting_queues
            || (int) $service->instansi_id !== (int) $this->selectedInstansi) {
            $this->dispatch('kiosk-print-error', message: 'Layanan tidak tersedia pada instansi yang dipilih.');

            return;
        }

        try {
            $queue = $queueService->reserveQueueForPrinting($service->id);
            $this->dispatch('ticket-ready', ...$this->printPayload($queue));
        } catch (QueueUnavailableException $exception) {
            $this->dispatch('kiosk-print-error', message: $exception->getMessage());
        } catch (\Throwable $exception) {
            Log::error('Gagal menyiapkan tiket kiosk admin.', [
                'service_id' => $serviceId,
                'exception' => $exception,
            ]);
            $this->dispatch('kiosk-print-error', message: 'Tiket gagal disiapkan. Silakan hubungi petugas.');
        }
    }

    public function resetSelection(): void
    {
        $this->selectedInstansi = null;
        $this->services = collect();
    }

    private function printPayload(Queue $queue): array
    {
        $expiresAt = now()->addMinutes(2);

        return [
            'queueId' => $queue->id,
            'number' => $queue->number,
            'printUrl' => URL::temporarySignedRoute('tickets.print', $expiresAt, ['queue' => $queue], absolute: false),
            'confirmUrl' => URL::temporarySignedRoute('tickets.print.confirm', $expiresAt, ['queue' => $queue], absolute: false),
            'failUrl' => URL::temporarySignedRoute('tickets.print.fail', $expiresAt, ['queue' => $queue], absolute: false),
        ];
    }
}
