<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private const ASSIGNMENTS = [
        '1k18' => ['1J'],
        '1l19' => ['1J', '1K', '1M'],
        '1m20' => ['1J', '1K', '1L'],
        '1m21' => ['1J', '1K', '1L'],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::ASSIGNMENTS as $counterCode => $prefixes) {
                $counter = DB::table('counters')
                    ->whereRaw('LOWER(code_loket) = ?', [strtolower($counterCode)])
                    ->first(['id', 'instansi_id']);

                if (! $counter) {
                    continue;
                }

                $serviceIds = DB::table('services')
                    ->where('instansi_id', $counter->instansi_id)
                    ->whereIn('prefix', $prefixes)
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
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (self::ASSIGNMENTS as $counterCode => $prefixes) {
                $counter = DB::table('counters')
                    ->whereRaw('LOWER(code_loket) = ?', [strtolower($counterCode)])
                    ->first(['id', 'instansi_id']);

                if (! $counter) {
                    continue;
                }

                $serviceIds = DB::table('services')
                    ->where('instansi_id', $counter->instansi_id)
                    ->whereIn('prefix', $prefixes)
                    ->pluck('id');

                DB::table('counter_service')
                    ->where('counter_id', $counter->id)
                    ->whereIn('service_id', $serviceIds)
                    ->delete();
            }
        });
    }
};
