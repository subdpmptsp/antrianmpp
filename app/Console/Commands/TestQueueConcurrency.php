<?php

namespace App\Console\Commands;

use App\Services\QueueService;
use Illuminate\Console\Command;

class TestQueueConcurrency extends Command
{
    protected $signature = 'queue:test-concurrency
        {action : claim atau create}
        {id : counter ID untuk claim atau service ID untuk create}
        {barrier : file yang melepas proses secara bersamaan}';

    protected $description = 'Internal test helper for real multi-process queue concurrency';

    public function handle(QueueService $queues): int
    {
        if (!app()->environment('testing')) {
            $this->error('Command ini hanya tersedia pada environment testing.');

            return self::FAILURE;
        }

        $deadline = microtime(true) + 10;
        $barrier = (string) $this->argument('barrier');

        while (!is_file($barrier)) {
            if (microtime(true) >= $deadline) {
                $this->error('Concurrency barrier timeout.');

                return self::FAILURE;
            }

            usleep(10_000);
        }

        $id = (int) $this->argument('id');

        if ($this->argument('action') === 'claim') {
            $queue = $queues->callNextQueue($id);
            $this->line($queue ? (string) $queue->id : 'null');

            return self::SUCCESS;
        }

        if ($this->argument('action') === 'create') {
            $this->line($queues->addQueue($id)->number);

            return self::SUCCESS;
        }

        $this->error('Action tidak dikenal.');

        return self::INVALID;
    }
}
