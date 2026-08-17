<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;

abstract class TestCase extends BaseTestCase
{
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
