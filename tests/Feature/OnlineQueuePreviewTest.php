<?php

namespace Tests\Feature;

use App\Exports\OnlineQueueReservationsExport;
use App\Exports\QueueChannelRecapExport;
use App\Filament\Pages\OnlineQueuePreview;
use App\Livewire\OnlineQueueParticipantTable;
use App\Models\OnlineQueueReservation;
use App\Models\OnlineQueueSession;
use App\Models\OnlineQueueSetting;
use App\Models\Queue;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class OnlineQueuePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('turnstile.enabled', false);
        $this->seed(TestingSeeder::class);
    }

    public function test_admin_can_open_online_queue_frontend_preview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/antrean-online')
            ->assertOk()
            ->assertSee('Satu pusat kendali untuk antrean online')
            ->assertSee('Layanan Harian')
            ->assertSee('/admin/integrasi-eksternal', false)
            ->assertSee('target="_blank"', false)
            ->assertDontSee('Event Khusus');

        Livewire::actingAs($admin)->test(OnlineQueuePreview::class)
            ->call('selectTab', 'participants')
            ->assertSee('Export Excel')
            ->assertSee('Cari kode, nama, atau WhatsApp')
            ->assertDontSee('Lingkaran = tanggal dipilih')
            ->mountAction('configurePilot')
            ->assertActionMounted('configurePilot')
            ->call('unmountAction')
            ->mountAction('exportChannelRecap')
            ->assertActionMounted('exportChannelRecap');

    }

    public function test_public_registration_preview_is_available_without_writing_data(): void
    {
        $this->get(route('online-queue.preview.registration'))
            ->assertOk()
            ->assertSee('Dalam tahap pengembangan')
            ->assertSee('NIK wajib');
    }

    public function test_global_header_actions_open_from_every_tab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(OnlineQueuePreview::class);

        foreach (['dashboard', 'sessions', 'participants', 'checkin', 'links', 'settings'] as $tab) {
            $component->call('selectTab', $tab)
                ->mountAction('configurePilot')
                ->assertActionMounted('configurePilot')
                ->call('unmountAction')
                ->mountAction('exportChannelRecap')
                ->assertActionMounted('exportChannelRecap')
                ->call('unmountAction');
        }
    }

    public function test_pilot_options_show_institution_service_and_code_and_can_stay_empty_when_inactive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(OnlineQueuePreview::class);
        $options = $component->instance()->pilotServiceOptions();

        $this->assertSame(
            'Instansi Uji ZONA 1 · Layanan Uji ZONA 1 · Kode antrean 1A · Loket TEST-5',
            $options['Instansi Uji ZONA 1'][1001],
        );

        $component->mountAction('configurePilot')
            ->setActionData([
                'pilot_service_ids' => [],
                'booking_window_days' => 7,
                'activate' => false,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $settings = OnlineQueueSetting::current()->fresh();
        $this->assertSame([], $settings->pilot_service_ids);
        $this->assertFalse($settings->global_enabled);
        $this->assertFalse($settings->regular_enabled);
    }

    public function test_daily_service_screen_explains_weekly_and_special_schedules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(OnlineQueuePreview::class)
            ->call('selectTab', 'sessions')
            ->assertSee('Jadwal mingguan')
            ->assertSee('pengalihan kanal')
            ->assertSee('Online Penuh')
            ->assertSee('Antrean onsite berjalan normal');
    }

    public function test_participant_table_uses_server_side_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $session = OnlineQueueSession::query()->create([
            'service_id' => 1001,
            'day_of_week' => 5,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'quota' => 50,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);

        foreach (range(1, 26) as $number) {
            OnlineQueueReservation::query()->create([
                'online_queue_session_id' => $session->id,
                'service_id' => 1001,
                'service_date' => today()->toDateString(),
                'booking_code' => sprintf('RSV-PAGE-%03d', $number),
                'access_token' => hash('sha256', 'access-'.$number),
                'nik' => sprintf('357800000000%04d', $number),
                'nik_hash' => hash('sha256', 'nik-'.$number),
                'nik_last_four' => sprintf('%04d', $number),
                'name' => 'Pemohon '.$number,
                'phone' => sprintf('08120000%04d', $number),
                'status' => OnlineQueueReservation::STATUS_BOOKED,
            ]);
        }

        $component = Livewire::actingAs($admin)->test(OnlineQueueParticipantTable::class);
        $component->assertSeeHtml('download="download"');
        $component->assertDontSeeHtml('wire:navigate');
        $records = $component->instance()->getTableRecords();

        $this->assertCount(25, $records);
        $this->assertSame(26, $records->total());

        $component->set('tableRecordsPerPage', 10);
        $this->assertCount(10, $component->instance()->getTableRecords());

        $component->set('reservationDate', today()->toDateString().' 00:00:00')
            ->assertSet('reservationDate', today()->toDateString().' 00:00:00');
        $this->assertSame(today()->toDateString(), $component->instance()->reservationDateFilter());
        $this->assertCount(10, $component->instance()->getTableRecords());

        $component->set('reservationStatus', OnlineQueueReservation::STATUS_BOOKED)
            ->set('reservationServiceId', '1001')
            ->set('reservationSearch', 'RSV-PAGE');
        $this->assertSame(26, $component->instance()->getTableRecords()->total());

        $reservation = OnlineQueueReservation::query()->firstOrFail();
        $component->mountTableAction('detail', $reservation)
            ->assertTableActionMounted('detail')
            ->assertSee($reservation->booking_code)
            ->call('unmountTableAction')
            ->assertTableActionNotMounted('detail');
    }

    public function test_links_are_locked_when_all_online_sessions_are_drafts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        OnlineQueueSetting::current()->update([
            'global_enabled' => true,
            'regular_enabled' => true,
            'pilot_mode' => false,
        ]);
        OnlineQueueSession::query()->create([
            'service_id' => 1001,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'quota' => 10,
            'status' => OnlineQueueSession::STATUS_DRAFT,
        ]);

        Livewire::actingAs($admin)->test(OnlineQueuePreview::class)
            ->call('selectTab', 'links')
            ->assertSee('Saat ini belum ada antrean online yang berjalan.')
            ->assertSee('Formulir publik terkunci')
            ->assertSee('QR check-in belum tersedia')
            ->assertSee('Layar kiosk belum tersedia');
    }

    public function test_identity_configuration_frontend_is_clear_and_never_exposes_secret(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Config::set('citizen_identity.enabled', false);
        Config::set('citizen_identity.driver', 'dukcapil');
        Config::set('citizen_identity.endpoint', 'https://dukcapil.internal.example/verify');
        Config::set('citizen_identity.token', 'SECRET-MUST-NOT-REACH-BROWSER');
        Config::set('citizen_identity.failure_mode', 'manual_review');

        Livewire::actingAs($admin)->test(OnlineQueuePreview::class)
            ->call('selectTab', 'settings')
            ->assertSee('Konfigurasi server tersedia')
            ->assertSee('Verifikasi NIK otomatis')
            ->assertSee('Antre verifikasi manual admin')
            ->assertSee('Tersimpan aman di server')
            ->assertSee('Perlindungan data kependudukan')
            ->assertDontSee('SECRET-MUST-NOT-REACH-BROWSER')
            ->assertDontSee('dukcapil.internal.example');
    }

    public function test_kiosk_and_phone_checkin_previews_are_available(): void
    {
        $this->get(route('online-queue.preview.kiosk'))
            ->assertOk()
            ->assertSee('Check-in antrean online');

        $this->get(route('online-queue.preview.checkin', 'SIMULASI'))
            ->assertOk()
            ->assertSee('Dalam tahap pengembangan')
            ->assertSee('Konfirmasi saya sudah tiba');
    }

    public function test_online_queue_export_requires_admin_and_returns_excel(): void
    {
        $this->get(route('export.online-queue-reservations'))->assertRedirect('/admin/login');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('export.online-queue-reservations', ['status' => 'all']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload('pendaftar-antrean-online-'.now('Asia/Jakarta')->format('Y-m-d').'.xlsx');
    }

    public function test_online_queue_export_is_formatted_and_matches_participant_filters(): void
    {
        $session = OnlineQueueSession::query()->create([
            'service_id' => 1001,
            'day_of_week' => 5,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'quota' => 10,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);
        $reservation = OnlineQueueReservation::query()->create([
            'online_queue_session_id' => $session->id,
            'service_id' => 1001,
            'service_date' => '2026-09-11',
            'booking_code' => 'RSV-EXPORT-001',
            'access_token' => hash('sha256', 'export-access'),
            'nik' => '3578000000004321',
            'nik_hash' => hash('sha256', 'export-nik'),
            'nik_last_four' => '4321',
            'name' => 'Pemohon Ekspor',
            'phone' => '081234567899',
            'status' => OnlineQueueReservation::STATUS_CHECKED_IN,
            'checked_in_at' => '2026-09-11 08:15:00',
        ]);

        $export = new OnlineQueueReservationsExport('2026-09-11', 'checked_in', 1001, '081234567899');
        $this->assertSame([$reservation->id], $export->query()->pluck('id')->all());

        $temporaryPath = tempnam(sys_get_temp_dir(), 'online-queue-export-');

        try {
            file_put_contents($temporaryPath, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            $spreadsheet = IOFactory::load($temporaryPath);
            $sheet = $spreadsheet->getActiveSheet();

            $this->assertSame('Pendaftar Antrean Online', $sheet->getTitle());
            $this->assertSame('Kode Booking', $sheet->getCell('A1')->getValue());
            $this->assertSame('RSV-EXPORT-001', $sheet->getCell('A2')->getValue());
            $this->assertSame('************4321', $sheet->getCell('D2')->getValue());
            $this->assertSame('081234567899', $sheet->getCell('E2')->getValue());
            $this->assertSame('Hadir / sudah check-in', $sheet->getCell('I2')->getValue());
            $this->assertSame('A2', $sheet->getFreezePane());
            $this->assertSame('A1:K2', $sheet->getAutoFilter()->getRange());
            $this->assertSame('FF174D9B', $sheet->getStyle('A1')->getFill()->getStartColor()->getARGB());
        } finally {
            if (is_string($temporaryPath) && file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    public function test_channel_recap_separates_onsite_online_canceled_and_no_show(): void
    {
        $date = Carbon::parse('2026-09-10', 'Asia/Jakarta');
        Carbon::setTestNow($date);
        $serviceId = 1001;
        Queue::query()->create(['service_id' => $serviceId, 'number' => '1A-001', 'status' => Queue::STATUS_WAITING, 'source' => 'kiosk', 'created_at' => $date, 'updated_at' => $date]);
        Queue::query()->create(['service_id' => $serviceId, 'number' => '1A-002', 'status' => Queue::STATUS_WAITING, 'source' => 'online', 'created_at' => $date, 'updated_at' => $date]);
        $session = OnlineQueueSession::query()->create([
            'service_id' => $serviceId,
            'day_of_week' => 4,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'quota' => 10,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);

        foreach ([OnlineQueueReservation::STATUS_CANCELED, OnlineQueueReservation::STATUS_EXPIRED] as $index => $status) {
            OnlineQueueReservation::query()->create([
                'online_queue_session_id' => $session->id,
                'service_id' => $serviceId,
                'service_date' => $date->toDateString(),
                'booking_code' => 'BOOK-'.($index + 1),
                'access_token' => str_repeat((string) ($index + 1), 64),
                'nik' => '357812341234123'.($index + 4),
                'nik_hash' => hash('sha256', 'nik-'.$index),
                'nik_last_four' => '123'.($index + 4),
                'name' => 'Pemohon uji',
                'phone' => '081234567890',
                'status' => $status,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        $rows = (new QueueChannelRecapExport($date->toDateString(), $date->toDateString(), $serviceId))->collection();
        $this->assertSame([1, 1, 2, 1, 1], array_slice($rows->first(), 4));

        $this->get(route('export.queue-channel-recap'))->assertRedirect('/admin/login');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('export.queue-channel-recap', ['from' => $date->toDateString(), 'to' => $date->toDateString()]))
            ->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
