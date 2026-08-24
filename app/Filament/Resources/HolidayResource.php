<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';

    protected static ?string $navigationLabel = 'Kalender Hari Libur';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Kalender Hari Libur';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')
                ->label('Tanggal')
                ->native(false)
                ->displayFormat('d F Y')
                ->unique(ignoreRecord: true)
                ->required(),
            Forms\Components\TextInput::make('name')
                ->label('Nama Hari Libur')
                ->maxLength(255)
                ->required(),
            Forms\Components\Select::make('type')
                ->label('Jenis')
                ->options([
                    'national' => 'Libur Nasional',
                    'collective' => 'Cuti Bersama',
                    'local' => 'Libur/penutupan lokal MPP',
                ])
                ->default('national')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Keterangan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'national' => 'Nasional',
                        'collective' => 'Cuti Bersama',
                        'local' => 'Lokal MPP',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'national' => 'danger',
                        'collective' => 'warning',
                        'local' => 'info',
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'national' => 'Libur Nasional',
                        'collective' => 'Cuti Bersama',
                        'local' => 'Lokal MPP',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah Hari Libur'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHolidays::route('/'),
        ];
    }
}
