<?php

namespace Tests\Feature;

use App\Models\Counter;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TestingSeeder::class);
    }

    #[DataProvider('publicEndpoints')]
    public function test_public_endpoint_is_available(string $uri, int $expectedStatus = 200): void
    {
        $response = $this->get($uri);

        $response->assertStatus($expectedStatus);
    }

    public static function publicEndpoints(): array
    {
        return [
            'admin login' => ['/admin/login'],
            'queue status empty state' => ['/queue-status'],
            'public queue kiosk' => ['/kiosk/cetak-antrian'],
            'main tv' => ['/tv'],
            'tv zone 1' => ['/tv1'],
            'tv zone 2' => ['/tv2'],
            'tv zone 3' => ['/tv3'],
            'tv zone 4' => ['/tv4'],
            'tv zone 5' => ['/tv5'],
            'tv queue status api' => ['/api/tv-display/queue-status'],
            'skck mpp' => ['/antrian-skck-mpp'],
            'test receipt preview is protected' => ['/struk/test', 302],
        ];
    }

    public function test_legacy_tv_urls_redirect_to_official_landing(): void
    {
        foreach (['/tampilan-tv', '/tv-display-legacy', '/tv-display-enhanced', '/tv-display-optimized'] as $url) {
            $this->get($url)->assertRedirect(route('tv.index'));
        }
    }

    public function test_official_zone_display_is_read_only(): void
    {
        $zoneId = Counter::query()->orderBy('id')->value('id');

        $this->get("/tv-display/zona/{$zoneId}")
            ->assertOk()
            ->assertSee('Tampilan hanya-baca');
    }
}
