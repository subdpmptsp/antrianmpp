<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('counters', 'code_loket')) {
            Schema::table('counters', function (Blueprint $table): void {
                $table->string('code_loket', 20)->nullable()->unique()->after('name');
            });
        }

        DB::transaction(function (): void {
            $instansiId = 8; // Dinas Kependudukan dan Pencatatan Sipil.
            $legacyServiceIds = DB::table('services')
                ->where('instansi_id', $instansiId)
                ->pluck('id');

            // Jangan berpindah konfigurasi ketika masih ada antrean hari ini yang belum selesai.
            $hasOpenQueueToday = DB::table('queues')
                ->whereIn('service_id', $legacyServiceIds)
                ->whereDate('created_at', today())
                ->whereIn('status', ['printing', 'waiting', 'called', 'serving'])
                ->exists();

            if ($hasOpenQueueToday) {
                throw new RuntimeException('Revisi loket Dispendukcapil dihentikan karena masih ada antrean aktif hari ini. Selesaikan atau batalkan antrean tersebut terlebih dahulu.');
            }

            // Antrean lama dari hari sebelumnya sudah tidak dapat dipanggil oleh sistem.
            // Riwayat tetap tersimpan, tetapi statusnya ditutup agar tidak tampak menggantung.
            DB::table('queues')
                ->whereIn('service_id', $legacyServiceIds)
                ->whereDate('created_at', '<', today())
                ->whereIn('status', ['printing', 'waiting', 'called', 'serving'])
                ->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                    'updated_at' => now(),
                ]);

            // Konfigurasi lama dipertahankan untuk laporan, tetapi tidak lagi tampil di kiosk.
            DB::table('services')
                ->whereIn('id', $legacyServiceIds)
                ->update(['is_active' => false, 'updated_at' => now()]);

            DB::table('counters')
                ->where('instansi_id', $instansiId)
                ->update(['is_active' => false, 'updated_at' => now()]);

            $definitions = [
                ['code' => '3A-1', 'service' => 'Perekaman KTP-el Baru'],
                ['code' => '3A-2', 'service' => 'Aktivasi IKD'],
                ['code' => '3B-3', 'service' => 'YOB yang Sudah Terjadwal'],
                ['code' => '3B-4', 'service' => 'Cek Biometrik'],
                ['code' => '3B-5', 'service' => 'Buka Blokir'],
                ['code' => '3C-6', 'service' => 'Konsultasi Kependudukan'],
                ['code' => '3C-7', 'service' => 'Konsultasi Kependudukan'],
                ['code' => '3C-8', 'service' => 'Konsultasi Kependudukan (Disabilitas, Lansia dan Ibu Menyusui)'],
            ];

            foreach ($definitions as $definition) {
                DB::table('services')->updateOrInsert(
                    ['instansi_id' => $instansiId, 'prefix' => $definition['code']],
                    [
                        'name' => $definition['service'],
                        'padding' => 2,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );

                $serviceId = DB::table('services')
                    ->where('instansi_id', $instansiId)
                    ->where('prefix', $definition['code'])
                    ->value('id');

                DB::table('counters')->updateOrInsert(
                    ['code_loket' => $definition['code']],
                    [
                        'name' => 'ZONA 3',
                        'instansi_id' => $instansiId,
                        'service_id' => $serviceId,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        });

        Cache::forget('master-data:version');
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('counters')
                ->where('instansi_id', 8)
                ->whereNotNull('code_loket')
                ->delete();

            DB::table('services')
                ->where('instansi_id', 8)
                ->whereIn('prefix', ['3A-1', '3A-2', '3B-3', '3B-4', '3B-5', '3C-6', '3C-7', '3C-8'])
                ->delete();

            DB::table('services')
                ->where('instansi_id', 8)
                ->update(['is_active' => true, 'updated_at' => now()]);

            DB::table('counters')
                ->where('instansi_id', 8)
                ->update(['is_active' => true, 'updated_at' => now()]);
        });

        if (Schema::hasColumn('counters', 'code_loket')) {
            Schema::table('counters', function (Blueprint $table): void {
                $table->dropUnique('counters_code_loket_unique');
                $table->dropColumn('code_loket');
            });
        }

        Cache::forget('master-data:version');
    }
};
