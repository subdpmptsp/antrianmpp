<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $zoneFiveId = DB::table('counters')
                ->whereRaw('UPPER(name) = ?', ['ZONA 5'])
                ->value('id');

            DB::table('instansis')->where('instansi_id', 5)->update([
                'nama_instansi' => 'Bagian Pengadaan Barang/Jasa dan Administrasi Pembangunan (BPBJAP)',
                'deskripsi' => 'Bagian Pengadaan Barang/Jasa dan Administrasi Pembangunan (BPBJAP) - Gedung D Lantai 1, Telp: (031) 567890',
            ]);
            DB::table('instansis')->where('instansi_id', 12)->update([
                'nama_instansi' => 'Dinas Perumahan Rakyat dan Kawasan Permukiman serta Pertanahan (DPRKPP)',
            ]);
            DB::table('instansis')->where('instansi_id', 17)->update([
                'nama_instansi' => 'Direktorat Jenderal Pajak',
            ]);
            DB::table('instansis')->where('instansi_id', 26)->update([
                'nama_instansi' => 'Klinik Investasi',
            ]);

            if ($zoneFiveId !== null) {
                DB::table('instansis')
                    ->whereIn('instansi_id', [27, 28])
                    ->update(['counter_id' => $zoneFiveId]);
            }

            $serviceCorrections = [
                6 => ['prefix' => '1O'],
                10 => ['name' => 'Konsultasi Perizinan Non Berusaha'],
                19 => ['prefix' => '2G'],
                23 => ['prefix' => '2I'],
                31 => ['name' => 'Layanan Pengaduan Lingkungan Hidup (Loket Informasi dan Konsultasi)'],
                34 => ['name' => 'Layanan DPRKPP (Loket Informasi dan Konsultasi Teknis)'],
                43 => ['prefix' => '3D'],
                44 => ['prefix' => '3B'],
                46 => ['name' => 'Layanan Perizinan Berusaha'],
            ];

            foreach ($serviceCorrections as $serviceId => $values) {
                DB::table('services')->where('id', $serviceId)->update($values);
            }

            DB::table('services')
                ->whereIn('id', [29, 30, 36, 37])
                ->whereNull('instansi_id')
                ->where('is_active', false)
                ->whereNotExists(fn ($query) => $query
                    ->selectRaw('1')
                    ->from('queues')
                    ->whereColumn('queues.service_id', 'services.id'))
                ->whereNotExists(fn ($query) => $query
                    ->selectRaw('1')
                    ->from('counters')
                    ->whereColumn('counters.service_id', 'services.id'))
                ->delete();
        });

        Cache::forget('master-data:version');
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('instansis')->where('instansi_id', 5)->update([
                'nama_instansi' => 'B/J & Adpemb Kota Surabaya',
                'deskripsi' => 'Bagian Keuangan & Administrasi Pembangunan - Gedung D Lantai 1, Telp: (031) 567890',
            ]);
            DB::table('instansis')->where('instansi_id', 12)->update([
                'nama_instansi' => 'Dinas Perumahan Rakyat Kawasan Permukiman serta Tanaman (DPRKPP)',
            ]);
            DB::table('instansis')->where('instansi_id', 17)->update([
                'nama_instansi' => 'Direktorat Jendral Pajak ',
            ]);
            DB::table('instansis')->where('instansi_id', 26)->update([
                'nama_instansi' => 'Klinik Investasi ',
            ]);
            DB::table('instansis')->whereIn('instansi_id', [27, 28])->update(['counter_id' => null]);

            $serviceRollbacks = [
                6 => ['prefix' => '1G'],
                10 => ['name' => 'Konsultasi Perijinan Non Berusaha'],
                19 => ['prefix' => '2E'],
                23 => ['prefix' => '2H'],
                31 => ['name' => 'Layanan Perkara (Loket Informasi dan Konsultasi)'],
                34 => ['name' => 'Layanan Dishub (Loket Informasi dan Konsultasi Teknis'],
                43 => ['prefix' => '5A'],
                44 => ['prefix' => '5B'],
                46 => ['name' => 'Layanan Perijinan Berusaha'],
            ];

            foreach ($serviceRollbacks as $serviceId => $values) {
                DB::table('services')->where('id', $serviceId)->update($values);
            }

            $timestamp = now();
            DB::table('services')->insertOrIgnore([
                ['id' => 29, 'instansi_id' => null, 'name' => 'Layanan Hukum (Loket Informasi dan Konsultasi)', 'prefix' => '3D', 'padding' => 0, 'counter_id' => null, 'is_active' => false, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['id' => 30, 'instansi_id' => null, 'name' => 'Layanan Hukum (Loket Informasi dan Konsultasi) ', 'prefix' => '3E', 'padding' => 0, 'counter_id' => null, 'is_active' => false, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['id' => 36, 'instansi_id' => null, 'name' => 'Layanan Informasi dan Konsultasi Teknis', 'prefix' => '4B', 'padding' => 0, 'counter_id' => null, 'is_active' => false, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['id' => 37, 'instansi_id' => null, 'name' => 'Layanan Informasi dan Konsultasi Teknis', 'prefix' => '4B', 'padding' => 0, 'counter_id' => null, 'is_active' => false, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ]);
        });

        Cache::forget('master-data:version');
    }
};
