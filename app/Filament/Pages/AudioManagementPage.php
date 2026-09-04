<?php

namespace App\Filament\Pages;

use App\Services\AudioConfigurationService;
use Filament\Pages\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AudioManagementPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-speaker-wave';
    protected static string $view = 'filament.pages.audio-management';
    protected static ?string $title = 'Manajemen Audio';
    protected static ?string $navigationLabel = 'Manajemen Audio';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 10;
    
    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public $audioUrl = '';
    public $audioName = '';
    public $audioDescription = '';
    public $audioType = 'announcement';
    public $ttsVoice = 'auto';
    public $ttsRate = 0.9;
    public $ttsPitch = 1.0;
    public $ttsVolume = 1.0;
    public $ttsPreviewText = 'Nomor antrean A nol empat dua, silakan menuju Loket Tiga untuk layanan I K D.';

    public function mount(): void
    {
        $audioConfig = app(AudioConfigurationService::class)->get();
        $this->audioUrl = $audioConfig['url'] ?? '';
        $this->audioName = $audioConfig['name'];
        $this->audioDescription = $audioConfig['description'] ?? '';
        $this->audioType = $audioConfig['type'];
        $tts = $audioConfig['tts'] ?? [];
        $this->ttsVoice = $tts['voice'] ?? 'auto';
        $this->ttsRate = $tts['rate'] ?? 0.9;
        $this->ttsPitch = $tts['pitch'] ?? 1.0;
        $this->ttsVolume = $tts['volume'] ?? 1.0;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('audioUrl')
                    ->label('URL Audio Eksternal')
                    ->placeholder('https://example.com/audio.mp3')
                    ->helperText('Masukkan URL audio dari link eksternal')
                    ->url(),
                
                TextInput::make('audioName')
                    ->label('Nama Audio')
                    ->placeholder('Audio Pemanggilan Antrian')
                    ->required(),
                
                Textarea::make('audioDescription')
                    ->label('Deskripsi')
                    ->placeholder('Deskripsi audio...')
                    ->rows(3),
                
                Select::make('audioType')
                    ->label('Tipe Audio')
                    ->options([
                        'announcement' => 'Audio Pemanggilan',
                        'background' => 'Audio Background',
                        'notification' => 'Audio Notifikasi',
                    ])
                    ->default('announcement')
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function getActions(): array
    {
        return [
            Action::make('testAudio')
                ->label('Test Audio')
                ->icon('heroicon-o-play')
                ->color('success')
                ->action('testAudio'),
            
            Action::make('saveAudio')
                ->label('Simpan Audio')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('saveAudio'),
            
            Action::make('uploadAudio')
                ->label('Upload File Audio')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('info')
                ->form([
                    FileUpload::make('audioFile')
                        ->label('File Audio')
                        ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/ogg'])
                        ->maxSize(10240) // 10MB
                        ->storeFiles(false)
                        ->required(),
                    
                    TextInput::make('fileName')
                        ->label('Nama File')
                        ->placeholder('audio_pemanggilan.mp3')
                        ->required(),
                ])
                ->action('uploadAudio'),
        ];
    }

    public function testAudio(): void
    {
        if (empty($this->audioUrl)) {
            Notification::make()
                ->title('Error')
                ->body('URL audio tidak boleh kosong')
                ->danger()
                ->send();
            return;
        }

        // Test audio dengan membuat audio element
        $this->dispatch('test-audio', url: $this->audioUrl);
        
        Notification::make()
            ->title('Test Audio')
            ->body('Audio sedang di-test, periksa console browser')
            ->info()
            ->send();
    }

    public function testTts(): void
    {
        $this->validate([
            'ttsPreviewText' => 'required|string|max:500',
            'ttsRate' => 'numeric|between:0.5,1.5',
            'ttsPitch' => 'numeric|between:0.5,1.5',
            'ttsVolume' => 'numeric|between:0,1',
        ]);

        $this->dispatch('test-tts',
            text: $this->ttsPreviewText,
            voice: $this->ttsVoice,
            rate: (float) $this->ttsRate,
            pitch: (float) $this->ttsPitch,
            volume: (float) $this->ttsVolume,
        );
    }

    public function saveAudio(): void
    {
        $this->validate([
            'audioUrl' => 'nullable|url',
            'audioName' => 'required|string|max:255',
        ]);

        app(AudioConfigurationService::class)->save([
            'url' => $this->audioUrl,
            'name' => $this->audioName,
            'description' => $this->audioDescription,
            'type' => $this->audioType,
            'tts' => [
                'voice' => $this->ttsVoice,
                'rate' => (float) $this->ttsRate,
                'pitch' => (float) $this->ttsPitch,
                'volume' => (float) $this->ttsVolume,
            ],
        ]);

        Notification::make()
            ->title('Berhasil')
            ->body('Konfigurasi audio berhasil disimpan')
            ->success()
            ->send();
    }

    public function uploadAudio(array $data): void
    {
        $file = $data['audioFile'];
        $requestedName = pathinfo((string) $data['fileName'], PATHINFO_FILENAME);
        $fileName = (Str::slug($requestedName) ?: 'audio') . '.' . $file->getClientOriginalExtension();
        
        // Simpan file ke storage
        $path = $file->storeAs('audio', $fileName, 'public');
        
        $storedUrl = Storage::disk('public')->url($path);
        app(AudioConfigurationService::class)->save([
            'url' => $storedUrl,
            'name' => $fileName,
            'description' => 'Uploaded audio file',
            'type' => 'announcement',
        ]);

        $this->audioUrl = $storedUrl;
        $this->audioName = $fileName;
        $this->audioDescription = 'Uploaded audio file';
        $this->audioType = 'announcement';

        Notification::make()
            ->title('Berhasil')
            ->body('File audio berhasil diupload')
            ->success()
            ->send();
    }

    public function getViewData(): array
    {
        $audioConfig = app(AudioConfigurationService::class)->get();
        
        return [
            'audioConfig' => $audioConfig,
            'currentAudioUrl' => $audioConfig['url'] ?? asset('sounds/opening.mp3'),
        ];
    }
}
