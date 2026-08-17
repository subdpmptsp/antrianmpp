<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;

class QueuePrintController extends Controller
{
    public function __construct(private readonly QueueService $queueService) {}

    public function confirm(Queue $queue): JsonResponse
    {
        $confirmed = $this->queueService->confirmPrintedQueue($queue);

        return response()->json([
            'confirmed' => $confirmed,
            'queue_id' => $queue->id,
        ], $confirmed ? 200 : 409);
    }

    public function fail(Queue $queue): JsonResponse
    {
        $canceled = $this->queueService->failPrintingQueue($queue);

        return response()->json([
            'canceled' => $canceled,
            'queue_id' => $queue->id,
        ], $canceled ? 200 : 409);
    }
}
