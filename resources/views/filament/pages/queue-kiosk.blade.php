<x-filament::page>
    @include('kiosk.partials.catalog', ['interactionMode' => 'livewire'])
</x-filament::page>

@push('styles')
    @include('kiosk.partials.styles')
@endpush

@push('scripts')
    @include('kiosk.partials.scripts')
    <script>
        (() => {
            const registerQueueKioskListeners = () => {
                if (!window.Livewire) return

                window.__queueKioskTicketCleanup?.()
                window.__queueKioskErrorCleanup?.()

                window.__queueKioskTicketCleanup = Livewire.on('ticket-ready', (payload) => {
                    window.queueKioskHandleTicket?.(payload).catch(() => {})
                })
                window.__queueKioskErrorCleanup = Livewire.on('kiosk-print-error', (payload) => {
                    window.queueKioskShowError?.(payload?.message || 'Tiket gagal dicetak. Silakan hubungi petugas.')
                })
            }

            registerQueueKioskListeners()
            document.addEventListener('livewire:navigated', registerQueueKioskListeners)
        })()
    </script>
@endpush
