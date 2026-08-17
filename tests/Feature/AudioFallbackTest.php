<?php

namespace Tests\Feature;

use App\Services\ExternalAudioService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AudioFallbackTest extends TestCase
{
    public function test_missing_cloud_credentials_fall_back_to_local_audio(): void
    {
        config()->set('audio.google.api_key');

        $url = (new ExternalAudioService())->generateAudioUrl('Nomor antrian A-001', 'google');

        $this->assertStringEndsWith('/sounds/opening.mp3', $url);
        $this->assertFileExists(public_path('sounds/opening.mp3'));
    }

    public function test_unreachable_custom_audio_falls_back_to_local_audio(): void
    {
        Http::fake(['*' => Http::response('', 503)]);
        config()->set('audio.custom.url', 'https://audio.example.test/{text}.mp3');

        $url = (new ExternalAudioService())->generateAudioUrl('Nomor antrian A-001', 'custom');

        $this->assertStringEndsWith('/sounds/opening.mp3', $url);
    }
}
