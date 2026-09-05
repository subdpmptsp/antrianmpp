<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Counter;
use App\Models\Service;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\OperatorPerCounterSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Test suite memakai hierarki data yang sama dengan aturan operasional:
        // Instansi -> Layanan -> Loket. Jangan gunakan seed data legacy di
        // environment testing karena loket legacy tidak memiliki instansi.
        if (app()->environment('testing')) {
            $this->call(TestingSeeder::class);

            return;
        }

        // Seed data dasar loket & layanan jika belum ada
        if (Counter::count() === 0) {
            $institutions = [
                ['instansi_id' => 9001, 'nama_instansi' => 'Instansi Uji Zona 1', 'zone' => 'ZONA 1'],
                ['instansi_id' => 9002, 'nama_instansi' => 'Instansi Uji Zona 2', 'zone' => 'ZONA 2'],
            ];

            foreach ($institutions as $institutionData) {
                $institution = \App\Models\Instansi::create($institutionData + [
                    'is_active' => true,
                    'is_archived' => false,
                ]);
                $service = Service::create([
                    'instansi_id' => $institution->instansi_id,
                    'name' => 'Layanan Uji '.$institution->zone,
                    'prefix' => substr((string) $institution->zone, -1).'A',
                    'padding' => 2,
                    'is_active' => true,
                    'is_accepting_queues' => true,
                ]);
                Counter::create([
                    'name' => $institution->zone,
                    'code_loket' => 'TEST-'.$institution->instansi_id,
                    'instansi_id' => $institution->instansi_id,
                    'service_id' => $service->id,
                    'is_active' => true,
                    'is_archived' => false,
                ]);
            }
        }

    // Admin default
    $this->call(AdminUserSeeder::class);

    // Buat 1 operator per loket
    $this->call(OperatorPerCounterSeeder::class);
}
}
