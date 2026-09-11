<?php

namespace App\Http\Controllers;

use App\Exceptions\QueueUnavailableException;
use App\Models\Queue;
use App\Models\OnlineQueueCheckinChallenge;
use App\Models\OnlineQueueSession;
use App\Models\OnlineQueueSetting;
use App\Models\Service;
use App\Services\KioskCatalogService;
use App\Services\MasterDataCache;
use App\Services\QueueService;
use App\Services\ServiceQueueAvailabilityService;
use App\Services\OnlineQueueChannelPolicyService;
use App\Services\FridayPrayerBreakService;
use App\Services\KioskOperationalClosureService;
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
        $kioskInstitutionEntries = $this->catalog->institutionEntries($instansis);
        $bpjsInstansis = $instansis
            ->whereIn('nama_instansi', config('kiosk.bpjs_institutions', []))
            ->sortBy(fn ($institution) => array_search($institution->nama_instansi, config('kiosk.bpjs_institutions', []), true))
            ->values();

        $selectedInstansi = $request->integer('instansi') ?: null;
        $kioskBreak = app(FridayPrayerBreakService::class)->active();
        $kioskOperationalClosure = $kioskBreak ? null : app(KioskOperationalClosureService::class)->active();
        $showOnlineCheckin = ! $selectedInstansi && $request->boolean('online_checkin');
        $onlineCheckinEnabled = false;
        $onlineCheckinToken = null;

        if ($showOnlineCheckin) {
            $onlineSettings = OnlineQueueSetting::current();
            $onlineCheckinEnabled = ! $kioskBreak && ! $kioskOperationalClosure && $this->hasActiveOnlineSession($onlineSettings);

            if ($onlineCheckinEnabled) {
                $onlineCheckinToken = Str::random(64);
                OnlineQueueCheckinChallenge::query()->create([
                    'token_hash' => hash('sha256', $onlineCheckinToken),
                    'station_code' => config('kiosk.online_checkin_station', 'KIOSK-01'),
                    'expires_at' => now()->addSeconds(90),
                ]);
                OnlineQueueCheckinChallenge::query()
                    ->whereNull('completed_at')
                    ->where('expires_at', '<', now()->subMinutes(10))
                    ->delete();
            }
        }
        $showBpjsChoices = ! $selectedInstansi && $request->boolean('bpjs') && $bpjsInstansis->isNotEmpty();
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

        // Tahap BPJS hanya menampilkan dua pilihan. Masing-masing langsung
        // menerbitkan tiket; BPJS Kesehatan memilih salah satu jalur 4A1/4A2
        // yang sedang tersedia tanpa meminta pemohon memilih lagi.
        $bpjsDirectServices = collect();
        if ($bpjsInstansis->isNotEmpty()) {
            $bpjsServices = Service::query()
                ->whereIn('instansi_id', $bpjsInstansis->pluck('instansi_id'))
                ->where('is_active', true)
                ->where('is_archived', false)
                ->whereHas('instansi', fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_archived', false)
                    ->whereHas('counters', fn ($counter) => $counter
                        ->where('is_active', true)
                        ->where('is_archived', false)))
                ->orderBy('id')
                ->get();

            $this->attachQueueAvailability($bpjsServices, app(ServiceQueueAvailabilityService::class));

            $bpjsDirectServices = $bpjsInstansis->mapWithKeys(function ($institution) use ($bpjsServices) {
                $institutionServices = $bpjsServices
                    ->where('instansi_id', $institution->instansi_id)
                    ->values();

                $preferredServices = $institution->nama_instansi === 'BPJS Kesehatan'
                    ? $institutionServices->filter(fn (Service $service): bool => in_array($service->prefix, ['4A1', '4A2'], true))->values()
                    : $institutionServices;

                $service = $preferredServices
                    ->first(fn (Service $service): bool => (bool) $service->getAttribute('queue_available'))
                    ?? $preferredServices->first();

                return $service ? [$institution->instansi_id => $service] : [];
            });
        }

        // Katalog kiosk berubah saat admin mengatur layanan/loket. Jangan biarkan
        // browser memakai halaman lama karena dapat menyembunyikan instansi aktif.
        return response()->view('public.queue-kiosk', [
            'selectedInstansi' => $selectedInstansi,
            'instansis' => $instansis,
            'kioskInstitutionEntries' => $kioskInstitutionEntries,
            'bpjsInstansis' => $bpjsInstansis,
            'bpjsDirectServices' => $bpjsDirectServices,
            'showBpjsChoices' => $showBpjsChoices,
            'showOnlineCheckin' => $showOnlineCheckin,
            'onlineCheckinEnabled' => $onlineCheckinEnabled,
            'onlineCheckinToken' => $onlineCheckinToken,
            'kioskBreak' => $kioskBreak,
            'kioskOperationalClosure' => $kioskOperationalClosure,
            'services' => $services,
            'queueRequestToken' => $queueRequestToken,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function hasActiveOnlineSession(OnlineQueueSetting $settings): bool
    {
        if (! $settings->global_enabled || ! $settings->regular_enabled) {
            return false;
        }

        $sessions = OnlineQueueSession::query()
            ->where('status', OnlineQueueSession::STATUS_ACTIVE)
            ->whereHas('service', fn ($service) => $service
                ->where('is_active', true)
                ->where('is_archived', false));

        if ($settings->pilot_mode) {
            $sessions->whereIn('service_id', array_map('intval', $settings->pilot_service_ids ?? []));
        }

        return $sessions->exists();
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
        $channelPolicy = app(OnlineQueueChannelPolicyService::class);
        $services->each(function (Service $service) use ($availability, $channelPolicy): void {
            $state = $availability->evaluate($service);
            $policy = $channelPolicy->onsitePolicy($service);
            if ($state['available'] && $policy['blocked']) {
                $state = ['available' => false, 'message' => $policy['message']];
            }
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
