<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AttendanceListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_attendance_is_recorded_once_on_login_only(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        Event::dispatch(new Login('web', $operator, false));
        Event::dispatch(new Login('web', $operator, false));

        foreach (range(1, 5) as $_) {
            Event::dispatch(new Authenticated('web', $operator));
        }

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $operator->id,
            'date' => now()->toDateString(),
        ]);
    }

    public function test_admin_login_does_not_create_attendance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Event::dispatch(new Login('web', $admin, false));

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_operator_logout_records_checkout_and_working_minutes(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(8));
        $operator = User::factory()->create(['role' => 'operator']);
        Event::dispatch(new Login('web', $operator, false));

        $this->travel(90)->minutes();
        Event::dispatch(new Logout('web', $operator));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $operator->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'check_out' => '09:30:00',
            'working_hours' => 90,
        ]);
    }

    public function test_admin_logout_does_not_create_attendance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Event::dispatch(new Logout('web', $admin));

        $this->assertDatabaseCount('attendances', 0);
    }
}
