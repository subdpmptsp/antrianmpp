<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QueueService
{
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
            $service = Service::query()
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($serviceId);

            return Queue::create([
                'service_id' => $service->id,
                'number' => $this->generateNumberForService($service),
                'status' => $status,
            ]);
        });
    }

    public function generateNumber($serviceId)
    {
        $service = Service::findOrFail($serviceId);

        return $this->generateNumberForService($service);
    }

    private function generateNumberForService(Service $service): string
    {

        $lastQueue = Queue::where('service_id', $service->id)
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

    public function callNextQueue($counterId)
    {
        return DB::transaction(function () use ($counterId) {
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

            $nextQueue = $this->eligibleWaitingQueues($counter)
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

    private function eligibleWaitingQueues(Counter $counter): Builder
    {
        $serviceIds = collect([$counter->service_id])
            ->filter()
            ->unique()
            ->values();

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
            ->where(function (Builder $query) use ($counter): void {
                $query->whereNull('counter_id')
                    ->orWhere('counter_id', $counter->id);
            });
    }
}
