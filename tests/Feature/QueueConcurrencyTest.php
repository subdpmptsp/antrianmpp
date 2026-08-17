<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Queue;
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
        $firstCounter = $this->createCounter('LOKET A');
        $secondCounter = $this->createCounter('LOKET B');
        $service = $this->createService();
        $firstCounter->update(['service_id' => $service->id]);
        $secondCounter->update(['service_id' => $service->id]);
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
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

                return trim($process->getOutput());
            }, $processes);
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    private function createCounter(string $name): Counter
    {
        return Counter::query()->create(['name' => $name, 'is_active' => true]);
    }

    private function createService(): Service
    {
        return Service::query()->create([
            'name' => 'LAYANAN CONCURRENCY',
            'prefix' => 'C',
            'padding' => 3,
            'is_active' => true,
        ]);
    }
}
