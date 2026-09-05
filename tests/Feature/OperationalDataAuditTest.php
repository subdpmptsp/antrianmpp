<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OperationalDataAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_consistent_operational_data_passes_audit(): void
    {
        $exitCode = Artisan::call('app:data-integrity-audit');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('lolos pemeriksaan wajib', Artisan::output());
    }

    public function test_inconsistent_operational_data_blocks_deployment(): void
    {
        $this->expectException(ValidationException::class);

        Service::query()->create([
            'name' => 'Layanan Tanpa Instansi',
            'prefix' => 'X',
            'padding' => 3,
            'is_active' => true,
        ]);
    }
}
