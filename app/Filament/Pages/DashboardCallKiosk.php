<?php

namespace App\Filament\Pages;

use App\Models\Counter;
use App\Models\CounterClosureRequest;
use App\Models\Queue;
use App\Models\Service;
use App\Services\AudioConfigurationService;
use App\Services\CounterClosureService;
use App\Services\QueueService;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View; // Penting untuk method render
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DashboardCallKiosk extends Page
{
    // --- Konfigurasi Halaman Filament ---
    // protected static ?string $navigationIcon = 'heroicon-o-speakerphone';
    protected static string $view = 'filament.pages.dashboard-call-kiosk';

    /** The operator workspace intentionally has no Filament sidebar. */
    protected static string $layout = 'filament.layouts.operator-call-kiosk';

    protected static ?string $title = 'Loket Panggilan Antrian';

    protected static ?string $navigationLabel = 'Loket Panggilan';

    protected static ?string $navigationIcon = 'heroicon-o-speaker-wave';

    protected static ?string $navigationGroup = 'Operasional';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('operate-counter') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    // --- Properti untuk State Komponen ---
    public $counters; // Akan menampung semua loket untuk navigasi

    public ?int $selectedCounterId = null; // ID dari loket yang sedang dipilih
    public ?int $selectedServiceId = null;
    public ?string $selectedZone = null;
    public ?string $closeReason = null;
    public bool $autoReopenCounter = true;

    /**
     * Method `mount` dijalankan sekali saat komponen pertama kali dimuat.
     * Kita gunakan untuk inisialisasi data awal.
     */
    public function mount(): void
    {
        $user = Auth::user();

        Log::debug('DashboardCallKiosk mount started', [
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'user_counter_id' => $user?->counter_id,
        ]);

        // Jika operator, batasi hanya ke loket yang ditugaskan
        if ($user && $user->role === 'operator') {
            if (! $user->counter_id) {
                $this->counters = collect();
                $this->selectedCounterId = null;

                return;
            }

            // Gunakan withoutGlobalScopes untuk memastikan counter ditemukan
            $counter = Counter::withoutGlobalScopes()
                ->with(['service', 'instansi'])
                ->find($user->counter_id);

            if ($counter) {
                $this->counters = collect([$counter]);
                $this->selectedCounterId = $counter->id; // Pastikan menggunakan counter->id, bukan user->counter_id
                $this->selectedServiceId = $counter->service_id;
                $this->selectedZone = $counter->name;

                // Log untuk debugging
                Log::debug('Operator counter loaded successfully', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'counter_id' => $counter->id,
                    'counter_name' => $counter->display_name,
                    'service_id' => $counter->service_id,
                    'service_name' => $counter->service?->name,
                    'instansi_id' => $counter->instansi_id,
                    'instansi_name' => $counter->instansi?->nama_instansi,
                    'selected_counter_id' => $this->selectedCounterId,
                    'is_active' => $counter->is_active,
                    'counter_loaded' => true,
                    'counters_collection_count' => $this->counters->count(),
                ]);
            } else {
                $this->counters = collect();
                $this->selectedCounterId = null;

                Log::error('Counter not found for operator', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_counter_id' => $user->counter_id,
                    'all_counters' => Counter::withoutGlobalScopes()->pluck('id', 'name')->toArray(),
                ]);
            }
        } else {
            // Admin bisa melihat semua loket yang ada (sama seperti di manajemen loket)
            $this->counters = Counter::with(['service', 'instansi'])
                ->orderBy('name')
                ->get();
            if ($this->counters->isNotEmpty()) {
                $this->selectedCounterId = $this->counters->first()->id;
                $this->selectedServiceId = $this->counters->first()->service_id;
                $this->selectedZone = $this->counters->first()->name;
            }
        }
    }

    public function getZoneOptionsProperty()
    {
        return Counter::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();
    }

    public function getVisibleCountersProperty()
    {
        if (! $this->selectedZone) {
            return $this->counters;
        }

        return $this->counters->filter(fn (Counter $counter): bool => $counter->name === $this->selectedZone)->values();
    }

    /**
     * Ini adalah Computed Property.
     * Cara elegan untuk mendapatkan model Counter yang sedang dipilih.
     * Bisa diakses di view dengan `$this->selectedCounter`.
     */
    public function getSelectedCounterProperty(): ?Counter
    {
        $user = Auth::user();

        if ($user && $user->role === 'operator') {
            if (! $user->counter_id) {
                $this->selectedCounterId = null;

                return null;
            }

            $this->selectedCounterId = $user->counter_id;
        }

        // Jika selectedCounterId null, coba ambil dari user untuk operator
        if (! $this->selectedCounterId) {
            if ($user && $user->role === 'operator' && $user->counter_id) {
                $this->selectedCounterId = $user->counter_id;
                Log::debug('getSelectedCounterProperty: Set selectedCounterId from user', [
                    'user_id' => $user->id,
                    'selected_counter_id' => $this->selectedCounterId,
                ]);
            } else {
                Log::warning('getSelectedCounterProperty: selectedCounterId is null', [
                    'user_id' => Auth::id(),
                    'user_role' => Auth::user()?->role,
                    'user_counter_id' => Auth::user()?->counter_id,
                ]);

                return null;
            }
        }

        // Pastikan counter dimuat dengan relasi service dan instansi
        // Gunakan withoutGlobalScopes untuk operator agar counter selalu ditemukan
        $query = Counter::with(['service', 'instansi']);

        if ($user && $user->role === 'operator') {
            $query = $query->withoutGlobalScopes();
        }

        $counter = $query->find($this->selectedCounterId);

        if (! $counter) {
            Log::error('getSelectedCounterProperty: Counter not found', [
                'selected_counter_id' => $this->selectedCounterId,
                'user_id' => $user?->id,
                'user_role' => $user?->role,
                'user_counter_id' => $user?->counter_id,
                'all_counters' => Counter::withoutGlobalScopes()->pluck('id', 'name')->toArray(),
            ]);
        }

        return $counter;
    }

    public function getPendingClosureRequestProperty(): ?CounterClosureRequest
    {
        if (! $this->selectedCounter) {
            return null;
        }

        return CounterClosureRequest::query()
            ->where('counter_id', $this->selectedCounter->id)
            ->where('status', CounterClosureRequest::STATUS_PENDING)
            ->latest('requested_at')
            ->first();
    }

    // --- Aksi yang Dipanggil dari View ---

    /**
     * Method ini dipanggil saat pengguna mengklik loket lain di navigasi.
     * Ini adalah inti dari fitur "live selection".
     */
    public function selectCounter(int $counterId): void
    {
        $user = Auth::user();

        // Operator tidak boleh berpindah loket di luar tugasnya
        if ($user && $user->role === 'operator') {
            $this->selectedCounterId = $user->counter_id;

            return;
        }

        $this->selectedCounterId = $counterId;

        // Log untuk debugging
        $user = Auth::user();
        $query = Counter::with(['service', 'instansi']);

        if ($user && $user->role === 'operator') {
            $query = $query->withoutGlobalScopes();
        }

        $counter = $query->find($counterId);
        $this->selectedServiceId = $counter?->service_id;
        Log::debug('Counter selected', [
            'counter_id' => $counterId,
            'counter_name' => $counter?->display_name,
            'service_id' => $counter?->service_id,
            'service_name' => $counter?->service?->name,
            'instansi_id' => $counter?->instansi_id,
        ]);

        // Livewire akan otomatis me-render ulang komponen dengan data baru
    }

    public function selectZone(string $zoneName): void
    {
        $user = Auth::user();

        if ($user && $user->role === 'operator') {
            $this->selectedZone = $user->counter?->name ?? $this->selectedZone;
            $this->selectedCounterId = $user->counter_id;

            return;
        }

        $this->selectedZone = $zoneName;

        $firstVisibleCounter = $this->visibleCounters->first();
        if ($firstVisibleCounter) {
            $this->selectedCounterId = $firstVisibleCounter->id;
            $this->selectedServiceId = $firstVisibleCounter->service_id;
        }
    }

    public function selectServiceTab(int $serviceId): void
    {
        $counter = $this->selectedCounter;

        if (! $counter || ! $counter->callableServiceIds()->contains($serviceId)) {
            return;
        }

        $this->selectedServiceId = $serviceId;
    }

    public function callNext(QueueService $queueService)
    {
        Log::debug('callNext method called', [
            'selectedCounterId' => $this->selectedCounterId,
            'selectedCounter' => $this->selectedCounter?->toArray(),
            'is_available' => $this->selectedCounter?->is_available,
            'is_active' => $this->selectedCounter?->is_active,
        ]);

        if (! $this->selectedCounter) {
            Log::debug('Call next blocked - no selected counter');

            return;
        }

        if (! $this->selectedCounter->is_available) {
            Log::debug('Call next blocked - counter not available', [
                'is_active' => $this->selectedCounter->is_active,
                'hasServingQueue' => $this->selectedCounter->queues()->where('status', 'serving')->exists(),
            ]);

            return;
        }

        // Cari antrian berikutnya berdasarkan service_id dari counter
        // Hanya ambil service yang benar-benar terkait dengan counter, bukan semua service di zona
        $nextQueue = null;

        // Kumpulkan semua service_id yang terkait dengan counter ini
        $activeServiceId = $this->selectedServiceId ?? $this->selectedCounter->service_id;
        $serviceIds = array_filter([$activeServiceId]);

        // 1. Cek service_id langsung dari counter
        // Kumpulkan semua service yang ditemukan untuk logging
        $allServicesFound = [];
        if (! empty($serviceIds)) {
            $allServicesFound = Service::whereIn('id', $serviceIds)
                ->get(['id', 'name', 'prefix'])
                ->map(function ($s) {
                    return ['id' => $s->id, 'name' => $s->name, 'prefix' => $s->prefix];
                })
                ->toArray();
        }

        Log::debug('Service IDs for counter', [
            'counter_id' => $this->selectedCounter->id,
            'counter_name' => $this->selectedCounter->display_name,
            'service_ids' => $serviceIds,
            'services_found' => $allServicesFound,
            'direct_service_id' => $this->selectedCounter->service_id,
            'total_service_ids' => count($serviceIds),
        ]);

        // Jika tidak ada service_id sama sekali, tampilkan error
        if (empty($serviceIds)) {
            Log::warning('Counter does not have any service_id', [
                'counter_id' => $this->selectedCounter->id,
                'counter_name' => $this->selectedCounter->display_name,
                'counter_data' => $this->selectedCounter->toArray(),
            ]);

            // Dispatch notification untuk user
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => $this->selectedCounter->display_name.' belum memiliki layanan yang ditetapkan. Silakan hubungi administrator.',
            ]);

            return;
        }

        // Cari antrian berdasarkan service_id yang terkait dengan counter
        $nextQueue = Queue::whereIn('service_id', $serviceIds)
            ->where('status', 'waiting')
            ->whereNull('called_at')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->orderBy('created_at', 'asc')
            ->first();

        // Log untuk debugging
        if (! $nextQueue) {
            $waitingQueuesCount = Queue::whereIn('service_id', $serviceIds)
                ->where('status', 'waiting')
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->count();

            Log::debug('No queue found by service_id', [
                'counter_id' => $this->selectedCounter->id,
            'counter_name' => $this->selectedCounter->display_name,
                'service_ids' => $serviceIds,
                'waiting_queues_count' => $waitingQueuesCount,
                'all_waiting_queues' => Queue::where('status', 'waiting')
                    ->whereDate('created_at', now()->format('Y-m-d'))
                    ->pluck('service_id', 'number')
                    ->toArray(),
            ]);
        }

        $nextQueue = $queueService->callNextQueue($this->selectedCounter->id, $activeServiceId);

        if ($nextQueue) {
            Log::debug('Found next queue', [
                'queueId' => $nextQueue->id,
                'queueNumber' => $nextQueue->number,
                'serviceName' => $nextQueue->service?->name,
            ]);

            // Refresh queue dengan relationships yang lengkap
            $nextQueue->refresh();
            $nextQueue->load(['service', 'counter.instansi']);

            // Dispatch event untuk suara pemanggilan dan tampilan TV
            $serviceName = $nextQueue->service ? $nextQueue->service->name : 'Layanan';
            $zonaName = $this->selectedCounter->name; // Menggunakan counter.name sebagai zona
            $servicePrefix = $nextQueue->service ? $nextQueue->service->prefix : 'A';

            $announcementData = [
                'queueId' => $nextQueue->id,
                'queueNumber' => $nextQueue->number,
                'serviceName' => $serviceName,
                'servicePrefix' => $servicePrefix,
                'counterName' => $this->selectedCounter->display_name,
                'zona' => $zonaName,
                'calledAt' => now()->format('H:i:s'),
            ];

            // Log data lengkap untuk debugging
            Log::debug('Announcement data prepared:', [
                'queueNumber' => $announcementData['queueNumber'],
                'serviceName' => $announcementData['serviceName'],
                'counterName' => $announcementData['counterName'],
                'zona' => $announcementData['zona'],
                'calledAt' => $announcementData['calledAt'],
                'queueId' => $nextQueue->id,
                'serviceId' => $nextQueue->service_id,
                'counterId' => $this->selectedCounter->id,
                'instansiId' => $this->selectedCounter->instansi_id,
            ]);

            Log::debug('Dispatching announce-queue event', $announcementData);
            Log::debug('Individual parameters:', [
                'queueNumber' => $announcementData['queueNumber'],
                'serviceName' => $announcementData['serviceName'],
                'counterName' => $announcementData['counterName'],
                'zona' => $announcementData['zona'],
                'calledAt' => $announcementData['calledAt'],
            ]);

            $this->dispatch('announce-queue', $announcementData);
        } else {
            Log::debug('No next queue found');
        }
    }

    public function markAsServing(QueueService $queueService, Queue $queue)
    {
        $this->authorizeQueueAction($queue);
        $queueService->serveQueue($queue);
    }

    public function callAgain(QueueService $queueService, Queue $queue)
    {
        $this->authorizeQueueAction($queue);

        Log::debug('Call again method called', [
            'queueId' => $queue->id,
            'queueNumber' => $queue->number,
        ]);

        if (! $queueService->recallQueue($queue)) {
            return;
        }

        // Dispatch event untuk suara pemanggilan ulang
        $serviceName = $queue->service ? $queue->service->name : 'Layanan';
        $zonaName = $this->selectedCounter->name; // Menggunakan counter.name sebagai zona
        $servicePrefix = $queue->service ? $queue->service->prefix : 'A';

        $announcementData = [
            'queueId' => $queue->id,
            'queueNumber' => $queue->number,
            'serviceName' => $serviceName,
            'servicePrefix' => $servicePrefix,
            'counterName' => $this->selectedCounter->display_name,
            'zona' => $zonaName,
            'calledAt' => now()->format('H:i:s'),
        ];

        $this->dispatch('announce-queue', $announcementData);
    }

    public function startServing(QueueService $queueService, Queue $queue)
    {
        $this->authorizeQueueAction($queue);

        Log::debug('Start serving method called', [
            'queueId' => $queue->id,
            'queueNumber' => $queue->number,
        ]);

        if ($queueService->serveQueue($queue)) {
            $this->dispatch('service-started', [
                'queueId' => $queue->id,
                'startedAt' => now()->timestamp,
            ]);
        }
    }

    public function markAsFinished(QueueService $queueService, Queue $queue)
    {
        $this->authorizeQueueAction($queue);
        $queueService->finishQueue($queue);
    }

    public function markAsCancelled(QueueService $queueService, Queue $queue)
    {
        $this->authorizeQueueAction($queue);
        $queueService->cancelQueue($queue);
    }

    public function cancelCalled(QueueService $queueService, Queue $queue)
    {
        $this->authorizeQueueAction($queue);

        Log::debug('Cancel called method called', [
            'queueId' => $queue->id,
            'queueNumber' => $queue->number,
        ]);

        $queueService->cancelQueue($queue);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Antrian '.$queue->number.' dibatalkan dan masuk ke statistik batal/lewat.',
        ]);
    }

    private function authorizeQueueAction(Queue $queue): void
    {
        $counter = $this->selectedCounter;

        abort_unless(
            $counter !== null && (int) $queue->counter_id === (int) $counter->id,
            403,
        );
    }

    public function requestCounterClosure(CounterClosureService $closureService): void
    {
        if (! $this->selectedCounter) {
            return;
        }

        if (blank($this->closeReason)) {
            $message = 'Deskripsi alasan harus diisi.';
            $this->addError('closeReason', $message);
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => $message,
            ]);

            return;
        }

        $this->resetErrorBag('closeReason');

        try {
            $closureService->requestClose(
                $this->selectedCounter,
                Auth::user(),
                (string) $this->closeReason,
                $this->autoReopenCounter,
            );
            $this->closeReason = null;
            $this->autoReopenCounter = true;
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Permintaan tutup loket sudah dikirim dan menunggu persetujuan admin.',
            ]);
        } catch (ValidationException $exception) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => collect($exception->errors())->flatten()->first() ?? 'Permintaan tidak dapat diproses.',
            ]);
        }
    }

    public function reopenCounter(CounterClosureService $closureService): void
    {
        if (! $this->selectedCounter) {
            return;
        }

        try {
            $closureService->reopen($this->selectedCounter, Auth::user());
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Loket kembali menerima nomor antrean baru. Waktu buka kembali telah dicatat.',
            ]);
        } catch (ValidationException $exception) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => collect($exception->errors())->flatten()->first() ?? 'Loket tidak dapat dibuka kembali.',
            ]);
        }
    }

    public function getViewData(): array
    {
        $currentQueue = null;
        $waitingQueues = collect(); // Default ke koleksi kosong
        $stats = [
            'total' => 0, 'finished' => 0, 'waiting' => 0, 'cancelled' => 0,
        ];
        $audioConfig = app(AudioConfigurationService::class)->get();
        $callableServices = collect();
        $activeService = null;

        // Hanya ambil data jika ada loket yang dipilih
        if ($this->selectedCounter) {
            $counter = $this->selectedCounter; // Ambil dari computed property

            $callableServices = $counter->additionalServices()
                ->where('services.is_active', true)
                ->orderBy('services.prefix')
                ->get()
                ->push($counter->service)
                ->filter()
                ->unique('id')
                ->sortBy('prefix')
                ->values();

            $activeService = $callableServices->firstWhere('id', $this->selectedServiceId)
                ?? $callableServices->first();
            $this->selectedServiceId = $activeService?->id;

            $waitingCounts = Queue::query()
                ->whereIn('service_id', $callableServices->pluck('id'))
                ->where('status', Queue::STATUS_WAITING)
                ->whereNull('called_at')
                ->whereDate('created_at', today())
                ->selectRaw('service_id, COUNT(*) as total')
                ->groupBy('service_id')
                ->pluck('total', 'service_id');

            $callableServices->each(fn (Service $service) => $service->setAttribute(
                'waiting_count',
                (int) ($waitingCounts[$service->id] ?? 0),
            ));

            // Cari antrian yang sedang dipanggil atau dilayani di loket ini
            // Prioritas: cari yang sedang serving, lalu yang called (dipanggil)
            $currentQueue = Queue::where('counter_id', $counter->id)
                ->whereIn('status', ['serving', 'called'])
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->orderByRaw("CASE WHEN status = 'serving' THEN 1 WHEN status = 'called' THEN 2 END")
                ->first();

            // Kumpulkan semua service ID yang terkait dengan counter ini
            // Hanya ambil service yang benar-benar terkait dengan counter, bukan semua service di zona
            $serviceIds = array_filter([$this->selectedServiceId]);

            // 1. Cek service_id langsung dari counter
            // Jika tidak ada service_id, gunakan counter_id sebagai fallback
            if (empty($serviceIds)) {
                $waitingQueues = Queue::where('counter_id', $counter->id)
                    ->whereIn('status', ['waiting'])
                    ->whereNull('called_at')
                    ->whereDate('created_at', now()->format('Y-m-d'))
                    ->orderBy('created_at', 'asc')
                    ->get();

                $baseQuery = Queue::where('counter_id', $counter->id)
                    ->whereDate('created_at', now()->format('Y-m-d'));
            } else {
                $waitingQueues = Queue::whereIn('service_id', $serviceIds)
                    ->whereIn('status', ['waiting'])
                    ->whereNull('called_at')
                    ->whereDate('created_at', now()->format('Y-m-d'))
                    ->orderBy('created_at', 'asc')
                    ->get();

                $baseQuery = Queue::whereIn('service_id', $serviceIds)
                    ->whereDate('created_at', now()->format('Y-m-d'));
            }
            $stats['total'] = (clone $baseQuery)->count();
            $stats['finished'] = (clone $baseQuery)->where('status', 'finished')->count();
            $stats['waiting'] = $waitingQueues->count();
            $stats['cancelled'] = (clone $baseQuery)->where('status', 'canceled')->count();
        }

        // Kirim semua data yang dibutuhkan ke view
        return [
            'currentQueue' => $currentQueue,
            'waitingQueues' => $waitingQueues,
            'stats' => $stats,
            'callableServices' => $callableServices,
            'activeService' => $activeService,
            'announcementOpeningAudioUrl' => $audioConfig['url'] ?? asset(config('audio.fallback.url', 'sounds/opening.mp3')),
            'ttsSettings' => $audioConfig['tts'] ?? [],
        ];
    }
}
