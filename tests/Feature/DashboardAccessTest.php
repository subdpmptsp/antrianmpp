<?php

namespace Tests\Feature;

use App\Filament\Pages\DashboardCallKiosk;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_without_counter_cannot_see_all_counters(): void
    {
        Counter::query()->create(['name' => 'LOKET ADMIN', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'operator', 'counter_id' => null]);

        $component = Livewire::actingAs($operator)->test(DashboardCallKiosk::class);

        $component->assertSet('selectedCounterId', null);
        $this->assertCount(0, $component->get('counters'));
    }

    public function test_operator_cannot_switch_or_tamper_to_another_counter(): void
    {
        $assigned = Counter::query()->create(['name' => 'LOKET OPERATOR', 'is_active' => true]);
        $other = Counter::query()->create(['name' => 'LOKET LAIN', 'is_active' => true]);
        $operator = User::factory()->create([
            'role' => 'operator',
            'counter_id' => $assigned->id,
        ]);

        $component = Livewire::actingAs($operator)->test(DashboardCallKiosk::class);

        $component->call('selectCounter', $other->id)
            ->assertSet('selectedCounterId', $assigned->id);

        $component->set('selectedCounterId', $other->id);
        $this->assertSame($assigned->id, $component->get('selectedCounter')->id);
    }

    public function test_operator_cannot_change_queue_owned_by_another_counter(): void
    {
        $assigned = Counter::query()->create(['name' => 'LOKET OPERATOR', 'is_active' => true]);
        $other = Counter::query()->create(['name' => 'LOKET LAIN', 'is_active' => true]);
        $service = Service::query()->create([
            'name' => 'Layanan Uji',
            'prefix' => 'U',
            'padding' => 3,
            'is_active' => true,
        ]);
        $queue = Queue::query()->create([
            'counter_id' => $other->id,
            'service_id' => $service->id,
            'number' => 'U-001',
            'status' => Queue::STATUS_CALLED,
            'called_at' => now(),
        ]);
        $operator = User::factory()->create([
            'role' => 'operator',
            'counter_id' => $assigned->id,
        ]);

        Livewire::actingAs($operator)
            ->test(DashboardCallKiosk::class)
            ->call('startServing', $queue)
            ->assertForbidden();

        $this->assertSame(Queue::STATUS_CALLED, $queue->refresh()->status);
        $this->assertNull($queue->served_at);
    }
}
