<?php

namespace App\Console\Commands;

use App\Models\DeviceRegistration;
use App\Services\TvZoneResolver;
use Illuminate\Console\Command;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class SystemInventory extends Command
{
    protected $signature = 'app:system-inventory {--json : Tampilkan hasil dalam JSON}';

    protected $description = 'Inventaris endpoint, jaringan, database, dan kesiapan perangkat tanpa membuka rahasia';

    public function handle(TvZoneResolver $zoneResolver): int
    {
        $inventory = $this->inventory($zoneResolver);

        if ($this->option('json')) {
            $this->line((string) json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Inventaris Sistem Antrian');
        $this->line('Dibuat: '.$inventory['generated_at']);
        $this->line('Aplikasi: '.$inventory['application']['name'].' ('.$inventory['application']['environment'].')');
        $this->line('URL: '.$inventory['application']['url']);
        $this->line('Host: '.$inventory['network']['hostname']);
        $this->line('Alamat lokal: '.implode(', ', $inventory['network']['local_addresses']));
        $this->line('Database: '.$inventory['database']['driver'].' @ '.$inventory['database']['host'].':'.$inventory['database']['port']);
        $this->line('Otorisasi perangkat: '.($inventory['devices']['authorization_enabled'] ? 'aktif' : 'nonaktif'));

        $this->newLine();
        $this->table(
            ['Perangkat', 'Token siap'],
            collect($inventory['devices']['token_readiness'])
                ->map(fn (bool $ready, string $device): array => [$device, $ready ? 'Ya' : 'Tidak'])
                ->values()
                ->all(),
        );
        $this->table(
            ['Zona', 'Nama', 'Counter ID', 'Token siap'],
            collect($inventory['zones'])
                ->map(fn (array $zone): array => [
                    $zone['zone_number'],
                    $zone['name'],
                    $zone['counter_id'] ?? '-',
                    $zone['token_configured'] ? 'Ya' : 'Tidak',
                ])
                ->all(),
        );
        $this->table(
            ['Nama route', 'Method', 'Endpoint', 'Middleware'],
            collect($inventory['endpoints'])
                ->map(fn (array $endpoint): array => [
                    $endpoint['name'],
                    implode('|', $endpoint['methods']),
                    '/'.$endpoint['uri'],
                    implode(', ', $endpoint['middleware']),
                ])
                ->all(),
        );

        $this->warn('Nilai token tidak pernah ditampilkan. Printer tetap harus diverifikasi pada browser/perangkat fisik.');

        return self::SUCCESS;
    }

    private function inventory(TvZoneResolver $zoneResolver): array
    {
        $connection = (string) config('database.default');
        $database = (array) config("database.connections.{$connection}", []);
        $hostname = gethostname() ?: 'unknown';
        $addresses = gethostbynamel($hostname) ?: [];
        $routeNames = [
            'filament.admin.auth.login',
            'public.queue-kiosk',
            'public.queue-kiosk.select-service',
            'tv.index',
            'tv.zona1',
            'tv.zona2',
            'tv.zona3',
            'tv.zona4',
            'tv.zona5',
            'api.tv.zone.queues',
            'api.tv.zone.services',
            'api.tv.latest-announcement',
            'queue.status',
        ];

        $endpoints = collect($routeNames)
            ->map(fn (string $name): ?LaravelRoute => Route::getRoutes()->getByName($name))
            ->filter()
            ->map(fn (LaravelRoute $route): array => [
                'name' => $route->getName(),
                'methods' => array_values(array_diff($route->methods(), ['HEAD'])),
                'uri' => $route->uri(),
                'middleware' => $route->gatherMiddleware(),
            ])
            ->values()
            ->all();

        $zones = collect((array) config('tv.zones', []))
            ->map(function (array $configuredZone, int|string $zoneNumber) use ($zoneResolver): array {
                $zoneNumber = (int) $zoneNumber;
                $counter = $zoneResolver->resolve($zoneNumber);

                return [
                    'zone_number' => $zoneNumber,
                    'name' => $counter?->name ?? ($configuredZone['name'] ?? $zoneResolver->fallbackName($zoneNumber)),
                    'counter_id' => $counter?->getKey(),
                    'token_configured' => filled(config("devices.tv.tokens.{$zoneNumber}")),
                ];
            })
            ->values()
            ->all();

        return [
            'generated_at' => now()->toIso8601String(),
            'application' => [
                'name' => (string) config('app.name'),
                'environment' => app()->environment(),
                'url' => (string) config('app.url'),
                'timezone' => (string) config('app.timezone'),
            ],
            'network' => [
                'hostname' => $hostname,
                'local_addresses' => array_values(array_unique($addresses)),
            ],
            'database' => [
                'driver' => $connection,
                'host' => (string) ($database['host'] ?? 'local-file'),
                'port' => (string) ($database['port'] ?? '-'),
                'database' => (string) ($database['database'] ?? '-'),
            ],
            'devices' => [
                'authorization_enabled' => (bool) config('devices.auth_enabled'),
                'token_readiness' => [
                    'kiosk' => filled(config('devices.kiosk.token')),
                    'tv_zones' => collect($zones)->every('token_configured'),
                ],
                'printer_mode' => 'browser-managed; verifikasi fisik diperlukan',
                'registrations' => Schema::hasTable('device_registrations')
                    ? DeviceRegistration::query()
                        ->orderBy('device_key')
                        ->get()
                        ->map(fn (DeviceRegistration $device): array => [
                            'device_key' => $device->device_key,
                            'device_type' => $device->device_type,
                            'zone_number' => $device->zone_number,
                            'ip_address' => $device->ip_address,
                            'user_agent' => $device->user_agent,
                            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                        ])
                        ->all()
                    : [],
            ],
            'zones' => $zones,
            'endpoints' => $endpoints,
        ];
    }
}
