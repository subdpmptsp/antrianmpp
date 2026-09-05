<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_called_queue_is_returned_with_stable_queue_id(): void
    {
        [$service, $counter] = $this->createServiceAndCounter();
        $queue = Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'number' => 'A-001',
            'status' => 'called',
            'called_at' => now(),
        ]);

        $this->getJson('/api/tv-display/latest-announcement')
            ->assertOk()
            ->assertJsonStructure(['announcementId'])
            ->assertJsonPath('queueId', $queue->id)
            ->assertJsonPath('queueNumber', 'A-001');

        $this->getJson('/api/announcements/latest')
            ->assertOk()
            ->assertJsonPath('queueId', $queue->id);
    }

    public function test_stale_call_is_not_reannounced(): void
    {
        [$service, $counter] = $this->createServiceAndCounter();
        Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'number' => 'A-001',
            'status' => 'serving',
            'called_at' => now()->subMinutes(3),
            'served_at' => now()->subMinutes(2),
        ]);

        $this->getJson('/api/tv-display/latest-announcement')
            ->assertOk()
            ->assertContent('{}');
    }

    public function test_client_receives_only_announcements_after_its_last_seen_id(): void
    {
        [$service, $counter] = $this->createServiceAndCounter();
        $queue = Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'number' => 'A-002',
            'status' => 'called',
            'called_at' => now(),
        ]);

        $first = $this->getJson('/api/announcements/latest')->assertOk();
        $announcementId = $first->json('announcementId');

        $this->getJson('/api/announcements/latest?after_id='.urlencode($announcementId))
            ->assertOk()
            ->assertContent('{}');

        $this->travel(1)->seconds();
        app(QueueService::class)->recallQueue($queue);

        $this->getJson('/api/announcements/latest?after_id='.urlencode($announcementId))
            ->assertOk()
            ->assertJsonStructure(['announcementId'])
            ->assertJsonMissingExact(['announcementId' => $announcementId]);
    }

    public function test_multiple_calls_are_delivered_in_order_without_being_skipped(): void
    {
        [$service, $counter] = $this->createServiceAndCounter();
        $firstQueue = Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'number' => 'A-010',
            'status' => Queue::STATUS_CALLED,
            'called_at' => now()->subSecond(),
        ]);
        $secondQueue = Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'number' => 'A-011',
            'status' => Queue::STATUS_CALLED,
            'called_at' => now(),
        ]);

        $first = $this->getJson('/api/tv-display/latest-announcement')
            ->assertOk()
            ->assertJsonPath('queueId', $firstQueue->id);
        $second = $this->getJson('/api/tv-display/latest-announcement?after_id='.urlencode($first->json('announcementId')))
            ->assertOk()
            ->assertJsonPath('queueId', $secondQueue->id);

        $this->getJson('/api/tv-display/latest-announcement?after_id='.urlencode($second->json('announcementId')))
            ->assertOk()
            ->assertContent('{}');
    }

    public function test_calls_with_identical_timestamps_are_delivered_once_in_queue_id_order(): void
    {
        [$service, $counter] = $this->createServiceAndCounter();
        $calledAt = now()->startOfSecond();
        $queues = collect(['A-101', 'A-102', 'A-103'])->map(
            fn (string $number) => Queue::query()->create([
                'service_id' => $service->id,
                'counter_id' => $counter->id,
                'number' => $number,
                'status' => Queue::STATUS_CALLED,
                'called_at' => $calledAt,
            ]),
        );
        $cursor = null;

        foreach ($queues as $queue) {
            $response = $this->getJson('/api/tv-display/latest-announcement'.(
                $cursor ? '?after_id='.urlencode($cursor) : ''
            ))
                ->assertOk()
                ->assertJsonPath('queueId', $queue->id);
            $cursor = $response->json('announcementId');
        }

        $this->getJson('/api/tv-display/latest-announcement?after_id='.urlencode($cursor))
            ->assertOk()
            ->assertContent('{}');
    }

    public function test_zone_tv_receives_only_calls_for_its_own_zone(): void
    {
        [$service, $zoneCounter] = $this->createServiceAndCounter('ZONA SATU', 'A');
        [$otherService, $otherCounter] = $this->createServiceAndCounter('ZONA DUA', 'B');
        Queue::query()->create([
            'service_id' => $otherService->id,
            'counter_id' => $otherCounter->id,
            'number' => 'B-001',
            'status' => Queue::STATUS_CALLED,
            'called_at' => now()->subSecond(),
        ]);
        $zoneQueue = Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $zoneCounter->id,
            'number' => 'A-020',
            'status' => Queue::STATUS_CALLED,
            'called_at' => now(),
        ]);

        $this->getJson('/api/tv-display/latest-announcement?zone_id='.$zoneCounter->id)
            ->assertOk()
            ->assertJsonPath('queueId', $zoneQueue->id);
    }

    private function createServiceAndCounter(string $zone = 'ZONA TEST', string $prefix = 'A'): array
    {
        $instansi = Instansi::query()->create([
            'nama_instansi' => 'INSTANSI '.$zone,
            'zone' => $zone,
            'is_active' => true,
            'is_archived' => false,
        ]);
        $counter = Counter::query()->create([
            'name' => $zone,
            'instansi_id' => $instansi->getKey(),
            'is_active' => false,
            'is_archived' => false,
        ]);
        $service = Service::query()->create([
            'name' => 'LAYANAN '.$zone,
            'prefix' => $prefix,
            'padding' => 3,
            'instansi_id' => $instansi->getKey(),
            'counter_id' => $counter->id,
            'is_active' => true,
            'is_archived' => false,
            'is_accepting_queues' => true,
        ]);
        $counter->update(['service_id' => $service->id, 'is_active' => true]);

        return [$service, $counter];
    }
}
