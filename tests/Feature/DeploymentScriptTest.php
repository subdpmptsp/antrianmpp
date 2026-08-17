<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeploymentScriptTest extends TestCase
{
    public function test_production_deployment_enforces_audits_caches_and_benchmark(): void
    {
        $script = file_get_contents(base_path('scripts/deploy-production.ps1'));

        $this->assertIsString($script);
        $this->assertStringContainsString("'app:production-audit'", $script);
        $this->assertStringContainsString("'Zend OPcache'", $script);
        $this->assertStringContainsString("'app:operator-password-audit'", $script);
        $this->assertStringContainsString("'app:data-integrity-audit'", $script);
        $this->assertStringContainsString("'optimize'", $script);
        $this->assertStringContainsString("'app:benchmark-endpoints'", $script);
        $this->assertStringContainsString("'up'", $script);
    }
}
