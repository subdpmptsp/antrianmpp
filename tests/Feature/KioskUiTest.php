<?php

namespace Tests\Feature;

use App\Filament\Pages\QueueKiosk;
use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KioskUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TestingSeeder::class);
    }

    public function test_kiosk_starts_with_institution_selection_without_zone_or_confirmation(): void
    {
        [$institution] = $this->createInstitutionWithService();

        $this->get(route('public.queue-kiosk'))
            ->assertOk()
            ->assertSee('Instansi apa yang Anda tuju?')
            ->assertSee($institution->nama_instansi)
            ->assertSee('data-kiosk-root', false)
            ->assertSee('data-kiosk-fullscreen', false)
            ->assertDontSee('Pilih area layanan')
            ->assertDontSee('Konfirmasi pilihan');
    }

    public function test_kiosk_guides_users_from_institution_to_direct_print_service_selection(): void
    {
        [$institution, $service] = $this->createInstitutionWithService();

        $this->get(route('public.queue-kiosk', ['instansi' => $institution->instansi_id]))
            ->assertOk()
            ->assertSee('Pilih layanan yang dibutuhkan')
            ->assertSee('Tiket akan langsung dicetak setelah layanan disentuh')
            ->assertSee($service->name)
            ->assertSee('Sentuh untuk mencetak tiket')
            ->assertSee(route('public.queue-kiosk.select-service', $service), false)
            ->assertSee('queue_request_token', false)
            ->assertSee('instansi_id', false)
            ->assertDontSee('Ya, cetak tiket');
    }

    public function test_institution_without_active_service_is_hidden_from_kiosk(): void
    {
        $zone = Counter::query()->findOrFail(20);
        $hidden = Instansi::query()->create([
            'nama_instansi' => 'Instansi Tanpa Layanan Aktif',
            'counter_id' => $zone->id,
        ]);
        Service::query()->create([
            'instansi_id' => $hidden->instansi_id,
            'name' => 'Layanan Nonaktif',
            'prefix' => 'N',
            'padding' => 3,
            'is_active' => false,
        ]);

        $this->get(route('public.queue-kiosk'))
            ->assertOk()
            ->assertDontSee($hidden->nama_instansi);
    }

    public function test_admin_livewire_kiosk_uses_the_same_direct_print_flow(): void
    {
        [$institution, $service] = $this->createInstitutionWithService();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(QueueKiosk::class)
            ->call('selectInstansi', $institution->instansi_id)
            ->assertSet('selectedInstansi', $institution->instansi_id)
            ->assertSee($service->name)
            ->call('selectService', $service->id)
            ->assertDispatched('ticket-ready');

        $this->assertDatabaseHas('queues', [
            'service_id' => $service->id,
            'status' => Queue::STATUS_PRINTING,
        ]);
    }

    private function createInstitutionWithService(): array
    {
        $zone = Counter::query()->findOrFail(20);
        $institution = Instansi::query()->create([
            'nama_instansi' => 'Dinas Pelayanan Terpadu',
            'deskripsi' => 'Pelayanan administrasi',
            'counter_id' => $zone->id,
        ]);
        $service = Service::query()->create([
            'instansi_id' => $institution->instansi_id,
            'name' => 'Konsultasi Perizinan dan Penanaman Modal',
            'prefix' => 'U',
            'padding' => 3,
            'is_active' => true,
        ]);

        return [$institution, $service];
    }
}
