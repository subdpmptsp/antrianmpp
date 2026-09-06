<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $counter = DB::table('counters')
                ->whereRaw('LOWER(code_loket) = ?', ['4a3'])
                ->where('instansi_id', 15)
                ->where('service_id', 55)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                // Database baru/testing dapat tidak membawa data operasional produksi.
                // Database yang sudah diperbaiki juga aman melewati migrasi ini.
                return;
            }

            if (DB::table('counters')->whereRaw('LOWER(code_loket) = ?', ['4k1'])->where('id', '!=', $counter->id)->exists()) {
                throw ValidationException::withMessages(['code_loket' => 'Kode loket 4k1 sudah digunakan.']);
            }

            $user = DB::table('users')
                ->where('counter_id', $counter->id)
                ->whereRaw('LOWER(username) = ?', ['4a3'])
                ->lockForUpdate()
                ->first();

            if (! $user) {
                throw ValidationException::withMessages([
                    'username' => 'Akun petugas 4a3 untuk loket Bursa Tenaga Kerja tidak ditemukan.',
                ]);
            }

            if (DB::table('users')->whereRaw('LOWER(username) = ?', ['4k1'])->where('id', '!=', $user->id)->exists()) {
                throw ValidationException::withMessages(['username' => 'Username 4k1 sudah digunakan.']);
            }

            DB::table('counters')->where('id', $counter->id)->update([
                'code_loket' => '4k1',
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $user->id)->update([
                'name' => 'Petugas Loket 4K1',
                'username' => '4k1',
                'email' => '4k1@loket.local',
                'service_id' => $counter->service_id,
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $counter = DB::table('counters')
                ->whereRaw('LOWER(code_loket) = ?', ['4k1'])
                ->where('instansi_id', 15)
                ->where('service_id', 55)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                return;
            }

            DB::table('counters')->where('id', $counter->id)->update([
                'code_loket' => '4a3',
                'updated_at' => now(),
            ]);

            DB::table('users')->where('counter_id', $counter->id)->whereRaw('LOWER(username) = ?', ['4k1'])->update([
                'name' => 'Petugas Loket 4A3',
                'username' => '4a3',
                'email' => '4a3@loket.local',
                'updated_at' => now(),
            ]);
        });
    }
};
