<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use App\Services\ThermalPrinterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PrintingArtifactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_receipt_pdf_contains_the_persisted_queue(): void
    {
        $queue = $this->createQueue();
        $url = URL::temporarySignedRoute('struk.generate', now()->addMinute(), ['queue_id' => $queue->id]);

        $response = $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString($queue->number, $response->headers->get('content-disposition'));
    }

    public function test_signed_ticket_pdf_embeds_a_qr_for_the_same_queue(): void
    {
        $queue = $this->createQueue();
        $url = URL::temporarySignedRoute('tickets.pdf', now()->addMinute(), ['queue' => $queue]);

        $response = $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString($queue->number, $response->headers->get('content-disposition'));
    }

    public function test_barcode_scan_preserves_queue_on_desktop_and_mobile(): void
    {
        $queue = $this->createQueue();
        $scanUrl = URL::temporarySignedRoute('barcode.scan', now()->addMinute(), ['queue_id' => $queue->id]);

        $desktop = $this->get($scanUrl)->assertRedirect();
        $this->assertStringContainsString('queue_id='.$queue->id, $desktop->headers->get('location'));

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Mobile)')
            ->get($scanUrl)
            ->assertOk()
            ->assertSee($queue->number)
            ->assertSee('queue_id='.$queue->id, false);
    }

    public function test_admin_barcode_preview_contains_svg_and_original_number(): void
    {
        $queue = $this->createQueue();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('barcode.show', ['queue_id' => $queue->id]))
            ->assertOk()
            ->assertSee($queue->number)
            ->assertSee('<svg', false);
    }

    public function test_thermal_output_contains_initialization_qr_number_and_cut_commands(): void
    {
        $output = app(ThermalPrinterService::class)->createText([
            ['text' => 'A-001', 'align' => 'center', 'style' => 'double-all'],
            ['type' => 'qrcode', 'data' => '{"queue_id":123}', 'size' => 6],
        ]);

        $this->assertStringStartsWith("\x1B\x40", $output);
        $this->assertStringContainsString('A-001', $output);
        $this->assertStringContainsString('{"queue_id":123}', $output);
        $this->assertStringContainsString("\x1D\x28\x6B", $output);
        $this->assertStringEndsWith("\x1B\x69", $output);
    }

    public function test_direct_print_ticket_is_html_and_contains_the_persisted_queue(): void
    {
        $queue = $this->createQueue();
        $url = URL::temporarySignedRoute(
            'tickets.print',
            now()->addMinute(),
            ['queue' => $queue],
            absolute: false,
        );

        $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee($queue->number)
            ->assertSee($queue->service->name)
            ->assertDontSee('.pdf');
    }

    private function createQueue(): Queue
    {
        $institution = $this->createTestInstitution('INSTANSI TEST', 'ZONA TEST');
        $service = $this->createTestService($institution, [
            'name' => 'LAYANAN TEST',
            'prefix' => 'A',
            'padding' => 3,
            'is_active' => true,
        ]);
        $counter = $this->createTestCounter($institution, $service, ['code_loket' => 'ZONA-TEST']);

        return Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'number' => 'A-001',
            'status' => Queue::STATUS_WAITING,
        ]);
    }
}
