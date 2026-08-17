<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Service;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KioskUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TestingSeeder::class);
    }

    public function test_kiosk_starts_with_zone_selection_and_shared_controls(): void
    {
        $this->get(route('public.queue-kiosk'))
            ->assertOk()
            ->assertSee('Pilih area layanan')
            ->assertSee('data-kiosk-root', false)
            ->assertSee('data-kiosk-fullscreen', false)
            ->assertSee('Konfirmasi pilihan');
    }

    public function test_kiosk_guides_users_from_institution_to_service_selection(): void
    {
        $zone = Counter::query()->findOrFail(20);
        $firstInstitution = Instansi::query()->create([
            'nama_instansi' => 'Dinas Pelayanan Terpadu',
            'deskripsi' => 'Pelayanan administrasi',
            'counter_id' => $zone->id,
        ]);
        Instansi::query()->create([
            'nama_instansi' => 'Dinas Kependudukan',
            'deskripsi' => 'Pelayanan kependudukan',
            'counter_id' => $zone->id,
        ]);
        $service = Service::query()->create([
            'instansi_id' => $firstInstitution->instansi_id,
            'name' => 'Konsultasi Perizinan dan Penanaman Modal',
            'prefix' => 'U',
            'padding' => 3,
            'is_active' => true,
        ]);

        $this->get(route('public.queue-kiosk', ['zona' => 2]))
            ->assertOk()
            ->assertSee('Pilih instansi tujuan')
            ->assertSee($firstInstitution->nama_instansi);

        $this->get(route('public.queue-kiosk', [
            'zona' => 2,
            'instansi' => $firstInstitution->instansi_id,
        ]))
            ->assertOk()
            ->assertSee('Pilih layanan')
            ->assertSee($service->name)
            ->assertSee('Sentuh untuk mengambil nomor')
            ->assertSee(route('public.queue-kiosk.select-service', $service), false)
            ->assertSee('queue_request_token', false);
    }
}
