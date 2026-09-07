<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CounterClosureRequestResource\Pages;
use App\Models\CounterClosureRequest;
use App\Services\CounterClosureService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CounterClosureRequestResource extends Resource
{
    protected static ?string $model = CounterClosureRequest::class;

    protected static ?string $navigationLabel = 'Persetujuan Tutup Loket';

    protected static ?string $modelLabel = 'Persetujuan Tutup Loket';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Operasional';

    /**
     * Tampilkan hanya pekerjaan admin yang masih memerlukan keputusan.
     * Kolom status sudah memiliki indeks, sehingga COUNT ini tetap ringan
     * ketika sidebar panel admin dimuat.
     */
    public static function getNavigationBadge(): ?string
    {
        $pendingCount = CounterClosureRequest::query()
            ->where('status', CounterClosureRequest::STATUS_PENDING)
            ->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Jumlah pengajuan tutup loket yang menunggu persetujuan.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['counter.instansi', 'service', 'requestedBy', 'reviewedBy', 'reopenedBy']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
                'xl' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('service.name')
                            ->label('Layanan')
                            ->weight('bold')
                            ->size('lg')
                            ->description(fn (CounterClosureRequest $record): string => collect([
                                $record->counter?->display_name,
                                $record->counter?->instansi?->zone,
                                $record->counter?->instansi?->nama_instansi,
                            ])->filter()->implode(' · '))
                            ->placeholder('Layanan tidak tersedia'),
                        Tables\Columns\TextColumn::make('status')
                            ->label('Status')
                            ->badge()
                            ->grow(false)
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                CounterClosureRequest::STATUS_PENDING => 'Menunggu',
                                CounterClosureRequest::STATUS_APPROVED => 'Disetujui',
                                CounterClosureRequest::STATUS_REJECTED => 'Ditolak',
                                CounterClosureRequest::STATUS_EXPIRED => 'Kedaluwarsa',
                                CounterClosureRequest::STATUS_REOPENED => 'Dibuka kembali',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                CounterClosureRequest::STATUS_PENDING => 'warning',
                                CounterClosureRequest::STATUS_APPROVED => 'success',
                                CounterClosureRequest::STATUS_REJECTED => 'danger',
                                CounterClosureRequest::STATUS_EXPIRED => 'gray',
                                CounterClosureRequest::STATUS_REOPENED => 'info',
                                default => 'gray',
                            }),
                    ])->from('sm'),
                    Tables\Columns\TextColumn::make('reason')
                        ->label('Alasan pengajuan')
                        ->weight('semibold')
                        ->wrap()
                        ->placeholder('-'),
                    Tables\Columns\TextColumn::make('requestedBy.name')
                        ->label('Pengajuan')
                        ->color('gray')
                        ->size('sm')
                        ->formatStateUsing(fn (?string $state): string => $state ? 'Diajukan '.$state : 'Pengaju tidak tercatat')
                        ->description(fn (CounterClosureRequest $record): ?string => $record->requested_at
                            ? $record->requested_at->timezone('Asia/Jakarta')->format('d M Y, H.i').' WIB'
                            : null),
                    Tables\Columns\TextColumn::make('scheduled_reopen_at')
                        ->label('Aktif kembali')
                        ->color('warning')
                        ->weight('medium')
                        ->formatStateUsing(fn (?\Carbon\Carbon $state): ?string => $state
                            ? 'Aktif kembali: '.$state->timezone('Asia/Jakarta')->format('d M Y, H.i').' WIB'
                            : null),
                ])->space(3),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->default(CounterClosureRequest::STATUS_PENDING)
                    ->options([
                        CounterClosureRequest::STATUS_PENDING => 'Menunggu',
                        CounterClosureRequest::STATUS_APPROVED => 'Disetujui',
                        CounterClosureRequest::STATUS_REJECTED => 'Ditolak',
                        CounterClosureRequest::STATUS_EXPIRED => 'Kedaluwarsa',
                        CounterClosureRequest::STATUS_REOPENED => 'Dibuka kembali',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->button()
                    ->extraAttributes(['class' => 'flex-1 justify-center'])
                    ->requiresConfirmation()
                    ->modalHeading('Setujui pengajuan?')
                    ->modalDescription(fn (CounterClosureRequest $record): string => $record->scheduled_reopen_at
                        ? 'Apakah Anda yakin ingin menyetujui penutupan loket ini? Loket akan dibuka otomatis kembali pukul '.$record->scheduled_reopen_at->timezone('Asia/Jakarta')->format('H.i').' WIB.'
                        : 'Apakah Anda yakin ingin menyetujui penutupan loket ini?')
                    ->modalSubmitActionLabel('Ya, setujui')
                    ->modalCancelActionLabel('Batal')
                    ->modalWidth('sm')
                    ->visible(fn (CounterClosureRequest $record): bool => $record->status === CounterClosureRequest::STATUS_PENDING)
                    ->action(function (CounterClosureRequest $record): void {
                        app(CounterClosureService::class)->approve(
                            $record,
                            auth()->user(),
                        );
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->button()
                    ->extraAttributes(['class' => 'flex-1 justify-center'])
                    ->requiresConfirmation()
                    ->modalHeading('Tolak pengajuan?')
                    ->modalDescription('Apakah Anda yakin ingin menolak pengajuan penutupan loket ini?')
                    ->modalSubmitActionLabel('Ya, tolak')
                    ->modalCancelActionLabel('Batal')
                    ->modalWidth('sm')
                    ->visible(fn (CounterClosureRequest $record): bool => $record->status === CounterClosureRequest::STATUS_PENDING)
                    ->action(fn (CounterClosureRequest $record) => app(CounterClosureService::class)
                        ->reject($record, auth()->user())),
            ])
            ->defaultSort('requested_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCounterClosureRequests::route('/'),
        ];
    }
}
