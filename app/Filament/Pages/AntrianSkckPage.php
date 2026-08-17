<?php

namespace App\Filament\Pages;

use Afatmustafa\FilamentTurnstile\Forms\Components\Turnstile;
use App\Models\AntrianSkck;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SimplePage;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AntrianSkckPage extends SimplePage implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.antrian-skck-page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Antrian SKCK MPP Siola';

    public function hasLogo(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama')
                    ->label('Nama Lengkap Sesuai KTP')
                    ->required(),
                TextInput::make('nik')
                    ->required()
                    ->rules([
                        Rule::unique('antrian_skcks', 'nik')
                            ->where('queue_date', now()->toDateString()),
                    ])
                    ->minLength(16)
                    ->label("NIK"),
                TextInput::make('nomor_whatsapp')
                    ->required()
                    ->rules([
                        Rule::unique('antrian_skcks', 'nomor_whatsapp')
                            ->where('queue_date', now()->toDateString()),
                    ])
                    ->label("Nomor Whatsapp"),
                Turnstile::make('turnstile')
                    ->theme('light')
                    ->size('normal')
                    ->language('id-ID'),

                Actions::make([
                    Action::make('Kirim')
                        ->extraAttributes([
                            'class' => 'w-full'
                        ])
                        ->requiresConfirmation()
                        ->modalHeading(new HtmlString('Pastikan data sesuai'))
                        ->modalDescription(new HtmlString("Jika data tidak sesuai nomor antrian anda akan dibatalkan!"))
                        ->action(function () {
                            $data = $this->form->getState();
                            $antrian = null;
                            DB::transaction(function () use ($data, &$antrian) {
                                $today = now()->toDateString();

                                // Cari antrian terakhir HARI INI dengan lock
                                $last = AntrianSkck::lockForUpdate()
                                    ->whereDate('created_at', $today)
                                    ->orderByDesc('antrian')
                                    ->first();

                                $next = $last ? $last->antrian + 1 : 1;

                                if ($next > 80) {
                                    throw ValidationException::withMessages([
                                        'nama' => 'Kuota antrian hari ini sudah penuh.',
                                    ]);
                                }

                                $antrian = AntrianSkck::create([
                                    'nama'           => $data['nama'],
                                    'nik'            => $data['nik'],
                                    'nomor_whatsapp' => $data['nomor_whatsapp'],
                                    'antrian'        => $next,
                                    'queue_date'     => $today,
                                ]);
                            });

                            $ticketUrl = URL::temporarySignedRoute('skck.ticket', now()->addMinutes(10), [
                                'id' => 'SKCK' . $antrian->id,
                            ]);
                            $this->js('window.open(' . json_encode($ticketUrl) . ', "_blank");');
                        }),
                    Action::make('Cetak Ulang Antrian')
                        ->extraAttributes([
                            'class' => 'w-full'
                        ])
                        ->color(Color::Blue)
                        ->form(function ($form) {
                            return $form->schema([
                                TextInput::make('nik')->label('NIK')->required(),
                            ]);
                        })
                        ->action(function ($data) {
                            $nik = $data['nik'];
                            $antrian = AntrianSkck::where('nik', $nik)
                                ->where('queue_date', now()->toDateString())
                                ->latest('id')
                                ->first();

                            if ($antrian == null) {
                                Notification::make('fail')
                                    ->title('Oops!')
                                    ->body('NIK Anda belum terdaftar')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $ticketUrl = URL::temporarySignedRoute('skck.ticket', now()->addMinutes(10), [
                                'id' => 'SKCK' . $antrian->id,
                            ]);
                            $this->js('window.open(' . json_encode($ticketUrl) . ', "_blank");');
                        }),
                    Action::make('Cek Antrian Terdaftar')
                        ->extraAttributes([
                            'class' => 'w-full'
                        ])
                        ->color(Color::Green)
                        ->action(function ($data) {

                            $this->js('window.open("' . '/antrian-skck-mpp/terdaftar", "_blank");');
                        })
                ])
            ])
            ->statePath('data');
    }
}
