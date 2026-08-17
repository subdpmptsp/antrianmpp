<style>
    :root {
        --kiosk-navy: #092650;
        --kiosk-blue: #1459c7;
        --kiosk-blue-dark: #0d429a;
        --kiosk-sky: #eaf3ff;
        --kiosk-cyan: #36c2e8;
        --kiosk-ink: #10233f;
        --kiosk-muted: #62738a;
        --kiosk-line: #dbe5f0;
        --kiosk-success: #12a36d;
        --kiosk-danger: #c83d4d;
    }

    html, body {
        min-height: 100%;
        margin: 0;
        background: #f3f7fb;
        color: var(--kiosk-ink);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    button, a { -webkit-tap-highlight-color: transparent; }
    button { font: inherit; }

    .queue-kiosk {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background:
            radial-gradient(circle at 12% 8%, rgba(54, 194, 232, .11), transparent 25rem),
            linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%);
    }

    .queue-kiosk__header {
        min-height: 112px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 28px;
        padding: 18px clamp(24px, 4vw, 64px);
        color: white;
        background: linear-gradient(120deg, #071f43 0%, #0b326c 58%, #0e4a91 100%);
        box-shadow: 0 12px 36px rgba(9, 38, 80, .2);
    }

    .queue-kiosk__brand, .queue-kiosk__header-tools, .queue-kiosk__brand-copy,
    .queue-kiosk__step, .queue-kiosk__toolbar, .queue-kiosk__selection-summary,
    .queue-kiosk__institution-card, .queue-kiosk__service-card,
    .queue-kiosk__footer, .queue-kiosk__connection, .queue-kiosk__dialog-confirm {
        display: flex;
        align-items: center;
    }

    .queue-kiosk__brand { gap: 20px; }
    .queue-kiosk__header-tools { gap: 18px; }

    .queue-kiosk__logo {
        display: grid;
        place-items: center;
        flex: 0 0 auto;
    }

    .queue-kiosk__logo--city { width: 74px; height: 74px; }
    .queue-kiosk__logo--office {
        width: 82px;
        height: 82px;
        padding: 8px;
        border-radius: 20px;
        background: rgba(255, 255, 255, .96);
    }
    .queue-kiosk__logo img { max-width: 100%; max-height: 100%; object-fit: contain; }

    .queue-kiosk__brand-copy { align-items: flex-start; flex-direction: column; }
    .queue-kiosk__eyebrow {
        margin-bottom: 4px;
        color: #9fe7fb;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }
    .queue-kiosk__brand-copy h1 { margin: 0; font-size: clamp(24px, 2.2vw, 36px); line-height: 1.08; font-weight: 850; }
    .queue-kiosk__brand-copy p { margin: 7px 0 0; color: #d6e7fb; font-size: 15px; }

    .queue-kiosk__clock { min-width: 116px; text-align: right; }
    .queue-kiosk__clock strong { display: block; font-size: 30px; line-height: 1; font-variant-numeric: tabular-nums; }
    .queue-kiosk__clock span { display: block; margin-top: 7px; color: #c9ddf4; font-size: 12px; }

    .queue-kiosk__icon-button {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        color: white;
        border: 1px solid rgba(255, 255, 255, .28);
        border-radius: 16px;
        background: rgba(255, 255, 255, .1);
        cursor: pointer;
    }
    .queue-kiosk__icon-button svg { width: 25px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    .queue-kiosk__main {
        width: min(1500px, 100%);
        flex: 1;
        margin: 0 auto;
        padding: 28px clamp(20px, 4vw, 64px) 40px;
        box-sizing: border-box;
    }

    .queue-kiosk__alert {
        max-width: 900px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0 auto 20px;
        padding: 15px 18px;
        color: #842936;
        border: 1px solid #f2b8c0;
        border-radius: 16px;
        background: #fff1f3;
        font-weight: 750;
    }
    .queue-kiosk__alert svg { width: 24px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    .queue-kiosk__steps {
        max-width: 850px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        margin: 0 auto 26px;
        padding: 9px;
        border: 1px solid var(--kiosk-line);
        border-radius: 22px;
        background: rgba(255, 255, 255, .86);
        box-shadow: 0 10px 30px rgba(37, 63, 91, .07);
    }

    .queue-kiosk__step {
        position: relative;
        justify-content: center;
        gap: 11px;
        min-height: 58px;
        color: #8995a5;
        border-radius: 15px;
    }
    .queue-kiosk__step:not(:last-child)::after {
        content: "";
        position: absolute;
        right: -1px;
        width: 1px;
        height: 28px;
        background: var(--kiosk-line);
    }
    .queue-kiosk__step.is-active { color: var(--kiosk-blue-dark); background: var(--kiosk-sky); }
    .queue-kiosk__step.is-complete { color: var(--kiosk-success); }
    .queue-kiosk__step-number {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border: 2px solid currentColor;
        border-radius: 50%;
        font-weight: 850;
    }
    .queue-kiosk__step-number svg { width: 18px; fill: none; stroke: currentColor; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .queue-kiosk__step strong, .queue-kiosk__step small { display: block; }
    .queue-kiosk__step strong { font-size: 14px; }
    .queue-kiosk__step small { margin-top: 2px; font-size: 11px; color: var(--kiosk-muted); }

    .queue-kiosk__content {
        padding: clamp(24px, 3.5vw, 48px);
        border: 1px solid rgba(205, 219, 233, .9);
        border-radius: 30px;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 22px 60px rgba(28, 54, 83, .09);
    }

    .queue-kiosk__intro { max-width: 760px; margin: 0 auto 32px; text-align: center; }
    .queue-kiosk__section-kicker {
        display: inline-flex;
        color: var(--kiosk-blue);
        font-size: 12px;
        font-weight: 850;
        letter-spacing: .12em;
        text-transform: uppercase;
    }
    .queue-kiosk__intro h2, .queue-kiosk__dialog h2 {
        margin: 8px 0 8px;
        color: var(--kiosk-ink);
        font-size: clamp(28px, 3vw, 44px);
        line-height: 1.1;
        font-weight: 850;
        letter-spacing: -.025em;
    }
    .queue-kiosk__intro p { margin: 0; color: var(--kiosk-muted); font-size: 17px; }

    .queue-kiosk__zone-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 18px;
    }

    .queue-kiosk__zone-card {
        position: relative;
        min-height: 300px;
        display: flex;
        flex-direction: column;
        padding: 24px 22px 20px;
        color: var(--kiosk-ink);
        text-align: left;
        text-decoration: none;
        border: 1px solid #d7e3ef;
        border-radius: 24px;
        background: linear-gradient(150deg, #ffffff 0%, #f2f7fd 100%);
        box-shadow: 0 10px 25px rgba(31, 61, 94, .08);
        cursor: pointer;
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .queue-kiosk__zone-card::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 6px;
        background: linear-gradient(180deg, var(--kiosk-cyan), var(--kiosk-blue));
    }
    .queue-kiosk__zone-card:hover, .queue-kiosk__zone-card:focus-visible,
    .queue-kiosk__institution-card:hover, .queue-kiosk__institution-card:focus-visible,
    .queue-kiosk__service-card:hover, .queue-kiosk__service-card:focus-visible {
        transform: translateY(-4px);
        border-color: #8db7ed;
        box-shadow: 0 18px 36px rgba(23, 76, 139, .15);
        outline: none;
    }
    .queue-kiosk__zone-card:active, .queue-kiosk__institution-card:active, .queue-kiosk__service-card:active { transform: scale(.985); }

    .queue-kiosk__zone-number {
        align-self: flex-start;
        padding: 7px 10px;
        color: var(--kiosk-blue-dark);
        border-radius: 10px;
        background: var(--kiosk-sky);
        font-size: 13px;
        font-weight: 900;
        letter-spacing: .08em;
    }
    .queue-kiosk__zone-heading { display: block; margin-top: 18px; }
    .queue-kiosk__zone-heading strong { display: block; color: var(--kiosk-navy); font-size: 22px; font-weight: 900; }
    .queue-kiosk__zone-heading small { display: block; margin-top: 5px; color: var(--kiosk-muted); font-size: 12px; }
    .queue-kiosk__zone-list { display: flex; flex: 1; flex-direction: column; gap: 7px; margin-top: 18px; color: #42556c; font-size: 12px; line-height: 1.35; }
    .queue-kiosk__zone-list span { display: -webkit-box; overflow: hidden; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .queue-kiosk__zone-list span::before { content: "•"; margin-right: 7px; color: var(--kiosk-cyan); font-weight: 900; }
    .queue-kiosk__zone-list em { color: var(--kiosk-blue); font-style: normal; font-weight: 800; }
    .queue-kiosk__card-action { display: flex; align-items: center; justify-content: space-between; margin-top: 18px; padding-top: 14px; color: var(--kiosk-blue-dark); border-top: 1px solid var(--kiosk-line); font-size: 13px; font-weight: 850; }
    .queue-kiosk__card-action svg, .queue-kiosk__institution-arrow svg, .queue-kiosk__selection-summary svg,
    .queue-kiosk__back svg, .queue-kiosk__dialog-confirm svg {
        width: 20px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.3;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .queue-kiosk__toolbar { justify-content: space-between; gap: 18px; margin-bottom: 24px; }
    .queue-kiosk__back {
        min-height: 52px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 18px;
        color: var(--kiosk-blue-dark);
        text-decoration: none;
        border: 1px solid #b9d0ea;
        border-radius: 14px;
        background: white;
        font-weight: 800;
        cursor: pointer;
    }
    .queue-kiosk__selection-pill {
        padding: 10px 16px;
        color: var(--kiosk-blue-dark);
        border-radius: 999px;
        background: var(--kiosk-sky);
        font-size: 13px;
        font-weight: 900;
        letter-spacing: .06em;
    }
    .queue-kiosk__selection-summary { justify-content: flex-end; gap: 8px; color: var(--kiosk-muted); font-size: 13px; }
    .queue-kiosk__selection-summary span { color: var(--kiosk-blue); font-weight: 850; }
    .queue-kiosk__selection-summary strong { max-width: 520px; color: var(--kiosk-ink); font-size: 15px; text-align: right; }

    .queue-kiosk__institution-grid, .queue-kiosk__service-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .queue-kiosk__institution-card, .queue-kiosk__service-card {
        min-height: 112px;
        gap: 16px;
        padding: 18px 20px;
        color: var(--kiosk-ink);
        text-align: left;
        text-decoration: none;
        border: 1px solid #d7e3ef;
        border-radius: 20px;
        background: white;
        box-shadow: 0 8px 22px rgba(31, 61, 94, .07);
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .queue-kiosk__institution-icon, .queue-kiosk__service-icon {
        width: 58px;
        height: 58px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        color: var(--kiosk-blue);
        border-radius: 17px;
        background: var(--kiosk-sky);
    }
    .queue-kiosk__institution-icon svg, .queue-kiosk__service-icon svg, .queue-kiosk__dialog-icon svg {
        width: 30px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .queue-kiosk__institution-name { flex: 1; font-size: 16px; line-height: 1.35; font-weight: 800; }
    .queue-kiosk__institution-arrow { color: #8ba0b7; }

    .queue-kiosk__service-form { display: none; }
    .queue-kiosk__service-card { position: relative; min-height: 128px; }
    .queue-kiosk__service-copy { flex: 1; min-width: 0; padding-right: 74px; }
    .queue-kiosk__service-copy strong, .queue-kiosk__service-copy small { display: block; }
    .queue-kiosk__service-copy strong { color: var(--kiosk-ink); font-size: 16px; line-height: 1.35; font-weight: 850; }
    .queue-kiosk__service-copy small { margin-top: 6px; color: var(--kiosk-muted); font-size: 12px; }
    .queue-kiosk__service-action {
        position: absolute;
        top: 12px;
        right: 12px;
        padding: 5px 8px;
        color: #087651;
        border-radius: 8px;
        background: #e6f8f1;
        font-size: 10px;
        font-weight: 850;
        text-transform: uppercase;
    }

    .queue-kiosk__empty { grid-column: 1 / -1; padding: 50px 24px; text-align: center; border: 2px dashed #cbd9e7; border-radius: 22px; background: #f8fbfe; }
    .queue-kiosk__empty h3 { margin: 0; color: var(--kiosk-ink); font-size: 22px; }
    .queue-kiosk__empty p { margin: 8px 0 0; color: var(--kiosk-muted); }

    .queue-kiosk__footer {
        min-height: 58px;
        justify-content: space-between;
        gap: 20px;
        padding: 0 clamp(24px, 4vw, 64px);
        color: #5e7085;
        border-top: 1px solid #d9e4ef;
        background: rgba(255, 255, 255, .9);
        font-size: 12px;
    }
    .queue-kiosk__connection { gap: 9px; color: #2f4965; font-weight: 750; }
    .queue-kiosk__connection i { width: 10px; height: 10px; border-radius: 50%; background: var(--kiosk-success); box-shadow: 0 0 0 5px rgba(18, 163, 109, .12); }
    .queue-kiosk__connection.is-offline i { background: var(--kiosk-danger); box-shadow: 0 0 0 5px rgba(200, 61, 77, .12); }

    .queue-kiosk__dialog {
        width: min(560px, calc(100vw - 32px));
        padding: 34px;
        color: var(--kiosk-ink);
        border: 0;
        border-radius: 28px;
        box-shadow: 0 28px 90px rgba(4, 24, 49, .35);
        box-sizing: border-box;
    }
    .queue-kiosk__dialog::backdrop { background: rgba(4, 18, 38, .68); backdrop-filter: blur(5px); }
    .queue-kiosk__dialog-icon { width: 68px; height: 68px; display: grid; place-items: center; margin-bottom: 20px; color: var(--kiosk-blue); border-radius: 20px; background: var(--kiosk-sky); }
    .queue-kiosk__dialog h2 { font-size: 32px; }
    .queue-kiosk__dialog dl { margin: 24px 0; overflow: hidden; border: 1px solid var(--kiosk-line); border-radius: 18px; }
    .queue-kiosk__dialog dl div { display: grid; grid-template-columns: 120px 1fr; gap: 16px; padding: 15px 17px; }
    .queue-kiosk__dialog dl div + div { border-top: 1px solid var(--kiosk-line); }
    .queue-kiosk__dialog dt { color: var(--kiosk-muted); font-size: 13px; }
    .queue-kiosk__dialog dd { margin: 0; color: var(--kiosk-ink); font-size: 14px; font-weight: 800; }
    .queue-kiosk__dialog-actions { display: grid; grid-template-columns: 1fr 1.25fr; gap: 12px; }
    .queue-kiosk__dialog-actions button { min-height: 58px; border-radius: 15px; font-weight: 850; cursor: pointer; }
    .queue-kiosk__dialog-cancel { color: #53667d; border: 1px solid #cbd9e7; background: white; }
    .queue-kiosk__dialog-confirm { justify-content: center; gap: 8px; color: white; border: 1px solid var(--kiosk-blue); background: linear-gradient(135deg, var(--kiosk-blue), var(--kiosk-blue-dark)); }

    .queue-kiosk__loading, .queue-kiosk__wire-loading {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 12px;
        color: white;
        background: rgba(4, 24, 49, .82);
        backdrop-filter: blur(6px);
    }
    .queue-kiosk__loading.is-visible { display: flex; }
    .queue-kiosk__loading strong { font-size: 24px; }
    .queue-kiosk__loading small { color: #c9dcef; }
    .queue-kiosk__wire-loading { background: rgba(238, 244, 250, .64); }
    .queue-kiosk__spinner { width: 44px; height: 44px; border: 5px solid rgba(255, 255, 255, .28); border-top-color: white; border-radius: 50%; animation: kiosk-spin .75s linear infinite; }
    .queue-kiosk__wire-loading .queue-kiosk__spinner { border-color: rgba(20, 89, 199, .2); border-top-color: var(--kiosk-blue); }
    @keyframes kiosk-spin { to { transform: rotate(360deg); } }

    @media (max-height: 800px) and (min-width: 900px) {
        .queue-kiosk__header { min-height: 84px; padding-top: 10px; padding-bottom: 10px; }
        .queue-kiosk__logo--city { width: 58px; height: 58px; }
        .queue-kiosk__logo--office { width: 64px; height: 64px; border-radius: 16px; }
        .queue-kiosk__brand-copy h1 { font-size: 27px; }
        .queue-kiosk__brand-copy p { margin-top: 4px; font-size: 13px; }
        .queue-kiosk__eyebrow { font-size: 11px; }
        .queue-kiosk__clock strong { font-size: 25px; }
        .queue-kiosk__icon-button { width: 48px; height: 48px; }
        .queue-kiosk__main { padding-top: 15px; padding-bottom: 18px; }
        .queue-kiosk__steps { margin-bottom: 14px; }
        .queue-kiosk__step { min-height: 46px; }
        .queue-kiosk__step-number { width: 29px; height: 29px; font-size: 12px; }
        .queue-kiosk__content { padding: 22px 28px; border-radius: 24px; }
        .queue-kiosk__intro { margin-bottom: 18px; }
        .queue-kiosk__intro h2 { margin-top: 5px; font-size: 31px; }
        .queue-kiosk__intro p { font-size: 14px; }
        .queue-kiosk__zone-card { min-height: 238px; padding: 17px 18px 15px; }
        .queue-kiosk__zone-heading { margin-top: 12px; }
        .queue-kiosk__zone-heading strong { font-size: 19px; }
        .queue-kiosk__zone-list { gap: 4px; margin-top: 11px; font-size: 11px; }
        .queue-kiosk__card-action { margin-top: 10px; padding-top: 9px; }
        .queue-kiosk__institution-card { min-height: 90px; }
        .queue-kiosk__service-card { min-height: 104px; }
        .queue-kiosk__institution-icon, .queue-kiosk__service-icon { width: 50px; height: 50px; }
        .queue-kiosk__toolbar { margin-bottom: 14px; }
        .queue-kiosk__footer { min-height: 44px; }
    }

    @media (max-width: 1200px) {
        .queue-kiosk__zone-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .queue-kiosk__institution-grid, .queue-kiosk__service-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 760px) {
        .queue-kiosk__header { min-height: 88px; padding: 14px 18px; }
        .queue-kiosk__logo--city { width: 52px; height: 52px; }
        .queue-kiosk__logo--office { width: 58px; height: 58px; border-radius: 15px; }
        .queue-kiosk__brand { gap: 12px; }
        .queue-kiosk__brand-copy h1 { font-size: 19px; }
        .queue-kiosk__brand-copy p, .queue-kiosk__eyebrow, .queue-kiosk__clock span { display: none; }
        .queue-kiosk__clock { min-width: 72px; }
        .queue-kiosk__clock strong { font-size: 22px; }
        .queue-kiosk__icon-button { display: none; }
        .queue-kiosk__header-tools { gap: 10px; }
        .queue-kiosk__main { padding: 18px 12px 28px; }
        .queue-kiosk__steps { margin-bottom: 16px; }
        .queue-kiosk__step { min-height: 50px; gap: 7px; }
        .queue-kiosk__step-number { width: 29px; height: 29px; font-size: 12px; }
        .queue-kiosk__step small { display: none; }
        .queue-kiosk__content { padding: 24px 16px; border-radius: 22px; }
        .queue-kiosk__intro { margin-bottom: 24px; }
        .queue-kiosk__intro h2 { font-size: 29px; }
        .queue-kiosk__intro p { font-size: 14px; }
        .queue-kiosk__zone-grid, .queue-kiosk__institution-grid, .queue-kiosk__service-grid { grid-template-columns: 1fr; }
        .queue-kiosk__zone-card { min-height: 225px; }
        .queue-kiosk__toolbar { align-items: flex-start; }
        .queue-kiosk__selection-summary { max-width: 65%; flex-wrap: wrap; }
        .queue-kiosk__selection-summary svg { display: none; }
        .queue-kiosk__selection-summary strong { width: 100%; }
        .queue-kiosk__footer { min-height: 68px; justify-content: center; text-align: center; }
        .queue-kiosk__footer > span:last-child { display: none; }
        .queue-kiosk__dialog { padding: 26px 20px; }
        .queue-kiosk__dialog-actions { grid-template-columns: 1fr; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; animation-duration: .01ms !important; }
    }
</style>
