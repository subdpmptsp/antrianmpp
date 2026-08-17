<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\DeviceRegistration;
use App\Services\TvZoneResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'devices.auth_enabled' => true,
            'devices.kiosk.token' => 'kiosk-secret-token',
            'devices.tv.tokens.1' => 'tv-zone-one-token',
            'devices.tv.tokens.2' => 'tv-zone-two-token',
            'tv.zones.1.counter_id' => null,
            'tv.zones.2.counter_id' => null,
        ]);
    }

    public function test_kiosk_rejects_unknown_device_and_remembers_valid_device_session(): void
    {
        $this->get('/kiosk/cetak-antrian')->assertForbidden();

        $this->get('/kiosk/cetak-antrian?device_token=kiosk-secret-token')
            ->assertRedirect('/kiosk/cetak-antrian');

        $this->get('/kiosk/cetak-antrian')->assertOk();

        $registration = DeviceRegistration::query()->where('device_key', 'kiosk')->firstOrFail();
        $this->assertSame('kiosk', $registration->device_type);
        $this->assertNotNull($registration->last_seen_at);
    }

    public function test_kiosk_accepts_device_token_header(): void
    {
        $this->withHeader('X-Device-Token', 'kiosk-secret-token')
            ->get('/kiosk/cetak-antrian')
            ->assertOk();
    }

    public function test_tv_token_is_bound_to_its_own_zone_and_api(): void
    {
        $zoneOne = Counter::create(['name' => 'ZONA 1', 'is_active' => true]);
        $zoneTwo = Counter::create(['name' => 'ZONA 2', 'is_active' => true]);

        $this->get('/tv1')->assertForbidden();
        $this->get('/tv1?device_token=tv-zone-one-token')
            ->assertRedirect('/tv1');
        $this->get('/tv1')->assertOk();

        $registration = DeviceRegistration::query()->where('device_key', 'tv:1')->firstOrFail();
        $this->assertSame(1, $registration->zone_number);

        $this->assertSame($zoneOne->id, app(TvZoneResolver::class)->resolve(1)?->id);
        $this->get("/api/tv-display/zone/{$zoneOne->id}/queues")->assertOk();
        $this->get("/api/tv-display/zone/{$zoneTwo->id}/queues")->assertForbidden();
        $this->get('/tv2')->assertForbidden();
    }

    public function test_direct_zone_display_accepts_only_matching_zone_token(): void
    {
        $zoneOne = Counter::create(['name' => 'ZONA 1', 'is_active' => true]);
        $zoneTwo = Counter::create(['name' => 'ZONA 2', 'is_active' => true]);

        $this->assertSame($zoneOne->id, app(TvZoneResolver::class)->resolve(1)?->id);
        $this->get("/tv-display/zona/{$zoneOne->id}?device_token=tv-zone-one-token")->assertOk();
        $this->get("/tv-display/zona/{$zoneTwo->id}")->assertForbidden();
    }

    public function test_enabled_mode_fails_closed_when_device_token_is_not_configured(): void
    {
        config()->set('devices.kiosk.token', null);

        $this->get('/kiosk/cetak-antrian')->assertStatus(503);
    }
}
