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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = '4. Pengguna & Peran';

    protected static ?string $Label = 'Pengguna';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Struktur Layanan';

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
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
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
                Forms\Components\Toggle::make('is_active')
                    ->label('Akun Aktif')
                    ->default(true)
                    ->helperText('Akun nonaktif tidak dihitung dalam kehadiran.'),
                Forms\Components\Select::make('counter_id')
                    ->label('Loket Utama')
                    ->options(fn (): array => Counter::withoutGlobalScopes()
                        ->where('is_active', true)
                        ->where('is_archived', false)
                        ->orderBy('name')
                        ->orderBy('code_loket')
                        ->get()
                        ->mapWithKeys(fn (Counter $counter): array => [$counter->id => $counter->display_name.' — '.$counter->name])
                        ->all())
                    ->visible(fn (Get $get) => $get('role') === 'operator')
                    ->required(fn (Get $get) => $get('role') === 'operator')
                    ->disabled(fn () => Auth::user()->role === 'operator')
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set) => $set(
                        'service_id',
                        Counter::withoutGlobalScopes()->find($state)?->service_id,
                    ))
                    ->searchable()
                    ->preload(),
                Forms\Components\Hidden::make('service_id'),
                Forms\Components\Placeholder::make('layanan_loket')
                    ->label('Layanan Utama')
                    ->content(function (Get $get): string {
                        $counter = Counter::withoutGlobalScopes()->with('service')->find($get('counter_id'));

                        return $counter?->service
                            ? $counter->service->prefix.' — '.$counter->service->name
                            : 'Pilih loket utama terlebih dahulu.';
                    })
                    ->visible(fn (Get $get): bool => $get('role') === User::ROLE_OPERATOR),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END"))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pengguna')
                    ->description(fn (User $record): string => "Username: {$record->username}")
                    ->searchable()
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->grow(false),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Penugasan')
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->role === 'admin' ? 'Akses seluruh panel' : ($state ?? '-'))
                    ->description(fn (User $record): string => $record->role === 'admin'
                        ? 'Administrator'
                        : 'Loket: '.($record->counter?->display_name ?? '-').' · '.($record->counter?->instansi?->zone ?? '-'))
                    ->wrap(),
                Tables\Columns\TextColumn::make('password_changed_at')
                    ->label('Rotasi Password')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum dirotasi')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('zone')
                    ->label('Tampilkan Petugas Zona')
                    ->options(fn (): array => collect(config('tv.zones', []))
                        ->mapWithKeys(fn (array $zone, int|string $id): array => [(string) $id => (string) $zone['name']])
                        ->all())
                    ->default('1')
                    ->query(function (Builder $query, array $data): Builder {
                        $zoneId = $data['value'] ?? null;

                        // Akun admin selalu terlihat paling atas. Akun petugas hanya
                        // dimuat dari zona yang dipilih agar tabel tetap ringkas.
                        if (blank($zoneId)) {
                            return $query->where('role', User::ROLE_ADMIN);
                        }

                        $zoneName = (string) config("tv.zones.{$zoneId}.name", "ZONA {$zoneId}");

                        return $query->where(function (Builder $scope) use ($zoneName): void {
                            $scope->where('role', User::ROLE_ADMIN)
                                ->orWhere(function (Builder $operators) use ($zoneName): void {
                                    $operators->where('role', User::ROLE_OPERATOR)
                                        ->whereHas('counter.instansi', fn (Builder $instansi) => $instansi->where('zone', $zoneName));
                                });
                        });
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit pengguna'),
            ])
            ->actionsColumnLabel('Aksi')
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
