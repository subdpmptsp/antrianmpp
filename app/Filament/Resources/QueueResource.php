<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QueueResource\Pages;
use App\Models\Queue;
use App\Models\Service;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;

    protected static ?string $navigationLabel = 'Daftar Antrian';

    protected static ?string $Label = 'Antrian';

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Operasional';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    /**
     * Riwayat tiket tetap dipertahankan di database, tetapi belum diperlukan
     * sebagai menu operasional karena monitoring sudah menjadi pusat pantauan.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canUpdate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->weight('bold')
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->alignment(Alignment::Center),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->description(fn (Queue $record): string => $record->counter ? "Loket: {$record->counter->display_name}" : 'Loket: Belum dipanggil'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('called_at')
                    ->label('Dipanggil')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('(H:i), d M Y') : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('served_at')
                    ->label('Dilayani')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('(H:i), d M Y') : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('canceled_at')
                    ->label('Dibatalkan')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('(H:i), d M Y') : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Selesai')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('(H:i), d M Y') : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->defaultSort('updated_at', 'desc')
            ->filters([
                Filter::make('service_id')
                    ->form([
                        Forms\Components\Select::make('service_id')
                            ->label('Layanan')
                            ->options(fn () => Service::all()->pluck('name', 'id'))
                            ->placeholder('Semua Layanan'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['service_id']) {
                            $query->where('service_id', $data['service_id']);
                        }
                    })
                    ->indicateUsing(
                        fn (array $data): ?string => $data['service_id'] ? 'Layanan: '.Service::find($data['service_id'])?->name : null
                    ),

                // 🔍 Filter Berdasarkan Status
                Filter::make('status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                Queue::STATUS_PRINTING => 'Sedang Dicetak',
                                Queue::STATUS_WAITING => 'Menunggu',
                                Queue::STATUS_CALLED => 'Dipanggil',
                                Queue::STATUS_SERVING => 'Sedang Dilayani',
                                Queue::STATUS_CANCELED => 'Dibatalkan',
                                Queue::STATUS_FINISHED => 'Selesai',
                            ])
                            ->placeholder('Semua Status'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['status']) {
                            $query->where('status', $data['status']);
                        }
                    })
                    ->indicateUsing(
                        fn (array $data): ?string => $data['status'] ? 'Status: '.ucfirst($data['status']) : null
                    ),

                Filter::make('selected_date')
                    ->form([
                        DatePicker::make('selected_date')
                            ->label('Tanggal')
                            ->default(Carbon::today())
                            ->closeOnDateSelection(),
                    ])
                    ->query(
                        fn (Builder $query, array $data) => $data['selected_date']
                            ? $query->whereDate('created_at', $data['selected_date'])
                            : $query
                    )
                    ->indicateUsing(
                        fn (array $data) => $data['selected_date']
                            ? 'Tanggal: '.Carbon::parse($data['selected_date'])->format('d M Y')
                            : null
                    ),

            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageQueues::route('/'),
        ];
    }
}
