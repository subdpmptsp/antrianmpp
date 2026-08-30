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
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->wrap(),
                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Petugas Pengaju')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->wrap(),
                Tables\Columns\TextColumn::make('auto_reopen')
                    ->label('Pembukaan Kembali')
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? 'Otomatis hari kerja berikutnya, 00.05'
                        : 'Manual oleh petugas/admin')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray')
                    ->wrap(),
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
                    }),
                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Ditinjau Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Waktu Persetujuan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reopenedBy.name')
                    ->label('Dibuka Oleh')
                    ->formatStateUsing(fn (?string $state, CounterClosureRequest $record): string => $state
                        ?? ($record->reopened_at ? 'Sistem otomatis' : '-'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reopened_at')
                    ->label('Dibuka Kembali')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('admin_note')
                    ->label('Catatan Admin')
                    ->wrap()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('requested_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCounterClosureRequests::route('/'),
        ];
    }
}
