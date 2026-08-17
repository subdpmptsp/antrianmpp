<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use App\Services\QueueService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class QueueCreationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_cannot_be_created_through_get_request(): void
    {
        $service = $this->createService();

        $this->get(route('public.queue-kiosk.select-service', $service->id))
            ->assertMethodNotAllowed();

        $this->assertDatabaseCount('queues', 0);
    }

    public function test_post_request_reserves_exactly_one_queue_and_confirmation_activates_it(): void
    {
        $service = $this->createService();
        $token = 'valid-queue-request-token';

        $response = $this->withSession(['queue_request_token' => $token])
            ->postJson(route('public.queue-kiosk.select-service', $service), [
                'queue_request_token' => $token,
                'instansi_id' => $service->instansi_id,
            ]);

        $queue = Queue::query()->sole();

        $response->assertCreated()
            ->assertJsonPath('queue_id', $queue->id)
            ->assertJsonStructure(['print_url', 'confirm_url', 'fail_url']);
        $this->assertSame('T-001', $queue->number);
        $this->assertSame(Queue::STATUS_PRINTING, $queue->status);
        $response->assertHeader('X-RateLimit-Limit', '30');

        $this->post($response->json('confirm_url'))
            ->assertOk()
            ->assertJsonPath('confirmed', true);
        $this->assertDatabaseHas('queues', [
            'id' => $queue->id,
            'status' => Queue::STATUS_WAITING,
        ]);
    }

    public function test_replaying_the_same_ticket_request_does_not_create_another_queue(): void
    {
        $service = $this->createService();
        $token = 'single-use-queue-request-token';
        $url = route('public.queue-kiosk.select-service', $service);

        $this->withSession(['queue_request_token' => $token])
            ->postJson($url, [
                'queue_request_token' => $token,
                'instansi_id' => $service->instansi_id,
            ])
            ->assertCreated();

        $this->postJson($url, [
            'queue_request_token' => $token,
            'instansi_id' => $service->instansi_id,
        ])->assertConflict();

        $this->assertDatabaseCount('queues', 1);
    }

    public function test_service_from_another_institution_cannot_be_submitted_by_changing_the_payload(): void
    {
        $service = $this->createService('ZONA 2');
        $otherService = $this->createService('ZONA 3', 'INSTANSI LAIN');
        $token = 'wrong-institution-token';

        $this->withSession(['queue_request_token' => $token])
            ->postJson(route('public.queue-kiosk.select-service', $service), [
                'queue_request_token' => $token,
                'instansi_id' => $otherService->instansi_id,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('queues', 0);
    }

    public function test_inactive_service_cannot_create_a_queue(): void
    {
        $service = $this->createService();
        $service->update(['is_active' => false]);
        $token = 'inactive-service-token';

        $this->withSession(['queue_request_token' => $token])
            ->postJson(route('public.queue-kiosk.select-service', $service), [
                'queue_request_token' => $token,
                'instansi_id' => $service->instansi_id,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('queues', 0);
    }

    public function test_queue_service_rejects_inactive_service_even_when_called_internally(): void
    {
        $service = $this->createService();
        $service->update(['is_active' => false]);

        try {
            app(QueueService::class)->addQueue($service->id);
            $this->fail('Layanan nonaktif seharusnya ditolak.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('queues', 0);
        }
    }

    public function test_kiosk_home_uses_current_active_institutions_from_database(): void
    {
        $activeService = $this->createService(institutionName: 'INSTANSI DINAMIS');
        $secondInstitution = Instansi::query()->create([
            'nama_instansi' => 'INSTANSI KEDUA',
            'counter_id' => $activeService->instansi->counter_id,
        ]);
        Service::query()->create([
            'instansi_id' => $secondInstitution->instansi_id,
            'name' => 'LAYANAN NONAKTIF',
            'prefix' => 'N',
            'padding' => 3,
            'is_active' => false,
        ]);

        $this->get(route('public.queue-kiosk'))
            ->assertOk()
            ->assertSee('INSTANSI DINAMIS')
            ->assertDontSee('INSTANSI KEDUA')
            ->assertDontSee('Kepolisian Resor Kota Besar');
    }

    public function test_failed_print_reservation_is_canceled_and_never_enters_waiting_queue(): void
    {
        $service = $this->createService();
        $token = 'failed-print-token';

        $response = $this->withSession(['queue_request_token' => $token])
            ->postJson(route('public.queue-kiosk.select-service', $service), [
                'queue_request_token' => $token,
                'instansi_id' => $service->instansi_id,
            ])
            ->assertCreated();

        $this->post($response->json('fail_url'))
            ->assertOk()
            ->assertJsonPath('canceled', true);

        $queue = Queue::query()->sole();
        $this->assertSame(Queue::STATUS_CANCELED, $queue->status);
        $this->assertNotNull($queue->canceled_at);
    }

    public function test_stale_print_reservation_is_canceled_automatically(): void
    {
        $service = $this->createService();
        $queue = app(QueueService::class)->reserveQueueForPrinting($service->id);
        $queue->timestamps = false;
        $queue->forceFill(['updated_at' => now()->subMinutes(3)])->save();

        $expired = app(QueueService::class)->expireStalePrintReservations();

        $this->assertSame(1, $expired);
        $this->assertDatabaseHas('queues', [
            'id' => $queue->id,
            'status' => Queue::STATUS_CANCELED,
        ]);
        $this->assertNotNull($queue->fresh()->canceled_at);
    }

    public function test_queue_service_generates_sequential_numbers(): void
    {
        $service = $this->createService();
        $queueService = app(QueueService::class);

        $first = $queueService->addQueue($service->id);
        $second = $queueService->addQueue($service->id);

        $this->assertSame('T-001', $first->number);
        $this->assertSame('T-002', $second->number);
    }

    public function test_queue_number_resets_on_a_new_day(): void
    {
        $service = $this->createService();
        $previousQueue = Queue::query()->create([
            'service_id' => $service->id,
            'number' => 'T-123',
            'status' => Queue::STATUS_FINISHED,
        ]);
        $previousQueue->timestamps = false;
        $previousQueue->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->save();

        $queue = app(QueueService::class)->addQueue($service->id);

        $this->assertSame('T-001', $queue->number);
    }

    public function test_queue_number_continues_safely_past_padding_width(): void
    {
        $service = $this->createService();
        $service->update(['padding' => 2]);
        Queue::query()->create([
            'service_id' => $service->id,
            'number' => 'T-99',
            'status' => Queue::STATUS_WAITING,
        ]);

        $queue = app(QueueService::class)->addQueue($service->id);

        $this->assertSame('T-100', $queue->number);
    }

    public function test_barcode_scan_preserves_original_queue_id_in_pdf_url(): void
    {
        $service = $this->createService();
        $queue = app(QueueService::class)->addQueue($service->id);
        $scanUrl = URL::temporarySignedRoute('barcode.scan', now()->addHour(), [
            'queue_id' => $queue->id,
        ]);

        $response = $this->get($scanUrl);

        $response->assertRedirect();
        $this->assertStringContainsString('queue_id='.$queue->id, $response->headers->get('Location'));
        $this->assertStringNotContainsString('service_id=', $response->headers->get('Location'));
        $this->get($response->headers->get('Location'))->assertOk();
    }

    public function test_unsigned_ticket_document_urls_are_rejected(): void
    {
        $service = $this->createService();
        $queue = app(QueueService::class)->addQueue($service->id);

        $this->get(route('struk.generate', ['queue_id' => $queue->id]))->assertForbidden();
        $this->get(route('barcode.scan', ['queue_id' => $queue->id]))->assertForbidden();
        $this->get(route('tickets.pdf', $queue))->assertForbidden();
        $this->get(route('tickets.print', $queue))->assertForbidden();
        $this->post(route('tickets.print.confirm', $queue))->assertForbidden();
        $this->post(route('tickets.print.fail', $queue))->assertForbidden();
    }

    public function test_signed_receipt_requires_a_real_persisted_queue(): void
    {
        $url = URL::temporarySignedRoute('struk.generate', now()->addHour());

        $this->get($url)->assertNotFound();
    }

    private function createService(string $zoneName = 'ZONA 1', string $institutionName = 'INSTANSI TEST'): Service
    {
        $counter = Counter::query()->create([
            'name' => $zoneName,
            'is_active' => true,
        ]);
        $institution = Instansi::query()->create([
            'nama_instansi' => $institutionName,
            'counter_id' => $counter->id,
        ]);

        return Service::query()->create([
            'instansi_id' => $institution->instansi_id,
            'name' => 'LAYANAN TEST',
            'prefix' => 'T',
            'padding' => 3,
            'is_active' => true,
        ]);
    }
}
