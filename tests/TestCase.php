<?php

namespace Tests;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Service;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    /** Helpers keep test fixtures aligned with Instansi -> Layanan -> Loket. */
    protected function createTestInstitution(string $name = 'Instansi Uji', string $zone = 'ZONA 1'): Instansi
    {
        return Instansi::query()->create([
            'nama_instansi' => $name,
            'zone' => $zone,
            'is_active' => true,
        ]);
    }

    protected function createTestService(Instansi $instansi, array $attributes = []): Service
    {
        return Service::query()->create(array_merge([
            'instansi_id' => $instansi->instansi_id,
            'name' => 'Layanan Uji',
            'prefix' => 'T',
            'padding' => 3,
            'is_active' => true,
        ], $attributes));
    }

    protected function createTestCounter(Instansi $instansi, ?Service $service, array $attributes = []): Counter
    {
        return Counter::query()->create(array_merge([
            'code_loket' => 'TEST-'.uniqid(),
            'instansi_id' => $instansi->instansi_id,
            'service_id' => $service?->id,
            'is_active' => $service !== null,
        ], $attributes));
    }

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");
        $usesIsolatedDatabase = ($connection === 'sqlite' && $database === ':memory:')
            || preg_match('/(?:_|-)test(?:ing)?$/i', $database) === 1;

        if (! $app->environment('testing') || ! $usesIsolatedDatabase) {
            throw new LogicException(
                "Test dibatalkan: environment '{$app->environment()}' dengan database '{$database}' bukan database testing terisolasi.",
            );
        }

        return $app;
    }
}
