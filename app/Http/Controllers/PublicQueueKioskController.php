<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Models\Service;
use App\Services\KioskCatalogService;
use App\Services\MasterDataCache;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PublicQueueKioskController extends Controller
{
    public function __construct(
        private readonly KioskCatalogService $catalog,
        private readonly MasterDataCache $masterData,
    ) {}

    public function index(Request $request)
    {
        $counters = $this->catalog->zones();
        $queueRequestToken = (string) Str::uuid();
        $request->session()->put('queue_request_token', $queueRequestToken);

        $selectedCounter = $request->integer('zona') ?: null;
        $selectedInstansi = $request->integer('instansi') ?: null;
        $selectedService = $request->get('service');

        $instansis = collect();
        $services = collect();

        // Jika zona dipilih, ambil instansi
        if ($selectedCounter && isset($counters[$selectedCounter])) {
            $counterId = $counters[$selectedCounter]['counter_id'];

            if ($counterId) {
                $instansis = $this->masterData->remember(
                    "instansis:counter:{$counterId}",
                    fn () => Instansi::where('counter_id', $counterId)
                        ->orderBy('nama_instansi')
                        ->get(),
                );

                // Auto-select instansi jika hanya ada satu dan belum dipilih
                if ($instansis->count() === 1 && ! $selectedInstansi) {
                    $selectedInstansi = $instansis->first()->instansi_id;
                }
            }
        } else {
            $selectedCounter = null;
        }

        // Jika instansi dipilih, ambil services
        if ($selectedInstansi && $instansis->contains('instansi_id', $selectedInstansi)) {
            $services = $this->masterData->remember(
                "services:instansi:{$selectedInstansi}",
                fn () => Service::where('instansi_id', $selectedInstansi)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),
            );
        } else {
            $selectedInstansi = null;
        }

        return view('public.queue-kiosk', [
            'counters' => $counters,
            'selectedCounter' => $selectedCounter,
            'selectedInstansi' => $selectedInstansi,
            'selectedService' => $selectedService,
            'instansis' => $instansis,
            'services' => $services,
            'queueRequestToken' => $queueRequestToken,
        ]);
    }

    public function selectService(Request $request, $serviceId, QueueService $queueService)
    {
        $submittedToken = $request->string('queue_request_token')->toString();
        $expectedToken = (string) $request->session()->pull('queue_request_token', '');

        if ($submittedToken === '' || $expectedToken === '' || ! hash_equals($expectedToken, $submittedToken)) {
            return redirect()->route('public.queue-kiosk')
                ->with('error', 'Permintaan tiket sudah diproses atau kedaluwarsa. Silakan pilih layanan kembali.');
        }

        $selectedCounter = $request->integer('zona');
        $zone = $this->catalog->zones()[$selectedCounter] ?? null;
        $service = Service::query()->with('instansi')->find($serviceId);

        if (
            ! $zone
            || ! $zone['counter_id']
            || ! $service
            || ! $service->is_active
            || ! $service->instansi
            || (int) $service->instansi->counter_id !== (int) $zone['counter_id']
        ) {
            return redirect()->route('public.queue-kiosk')
                ->with('error', 'Layanan tidak aktif atau tidak tersedia pada zona yang dipilih.');
        }

        $queue = $queueService->addQueue($service->id);

        // Redirect ke PDF generator
        $pdfUrl = URL::temporarySignedRoute('struk.generate', now()->addMinutes(15), [
            'queue_id' => $queue->id,
        ]);

        return redirect($pdfUrl);
    }
}
