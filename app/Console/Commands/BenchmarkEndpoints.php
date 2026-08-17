<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BenchmarkEndpoints extends Command
{
    protected $signature = 'app:benchmark-endpoints
        {--runs=5 : Jumlah pengukuran per endpoint setelah warm-up}
        {--max-average-ms=0 : Batas rata-rata milidetik; 0 menonaktifkan batas}
        {--max-query-count=0 : Batas query maksimum per request; 0 menonaktifkan batas}
        {--json : Tampilkan hasil dalam JSON}';

    protected $description = 'Ukur waktu respons dan jumlah query endpoint utama';

    public function handle(Kernel $kernel): int
    {
        $runs = max(1, min(50, (int) $this->option('runs')));
        $maxAverageMs = max(0, (float) $this->option('max-average-ms'));
        $maxQueryCount = max(0, (int) $this->option('max-query-count'));
        $results = [];

        DB::enableQueryLog();

        foreach ($this->endpoints() as $endpoint) {
            $this->request($kernel, $endpoint);
            $durations = [];
            $queryCounts = [];
            $statuses = [];
            $error = null;

            for ($run = 0; $run < $runs; $run++) {
                DB::flushQueryLog();
                $startedAt = hrtime(true);

                try {
                    $response = $this->request($kernel, $endpoint);
                    $statuses[] = $response['status'];
                } catch (Throwable $exception) {
                    $statuses[] = 500;
                    $error = $exception->getMessage();
                }

                $durations[] = (hrtime(true) - $startedAt) / 1_000_000;
                $queryCounts[] = count(DB::getQueryLog());
            }

            sort($durations);
            $averageMs = array_sum($durations) / count($durations);
            $p95Index = max(0, (int) ceil(count($durations) * 0.95) - 1);
            $status = max($statuses);
            $queryCount = max($queryCounts);
            $passed = $status < 400
                && ($maxAverageMs === 0.0 || $averageMs <= $maxAverageMs)
                && ($maxQueryCount === 0 || $queryCount <= $maxQueryCount);

            $results[] = [
                'name' => $endpoint['name'],
                'path' => $endpoint['path'],
                'status' => $status,
                'average_ms' => round($averageMs, 2),
                'p95_ms' => round($durations[$p95Index], 2),
                'max_queries' => $queryCount,
                'passed' => $passed,
                'error' => $error,
            ];
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'runs_per_endpoint' => $runs,
            'limits' => [
                'max_average_ms' => $maxAverageMs,
                'max_query_count' => $maxQueryCount,
            ],
            'passed' => collect($results)->every('passed'),
            'results' => $results,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Endpoint', 'HTTP', 'Rata-rata', 'P95', 'Query maks.', 'Hasil'],
                collect($results)->map(fn (array $result): array => [
                    $result['path'],
                    $result['status'],
                    $result['average_ms'].' ms',
                    $result['p95_ms'].' ms',
                    $result['max_queries'],
                    $result['passed'] ? 'LULUS' : 'GAGAL',
                ])->all(),
            );
        }

        return $report['passed'] ? self::SUCCESS : self::FAILURE;
    }

    private function endpoints(): array
    {
        return [
            ['name' => 'login', 'path' => '/admin/login'],
            ['name' => 'queue_status', 'path' => '/queue-status'],
            [
                'name' => 'queue_kiosk',
                'path' => '/kiosk/cetak-antrian',
                'token' => (string) config('devices.kiosk.token'),
            ],
            ['name' => 'tv_landing', 'path' => '/tv'],
            [
                'name' => 'tv_zone_1',
                'path' => '/tv1',
                'token' => (string) config('devices.tv.tokens.1'),
            ],
            ['name' => 'tv_queue_status_api', 'path' => '/api/tv-display/queue-status', 'json' => true],
            ['name' => 'announcement_api', 'path' => '/api/tv-display/latest-announcement', 'json' => true],
        ];
    }

    private function request(Kernel $kernel, array $endpoint): array
    {
        $server = [
            'HTTP_ACCEPT' => ($endpoint['json'] ?? false) ? 'application/json' : 'text/html',
        ];

        if (($endpoint['token'] ?? '') !== '') {
            $server['HTTP_X_DEVICE_TOKEN'] = $endpoint['token'];
        }

        $request = Request::create($endpoint['path'], 'GET', [], [], [], $server);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return ['status' => $response->getStatusCode()];
    }
}
