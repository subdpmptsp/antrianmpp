<?php

namespace Tests\Feature;

use App\Filament\Pages\QueueKiosk;
use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\QueueOperatingSetting;
use App\Models\Service;
use App\Models\User;
use App\Services\KioskCatalogService;
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
        QueueOperatingSetting::query()->update([
            'weekly_schedule' => collect(range(1, 7))->map(fn (int $day) => [
                'day' => $day,
                'is_open' => true,
                'opens_at' => '00:00',
                'closes_at' => '23:59',
            ])->all(),
            'cutoff_minutes' => 0,
        ]);
    }

    public function test_kiosk_starts_with_institution_selection_without_zone_or_confirmation(): void
    {
        [$institution] = $this->createInstitutionWithService();

        $this->get(route('public.queue-kiosk'))
            ->assertOk()
            ->assertSee('Instansi apa yang Anda tuju?')
            ->assertSee('Layanan populer')
            ->assertSee('Instansi lainnya')
            ->assertSee($institution->nama_instansi)
            ->assertSee('data-kiosk-root', false)
            ->assertSee('data-kiosk-fullscreen', false)
            ->assertDontSee('data-kiosk-pagination', false)
            ->assertDontSee('data-kiosk-page-next', false)
            ->assertDontSee('Pilih area layanan')
            ->assertDontSee('Konfirmasi pilihan');
    }

    public function test_kiosk_popular_column_uses_current_month_queue_totals(): void
    {
        config()->set('kiosk.popular_institution_count', 1);
        [$popularInstitution, $popularService] = $this->createInstitutionWithService('Instansi Paling Ramai', 'R');
        [$otherInstitution] = $this->createInstitutionWithService('Instansi Lebih Sepi', 'S');

        foreach (range(1, 12) as $number) {
            Queue::query()->create([
                'service_id' => $popularService->id,
                'number' => 'R-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'status' => Queue::STATUS_FINISHED,
            ]);
        }

        $catalog = app(KioskCatalogService::class);
        $columns = $catalog->splitInstitutions($catalog->rankedInstitutions());

        $this->assertSame($popularInstitution->instansi_id, $columns['popular']->first()->instansi_id);
        $this->assertTrue($columns['others']->contains('instansi_id', $otherInstitution->instansi_id));
    }

    public function test_kiosk_guides_users_from_institution_to_direct_print_service_selection(): void
    {
        [$institution, $service] = $this->createInstitutionWithService();

        $this->get(route('public.queue-kiosk', ['instansi' => $institution->instansi_id]))
            ->assertOk()
            ->assertSee('Pilih layanan yang dibutuhkan')
            ->assertSee('Tiket akan langsung dicetak setelah layanan disentuh.')
            ->assertSee($service->name)
            ->assertSee('Belum ada antrean menunggu')
            ->assertSee(route('public.queue-kiosk.select-service', $service), false)
            ->assertSee('queue_request_token', false)
            ->assertSee('instansi_id', false)
            ->assertDontSee('Ya, cetak tiket');
    }

    public function test_institution_without_active_service_is_hidden_from_kiosk(): void
    {
        $hidden = Instansi::query()->create([
            'nama_instansi' => 'Instansi Tanpa Layanan Aktif',
            'zone' => 'ZONA 2',
            'is_active' => true,
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

    private function createInstitutionWithService(
        string $institutionName = 'Dinas Pelayanan Terpadu',
        string $prefix = 'U',
    ): array {
        $institution = Instansi::query()->create([
            'nama_instansi' => $institutionName,
            'deskripsi' => 'Pelayanan administrasi',
            'zone' => 'ZONA 2',
            'is_active' => true,
        ]);
        $service = Service::query()->create([
            'instansi_id' => $institution->instansi_id,
            'name' => 'Konsultasi Perizinan dan Penanaman Modal',
            'prefix' => $prefix,
            'padding' => 3,
            'is_active' => true,
        ]);

        Counter::query()->create([
            'code_loket' => 'UJI-'.strtoupper($prefix),
            'instansi_id' => $institution->instansi_id,
            'service_id' => $service->id,
            'is_active' => true,
        ]);

        return [$institution, $service];
    }
}
