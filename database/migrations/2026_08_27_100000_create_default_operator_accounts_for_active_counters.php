<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $counters = DB::table('counters')
                ->where('is_active', true)
                ->whereNotNull('code_loket')
                ->orderBy('id')
                ->get(['id', 'code_loket', 'service_id']);

            foreach ($counters as $counter) {
                $username = strtolower(str_replace('-', '', $counter->code_loket));
                $existingUser = DB::table('users')
                    ->where('counter_id', $counter->id)
                    ->where('role', 'operator')
                    ->first(['id']);

                $values = [
                    'name' => 'Petugas Loket '.$counter->code_loket,
                    'username' => $username,
                    'email' => $username.'@loket.local',
                    'password' => Hash::make('mpp'),
                    'role' => 'operator',
                    'is_active' => true,
                    'counter_id' => $counter->id,
                    'service_id' => $counter->service_id,
                    'password_changed_at' => now(),
                    'updated_at' => now(),
                ];

                if ($existingUser) {
                    DB::table('users')->where('id', $existingUser->id)->update($values);

                    continue;
                }

                DB::table('users')->insert([
                    ...$values,
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Akun petugas yang sudah digunakan tidak dihapus otomatis demi menjaga riwayat kehadiran.
    }
};
