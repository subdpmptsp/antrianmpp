<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MppBrandingService
{
    private const CACHE_KEY = 'mpp.branding';

    /**
     * Return the single branding record with safe defaults for fresh installs.
     *
     * @return array{name: string, address: string, phone: ?string, logo_url: string}
     */
    public function current(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $setting = Setting::query()->first();

            return [
                'name' => $setting?->name ?: 'Mal Pelayanan Publik Siola',
                'address' => $setting?->address ?: 'Jl. Tunjungan No. 1-3, Genteng, Surabaya',
                'phone' => $setting?->phone,
                'logo_url' => $setting?->image
                    ? Storage::disk('public')->url($setting->image)
                    : asset('img/logopemkot_white.png'),
            ];
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
