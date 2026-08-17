<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use App\Services\MonitoringRealtimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitoringRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_realtime_monitoring_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/monitoring-dashboard-real-time')
            ->assertOk()
            ->assertSee('Monitoring Real-Time');
    }

    public function test_guest_cannot_export_realtime_monitoring(): void
    {
        $this->get('/exports/monitoring-realtime')
            ->assertRedirect('/admin/login');
    }

    public function test_monitoring_query_count_stays_bounded_with_many_counters(): void
    {
        foreach (range(0, 4) as $zoneIndex) {
            $zoneName = 'ZONA ' . ($zoneIndex + 1);
            $zone = Counter::query()->create(['name' => $zoneName, 'is_active' => true]);

            for ($counterIndex = 1; $counterIndex <= 6; $counterIndex++) {
                Counter::query()->create([
                    'name' => $zoneName,
                    'is_active' => true,
                ]);
            }

            $instansi = Instansi::query()->create([
                'nama_instansi' => "INSTANSI {$zoneIndex}",
                'counter_id' => $zone->id,
            ]);
            $service = Service::query()->create([
                'name' => "LAYANAN {$zoneIndex}",
                'prefix' => "M{$zoneIndex}",
                'padding' => 3,
                'instansi_id' => $instansi->instansi_id,
                'is_active' => true,
            ]);
            Queue::query()->create([
                'service_id' => $service->id,
                'number' => "M{$zoneIndex}-001",
                'status' => 'waiting',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $service = app(MonitoringRealtimeService::class);
        $summary = $service->getSummary();
        $zones = $service->getZones();
        $services = $service->getServices();
        $options = $service->getInstansiOptions();
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame(5, $summary['menunggu']);
        $this->assertCount(5, $zones);
        $this->assertCount(5, $services);
        $this->assertCount(5, $options);
        $this->assertLessThanOrEqual(10, $queryCount, "Monitoring executed {$queryCount} queries.");
    }
}
