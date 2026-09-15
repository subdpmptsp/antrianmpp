<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MppBrandingService
{
    private const CACHE_KEY = 'mpp.branding.v4';

    /**
     * Return the single branding record with safe defaults for fresh installs.
     *
     * @return array{name: string, address: string, phone: ?string, logo_url: string, image_size: string, landing_logo_url: string, landing_city_logo_url: ?string, landing_logo_size: string, landing_city_logo_size: string, landing_hero_image_url: string, landing_hero_image_size: string, kiosk_logo_url: string, kiosk_logo_size: string, kiosk_office_logo_url: string, kiosk_office_logo_size: string}
     */
    public function current(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            // Panel provider juga berjalan saat database baru belum dimigrasikan
            // (misalnya test/fresh install), jadi branding harus aman tanpa tabel.
            $setting = Schema::hasTable('settings') ? Setting::query()->first() : null;

            $logoUrl = $setting?->image
                ? Storage::disk('public')->url($setting->image)
                : asset('img/logopemkot_white.png');
            $landingLogoSize = in_array($setting?->landing_logo_size, ['small', 'medium', 'large'], true)
                ? $setting->landing_logo_size
                : 'medium';
            $imageSize = in_array($setting?->image_size, ['small', 'medium', 'large'], true)
                ? $setting->image_size
                : 'medium';
            $landingCityLogoSize = in_array($setting?->landing_city_logo_size, ['small', 'medium', 'large'], true)
                ? $setting->landing_city_logo_size
                : 'medium';
            $landingHeroImageSize = in_array($setting?->landing_hero_image_size, ['small', 'medium', 'large'], true)
                ? $setting->landing_hero_image_size
                : 'medium';
            $kioskLogoSize = in_array($setting?->kiosk_logo_size, ['small', 'medium', 'large'], true)
                ? $setting->kiosk_logo_size
                : 'medium';
            $kioskOfficeLogoSize = in_array($setting?->kiosk_office_logo_size, ['small', 'medium', 'large'], true)
                ? $setting->kiosk_office_logo_size
                : 'medium';

            return [
                'name' => $setting?->name ?: 'Mal Pelayanan Publik Siola',
                'address' => $setting?->address ?: 'Jl. Tunjungan No. 1-3, Genteng, Surabaya',
                'phone' => $setting?->phone,
                'logo_url' => $logoUrl,
                'image_size' => $imageSize,
                // Landing Page boleh memakai versi logo khusus. Bila kosong,
                // otomatis memakai Logo Utama MPP agar tidak perlu upload ganda.
                'landing_logo_url' => $setting?->landing_logo
                    ? Storage::disk('public')->url($setting->landing_logo)
                    : ($setting?->image ? $logoUrl : asset('images/siola-queue-login-logo.png')),
                'landing_city_logo_url' => $setting?->landing_city_logo
                    ? Storage::disk('public')->url($setting->landing_city_logo)
                    : null,
                'landing_logo_size' => $landingLogoSize,
                'landing_city_logo_size' => $landingCityLogoSize,
                'landing_hero_image_url' => $setting?->landing_hero_image
                    ? Storage::disk('public')->url($setting->landing_hero_image)
                    : asset('img/bg.png'),
                'landing_hero_image_size' => $landingHeroImageSize,
                // Kiosk boleh memakai berkas khusus agar logo pada layar sentuh
                // tidak perlu disamakan dengan Landing Page atau panel admin.
                'kiosk_logo_url' => $setting?->kiosk_logo
                    ? Storage::disk('public')->url($setting->kiosk_logo)
                    : $logoUrl,
                'kiosk_logo_size' => $kioskLogoSize,
                'kiosk_office_logo_url' => $setting?->kiosk_office_logo
                    ? Storage::disk('public')->url($setting->kiosk_office_logo)
                    : asset('img/dpmptsp.png'),
                'kiosk_office_logo_size' => $kioskOfficeLogoSize,
            ];
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
