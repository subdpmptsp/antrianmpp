<?php

namespace Tests\Feature;

use App\Models\AntrianSkck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkckDailyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_person_can_register_again_on_a_different_day(): void
    {
        AntrianSkck::query()->create([
            'nama' => 'PEMOHON TEST',
            'nik' => '3578000000000001',
            'nomor_whatsapp' => '081200000001',
            'antrian' => 1,
            'queue_date' => '2026-08-16',
        ]);

        AntrianSkck::query()->create([
            'nama' => 'PEMOHON TEST',
            'nik' => '3578000000000001',
            'nomor_whatsapp' => '081200000001',
            'antrian' => 1,
            'queue_date' => '2026-08-17',
        ]);

        $this->assertDatabaseCount('antrian_skcks', 2);
    }

    public function test_same_nik_cannot_register_twice_on_the_same_day(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        foreach (range(1, 2) as $number) {
            AntrianSkck::query()->create([
                'nama' => 'PEMOHON TEST',
                'nik' => '3578000000000001',
                'nomor_whatsapp' => '08120000000' . $number,
                'antrian' => $number,
                'queue_date' => '2026-08-17',
            ]);
        }
    }
}
