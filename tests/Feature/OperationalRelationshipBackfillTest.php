<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalRelationshipBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_unambiguous_relationships_are_backfilled(): void
    {
        $firstInstansi = Instansi::query()->create(['nama_instansi' => 'INSTANSI SATU']);
        $secondInstansi = Instansi::query()->create(['nama_instansi' => 'INSTANSI DUA']);

        $uniqueService = $this->createService('LAYANAN UNIK');
        $uniqueCounter = Counter::query()->create([
            'name' => 'LOKET UNIK',
            'instansi_id' => $firstInstansi->getKey(),
            'service_id' => $uniqueService->id,
            'is_active' => true,
        ]);
        $uniqueOperator = User::factory()->create([
            'role' => 'operator',
            'service_id' => $uniqueService->id,
            'counter_id' => null,
        ]);

        $ambiguousService = $this->createService('LAYANAN AMBIGU');
        Counter::query()->create([
            'name' => 'LOKET AMBIGU 1',
            'instansi_id' => $firstInstansi->getKey(),
            'service_id' => $ambiguousService->id,
            'is_active' => true,
        ]);
        Counter::query()->create([
            'name' => 'LOKET AMBIGU 2',
            'instansi_id' => $secondInstansi->getKey(),
            'service_id' => $ambiguousService->id,
            'is_active' => true,
        ]);
        $ambiguousOperator = User::factory()->create([
            'role' => 'operator',
            'service_id' => $ambiguousService->id,
            'counter_id' => null,
        ]);

        $migration = require database_path('migrations/2026_08_17_000005_backfill_unambiguous_operational_relationships.php');
        $migration->up();

        $this->assertSame($firstInstansi->getKey(), $uniqueService->refresh()->instansi_id);
        $this->assertSame($uniqueCounter->id, $uniqueOperator->refresh()->counter_id);
        $this->assertNull($ambiguousService->refresh()->instansi_id);
        $this->assertNull($ambiguousOperator->refresh()->counter_id);
    }

    private function createService(string $name): Service
    {
        return Service::query()->create([
            'name' => $name,
            'prefix' => 'R',
            'padding' => 3,
            'is_active' => true,
        ]);
    }
}
