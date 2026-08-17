<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestingSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminMenuSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TestingSeeder::class);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    #[DataProvider('adminMenuUrls')]
    public function test_every_admin_sidebar_destination_renders_without_error(string $url): void
    {
        $this->actingAs($this->admin)
            ->get($url)
            ->assertOk();
    }

    public function test_admin_panel_uses_spa_navigation(): void
    {
        $this->assertTrue(Filament::getPanel('admin')->hasSpaMode());
    }

    public static function adminMenuUrls(): array
    {
        return [
            'users' => ['/admin/users'],
            'counters' => ['/admin/counters'],
            'institutions' => ['/admin/instansis'],
            'queues' => ['/admin/queues'],
            'services' => ['/admin/services'],
            'settings' => ['/admin/settings'],
            'attendances' => ['/admin/attendances'],
            'audio management' => ['/admin/audio-management-page'],
            'waiting room kiosk' => ['/admin/dashboard-kiosk'],
            'monitoring dashboard' => ['/admin/monitoring-dashboard'],
            'realtime monitoring' => ['/admin/monitoring-dashboard-real-time'],
            'queue print kiosk' => ['/admin/queue-kiosk'],
            'test print' => ['/admin/test-print-page'],
            'counter call' => ['/admin/dashboard-call-kiosk'],
        ];
    }
}
