<?php

namespace App\Services;

use App\Exceptions\QueueUnavailableException;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QueueService
{
    /** @var array<int, array<int, string>> */
    private const ALTERNATING_SERVICE_PREFIX_GROUPS = [
        ['3C-6', '3C-7'],
        ['4A1', '4A2'],
    ];

    public function __construct(
        private readonly ServiceQueueAvailabilityService $availability,
    ) {}

    public function addQueue(int $serviceId): Queue
    {
        return $this->createQueue($serviceId, Queue::STATUS_WAITING);
    }

    public function reserveQueueForPrinting(int $serviceId): Queue
    {
        $this->expireStalePrintReservations();

        return $this->createQueue($serviceId, Queue::STATUS_PRINTING);
    }

    public function confirmPrintedQueue(Queue $queue): bool
    {
        if ($queue->status === Queue::STATUS_WAITING) {
            return true;
        }

        return Queue::query()
            ->whereKey($queue->id)
            ->where('status', Queue::STATUS_PRINTING)
            ->update(['status' => Queue::STATUS_WAITING]) === 1;
    }

    public function failPrintingQueue(Queue $queue): bool
    {
        if ($queue->status === Queue::STATUS_CANCELED) {
            return true;
        }

        return Queue::query()
            ->whereKey($queue->id)
            ->where('status', Queue::STATUS_PRINTING)
            ->update([
                'status' => Queue::STATUS_CANCELED,
                'canceled_at' => now(),
            ]) === 1;
    }

    public function expireStalePrintReservations(int $minutes = 2): int
    {
        return Queue::query()
            ->where('status', Queue::STATUS_PRINTING)
            ->where('updated_at', '<', now()->subMinutes($minutes))
            ->update([
                'status' => Queue::STATUS_CANCELED,
                'canceled_at' => now(),
            ]);
    }

    private function createQueue(int $serviceId, string $status): Queue
    {
        return DB::transaction(function () use ($serviceId, $status) {
            $requestedService = Service::query()->findOrFail($serviceId);

            $alternatingService = $this->lockAlternatingSharedCounterService($requestedService);

            if ($alternatingService) {
                $this->ensureAvailable($alternatingService);

                return Queue::create([
                    'service_id' => $alternatingService->id,
                    'number' => $this->generateNumberForService($alternatingService),
                    'status' => $status,
                ]);
            }

            $service = Service::query()
                ->where('is_active', true)
                ->where('is_archived', false)
                ->where('is_accepting_queues', true)
                ->lockForUpdate()
                ->findOrFail($serviceId);

            $this->ensureAvailable($service);

            // Services dengan prefix yang sama memakai satu urutan nomor.
            // Semua baris layanan tersebut dikunci dalam transaksi agar dua
            // loket tidak dapat menerbitkan nomor yang sama secara bersamaan.
            $serviceIdsForPrefix = Service::query()
                ->where('prefix', $service->prefix)
                ->where('is_active', true)
                ->where('is_archived', false)
                ->lockForUpdate()
                ->pluck('id');

            return Queue::create([
                'service_id' => $service->id,
                'number' => $this->generateNumberForService($service, $serviceIdsForPrefix),
                'status' => $status,
            ]);
        });
    }

    private function ensureAvailable(Service $service): void
    {
        $availability = $this->availability->evaluate($service);

        if (! $availability['available']) {
            throw new QueueUnavailableException($availability['message']);
        }
    }

    /**
     * Beberapa pasangan loket melayani satu jenis layanan yang sama. Tiket
     * diarahkan bergiliran dalam satu transaksi agar sentuhan serentak tetap
     * konsisten dan tidak selalu menumpuk pada loket pertama.
     */
    private function lockAlternatingSharedCounterService(Service $requestedService): ?Service
    {
        $prefixes = collect(self::ALTERNATING_SERVICE_PREFIX_GROUPS)
            ->first(fn (array $group): bool => in_array($requestedService->prefix, $group, true));

        if (! $prefixes) {
            return null;
        }

        $services = Service::query()
            ->where('instansi_id', $requestedService->instansi_id)
            ->whereIn('prefix', $prefixes)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->where('is_accepting_queues', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($services->count() !== 2 || ! $services->contains('id', $requestedService->id)) {
            return null;
        }

        $lastServiceId = Queue::query()
            ->whereIn('service_id', $services->pluck('id'))
            ->whereDate('created_at', today())
            ->latest('id')
            ->value('service_id');

        return $services->first(fn (Service $service): bool => (int) $service->id !== (int) $lastServiceId)
            ?? $services->firstWhere('prefix', $prefixes[0]);
    }

    public function generateNumber($serviceId)
    {
        $service = Service::findOrFail($serviceId);

        $serviceIdsForPrefix = Service::query()
            ->where('prefix', $service->prefix)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->pluck('id');

        return $this->generateNumberForService($service, $serviceIdsForPrefix);
    }

    private function generateNumberForService(Service $service, $serviceIds = null): string
    {
        $serviceIds ??= collect([$service->id]);

        $lastQueue = Queue::whereIn('service_id', $serviceIds)
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        $lastQueueNumber = $lastQueue ? intval(
            substr($lastQueue->number, strlen($service->prefix) + 1)
        ) : 0;

        $padding = $service->padding ?? 0;
        $newQueueNumber = $lastQueue ? $lastQueueNumber + 1 : 1;

        // Jika padding = 0, tidak perlu str_pad
        if ($padding == 0) {
            return $service->prefix.'-'.$newQueueNumber;
        }

        return $service->prefix.'-'.str_pad($newQueueNumber, $padding, '0', STR_PAD_LEFT);
    }

    public function getNextQueue($counterId)
    {
        $counter = $this->findCounter($counterId);

        return $this->eligibleWaitingQueues($counter)
            ->orderBy('id')
            ->first();
    }

    public function callNextQueue($counterId, ?int $serviceId = null)
    {
        return DB::transaction(function () use ($counterId, $serviceId) {
            $counter = Counter::withoutGlobalScopes()
                ->whereKey($counterId)
                ->lockForUpdate()
                ->firstOrFail();

            $counter->load('service');

            $hasActiveQueue = Queue::query()
                ->where('counter_id', $counter->id)
                ->whereIn('status', Queue::ACTIVE_STATUSES)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($hasActiveQueue || ! $counter->is_active) {
                return null;
            }

            if ($serviceId !== null && ! $counter->callableServiceIds()->contains($serviceId)) {
                return null;
            }

            $nextQueue = $this->eligibleWaitingQueues($counter, $serviceId)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $nextQueue) {
                return null;
            }

            $nextQueue->update([
                'status' => Queue::STATUS_CALLED,
                'counter_id' => $counterId,
                'called_at' => now(),
            ]);

            return $nextQueue->refresh();
        });
    }

    public function serveQueue(Queue $queue): bool
    {
        return Queue::query()
            ->whereKey($queue->id)
            ->where('status', Queue::STATUS_CALLED)
            ->update([
                'status' => Queue::STATUS_SERVING,
                'served_at' => now(),
            ]) === 1;
    }

    public function finishQueue(Queue $queue): bool
    {
        return Queue::query()
            ->whereKey($queue->id)
            ->where('status', Queue::STATUS_SERVING)
            ->update([
                'status' => Queue::STATUS_FINISHED,
                'finished_at' => now(),
            ]) === 1;
    }

    public function cancelQueue(Queue $queue): bool
    {
        return Queue::query()
            ->whereKey($queue->id)
            ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_CALLED])
            ->update([
                'status' => Queue::STATUS_CANCELED,
                'canceled_at' => now(),
            ]) === 1;
    }

    public function recallQueue(Queue $queue): bool
    {
        return Queue::query()
            ->whereKey($queue->id)
            ->where('status', Queue::STATUS_CALLED)
            ->update(['called_at' => now()]) === 1;
    }

    private function findCounter(int $counterId): Counter
    {
        return Counter::withoutGlobalScopes()
            ->with('service')
            ->findOrFail($counterId);
    }

    private function eligibleWaitingQueues(Counter $counter, ?int $serviceId = null): Builder
    {
        $serviceIds = collect([$serviceId ?? $counter->service_id])
            ->filter()
            ->unique()
            ->values();

        // Nomor menunggu adalah milik layanan, bukan milik loket tertentu.
        // Dukungan counter_id selain null dipertahankan untuk data lama yang
        // sempat diarahkan lebih awal. Loket saudara hanya boleh mengambilnya
        // jika berada pada instansi dan layanan utama yang sama.
        $eligibleCounterIds = collect([$counter->id]);
        if ($serviceIds->count() === 1 && $counter->instansi_id) {
            $eligibleCounterIds = Counter::withoutGlobalScopes()
                ->where('instansi_id', $counter->instansi_id)
                ->where('service_id', $serviceIds->first())
                ->where('is_archived', false)
                ->pluck('id')
                ->push($counter->id)
                ->unique()
                ->values();
        }

        return Queue::query()
            ->where('status', Queue::STATUS_WAITING)
            ->whereNull('called_at')
            ->whereDate('created_at', now()->toDateString())
            ->where(function (Builder $query) use ($counter, $serviceIds): void {
                if ($serviceIds->isNotEmpty()) {
                    $query->whereIn('service_id', $serviceIds);
                } else {
                    $query->whereRaw('1 = 0');
                }

                $query->orWhere('counter_id', $counter->id);
            })
            ->where(function (Builder $query) use ($eligibleCounterIds): void {
                $query->whereNull('counter_id')
                    ->orWhereIn('counter_id', $eligibleCounterIds);
            });
    }
}
