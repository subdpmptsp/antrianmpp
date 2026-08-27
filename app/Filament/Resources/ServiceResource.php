<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Instansi;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationLabel = 'Manajemen Layanan';

    protected static ?string $Label = 'Layanan';

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationGroup = 'Master Data';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('instansi_id')
                    ->label('Instansi')
                    ->relationship('instansi', 'nama_instansi')
                    ->helperText('Pilih instansi pemilik layanan. Zona mengikuti pengaturan instansi tersebut.')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Layanan')
                    ->helperText('Nama layanan yang akan tampil di antrian, TV display, tiket, dan laporan.')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('prefix')
                    ->label('Prefix')
                    ->helperText('Awalan nomor antrian, misalnya A, B, BPOM, atau 1F.')
                    ->live(onBlur: true)
                    ->maxLength(10)
                    ->required()
                    ->maxLength(255),
                Forms\Components\Hidden::make('padding')
                    ->default(2),
                Forms\Components\Placeholder::make('preview_format')
                    ->label('Preview Format')
                    ->content(function (Get $get): string {
                        $prefix = trim((string) $get('prefix'));
                        $padding = (int) ($get('padding') ?: 2);

                        if ($prefix === '') {
                            return 'Isi Prefix untuk melihat preview format nomor antrian.';
                        }

                        return $prefix.'-'.str_pad('1', $padding, '0', STR_PAD_LEFT);
                    }),
                Forms\Components\Toggle::make('is_active')
                    ->helperText('Aktifkan jika layanan ini sedang dipakai. Jika nonaktif, layanan tidak muncul di pilihan antrian.')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Layanan')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('instansi.nama_instansi')
                    ->label('Instansi')
                    ->searchable()
                    ->placeholder('Belum ditentukan'),
                Tables\Columns\TextColumn::make('instansi.counter.name')
                    ->label('Zona')
                    ->badge()
                    ->placeholder('Belum ditentukan'),
                Tables\Columns\TextColumn::make('prefix')            
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('padding')
                    ->label('Jumlah Digit Angka')
                    ->numeric()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Is active')
                    ->tooltip('Menandakan layanan sedang aktif atau tidak.'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('zone')
                    ->label('Zona')
                    ->options(fn (): array => collect(config('tv.zones', []))
                        ->mapWithKeys(fn (array $zone): array => [$zone['name'] => $zone['name']])
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $zoneName = $data['value'] ?? null;

                        if (blank($zoneName)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'instansi.counter',
                            fn (Builder $counterQuery): Builder => $counterQuery->where('name', $zoneName),
                        );
                    }),
                Tables\Filters\SelectFilter::make('instansi_id')
                    ->label('Instansi')
                    ->options(fn (): array => Instansi::query()
                        ->orderBy('nama_instansi')
                        ->pluck('nama_instansi', 'instansi_id')
                        ->all())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ManageServices::route('/'),
        ];
    }
}
