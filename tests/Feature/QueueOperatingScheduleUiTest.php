<?php

namespace Tests\Feature;

use App\Filament\Pages\QueueOperatingSchedule;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QueueOperatingScheduleUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_tabs_render_two_column_time_picker_and_modern_date_picker(): void
    {
        $this->seed(TestingSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(QueueOperatingSchedule::class)
            ->assertSee('Simpan Perubahan')
            ->assertDontSee('Simulasikan status')
            ->assertSeeHtml('weekly-schedule-row')
            ->assertSeeHtml('weekly-time-range')
            ->assertSeeHtml('@media (max-width: 640px)')
            ->assertSee('Pilih waktu')
            ->assertSee('Jam')
            ->assertSee('Menit')
            ->set('weeklySchedule.0.opens_at', '09:00')
            ->assertDontSeeHtml('&lt; wire:id=')
            ->call('selectTab', 'tanggal_khusus')
            ->assertSeeHtml('fi-fo-date-time-picker');
    }
}
