<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use App\Services\AnnouncementService;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $queueService;

    private Counter $counter;

    private Service $service;

    private Instansi $instansi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);
        $this->instansi = Instansi::query()->create([
            'nama_instansi' => 'INSTANSI TEST',
            'zone' => 'ZONA TEST',
            'is_active' => true,
            'is_archived' => false,
        ]);
        $this->counter = Counter::query()->create([
            'name' => 'LOKET TEST',
            'instansi_id' => $this->instansi->getKey(),
            'is_active' => false,
            'is_archived' => false,
        ]);
        $this->service = Service::query()->create([
            'name' => 'LAYANAN TEST',
            'prefix' => 'T',
            'padding' => 3,
            'instansi_id' => $this->instansi->getKey(),
            'counter_id' => $this->counter->id,
            'is_active' => true,
            'is_archived' => false,
            'is_accepting_queues' => true,
        ]);
        $this->counter->update(['service_id' => $this->service->id, 'is_active' => true]);
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

    public function test_shared_service_counter_can_claim_queue_preassigned_to_sibling_counter(): void
    {
        $sibling = Counter::query()->create([
            'name' => 'LOKET TEST',
            'code_loket' => '1i15',
            'instansi_id' => $this->instansi->getKey(),
            'service_id' => $this->service->id,
            'is_active' => true,
            'is_archived' => false,
        ]);
        $queue = $this->createQueue('waiting', [
            'number' => 'T-015',
            'counter_id' => $sibling->id,
        ]);

        $claimed = $this->queueService->callNextQueue($this->counter->id, $this->service->id);

        $this->assertSame($queue->id, $claimed?->id);
        $this->assertSame(Queue::STATUS_CALLED, $queue->refresh()->status);
        $this->assertSame($this->counter->id, $queue->counter_id);

        $announcement = app(AnnouncementService::class)->latest();
        $this->assertSame($this->counter->display_name, $announcement['counterName']);
        $this->assertSame('T-015', $announcement['queueNumber']);
    }

    public function test_four_counters_on_one_service_can_call_consecutive_waiting_queues(): void
    {
        $teamCounters = collect([$this->counter]);
        foreach (['2b3', '2b4', '2b5'] as $codeLoket) {
            $teamCounters->push(Counter::query()->create([
                'name' => 'LOKET TEST',
                'code_loket' => $codeLoket,
                'instansi_id' => $this->instansi->getKey(),
                'service_id' => $this->service->id,
                'is_active' => true,
                'is_archived' => false,
            ]));
        }

        $queues = collect(range(1, 4))->map(fn (int $number) => $this->createQueue('waiting', [
            'number' => 'T-'.$number,
        ]));
        $claimedQueues = $teamCounters
            ->map(fn (Counter $counter) => $this->queueService->callNextQueue($counter->id, $this->service->id));

        $this->assertSame($queues->pluck('id')->all(), $claimedQueues->pluck('id')->all());
        $claimedQueues->each(function (Queue $queue, int $index) use ($teamCounters): void {
            $this->assertSame($teamCounters[$index]->id, $queue->counter_id);
            $this->assertSame(Queue::STATUS_CALLED, $queue->status);
        });
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
