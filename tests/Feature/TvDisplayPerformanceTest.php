<?php

namespace Tests\Feature;

use App\Http\Controllers\TvDisplayController;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TvDisplayPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_status_query_count_does_not_grow_per_service(): void
    {
        for ($index = 1; $index <= 12; $index++) {
            $counter = Counter::query()->create([
                'name' => "LOKET {$index}",
                'is_active' => true,
            ]);
            $service = Service::query()->create([
                'name' => "LAYANAN {$index}",
                'prefix' => "T{$index}",
                'padding' => 3,
                'counter_id' => $counter->id,
                'is_active' => true,
            ]);
            $counter->update(['service_id' => $service->id]);
            Queue::query()->create([
                'service_id' => $service->id,
                'number' => "T{$index}-001",
                'status' => 'waiting',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = app(TvDisplayController::class)->getQueueStatus();
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertLessThanOrEqual(10, $queryCount, "TV status executed {$queryCount} queries.");
    }

    public function test_zone_queue_query_count_does_not_grow_per_counter(): void
    {
        $zone = Counter::query()->create([
            'id' => 5,
            'name' => 'ZONA 1',
            'is_active' => true,
        ]);

        for ($index = 1; $index <= 10; $index++) {
            $counter = Counter::query()->create([
                'name' => "ZONA 1 - LOKET {$index}",
                'is_active' => true,
            ]);
            $service = Service::query()->create([
                'name' => "LAYANAN ZONA {$index}",
                'prefix' => "Z{$index}",
                'padding' => 3,
                'counter_id' => $counter->id,
                'is_active' => true,
            ]);
            $counter->update(['service_id' => $service->id]);
            Queue::query()->create([
                'service_id' => $service->id,
                'counter_id' => $counter->id,
                'number' => "Z{$index}-001",
                'status' => 'called',
                'called_at' => now(),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = app(TvDisplayController::class)->getZoneQueues($zone->id);
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertLessThanOrEqual(4, $queryCount, "Zone queue endpoint executed {$queryCount} queries.");
    }
}
