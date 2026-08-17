<style>
    :root {
        --kiosk-navy: #082b5c;
        --kiosk-blue: #1264ad;
        --kiosk-sky: #e9f4ff;
        --kiosk-ink: #17283d;
        --kiosk-muted: #68798d;
        --kiosk-line: #d9e4ef;
        --kiosk-success: #14865a;
        --kiosk-danger: #c63848;
    }

    html, body { min-height: 100%; margin: 0; background: #edf3f9; color: var(--kiosk-ink); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    button, a { -webkit-tap-highlight-color: transparent; }
    button { font: inherit; }
    .queue-kiosk * { box-sizing: border-box; }
    .queue-kiosk { min-height: 100vh; display: flex; flex-direction: column; background: linear-gradient(180deg, #f8fbfe 0%, #edf3f9 100%); }

    .queue-kiosk__header { min-height: 108px; display: flex; align-items: center; justify-content: space-between; gap: 28px; padding: 17px clamp(24px, 4vw, 58px); color: #fff; background: linear-gradient(120deg, #07264f, #07589d); box-shadow: 0 10px 30px rgba(8, 43, 92, .2); }
    .queue-kiosk__brand, .queue-kiosk__header-tools, .queue-kiosk__toolbar, .queue-kiosk__selected-institution, .queue-kiosk__institution-card, .queue-kiosk__service-card, .queue-kiosk__footer, .queue-kiosk__connection { display: flex; align-items: center; }
    .queue-kiosk__brand { gap: 18px; }
    .queue-kiosk__brand-copy { display: grid; gap: 2px; }
    .queue-kiosk__brand-copy > span { color: #a9dff9; font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .queue-kiosk__brand-copy h1 { margin: 0; font-size: clamp(23px, 2.3vw, 34px); line-height: 1.08; font-weight: 850; }
    .queue-kiosk__brand-copy p { margin: 3px 0 0; color: #d2e7f8; font-size: 14px; }
    .queue-kiosk__header-tools { gap: 16px; }
    .queue-kiosk__logo { display: grid; place-items: center; flex: 0 0 auto; }
    .queue-kiosk__logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .queue-kiosk__logo--city { width: 68px; height: 68px; }
    .queue-kiosk__logo--office { width: 76px; height: 76px; padding: 7px; border-radius: 18px; background: #fff; }
    .queue-kiosk__clock { min-width: 110px; text-align: right; }
    .queue-kiosk__clock strong { display: block; font-size: 28px; font-variant-numeric: tabular-nums; }
    .queue-kiosk__clock span { display: block; margin-top: 3px; color: #c8e0f4; font-size: 11px; }
    .queue-kiosk__fullscreen { width: 50px; height: 50px; display: grid; place-items: center; color: #fff; border: 1px solid rgba(255,255,255,.3); border-radius: 14px; background: rgba(255,255,255,.1); cursor: pointer; }
    .queue-kiosk__fullscreen svg, .queue-kiosk__arrow, .queue-kiosk__back svg, .queue-kiosk__pagination svg { width: 22px; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }

    .queue-kiosk__main { width: min(1450px, 100%); flex: 1; margin: 0 auto; padding: 22px clamp(18px, 4vw, 56px) 28px; }
    .queue-kiosk__steps { max-width: 700px; display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin: 0 auto 18px; padding: 8px; border: 1px solid var(--kiosk-line); border-radius: 19px; background: rgba(255,255,255,.9); }
    .queue-kiosk__step { min-height: 52px; display: flex; align-items: center; justify-content: center; gap: 10px; color: #8a98a8; border-radius: 13px; }
    .queue-kiosk__step.is-active { color: #0d4f8d; background: var(--kiosk-sky); }
    .queue-kiosk__step.is-complete { color: var(--kiosk-success); }
    .queue-kiosk__step-number { width: 32px; height: 32px; display: grid; place-items: center; border: 2px solid currentColor; border-radius: 50%; font-weight: 850; }
    .queue-kiosk__step-number svg { width: 17px; fill: none; stroke: currentColor; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; }
    .queue-kiosk__step strong, .queue-kiosk__step small { display: block; }
    .queue-kiosk__step strong { font-size: 14px; }
    .queue-kiosk__step small { margin-top: 1px; color: var(--kiosk-muted); font-size: 11px; }

    .queue-kiosk__content { min-height: 430px; padding: clamp(22px, 3vw, 38px); border: 1px solid #d5e1ed; border-radius: 26px; background: rgba(255,255,255,.94); box-shadow: 0 18px 48px rgba(31,57,87,.08); }
    .queue-kiosk__intro { max-width: 800px; margin: 0 auto 22px; text-align: center; }
    .queue-kiosk__intro > span { color: var(--kiosk-blue); font-size: 11px; font-weight: 850; letter-spacing: .13em; text-transform: uppercase; }
    .queue-kiosk__intro h2 { margin: 6px 0 5px; font-size: clamp(27px, 3vw, 40px); line-height: 1.1; font-weight: 850; letter-spacing: -.02em; }
    .queue-kiosk__intro p { margin: 0; color: var(--kiosk-muted); font-size: 15px; }

    .queue-kiosk__institution-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    .queue-kiosk__institution-card, .queue-kiosk__service-card { min-height: 116px; gap: 14px; padding: 16px 18px; color: var(--kiosk-ink); text-align: left; text-decoration: none; border: 1px solid #d7e2ed; border-radius: 19px; background: #fff; box-shadow: 0 7px 18px rgba(31,61,94,.06); cursor: pointer; transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease; }
    .queue-kiosk__institution-card:hover, .queue-kiosk__institution-card:focus-visible, .queue-kiosk__service-card:hover, .queue-kiosk__service-card:focus-visible { transform: translateY(-3px); border-color: #80afe0; box-shadow: 0 14px 28px rgba(20,83,143,.14); outline: none; }
    .queue-kiosk__institution-card:active, .queue-kiosk__service-card:active { transform: scale(.985); }
    .queue-kiosk__institution-card.is-page-hidden { display: none; }
    .queue-kiosk__institution-logo, .queue-kiosk__service-icon { width: 58px; height: 58px; display: grid; place-items: center; flex: 0 0 auto; color: var(--kiosk-blue); border-radius: 16px; background: var(--kiosk-sky); overflow: hidden; }
    .queue-kiosk__institution-logo.has-image { padding: 5px; background: #fff; border: 1px solid #e1e8f0; }
    .queue-kiosk__institution-logo img { width: 100%; height: 100%; object-fit: contain; }
    .queue-kiosk__institution-logo svg, .queue-kiosk__service-icon svg, .queue-kiosk__printer-icon svg { width: 30px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .queue-kiosk__institution-copy, .queue-kiosk__service-copy { min-width: 0; flex: 1; }
    .queue-kiosk__institution-copy strong, .queue-kiosk__institution-copy small, .queue-kiosk__service-copy strong, .queue-kiosk__service-copy small { display: block; }
    .queue-kiosk__institution-copy strong, .queue-kiosk__service-copy strong { font-size: 15px; line-height: 1.32; font-weight: 800; }
    .queue-kiosk__institution-copy small, .queue-kiosk__service-copy small { margin-top: 5px; color: var(--kiosk-muted); font-size: 11px; }
    .queue-kiosk__arrow { flex: 0 0 auto; color: #8194a8; }

    .queue-kiosk__pagination { display: flex; align-items: center; justify-content: center; gap: 15px; margin-top: 18px; }
    .queue-kiosk__pagination button { width: 44px; height: 44px; display: grid; place-items: center; color: var(--kiosk-blue); border: 1px solid #b8cfe5; border-radius: 13px; background: #fff; cursor: pointer; }
    .queue-kiosk__pagination button:disabled { opacity: .35; cursor: default; }
    .queue-kiosk__pagination strong { color: #5d7187; font-size: 13px; }

    .queue-kiosk__toolbar { justify-content: space-between; gap: 18px; margin-bottom: 10px; }
    .queue-kiosk__back { min-height: 48px; display: inline-flex; align-items: center; gap: 8px; padding: 0 16px; color: #0d4f8d; text-decoration: none; border: 1px solid #b8cfe5; border-radius: 13px; background: #fff; font-weight: 800; cursor: pointer; }
    .queue-kiosk__selected-institution { justify-content: flex-end; gap: 10px; max-width: 62%; text-align: right; }
    .queue-kiosk__selected-institution img { width: 44px; height: 44px; object-fit: contain; border-radius: 10px; }
    .queue-kiosk__selected-institution span { display: grid; }
    .queue-kiosk__selected-institution small { color: var(--kiosk-muted); font-size: 10px; text-transform: uppercase; letter-spacing: .08em; }
    .queue-kiosk__selected-institution strong { font-size: 14px; line-height: 1.25; }

    .queue-kiosk__service-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
    .queue-kiosk__service-form { display: none; }
    .queue-kiosk__service-card { min-height: 112px; }
    .queue-kiosk__service-card:disabled { opacity: .55; pointer-events: none; }
    .queue-kiosk__empty { grid-column: 1 / -1; padding: 42px 20px; text-align: center; border: 2px dashed #cbd8e5; border-radius: 20px; background: #f8fbfe; }
    .queue-kiosk__empty h3 { margin: 0; font-size: 21px; }
    .queue-kiosk__empty p { margin: 7px 0 0; color: var(--kiosk-muted); }

    .queue-kiosk__footer { min-height: 54px; justify-content: space-between; gap: 20px; padding: 0 clamp(24px,4vw,58px); color: #607186; border-top: 1px solid #d7e2ed; background: rgba(255,255,255,.92); font-size: 12px; }
    .queue-kiosk__connection { gap: 9px; color: #24684f; }
    .queue-kiosk__connection i { width: 9px; height: 9px; border-radius: 50%; background: var(--kiosk-success); box-shadow: 0 0 0 4px rgba(20,134,90,.12); }
    .queue-kiosk__connection.is-offline { color: var(--kiosk-danger); }
    .queue-kiosk__connection.is-offline i { background: var(--kiosk-danger); box-shadow: 0 0 0 4px rgba(198,56,72,.12); }

    .queue-kiosk__loading, .queue-kiosk__error, .queue-kiosk__wire-loading { position: fixed; inset: 0; z-index: 100; display: none; align-items: center; justify-content: center; flex-direction: column; gap: 8px; color: #fff; text-align: center; background: rgba(5,30,59,.94); backdrop-filter: blur(5px); }
    .queue-kiosk__loading.is-visible, .queue-kiosk__error:not([hidden]) { display: flex; }
    .queue-kiosk__printer-icon { width: 82px; height: 82px; display: grid; place-items: center; margin-bottom: 8px; color: var(--kiosk-blue); border-radius: 23px; background: #fff; animation: kiosk-printer-pulse .8s ease-in-out infinite alternate; }
    .queue-kiosk__printer-icon svg { width: 40px; }
    .queue-kiosk__loading strong, .queue-kiosk__error strong { font-size: 28px; }
    .queue-kiosk__loading > span:not(.queue-kiosk__printer-icon) { font-size: 17px; }
    .queue-kiosk__loading small { margin-top: 7px; color: #c7dbed; }
    .queue-kiosk__error > span { width: 68px; height: 68px; display: grid; place-items: center; margin-bottom: 8px; color: #9d2634; border-radius: 20px; background: #fff; font-size: 36px; font-weight: 900; }
    .queue-kiosk__error p { max-width: 520px; margin: 2px 20px 14px; color: #d9e6f1; }
    .queue-kiosk__error button { min-height: 50px; padding: 0 24px; color: #0b416f; border: 0; border-radius: 13px; background: #fff; font-weight: 800; cursor: pointer; }
    .queue-kiosk__wire-loading { z-index: 90; color: var(--kiosk-blue); background: rgba(238,244,250,.75); }
    .queue-kiosk__spinner { width: 44px; height: 44px; border: 5px solid rgba(18,100,173,.2); border-top-color: var(--kiosk-blue); border-radius: 50%; animation: kiosk-spin .7s linear infinite; }
    .queue-kiosk__print-frame { position: fixed; width: 1px; height: 1px; left: -10000px; top: -10000px; border: 0; opacity: 0; pointer-events: none; }
    @keyframes kiosk-spin { to { transform: rotate(360deg); } }
    @keyframes kiosk-printer-pulse { to { transform: translateY(-5px); } }

    @media (max-height: 800px) and (min-width: 900px) {
        .queue-kiosk__header { min-height: 84px; padding-top: 10px; padding-bottom: 10px; }
        .queue-kiosk__logo--city { width: 56px; height: 56px; }
        .queue-kiosk__logo--office { width: 62px; height: 62px; border-radius: 15px; }
        .queue-kiosk__brand-copy h1 { font-size: 26px; }
        .queue-kiosk__brand-copy p { font-size: 12px; }
        .queue-kiosk__clock strong { font-size: 24px; }
        .queue-kiosk__fullscreen { width: 45px; height: 45px; }
        .queue-kiosk__main { padding-top: 13px; padding-bottom: 15px; }
        .queue-kiosk__steps { margin-bottom: 12px; }
        .queue-kiosk__step { min-height: 42px; }
        .queue-kiosk__step-number { width: 28px; height: 28px; }
        .queue-kiosk__content { min-height: 0; padding: 19px 25px; border-radius: 22px; }
        .queue-kiosk__intro { margin-bottom: 15px; }
        .queue-kiosk__intro h2 { font-size: 29px; }
        .queue-kiosk__institution-card { min-height: 94px; padding: 13px 15px; }
        .queue-kiosk__service-card { min-height: 96px; }
        .queue-kiosk__institution-logo, .queue-kiosk__service-icon { width: 50px; height: 50px; }
        .queue-kiosk__pagination { margin-top: 12px; }
        .queue-kiosk__pagination button { width: 38px; height: 38px; }
        .queue-kiosk__footer { min-height: 42px; }
    }

    @media (max-width: 1000px) {
        .queue-kiosk__institution-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 680px) {
        .queue-kiosk__header { min-height: 84px; padding: 13px 16px; }
        .queue-kiosk__brand { gap: 10px; }
        .queue-kiosk__logo--city { width: 46px; height: 46px; }
        .queue-kiosk__logo--office { display: none; }
        .queue-kiosk__brand-copy > span, .queue-kiosk__brand-copy p, .queue-kiosk__clock span, .queue-kiosk__fullscreen { display: none; }
        .queue-kiosk__brand-copy h1 { font-size: 18px; }
        .queue-kiosk__clock { min-width: auto; }
        .queue-kiosk__clock strong { font-size: 21px; }
        .queue-kiosk__main { padding: 14px 10px 22px; }
        .queue-kiosk__steps { margin-bottom: 12px; }
        .queue-kiosk__step { min-height: 44px; gap: 6px; }
        .queue-kiosk__step small { display: none; }
        .queue-kiosk__content { padding: 20px 14px; border-radius: 20px; }
        .queue-kiosk__institution-grid, .queue-kiosk__service-grid { grid-template-columns: 1fr; }
        .queue-kiosk__institution-card, .queue-kiosk__service-card { min-height: 96px; }
        .queue-kiosk__toolbar { align-items: flex-start; }
        .queue-kiosk__selected-institution { max-width: 55%; }
        .queue-kiosk__selected-institution img { display: none; }
        .queue-kiosk__footer { min-height: 58px; justify-content: center; }
        .queue-kiosk__footer > span:first-child { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .queue-kiosk *, .queue-kiosk *::before, .queue-kiosk *::after { animation: none !important; transition: none !important; }
    }
</style>
