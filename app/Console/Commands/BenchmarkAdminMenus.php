<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class BenchmarkAdminMenus extends Command
{
    protected $signature = 'app:benchmark-admin
        {--runs=3 : Jumlah pengukuran per menu setelah warm-up}
        {--max-average-ms=500 : Batas rata-rata milidetik per menu}
        {--max-query-count=40 : Batas query maksimum per request}
        {--json : Tampilkan hasil dalam JSON}';

    protected $description = 'Ukur waktu respons dan jumlah query seluruh menu admin tanpa memerlukan password';

    public function handle(Kernel $kernel): int
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();

        if (! $admin) {
            $this->error('Akun admin tidak ditemukan.');

            return self::FAILURE;
        }

        $runs = max(1, min(20, (int) $this->option('runs')));
        $maxAverageMs = max(0, (float) $this->option('max-average-ms'));
        $maxQueryCount = max(0, (int) $this->option('max-query-count'));
        $results = [];

        // Benchmark tidak perlu menulis sesi baru ke database operasional.
        config()->set('session.driver', 'array');
        DB::enableQueryLog();

        foreach ($this->menus() as $menu) {
            $this->request($kernel, $admin, $menu['path']);
            $durations = [];
            $queryCounts = [];
            $statuses = [];
            $error = null;

            for ($run = 0; $run < $runs; $run++) {
                DB::flushQueryLog();
                $startedAt = hrtime(true);

                try {
                    $statuses[] = $this->request($kernel, $admin, $menu['path']);
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
                'name' => $menu['name'],
                'path' => $menu['path'],
                'status' => $status,
                'average_ms' => round($averageMs, 2),
                'p95_ms' => round($durations[$p95Index], 2),
                'max_queries' => $queryCount,
                'passed' => $passed,
                'error' => $error,
            ];
        }

        Auth::forgetGuards();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'runs_per_menu' => $runs,
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
                ['Menu', 'HTTP', 'Rata-rata', 'P95', 'Query maks.', 'Hasil'],
                collect($results)->map(fn (array $result): array => [
                    $result['name'],
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

    private function request(Kernel $kernel, User $admin, string $path): int
    {
        Auth::shouldUse('web');
        Auth::guard('web')->setUser($admin);

        $request = Request::create($path, 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->setUserResolver(fn (): User => $admin);

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response->getStatusCode();
    }

    /** @return array<int, array{name: string, path: string}> */
    private function menus(): array
    {
        return [
            ['name' => 'Pengguna', 'path' => '/admin/users'],
            ['name' => 'Loket', 'path' => '/admin/counters'],
            ['name' => 'Instansi', 'path' => '/admin/instansis'],
            ['name' => 'Antrian', 'path' => '/admin/queues'],
            ['name' => 'Layanan', 'path' => '/admin/services'],
            ['name' => 'Pengaturan', 'path' => '/admin/settings'],
            ['name' => 'Absensi', 'path' => '/admin/attendances'],
            ['name' => 'Manajemen Audio', 'path' => '/admin/audio-management-page'],
            ['name' => 'Kiosk Ruang Tunggu', 'path' => '/admin/dashboard-kiosk'],
            ['name' => 'Monitoring', 'path' => '/admin/monitoring-dashboard'],
            ['name' => 'Monitoring Real-Time', 'path' => '/admin/monitoring-dashboard-real-time'],
            ['name' => 'Kiosk Cetak', 'path' => '/admin/queue-kiosk'],
            ['name' => 'Test Print', 'path' => '/admin/test-print-page'],
            ['name' => 'Loket Panggilan', 'path' => '/admin/dashboard-call-kiosk'],
        ];
    }
}
