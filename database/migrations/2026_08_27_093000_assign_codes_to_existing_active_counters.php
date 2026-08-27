<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $codes = [
            5 => 'Z1-01', 6 => 'Z1-02', 7 => 'Z1-03', 8 => 'Z1-04', 9 => 'Z1-05',
            10 => 'Z1-06', 11 => 'Z1-07', 12 => 'Z1-08', 13 => 'Z1-09', 14 => 'Z1-10',
            15 => 'Z1-11', 16 => 'Z1-12', 17 => 'Z1-13', 18 => 'Z1-14', 19 => 'Z1-15',

            20 => 'Z2-01', 21 => 'Z2-02', 22 => 'Z2-03', 23 => 'Z2-04', 25 => 'Z2-05',
            26 => 'Z2-06', 27 => 'Z2-07', 28 => 'Z2-08',

            34 => 'Z3-01', 35 => 'Z3-02', 36 => 'Z3-03', 37 => 'Z3-04', 38 => 'Z3-05',
            39 => 'Z3-06',

            40 => 'Z4-01', 41 => 'Z4-02', 42 => 'Z4-03', 43 => 'Z4-04', 44 => 'Z4-05',
            105 => 'Z4-06', 106 => 'Z4-07', 107 => 'Z4-08', 108 => 'Z4-09',

            109 => 'Z5-01', 110 => 'Z5-02', 111 => 'Z5-03', 112 => 'Z5-04',
        ];

        DB::transaction(function () use ($codes): void {
            foreach ($codes as $counterId => $code) {
                DB::table('counters')
                    ->where('id', $counterId)
                    ->where('is_active', true)
                    ->whereNull('code_loket')
                    ->update([
                        'code_loket' => $code,
                        'updated_at' => now(),
                    ]);
            }
        });

        Cache::forget('master-data:version');
    }

    public function down(): void
    {
        DB::table('counters')
            ->whereIn('code_loket', [
                'Z1-01', 'Z1-02', 'Z1-03', 'Z1-04', 'Z1-05', 'Z1-06', 'Z1-07', 'Z1-08', 'Z1-09', 'Z1-10', 'Z1-11', 'Z1-12', 'Z1-13', 'Z1-14', 'Z1-15',
                'Z2-01', 'Z2-02', 'Z2-03', 'Z2-04', 'Z2-05', 'Z2-06', 'Z2-07', 'Z2-08',
                'Z3-01', 'Z3-02', 'Z3-03', 'Z3-04', 'Z3-05', 'Z3-06',
                'Z4-01', 'Z4-02', 'Z4-03', 'Z4-04', 'Z4-05', 'Z4-06', 'Z4-07', 'Z4-08', 'Z4-09',
                'Z5-01', 'Z5-02', 'Z5-03', 'Z5-04',
            ])
            ->update(['code_loket' => null, 'updated_at' => now()]);

        Cache::forget('master-data:version');
    }
};
