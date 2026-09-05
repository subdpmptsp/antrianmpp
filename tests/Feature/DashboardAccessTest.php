<?php

namespace Tests\Feature;

use App\Filament\Pages\DashboardCallKiosk;
use App\Models\Counter;
use App\Models\Instansi;
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
        $operator = User::factory()->create([
            'role' => 'operator',
            'counter_id' => null,
            'is_active' => false,
        ]);

        $component = Livewire::actingAs($operator)->test(DashboardCallKiosk::class);

        $component->assertSet('selectedCounterId', null);
        $this->assertCount(0, $component->get('counters'));
    }

    public function test_operator_cannot_switch_or_tamper_to_another_counter(): void
    {
        [, , [$assigned]] = $this->createServiceTeam('ZONA 1', 'A', ['1a1']);
        [, , [$other]] = $this->createServiceTeam('ZONA 2', 'B', ['2b1']);
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
        [, , [$assigned]] = $this->createServiceTeam('ZONA 1', 'A', ['1a1']);
        [, $service, [$other]] = $this->createServiceTeam('ZONA 2', 'B', ['2b1']);
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

    public function test_operator_can_switch_to_active_counter_in_same_institution_and_service(): void
    {
        [, $service, [$assigned, $partner]] = $this->createServiceTeam(
            'ZONA 1',
            '1I',
            ['1i14', '1i15'],
        );
        $operator = User::factory()->create([
            'role' => 'operator',
            'counter_id' => $assigned->id,
        ]);
        $queue = Queue::query()->create([
            'service_id' => $service->id,
            'number' => '1I-1',
            'status' => Queue::STATUS_WAITING,
        ]);

        $component = Livewire::actingAs($operator)->test(DashboardCallKiosk::class);
        $component->call('selectCounter', $partner->id)
            ->assertSet('selectedCounterId', $partner->id)
            ->call('callNext');

        $this->assertSame($partner->id, $queue->refresh()->counter_id);
        $this->assertSame(Queue::STATUS_CALLED, $queue->status);
        $this->assertSame($assigned->id, $operator->refresh()->counter_id);
    }

    public function test_operator_can_select_authorized_help_service_but_not_during_active_call(): void
    {
        [$instansi, $primaryService, [$counter]] = $this->createServiceTeam('ZONA 1', '1L', ['1l19']);
        $helpService = Service::query()->create([
            'instansi_id' => $instansi->getKey(),
            'name' => 'LAYANAN BANTUAN 1M',
            'prefix' => '1M',
            'padding' => 2,
            'is_active' => true,
            'is_archived' => false,
            'is_accepting_queues' => true,
        ]);
        $counter->additionalServices()->attach($helpService->id);
        $operator = User::factory()->create([
            'role' => 'operator',
            'counter_id' => $counter->id,
        ]);

        $component = Livewire::actingAs($operator)->test(DashboardCallKiosk::class);
        $component->assertSee('Layanan yang akan dipanggil')
            ->call('selectServiceTab', $helpService->id)
            ->assertSet('selectedServiceId', $helpService->id)
            ->call('selectServiceTab', $primaryService->id)
            ->assertSet('selectedServiceId', $primaryService->id);

        Queue::query()->create([
            'service_id' => $primaryService->id,
            'counter_id' => $counter->id,
            'number' => '1L-1',
            'status' => Queue::STATUS_CALLED,
            'called_at' => now(),
        ]);

        $component->call('selectServiceTab', $helpService->id)
            ->assertSet('selectedServiceId', $primaryService->id);
    }

    private function createServiceTeam(string $zone, string $prefix, array $counterCodes): array
    {
        $instansi = Instansi::query()->create([
            'nama_instansi' => 'INSTANSI '.$zone.' '.$prefix,
            'zone' => $zone,
            'is_active' => true,
            'is_archived' => false,
        ]);
        $service = Service::query()->create([
            'instansi_id' => $instansi->getKey(),
            'name' => 'LAYANAN '.$prefix,
            'prefix' => $prefix,
            'padding' => 0,
            'is_active' => true,
            'is_archived' => false,
            'is_accepting_queues' => true,
        ]);
        $counters = collect($counterCodes)->map(fn (string $code): Counter => Counter::query()->create([
            'name' => $zone,
            'code_loket' => $code,
            'instansi_id' => $instansi->getKey(),
            'service_id' => $service->id,
            'is_active' => true,
            'is_archived' => false,
        ]))->all();

        return [$instansi, $service, $counters];
    }
}
