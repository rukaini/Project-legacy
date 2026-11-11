<style>
    :root {
        --pf-primary: #2563eb;
        --pf-primary-dark: #1d4ed8;
        --pf-primary-soft: #eff6ff;
        --pf-green: #059669;
        --pf-green-soft: #ecfdf5;
        --pf-amber: #d97706;
        --pf-amber-soft: #fffbeb;
        --pf-red: #dc2626;
        --pf-red-soft: #fef2f2;
        --pf-indigo: #4338ca;
        --pf-indigo-soft: #eef2ff;
        --pf-slate-900: #0f172a;
        --pf-slate-700: #334155;
        --pf-slate-600: #475569;
        --pf-slate-500: #64748b;
        --pf-slate-400: #94a3b8;
        --pf-border: #e2e8f0;
        --pf-border-strong: #cbd5e1;
        --pf-surface-muted: #f8fafc;
        --pf-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
        --pf-shadow-raised: 0 12px 24px rgba(15, 23, 42, 0.08);
        --pf-focus: 0 0 0 3px rgba(37, 99, 235, 0.16);
    }

    body { background: #f8fafc; }
    .profile-shell { min-width: 0; color: var(--pf-slate-900); }
    .profile-head { margin-bottom: 20px; }

    .profile-title {
        margin: 0 0 5px;
        color: var(--pf-slate-900);
        font-size: 26px;
        font-weight: 850;
        letter-spacing: -0.035em;
        line-height: 1.18;
    }

    .profile-subtitle {
        max-width: 760px;
        margin: 0;
        color: var(--pf-slate-500);
        font-size: 14px;
        font-weight: 600;
        line-height: 1.5;
    }

    .pf-card {
        overflow: hidden;
        border: 1px solid var(--pf-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: var(--pf-shadow);
    }

    .pf-profile-workspace { width: 100%; }

    .pf-tab-nav {
        display: flex;
        gap: 4px;
        padding: 8px 8px 0;
        overflow-x: auto;
        border-bottom: 1px solid var(--pf-border);
        scrollbar-width: thin;
    }

    .pf-tab-btn {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 7px;
        padding: 11px 16px;
        border: 0;
        border-bottom: 2.5px solid transparent;
        background: transparent;
        color: var(--pf-slate-500);
        cursor: pointer;
        font-family: inherit;
        font-size: 13px;
        font-weight: 850;
        white-space: nowrap;
        transition: color .15s ease, border-color .15s ease, background-color .15s ease;
    }

    .pf-tab-btn:hover { background: var(--pf-surface-muted); color: var(--pf-primary); }
    .pf-tab-btn.active { border-bottom-color: var(--pf-primary); color: var(--pf-primary); }

    .pf-tab-count {
        min-width: 19px;
        height: 19px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: var(--pf-primary-soft);
        color: var(--pf-primary);
        font-size: 10.5px;
        font-weight: 900;
    }

    .pf-tab-panel { display: none; padding: clamp(20px, 2vw, 28px); }
    .pf-tab-panel.show { display: block; }

    .pf-identity {
        padding: 17px 20px;
        border: 1px solid var(--pf-border);
        border-radius: 14px;
        background: var(--pf-surface-muted);
    }

    .pf-identity-name {
        margin: 0;
        color: var(--pf-slate-900);
        font-size: clamp(19px, 1.6vw, 23px);
        font-weight: 850;
        letter-spacing: -0.025em;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .pf-identity-email {
        margin: 3px 0 0;
        color: var(--pf-slate-500);
        font-size: 13px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .pf-profile-section { margin-top: clamp(20px, 2vw, 26px); }
    .pf-section-head { margin-bottom: 14px; }

    .pf-section-title,
    .pf-panel-title {
        margin: 0;
        color: var(--pf-slate-900);
        font-size: 15.5px;
        font-weight: 850;
        letter-spacing: -0.015em;
        line-height: 1.35;
    }

    .pf-section-subtitle,
    .pf-panel-sub {
        margin: 3px 0 0;
        color: var(--pf-slate-500);
        font-size: 12px;
        font-weight: 600;
        line-height: 1.5;
    }

    .pf-panel-sub { margin-bottom: 18px; }

    .pf-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
    }

    .pf-stat {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        border: 1px solid var(--pf-border);
        border-radius: 14px;
        background: var(--pf-surface-muted);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .pf-stat-icon {
        width: 40px;
        height: 40px;
        display: grid;
        flex: 0 0 auto;
        place-items: center;
        border-radius: 12px;
        background: var(--pf-primary-soft);
        color: var(--pf-primary);
        font-size: 18px;
    }

    .pf-stat.proposals .pf-stat-icon { background: var(--pf-indigo-soft); color: var(--pf-indigo); }
    .pf-stat.certificates .pf-stat-icon { background: var(--pf-green-soft); color: var(--pf-green); }
    .pf-stat.rating .pf-stat-icon { background: var(--pf-amber-soft); color: var(--pf-amber); }

    .pf-stat-value {
        color: var(--pf-slate-900);
        font-size: clamp(18px, 1.7vw, 23px);
        font-weight: 900;
        line-height: 1.1;
        overflow-wrap: anywhere;
    }

    .pf-stat-value.muted { color: var(--pf-slate-500); font-size: 15px; }
    .pf-stat-unit { color: var(--pf-slate-500); font-size: 11px; font-weight: 750; }
    .pf-stat-label { margin-top: 4px; color: var(--pf-slate-500); font-size: 10.5px; font-weight: 750; line-height: 1.35; }

    .pf-detail-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 11px;
        margin: 0;
    }

    .pf-detail-item {
        min-width: 0;
        grid-column: span 2;
        margin: 0;
        padding: 13px 14px;
        border: 1px solid var(--pf-border);
        border-radius: 12px;
        background: var(--pf-surface-muted);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .pf-detail-item--tail { grid-column: span 3; }

    .pf-detail-label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0 0 5px;
        color: var(--pf-slate-400);
        font-size: 9.5px;
        font-weight: 900;
        letter-spacing: 0.055em;
        line-height: 1.35;
        text-transform: uppercase;
    }

    .pf-detail-label i { color: var(--pf-slate-500); font-size: 12px; }
    .pf-detail-value { margin: 0; color: var(--pf-slate-700); font-size: 13px; font-weight: 800; line-height: 1.45; overflow-wrap: anywhere; }
    .pf-detail-value.muted { color: var(--pf-slate-400); font-style: italic; font-weight: 650; }

    .pf-cert-upload-form {
        margin-bottom: 20px;
        padding: 16px;
        border: 1px dashed #bfdbfe;
        border-radius: 14px;
        background: #f8fbff;
    }

    .pf-cert-upload-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .pf-cert-title-field { flex: 1 1 280px; min-width: min(100%, 220px); }

    .pf-cert-title-field input,
    .pf-doc-search input,
    .pf-doc-sort {
        min-height: 40px;
        border: 1px solid var(--pf-border);
        border-radius: 10px;
        background: #fff;
        color: var(--pf-slate-700);
        font-family: inherit;
        font-size: 13px;
        font-weight: 650;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .pf-cert-title-field input { width: 100%; padding: 9px 12px; }

    .pf-cert-file-btn {
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 14px;
        border: 1px solid var(--pf-border);
        border-radius: 10px;
        background: #fff;
        color: var(--pf-slate-700);
        cursor: pointer;
        font-size: 12.5px;
        font-weight: 800;
        transition: border-color .15s ease, color .15s ease, background-color .15s ease;
    }

    .pf-cert-file-btn:hover { border-color: #bfdbfe; background: var(--pf-primary-soft); color: var(--pf-primary); }

    .pf-cert-file-name {
        min-width: 130px;
        max-width: 240px;
        overflow: hidden;
        color: var(--pf-slate-500);
        font-size: 12px;
        font-weight: 650;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pf-cert-file-name.has-file { color: var(--pf-slate-700); font-weight: 800; }
    .pf-cert-upload-hint { margin-top: 8px; color: var(--pf-slate-500); font-size: 11px; font-weight: 600; line-height: 1.5; }
    .pf-error { margin-top: 5px; color: var(--pf-red); font-size: 11px; font-weight: 800; }

    .pf-btn {
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 18px;
        border: 0;
        border-radius: 10px;
        background: var(--pf-primary);
        color: #fff;
        cursor: pointer;
        font-family: inherit;
        font-size: 12.5px;
        font-weight: 850;
        transition: background-color .15s ease, box-shadow .15s ease;
    }

    .pf-btn:hover { background: var(--pf-primary-dark); box-shadow: 0 6px 14px rgba(37, 99, 235, 0.18); }

    .pf-cert-count-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
    .pf-cert-count-label { color: var(--pf-slate-500); font-size: 12px; font-weight: 800; }
    .pf-cert-count-label strong { color: var(--pf-slate-900); font-weight: 900; }
    .pf-cert-list { display: grid; gap: 10px; }

    .pf-cert-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 14px;
        border: 1px solid var(--pf-border);
        border-radius: 12px;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }

    .pf-cert-icon,
    .pf-doc-icon {
        display: grid;
        flex: 0 0 auto;
        place-items: center;
        background: var(--pf-primary-soft);
        color: var(--pf-primary);
    }

    .pf-cert-icon { width: 42px; height: 42px; border-radius: 11px; font-size: 19px; }
    .pf-cert-info { min-width: 0; flex: 1; }
    .pf-cert-title-text { color: var(--pf-slate-900); font-size: 13px; font-weight: 850; line-height: 1.35; overflow-wrap: anywhere; }
    .pf-cert-sub { display: flex; flex-wrap: wrap; gap: 4px 10px; margin-top: 3px; color: var(--pf-slate-500); font-size: 11px; font-weight: 700; }
    .pf-cert-sub span { display: inline-flex; align-items: center; gap: 4px; }
    .pf-cert-sub i { color: var(--pf-slate-400); }
    .pf-cert-actions { display: flex; flex: 0 0 auto; align-items: center; gap: 6px; }

    .pf-link-btn,
    .pf-doc-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid var(--pf-border);
        border-radius: 9px;
        background: #fff;
        color: var(--pf-slate-700);
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 850;
        text-decoration: none;
        transition: border-color .15s ease, color .15s ease, background-color .15s ease;
    }

    .pf-link-btn { min-height: 34px; padding: 7px 12px; }
    .pf-link-btn:hover,
    .pf-doc-link:hover { border-color: #bfdbfe; background: var(--pf-primary-soft); color: var(--pf-primary); }
    .pf-link-btn.danger { color: var(--pf-red); }
    .pf-link-btn.danger:hover { border-color: #fecaca; background: var(--pf-red-soft); color: var(--pf-red); }

    .pf-doc-controls { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
    .pf-doc-search { position: relative; min-width: 0; flex: 1; }
    .pf-doc-search i { position: absolute; top: 50%; left: 12px; color: var(--pf-slate-400); font-size: 14px; pointer-events: none; transform: translateY(-50%); }
    .pf-doc-search input { width: 100%; padding: 9px 12px 9px 34px; }
    .pf-doc-search input::-webkit-search-cancel-button { display: none; }
    .pf-doc-sort { flex: 0 0 190px; padding: 9px 12px; cursor: pointer; font-weight: 750; }

    .pf-doc-card {
        display: flex;
        gap: 14px;
        margin-bottom: 10px;
        padding: 15px;
        border: 1px solid var(--pf-border);
        border-radius: 13px;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }

    .pf-doc-card.is-unread { border-color: #bfdbfe; background: #fbfdff; }
    .pf-doc-icon { width: 44px; height: 44px; border-radius: 12px; font-size: 19px; }
    .pf-doc-main { min-width: 0; flex: 1; }
    .pf-doc-title { color: var(--pf-slate-900); font-size: 13.5px; font-weight: 900; line-height: 1.35; overflow-wrap: anywhere; }

    .pf-doc-unread {
        width: 8px;
        height: 8px;
        display: inline-block;
        margin-right: 6px;
        border-radius: 50%;
        background: var(--pf-primary);
        vertical-align: middle;
    }

    .pf-doc-desc { margin-top: 5px; color: var(--pf-slate-500); font-size: 12px; font-weight: 650; line-height: 1.5; }
    .pf-doc-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 11px; padding-top: 10px; border-top: 1px solid #f1f5f9; }
    .pf-doc-date { display: inline-flex; align-items: center; gap: 6px; color: var(--pf-slate-400); font-size: 11px; font-weight: 800; }
    .pf-doc-link { min-height: 32px; padding: 6px 12px; font-size: 11.5px; }

    .pf-pager { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 14px; padding-top: 14px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; }
    .pf-pager:empty { display: none; }
    .pf-pager-info { color: var(--pf-slate-500); font-size: 11.5px; font-weight: 750; }
    .pf-pager-btns { display: flex; align-items: center; gap: 8px; }

    .pf-pager-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--pf-border);
        border-radius: 9px;
        background: #fff;
        color: var(--pf-slate-600);
        cursor: pointer;
        font-size: 13px;
        transition: border-color .15s ease, color .15s ease, background-color .15s ease;
    }

    .pf-pager-btn:hover:not(:disabled) { border-color: #bfdbfe; background: var(--pf-primary-soft); color: var(--pf-primary); }
    .pf-pager-btn:disabled { opacity: .4; cursor: not-allowed; }
    .pf-pager-cur { min-width: 84px; color: var(--pf-slate-700); font-size: 12px; font-weight: 850; text-align: center; }

    .pf-empty,
    .pf-setup-notice { padding: 38px 20px; color: var(--pf-slate-500); text-align: center; }
    .pf-empty i,
    .pf-setup-notice > i { display: block; margin-bottom: 10px; color: var(--pf-slate-400); font-size: 32px; }
    .pf-empty-title,
    .pf-setup-title { margin-bottom: 4px; color: var(--pf-slate-700); font-size: 14px; font-weight: 900; }
    .pf-empty-sub,
    .pf-setup-sub { color: var(--pf-slate-500); font-size: 12.5px; font-weight: 650; line-height: 1.5; }
    .pf-doc-noresult { display: none; }

    @media (hover: hover) {
        .pf-stat:hover,
        .pf-detail-item:hover {
            border-color: #bfdbfe;
            box-shadow: var(--pf-shadow-raised);
            transform: translateY(-3px);
        }

        .pf-cert-card:hover,
        .pf-doc-card:hover {
            border-color: #bfdbfe;
            background: #fbfcfe;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.055);
        }
    }

    .pf-tab-btn:focus-visible,
    .pf-cert-file-btn:focus-visible,
    .pf-btn:focus-visible,
    .pf-link-btn:focus-visible,
    .pf-doc-link:focus-visible,
    .pf-pager-btn:focus-visible,
    .pf-cert-title-field input:focus-visible,
    .pf-doc-search input:focus-visible,
    .pf-doc-sort:focus-visible {
        outline: 3px solid rgba(37, 99, 235, 0.28);
        outline-offset: 2px;
    }

    .pf-cert-title-field input:focus,
    .pf-doc-search input:focus,
    .pf-doc-sort:focus { border-color: #93c5fd; box-shadow: var(--pf-focus); }

    @media (max-width: 991.98px) {
        .pf-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pf-detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pf-detail-item,
        .pf-detail-item--tail { grid-column: auto; }
        .pf-detail-item--tail:last-child { grid-column: 1 / -1; }
    }

    @media (max-width: 767.98px) {
        .profile-title { font-size: 21px; }
        .pf-tab-panel { padding: 18px; }
        .pf-cert-file-name { flex: 1 1 150px; max-width: none; }
        .pf-cert-card { align-items: flex-start; flex-wrap: wrap; }
        .pf-cert-actions { width: 100%; justify-content: flex-end; }
    }

    @media (max-width: 575.98px) {
        .pf-tab-btn { padding-inline: 12px; }
        .pf-stats,
        .pf-detail-grid { grid-template-columns: 1fr; }
        .pf-detail-item,
        .pf-detail-item--tail,
        .pf-detail-item--tail:last-child { grid-column: auto; }
        .pf-cert-title-field { flex-basis: 100%; }
        .pf-cert-file-btn,
        .pf-btn { flex: 1 1 auto; }
        .pf-doc-controls { align-items: stretch; flex-direction: column; }
        .pf-doc-sort { flex-basis: auto; width: 100%; }
        .pf-doc-card { gap: 10px; padding: 13px; }
        .pf-doc-foot { align-items: flex-start; flex-direction: column; }
        .pf-doc-link { width: 100%; }
        .pf-pager { align-items: flex-start; flex-direction: column; }
    }

    @media (prefers-reduced-motion: reduce) {
        .profile-shell * {
            scroll-behavior: auto !important;
            transition-duration: .01ms !important;
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
        }
    }
</style>
