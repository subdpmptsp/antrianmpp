<?php

namespace App\Livewire;

use App\Support\TimeOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class ModernPicker extends Component implements HasForms
{
    use InteractsWithForms;

    #[Modelable]
    public ?string $value = null;

    public string $type = 'date';

    public string $label = '';

    public ?string $min = null;

    public bool $disabled = false;

    public function form(Form $form): Form
    {
        $field = match ($this->type) {
            'time' => Select::make('value')
                ->options(TimeOptions::everyFiveMinutes())
                ->searchable()
                ->searchPrompt('Cari jam, contoh 07:30')
                ->searchingMessage('Mencari pilihan jam...')
                ->noSearchResultsMessage('Jam tidak ditemukan')
                ->native(false)
                ->placeholder('Pilih jam'),
            'datetime' => DateTimePicker::make('value')
                ->seconds(false)
                ->minutesStep(5)
                ->displayFormat('d M Y H:i'),
            default => DatePicker::make('value')
                ->format('Y-m-d')
                ->displayFormat('d M Y')
                ->placeholder('Pilih tanggal')
                ->closeOnDateSelection(),
        };

        $field
            ->label($this->label ?: null)
            ->hiddenLabel(blank($this->label))
            ->disabled($this->disabled)
            ->live();

        if ($field instanceof DatePicker) {
            $field
                ->native(false)
                ->minDate($this->min);
        }

        return $form->schema([$field]);
    }

    public function render(): View
    {
        return view('livewire.modern-picker');
    }
}
