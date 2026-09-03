<?php

namespace App\Filament\Pages;

use App\Models\EventQueue;
use App\Models\EventQueueParticipant;
use App\Services\EventQueueService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class EventQueueManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Antrean Online / Event';
    protected static ?string $navigationGroup = 'Operasional';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Antrean Online / Event';
    protected static string $view = 'filament.pages.event-queue-management';

    public string $activeTab = 'events';
    public ?int $selectedEventId = null;
    public string $checkinCode = '';
    public string $participantStatusFilter = 'all';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function selectEvent(int $eventId, string $tab = 'dashboard'): void
    {
        abort_unless(EventQueue::query()->whereKey($eventId)->exists(), 404);

        $this->selectedEventId = $eventId;
        $this->activeTab = $tab;
    }

    public function selectTab(string $tab): void
    {
        $available = ['events', 'dashboard', 'participants', 'checkin', 'links', 'settings'];

        if (! in_array($tab, $available, true)) {
            return;
        }

        if ($tab !== 'events' && ! $this->selectedEventId) {
            Notification::make()
                ->title('Pilih event terlebih dahulu')
                ->body('Pilih event pada tab Daftar Event untuk membuka data event tersebut.')
                ->warning()
                ->send();

            return;
        }

        $this->activeTab = $tab;
    }

    public function checkIn(int $participantId): void
    {
        $participant = EventQueueParticipant::query()
            ->where('event_queue_id', $this->selectedEventId)
            ->findOrFail($participantId);

        if ($participant->status === EventQueueParticipant::STATUS_CHECKED_IN) {
            Notification::make()->title('Peserta sudah check-in')->info()->send();
            return;
        }

        $participant->update([
            'status' => EventQueueParticipant::STATUS_CHECKED_IN,
            'checked_in_at' => now(),
        ]);

        Notification::make()->title('Check-in berhasil')->success()->send();
    }

    public function checkInByCode(): void
    {
        $value = trim($this->checkinCode);
        $path = parse_url($value, PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            $value = (string) collect(explode('/', trim($path, '/')))->last();
        }

        $participant = EventQueueParticipant::query()
            ->where('event_queue_id', $this->selectedEventId)
            ->where('qr_token', $value)
            ->first();

        if (! $participant) {
            Notification::make()
                ->title('QR tidak ditemukan')
                ->body('Pastikan QR berasal dari tiket event yang sedang dipilih.')
                ->danger()
                ->send();

            return;
        }

        $this->checkinCode = '';
        $this->checkIn($participant->id);
    }

    public function markAsServing(int $participantId): void
    {
        $participant = EventQueueParticipant::query()
            ->where('event_queue_id', $this->selectedEventId)
            ->findOrFail($participantId);

        if ($participant->status !== EventQueueParticipant::STATUS_CHECKED_IN) {
            Notification::make()->title('Peserta harus check-in terlebih dahulu')->warning()->send();
            return;
        }

        $participant->update([
            'status' => EventQueueParticipant::STATUS_SERVING,
            'served_at' => now(),
        ]);
        Notification::make()->title('Peserta sedang dilayani')->success()->send();
    }

    public function regeneratePublicLink(EventQueueService $service): void
    {
        $event = $this->selectedEvent;
        if (! $event) return;

        $service->regeneratePublicToken($event);
        Notification::make()->title('Link publik dan QR diperbarui')->warning()->send();
    }

    public function regenerateTvLink(EventQueueService $service): void
    {
        $event = $this->selectedEvent;
        if (! $event) return;

        $service->regenerateTvToken($event);
        Notification::make()->title('Link TV dan QR diperbarui')->warning()->send();
    }

    public function togglePublicLink(): void
    {
        $event = $this->selectedEvent;
        if (! $event) return;

        $event->update(['public_link_enabled' => ! $event->public_link_enabled]);
    }

    public function toggleNameMask(): void
    {
        $event = $this->selectedEvent;
        if (! $event) return;

        $event->update(['mask_participant_names' => ! $event->mask_participant_names]);
    }

    public function getSelectedEventProperty(): ?EventQueue
    {
        return $this->selectedEventId ? EventQueue::query()->find($this->selectedEventId) : null;
    }

    public function getEventsProperty()
    {
        return EventQueue::query()
            ->withCount([
                'participants as participants_count' => fn ($query) => $query->where('status', '!=', EventQueueParticipant::STATUS_CANCELED),
                'participants as checked_in_count' => fn ($query) => $query->whereIn('status', [EventQueueParticipant::STATUS_CHECKED_IN, EventQueueParticipant::STATUS_SERVING]),
            ])
            ->latest('starts_at')
            ->latest('id')
            ->get();
    }

    public function getParticipantsProperty()
    {
        if (! $this->selectedEventId) return collect();

        return EventQueueParticipant::query()
            ->where('event_queue_id', $this->selectedEventId)
            ->when($this->participantStatusFilter !== 'all', fn ($query) => $query->where('status', $this->participantStatusFilter))
            ->latest('id')
            ->limit(100)
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createEvent')
                ->label('Buat event baru')
                ->icon('heroicon-o-plus')
                ->form($this->eventFormSchema())
                ->action(function (array $data): void {
                    EventQueue::create([
                        ...$data,
                        'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
                        'public_token' => Str::random(48),
                        'tv_token' => Str::random(48),
                    ]);

                    Notification::make()->title('Event berhasil dibuat')->success()->send();
                }),
            Action::make('editEvent')
                ->label('Ubah pengaturan')
                ->icon('heroicon-o-cog-6-tooth')
                ->visible(fn (): bool => $this->selectedEvent !== null)
                ->fillForm(fn (): array => $this->selectedEvent?->only([
                    'name', 'description', 'starts_at', 'ends_at', 'arrival_date', 'session_label', 'daily_quota',
                    'session_quota', 'checkin_grace_minutes', 'status',
                    'ticket_prefix', 'reference_prefix',
                ]) ?? [])
                ->form($this->eventFormSchema())
                ->action(function (array $data): void {
                    $this->selectedEvent?->update($data);
                    Notification::make()->title('Pengaturan event disimpan')->success()->send();
                }),
        ];
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    private function eventFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->label('Nama event')->required()->maxLength(150),
            Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(3)->columnSpanFull(),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\DateTimePicker::make('starts_at')->label('Mulai event')->seconds(false),
                Forms\Components\DateTimePicker::make('ends_at')->label('Selesai event')->seconds(false),
                Forms\Components\DatePicker::make('arrival_date')->label('Tanggal kedatangan'),
                Forms\Components\TextInput::make('session_label')->label('Sesi kedatangan')->placeholder('Contoh: 08.00–09.00'),
                Forms\Components\TextInput::make('daily_quota')->label('Kuota total')->numeric()->minValue(1)->default(60)->required(),
                Forms\Components\TextInput::make('session_quota')->label('Kuota per sesi')->numeric()->minValue(1),
                Forms\Components\TextInput::make('ticket_prefix')->label('Prefix tiket pendek')->helperText('Contoh: B, maka tiket menjadi B-001.')->alphaNum()->maxLength(8)->default('E')->required(),
                Forms\Components\TextInput::make('reference_prefix')->label('Kode referensi lengkap')->helperText('Opsional. Contoh: SKCKONLINEDSKI.')->maxLength(40),
                Forms\Components\TextInput::make('checkin_grace_minutes')->label('Batas check-in (menit)')->numeric()->minValue(0)->default(30)->required(),
                Forms\Components\Select::make('status')->label('Status event')->options([
                    EventQueue::STATUS_DRAFT => 'Draft',
                    EventQueue::STATUS_ACTIVE => 'Aktif',
                    EventQueue::STATUS_CLOSED => 'Pendaftaran ditutup',
                    EventQueue::STATUS_COMPLETED => 'Selesai',
                ])->default(EventQueue::STATUS_DRAFT)->required(),
            ]),
        ];
    }
}
