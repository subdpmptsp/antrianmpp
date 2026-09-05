<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\QueueOperatingSetting;
use App\Models\Service;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class QueueConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--force' => true]);
        QueueOperatingSetting::query()->update([
            'weekly_schedule' => collect(range(1, 7))->map(fn (int $day) => [
                'day' => $day, 'is_open' => true, 'opens_at' => '00:00', 'closes_at' => '23:59',
            ])->all(),
            'cutoff_minutes' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
        } finally {
            parent::tearDown();
        }
    }

    public function test_two_operators_cannot_claim_the_same_ticket(): void
    {
        $service = $this->createService();
        $firstCounter = $this->createCounter('LOKET A', $service);
        $secondCounter = $this->createCounter('LOKET B', $service);
        $queue = Queue::query()->create([
            'service_id' => $service->id,
            'number' => 'C-001',
            'status' => Queue::STATUS_WAITING,
        ]);

        $results = $this->runConcurrently('claim', [$firstCounter->id, $secondCounter->id]);

        $this->assertCount(1, array_filter($results, fn (string $result): bool => $result === (string) $queue->id));
        $this->assertCount(1, array_filter($results, fn (string $result): bool => $result === 'null'));
        $this->assertSame(Queue::STATUS_CALLED, $queue->refresh()->status);
    }

    public function test_two_kiosks_generate_distinct_sequential_numbers(): void
    {
        $service = $this->createService();
        $this->createCounter('LOKET KIOSK', $service);

        $results = $this->runConcurrently('create', [$service->id, $service->id]);
        sort($results);

        $this->assertSame(['C-001', 'C-002'], $results);
        $this->assertSame(2, Queue::query()->distinct()->count('number'));
    }

    private function runConcurrently(string $action, array $ids): array
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'queue-barrier-');
        $this->assertNotFalse($temporaryFile);
        unlink($temporaryFile);

        $processes = array_map(function (int $id) use ($action, $temporaryFile): Process {
            $process = new Process([
                PHP_BINARY,
                base_path('artisan'),
                'queue:test-concurrency',
                $action,
                (string) $id,
                $temporaryFile,
                '--env=testing',
            ], base_path());
            $process->setTimeout(20);
            $process->start();

            return $process;
        }, $ids);

        try {
            file_put_contents($temporaryFile, 'go');

            return array_map(function (Process $process): string {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput()."\n".$process->getOutput());

                return trim($process->getOutput());
            }, $processes);
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    private function createCounter(string $name, Service $service): Counter
    {
        return $this->createTestCounter($service->instansi, $service, ['code_loket' => $name]);
    }

    private function createService(): Service
    {
        $institution = $this->createTestInstitution('INSTANSI CONCURRENCY', 'ZONA 1');

        return $this->createTestService($institution, [
            'name' => 'LAYANAN CONCURRENCY',
            'prefix' => 'C',
            'padding' => 3,
            'is_active' => true,
        ]);
    }
}
