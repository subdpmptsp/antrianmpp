<script>
(() => {
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || ''

    const post = async (url, body = null) => {
        const response = await fetch(url, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
        })

        const data = await response.json().catch(() => ({}))
        if (!response.ok) throw Object.assign(new Error(data.message || 'Permintaan gagal diproses.'), { response, data })

        return data
    }

    const normalizeTicket = (payload) => ({
        printUrl: payload?.printUrl || payload?.print_url,
        confirmUrl: payload?.confirmUrl || payload?.confirm_url,
        failUrl: payload?.failUrl || payload?.fail_url,
    })

    const initializeKiosk = () => {
        const root = document.querySelector('[data-kiosk-root]')
        if (!root) return

        window.__queueKioskController?.abort()
        const controller = new AbortController()
        window.__queueKioskController = controller
        const { signal } = controller
        const loading = root.querySelector('[data-kiosk-loading]')
        const errorOverlay = root.querySelector('[data-kiosk-error]')
        const errorMessage = root.querySelector('[data-kiosk-error-message]')
        const serviceButtons = [...root.querySelectorAll('[data-kiosk-service]')]
        let processing = false
        let idleTimer = null

        const setLoading = (visible) => {
            loading?.classList.toggle('is-visible', visible)
            loading?.setAttribute('aria-hidden', visible ? 'false' : 'true')
            serviceButtons.forEach((button) => { button.disabled = visible })
        }

        const goHome = async () => {
            if (root.dataset.mode === 'livewire') {
                const wireRoot = root.closest('[wire\\:id]')
                const component = wireRoot && window.Livewire
                    ? window.Livewire.find(wireRoot.getAttribute('wire:id'))
                    : null
                await component?.call('resetSelection')
                processing = false
                setLoading(false)
                return
            }

            window.location.replace(root.dataset.homeUrl)
        }

        const showError = (message) => {
            processing = false
            setLoading(false)
            if (errorMessage) errorMessage.textContent = message || 'Silakan hubungi petugas.'
            if (errorOverlay) errorOverlay.hidden = false
        }

        const printTicket = async (rawPayload) => {
            const ticket = normalizeTicket(rawPayload)
            if (!ticket.printUrl || !ticket.confirmUrl || !ticket.failUrl) throw new Error('Data pencetakan tidak lengkap.')

            let frame = null
            try {
                frame = document.createElement('iframe')
                frame.className = 'queue-kiosk__print-frame'
                frame.setAttribute('aria-hidden', 'true')
                document.body.appendChild(frame)

                await new Promise((resolve, reject) => {
                    const timer = window.setTimeout(() => reject(new Error('Printer tidak merespons tepat waktu.')), 12000)
                    frame.addEventListener('load', () => {
                        window.clearTimeout(timer)
                        resolve()
                    }, { once: true })
                    frame.addEventListener('error', () => {
                        window.clearTimeout(timer)
                        reject(new Error('Halaman tiket gagal dimuat.'))
                    }, { once: true })
                    frame.src = ticket.printUrl
                })

                const printWindow = frame.contentWindow
                if (!printWindow) throw new Error('Printer browser tidak tersedia.')

                await new Promise((resolve) => {
                    let completed = false
                    const complete = () => {
                        if (completed) return
                        completed = true
                        resolve()
                    }
                    printWindow.addEventListener('afterprint', complete, { once: true })
                    printWindow.focus()
                    printWindow.print()
                    window.setTimeout(complete, 1600)
                })

                await post(ticket.confirmUrl)
                await goHome()
            } catch (error) {
                await post(ticket.failUrl).catch(() => {})
                showError(error.message || 'Pencetakan gagal. Silakan hubungi petugas.')
                throw error
            } finally {
                frame?.remove()
            }
        }

        window.queueKioskHandleTicket = (payload) => printTicket(payload)
        window.queueKioskShowError = showError

        serviceButtons.forEach((button) => {
            button.addEventListener('click', async () => {
                if (processing) return
                processing = true
                setLoading(true)

                try {
                    if (root.dataset.mode === 'livewire') {
                        const wireRoot = root.closest('[wire\\:id]')
                        const component = wireRoot && window.Livewire
                            ? window.Livewire.find(wireRoot.getAttribute('wire:id'))
                            : null
                        if (!component) throw new Error('Koneksi kiosk tidak tersedia.')
                        await component.call('selectService', Number(button.dataset.serviceId))
                        return
                    }

                    const form = document.getElementById(button.dataset.formId)
                    if (!form) throw new Error('Form layanan tidak ditemukan.')
                    const payload = await post(form.action, new FormData(form))
                    await printTicket(payload)
                } catch (error) {
                    if (!errorOverlay || errorOverlay.hidden) showError(error.message)
                }
            }, { signal })
        })

        root.querySelector('[data-kiosk-error-home]')?.addEventListener('click', goHome, { signal })
        root.querySelector('[data-kiosk-fullscreen]')?.addEventListener('click', async () => {
            if (!document.fullscreenElement) await document.documentElement.requestFullscreen?.()
            else await document.exitFullscreen?.()
        }, { signal })

        const clock = root.querySelector('[data-kiosk-clock]')
        const date = root.querySelector('[data-kiosk-date]')
        const connection = root.querySelector('.queue-kiosk__connection')
        const onlineText = root.querySelector('[data-kiosk-online-text]')
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
        updateClock()
        updateConnection()
        window.addEventListener('online', updateConnection, { signal })
        window.addEventListener('offline', updateConnection, { signal })
        window.__queueKioskClock = window.setInterval(updateClock, 30000)

        const cards = [...root.querySelectorAll('[data-kiosk-institution-card]')]
        const pageCurrent = root.querySelector('[data-kiosk-page-current]')
        const pageTotal = root.querySelector('[data-kiosk-page-total]')
        const previous = root.querySelector('[data-kiosk-page-prev]')
        const next = root.querySelector('[data-kiosk-page-next]')
        const pageSize = 6
        let page = 1
        const totalPages = Math.max(1, Math.ceil(cards.length / pageSize))
        const renderPage = () => {
            cards.forEach((card, index) => card.classList.toggle('is-page-hidden', index < (page - 1) * pageSize || index >= page * pageSize))
            if (pageCurrent) pageCurrent.textContent = String(page)
            if (pageTotal) pageTotal.textContent = String(totalPages)
            if (previous) previous.disabled = page === 1
            if (next) next.disabled = page === totalPages
        }
        previous?.addEventListener('click', () => { if (page > 1) { page--; renderPage() } }, { signal })
        next?.addEventListener('click', () => { if (page < totalPages) { page++; renderPage() } }, { signal })
        renderPage()

        const resetIdleTimer = () => {
            if (root.dataset.step === '1' || processing) return
            window.clearTimeout(idleTimer)
            idleTimer = window.setTimeout(goHome, 45000)
        }
        root.addEventListener('pointerdown', resetIdleTimer, { signal })
        root.addEventListener('keydown', resetIdleTimer, { signal })
        resetIdleTimer()

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
