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

                window.__queueKioskPdfCleanup?.()
                window.__queueKioskBarcodeCleanup?.()
                window.__queueKioskNotificationCleanup?.()
                window.__queueKioskPrintCleanup?.()

                window.__queueKioskPdfCleanup = Livewire.on('open-pdf', (payload) => {
                    const url = payload?.url || payload
                    if (url) window.open(url, '_blank')
                })
                window.__queueKioskBarcodeCleanup = Livewire.on('open-barcode', (payload) => {
                    const url = payload?.url || payload
                    if (url) window.open(url, '_blank')
                })
                window.__queueKioskNotificationCleanup = Livewire.on('notify', (payload) => {
                    const message = typeof payload === 'string' ? payload : payload?.message
                    if (message) window.alert(message)
                })
                window.__queueKioskPrintCleanup = Livewire.on('print-start', async (payload) => {
                    const text = typeof payload === 'string' ? payload : payload?.text
                    if (text && typeof window.printThermal === 'function') await window.printThermal(text)
                })
            }

            registerQueueKioskListeners()
            document.addEventListener('livewire:navigated', registerQueueKioskListeners)
        })()
    </script>
@endpush
