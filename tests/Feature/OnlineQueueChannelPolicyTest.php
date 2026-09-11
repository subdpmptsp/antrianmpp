<?php

namespace Tests\Feature;

use App\Exceptions\QueueUnavailableException;
use App\Models\OnlineQueueSpecialWindow;
use App\Models\QueueOperatingSetting;
use App\Services\QueueService;
use Carbon\Carbon;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineQueueChannelPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
        QueueOperatingSetting::query()->firstOrCreate([])->update(['enforce_operating_hours' => false]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_online_only_window_blocks_onsite_but_allows_online_checkin_queue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', 'Asia/Jakarta'));
        $service = \App\Models\Service::query()->where('is_active', true)->where('is_archived', false)->firstOrFail();
        $service->update(['is_accepting_queues' => true]);

        OnlineQueueSpecialWindow::query()->create([
            'service_id' => $service->id,
            'date' => '2026-09-11',
            'starts_at' => '09:00',
            'ends_at' => '12:00',
            'mode' => OnlineQueueSpecialWindow::MODE_ONLINE_ONLY,
            'is_active' => true,
        ]);

        try {
            app(QueueService::class)->addQueue($service->id);
            $this->fail('Antrean onsite seharusnya ditolak selama Online Penuh.');
        } catch (QueueUnavailableException $exception) {
            $this->assertStringContainsString('dialihkan ke antrean online', $exception->getMessage());
        }

        $queueService = app(QueueService::class);
        $createQueue = new \ReflectionMethod($queueService, 'createQueue');
        $queue = $createQueue->invoke($queueService, $service->id, 'waiting', ['source' => 'online']);
        $this->assertSame('online', $queue->source);
    }

    public function test_onsite_is_restored_automatically_after_window_ends(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 12:01:00', 'Asia/Jakarta'));
        $service = \App\Models\Service::query()->where('is_active', true)->where('is_archived', false)->firstOrFail();
        $service->update(['is_accepting_queues' => true]);

        OnlineQueueSpecialWindow::query()->create([
            'service_id' => $service->id,
            'date' => '2026-09-11',
            'starts_at' => '09:00',
            'ends_at' => '12:00',
            'mode' => OnlineQueueSpecialWindow::MODE_ONLINE_ONLY,
            'is_active' => true,
        ]);

        $queue = app(QueueService::class)->addQueue($service->id);
        $this->assertDatabaseHas('queues', ['id' => $queue->id]);
    }
}
