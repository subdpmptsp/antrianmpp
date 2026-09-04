<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceQueueDateOverrideResource\Pages;
use App\Models\Service;
use App\Models\ServiceQueueDateOverride;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceQueueDateOverrideResource extends Resource
{
    protected static ?string $model = ServiceQueueDateOverride::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Jadwal Operasional';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $modelLabel = 'Tanggal Khusus Antrean';
    protected static ?string $pluralModelLabel = 'Jadwal Operasional Antrean';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('service_id')
                ->label('Layanan')
                ->options(fn (): array => Service::query()
                    ->where('is_archived', false)
                    ->with('instansi')
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn (Service $service): array => [$service->id => ($service->instansi?->nama_instansi.' — '.$service->name)])
                    ->all())
                ->searchable()
                ->required(),
            Forms\Components\DatePicker::make('date')
                ->label('Tanggal khusus')
                ->native(false)
                ->required(),
            Forms\Components\Toggle::make('is_closed')
                ->label('Tutup pengambilan antrean pada tanggal ini')
                ->default(true)
                ->required(),
            Forms\Components\Textarea::make('reason')
                ->label('Alasan')
                ->rows(2)
                ->required()
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->description(fn (ServiceQueueDateOverride $record): string => $record->service?->instansi?->nama_instansi ?? '-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')->label('Alasan')->limit(50)->tooltip(fn (ServiceQueueDateOverride $record): string => $record->reason ?? ''),
                Tables\Columns\IconColumn::make('is_closed')->label('Antrean Ditutup')->boolean(),
                Tables\Columns\TextColumn::make('creator.name')->label('Diatur oleh')->placeholder('Sistem'),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([Tables\Actions\CreateAction::make()->label('Tambah tanggal khusus')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageServiceQueueDateOverrides::route('/')];
    }
}
