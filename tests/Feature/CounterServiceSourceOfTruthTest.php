<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CounterServiceSourceOfTruthTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_counters_are_resolved_from_counter_service_id(): void
    {
        $service = $this->createService();
        $assigned = Counter::query()->create([
            'name' => 'LOKET RESMI',
            'service_id' => $service->id,
            'is_active' => true,
        ]);

        $this->assertTrue($service->counters()->whereKey($assigned->id)->exists());
    }

    public function test_legacy_relation_does_not_make_queue_eligible_for_counter(): void
    {
        $counter = Counter::query()->create(['name' => 'LOKET TANPA LAYANAN', 'is_active' => true]);
        $service = $this->createService(['counter_id' => $counter->id]);
        DB::table('counter_service')->insert([
            'counter_id' => $counter->id,
            'service_id' => $service->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Queue::query()->create([
            'service_id' => $service->id,
            'number' => 'S-001',
            'status' => Queue::STATUS_WAITING,
        ]);

        $this->assertNull(app(QueueService::class)->callNextQueue($counter->id));
    }

    private function createService(array $attributes = []): Service
    {
        return Service::query()->create(array_merge([
            'name' => 'LAYANAN SUMBER TUNGGAL',
            'prefix' => 'S',
            'padding' => 3,
            'is_active' => true,
        ], $attributes));
    }
}
