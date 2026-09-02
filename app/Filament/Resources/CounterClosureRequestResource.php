<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CounterClosureRequestResource\Pages;
use App\Models\CounterClosureRequest;
use App\Services\CounterClosureService;
use Filament\Forms;
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
            ->columns([
                Tables\Columns\TextColumn::make('counter.code_loket')
                    ->label('Loket')
                    ->weight('bold')
                    ->description(fn (CounterClosureRequest $record): string => collect([
                        $record->counter?->name,
                        $record->counter?->instansi?->nama_instansi,
                    ])->filter()->implode(' · '))
                    ->placeholder('-')
                    ->width('22%'),
                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Pengajuan')
                    ->description(fn (CounterClosureRequest $record): string => $record->requested_at?->format('d M Y, H:i') ? 'Diajukan '.$record->requested_at->format('d M Y, H:i') : 'Waktu pengajuan belum tercatat')
                    ->placeholder('-')
                    ->width('20%'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(42)
                    ->tooltip(fn (CounterClosureRequest $record): string => $record->reason)
                    ->width('27%'),
                Tables\Columns\TextColumn::make('auto_reopen')
                    ->label('Pembukaan Kembali')
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? 'Otomatis besok, 00.05'
                        : 'Manual oleh petugas/admin')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray')
                    ->width('16%'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        CounterClosureRequest::STATUS_PENDING => 'Menunggu',
                        CounterClosureRequest::STATUS_APPROVED => 'Disetujui',
                        CounterClosureRequest::STATUS_REJECTED => 'Ditolak',
                        CounterClosureRequest::STATUS_REOPENED => 'Dibuka kembali',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        CounterClosureRequest::STATUS_PENDING => 'warning',
                        CounterClosureRequest::STATUS_APPROVED => 'success',
                        CounterClosureRequest::STATUS_REJECTED => 'danger',
                        CounterClosureRequest::STATUS_REOPENED => 'info',
                        default => 'gray',
                    })
                    ->width('15%'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        CounterClosureRequest::STATUS_PENDING => 'Menunggu',
                        CounterClosureRequest::STATUS_APPROVED => 'Disetujui',
                        CounterClosureRequest::STATUS_REJECTED => 'Ditolak',
                        CounterClosureRequest::STATUS_REOPENED => 'Dibuka kembali',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('viewDetails')
                    ->label('Detail')
                    ->icon('heroicon-m-chevron-right')
                    ->iconButton()
                    ->tooltip('Lihat detail pengajuan')
                    ->modalHeading('Detail Pengajuan Penutupan Loket')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('3xl')
                    ->form([
                        Forms\Components\Placeholder::make('detail_loket')
                            ->label('Loket')
                            ->content(fn (CounterClosureRequest $record): string => $record->counter?->display_name ?? '-'),
                        Forms\Components\Placeholder::make('detail_layanan')
                            ->label('Layanan')
                            ->content(fn (CounterClosureRequest $record): string => $record->service?->name ?? '-'),
                        Forms\Components\Placeholder::make('detail_alasan')
                            ->label('Alasan Lengkap')
                            ->content(fn (CounterClosureRequest $record): string => $record->reason),
                        Forms\Components\Placeholder::make('detail_reopened_at')
                            ->label('Tanggal Dibuka Kembali')
                            ->content(fn (CounterClosureRequest $record): string => $record->reopened_at
                                ? $record->reopened_at->format('d F Y, H:i').' WIB'
                                : 'Belum dibuka kembali'),
                        Forms\Components\Placeholder::make('detail_admin_note')
                            ->label('Catatan Admin')
                            ->content(fn (CounterClosureRequest $record): string => $record->admin_note ?: '-'),
                    ]),
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Catatan Admin')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->visible(fn (CounterClosureRequest $record): bool => $record->status === CounterClosureRequest::STATUS_PENDING)
                    ->action(fn (CounterClosureRequest $record, array $data) => app(CounterClosureService::class)
                        ->approve($record, auth()->user(), $data['admin_note'] ?? null)),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->visible(fn (CounterClosureRequest $record): bool => $record->status === CounterClosureRequest::STATUS_PENDING)
                    ->action(fn (CounterClosureRequest $record, array $data) => app(CounterClosureService::class)
                        ->reject($record, auth()->user(), $data['admin_note'] ?? null)),
            ])
            ->recordAction('viewDetails')
            ->defaultSort('requested_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCounterClosureRequests::route('/'),
        ];
    }
}
