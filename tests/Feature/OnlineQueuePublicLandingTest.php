<?php

namespace Tests\Feature;

use App\Models\OnlineQueueSession;
use App\Models\OnlineQueueSetting;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineQueuePublicLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
    }

    public function test_public_landing_shows_lookup_when_no_online_queue_is_active(): void
    {
        $this->get(route('online-queue.index'))
            ->assertOk()
            ->assertSee('Ambil antrean')
            ->assertSee('sebelum datang ke')
            ->assertSee('MPP Siola')
            ->assertSee('Cari Reservasi Saya')
            ->assertSee('Saat ini antrean online belum aktif')
            ->assertSee('Belum tersedia')
            ->assertDontSee('name="nik"', false);
    }

    public function test_public_landing_links_to_registration_when_a_session_is_available(): void
    {
        OnlineQueueSetting::current()->update([
            'global_enabled' => true,
            'regular_enabled' => true,
            'pilot_mode' => false,
        ]);
        OnlineQueueSession::query()->create([
            'service_id' => 1001,
            'day_of_week' => now('Asia/Jakarta')->isoWeekday(),
            'starts_at' => '00:00',
            'ends_at' => '23:59',
            'quota' => 10,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);

        $this->get(route('online-queue.index'))
            ->assertOk()
            ->assertSee('Ambil Antrean Sekarang')
            ->assertSee(route('online-queue.registration'), false)
            ->assertSee('Layanan Uji ZONA 1')
            ->assertSee('Kuota tersisa:')
            ->assertSee('sesi')
            ->assertSee('00:00 – 23:59 WIB');

        $this->get(route('online-queue.registration'))
            ->assertOk()
            ->assertSee('Data reservasi')
            ->assertSee('name="nik"', false);
    }
}
