<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OperatorPasswordRotationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_recently_changed_hashed_operator_password_passes_audit(): void
    {
        User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'password' => 'new-secure-password',
        ]);

        $exitCode = Artisan::call('app:operator-password-audit', [
            '--since' => now()->subMinute()->toIso8601String(),
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('telah dirotasi', Artisan::output());
    }

    public function test_operator_without_recorded_rotation_blocks_audit(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        User::withoutEvents(fn () => $operator->forceFill(['password_changed_at' => null])->save());

        $exitCode = Artisan::call('app:operator-password-audit', [
            '--since' => now()->subDay()->toIso8601String(),
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Belum rotasi: 1', Artisan::output());
    }
}
