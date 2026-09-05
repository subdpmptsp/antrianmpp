<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $institutionId = DB::table('instansis')
            ->where('nama_instansi', 'BPJS Kesehatan')
            ->value('instansi_id');

        if (! $institutionId) {
            return;
        }

        $serviceId = DB::table('services')
            ->where('instansi_id', $institutionId)
            ->where('prefix', '4A2')
            ->value('id');

        $counterId = DB::table('counters')
            ->where('instansi_id', $institutionId)
            ->where('code_loket', '4a2')
            ->value('id');

        if (! $serviceId || ! $counterId) {
            return;
        }

        DB::table('counters')->where('id', $counterId)->update([
            'service_id' => $serviceId,
            'updated_at' => now(),
        ]);

        DB::table('users')->where('counter_id', $counterId)->update([
            'service_id' => $serviceId,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Riwayat antrean tidak diubah saat rollback.
    }
};
