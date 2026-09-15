<?php

namespace Tests\Feature;

use App\Filament\Pages\ExternalIntegrations;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ExternalIntegrationsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
    }

    public function test_only_admin_can_open_external_integrations_hub(): void
    {
        $this->get('/admin/integrasi-eksternal')->assertRedirect('/admin/login');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/integrasi-eksternal')
            ->assertOk()
            ->assertSee('Pakta Integritas Elektronik PANRB')
            ->assertSee('Verifikasi Identitas Dukcapil');
    }

    public function test_integration_hub_never_renders_credentials_or_private_endpoints(): void
    {
        Config::set('external_integrations.panrb.base_url', 'https://private-panrb.example');
        Config::set('external_integrations.panrb.api_key', 'PANRB-VERY-SECRET');
        Config::set('citizen_identity.driver', 'dukcapil');
        Config::set('citizen_identity.endpoint', 'https://private-dukcapil.example');
        Config::set('citizen_identity.token', 'DUKCAPIL-VERY-SECRET');
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(ExternalIntegrations::class)
            ->assertSee('Siap diuji')
            ->assertSee('Terpasang di server')
            ->assertDontSee('PANRB-VERY-SECRET')
            ->assertDontSee('DUKCAPIL-VERY-SECRET')
            ->assertDontSee('private-panrb.example')
            ->assertDontSee('private-dukcapil.example')
            ->call('selectIntegration', 'panrb')
            ->assertSee('Tahapan aktivasi')
            ->call('selectIntegration', 'dukcapil')
            ->assertSee('Konfigurasi verifikasi NIK dipusatkan');
    }

    public function test_admin_can_test_configured_panrb_connection_without_logging_secret(): void
    {
        Config::set('external_integrations.panrb.base_url', 'https://panrb.test');
        Config::set('external_integrations.panrb.endpoints.ping', '/sync/ping');
        Config::set('external_integrations.panrb.auth_header', 'X-API-Key');
        Config::set('external_integrations.panrb.api_key', 'PANRB-SECRET');
        Http::fake(['https://panrb.test/sync/ping' => Http::response(['status' => 'ok'])]);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(ExternalIntegrations::class)
            ->call('selectIntegration', 'panrb')
            ->call('testConnection', 'panrb')
            ->assertSee('Koneksi berhasil dan credential diterima server.')
            ->assertDontSee('PANRB-SECRET');

        $this->assertDatabaseHas('external_integration_health_checks', [
            'integration' => 'panrb',
            'successful' => true,
            'http_status' => 200,
            'checked_by' => $admin->id,
        ]);
        $this->assertDatabaseMissing('external_integration_health_checks', ['message' => 'PANRB-SECRET']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://panrb.test/sync/ping'
            && $request->hasHeader('X-API-Key', 'PANRB-SECRET'));
    }
}
