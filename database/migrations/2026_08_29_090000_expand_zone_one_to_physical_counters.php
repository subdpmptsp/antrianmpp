<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Menyamakan master Zona 1 dengan nomor loket fisik hasil survei.
     * Satu layanan dapat dilayani beberapa loket, tetapi nomor antrean tetap
     * satu urutan per layanan.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $layout = [
                'Z1-01' => '1A', 'Z1-02' => '1A',
                'Z1-03' => '1B', 'Z1-04' => '1B',
                'Z1-05' => '1C',
                'Z1-06' => '1D', 'Z1-07' => '1D',
                'Z1-08' => '1E', 'Z1-09' => '1E',
                'Z1-10' => '1F',
                'Z1-11' => '1O',
                'Z1-12' => '1G',
                'Z1-13' => '1H',
                'Z1-14' => '1I', 'Z1-15' => '1I',
                'Z1-16' => '1J', 'Z1-17' => '1J',
                'Z1-18' => '1K',
                'Z1-19' => '1L',
                'Z1-20' => '1M', 'Z1-21' => '1M',
                'Z1-22' => '1N', 'Z1-23' => '1N', 'Z1-24' => '1N',
            ];

            $existingCounters = DB::table('counters as c')
                ->join('services as s', 's.id', '=', 'c.service_id')
                ->where('c.name', 'ZONA 1')
                ->orderBy('c.id')
                ->get(['c.id', 'c.instansi_id', 'c.service_id', 's.prefix']);

            if ($existingCounters->count() !== 15) {
                throw new RuntimeException('Penataan Zona 1 dibatalkan karena jumlah loket awal tidak sesuai audit (harus 15).');
            }

            $counterByPrefix = $existingCounters->keyBy('prefix');
            $requiredPrefixes = collect($layout)->unique()->sort()->values();

            if ($counterByPrefix->keys()->sort()->values()->all() !== $requiredPrefixes->all()) {
                throw new RuntimeException('Penataan Zona 1 dibatalkan karena layanan Zona 1 tidak sesuai denah survei.');
            }

            $instansiId = $existingCounters->first()->instansi_id;

            // Lepaskan kode dan username lama terlebih dahulu agar tidak berbenturan
            // saat kode fisik diurutkan ulang dari Z1-01 sampai Z1-24.
            foreach ($existingCounters as $counter) {
                DB::table('counters')->where('id', $counter->id)->update([
                    'code_loket' => 'TMP-Z1-'.$counter->id,
                    'updated_at' => now(),
                ]);

                DB::table('users')
                    ->where('counter_id', $counter->id)
                    ->where('role', 'operator')
                    ->update([
                        'username' => 'tmpz1'.$counter->id,
                        'email' => 'tmpz1'.$counter->id.'@loket.local',
                        'updated_at' => now(),
                    ]);
            }

            $usedPrefixes = [];

            foreach ($layout as $codeLoket => $prefix) {
                $username = strtolower(str_replace('-', '', $codeLoket));

                if (! isset($usedPrefixes[$prefix])) {
                    $counter = $counterByPrefix->get($prefix);
                    $usedPrefixes[$prefix] = true;

                    DB::table('counters')->where('id', $counter->id)->update([
                        'code_loket' => $codeLoket,
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);

                    DB::table('users')
                        ->where('counter_id', $counter->id)
                        ->where('role', 'operator')
                        ->update([
                            'name' => 'Petugas Loket '.$codeLoket,
                            'username' => $username,
                            'email' => $username.'@loket.local',
                            'service_id' => $counter->service_id,
                            'is_active' => true,
                            'updated_at' => now(),
                        ]);

                    continue;
                }

                $templateCounter = $counterByPrefix->get($prefix);
                $counterId = DB::table('counters')->insertGetId([
                    'name' => 'ZONA 1',
                    'code_loket' => $codeLoket,
                    'instansi_id' => $instansiId,
                    'service_id' => $templateCounter->service_id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')->insert([
                    'name' => 'Petugas Loket '.$codeLoket,
                    'username' => $username,
                    'email' => $username.'@loket.local',
                    'password' => Hash::make('mpp'),
                    'role' => 'operator',
                    'is_active' => true,
                    'counter_id' => $counterId,
                    'service_id' => $templateCounter->service_id,
                    'password_changed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Tidak ada rollback otomatis: kode loket dan akun sudah dapat digunakan
        // di operasional. Pemulihan dilakukan dari backup database sebelum migrasi.
    }
};
