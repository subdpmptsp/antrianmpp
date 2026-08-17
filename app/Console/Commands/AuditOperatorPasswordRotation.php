<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class AuditOperatorPasswordRotation extends Command
{
    protected $signature = 'app:operator-password-audit
        {--since= : Batas waktu rotasi dalam format ISO-8601}';

    protected $description = 'Pastikan seluruh password petugas sudah dirotasi dan tetap menggunakan hash';

    public function handle(): int
    {
        try {
            $cutoff = CarbonImmutable::parse(
                $this->option('since') ?: config('security.operator_password_rotation_after'),
            );
        } catch (Throwable) {
            $this->error('Nilai --since tidak valid. Gunakan format ISO-8601.');

            return self::FAILURE;
        }

        $operators = User::query()->where('role', User::ROLE_OPERATOR)->get();
        $notRotated = $operators->filter(
            fn (User $user): bool => ! $user->password_changed_at
                || $user->password_changed_at->lt($cutoff),
        );
        $invalidHashes = $operators->filter(
            fn (User $user): bool => password_get_info((string) $user->getRawOriginal('password'))['algoName'] === 'unknown',
        );

        $this->line('Batas rotasi: '.$cutoff->toIso8601String());
        $this->line('Total petugas: '.$operators->count());
        $this->line('Belum rotasi: '.$notRotated->count());
        $this->line('Hash tidak valid: '.$invalidHashes->count());

        if ($notRotated->isNotEmpty() || $invalidHashes->isNotEmpty()) {
            $this->error('Rotasi password petugas belum selesai. Reset melalui Manajemen Pengguna dan distribusikan secara aman.');

            return self::FAILURE;
        }

        $this->info('Seluruh password petugas telah dirotasi dan tersimpan sebagai hash.');

        return self::SUCCESS;
    }
}
