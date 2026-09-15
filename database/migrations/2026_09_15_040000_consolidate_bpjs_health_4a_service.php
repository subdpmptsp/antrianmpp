<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $institutionId = DB::table('instansis')
            ->whereRaw('LOWER(nama_instansi) = ?', ['bpjs kesehatan'])
            ->value('instansi_id');

        if (! $institutionId) {
            return;
        }

        $primary = DB::table('services')
            ->where('instansi_id', $institutionId)
            ->whereRaw('UPPER(prefix) = ?', ['4A1'])
            ->first();
        $duplicate = DB::table('services')
            ->where('instansi_id', $institutionId)
            ->whereRaw('UPPER(prefix) = ?', ['4A2'])
            ->first();

        if (! $primary || ! $duplicate || (int) $primary->id === (int) $duplicate->id) {
            return;
        }

        DB::transaction(function () use ($primary, $duplicate): void {
            $primaryId = (int) $primary->id;
            $duplicateId = (int) $duplicate->id;

            // Riwayat queue, reservasi, log override, dan penutupan loket sengaja
            // tetap menunjuk layanan lama agar laporan historis tidak berubah.
            DB::table('counters')->where('service_id', $duplicateId)->update([
                'service_id' => $primaryId,
                'updated_at' => now(),
            ]);
            DB::table('users')->where('service_id', $duplicateId)->update([
                'service_id' => $primaryId,
                'updated_at' => now(),
            ]);

            $duplicateSessions = DB::table('online_queue_sessions')
                ->where('service_id', $duplicateId)
                ->orderBy('id')
                ->get();
            foreach ($duplicateSessions as $session) {
                $hasReservations = DB::table('online_queue_reservations')
                    ->where('online_queue_session_id', $session->id)
                    ->exists();

                if (! $hasReservations) {
                    DB::table('online_queue_sessions')->where('id', $session->id)->update([
                        'service_id' => $primaryId,
                        'updated_at' => now(),
                    ]);

                    continue;
                }

                DB::table('online_queue_sessions')->where('id', $session->id)->update([
                    'status' => 'closed',
                    'updated_at' => now(),
                ]);
                DB::table('online_queue_sessions')->insert([
                    'service_id' => $primaryId,
                    'day_of_week' => $session->day_of_week,
                    'starts_at' => $session->starts_at,
                    'ends_at' => $session->ends_at,
                    'quota' => $session->quota,
                    'checkin_open_minutes' => $session->checkin_open_minutes,
                    'checkin_grace_minutes' => $session->checkin_grace_minutes,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (Schema::hasTable('online_queue_special_windows')) {
                DB::table('online_queue_special_windows')->where('service_id', $duplicateId)->update([
                    'service_id' => $primaryId,
                    'updated_at' => now(),
                ]);
            }

            // Kedua loket kini memakai service_id utama yang sama. Pivot yang
            // duplikatif dibersihkan; relasi historis tidak dihapus dari queue.
            DB::table('counter_service')
                ->whereIn('service_id', [$primaryId, $duplicateId])
                ->whereIn('counter_id', DB::table('counters')->where('service_id', $primaryId)->pluck('id'))
                ->delete();

            DB::table('services')->where('id', $duplicateId)->update([
                'is_active' => false,
                'is_accepting_queues' => false,
                'is_archived' => true,
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Konsolidasi operasional tidak dibalik otomatis karena setelah dipakai
        // data baru dapat sudah terbentuk pada layanan gabungan. Riwayat lama
        // tetap tersimpan pada service 4A2 dan dapat dipulihkan secara manual.
    }
};
