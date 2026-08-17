<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('email', 'admin@gmail.com')->exists()) {
            $this->command?->info('Akun admin sudah tersedia; password yang ada tidak diubah.');

            return;
        }

        $password = config('security.initial_admin_password');

        if (! is_string($password) || mb_strlen($password) < 12) {
            throw new RuntimeException(
                'Set INITIAL_ADMIN_PASSWORD minimal 12 karakter sebelum menjalankan AdminUserSeeder.',
            );
        }

        User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'username' => 'admin',
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
