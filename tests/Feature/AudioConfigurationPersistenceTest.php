<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AudioConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AudioConfigurationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_audio_configuration_persists_outside_the_user_session(): void
    {
        Storage::fake('local');
        $service = new AudioConfigurationService;
        $admin = User::factory()->create(['role' => 'admin']);

        $service->save([
            'url' => 'https://cdn.example.test/announcement.mp3',
            'name' => 'Audio Utama',
            'description' => 'Dipakai semua TV',
            'type' => 'announcement',
        ]);

        $this->actingAs($admin)
            ->withSession(['audio_config' => ['url' => 'https://wrong.example.test/audio.mp3']])
            ->getJson('/api/audio/announcement')
            ->assertOk()
            ->assertJsonPath('audioUrl', 'https://cdn.example.test/announcement.mp3');

        Storage::disk('local')->assertExists('audio/config.json');
        $this->assertSame('Audio Utama', (new AudioConfigurationService)->get()['name']);
    }
}
