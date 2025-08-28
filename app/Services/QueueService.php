<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;

class QueueService
{

    public function addQueue($serviceId)
    {
        $number = $this->generateNumber($serviceId);

        return Queue::create([
            'service_id' => $serviceId,
            'number' => $number,
            'status' => 'waiting',
        ]);
    }

    public function generateNumber($serviceId)
    {
        $service = Service::findOrFail($serviceId);

        $lastQueue = Queue::where('service_id', $serviceId)
            ->orderByDesc('id')
            ->first();

        $currentDate = now()->format('Y-m-d');

        $lastQueueDate = $lastQueue ? $lastQueue->created_at->format('Y-m-d') : null;

        $isSameDate = $currentDate === $lastQueueDate;

        $lastQueueNumber = $lastQueue ? intval(
            substr($lastQueue->number, strlen($service->prefix))
        ) : 0;

        $maximumNumber = pow(10, $service->padding) - 1;

        $isMaximumNumber = $lastQueueNumber === $maximumNumber;

        if ($isSameDate && !$isMaximumNumber) {
            $newQueueNumber = $lastQueueNumber + 1;
        } else {
            $newQueueNumber = 1;
        }

        return $service->prefix . str_pad($newQueueNumber, $service->padding, "0", STR_PAD_LEFT);
    }

    public function getNextQueue($counterId)
    {
        $counter = Counter::findOrFail($counterId);

        return Queue::where('status', 'waiting')
            ->where('service_id', $counter->service_id)
            ->where(function($query) use ($counterId) {
                $query->whereNull('counter_id')->orWhere('counter_id', $counterId);
            })
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->orderBy('id')
            ->first();
    }

    public function callNextQueue($counterId)
    {
        $nextQueue = $this->getNextQueue($counterId);

        if ($nextQueue && !$nextQueue->counter_id) {
            $nextQueue->update([
                'counter_id' => $counterId,
                'called_at' => now()
            ]);
        }

        return $nextQueue;
    }
    
    public function serveQueue(Queue $queue)
    {
        if ($queue->status !== 'waiting') {
            return;
        }

        $queue->update([
            'status' => 'serving',
            'served_at' => now()
        ]);
    }

    public function finishQueue(Queue $queue)
    {
        if ($queue->status !== 'serving') {
            return;
        }

        $queue->update([
            'status' => 'finished',
            'finished_at' => now()
        ]);
    }

    public function cancelQueue(Queue $queue)
    {
        if ($queue->status !== 'waiting') {
            return;
        }

        $queue->update([
            'status' => 'canceled',
            'canceled_at' => now()
        ]);
    }
}
