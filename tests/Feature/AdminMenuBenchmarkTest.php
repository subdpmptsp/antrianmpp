<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AdminMenuBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_menu_benchmark_produces_a_machine_readable_report(): void
    {
        $this->seed(TestingSeeder::class);
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $exitCode = Artisan::call('app:benchmark-admin', [
            '--runs' => 1,
            '--max-average-ms' => 5000,
            '--max-query-count' => 40,
            '--json' => true,
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($report['passed']);
        $this->assertCount(14, $report['results']);

        foreach ($report['results'] as $result) {
            $this->assertLessThan(400, $result['status']);
            $this->assertIsNumeric($result['average_ms']);
            $this->assertIsInt($result['max_queries']);
        }
    }
}
