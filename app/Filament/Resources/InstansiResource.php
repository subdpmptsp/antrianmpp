<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstansiResource\Pages;
use App\Models\Instansi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InstansiResource extends Resource
{
    protected static ?string $model = Instansi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Instansi';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $Label = 'Instansi';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('logo_path')
                    ->label('Logo Instansi untuk Kiosk')
                    ->helperText('Gunakan PNG transparan. Maksimal 1 MB; otomatis diseragamkan ke kanvas 512×512 px.')
                    ->image()
                    ->acceptedFileTypes(['image/png'])
                    ->disk('public')
                    ->directory('instansi-logos')
                    ->visibility('public')
                    ->maxSize(1024)
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('512')
                    ->imageResizeTargetHeight('512')
                    ->imageResizeUpscale(false)
                    ->imagePreviewHeight('140')
                    ->openable()
                    ->downloadable()
                    ->deletable()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('nama_instansi')
                    ->label('Nama Instansi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('work_days_per_week')
                    ->label('Pola Hari Kerja')
                    ->options([
                        5 => '5 hari (Senin–Jumat)',
                        6 => '6 hari (Senin–Sabtu)',
                    ])
                    ->default(5)
                    ->required()
                    ->helperText('Hari libur nasional tetap dikecualikan dari perhitungan.'),
                Forms\Components\Select::make('counter_id')
                    ->label('Zona Internal')
                    ->relationship('counter', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->square(),
                Tables\Columns\TextColumn::make('nama_instansi')
                    ->label('Nama Instansi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('counter.name')
                    ->label('Zona Internal')
                    ->placeholder('Belum ditentukan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('work_days_per_week')
                    ->label('Hari Kerja')
                    ->formatStateUsing(fn (int $state): string => $state === 6 ? 'Senin–Sabtu' : 'Senin–Jumat')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit Instansi')
                    ->modalSubmitActionLabel('Simpan Perubahan')
                    ->modalCancelActionLabel('Batal')
                    ->modalWidth('3xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstansis::route('/'),
        ];
    }
}
