<main class="siola-login-shell">
        <section class="siola-login-card" aria-label="Masuk ke Siola Q">
            <aside class="siola-login-brand">
                <img
                    src="{{ asset('images/siola-queue-login-logo.png') }}"
                    alt="SIOLA Queue"
                    class="siola-login-brand__logo"
                >

                <div class="siola-login-brand__copy">
                    <p class="siola-login-brand__name">SiolaQ</p>
                    <p class="siola-login-brand__tagline">Smart integrated online queue</p>
                </div>

                <p class="siola-login-brand__government">Pemerintah Kota Surabaya</p>
            </aside>

            <section class="siola-login-form-panel">
                <div class="siola-login-form-wrap">
                    <h1>Selamat datang di Siola Q</h1>

                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

                    <x-filament-panels::form id="form" wire:submit="authenticate" class="siola-login-form">
                        {{ $this->form }}

                        <x-filament-panels::form.actions
                            :actions="$this->getCachedFormActions()"
                            :full-width="$this->hasFullWidthFormActions()"
                        />
                    </x-filament-panels::form>

                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
                </div>

                <footer class="siola-login-footer">© {{ now()->year }} MPP Siola, Pemerintah Kota Surabaya</footer>
            </section>
        </section>
    
    <style>
        .siola-login-shell {
            --siola-login-page: #f7f9fc;
            --siola-login-surface: #ffffff;
            --siola-login-border: #dfe5ee;
            --siola-login-heading: #102a5c;
            --siola-login-text: #172b4d;
            --siola-login-muted: #56657c;
            --siola-login-subtle: #7b8799;
            --siola-login-input: #ffffff;
            --siola-login-input-border: #cfd8e6;
            align-items: center;
            background: var(--siola-login-page);
            color: var(--siola-login-text);
            color-scheme: light;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .siola-login-card {
            background: var(--siola-login-surface);
            border: 1px solid var(--siola-login-border);
            border-radius: 1.25rem;
            box-shadow: 0 18px 45px rgb(15 23 42 / 8%);
            display: grid;
            grid-template-columns: minmax(0, .72fr) minmax(0, 1.7fr);
            max-width: 980px;
            min-height: 540px;
            overflow: hidden;
            width: 100%;
        }

        .siola-login-brand {
            border-right: 1px solid var(--siola-login-border);
            display: flex;
            flex-direction: column;
            padding: 4rem 2.25rem 2.25rem;
        }

        .siola-login-brand__logo {
            height: auto;
            max-width: 210px;
            object-fit: contain;
            width: 100%;
        }

        .siola-login-brand__copy {
            margin-top: 2.5rem;
        }

        .siola-login-brand__name {
            color: var(--siola-login-heading);
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -.03em;
            line-height: 1.2;
            margin: 0;
        }

        .siola-login-brand__tagline {
            color: var(--siola-login-muted);
            font-size: .9rem;
            line-height: 1.5;
            margin: .45rem 0 0;
        }

        .siola-login-brand__government {
            color: var(--siola-login-subtle);
            font-size: .75rem;
            margin: auto 0 0;
        }

        .siola-login-form-panel {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .siola-login-form-wrap {
            margin: auto;
            max-width: 520px;
            padding: 3.75rem 3.5rem 2.5rem;
            width: 100%;
        }

        .siola-login-form-wrap h1 {
            color: var(--siola-login-heading);
            font-size: clamp(1.6rem, 2.2vw, 2rem);
            font-weight: 700;
            letter-spacing: -.04em;
            line-height: 1.15;
            margin: 0 0 3rem;
        }

        .siola-login-form {
            gap: 1.35rem;
        }

        .siola-login-form .fi-fo-field-wrp-label {
            margin-bottom: .45rem;
        }

        .siola-login-form .fi-fo-field-wrp-label label {
            color: var(--siola-login-text);
            font-size: .875rem;
            font-weight: 600;
        }

        .siola-login-form .fi-input-wrp {
            background: var(--siola-login-input);
            border-color: var(--siola-login-input-border);
            border-radius: .5rem;
            min-height: 2.8rem;
        }

        .siola-login-form .fi-fo-checkbox {
            margin-top: -.2rem;
        }

        /* Warna teks dikunci secara eksplisit agar tidak hilang saat tema
           browser/Filament berubah atau ketika Chrome mengisi otomatis. */
        .siola-login-form .fi-input-wrp input,
        .siola-login-form .fi-input-wrp textarea {
            background: transparent !important;
            caret-color: var(--siola-login-text);
            color: var(--siola-login-text) !important;
            -webkit-text-fill-color: var(--siola-login-text);
        }

        .siola-login-form .fi-input-wrp input::placeholder,
        .siola-login-form .fi-input-wrp textarea::placeholder {
            color: var(--siola-login-subtle) !important;
            opacity: 1;
        }

        .siola-login-form .fi-input-wrp:focus-within {
            border-color: #2875d6;
            box-shadow: 0 0 0 3px rgb(40 117 214 / 18%);
        }

        .siola-login-form .fi-fo-checkbox-label,
        .siola-login-form .fi-input-wrp-suffix {
            color: var(--siola-login-text);
        }

        .siola-login-form input:-webkit-autofill,
        .siola-login-form input:-webkit-autofill:hover,
        .siola-login-form input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px var(--siola-login-input) inset;
            -webkit-text-fill-color: var(--siola-login-text);
            caret-color: var(--siola-login-text);
        }

        .siola-login-form .fi-btn {
            background: #2875d6;
            border-radius: .5rem;
            font-weight: 700;
            min-height: 2.8rem;
        }

        .siola-login-form .fi-btn:hover {
            background: #1d65bd;
        }

        .siola-login-footer {
            border-top: 1px solid var(--siola-login-border);
            color: var(--siola-login-subtle);
            font-size: .75rem;
            padding: 1.35rem 2rem;
            text-align: center;
        }

        .dark .siola-login-shell {
            --siola-login-page: #0f172a;
            --siola-login-surface: #172033;
            --siola-login-border: #334155;
            --siola-login-heading: #f8fafc;
            --siola-login-text: #e2e8f0;
            --siola-login-muted: #b6c2d3;
            --siola-login-subtle: #94a3b8;
            --siola-login-input: #0f172a;
            --siola-login-input-border: #475569;
            color-scheme: dark;
        }

        .dark .siola-login-card {
            box-shadow: 0 18px 45px rgb(0 0 0 / 28%);
        }

        .dark .siola-login-form .fi-input-wrp:focus-within {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgb(96 165 250 / 22%);
        }

        @media (max-width: 680px) {
            .siola-login-shell { padding: 1rem; }
            .siola-login-card { grid-template-columns: 1fr; min-height: 0; }
            .siola-login-brand { border-bottom: 1px solid var(--siola-login-border); border-right: 0; padding: 1.75rem; }
            .siola-login-brand__logo { max-width: 160px; }
            .siola-login-brand__copy { margin-top: 1.25rem; }
            .siola-login-brand__government { margin-top: 1.5rem; }
            .siola-login-form-wrap { padding: 2.25rem 1.75rem; }
            .siola-login-form-wrap h1 { margin-bottom: 2rem; }
        }
    </style>
</main>
