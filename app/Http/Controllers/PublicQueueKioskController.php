<?php

namespace App\Http\Controllers;

use App\Exceptions\QueueUnavailableException;
use App\Models\Queue;
use App\Models\Service;
use App\Services\KioskCatalogService;
use App\Services\MasterDataCache;
use App\Services\QueueService;
use App\Services\ServiceQueueAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PublicQueueKioskController extends Controller
{
    public function __construct(
        private readonly MasterDataCache $masterData,
        private readonly KioskCatalogService $catalog,
    ) {}

    public function index(Request $request)
    {
        $queueRequestToken = (string) Str::uuid();
        $request->session()->put('queue_request_token', $queueRequestToken);

        $instansis = $this->catalog->rankedInstitutions();
        $institutionColumns = $this->catalog->splitInstitutions($instansis);

        $selectedInstansi = $request->integer('instansi') ?: null;
        $selectedInstitution = $selectedInstansi
            ? $instansis->firstWhere('instansi_id', $selectedInstansi)
            : null;

        if (! $selectedInstitution) {
            $selectedInstansi = null;
        }

        $services = $selectedInstansi
            ? $this->masterData->remember(
                "services:instansi:{$selectedInstansi}:active",
                fn () => Service::query()
                    ->where('instansi_id', $selectedInstansi)
                    ->where('is_active', true)
                    ->where('is_archived', false)
                    ->whereHas('instansi', fn ($query) => $query
                        ->where('is_active', true)
                        ->where('is_archived', false)
                        ->whereHas('counters', fn ($counter) => $counter
                            ->where('is_active', true)
                            ->where('is_archived', false)))
                    ->orderBy('name')
                    ->get(),
            )
            : collect();

        $services = $this->catalog->withDisdukcapilConsultationQueueCounts($services);
        $this->attachQueueAvailability($services, app(ServiceQueueAvailabilityService::class));

        // Katalog kiosk berubah saat admin mengatur layanan/loket. Jangan biarkan
        // browser memakai halaman lama karena dapat menyembunyikan instansi aktif.
        return response()->view('public.queue-kiosk', [
            'selectedInstansi' => $selectedInstansi,
            'instansis' => $instansis,
            'popularInstansis' => $institutionColumns['popular'],
            'otherInstansis' => $institutionColumns['others'],
            'services' => $services,
            'queueRequestToken' => $queueRequestToken,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function selectService(Request $request, int $serviceId, QueueService $queueService): JsonResponse
    {
        $submittedToken = $request->string('queue_request_token')->toString();
        $expectedToken = (string) $request->session()->pull('queue_request_token', '');

        if ($submittedToken === '' || $expectedToken === '' || ! hash_equals($expectedToken, $submittedToken)) {
            return response()->json([
                'message' => 'Permintaan tiket sudah diproses atau kedaluwarsa. Silakan kembali ke halaman awal.',
            ], 409);
        }

        $selectedInstansi = $request->integer('instansi_id');
        $service = Service::query()->with('instansi.counters')->find($serviceId);

        if (
            ! $service
            || ! $service->is_active
            || $service->is_archived
            || ! $service->is_accepting_queues
            || ! $service->instansi
            || ! $service->instansi->is_active
            || $service->instansi->is_archived
            || ! $service->instansi->counters->contains(fn ($counter): bool => $counter->is_active && ! $counter->is_archived)
            || (int) $service->instansi_id !== $selectedInstansi
        ) {
            return response()->json([
                'message' => 'Layanan sedang tidak menerima nomor antrean baru. Silakan pilih layanan lain atau hubungi petugas.',
            ], 422);
        }

        try {
            $queue = $queueService->reserveQueueForPrinting($service->id);

            return response()->json($this->printPayload($queue), 201);
        } catch (QueueUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            Log::error('Gagal menyiapkan tiket kiosk publik.', [
                'service_id' => $serviceId,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Tiket gagal disiapkan. Silakan hubungi petugas.',
            ], 500);
        }
    }

    private function attachQueueAvailability($services, ServiceQueueAvailabilityService $availability): void
    {
        $services->each(function (Service $service) use ($availability): void {
            $state = $availability->evaluate($service);
            $service->setAttribute('queue_available', $state['available']);
            $service->setAttribute('queue_unavailable_message', $state['message']);
        });
    }

    private function printPayload(Queue $queue): array
    {
        $expiresAt = now()->addMinutes(2);

        return [
            'queue_id' => $queue->id,
            'number' => $queue->number,
            'print_url' => URL::temporarySignedRoute('tickets.print', $expiresAt, ['queue' => $queue], absolute: false),
            'confirm_url' => URL::temporarySignedRoute('tickets.print.confirm', $expiresAt, ['queue' => $queue], absolute: false),
            'fail_url' => URL::temporarySignedRoute('tickets.print.fail', $expiresAt, ['queue' => $queue], absolute: false),
        ];
    }
}
