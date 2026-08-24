<?php

namespace Tests\Feature;

use App\Filament\Resources\AttendanceResource\Pages\ListAttendances;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Instansi;
use App\Models\Service;
use App\Models\User;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_active_operator_as_absent_until_first_login(): void
    {
        $this->travelTo(Carbon::parse('2026-08-18 08:00:00'));
        [$instansi, $operator] = $this->createOperatorForInstansi(5);
        $service = app(AttendanceReportService::class);

        $beforeLogin = $service->todayOverview(now());
        $this->assertSame(1, $beforeLogin['absent_operators']);
        $this->assertSame($operator->name, $beforeLogin['absent']->first()['name']);

        Attendance::create([
            'user_id' => $operator->id,
            'instansi_id' => $instansi->instansi_id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'status' => 'present',
        ]);

        $afterLogin = $service->todayOverview(now());
        $this->assertSame(1, $afterLogin['present_operators']);
        $this->assertSame(1, $afterLogin['represented_instansis']);
        $this->assertSame('08:00', $afterLogin['present']->first()['check_in']);
    }

    public function test_holiday_and_institution_workweek_are_excluded_from_absence(): void
    {
        $this->travelTo(Carbon::parse('2026-08-22 08:00:00')); // Saturday
        $this->createOperatorForInstansi(5, 'Instansi Vertikal');
        $this->createOperatorForInstansi(6, 'Instansi Pemkot');

        $saturday = app(AttendanceReportService::class)->todayOverview(now());
        $this->assertSame(1, $saturday['absent_operators']);
        $this->assertSame(1, $saturday['off']->count());

        Holiday::create([
            'date' => now()->toDateString(),
            'name' => 'Libur Nasional',
            'type' => 'national',
        ]);

        $holiday = app(AttendanceReportService::class)->todayOverview(now());
        $this->assertSame(0, $holiday['absent_operators']);
        $this->assertSame(2, $holiday['off']->count());
    }

    public function test_monthly_recap_uses_effective_working_days_and_institution_representation(): void
    {
        $this->travelTo(Carbon::parse('2026-08-05 12:00:00')); // Wednesday
        [$instansi, $operator] = $this->createOperatorForInstansi(5);
        Holiday::create(['date' => '2026-08-03', 'name' => 'Libur', 'type' => 'national']);

        foreach (['2026-08-04', '2026-08-05'] as $date) {
            Attendance::create([
                'user_id' => $operator->id,
                'instansi_id' => $instansi->instansi_id,
                'date' => $date,
                'check_in' => '08:00:00',
                'status' => 'present',
            ]);
        }

        $recap = app(AttendanceReportService::class)->monthlyRecap(2026);
        $august = $recap['instansis']->first()['months'][8];

        $this->assertSame(2, $august['total_days']);
        $this->assertSame(2, $august['days_present']);
        $this->assertSame(100, $august['percentage']);
        $this->assertNull($recap['instansis']->first()['months'][9]);
    }

    public function test_admin_can_open_attendance_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/admin/attendances')->assertOk();
        $this->actingAs($admin)->get('/admin/holidays')->assertOk();
    }

    public function test_history_screen_rejects_an_excessive_date_range(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Livewire::actingAs($admin)
            ->test(ListAttendances::class)
            ->set('historyFrom', '2026-01-01')
            ->set('historyUntil', '2026-06-01')
            ->call('applyHistoryFilters')
            ->assertHasErrors(['historyUntil']);
    }

    /** @return array{Instansi, User} */
    private function createOperatorForInstansi(int $workDays, string $name = 'Instansi Uji'): array
    {
        $instansi = Instansi::create([
            'nama_instansi' => $name,
            'work_days_per_week' => $workDays,
        ]);
        $service = Service::create([
            'instansi_id' => $instansi->instansi_id,
            'name' => 'Layanan '.$name,
            'prefix' => fake()->unique()->lexify('??'),
            'padding' => 3,
            'is_active' => true,
        ]);
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'service_id' => $service->id,
            'username' => fake()->unique()->userName(),
        ]);

        return [$instansi, $operator];
    }
}
