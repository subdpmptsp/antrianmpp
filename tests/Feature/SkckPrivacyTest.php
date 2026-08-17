<?php

namespace Tests\Feature;

use App\Models\AntrianSkck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SkckPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registered_queue_masks_the_applicant_name(): void
    {
        AntrianSkck::query()->create([
            'nama' => 'Budiman Santoso',
            'nik' => '3578000000000001',
            'nomor_whatsapp' => '081200000001',
            'antrian' => 1,
            'queue_date' => now()->toDateString(),
        ]);

        $this->get('/antrian-skck-mpp/terdaftar')
            ->assertOk()
            ->assertSee('B****** S******')
            ->assertDontSee('Budiman Santoso');
    }

    public function test_skck_ticket_rejects_an_unsigned_enumerable_id(): void
    {
        $queue = $this->createQueue();

        $this->get('/antrian-skck-mpp/SKCK' . $queue->id)->assertForbidden();
    }

    public function test_skck_ticket_accepts_a_temporary_signed_url(): void
    {
        $queue = $this->createQueue();
        $url = URL::temporarySignedRoute('skck.ticket', now()->addMinute(), [
            'id' => 'SKCK' . $queue->id,
        ]);

        $this->get($url)->assertOk();
    }

    private function createQueue(): AntrianSkck
    {
        return AntrianSkck::query()->create([
            'nama' => 'Pemohon Rahasia',
            'nik' => '3578000000000002',
            'nomor_whatsapp' => '081200000002',
            'antrian' => 2,
            'queue_date' => now()->toDateString(),
        ]);
    }
}
