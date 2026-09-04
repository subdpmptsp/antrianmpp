<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            5 => ['zone' => 'ZONA 1', 'instansi_id' => 1001, 'service_id' => 1001, 'prefix' => '1A'],
            20 => ['zone' => 'ZONA 2', 'instansi_id' => 1002, 'service_id' => 1002, 'prefix' => '2A'],
            29 => ['zone' => 'ZONA 3', 'instansi_id' => 1003, 'service_id' => 1003, 'prefix' => '3A'],
            40 => ['zone' => 'ZONA 4', 'instansi_id' => 1004, 'service_id' => 1004, 'prefix' => '4A'],
            109 => ['zone' => 'ZONA 5', 'instansi_id' => 1005, 'service_id' => 1005, 'prefix' => '5A'],
        ];

        foreach ($zones as $counterId => $data) {
            DB::table('instansis')->updateOrInsert(
                ['instansi_id' => $data['instansi_id']],
                [
                    'nama_instansi' => 'Instansi Uji '.$data['zone'],
                    'zone' => $data['zone'],
                    'is_active' => true,
                    'is_archived' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('services')->updateOrInsert(
                ['id' => $data['service_id']],
                [
                    'instansi_id' => $data['instansi_id'],
                    'name' => 'Layanan Uji '.$data['zone'],
                    'prefix' => $data['prefix'],
                    'padding' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('counters')->updateOrInsert(
                ['id' => $counterId],
                [
                    'name' => $data['zone'],
                    'code_loket' => 'TEST-'.$counterId,
                    'instansi_id' => $data['instansi_id'],
                    'service_id' => $data['service_id'],
                    'is_active' => true,
                    'is_archived' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
