<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditProductionConfiguration extends Command
{
    protected $signature = 'app:production-audit';

    protected $description = 'Validasi konfigurasi penting sebelum deployment produksi';

    public function handle(): int
    {
        $failures = [];
        $warnings = [];
        $url = (string) config('app.url');
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));
        $database = (string) config('database.connections.'.config('database.default').'.database');
        $databaseConfig = (array) config('database.connections.'.config('database.default'), []);
        $logStack = (array) config('logging.channels.stack.channels', []);

        $this->check(config('app.env') === 'production', 'APP_ENV menggunakan production', $failures);
        $this->check(config('app.debug') === false, 'APP_DEBUG sudah nonaktif', $failures);
        $this->check(
            $this->isConfiguredValue(config('app.key')),
            'APP_KEY sudah terpasang',
            $failures,
        );
        $this->check(
            filter_var($url, FILTER_VALIDATE_URL) !== false
                && ! in_array($host, ['', 'localhost', '127.0.0.1', '::1'], true)
                && ! str_contains($host, 'change-me')
                && ! str_ends_with($host, '.invalid')
                && ! str_ends_with($host, '.test')
                && ! str_ends_with($host, '.example'),
            'APP_URL mengarah ke alamat server',
            $failures,
        );
        $this->check(
            $database !== ''
                && $database !== ':memory:'
                && preg_match('/(?:_|-)test(?:ing)?$/i', $database) !== 1,
            'Database bukan database pengujian',
            $failures,
        );
        $this->check(in_array('daily', $logStack, true), 'Log harian aktif', $failures);
        $this->check(is_writable(storage_path()), 'Folder storage dapat ditulis', $failures);
        if (in_array(config('database.default'), ['mysql', 'mariadb', 'pgsql', 'sqlsrv'], true)) {
            $this->check(
                $this->isConfiguredValue($databaseConfig['username'] ?? null)
                    && $this->isConfiguredValue($databaseConfig['password'] ?? null),
                'Kredensial database produksi sudah diisi',
                $failures,
            );
        }

        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            $warnings[] = 'APP_URL belum menggunakan HTTPS.';
        } elseif (config('session.secure') !== true) {
            $failures[] = 'SESSION_SECURE_COOKIE wajib true saat APP_URL menggunakan HTTPS.';
        }

        if (config('devices.auth_enabled')) {
            $this->check($this->isConfiguredValue(config('devices.kiosk.token')), 'Token perangkat kiosk terpasang', $failures);

            foreach (array_keys((array) config('tv.zones', [])) as $zoneNumber) {
                $this->check(
                    $this->isConfiguredValue(config("devices.tv.tokens.{$zoneNumber}")),
                    "Token perangkat TV zona {$zoneNumber} terpasang",
                    $failures,
                );
            }
        } else {
            $warnings[] = 'Otorisasi perangkat masih nonaktif; aktifkan setelah token terpasang pada perangkat fisik.';
        }

        foreach ($warnings as $warning) {
            $this->warn("[PERINGATAN] {$warning}");
        }

        if ($failures !== []) {
            foreach ($failures as $failure) {
                $this->error("[GAGAL] {$failure}");
            }

            $this->newLine();
            $this->error('Deployment dihentikan: perbaiki konfigurasi yang gagal di atas.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Konfigurasi produksi lolos pemeriksaan wajib.');

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

    private function isConfiguredValue(mixed $value): bool
    {
        return is_string($value)
            && trim($value) !== ''
            && preg_match('/change[\s_-]*me/i', $value) !== 1;
    }
}
