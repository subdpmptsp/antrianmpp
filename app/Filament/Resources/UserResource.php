<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Counter;
use App\Models\Service;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Manajemen Pengguna';

    protected static ?string $Label = 'Pengguna';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        // Hanya admin yang bisa delete user
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('username')
                    ->label('Username')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record && $record instanceof User && $record->role === 'admin'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->disabled(fn ($record) => $record && $record instanceof User && $record->role === 'admin'),
                Forms\Components\Select::make('role')
                    ->options(function (Get $get, ?User $record) {
                        // Pada create: hanya izinkan operator. Pada edit admin: tampilkan Admin tapi dikunci lewat disabled.
                        return [
                            User::ROLE_OPERATOR => User::ROLE_LABELS[User::ROLE_OPERATOR],
                            ...($record && $record->role === 'admin' ? ['admin' => 'Admin'] : []),
                        ];
                    })
                    ->default(User::ROLE_OPERATOR)
                    ->live()
                    ->required()
                    ->disabled(fn ($record) => ($record && $record instanceof User && $record->role === 'admin') ||
                        (Auth::user()->role === 'operator')
                    ),
                Forms\Components\Select::make('service_id')
                    ->label('Layanan')
                    ->options(Service::where('is_active', true)->pluck('name', 'id'))
                    ->visible(fn (Get $get) => $get('role') === 'operator')
                    ->required(fn (Get $get) => $get('role') === 'operator')
                    ->disabled(fn () => Auth::user()->role === 'operator'),
                Forms\Components\Select::make('counter_id')
                    ->label('Loket')
                    ->options(Counter::withoutGlobalScopes()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn (Get $get) => $get('role') === 'operator')
                    ->required(fn (Get $get) => $get('role') === 'operator')
                    ->disabled(fn () => Auth::user()->role === 'operator')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pengguna'),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Peran'),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->role === 'admin' ? 'Semua' : ($state ?? '-')),
                Tables\Columns\TextColumn::make('counter.name')
                    ->label('Loket')
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->role === 'admin' ? 'Semua' : ($state ?? '-')),
                Tables\Columns\TextColumn::make('password_changed_at')
                    ->label('Rotasi Password')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum dirotasi')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => $record->role !== 'admin'),
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
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
