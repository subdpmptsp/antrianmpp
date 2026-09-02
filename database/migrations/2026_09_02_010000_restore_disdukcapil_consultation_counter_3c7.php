<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $counter = DB::table('counters')->where('code_loket', '3c7')->first();

        if (! $counter || ! $counter->instansi_id) {
            return;
        }

        DB::table('services')->updateOrInsert(
            [
                'instansi_id' => $counter->instansi_id,
                'prefix' => '3C-7',
            ],
            [
                'name' => 'Konsultasi Kependudukan',
                'padding' => 2,
                'counter_id' => null,
                'is_active' => true,
                'is_accepting_queues' => true,
                'is_archived' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $serviceId = DB::table('services')
            ->where('instansi_id', $counter->instansi_id)
            ->where('prefix', '3C-7')
            ->value('id');

        if (! $serviceId) {
            return;
        }

        DB::table('counters')->where('id', $counter->id)->update([
            'service_id' => $serviceId,
            'updated_at' => now(),
        ]);

        DB::table('users')->where('counter_id', $counter->id)->update([
            'service_id' => $serviceId,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Riwayat tiket 3C-7 harus tetap aman; rollback tidak menghapus data operasional.
    }
};
