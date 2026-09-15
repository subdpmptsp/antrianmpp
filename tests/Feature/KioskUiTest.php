<?php

namespace Tests\Feature;

use App\Filament\Pages\QueueKiosk;
use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\QueueOperatingSetting;
use App\Models\OnlineQueueCheckinChallenge;
use App\Models\OnlineQueueSession;
use App\Models\OnlineQueueSetting;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\KioskCatalogService;
use App\Services\ServiceQueueAvailabilityService;
use Database\Seeders\TestingSeeder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee($institution->nama_instansi)
            ->assertSee('data-kiosk-root', false)
            ->assertSee('data-kiosk-fullscreen', false)
            ->assertDontSee('data-kiosk-pagination', false)
            ->assertDontSee('data-kiosk-page-next', false)
            ->assertSee('Check-in Antrean Online')
            ->assertSee(route('public.queue-kiosk', ['online_checkin' => 1]), false)
            ->assertDontSee('Pilih area layanan')
            ->assertDontSee('Konfirmasi pilihan');
    }

    public function test_kiosk_uses_configured_header_logos_and_sizes(): void
    {
        Storage::fake('public');

        Setting::query()->create([
            'name' => 'MPP Uji',
            'address' => 'Surabaya',
            'phone' => '031-0000000',
            'image' => 'branding/logo-utama/default.png',
            'kiosk_logo' => 'branding/kiosk/utama.png',
            'kiosk_logo_size' => 'large',
            'kiosk_office_logo' => 'branding/kiosk/pendamping.png',
            'kiosk_office_logo_size' => 'small',
        ]);

        $this->get(route('public.queue-kiosk'))
            ->assertOk()
            ->assertSee('/storage/branding/kiosk/utama.png', false)
            ->assertSee('/storage/branding/kiosk/pendamping.png', false)
            ->assertSee('queue-kiosk__logo--large', false)
            ->assertSee('queue-kiosk__logo--small', false);
    }

    public function test_online_checkin_stays_inside_kiosk_shell_and_can_return_home(): void
    {
        $this->createInstitutionWithService();

        $this->get(route('public.queue-kiosk', ['online_checkin' => 1]))
            ->assertOk()
            ->assertSee('data-kiosk-root', false)
            ->assertSee('Check-in antrean online')
            ->assertSee('Kembali ke daftar instansi')
            ->assertSee(route('public.queue-kiosk'), false);
    }

    public function test_enabled_online_checkin_renders_live_qr_inside_kiosk_shell(): void
    {
        [, $service] = $this->createInstitutionWithService();
        OnlineQueueSetting::current()->update([
            'global_enabled' => true,
            'regular_enabled' => true,
            'pilot_mode' => false,
        ]);
        OnlineQueueSession::query()->create([
            'service_id' => $service->id,
            'day_of_week' => now('Asia/Jakarta')->isoWeekday(),
            'starts_at' => '08:00',
            'ends_at' => '16:00',
            'quota' => 10,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);

        $this->get(route('public.queue-kiosk', ['online_checkin' => 1]))
            ->assertOk()
            ->assertSee('data-online-checkin', false)
            ->assertSee('Pindai untuk check-in')
            ->assertSee('data-online-checkin-timer', false)
            ->assertSee('Kembali ke daftar instansi');

        $this->assertDatabaseCount((new OnlineQueueCheckinChallenge)->getTable(), 1);
    }

    public function test_online_checkin_hides_qr_when_no_online_session_is_active(): void
    {
        [, $service] = $this->createInstitutionWithService();
        OnlineQueueSetting::current()->update([
            'global_enabled' => true,
            'regular_enabled' => true,
            'pilot_mode' => false,
        ]);
        OnlineQueueSession::query()->create([
            'service_id' => $service->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'quota' => 10,
            'status' => OnlineQueueSession::STATUS_DRAFT,
        ]);

        $this->get(route('public.queue-kiosk', ['online_checkin' => 1]))
            ->assertOk()
            ->assertSee('Belum ada antrean online aktif.')
            ->assertSee('QR check-in tidak ditampilkan.')
            ->assertDontSee('<div class="queue-kiosk__online-checkin"', false);

        $this->assertDatabaseCount((new OnlineQueueCheckinChallenge)->getTable(), 0);
    }

    public function test_friday_prayer_break_shows_kiosk_modal_and_blocks_ticket_issuance(): void
    {
        [, $service] = $this->createInstitutionWithService();
        $service->update(['is_accepting_queues' => true]);
        QueueOperatingSetting::query()->update([
            'weekly_schedule' => collect(range(1, 7))->map(fn (int $day) => [
                'day' => $day,
                'is_open' => true,
                'opens_at' => '08:00',
                'closes_at' => '16:00',
                'break_starts_at' => $day === 5 ? '11:30' : null,
                'break_ends_at' => $day === 5 ? '13:00' : null,
            ])->all(),
            'cutoff_minutes' => 0,
        ]);
        $fridayNoon = Carbon::parse('2026-09-11 12:00:00', 'Asia/Jakarta');
        Carbon::setTestNow($fridayNoon);

        try {
            $this->get(route('public.queue-kiosk'))
                ->assertOk()
                ->assertSee('Jeda Istirahat Salat Jumat')
                ->assertSee('Pengambilan nomor antrean dihentikan sementara.')
                ->assertSee('pukul 13.00 WIB')
                ->assertSee('data-kiosk-break-until', false);

            $availability = app(ServiceQueueAvailabilityService::class)->evaluate($service, $fridayNoon);
            $this->assertFalse($availability['available']);
            $this->assertSame('friday_prayer_break', $availability['code']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_global_operational_closure_uses_next_opening_from_weekly_schedule(): void
    {
        $this->createInstitutionWithService();
        QueueOperatingSetting::query()->update([
            'weekly_schedule' => collect(range(1, 7))->map(fn (int $day) => [
                'day' => $day,
                'is_open' => $day !== 7,
                'opens_at' => $day === 6 ? '07:00' : '07:30',
                'closes_at' => $day === 5 ? '14:00' : '16:00',
                'break_starts_at' => null,
                'break_ends_at' => null,
            ])->all(),
            'cutoff_minutes' => 0,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-09-11 14:05:00', 'Asia/Jakarta'));

        try {
            $this->get(route('public.queue-kiosk'))
                ->assertOk()
                ->assertSee('Pelayanan Hari Ini Telah Selesai')
                ->assertSee('Pengambilan nomor antrean untuk hari ini telah ditutup.')
                ->assertSee('Sabtu · pukul 07.00 WIB')
                ->assertSee('data-kiosk-operational-closure', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_kiosk_shows_editable_fullscreen_message_before_opening_time(): void
    {
        $this->createInstitutionWithService();
        QueueOperatingSetting::query()->update([
            'weekly_schedule' => collect(range(1, 7))->map(fn (int $day) => [
                'day' => $day,
                'is_open' => true,
                'opens_at' => '07:30',
                'closes_at' => '16:00',
            ])->all(),
            'cutoff_minutes' => 0,
            'kiosk_messages' => [
                'pre_opening' => [
                    'title' => 'Mohon Menunggu',
                    'body' => 'Antrean dimulai pukul {jam_buka} WIB.',
                    'footer' => 'Terima kasih.',
                ],
            ],
        ]);
        Carbon::setTestNow(Carbon::parse('2026-09-15 07:00:00', 'Asia/Jakarta'));

        try {
            $this->get(route('public.queue-kiosk'))
                ->assertOk()
                ->assertSee('Mohon Menunggu')
                ->assertSee('Antrean dimulai pukul 07.30 WIB.')
                ->assertSee('data-kiosk-break-until', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_kiosk_catalog_order_does_not_use_current_month_queue_totals(): void
    {
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
        $orderedInstitutionIds = collect($catalog->institutionEntries($catalog->rankedInstitutions()))
            ->where('type', 'institution')
            ->pluck('instansi_id')
            ->filter(fn (int $id): bool => in_array($id, [$popularInstitution->instansi_id, $otherInstitution->instansi_id], true))
            ->values();

        $this->assertSame([$otherInstitution->instansi_id, $popularInstitution->instansi_id], $orderedInstitutionIds->all());
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
