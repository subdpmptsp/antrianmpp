<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionConfigurationAuditTest extends TestCase
{
    public function test_safe_production_configuration_passes_audit(): void
    {
        $this->configureSafeProduction();

        $exitCode = Artisan::call('app:production-audit');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('lolos pemeriksaan wajib', Artisan::output());
    }

    public function test_unsafe_environment_is_rejected_without_printing_credentials(): void
    {
        $this->configureSafeProduction();
        config([
            'app.env' => 'local',
            'app.debug' => true,
            'app.url' => 'http://localhost',
            'database.connections.mysql.database' => 'antrianmpp_testing',
            'database.connections.mysql.password' => 'rahasia-jangan-ditampilkan',
        ]);

        $exitCode = Artisan::call('app:production-audit');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Deployment dihentikan', $output);
        $this->assertStringContainsString('Database bukan database pengujian', $output);
        $this->assertStringNotContainsString('rahasia-jangan-ditampilkan', $output);
    }

    public function test_enabled_device_authorization_requires_every_device_token(): void
    {
        $this->configureSafeProduction();
        config([
            'devices.auth_enabled' => true,
            'devices.kiosk.token' => null,
            'devices.tv.tokens.3' => null,
        ]);

        $exitCode = Artisan::call('app:production-audit');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Token perangkat kiosk', $output);
        $this->assertStringContainsString('TV zona 3', $output);
    }

    public function test_placeholder_production_values_are_rejected(): void
    {
        $this->configureSafeProduction();
        config([
            'app.url' => 'https://CHANGE-ME.invalid',
            'database.connections.mysql.password' => 'CHANGE_ME',
            'devices.auth_enabled' => true,
            'devices.kiosk.token' => 'CHANGE_ME',
        ]);

        $exitCode = Artisan::call('app:production-audit');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('APP_URL mengarah ke alamat server', $output);
        $this->assertStringContainsString('Kredensial database produksi', $output);
        $this->assertStringContainsString('Token perangkat kiosk', $output);
    }

    private function configureSafeProduction(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:abcdefghijklmnopqrstuvwxyz0123456789=',
            'app.url' => 'https://antrian.example.go.id',
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'antrianmpp',
            'database.connections.mysql.username' => 'antrian_app',
            'database.connections.mysql.password' => 'database-secret',
            'logging.channels.stack.channels' => ['daily'],
            'session.secure' => true,
            'devices.auth_enabled' => false,
        ]);
    }
}
