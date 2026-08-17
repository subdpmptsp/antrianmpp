<?php

namespace Tests\Feature;

use App\Filament\Resources\InstansiResource;
use App\Models\Instansi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstitutionCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_institution_logo_and_zone_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(InstansiResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Logo Instansi untuk Kiosk')
            ->assertSee('Zona Internal');

        $this->assertTrue(Schema::hasColumn('instansis', 'logo_path'));

        $institution = Instansi::query()->create([
            'nama_instansi' => 'Instansi Uji',
            'logo_path' => 'instansi-logos/logo-uji.webp',
        ]);

        $this->assertSame('instansi-logos/logo-uji.webp', $institution->logo_path);
        $this->assertSame('/storage/instansi-logos/logo-uji.webp', Storage::disk('public')->url($institution->logo_path));
    }

    public function test_catalog_normalization_applies_approved_corrections(): void
    {
        $now = now();
        DB::table('counters')->insert([
            'id' => 109,
            'name' => 'ZONA 5',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $institutions = [
            3 => 'UPTSP',
            5 => 'B/J & Adpemb Kota Surabaya',
            7 => 'Badan Pendapatan Daerah',
            9 => 'Pengadilan Negeri Surabaya',
            10 => 'Pengadilan Tata Usaha Negeri Surabaya',
            11 => 'Dinas Lingkungan Hidup',
            12 => 'Dinas Perumahan Rakyat Kawasan Permukiman serta Tanaman (DPRKPP)',
            17 => 'Direktorat Jendral Pajak ',
            26 => 'Klinik Investasi ',
            27 => 'BURSA EFEK',
            28 => 'BNI Sekuritas',
        ];

        foreach ($institutions as $id => $name) {
            DB::table('instansis')->insert([
                'instansi_id' => $id,
                'nama_instansi' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $services = [
            6 => [3, 'Konsultasi Dispendik', '1G', true],
            10 => [3, 'Konsultasi Perijinan Non Berusaha', '1J', true],
            19 => [5, 'Layanan Pendaftaran Katalog Elektronik', '2E', true],
            23 => [7, 'Layanan Pajak Reklame', '2H', true],
            31 => [11, 'Layanan Perkara (Loket Informasi dan Konsultasi)', '3E', true],
            34 => [12, 'Layanan Dishub (Loket Informasi dan Konsultasi Teknis', '3H', true],
            43 => [10, 'Layanan Hukum', '5A', true],
            44 => [9, 'Layanan Hukum', '5B', true],
            46 => [26, 'Layanan Perijinan Berusaha', '5D', true],
            29 => [null, 'Layanan Hukum', '3D', false],
            30 => [null, 'Layanan Hukum ', '3E', false],
            36 => [null, 'Layanan Informasi', '4B', false],
            37 => [null, 'Layanan Informasi', '4B', false],
        ];

        foreach ($services as $id => [$institutionId, $name, $prefix, $active]) {
            DB::table('services')->insert([
                'id' => $id,
                'instansi_id' => $institutionId,
                'name' => $name,
                'prefix' => $prefix,
                'padding' => 3,
                'is_active' => $active,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $migration = require database_path('migrations/2026_08_17_120100_normalize_kiosk_catalog_data.php');
        $migration->up();

        $this->assertDatabaseHas('instansis', [
            'instansi_id' => 5,
            'nama_instansi' => 'Bagian Pengadaan Barang/Jasa dan Administrasi Pembangunan (BPBJAP)',
        ]);
        $this->assertDatabaseHas('instansis', [
            'instansi_id' => 12,
            'nama_instansi' => 'Dinas Perumahan Rakyat dan Kawasan Permukiman serta Pertanahan (DPRKPP)',
        ]);
        $this->assertDatabaseHas('instansis', ['instansi_id' => 17, 'nama_instansi' => 'Direktorat Jenderal Pajak']);
        $this->assertDatabaseHas('instansis', ['instansi_id' => 27, 'counter_id' => 109]);
        $this->assertDatabaseHas('instansis', ['instansi_id' => 28, 'counter_id' => 109]);

        foreach ([6 => '1O', 19 => '2G', 23 => '2I', 43 => '3D', 44 => '3B'] as $id => $prefix) {
            $this->assertDatabaseHas('services', ['id' => $id, 'prefix' => $prefix]);
        }

        $this->assertDatabaseHas('services', ['id' => 10, 'name' => 'Konsultasi Perizinan Non Berusaha']);
        $this->assertDatabaseHas('services', ['id' => 34, 'name' => 'Layanan DPRKPP (Loket Informasi dan Konsultasi Teknis)']);
        $this->assertDatabaseHas('services', ['id' => 46, 'name' => 'Layanan Perizinan Berusaha']);
        $this->assertDatabaseMissing('services', ['id' => 29]);
        $this->assertDatabaseMissing('services', ['id' => 30]);
        $this->assertDatabaseMissing('services', ['id' => 36]);
        $this->assertDatabaseMissing('services', ['id' => 37]);
    }
}
