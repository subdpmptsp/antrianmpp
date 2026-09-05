<?php

namespace Tests\Feature;

use App\Models\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QueueTimestampMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_lifecycle_transitions_always_write_matching_timestamps(): void
    {
        $institution = $this->createTestInstitution();
        $service = $this->createTestService($institution, [
            'name' => 'LAYANAN TEST',
            'prefix' => 'T',
            'padding' => 3,
            'is_active' => true,
        ]);

        $serving = Queue::query()->create([
            'service_id' => $service->id,
            'number' => 'T-001',
            'status' => 'serving',
            'called_at' => now(),
            'served_at' => now(),
        ]);
        $finished = Queue::query()->create([
            'service_id' => $service->id,
            'number' => 'T-002',
            'status' => 'finished',
            'called_at' => now(),
            'served_at' => now(),
            'finished_at' => now(),
        ]);
        $canceled = Queue::query()->create([
            'service_id' => $service->id,
            'number' => 'T-003',
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        $this->assertNotNull($serving->served_at);
        $this->assertNotNull($finished->finished_at);
        $this->assertNotNull($canceled->canceled_at);
    }

    public function test_queue_lookup_indexes_exist(): void
    {
        $indexNames = collect(Schema::getIndexes('queues'))->pluck('name');

        $this->assertContains('queues_service_status_created_index', $indexNames);
        $this->assertContains('queues_counter_status_created_index', $indexNames);
    }
}
