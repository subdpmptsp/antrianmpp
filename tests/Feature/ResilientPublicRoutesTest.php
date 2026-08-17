<?php

namespace Tests\Feature;

use App\Models\Counter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResilientPublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tv_zone_uses_fallback_name_when_configured_counter_is_missing(): void
    {
        Counter::query()->whereKey(5)->delete();

        $this->get('/tv1')
            ->assertOk()
            ->assertSee('ZONA 1');
    }

    public function test_tv_zone_can_resolve_an_explicitly_configured_counter(): void
    {
        $counter = Counter::query()->create([
            'name' => 'TV LANTAI KHUSUS',
            'is_active' => true,
        ]);
        config()->set('tv.zones.1.counter_id', $counter->id);

        $this->get('/tv1')
            ->assertOk()
            ->assertSee('TV LANTAI KHUSUS');
    }

    public function test_queue_status_without_id_shows_controlled_empty_state(): void
    {
        $this->get('/queue-status')
            ->assertOk()
            ->assertSee('Status Antrian Tidak Tersedia');
    }

    public function test_queue_status_with_invalid_id_shows_controlled_empty_state(): void
    {
        $this->get('/queue-status?id=invalid!')
            ->assertOk()
            ->assertSee('Status Antrian Tidak Tersedia');
    }
}
