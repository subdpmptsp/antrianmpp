<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyServiceCounterReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_merges_duplicate_history_and_corrects_counter_assignment(): void
    {
        $canonical = Service::unguarded(fn () => Service::query()->create([
            'id' => 44,
            'name' => 'Layanan Hukum',
            'prefix' => 'A',
            'padding' => 3,
            'is_active' => true,
        ]));
        $duplicate = Service::unguarded(fn () => Service::query()->create([
            'id' => 58,
            'name' => 'Layanan Hukum',
            'prefix' => 'B',
            'padding' => 3,
            'is_active' => true,
        ]));
        $counter = Counter::unguarded(fn () => Counter::query()->create([
            'id' => 34,
            'name' => 'ZONA 3',
            'service_id' => $canonical->id,
            'is_active' => true,
        ]));
        $queue = Queue::query()->create([
            'service_id' => $duplicate->id,
            'counter_id' => $counter->id,
            'number' => 'B-001',
            'status' => Queue::STATUS_WAITING,
        ]);

        $serviceTwenty = Service::unguarded(fn () => Service::query()->create([
            'id' => 20,
            'name' => 'Layanan Konsultasi Pengadaan',
            'prefix' => 'C',
            'padding' => 3,
            'is_active' => true,
        ]));
        $counterTwentyFour = Counter::unguarded(fn () => Counter::query()->create([
            'id' => 24,
            'name' => 'ZONA 2',
            'service_id' => $canonical->id,
            'is_active' => true,
        ]));
        $operator = User::factory()->create([
            'role' => 'operator',
            'counter_id' => $counterTwentyFour->id,
            'service_id' => $canonical->id,
        ]);

        $migration = require database_path('migrations/2026_08_17_000006_reconcile_legacy_service_counter_assignments.php');
        $migration->up();

        $this->assertSame($canonical->id, $queue->refresh()->service_id);
        $this->assertFalse($duplicate->refresh()->is_active);
        $this->assertSame($serviceTwenty->id, $counterTwentyFour->refresh()->service_id);
        $this->assertSame($serviceTwenty->id, $operator->refresh()->service_id);
    }
}
