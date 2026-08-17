<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MasterDataCache
{
    private const VERSION_KEY = 'master-data:version';

    public function remember(string $key, Closure $callback, int $seconds = 600): mixed
    {
        $version = Cache::rememberForever(self::VERSION_KEY, fn (): string => (string) Str::uuid());

        return Cache::remember("master-data:{$version}:{$key}", $seconds, $callback);
    }

    public function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());
    }
}
