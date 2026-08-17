<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $queueService;

    private Counter $counter;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);
        $this->counter = Counter::query()->create([
            'name' => 'LOKET TEST',
            'is_active' => true,
        ]);
        $this->service = Service::query()->create([
            'name' => 'LAYANAN TEST',
            'prefix' => 'T',
            'padding' => 3,
            'counter_id' => $this->counter->id,
            'is_active' => true,
        ]);
        $this->counter->update(['service_id' => $this->service->id]);
    }

    public function test_calling_next_queue_changes_waiting_to_called(): void
    {
        $queue = $this->createQueue('waiting');

        $this->queueService->callNextQueue($this->counter->id);

        $queue->refresh();
        $this->assertSame('called', $queue->status);
        $this->assertSame($this->counter->id, $queue->counter_id);
        $this->assertNotNull($queue->called_at);
    }

    public function test_called_queue_can_start_serving(): void
    {
        $queue = $this->createQueue('called', [
            'counter_id' => $this->counter->id,
            'called_at' => now(),
        ]);

        $this->queueService->serveQueue($queue);

        $queue->refresh();
        $this->assertSame('serving', $queue->status);
        $this->assertNotNull($queue->served_at);
    }

    public function test_serving_queue_can_be_finished(): void
    {
        $queue = $this->createQueue('serving', [
            'counter_id' => $this->counter->id,
            'called_at' => now()->subMinute(),
            'served_at' => now(),
        ]);

        $this->queueService->finishQueue($queue);

        $queue->refresh();
        $this->assertSame('finished', $queue->status);
        $this->assertNotNull($queue->finished_at);
    }

    public function test_called_queue_can_be_canceled(): void
    {
        $queue = $this->createQueue('called', [
            'counter_id' => $this->counter->id,
            'called_at' => now(),
        ]);

        $this->queueService->cancelQueue($queue);

        $queue->refresh();
        $this->assertSame('canceled', $queue->status);
        $this->assertNotNull($queue->canceled_at);
    }

    public function test_counter_cannot_claim_a_second_active_queue(): void
    {
        $first = $this->createQueue('waiting', ['number' => 'T-001']);
        $second = $this->createQueue('waiting', ['number' => 'T-002']);

        $claimed = $this->queueService->callNextQueue($this->counter->id);
        $blocked = $this->queueService->callNextQueue($this->counter->id);

        $this->assertSame($first->id, $claimed?->id);
        $this->assertNull($blocked);
        $this->assertSame('waiting', $second->refresh()->status);
    }

    public function test_illegal_lifecycle_transitions_are_rejected(): void
    {
        $waiting = $this->createQueue('waiting');

        $this->assertFalse($this->queueService->serveQueue($waiting));
        $this->assertFalse($this->queueService->finishQueue($waiting));
        $this->assertFalse($this->queueService->recallQueue($waiting));
        $this->assertSame('waiting', $waiting->refresh()->status);
    }

    private function createQueue(string $status, array $attributes = []): Queue
    {
        return Queue::query()->create(array_merge([
            'service_id' => $this->service->id,
            'number' => 'T-001',
            'status' => $status,
        ], $attributes));
    }
}
