<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_admin_requires_a_non_default_password(): void
    {
        config()->set('security.initial_admin_password');

        $this->expectException(RuntimeException::class);

        $this->seed(AdminUserSeeder::class);
    }

    public function test_seeder_never_resets_an_existing_admin_password(): void
    {
        config()->set('security.initial_admin_password', 'password-awal-aman');
        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $this->assertTrue(Hash::check('password-awal-aman', $admin->password));

        config()->set('security.initial_admin_password', 'password-pengganti-baru');
        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(Hash::check('password-awal-aman', $admin->refresh()->password));
        $this->assertFalse(Hash::check('password-pengganti-baru', $admin->password));
    }
}
