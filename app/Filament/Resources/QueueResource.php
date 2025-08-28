<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Queue;
use App\Models\Service;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\QueueResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\QueueResource\RelationManagers;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;

    protected static ?string $navigationLabel = 'Daftar Antrian';

    protected static ?string $Label = 'Antrian';

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';


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
                    ->description(fn(Queue $record): string => optional($record->counter)->name ? "Loket: {$record->counter->name}" : "Loket: Belum dipanggil"),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('called_at')
                    ->label('Dipanggil')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('(H:i), d M Y') : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('served_at')
                    ->label('Dilayani')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('(H:i), d M Y') : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('canceled_at')
                    ->label('Dibatalkan')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('(H:i), d M Y') : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Selesai')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('(H:i), d M Y') : '-')
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
                            ->options(fn() => Service::all()->pluck('name', 'id'))
                            ->placeholder('Semua Layanan'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['service_id']) {
                            $query->where('service_id', $data['service_id']);
                        }
                    })
                    ->indicateUsing(
                        fn(array $data): ?string =>
                        $data['service_id'] ? 'Layanan: ' . Service::find($data['service_id'])?->name : null
                    ),

                // 🔍 Filter Berdasarkan Status
                Filter::make('status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'waiting' => 'Menunggu',
                                'serving' => 'Sedang Dilayani',
                                'canceled' => 'Dibatalkan',
                                'finished' => 'Selesai',
                            ])
                            ->placeholder('Semua Status'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['status']) {
                            $query->where('status', $data['status']);
                        }
                    })
                    ->indicateUsing(
                        fn(array $data): ?string =>
                        $data['status'] ? 'Status: ' . ucfirst($data['status']) : null
                    ),

                Filter::make('selected_date')
                    ->form([
                        DatePicker::make('selected_date')
                            ->label('Tanggal')
                            ->default(Carbon::today())
                            ->closeOnDateSelection(),
                    ])
                    ->query(
                        fn(Builder $query, array $data) =>
                        $data['selected_date']
                            ? $query->whereDate('created_at', $data['selected_date'])
                            : $query
                    )
                    ->indicateUsing(
                        fn(array $data) =>
                        $data['selected_date']
                            ? 'Tanggal: ' . Carbon::parse($data['selected_date'])->format('d M Y')
                            : null
                    ),

            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageQueues::route('/'),
        ];
    }
}
