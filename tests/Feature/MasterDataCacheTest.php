<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Services\MasterDataCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_is_cached_and_model_changes_invalidate_it(): void
    {
        $cache = app(MasterDataCache::class);
        $callbackRuns = 0;

        $first = $cache->remember('test-key', function () use (&$callbackRuns): int {
            return ++$callbackRuns;
        });
        $second = $cache->remember('test-key', function () use (&$callbackRuns): int {
            return ++$callbackRuns;
        });

        $this->assertSame(1, $first);
        $this->assertSame(1, $second);
        $this->assertSame(1, $callbackRuns);

        Service::query()->create([
            'name' => 'LAYANAN INVALIDASI',
            'prefix' => 'CI',
            'padding' => 3,
            'is_active' => true,
        ]);

        $afterInvalidation = $cache->remember('test-key', function () use (&$callbackRuns): int {
            return ++$callbackRuns;
        });

        $this->assertSame(2, $afterInvalidation);
        $this->assertSame(2, $callbackRuns);
    }
}
