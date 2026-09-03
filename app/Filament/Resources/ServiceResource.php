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
use Closure;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_archived', false)
            ->with('instansi.counter');
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
                    ->helperText('Kode nomor antrean harus unik. Periksa workbook kode loket/layanan sebelum menentukan kode baru.')
                    ->live(onBlur: true)
                    ->maxLength(10)
                    ->required()
                    ->rule(function (?Service $record): Closure {
                        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            $prefix = strtoupper(trim((string) $value));

                            if ($prefix === '') {
                                return;
                            }

                            $conflict = Service::query()
                                ->whereRaw('UPPER(TRIM(prefix)) = ?', [$prefix])
                                ->when($record, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
                                ->with('instansi')
                                ->first();

                            if ($conflict) {
                                $owner = trim(($conflict->instansi?->nama_instansi ? $conflict->instansi->nama_instansi.' — ' : '').$conflict->name);
                                $fail("Kode {$prefix} sudah terpakai untuk antrean lain: {$owner}. Silakan cek workbook lalu gunakan kode berbeda.");
                            }
                        };
                    })
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
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
                    ->label('Tampilkan di Kiosk')
                    ->helperText('Matikan hanya untuk layanan lama/arsip. Layanan akan disembunyikan dari kiosk dan tidak dapat menerima antrean.')
                    ->default(true)
                    ->live()
                    ->required(),
                Forms\Components\Toggle::make('is_accepting_queues')
                    ->label('Buka Antrean Hari Ini')
                    ->helperText('Jika dimatikan, layanan tetap terlihat abu-abu di kiosk dengan keterangan bahwa layanan sudah tutup.')
                    ->default(true)
                    ->visible(fn (Get $get): bool => (bool) $get('is_active'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Layanan')
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->where(function (Builder $searchQuery) use ($search): void {
                                $searchQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhereHas(
                                        'instansi',
                                        fn (Builder $instansiQuery): Builder => $instansiQuery
                                            ->where('nama_instansi', 'like', "%{$search}%"),
                                    );
                            });
                        },
                    )
                    ->weight('bold')
                    ->description(function (Service $record): string {
                        $details = collect([
                            $record->instansi?->nama_instansi,
                            $record->instansi?->counter?->name,
                        ])->filter();

                        return $details->isNotEmpty()
                            ? $details->implode(' · ')
                            : 'Instansi atau zona belum ditentukan';
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('prefix')
                    ->label('Kode Antrian')
                    ->badge()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('padding')
                    ->label('Jumlah Digit Angka')
                    ->numeric()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Tampil di Kiosk')
                    ->tooltip('Matikan untuk menyembunyikan layanan lama atau arsip dari kiosk.'),
                Tables\Columns\ToggleColumn::make('is_accepting_queues')
                    ->label('Antrean Hari Ini')
                    ->tooltip('Matikan untuk menutup antrean sementara tanpa menyembunyikan layanan dari kiosk.'),
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
            'index' => Pages\ManageServices::route('/'),
        ];
    }
}
