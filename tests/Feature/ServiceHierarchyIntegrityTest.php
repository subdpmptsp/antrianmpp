<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiceHierarchyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_hierarchy_can_be_created_in_business_order(): void
    {
        $instansi = $this->institution('ZONA 1');
        $service = $this->service($instansi, '1A');
        $counter = Counter::query()->create([
            'code_loket' => '1A1',
            'instansi_id' => $instansi->getKey(),
            'service_id' => $service->getKey(),
            'is_active' => true,
        ]);
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'counter_id' => $counter->getKey(),
        ]);

        $this->assertSame('ZONA 1', $counter->fresh()->name);
        $this->assertSame($service->getKey(), $operator->fresh()->service_id);
    }

    public function test_service_cannot_be_saved_without_an_institution(): void
    {
        $this->expectException(ValidationException::class);

        Service::query()->create(['name' => 'Tanpa Instansi', 'prefix' => 'XX']);
    }

    public function test_counter_rejects_a_service_from_another_institution(): void
    {
        $first = $this->institution('ZONA 1');
        $second = $this->institution('ZONA 2');
        $service = $this->service($second, '2A');

        $this->expectException(ValidationException::class);

        Counter::query()->create([
            'instansi_id' => $first->getKey(),
            'service_id' => $service->getKey(),
            'is_active' => true,
        ]);
    }

    public function test_referenced_institution_cannot_be_physically_deleted(): void
    {
        $instansi = $this->institution('ZONA 1');
        $this->service($instansi, '1A');

        $this->expectException(QueryException::class);

        $instansi->delete();
    }

    private function institution(string $zone): Instansi
    {
        return Instansi::query()->create([
            'nama_instansi' => 'Instansi '.$zone,
            'zone' => $zone,
            'is_active' => true,
        ]);
    }

    private function service(Instansi $instansi, string $prefix): Service
    {
        return Service::query()->create([
            'instansi_id' => $instansi->getKey(),
            'name' => 'Layanan '.$prefix,
            'prefix' => $prefix,
            'is_active' => true,
        ]);
    }
}
