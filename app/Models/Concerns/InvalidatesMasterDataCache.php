<?php

namespace App\Models\Concerns;

use App\Services\MasterDataCache;

trait InvalidatesMasterDataCache
{
    protected static function bootInvalidatesMasterDataCache(): void
    {
        static::saved(function (): void {
            app(MasterDataCache::class)->invalidate();
        });
        static::deleted(function (): void {
            app(MasterDataCache::class)->invalidate();
        });
    }
}
