<?php

namespace App\Console\Commands;

use App\Models\Queue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditOperationalData extends Command
{
    protected $signature = 'app:data-integrity-audit';

    protected $description = 'Validasi integritas data operasional setelah migrasi';

    public function handle(): int
    {
        $failures = [];

        $this->check(
            ! Schema::hasColumn('users', 'plain_password'),
            'Kolom plain_password sudah dihapus',
            $failures,
        );
        $this->checkCount(
            DB::table('users')->where('role', 'operator')->whereNull('counter_id')->count(),
            'Operator tanpa loket',
            $failures,
        );
        $this->checkCount(
            DB::table('services')->where('is_active', true)->whereNull('instansi_id')->count(),
            'Layanan aktif tanpa instansi',
            $failures,
        );
        $this->checkCount(
            DB::table('services')
                ->leftJoin('counters', 'counters.service_id', '=', 'services.id')
                ->where('services.is_active', true)
                ->whereNull('counters.id')
                ->count(),
            'Layanan aktif tanpa loket',
            $failures,
        );
        $this->checkCount(
            DB::table('counters')->where('is_active', true)->whereNull('service_id')->count(),
            'Loket aktif tanpa layanan',
            $failures,
        );
        $this->checkCount(
            DB::table('counters')
                ->join('services', 'services.id', '=', 'counters.service_id')
                ->whereNotNull('counters.instansi_id')
                ->whereColumn('counters.instansi_id', '!=', 'services.instansi_id')
                ->count(),
            'Instansi loket tidak sesuai dengan layanan',
            $failures,
        );
        $this->checkCount(
            DB::table('users')
                ->join('counters', 'counters.id', '=', 'users.counter_id')
                ->where('users.role', 'operator')
                ->whereNotNull('users.service_id')
                ->whereColumn('users.service_id', '!=', 'counters.service_id')
                ->count(),
            'Layanan operator tidak sesuai dengan loket',
            $failures,
        );
        $this->checkCount(
            DB::table('counter_service')->count(),
            'Relasi pivot loket-layanan lama yang belum dibersihkan',
            $failures,
        );
        $this->checkCount(
            DB::table('queues')->whereNotIn('status', Queue::VALID_STATUSES)->count(),
            'Antrian dengan status tidak resmi',
            $failures,
        );
        $this->checkCount(
            DB::table('queues')
                ->whereIn('status', [Queue::STATUS_CALLED, Queue::STATUS_SERVING, Queue::STATUS_FINISHED])
                ->whereNull('called_at')
                ->count(),
            'Antrian aktif/selesai tanpa called_at',
            $failures,
        );
        $this->checkCount(
            DB::table('queues')
                ->whereIn('status', [Queue::STATUS_SERVING, Queue::STATUS_FINISHED])
                ->whereNull('served_at')
                ->count(),
            'Antrian dilayani/selesai tanpa served_at',
            $failures,
        );
        $this->checkCount(
            DB::table('queues')
                ->where('status', Queue::STATUS_FINISHED)
                ->whereNull('finished_at')
                ->count(),
            'Antrian selesai tanpa finished_at',
            $failures,
        );
        $this->checkCount(
            DB::table('queues')
                ->where('status', Queue::STATUS_CANCELED)
                ->whereNull('canceled_at')
                ->count(),
            'Antrian batal tanpa canceled_at',
            $failures,
        );

        if ($failures !== []) {
            foreach ($failures as $failure) {
                $this->error("[GAGAL] {$failure}");
            }

            $this->newLine();
            $this->error('Deployment dihentikan: perbaiki data operasional yang gagal di atas.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Integritas data operasional lolos pemeriksaan wajib.');

        return self::SUCCESS;
    }

    private function check(bool $condition, string $label, array &$failures): void
    {
        if ($condition) {
            $this->info("[OK] {$label}");

            return;
        }

        $failures[] = $label;
    }

    private function checkCount(int $count, string $label, array &$failures): void
    {
        if ($count === 0) {
            $this->info("[OK] {$label}: 0");

            return;
        }

        $failures[] = "{$label}: {$count}";
    }
}
