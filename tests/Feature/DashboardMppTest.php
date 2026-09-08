<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMppTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_mpp_is_publicly_accessible(): void
    {
        $this->get('/dashboard-mpp')
            ->assertOk()
            ->assertSee('SIOLA DALAM SATU DATA')
            ->assertSee('Aktivitas hari ini');
    }

    public function test_dashboard_mpp_data_endpoint_has_expected_shape(): void
    {
        $this->getJson('/api/dashboard-mpp')
            ->assertOk()
            ->assertJsonStructure([
                'tickets_today',
                'completed_today',
                'institution_count',
                'service_count',
                'zone_count',
                'institutions',
                'remaining_institution_count',
                'trend' => [['date', 'label', 'total']],
                'updated_at',
            ]);
    }
}
