<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use App\Models\Instansi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'Absensi Petugas';
    
    protected static ?string $navigationGroup = 'Monitoring';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        // Form tidak digunakan karena absensi otomatis
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->searchPlaceholder('Cari absensi...')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Check In')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Check Out')
                    ->time('H:i')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Petugas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instansi.nama_instansi')
                    ->label('Instansi / Perusahaan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Kehadiran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'Hadir',
                        'absent' => 'Tidak Hadir',
                        'late' => 'Terlambat',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(now())
                            ->displayFormat('d F Y')
                            ->native(false)
                            ->id('attendance-date-filter')
                            ->extraAttributes([
                                'name' => 'attendance-date-filter',
                                'aria-label' => 'Filter Tanggal Absensi'
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // Filter is handled in the page's getTableQuery method
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Tanggal: ' . \Carbon\Carbon::parse($data['date'])->format('d M Y'))
                                ->removeField('date');
                        }
                        return $indicators;
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('Tidak ada data absensi')
            ->emptyStateDescription('Belum ada data absensi untuk tanggal yang dipilih.');
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
            'index' => Pages\ListAttendances::route('/'),
        ];
    }
}
