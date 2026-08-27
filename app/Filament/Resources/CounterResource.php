<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CounterResource\Pages;
use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Service;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CounterResource extends Resource
{
    protected static ?string $model = Counter::class;

    protected static ?string $navigationLabel = 'Manajemen Loket';

    protected static ?string $Label = 'Loket';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Master Data';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['instansi', 'service']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('name')
                    ->label('Zona')
                    ->options(fn (): array => collect(config('tv.zones', []))
                        ->mapWithKeys(fn (array $zone): array => [$zone['name'] => $zone['name']])
                        ->all())
                    ->required(),

                Select::make('instansi_id')
                    ->label('Instansi')
                    ->relationship('instansi', 'nama_instansi')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Reset service when instansi changes
                        $set('service_id', null);
                    }),

                TextInput::make('code_loket')
                    ->label('Kode Loket')
                    ->helperText('Kode fisik loket, misalnya 3A-1. Biarkan kosong jika loket belum memakai kode khusus.')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),

                Select::make('service_id')
                    ->label('Layanan')
                    ->options(function ($get) {
                        $instansiId = $get('instansi_id');
                        if (! $instansiId) {
                            return [];
                        }

                        return Service::where('instansi_id', $instansiId)
                            ->orderBy('prefix')
                            ->get()
                            ->mapWithKeys(fn (Service $service): array => [
                                $service->id => $service->prefix.' — '.$service->name,
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->reactive()
                    ->required(),

                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code_loket')
                    ->label('Loket')
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Belum berkode')
                    ->weight('bold')
                    ->searchable()
                    ->description(function (Counter $record): string {
                        $details = collect([$record->name, $record->instansi?->nama_instansi])->filter();

                        return $details->isNotEmpty()
                            ? $details->implode(' · ')
                            : 'Zona atau instansi belum ditentukan';
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->tooltip('Layanan antrean yang dipanggil dari loket ini.')
                    ->searchable()
                    ->wrap()
                    ->placeholder('Belum ditentukan'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status')
                    ->tooltip('Menentukan apakah loket siap digunakan.'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('zona')
                    ->label('Zona')
                    ->options(fn (): array => Counter::withoutGlobalScopes()
                        ->whereNotNull('name')
                        ->where('name', '!=', '')
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $zoneName = $data['value'] ?? null;

                        if (! $zoneName) {
                            return $query;
                        }

                        return $query->where('name', $zoneName);
                    })
                    ->placeholder('Semua zona'),
                SelectFilter::make('instansi_id')
                    ->label('Instansi')
                    ->options(fn (): array => Instansi::query()
                        ->orderBy('nama_instansi')
                        ->pluck('nama_instansi', 'instansi_id')
                        ->all())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
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
            'index' => Pages\ManageCounters::route('/'),
        ];
    }
}
