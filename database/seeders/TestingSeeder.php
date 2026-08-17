<?php

namespace Database\Seeders;

use App\Models\Counter;
use Illuminate\Database\Seeder;

class TestingSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            5 => 'ZONA 1',
            20 => 'ZONA 2',
            29 => 'ZONA 3',
            40 => 'ZONA 4',
            109 => 'ZONA 5',
        ];

        foreach ($zones as $id => $name) {
            Counter::query()->updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
