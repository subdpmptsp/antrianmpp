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

    public function test_schedule_tabs_render_modern_filament_pickers(): void
    {
        $this->seed(TestingSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(QueueOperatingSchedule::class)
            ->assertSee('Simpan Perubahan')
            ->assertDontSee('Simulasikan status')
            ->assertSeeHtml('fi-fo-select')
            ->call('selectTab', 'tanggal_khusus')
            ->assertSeeHtml('fi-fo-date-time-picker');
    }
}
