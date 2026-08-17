<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $countersByService = DB::table('counters')
            ->whereNotNull('service_id')
            ->get(['id', 'service_id', 'instansi_id'])
            ->groupBy('service_id');

        foreach ($countersByService as $serviceId => $counters) {
            $instansiIds = $counters->pluck('instansi_id')->filter()->unique()->values();

            if ($instansiIds->count() === 1) {
                DB::table('services')
                    ->where('id', $serviceId)
                    ->whereNull('instansi_id')
                    ->update(['instansi_id' => $instansiIds->first()]);
            }

            $counterIds = $counters->pluck('id')->unique()->values();

            if ($counterIds->count() === 1) {
                DB::table('users')
                    ->where('role', 'operator')
                    ->where('service_id', $serviceId)
                    ->whereNull('counter_id')
                    ->update(['counter_id' => $counterIds->first()]);
            }
        }
    }

    public function down(): void
    {
        // Data lama tidak dapat dibedakan secara aman dari assignment yang dibuat setelah migrasi.
    }
};
