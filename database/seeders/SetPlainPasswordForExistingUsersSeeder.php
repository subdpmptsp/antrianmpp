<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetPlainPasswordForExistingUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Schema::hasColumn('users', 'plain_password')) {
            DB::table('users')->update(['plain_password' => null]);
        }

        $this->command?->warn('Plain-text password values have been cleared. Passwords were not reset.');
    }
}
