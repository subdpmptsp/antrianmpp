<script>
(() => {
    const initializeKiosk = () => {
        const root = document.querySelector('[data-kiosk-root]')
        if (!root) return

        window.__queueKioskController?.abort()
        const controller = new AbortController()
        window.__queueKioskController = controller
        const { signal } = controller

        const clock = root.querySelector('[data-kiosk-clock]')
        const date = root.querySelector('[data-kiosk-date]')
        const connection = root.querySelector('.queue-kiosk__connection')
        const onlineText = root.querySelector('[data-kiosk-online-text]')
        const dialog = root.querySelector('[data-kiosk-dialog]')
        const loading = root.querySelector('[data-kiosk-loading]')
        let selectedService = null
        let idleTimer = null

        const updateClock = () => {
            const now = new Date()
            if (clock) clock.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).replace('.', ':')
            if (date) date.textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' })
        }

        const updateConnection = () => {
            const online = navigator.onLine
            connection?.classList.toggle('is-offline', !online)
            if (onlineText) onlineText.textContent = online ? 'Sistem siap digunakan' : 'Koneksi terputus'
        }

        const goHome = () => {
            if (root.dataset.mode === 'livewire') {
                const wireRoot = root.closest('[wire\\:id]')
                if (wireRoot && window.Livewire) {
                    window.Livewire.find(wireRoot.getAttribute('wire:id'))?.call('resetSelection')
                    return
                }
            }

            window.location.assign(root.dataset.homeUrl)
        }

        const resetIdleTimer = () => {
            if (root.dataset.step === '1') return
            window.clearTimeout(idleTimer)
            idleTimer = window.setTimeout(goHome, 45000)
        }

        root.addEventListener('pointerdown', resetIdleTimer, { signal })
        root.addEventListener('keydown', resetIdleTimer, { signal })
        window.addEventListener('online', updateConnection, { signal })
        window.addEventListener('offline', updateConnection, { signal })

        root.querySelector('[data-kiosk-fullscreen]')?.addEventListener('click', async () => {
            if (!document.fullscreenElement) await document.documentElement.requestFullscreen?.()
            else await document.exitFullscreen?.()
        }, { signal })

        root.querySelectorAll('[data-kiosk-service]').forEach((button) => {
            button.addEventListener('click', () => {
                selectedService = {
                    id: button.dataset.serviceId,
                    formId: button.dataset.formId,
                    name: button.dataset.serviceName,
                    institution: button.dataset.institutionName,
                }
                const serviceLabel = dialog?.querySelector('[data-kiosk-dialog-service]')
                const institutionLabel = dialog?.querySelector('[data-kiosk-dialog-institution]')
                if (serviceLabel) serviceLabel.textContent = selectedService.name
                if (institutionLabel) institutionLabel.textContent = selectedService.institution
                dialog?.showModal()
            }, { signal })
        })

        root.querySelector('[data-kiosk-dialog-cancel]')?.addEventListener('click', () => dialog?.close(), { signal })
        root.querySelector('[data-kiosk-dialog-confirm]')?.addEventListener('click', () => {
            if (!selectedService) return
            dialog?.close()
            loading?.classList.add('is-visible')
            loading?.setAttribute('aria-hidden', 'false')

            if (root.dataset.mode === 'livewire') {
                const wireRoot = root.closest('[wire\\:id]')
                const component = wireRoot && window.Livewire
                    ? window.Livewire.find(wireRoot.getAttribute('wire:id'))
                    : null
                component?.call('selectService', Number(selectedService.id))
                    .finally(() => loading?.classList.remove('is-visible'))
                return
            }

            document.getElementById(selectedService.formId)?.requestSubmit()
        }, { signal })

        updateClock()
        updateConnection()
        resetIdleTimer()
        window.__queueKioskClock = window.setInterval(updateClock, 30000)
        signal.addEventListener('abort', () => {
            window.clearInterval(window.__queueKioskClock)
            window.clearTimeout(idleTimer)
        }, { once: true })
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeKiosk, { once: true })
    else initializeKiosk()

    document.addEventListener('livewire:navigated', initializeKiosk)
})()
</script>
