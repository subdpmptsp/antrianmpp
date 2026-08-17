<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProtectedMutationRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_audio(): void
    {
        $response = $this->post('/api/audio/upload');

        $response->assertRedirect('/admin/login');
    }

    public function test_guest_cannot_delete_audio(): void
    {
        $response = $this->delete('/api/audio/delete', ['filename' => 'example.mp3']);

        $response->assertRedirect('/admin/login');
    }

    public function test_guest_cannot_trigger_announcement_audio(): void
    {
        $this->getJson('/api/audio/announcement')->assertUnauthorized();
    }

    public function test_operator_can_only_generate_audio_for_queue_at_assigned_counter(): void
    {
        $assignedCounter = Counter::query()->create(['name' => 'LOKET OPERATOR', 'is_active' => true]);
        $otherCounter = Counter::query()->create(['name' => 'LOKET LAIN', 'is_active' => true]);
        $service = Service::query()->create([
            'name' => 'LAYANAN AUDIO',
            'prefix' => 'A',
            'padding' => 3,
            'is_active' => true,
        ]);
        $ownQueue = Queue::query()->create([
            'counter_id' => $assignedCounter->id,
            'service_id' => $service->id,
            'number' => 'A-001',
            'status' => Queue::STATUS_CALLED,
            'called_at' => now(),
        ]);
        $otherQueue = Queue::query()->create([
            'counter_id' => $otherCounter->id,
            'service_id' => $service->id,
            'number' => 'A-002',
            'status' => Queue::STATUS_CALLED,
            'called_at' => now(),
        ]);
        $operator = User::factory()->create([
            'role' => 'operator',
            'counter_id' => $assignedCounter->id,
        ]);

        $this->actingAs($operator)
            ->getJson(route('api.audio.announcement', ['queue_id' => $ownQueue->id]))
            ->assertOk()
            ->assertJsonPath('queueNumber', 'A-001');

        $this->getJson(route('api.audio.announcement', ['queue_id' => $otherQueue->id]))
            ->assertNotFound();
    }

    public function test_admin_can_upload_list_and_delete_audio_on_configured_disk(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $upload = $this->actingAs($admin)->post('/api/audio/upload', [
            'name' => 'Pengumuman Testing',
            'audio' => UploadedFile::fake()->create('announcement.mp3', 100, 'audio/mpeg'),
        ]);

        $upload->assertOk()->assertJsonPath('success', true);
        $filename = $upload->json('filename');
        Storage::disk('public')->assertExists('audio/'.$filename);

        $this->getJson('/api/audio/list')
            ->assertOk()
            ->assertJsonPath('audioList.0.filename', $filename);

        $this->deleteJson('/api/audio/delete', ['filename' => $filename])
            ->assertOk()
            ->assertJsonPath('success', true);
        Storage::disk('public')->assertMissing('audio/'.$filename);
    }

    public function test_operator_cannot_upload_or_delete_audio(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post('/api/audio/upload')->assertForbidden();
        $this->actingAs($operator)->delete('/api/audio/delete', ['filename' => 'example.mp3'])->assertForbidden();
    }

    public function test_all_administrative_exports_require_admin_role(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        foreach ([
            '/exports/rekap-layanan',
            '/exports/monitoring-realtime',
            '/export/rekap-jumlah-pemohon',
        ] as $url) {
            $this->actingAs($operator)->get($url)->assertForbidden();
        }
    }
}
