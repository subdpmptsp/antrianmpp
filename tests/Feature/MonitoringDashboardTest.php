<?php

namespace Tests\Feature;

use App\Filament\Pages\MonitoringDashboard;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonitoringDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
    }

    public function test_queue_analysis_defaults_to_today(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(MonitoringDashboard::class)
            ->assertSet('analysisRange', 'today')
            ->assertSee('Hari ini');
    }

    public function test_admin_can_preview_monitoring_report_as_inline_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('preview.rekap-layanan-pdf', [
            'from' => '2026-09-14',
            'to' => '2026-09-14',
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=rekap-layanan-2026-09-14-sd-2026-09-14.pdf');
    }

    public function test_non_admin_cannot_preview_monitoring_pdf(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get(route('preview.rekap-layanan-pdf', [
            'from' => '2026-09-14',
            'to' => '2026-09-14',
        ]))->assertForbidden();
    }
}
