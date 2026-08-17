<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScheduledMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_reset_closes_incomplete_attendance_without_double_time_error(): void
    {
        $this->travelTo(Carbon::parse('2026-08-17 00:05:00', 'Asia/Jakarta'));
        $operator = User::factory()->create(['role' => 'operator']);

        Attendance::create([
            'user_id' => $operator->id,
            'date' => '2026-08-16',
            'check_in' => '08:30:00',
            'status' => 'present',
        ]);

        $this->artisan('attendance:reset-daily')->assertSuccessful();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $operator->id,
            'date' => '2026-08-16',
            'check_out' => '23:59:59',
            'status' => 'late',
            'working_hours' => 929,
        ]);
    }

    public function test_audio_cleanup_removes_only_expired_generated_tts_files(): void
    {
        Storage::fake('local');
        $disk = Storage::disk('local');

        $disk->put('audio/google_tts_old.mp3', 'generated');
        $disk->put('audio/azure_tts_recent.mp3', 'generated');
        $disk->put('audio/config.json', '{}');
        $disk->put('audio/pengumuman-petugas.mp3', 'uploaded');

        touch($disk->path('audio/google_tts_old.mp3'), now()->subDays(8)->timestamp);
        touch($disk->path('audio/config.json'), now()->subDays(30)->timestamp);
        touch($disk->path('audio/pengumuman-petugas.mp3'), now()->subDays(30)->timestamp);

        $this->artisan('audio:cleanup-generated', ['--days' => 7])
            ->expectsOutput('1 file audio TTS lama berhasil dibersihkan.')
            ->assertSuccessful();

        $disk->assertMissing('audio/google_tts_old.mp3');
        $disk->assertExists('audio/azure_tts_recent.mp3');
        $disk->assertExists('audio/config.json');
        $disk->assertExists('audio/pengumuman-petugas.mp3');
    }

    public function test_audio_cleanup_rejects_unsafe_retention_value(): void
    {
        $this->artisan('audio:cleanup-generated', ['--days' => 0])
            ->assertExitCode(2);
    }
}
