<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COUNTER_CODE = '1k18';

    private const SERVICE_PREFIXES = ['1L', '1M'];

    public function up(): void
    {
        DB::transaction(function (): void {
            $counter = DB::table('counters')
                ->whereRaw('LOWER(code_loket) = ?', [self::COUNTER_CODE])
                ->first(['id', 'instansi_id']);

            if (! $counter) {
                return;
            }

            $serviceIds = DB::table('services')
                ->where('instansi_id', $counter->instansi_id)
                ->whereIn('prefix', self::SERVICE_PREFIXES)
                ->where('is_active', true)
                ->where('is_archived', false)
                ->pluck('id');

            foreach ($serviceIds as $serviceId) {
                DB::table('counter_service')->insertOrIgnore([
                    'counter_id' => $counter->id,
                    'service_id' => $serviceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $counter = DB::table('counters')
                ->whereRaw('LOWER(code_loket) = ?', [self::COUNTER_CODE])
                ->first(['id', 'instansi_id']);

            if (! $counter) {
                return;
            }

            $serviceIds = DB::table('services')
                ->where('instansi_id', $counter->instansi_id)
                ->whereIn('prefix', self::SERVICE_PREFIXES)
                ->pluck('id');

            DB::table('counter_service')
                ->where('counter_id', $counter->id)
                ->whereIn('service_id', $serviceIds)
                ->delete();
        });
    }
};
