<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Disetujui admin: Z1-16 s.d. Z1-21 saling membantu pemanggilan
     * layanan 1J, 1K, 1L, dan 1M tanpa pergantian akun.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $services = DB::table('services')
                ->whereIn('prefix', ['1J', '1K', '1L', '1M'])
                ->pluck('id', 'prefix');
            $counters = DB::table('counters')
                ->where('name', 'ZONA 1')
                ->whereIn('code_loket', ['Z1-16', 'Z1-17', 'Z1-18', 'Z1-19', 'Z1-20', 'Z1-21'])
                ->pluck('id');

            // Tidak ada master data produksi pada instalasi baru/test.
            if ($services->isEmpty() && $counters->isEmpty()) {
                return;
            }

            if ($services->count() !== 4 || $counters->count() !== 6) {
                throw new RuntimeException('Pengaturan tab layanan bersama Zona 1 dibatalkan karena data loket atau layanan tidak sesuai.');
            }

            $now = now();
            $rows = [];

            foreach ($counters as $counterId) {
                foreach ($services as $serviceId) {
                    $rows[] = [
                        'counter_id' => $counterId,
                        'service_id' => $serviceId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            DB::table('counter_service')->insertOrIgnore($rows);
        });
    }

    public function down(): void
    {
        $counterIds = DB::table('counters')
            ->where('name', 'ZONA 1')
            ->whereIn('code_loket', ['Z1-16', 'Z1-17', 'Z1-18', 'Z1-19', 'Z1-20', 'Z1-21'])
            ->pluck('id');
        $serviceIds = DB::table('services')
            ->whereIn('prefix', ['1J', '1K', '1L', '1M'])
            ->pluck('id');

        DB::table('counter_service')
            ->whereIn('counter_id', $counterIds)
            ->whereIn('service_id', $serviceIds)
            ->delete();
    }
};
