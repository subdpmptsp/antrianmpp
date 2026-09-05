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
        $firstInstansi = $this->createTestInstitution('INSTANSI SATU', 'ZONA 1');
        $uniqueService = $this->createTestService($firstInstansi, ['name' => 'LAYANAN UNIK', 'prefix' => 'R']);
        $uniqueCounter = $this->createTestCounter($firstInstansi, $uniqueService, ['code_loket' => 'LOKET-UNIK']);
        $uniqueOperator = User::factory()->create(['role' => 'operator', 'counter_id' => $uniqueCounter->id]);

        $migration = require database_path('migrations/2026_08_17_000005_backfill_unambiguous_operational_relationships.php');
        $migration->up();

        $this->assertSame($firstInstansi->getKey(), $uniqueService->refresh()->instansi_id);
        $this->assertSame($uniqueCounter->id, $uniqueOperator->refresh()->counter_id);
    }
}
