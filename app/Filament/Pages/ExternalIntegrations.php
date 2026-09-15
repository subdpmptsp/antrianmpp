<?php

namespace App\Filament\Pages;

use App\Models\ExternalIntegrationHealthCheck;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class ExternalIntegrations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static ?string $navigationLabel = 'Integrasi Eksternal';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Integrasi Eksternal';

    protected static ?string $slug = 'integrasi-eksternal';

    protected static string $view = 'filament.pages.external-integrations';

    public string $activeIntegration = 'overview';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public function selectIntegration(string $integration): void
    {
        if (in_array($integration, ['overview', 'panrb', 'dukcapil'], true)) {
            $this->activeIntegration = $integration;
        }
    }

    public function testConnection(string $integration): void
    {
        abort_unless(static::canAccess(), 403);

        if (! in_array($integration, ['panrb', 'dukcapil'], true)) {
            abort(404);
        }

        $rateLimitKey = 'external-integration-test:'.auth()->id().':'.$integration;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Notification::make()->title('Terlalu banyak pengujian')->body("Coba kembali dalam {$seconds} detik.")->warning()->send();

            return;
        }
        RateLimiter::hit($rateLimitKey, 60);

        $connection = $this->connectionConfiguration($integration);
        if ($connection['missing'] !== []) {
            $message = 'Lengkapi '.implode(', ', $connection['missing']).' pada .env server.';
            $this->recordCheck($integration, false, null, null, 'configuration_incomplete', $message);
            Notification::make()->title('Konfigurasi belum lengkap')->body($message)->danger()->send();

            return;
        }

        if (app()->environment('production') && parse_url($connection['url'], PHP_URL_SCHEME) !== 'https') {
            $message = 'Endpoint production wajib menggunakan HTTPS.';
            $this->recordCheck($integration, false, null, null, 'insecure_endpoint', $message);
            Notification::make()->title('Endpoint tidak aman')->body($message)->danger()->send();

            return;
        }

        $startedAt = hrtime(true);
        try {
            $response = Http::acceptJson()
                ->withHeaders($connection['headers'])
                ->timeout($connection['timeout'])
                ->get($connection['url']);
            $duration = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            $successful = $response->successful();
            $message = $successful
                ? 'Koneksi berhasil dan credential diterima server.'
                : $this->safeHttpFailureMessage($response->status());

            $this->recordCheck($integration, $successful, $response->status(), $duration, $successful ? null : 'http_'.$response->status(), $message);
            Notification::make()
                ->title($successful ? 'Uji koneksi berhasil' : 'Uji koneksi gagal')
                ->body("HTTP {$response->status()} · {$duration} ms. {$message}")
                ->color($successful ? 'success' : 'danger')
                ->send();
        } catch (ConnectionException) {
            $duration = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            $message = 'Server tidak dapat dijangkau. Periksa URL, DNS, firewall, whitelist IP, dan koneksi jaringan.';
            $this->recordCheck($integration, false, null, $duration, 'connection_failed', $message);
            Notification::make()->title('Koneksi tidak tersambung')->body($message)->danger()->send();
        } catch (Throwable) {
            $duration = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            $message = 'Pengujian gagal diproses. Periksa log server menggunakan ID pemeriksaan terbaru.';
            $this->recordCheck($integration, false, null, $duration, 'internal_error', $message);
            Notification::make()->title('Uji koneksi gagal')->body($message)->danger()->send();
        }
    }

    public function getRequestsLastMinuteProperty(): int
    {
        return ExternalIntegrationHealthCheck::query()
            ->where('integration', $this->activeIntegration)
            ->where('created_at', '>=', now()->subMinute())
            ->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ExternalIntegrationHealthCheck::query()->where('integration', $this->activeIntegration))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H.i', timezone: 'Asia/Jakarta')->sortable(),
                IconColumn::make('successful')->label('Status')->boolean(),
                TextColumn::make('http_status')->label('HTTP')->placeholder('—'),
                TextColumn::make('duration_ms')->label('Durasi')->formatStateUsing(fn (?int $state): string => $state !== null ? $state.' ms' : '—'),
                TextColumn::make('message')->label('Keterangan')->wrap()->limit(120),
                TextColumn::make('checkedBy.name')->label('Admin')->placeholder('Sistem'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Belum ada pengujian koneksi')
            ->emptyStateDescription('Riwayat aman akan tampil setelah tombol Uji koneksi digunakan.')
            ->emptyStateIcon('heroicon-o-signal');
    }

    /** @return array{configured: bool, enabled: bool, credential: bool, label: string, color: string} */
    public function panrbStatus(): array
    {
        $credential = filled(config('external_integrations.panrb.api_key'));
        $configured = filled(config('external_integrations.panrb.base_url')) && $credential;
        $enabled = (bool) config('external_integrations.panrb.enabled') && $configured;

        return $this->status($configured, $enabled, $credential);
    }

    /** @return array{configured: bool, enabled: bool, credential: bool, label: string, color: string} */
    public function dukcapilStatus(): array
    {
        $authType = (string) config('citizen_identity.auth_type', 'bearer');
        $credential = match ($authType) {
            'client_credentials' => filled(config('citizen_identity.client_id')) && filled(config('citizen_identity.client_secret')),
            'none' => true,
            default => filled(config('citizen_identity.token')),
        };
        $configured = config('citizen_identity.driver', 'disabled') !== 'disabled'
            && filled(config('citizen_identity.endpoint'))
            && $credential;
        $enabled = (bool) config('citizen_identity.enabled') && $configured;

        return $this->status($configured, $enabled, $credential);
    }

    /** @return array{configured: bool, enabled: bool, credential: bool, label: string, color: string} */
    private function status(bool $configured, bool $enabled, bool $credential): array
    {
        return [
            'configured' => $configured,
            'enabled' => $enabled,
            'credential' => $credential,
            'label' => $enabled ? 'Aktif' : ($configured ? 'Siap diuji' : 'Belum dikonfigurasi'),
            'color' => $enabled ? 'success' : ($configured ? 'info' : 'warning'),
        ];
    }

    /** @return array{url: string, headers: array<string, string>, timeout: int, missing: array<int, string>} */
    private function connectionConfiguration(string $integration): array
    {
        if ($integration === 'panrb') {
            $baseUrl = rtrim((string) config('external_integrations.panrb.base_url'), '/');
            $endpoint = '/'.ltrim((string) config('external_integrations.panrb.endpoints.ping', '/sync/ping'), '/');
            $apiKey = (string) config('external_integrations.panrb.api_key');
            $header = (string) config('external_integrations.panrb.auth_header', 'X-API-Key');
            $missing = [];
            if ($baseUrl === '') $missing[] = 'PANRB_API_BASE_URL';
            if ($apiKey === '') $missing[] = 'PANRB_API_KEY';

            return [
                'url' => $baseUrl.$endpoint,
                'headers' => $apiKey !== '' ? [$header => $apiKey] : [],
                'timeout' => max(1, (int) config('external_integrations.panrb.timeout_seconds', 10)),
                'missing' => $missing,
            ];
        }

        $url = (string) (config('citizen_identity.health_endpoint') ?: config('citizen_identity.endpoint'));
        $authType = (string) config('citizen_identity.auth_type', 'bearer');
        $token = (string) config('citizen_identity.token');
        $missing = [];
        if ($url === '') $missing[] = 'DUKCAPIL_API_HEALTH_ENDPOINT';
        if ($authType === 'bearer' && $token === '') $missing[] = 'DUKCAPIL_API_TOKEN';
        if ($authType === 'client_credentials') $missing[] = 'dokumentasi endpoint OAuth client_credentials';

        return [
            'url' => $url,
            'headers' => $authType === 'bearer' && $token !== '' ? ['Authorization' => 'Bearer '.$token] : [],
            'timeout' => max(1, (int) config('citizen_identity.timeout_seconds', 5)),
            'missing' => $missing,
        ];
    }

    private function recordCheck(string $integration, bool $successful, ?int $status, ?int $duration, ?string $errorCode, string $message): void
    {
        ExternalIntegrationHealthCheck::query()->create([
            'integration' => $integration,
            'successful' => $successful,
            'http_status' => $status,
            'duration_ms' => $duration,
            'error_code' => $errorCode,
            'message' => $message,
            'checked_by' => auth()->id(),
        ]);
    }

    private function safeHttpFailureMessage(int $status): string
    {
        return match ($status) {
            401 => 'Credential ditolak. Periksa API key/token dan header autentikasi.',
            403 => 'Akses ditolak. Periksa izin credential atau whitelist IP.',
            404 => 'Endpoint tidak ditemukan. Periksa base URL dan endpoint uji koneksi.',
            408, 504 => 'Server tujuan mengalami timeout.',
            429 => 'Batas permintaan provider tercapai. Tunggu sebelum mencoba kembali.',
            default => $status >= 500
                ? 'Server penyedia sedang bermasalah. Coba kembali atau hubungi pengelola API.'
                : 'Server menolak permintaan. Periksa dokumentasi dan konfigurasi integrasi.',
        };
    }
}
