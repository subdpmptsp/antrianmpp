<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Filament\Resources\SettingResource\RelationManagers;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationLabel = 'Identitas & Branding MPP';

    protected static ?string $modelLabel = 'Identitas & Branding MPP';

    protected static ?string $pluralModelLabel = 'Identitas & Branding MPP';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function canCreate(): bool
    {
        return Setting::count() < 1;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama MPP')
                    ->helperText('Nama ini digunakan pada Kiosk, TV Display, dan halaman loket.')
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('address')
                    ->label('Alamat MPP')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Kontak MPP')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\FileUpload::make('image')
                    ->label('Logo Utama MPP')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                    ->directory('logo')
                    ->maxSize(2048)
                    ->helperText('PNG, JPG, WebP, atau SVG transparan. Maksimal 2 MB.')
                    ->deletable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->circular()
                    ->label('Logo MPP'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama MPP'),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat MPP'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak MPP'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
        ];
    }
}
