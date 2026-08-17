<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_discovers_routes_and_never_prints_device_tokens(): void
    {
        config()->set([
            'devices.auth_enabled' => true,
            'devices.kiosk.token' => 'inventory-kiosk-secret',
            'devices.tv.tokens.1' => 'inventory-tv-secret',
        ]);

        $exitCode = Artisan::call('app:system-inventory', ['--json' => true]);
        $output = Artisan::output();
        $inventory = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertSame(config('app.name'), $inventory['application']['name']);
        $this->assertTrue($inventory['devices']['token_readiness']['kiosk']);
        $this->assertContains('public.queue-kiosk', array_column($inventory['endpoints'], 'name'));
        $this->assertStringNotContainsString('inventory-kiosk-secret', $output);
        $this->assertStringNotContainsString('inventory-tv-secret', $output);
    }
}
