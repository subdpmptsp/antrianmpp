<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                Forms\Components\Tabs::make('Branding MPP')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Identitas MPP')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama MPP')
                                    ->helperText('Digunakan pada Kiosk, TV Display, halaman loket, dan Landing Page.')
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
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make('Logo Utama Sistem')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('Logo Utama MPP')
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                                    ->directory('branding/logo-utama')
                                    ->maxSize(2048)
                                    ->helperText('Dipakai sebagai logo bawaan Kiosk, TV, dan halaman lain. Gunakan PNG/SVG transparan, rasio mendatar, minimal 240 × 80 piksel, maksimal 2 MB.')
                                    ->deletable(),
                                Forms\Components\Select::make('image_size')
                                    ->label('Ukuran logo utama di panel admin')
                                    ->options([
                                        'small' => 'Kecil — tinggi 64 px',
                                        'medium' => 'Sedang — tinggi 80 px',
                                        'large' => 'Besar — tinggi 96 px',
                                    ])
                                    ->default('medium')
                                    ->required()
                                    ->helperText('Mengatur tinggi logo utama pada sidebar admin. Tampilan lain dapat memakai ukuran khususnya sendiri.'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Landing Page Antrean Online')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Identitas di header Landing Page')
                                    ->description('Logo tampil di pojok kiri atas Landing Page. Jika logo khusus dikosongkan, sistem memakai Logo Utama MPP agar identitas tetap konsisten.')
                                    ->schema([
                                        Forms\Components\FileUpload::make('landing_logo')
                                            ->label('Logo MPP untuk Landing Page')
                                            ->image()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                                            ->directory('branding/landing')
                                            ->maxSize(2048)
                                            ->helperText('Opsional. Gunakan PNG/SVG transparan dengan rasio mendatar. Untuk header biru, gunakan versi logo berwarna terang/putih. Minimal 240 × 80 piksel, maksimal 2 MB.')
                                            ->deletable(),
                                        Forms\Components\Select::make('landing_logo_size')
                                            ->label('Ukuran logo di Landing Page')
                                            ->options([
                                                'small' => 'Kecil — tinggi 40 px',
                                                'medium' => 'Sedang — tinggi 48 px',
                                                'large' => 'Besar — tinggi 56 px',
                                            ])
                                            ->default('medium')
                                            ->required()
                                            ->helperText('Ukuran otomatis mengecil di HP agar header tetap rapi.'),
                                        Forms\Components\FileUpload::make('landing_city_logo')
                                            ->label('Logo Pemerintah Kota Surabaya')
                                            ->image()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                                            ->directory('branding/landing')
                                            ->maxSize(2048)
                                            ->helperText('Opsional. Tampil sebagai logo pendamping di header. Gunakan PNG/SVG transparan berbentuk kotak atau mendekati kotak, minimal 160 × 160 piksel, maksimal 2 MB.')
                                            ->deletable(),
                                        Forms\Components\Select::make('landing_city_logo_size')
                                            ->label('Ukuran logo pendamping Landing Page')
                                            ->options([
                                                'small' => 'Kecil — 40 × 40 px',
                                                'medium' => 'Sedang — 48 × 48 px',
                                                'large' => 'Besar — 56 × 56 px',
                                            ])
                                            ->default('medium')
                                            ->required()
                                            ->helperText('Ukuran otomatis dibatasi pada layar HP agar header tetap proporsional.'),
                                    ])
                                    ->columns(2),
                                Forms\Components\Section::make('Foto utama Landing Page')
                                    ->description('Foto tampil pada sisi kanan hero Landing Page. Jika dikosongkan, sistem memakai foto Gedung Siola bawaan.')
                                    ->schema([
                                        Forms\Components\FileUpload::make('landing_hero_image')
                                            ->label('Foto Gedung/Fasilitas MPP')
                                            ->image()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                                            ->directory('branding/landing')
                                            ->maxSize(4096)
                                            ->imageEditor()
                                            ->imageEditorAspectRatios(['16:9'])
                                            ->imageCropAspectRatio('16:9')
                                            ->imageResizeMode('cover')
                                            ->imageResizeTargetWidth('1600')
                                            ->imageResizeTargetHeight('900')
                                            ->imageResizeUpscale(false)
                                            ->imagePreviewHeight('180')
                                            ->helperText('Gunakan foto gedung/fasilitas MPP orientasi mendatar. Foto otomatis diarahkan ke rasio 16:9 dan maksimal 1600 × 900 piksel agar ringan serta menyatu dengan hero. Ukuran file maksimal 4 MB; hindari gambar berisi teks kecil.')
                                            ->deletable(),
                                        Forms\Components\Select::make('landing_hero_image_size')
                                            ->label('Tinggi area foto hero')
                                            ->options([
                                                'small' => 'Kecil — tinggi 280 px',
                                                'medium' => 'Sedang — tinggi 330 px',
                                                'large' => 'Besar — tinggi 380 px',
                                            ])
                                            ->default('medium')
                                            ->required()
                                            ->helperText('Pada HP, tinggi foto otomatis disesuaikan agar halaman tetap ringkas.'),
                                    ])
                                    ->columns(2),
                            ]),
                        Forms\Components\Tabs\Tab::make('Queue Kiosk')
                            ->icon('heroicon-o-computer-desktop')
                            ->schema([
                                Forms\Components\Section::make('Logo header mesin cetak antrean')
                                    ->description('Kedua gambar ini hanya dipakai pada header Queue Kiosk. Bila dikosongkan, sistem memakai logo bawaan agar mesin tetap dapat digunakan.')
                                    ->schema([
                                        Forms\Components\FileUpload::make('kiosk_logo')
                                            ->label('Logo utama Queue Kiosk')
                                            ->image()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                                            ->directory('branding/kiosk')
                                            ->maxSize(2048)
                                            ->helperText('Opsional. Tampil di sisi kiri header kiosk. Gunakan PNG/SVG transparan berbentuk kotak atau mendekati kotak, minimal 160 × 160 piksel, maksimal 2 MB.')
                                            ->deletable(),
                                        Forms\Components\Select::make('kiosk_logo_size')
                                            ->label('Ukuran logo utama Queue Kiosk')
                                            ->options([
                                                'small' => 'Kecil — 56 × 56 px',
                                                'medium' => 'Sedang — 68 × 68 px',
                                                'large' => 'Besar — 80 × 80 px',
                                            ])
                                            ->default('medium')
                                            ->required()
                                            ->helperText('Pilih ukuran yang tetap menyisakan ruang cukup untuk nama MPP dan jam.'),
                                        Forms\Components\FileUpload::make('kiosk_office_logo')
                                            ->label('Logo instansi pendamping Queue Kiosk')
                                            ->image()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                                            ->directory('branding/kiosk')
                                            ->maxSize(2048)
                                            ->helperText('Opsional. Tampil di sisi kanan header kiosk. Gunakan PNG/SVG transparan atau gambar berlatar terang, bentuk kotak, minimal 160 × 160 piksel, maksimal 2 MB.')
                                            ->deletable(),
                                        Forms\Components\Select::make('kiosk_office_logo_size')
                                            ->label('Ukuran logo instansi pendamping')
                                            ->options([
                                                'small' => 'Kecil — 64 × 64 px',
                                                'medium' => 'Sedang — 76 × 76 px',
                                                'large' => 'Besar — 88 × 88 px',
                                            ])
                                            ->default('medium')
                                            ->required()
                                            ->helperText('Logo dibungkus kartu putih otomatis agar tetap terbaca di header biru.'),
                                    ])
                                    ->columns(2),
                            ]),
                        Forms\Components\Tabs\Tab::make('Tautan Sistem')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Forms\Components\ViewField::make('system_links')
                                    ->label('')
                                    ->view('filament.forms.components.system-links-table')
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->height(48)
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
