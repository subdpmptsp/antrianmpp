<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnlineQueueLandingBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_uses_configured_logos_and_size_from_mpp_branding(): void
    {
        Storage::fake('public');
        Setting::query()->create([
            'name' => 'MPP SIOLA Uji',
            'address' => 'Jl. Uji',
            'phone' => '031000000',
            'image' => 'branding/logo-utama/logo-global.png',
            'landing_logo' => 'branding/landing/logo-mpp.png',
            'landing_city_logo' => 'branding/landing/logo-pemkot.png',
            'landing_logo_size' => 'large',
            'landing_hero_image' => 'branding/landing/gedung-siola.webp',
            'landing_hero_image_size' => 'large',
        ]);

        $this->get(route('online-queue.index'))
            ->assertOk()
            ->assertSee('branding/landing/logo-mpp.png', false)
            ->assertSee('branding/landing/logo-pemkot.png', false)
            ->assertSee('branding/landing/gedung-siola.webp', false)
            ->assertSee('brand-logo--large', false)
            ->assertSee('hero-photo--large', false)
            ->assertSee('MPP SIOLA Uji');
    }
}
