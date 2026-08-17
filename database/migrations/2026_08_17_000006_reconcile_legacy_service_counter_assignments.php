<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // Duplikat dengan instansi dan fungsi identik digabung tanpa menghapus histori.
            $canonicalServices = [
                53 => 45,
                57 => 50,
                58 => 44,
                59 => 43,
            ];

            foreach ($canonicalServices as $duplicateId => $canonicalId) {
                if (! $this->serviceExists($duplicateId) || ! $this->serviceExists($canonicalId)) {
                    continue;
                }

                DB::table('queues')->where('service_id', $duplicateId)->update(['service_id' => $canonicalId]);
                DB::table('users')->where('service_id', $duplicateId)->update(['service_id' => $canonicalId]);
            }

            // Assignment diturunkan dari pasangan instansi-loket yang sudah ada pada data restore.
            $counterAssignments = [
                24 => 20,
                25 => 19,
                41 => 54,
                42 => 55,
                106 => 40,
                108 => 42,
                109 => 51,
                110 => 52,
            ];

            foreach ($counterAssignments as $counterId => $serviceId) {
                if (! $this->counterExists($counterId) || ! $this->serviceExists($serviceId)) {
                    continue;
                }

                DB::table('counters')->where('id', $counterId)->update(['service_id' => $serviceId]);
            }

            // Sinkronkan assignment operator setelah relasi loket diperbaiki.
            DB::table('users')
                ->join('counters', 'counters.id', '=', 'users.counter_id')
                ->where('users.role', 'operator')
                ->whereNotNull('counters.service_id')
                ->update(['users.service_id' => DB::raw('counters.service_id')]);

            // Record generik/duplikat kosong dipertahankan sebagai histori, tetapi tidak boleh dipilih kiosk.
            DB::table('services')
                ->whereIn('id', [29, 30, 36, 37, 53, 56, 57, 58, 59])
                ->update(['is_active' => false]);
        });
    }

    public function down(): void
    {
        // Penggabungan histori tidak dibalik otomatis karena dapat menciptakan assignment ambigu kembali.
    }

    private function serviceExists(int $serviceId): bool
    {
        return DB::table('services')->where('id', $serviceId)->exists();
    }

    private function counterExists(int $counterId): bool
    {
        return DB::table('counters')->where('id', $counterId)->exists();
    }
};
