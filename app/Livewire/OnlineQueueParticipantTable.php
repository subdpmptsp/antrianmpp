<?php

namespace App\Livewire;

use App\Models\OnlineQueueReservation;
use App\Models\Service;
use App\Services\OnlineQueueService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OnlineQueueParticipantTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $reservationDate = '';

    public string $reservationStatus = 'all';

    public string $reservationSearch = '';

    public string $reservationServiceId = '';

    /** @return array<int, string> */
    public function getServiceOptionsProperty(): array
    {
        return Service::query()
            ->with('instansi')
            ->where('is_active', true)
            ->where('is_archived', false)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Service $service): array => [
                $service->id => collect([
                    $service->instansi?->nama_instansi,
                    $service->name,
                    $service->prefix,
                ])->filter()->implode(' · '),
            ])
            ->all();
    }

    public function updatedReservationDate(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
    }

    public function updatedReservationStatus(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
    }

    public function updatedReservationServiceId(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
    }

    public function updatedReservationSearch(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
    }

    public function reservationDateFilter(): string
    {
        return substr($this->reservationDate, 0, 10);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OnlineQueueReservation::query()
                    ->with(['service.instansi', 'session'])
                    ->when($this->reservationDateFilter() !== '', fn ($query) => $query->whereDate('service_date', $this->reservationDateFilter()))
                    ->when($this->reservationStatus !== 'all', fn ($query) => $query->where('status', $this->reservationStatus))
                    ->when($this->reservationServiceId !== '', fn ($query) => $query->where('service_id', (int) $this->reservationServiceId))
                    ->when($this->reservationSearch !== '', function ($query): void {
                        $term = trim($this->reservationSearch);
                        $query->where(fn ($nested) => $nested
                            ->where('booking_code', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%"));
                    })
            )
            ->columns([
                TextColumn::make('booking_code')->label('Kode booking')
                    ->description(fn (OnlineQueueReservation $record): string => $record->service_date->format('d-m-Y'))
                    ->weight('bold')->sortable(),
                TextColumn::make('name')->label('Nama')->wrap()->sortable(),
                TextColumn::make('masked_nik')->label('NIK'),
                TextColumn::make('service.name')->label('Layanan & sesi')
                    ->description(fn (OnlineQueueReservation $record): string => substr((string) $record->session?->starts_at, 0, 5).'–'.substr((string) $record->session?->ends_at, 0, 5))
                    ->wrap()->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => [
                        OnlineQueueReservation::STATUS_BOOKED => 'Belum hadir / check-in',
                        OnlineQueueReservation::STATUS_CHECKED_IN => 'Hadir / sudah check-in',
                        OnlineQueueReservation::STATUS_CANCELED => 'Dibatalkan',
                        OnlineQueueReservation::STATUS_EXPIRED => 'Tidak hadir (no-show)',
                    ][$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        OnlineQueueReservation::STATUS_CHECKED_IN => 'success',
                        OnlineQueueReservation::STATUS_CANCELED => 'danger',
                        OnlineQueueReservation::STATUS_EXPIRED => 'warning',
                        default => 'info',
                    })->sortable(),
            ])
            ->actions([
                Action::make('detail')->label('Detail')->icon('heroicon-o-eye')->color('gray')
                    ->modalHeading('Detail reservasi')->modalSubmitAction(false)->modalCancelActionLabel('Tutup')
                    ->modalContent(function (OnlineQueueReservation $record) {
                        $reservation = $record->loadMissing(['service.instansi', 'session', 'queue', 'audits.user']);

                        return view('filament.partials.online-reservation-detail', compact('reservation'));
                    }),
                Action::make('checkin')->label('Check-in')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (OnlineQueueReservation $record): bool => $record->status === OnlineQueueReservation::STATUS_BOOKED)
                    ->requiresConfirmation()
                    ->modalDescription('Nomor antrean layanan akan diterbitkan dan tindakan petugas dicatat pada audit log.')
                    ->action(function (OnlineQueueReservation $record, OnlineQueueService $onlineQueues): void {
                        $queue = $onlineQueues->checkIn($record, auth()->id(), 'admin_assisted');
                        Notification::make()->title('Check-in berhasil: '.$queue->number)->success()->send();
                    }),
                Action::make('cancel')->label('Batalkan')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (OnlineQueueReservation $record): bool => $record->status === OnlineQueueReservation::STATUS_BOOKED)
                    ->form([Forms\Components\Textarea::make('reason')->label('Alasan pembatalan')->required()->maxLength(500)])
                    ->requiresConfirmation()
                    ->action(function (OnlineQueueReservation $record, array $data, OnlineQueueService $onlineQueues): void {
                        $canceled = $onlineQueues->cancel($record, auth()->id(), $data['reason']);
                        Notification::make()->title($canceled ? 'Reservasi dibatalkan' : 'Reservasi tidak dapat dibatalkan')
                            ->color($canceled ? 'success' : 'danger')->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->emptyStateHeading('Tidak ada reservasi sesuai filter')
            ->emptyStateDescription('Ubah tanggal, status, layanan, atau kata pencarian untuk melihat data lainnya.')
            ->striped();
    }

    public function render(): View
    {
        return view('livewire.online-queue-participant-table');
    }
}
