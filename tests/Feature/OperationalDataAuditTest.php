<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OperationalDataAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_consistent_operational_data_passes_audit(): void
    {
        $exitCode = Artisan::call('app:data-integrity-audit');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('lolos pemeriksaan wajib', Artisan::output());
    }

    public function test_inconsistent_operational_data_blocks_deployment(): void
    {
        User::factory()->create(['role' => 'operator', 'counter_id' => null]);
        $service = Service::query()->create([
            'name' => 'Layanan Tanpa Instansi',
            'prefix' => 'X',
            'padding' => 3,
            'is_active' => true,
        ]);
        $counter = Counter::query()->create([
            'name' => 'LOKET UJI',
            'service_id' => $service->id,
            'is_active' => true,
        ]);
        Service::query()->create([
            'name' => 'Layanan Tanpa Loket',
            'prefix' => 'Y',
            'padding' => 3,
            'is_active' => true,
        ]);
        Counter::query()->create([
            'name' => 'LOKET TANPA LAYANAN',
            'is_active' => false,
        ]);
        Queue::query()->create([
            'counter_id' => $counter->id,
            'service_id' => $service->id,
            'number' => 'X-001',
            'status' => Queue::STATUS_SERVING,
            'called_at' => now(),
        ]);

        $exitCode = Artisan::call('app:data-integrity-audit');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Operator tanpa loket: 1', $output);
        $this->assertStringContainsString('Layanan aktif tanpa instansi: 2', $output);
        $this->assertStringContainsString('Layanan aktif tanpa loket: 1', $output);
        $this->assertStringContainsString('Loket aktif tanpa layanan: 0', $output);
        $this->assertStringContainsString('Antrian dilayani/selesai tanpa served_at: 1', $output);
        $this->assertStringContainsString('Deployment dihentikan', $output);
    }
}
