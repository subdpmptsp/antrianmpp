<?php

namespace App\Filament\Pages;

use App\Models\OnlineQueueSession;
use App\Models\OnlineQueueSpecialWindow;
use App\Models\OnlineQueueSetting;
use App\Models\Service;
use App\Services\OnlineQueueScheduleService;
use App\Support\TimeOptions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class OnlineQueuePreview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Antrean Online';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'antrean-online';

    protected static ?string $title = 'Antrean Online';

    protected static string $view = 'filament.pages.online-queue-preview';

    public string $activeTab = 'dashboard';

    public ?int $editingSessionId = null;

    public ?int $editingSpecialWindowId = null;

    public function selectTab(string $tab): void
    {
        if (in_array($tab, ['dashboard', 'sessions', 'participants', 'checkin', 'links', 'settings'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function openEditSession(int $sessionId): void
    {
        $this->editingSessionId = OnlineQueueSession::query()->findOrFail($sessionId)->id;
        $this->mountAction('editSession');
    }

    public function openEditSpecialWindow(int $windowId): void
    {
        $this->editingSpecialWindowId = OnlineQueueSpecialWindow::query()->findOrFail($windowId)->id;
        $this->mountAction('editSpecialWindow');
    }

    public function getOnlineSettingsProperty(): OnlineQueueSetting
    {
        return OnlineQueueSetting::current();
    }

    public function getSessionsProperty()
    {
        return OnlineQueueSession::query()->with('service.instansi')->orderBy('day_of_week')->orderBy('starts_at')->get();
    }

    public function getSpecialWindowsProperty()
    {
        return OnlineQueueSpecialWindow::query()
            ->with('service.instansi')
            ->whereDate('date', '>=', now('Asia/Jakarta')->toDateString())
            ->orderBy('date')->orderBy('starts_at')->get();
    }

    /**
     * Draft dan sesi yang ditutup tidak boleh membuat kanal publik terlihat siap.
     * Ketersediaan slot akhirnya tetap divalidasi oleh controller publik.
     */
    public function getHasActivePublicSessionsProperty(): bool
    {
        $settings = $this->onlineSettings;

        if (! $settings->global_enabled || ! $settings->regular_enabled) {
            return false;
        }

        $sessions = OnlineQueueSession::query()
            ->where('status', OnlineQueueSession::STATUS_ACTIVE)
            ->whereHas('service', fn ($service) => $service
                ->where('is_active', true)
                ->where('is_archived', false));

        if ($settings->pilot_mode) {
            $sessions->whereIn('service_id', array_map('intval', $settings->pilot_service_ids ?? []));
        }

        return $sessions->exists();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configurePilot')
                ->label('Konfigurasi pilot')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->fillForm(function (): array {
                    $settings = OnlineQueueSetting::current();

                    return [
                        'pilot_service_ids' => $settings->pilot_service_ids ?? [],
                        'booking_window_days' => $settings->booking_window_days,
                        'activate' => $settings->global_enabled && $settings->regular_enabled,
                    ];
                })
                ->form([
                    Forms\Components\Select::make('pilot_service_ids')
                        ->label('Layanan pilot')
                        ->options(Service::query()->where('is_active', true)->where('is_archived', false)->orderBy('name')->pluck('name', 'id'))
                        ->multiple()->searchable()->preload()->required()
                        ->helperText('Hanya layanan ini yang dapat muncul pada formulir booking.'),
                    Forms\Components\TextInput::make('booking_window_days')
                        ->label('Jendela reservasi')->suffix('hari')->numeric()->minValue(1)->maxValue(30)->required(),
                    Forms\Components\Toggle::make('activate')
                        ->label('Aktifkan booking dan check-in pilot')
                        ->helperText('Biarkan mati sampai kesiapan perangkat, printer, petugas, dan jadwal telah diperiksa.'),
                ])
                ->requiresConfirmation()
                ->modalDescription('Aktivasi membuat layanan yang dipilih dapat dipesan masyarakat. Pastikan kiosk dan petugas sudah siap.')
                ->action(function (array $data): void {
                    $serviceIds = array_values(array_unique(array_map('intval', $data['pilot_service_ids'] ?? [])));
                    if (($data['activate'] ?? false) && $serviceIds === []) {
                        Notification::make()->title('Pilih minimal satu layanan pilot')->danger()->send();
                        return;
                    }

                    OnlineQueueSetting::current()->update([
                        'pilot_mode' => true,
                        'pilot_service_ids' => $serviceIds,
                        'booking_window_days' => (int) $data['booking_window_days'],
                        'global_enabled' => (bool) ($data['activate'] ?? false),
                        'regular_enabled' => (bool) ($data['activate'] ?? false),
                    ]);
                    Notification::make()
                        ->title(($data['activate'] ?? false) ? 'Pilot antrean online diaktifkan' : 'Pilot tetap dinonaktifkan')
                        ->body(count($serviceIds).' layanan masuk daftar pilot.')
                        ->success()->send();
                }),
            Action::make('createSession')
                ->label('Tambah jadwal mingguan')
                ->icon('heroicon-o-plus')
                ->extraAttributes(['class' => 'hidden'])
                ->form([
                    Forms\Components\Select::make('service_ids')->label('Layanan')->options(
                        Service::query()->where('is_active', true)->where('is_archived', false)->orderBy('name')->pluck('name', 'id')
                    )->multiple()->searchable()->preload()->required()->helperText('Pilih satu atau beberapa layanan sekaligus.'),
                    Forms\Components\Select::make('days')->label('Hari operasional')->options([
                        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
                    ])->multiple()->required()->helperText('Sesi yang sama dibuat terpisah untuk setiap hari terpilih.'),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('starts_at')->label('Mulai sesi')->options(TimeOptions::everyFiveMinutes())->searchable()->native(false)->required(),
                        Forms\Components\Select::make('ends_at')->label('Selesai sesi')->options(TimeOptions::everyFiveMinutes())->searchable()->native(false)->rules(['after:starts_at'])->required(),
                        Forms\Components\TextInput::make('quota')->label('Kuota online')->numeric()->minValue(1)->required(),
                        Forms\Components\Select::make('status')->label('Status')->options([
                            OnlineQueueSession::STATUS_DRAFT => 'Draft',
                            OnlineQueueSession::STATUS_ACTIVE => 'Aktif',
                            OnlineQueueSession::STATUS_CLOSED => 'Ditutup',
                        ])->default(OnlineQueueSession::STATUS_DRAFT)->required(),
                        Forms\Components\TextInput::make('checkin_open_minutes')->label('Check-in dibuka sebelum sesi')->suffix('menit')->numeric()->minValue(0)->default(15)->required(),
                        Forms\Components\TextInput::make('checkin_grace_minutes')->label('Toleransi setelah sesi')->suffix('menit')->numeric()->minValue(0)->default(15)->required(),
                    ]),
                ])
                ->action(function (array $data, OnlineQueueScheduleService $schedules): void {
                    $created = $schedules->createBulk($data);
                    Notification::make()
                        ->title($created->count().' sesi berhasil disiapkan')
                        ->body('Setiap layanan memiliki kuota terpisah. Sesi Draft tidak ditampilkan kepada masyarakat.')
                        ->success()
                        ->send();
                }),
            Action::make('createSpecialWindow')
                ->label('Tambah jadwal khusus')
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->extraAttributes(['class' => 'hidden'])
                ->modalHeading('Tambah jadwal khusus layanan')
                ->modalDescription('Online Penuh menghentikan cetak tiket onsite hanya pada layanan, tanggal, dan jam yang dipilih. Setelah waktu berakhir, onsite aktif kembali otomatis.')
                ->form([
                    Forms\Components\Select::make('service_ids')->label('Layanan')->options(
                        Service::query()->where('is_active', true)->where('is_archived', false)->orderBy('name')->pluck('name', 'id')
                    )->multiple()->searchable()->preload()->required()->helperText('Bisa memilih beberapa layanan sekaligus.'),
                    Forms\Components\DatePicker::make('date')->label('Tanggal berlaku')->native(false)->closeOnDateSelection()->minDate(today())->required(),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('starts_at')->label('Mulai')->options(TimeOptions::everyFiveMinutes())->searchable()->native(false)->required(),
                        Forms\Components\Select::make('ends_at')->label('Selesai')->options(TimeOptions::everyFiveMinutes())->searchable()->native(false)->rules(['after:starts_at'])->required(),
                    ]),
                    Forms\Components\Radio::make('mode')->label('Kanal penerbitan nomor')->options([
                        OnlineQueueSpecialWindow::MODE_HYBRID => 'Hybrid — online dan onsite tetap berjalan',
                        OnlineQueueSpecialWindow::MODE_ONLINE_ONLY => 'Online Penuh — cetak tiket onsite dihentikan sementara',
                    ])->default(OnlineQueueSpecialWindow::MODE_ONLINE_ONLY)->required()
                        ->helperText('Reservasi online dan check-in tetap memakai sesi mingguan serta kuotanya.'),
                    Forms\Components\Textarea::make('note')->label('Catatan admin')->maxLength(255),
                    Forms\Components\Toggle::make('is_active')->label('Aktifkan jadwal setelah disimpan')->default(false)
                        ->helperText('Simpan sebagai nonaktif bila masih ingin diperiksa.'),
                ])
                ->action(function (array $data): void {
                    $startsAt = substr((string) $data['starts_at'], 0, 5);
                    $endsAt = substr((string) $data['ends_at'], 0, 5);
                    foreach (array_unique(array_map('intval', $data['service_ids'])) as $serviceId) {
                        OnlineQueueSpecialWindow::query()->updateOrCreate([
                            'service_id' => $serviceId,
                            'date' => $data['date'],
                            'starts_at' => $startsAt,
                            'ends_at' => $endsAt,
                        ], [
                            'mode' => $data['mode'],
                            'is_active' => (bool) ($data['is_active'] ?? false),
                            'note' => $data['note'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                    }
                    Notification::make()->title('Jadwal khusus berhasil disimpan')->body(
                        ($data['is_active'] ?? false) ? 'Aturan akan berlaku otomatis pada waktunya.' : 'Jadwal masih nonaktif dan belum memengaruhi kiosk.'
                    )->success()->send();
                }),
            Action::make('exportChannelRecap')
                ->label('Rekap langsung & online')
                ->icon('heroicon-o-chart-bar-square')
                ->color('success')
                ->form([
                    Forms\Components\DatePicker::make('from')->label('Dari tanggal')->native(false)->closeOnDateSelection()->default(now('Asia/Jakarta')->startOfMonth())->required(),
                    Forms\Components\DatePicker::make('to')->label('Sampai tanggal')->native(false)->closeOnDateSelection()->default(now('Asia/Jakarta'))->required()->afterOrEqual('from'),
                    Forms\Components\Select::make('service_id')->label('Layanan')->options(
                        Service::query()->where('is_active', true)->where('is_archived', false)->orderBy('name')->pluck('name', 'id')
                    )->searchable()->placeholder('Semua layanan'),
                ])
                ->modalDescription('Ekspor membedakan antrean langsung dan antrean online yang sudah hadir berdasarkan sumber data, bukan prefix nomor.')
                ->action(function (array $data) {
                    return redirect()->route('export.queue-channel-recap', [
                        'from' => $data['from'],
                        'to' => $data['to'],
                        'service_id' => $data['service_id'] ?? null,
                    ]);
                }),
            Action::make('editSpecialWindow')
                ->label('Edit jadwal khusus')
                ->extraAttributes(['class' => 'hidden'])
                ->icon('heroicon-o-pencil-square')
                ->fillForm(function (): array {
                    $window = OnlineQueueSpecialWindow::query()->findOrFail($this->editingSpecialWindowId);

                    return [
                        'date' => $window->date,
                        'starts_at' => $window->starts_at,
                        'ends_at' => $window->ends_at,
                        'mode' => $window->mode,
                        'is_active' => $window->is_active,
                        'note' => $window->note,
                    ];
                })
                ->form([
                    Forms\Components\DatePicker::make('date')->label('Tanggal berlaku')->native(false)->closeOnDateSelection()->required(),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('starts_at')->label('Mulai')->options(TimeOptions::everyFiveMinutes())->searchable()->native(false)->required(),
                        Forms\Components\Select::make('ends_at')->label('Selesai')->options(TimeOptions::everyFiveMinutes())->searchable()->native(false)->rules(['after:starts_at'])->required(),
                    ]),
                    Forms\Components\Radio::make('mode')->label('Kanal penerbitan nomor')->options([
                        OnlineQueueSpecialWindow::MODE_HYBRID => 'Hybrid — online dan onsite tetap berjalan',
                        OnlineQueueSpecialWindow::MODE_ONLINE_ONLY => 'Online Penuh — cetak tiket onsite dihentikan sementara',
                    ])->required(),
                    Forms\Components\Textarea::make('note')->label('Catatan admin')->maxLength(255),
                    Forms\Components\Toggle::make('is_active')->label('Aktif')->helperText('Matikan untuk menghentikan aturan tanpa menghapus riwayat.'),
                ])
                ->action(function (array $data): void {
                    OnlineQueueSpecialWindow::query()->findOrFail($this->editingSpecialWindowId)->update([
                        'date' => $data['date'],
                        'starts_at' => substr((string) $data['starts_at'], 0, 5),
                        'ends_at' => substr((string) $data['ends_at'], 0, 5),
                        'mode' => $data['mode'],
                        'is_active' => (bool) ($data['is_active'] ?? false),
                        'note' => $data['note'] ?? null,
                    ]);
                    Notification::make()->title('Jadwal khusus diperbarui')->success()->send();
                }),
            Action::make('editSession')
                ->label('Edit sesi')
                ->extraAttributes(['class' => 'hidden'])
                ->icon('heroicon-o-pencil-square')
                ->fillForm(function (): array {
                    $session = OnlineQueueSession::query()->findOrFail($this->editingSessionId);

                    return $session->only([
                        'service_id', 'day_of_week', 'starts_at', 'ends_at', 'quota',
                        'checkin_open_minutes', 'checkin_grace_minutes', 'status',
                    ]);
                })
                ->form([
                    Forms\Components\Select::make('service_id')->label('Layanan')->options(
                        Service::query()->where('is_active', true)->where('is_archived', false)->orderBy('name')->pluck('name', 'id')
                    )->searchable()->preload()->required(),
                    Forms\Components\Select::make('day_of_week')->label('Hari')->options([
                        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
                    ])->required(),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('starts_at')->label('Mulai sesi')->options(TimeOptions::everyFiveMinutes())->searchable()->native(false)->required(),
                        Forms\Components\Select::make('ends_at')->label('Selesai sesi')->options(TimeOptions::everyFiveMinutes())->searchable()->native(false)->rules(['after:starts_at'])->required(),
                        Forms\Components\TextInput::make('quota')->label('Kuota online')->numeric()->minValue(1)->required(),
                        Forms\Components\Select::make('status')->label('Status')->options([
                            OnlineQueueSession::STATUS_DRAFT => 'Draft',
                            OnlineQueueSession::STATUS_ACTIVE => 'Aktif',
                            OnlineQueueSession::STATUS_CLOSED => 'Ditutup',
                        ])->required(),
                        Forms\Components\TextInput::make('checkin_open_minutes')->label('Check-in dibuka sebelum sesi')->suffix('menit')->numeric()->minValue(0)->required(),
                        Forms\Components\TextInput::make('checkin_grace_minutes')->label('Toleransi setelah sesi')->suffix('menit')->numeric()->minValue(0)->required(),
                    ]),
                ])
                ->action(function (array $data, OnlineQueueScheduleService $schedules): void {
                    $session = OnlineQueueSession::query()->findOrFail($this->editingSessionId);
                    $schedules->updateSession($session, $data);
                    Notification::make()->title('Sesi berhasil diperbarui')->success()->send();
                }),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

}
