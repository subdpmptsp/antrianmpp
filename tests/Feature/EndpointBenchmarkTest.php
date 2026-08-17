<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EndpointBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_endpoint_benchmark_produces_a_machine_readable_report(): void
    {
        $exitCode = Artisan::call('app:benchmark-endpoints', [
            '--runs' => 1,
            '--max-average-ms' => 5000,
            '--max-query-count' => 15,
            '--json' => true,
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($report['passed']);
        $this->assertCount(7, $report['results']);
        $this->assertContains('/kiosk/cetak-antrian', array_column($report['results'], 'path'));

        foreach ($report['results'] as $result) {
            $this->assertLessThan(400, $result['status']);
            $this->assertIsNumeric($result['average_ms']);
            $this->assertIsInt($result['max_queries']);
        }
    }
}
