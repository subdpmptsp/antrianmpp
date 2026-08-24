<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_id_query_parameter_cannot_select_a_filament_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($admin)->get('/admin?user_id='.$otherUser->id);

        $response->assertRedirect();
        $this->assertSame($admin->id, auth()->id());
        $this->assertStringNotContainsString('_filament_user_id', implode(';', $response->headers->all('set-cookie')));
    }

    public function test_operator_must_login_again_after_calendar_day_changes(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $response = $this->actingAs($operator)
            ->withSession([
                'operator_session_started_at' => now()->subDay()->toIso8601String(),
                'operator_session_date' => now()->subDay()->toDateString(),
            ])
            ->get('/admin');

        $response->assertRedirect(route('filament.admin.auth.login'));
        $this->assertGuest();
    }
}
