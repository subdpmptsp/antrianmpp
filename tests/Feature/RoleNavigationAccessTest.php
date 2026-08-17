<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\TestPrintPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleNavigationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_is_blocked_from_every_administrative_page(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        foreach ([
            '/admin/users',
            '/admin/counters',
            '/admin/instansis',
            '/admin/queues',
            '/admin/services',
            '/admin/settings',
            '/admin/attendances',
            '/admin/audio-management-page',
            '/admin/dashboard-kiosk',
            '/admin/monitoring-dashboard',
            '/admin/monitoring-dashboard-real-time',
            '/admin/queue-kiosk',
            '/admin/test-print-page',
        ] as $url) {
            $this->actingAs($operator)->get($url)->assertForbidden();
        }

        $this->actingAs($operator)
            ->get('/admin/dashboard-call-kiosk')
            ->assertOk();
    }

    public function test_operator_login_redirects_directly_to_the_assigned_counter_page(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator);

        $login = new class extends Login
        {
            public function redirectUrlForTest(): string
            {
                return $this->getRedirectUrl();
            }
        };

        $this->assertSame(
            route('filament.admin.pages.dashboard-call-kiosk'),
            $login->redirectUrlForTest(),
        );
    }

    public function test_test_print_is_available_to_admin_only_outside_production(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->assertTrue(TestPrintPage::canAccess());

        $originalEnvironment = app()->environment();
        app()->instance('env', 'production');

        try {
            $this->assertFalse(TestPrintPage::canAccess());
        } finally {
            app()->instance('env', $originalEnvironment);
        }
    }
}
