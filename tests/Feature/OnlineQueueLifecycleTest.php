<?php

namespace Tests\Feature;

use App\Models\OnlineQueueReservation;
use App\Models\OnlineQueueAudit;
use App\Models\OnlineQueueCheckinChallenge;
use App\Models\OnlineQueueSession;
use App\Models\OnlineQueueSetting;
use App\Models\Queue;
use App\Models\QueueOperatingSetting;
use App\Models\Service;
use App\Services\OnlineQueueService;
use App\Services\OnlineQueueScheduleService;
use Carbon\Carbon;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OnlineQueueLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-14 08:30:00', 'Asia/Jakarta'));
        $this->seed(TestingSeeder::class);
        OnlineQueueSetting::query()->create([
            'global_enabled' => true, 'regular_enabled' => true, 'event_enabled' => false,
            'booking_window_days' => 7, 'pilot_mode' => true, 'pilot_service_ids' => [1001, 1002],
        ]);
        QueueOperatingSetting::query()->create([
            'weekly_schedule' => collect(range(1, 7))->map(fn (int $day) => ['day' => $day, 'is_open' => true, 'opens_at' => '07:30', 'closes_at' => '15:00'])->all(),
            'cutoff_minutes' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_booking_encrypts_nik_and_enforces_active_identity_uniqueness(): void
    {
        $session = $this->createOnlineSession(quota: 2);
        $reservation = $this->book($session, '3578123412341234');

        $rawNik = DB::table('online_queue_reservations')->where('id', $reservation->id)->value('nik');
        $this->assertNotSame('3578123412341234', $rawNik);
        $this->assertSame('3578123412341234', $reservation->fresh()->nik);
        $this->assertSame('************1234', $reservation->fresh()->masked_nik);

        $this->expectException(ValidationException::class);
        $this->book($session, '3578123412341234');
    }

    public function test_session_quota_cannot_be_exceeded(): void
    {
        $session = $this->createOnlineSession(quota: 1);
        $this->book($session, '3578123412341234');

        $this->expectException(ValidationException::class);
        $this->book($session, '3578123412345678');
    }

    public function test_pilot_scope_rejects_service_outside_allowlist(): void
    {
        $session = OnlineQueueSession::query()->create([
            'service_id' => 1003, 'day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '09:00',
            'quota' => 2, 'checkin_open_minutes' => 15, 'checkin_grace_minutes' => 15,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);

        $this->expectException(ValidationException::class);
        $this->book($session, '3578123412345678');
    }

    public function test_checkin_creates_one_regular_queue_with_service_prefix(): void
    {
        $reservation = $this->book($this->createOnlineSession(quota: 2), '3578123412341234');
        $service = app(OnlineQueueService::class);

        $first = $service->checkIn($reservation);
        $second = $service->checkIn($reservation->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertStringStartsWith('1A-', $first->number);
        $this->assertSame('online', $first->source);
        $this->assertSame(Queue::STATUS_WAITING, $first->status);
        $this->assertSame($reservation->id, $first->online_queue_reservation_id);
        $this->assertSame(1, Queue::query()->where('online_queue_reservation_id', $reservation->id)->count());
        $this->assertDatabaseHas('online_queue_audits', [
            'online_queue_reservation_id' => $reservation->id,
            'action' => 'checked_in',
        ]);
    }

    public function test_bulk_schedule_creates_separate_sessions_for_multiple_services_and_days(): void
    {
        $created = app(OnlineQueueScheduleService::class)->createBulk([
            'service_ids' => [1001, 1002],
            'days' => [1, 2],
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'quota' => 20,
            'checkin_open_minutes' => 15,
            'checkin_grace_minutes' => 15,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);

        $this->assertCount(4, $created);
        $this->assertSame(4, OnlineQueueSession::query()->count());
        $this->assertSame([1001, 1002], OnlineQueueSession::query()->distinct()->orderBy('service_id')->pluck('service_id')->all());
        $this->assertSame([1, 2], OnlineQueueSession::query()->distinct()->orderBy('day_of_week')->pluck('day_of_week')->all());

        app(OnlineQueueScheduleService::class)->createBulk([
            'service_ids' => [1001, 1002],
            'days' => [1, 2],
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'quota' => 25,
            'checkin_open_minutes' => 10,
            'checkin_grace_minutes' => 20,
            'status' => OnlineQueueSession::STATUS_DRAFT,
        ]);

        $this->assertSame(4, OnlineQueueSession::query()->count());
        $this->assertSame([25], OnlineQueueSession::query()->distinct()->pluck('quota')->all());
    }

    public function test_editing_one_session_updates_only_that_row_and_protects_existing_reservations(): void
    {
        $first = $this->createOnlineSession(quota: 2);
        $second = OnlineQueueSession::query()->create([
            'service_id' => 1002,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'quota' => 10,
            'checkin_open_minutes' => 15,
            'checkin_grace_minutes' => 15,
            'status' => OnlineQueueSession::STATUS_DRAFT,
        ]);

        $schedule = app(OnlineQueueScheduleService::class);
        $schedule->updateSession($first, [
            'service_id' => 1001, 'day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '09:00',
            'quota' => 5, 'checkin_open_minutes' => 10, 'checkin_grace_minutes' => 20,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);

        $this->assertSame(5, $first->fresh()->quota);
        $this->assertSame(10, $second->fresh()->quota);

        $this->book($first->fresh(), '3578123412341234');
        $this->expectException(ValidationException::class);
        $schedule->updateSession($first->fresh(), [
            'service_id' => 1001, 'day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '10:00',
            'quota' => 5, 'checkin_open_minutes' => 10, 'checkin_grace_minutes' => 20,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);
    }

    public function test_public_booking_ticket_lookup_and_cancellation_flow(): void
    {
        $onlineSession = $this->createOnlineSession(quota: 2);

        $this->get(route('online-queue.index'))->assertOk()->assertSee('Layanan Uji ZONA 1')->assertSee('sisa 2');

        $response = $this->post(route('online-queue.store'), [
            'nik' => '3578123412341234', 'name' => 'Pemohon Publik', 'phone' => '081234567890',
            'slot' => $onlineSession->id.'|2026-09-14', 'agreement' => '1',
        ]);
        $reservation = OnlineQueueReservation::query()->sole();
        $response->assertRedirect(route('online-queue.ticket', $reservation->access_token));
        $this->get(route('online-queue.ticket', $reservation->access_token))
            ->assertOk()->assertSee($reservation->booking_code)->assertSee('Ini bukan nomor antrean');

        $this->post(route('online-queue.lookup.find'), [
            'booking_code' => $reservation->booking_code, 'nik_last_four' => '1234',
        ])->assertRedirect(route('online-queue.ticket', $reservation->access_token));

        $this->post(route('online-queue.cancel', $reservation->access_token))
            ->assertRedirect(route('online-queue.ticket', $reservation->access_token));
        $this->assertSame(OnlineQueueReservation::STATUS_CANCELED, $reservation->fresh()->status);
        $this->assertNull($reservation->fresh()->active_identity_key);
    }

    public function test_short_lived_kiosk_qr_checks_in_once_and_returns_print_payload(): void
    {
        $reservation = $this->book($this->createOnlineSession(quota: 2), '3578123412341234');
        $token = 'secure-kiosk-checkin-token';
        OnlineQueueCheckinChallenge::query()->create([
            'token_hash' => hash('sha256', $token),
            'station_code' => 'KIOSK-UJI',
            'expires_at' => now()->addSeconds(90),
        ]);

        $this->get(route('online-queue.checkin.phone', $token))
            ->assertOk()->assertSee('KIOSK-UJI')->assertSee('Konfirmasi kedatangan');

        $this->post(route('online-queue.checkin.confirm', $token), [
            'booking_code' => $reservation->booking_code,
            'nik_last_four' => '1234',
        ])->assertRedirect(route('online-queue.checkin.phone', $token));

        $queue = Queue::query()->where('online_queue_reservation_id', $reservation->id)->sole();
        $this->assertSame('online', $queue->source);
        $this->getJson(route('online-queue.checkin.status', $token))
            ->assertOk()->assertJsonPath('number', $queue->number)
            ->assertJsonStructure(['print_url', 'confirm_url', 'fail_url']);

        $this->post(route('online-queue.checkin.confirm', $token), [
            'booking_code' => $reservation->booking_code,
            'nik_last_four' => '1234',
        ])->assertRedirect(route('online-queue.checkin.phone', $token));
        $this->assertSame(1, Queue::query()->where('online_queue_reservation_id', $reservation->id)->count());
    }

    public function test_kiosk_checkin_rejects_wrong_identity_and_expired_qr_without_creating_queue(): void
    {
        $reservation = $this->book($this->createOnlineSession(quota: 2), '3578123412341234');
        $validToken = $this->createCheckinChallenge('valid-but-wrong-identity');

        $this->post(route('online-queue.checkin.confirm', $validToken), [
            'booking_code' => $reservation->booking_code,
            'nik_last_four' => '9999',
        ])->assertSessionHasErrors('booking_code');
        $this->assertDatabaseCount('queues', 0);

        $expiredToken = $this->createCheckinChallenge('expired-kiosk-token', now()->subSecond());
        $this->get(route('online-queue.checkin.phone', $expiredToken))->assertGone();
        $this->post(route('online-queue.checkin.confirm', $expiredToken), [
            'booking_code' => $reservation->booking_code,
            'nik_last_four' => '1234',
        ])->assertSessionHasErrors('booking_code');
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_kiosk_checkin_enforces_opening_and_late_grace_period(): void
    {
        $session = $this->createOnlineSession(quota: 2);
        $reservation = $this->book($session, '3578123412341234');

        Carbon::setTestNow(Carbon::parse('2026-09-14 07:44:00', 'Asia/Jakarta'));
        $earlyToken = $this->createCheckinChallenge('early-checkin-token');
        $this->post(route('online-queue.checkin.confirm', $earlyToken), [
            'booking_code' => $reservation->booking_code, 'nik_last_four' => '1234',
        ])->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('queues', 0);

        Carbon::setTestNow(Carbon::parse('2026-09-14 09:16:00', 'Asia/Jakarta'));
        $lateToken = $this->createCheckinChallenge('late-checkin-token');
        $this->post(route('online-queue.checkin.confirm', $lateToken), [
            'booking_code' => $reservation->booking_code, 'nik_last_four' => '1234',
        ])->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_online_queue_survives_printer_failure_without_duplicate_number(): void
    {
        $reservation = $this->book($this->createOnlineSession(quota: 2), '3578123412341234');
        $token = $this->createCheckinChallenge('printer-failure-token');
        $this->post(route('online-queue.checkin.confirm', $token), [
            'booking_code' => $reservation->booking_code, 'nik_last_four' => '1234',
        ])->assertRedirect();

        $payload = $this->getJson(route('online-queue.checkin.status', $token))->assertOk()->json();
        $this->post($payload['fail_url'])->assertConflict();

        $queue = Queue::query()->where('online_queue_reservation_id', $reservation->id)->sole();
        $this->assertSame(Queue::STATUS_WAITING, $queue->status);
        $this->assertSame($queue->id, app(OnlineQueueService::class)->checkIn($reservation->fresh())->id);
        $this->assertSame(1, Queue::query()->where('online_queue_reservation_id', $reservation->id)->count());
    }

    public function test_no_show_is_expired_automatically_and_audited(): void
    {
        $reservation = $this->book($this->createOnlineSession(quota: 2), '3578123412341234');
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:16:00', 'Asia/Jakarta'));

        $this->assertSame(1, app(OnlineQueueService::class)->expireNoShows());
        $this->assertSame(OnlineQueueReservation::STATUS_EXPIRED, $reservation->fresh()->status);
        $this->assertNull($reservation->fresh()->active_identity_key);
        $this->assertSame('expired', OnlineQueueAudit::query()->where('online_queue_reservation_id', $reservation->id)->value('action'));
        $this->assertSame(0, app(OnlineQueueService::class)->expireNoShows());
    }

    private function createCheckinChallenge(string $token, $expiresAt = null): string
    {
        OnlineQueueCheckinChallenge::query()->create([
            'token_hash' => hash('sha256', $token),
            'station_code' => 'KIOSK-UJI',
            'expires_at' => $expiresAt ?? now()->addSeconds(90),
        ]);

        return $token;
    }

    private function createOnlineSession(int $quota): OnlineQueueSession
    {
        return OnlineQueueSession::query()->create([
            'service_id' => Service::query()->findOrFail(1001)->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'quota' => $quota,
            'checkin_open_minutes' => 15,
            'checkin_grace_minutes' => 15,
            'status' => OnlineQueueSession::STATUS_ACTIVE,
        ]);
    }

    private function book(OnlineQueueSession $session, string $nik): OnlineQueueReservation
    {
        return app(OnlineQueueService::class)->book($session, [
            'name' => 'Pemohon Uji',
            'nik' => $nik,
            'phone' => '081234567890',
            'service_date' => '2026-09-14',
        ]);
    }
}
