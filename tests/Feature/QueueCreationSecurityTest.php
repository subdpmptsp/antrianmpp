<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use App\Services\KioskCatalogService;
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

    public function test_post_request_creates_exactly_one_queue_and_redirects_with_queue_id(): void
    {
        $service = $this->createService();
        $token = 'valid-queue-request-token';

        $response = $this->withSession(['queue_request_token' => $token])->post(route('public.queue-kiosk.select-service', [
            'serviceId' => $service->id,
            'zona' => 1,
        ]), ['queue_request_token' => $token]);

        $queue = Queue::query()->sole();

        $response->assertRedirect();
        $this->assertStringContainsString('queue_id='.$queue->id, $response->headers->get('Location'));
        $this->assertSame('T-001', $queue->number);
        $this->assertSame('waiting', $queue->status);
        $response->assertHeader('X-RateLimit-Limit', '30');
    }

    public function test_replaying_the_same_ticket_request_does_not_create_another_queue(): void
    {
        $service = $this->createService();
        $token = 'single-use-queue-request-token';
        $url = route('public.queue-kiosk.select-service', ['serviceId' => $service->id, 'zona' => 1]);

        $this->withSession(['queue_request_token' => $token])
            ->post($url, ['queue_request_token' => $token])
            ->assertRedirect();

        $this->post($url, ['queue_request_token' => $token])
            ->assertRedirect(route('public.queue-kiosk'));

        $this->assertDatabaseCount('queues', 1);
    }

    public function test_service_from_another_zone_cannot_be_submitted_by_changing_the_url(): void
    {
        $service = $this->createService('ZONA 2');
        $token = 'wrong-zone-token';

        $this->withSession(['queue_request_token' => $token])
            ->post(route('public.queue-kiosk.select-service', [
                'serviceId' => $service->id,
                'zona' => 1,
            ]), ['queue_request_token' => $token])
            ->assertRedirect(route('public.queue-kiosk'));

        $this->assertDatabaseCount('queues', 0);
    }

    public function test_inactive_service_cannot_create_a_queue(): void
    {
        $service = $this->createService();
        $service->update(['is_active' => false]);
        $token = 'inactive-service-token';

        $this->withSession(['queue_request_token' => $token])
            ->post(route('public.queue-kiosk.select-service', [
                'serviceId' => $service->id,
                'zona' => 1,
            ]), ['queue_request_token' => $token])
            ->assertRedirect(route('public.queue-kiosk'));

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

    public function test_kiosk_zone_catalog_uses_current_institutions_from_database(): void
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

        $zone = app(KioskCatalogService::class)->zones()[1];

        $this->assertSame(2, $zone['institution_count']);
        $this->assertSame(1, $zone['service_count']);

        $this->get(route('public.queue-kiosk'))
            ->assertOk()
            ->assertSee('INSTANSI DINAMIS')
            ->assertDontSee('Kepolisian Resor Kota Besar');
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
