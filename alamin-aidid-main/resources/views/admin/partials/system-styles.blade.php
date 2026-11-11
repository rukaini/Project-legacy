<style>
    :root {
        --adm-primary: #2563eb;
        --adm-primary-dark: #1d4ed8;
        --adm-primary-soft: #eff6ff;
        --adm-indigo: #4338ca;
        --adm-indigo-soft: #eef2ff;
        --adm-green: #059669;
        --adm-green-soft: #ecfdf5;
        --adm-amber: #d97706;
        --adm-amber-soft: #fffbeb;
        --adm-red: #dc2626;
        --adm-red-soft: #fef2f2;
        --adm-cyan: #0e7490;
        --adm-cyan-soft: #ecfeff;
        --adm-slate-900: #0f172a;
        --adm-slate-700: #334155;
        --adm-slate-600: #475569;
        --adm-slate-500: #64748b;
        --adm-slate-400: #94a3b8;
        --adm-border: #e2e8f0;
        --adm-border-strong: #cbd5e1;
        --adm-surface: #ffffff;
        --adm-surface-muted: #f8fafc;
        --adm-radius-sm: 10px;
        --adm-radius: 14px;
        --adm-radius-lg: 16px;
        --adm-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
        --adm-shadow-raised: 0 12px 28px rgba(15, 23, 42, 0.08);
        --adm-focus: 0 0 0 3px rgba(37, 99, 235, 0.16);
    }

    html { background: #f6f8fb; }

    body {
        min-width: 320px;
        background: #f6f8fb !important;
        color: var(--adm-slate-900);
        font-family: "Inter", sans-serif !important;
        -webkit-font-smoothing: antialiased;
    }

    main.adm-wrap { color: var(--adm-slate-900); }

    main.adm-wrap .adm-head { margin-bottom: clamp(18px, 1.6vw, 24px) !important; }

    main.adm-wrap .adm-head-row {
        display: flex !important;
        align-items: center !important;
        gap: 14px !important;
    }

    main.adm-wrap .adm-head-row > :last-child { min-width: 0; }

    main.adm-wrap .adm-head-icon {
        width: 52px !important;
        height: 52px !important;
        border: 1px solid #c7d2fe !important;
        border-radius: 15px !important;
        background: linear-gradient(135deg, #eff6ff, #e0e7ff) !important;
        color: var(--adm-primary) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 0 0 auto !important;
        font-size: 23px !important;
    }

    main.adm-wrap .adm-title {
        margin: 0 !important;
        color: var(--adm-slate-900) !important;
        font-size: clamp(21px, 1.45vw, 26px) !important;
        font-weight: 800 !important;
        letter-spacing: -0.03em !important;
        line-height: 1.2 !important;
        overflow-wrap: anywhere;
    }

    main.adm-wrap .adm-subtitle {
        max-width: 780px;
        margin: 3px 0 0 !important;
        color: var(--adm-slate-500) !important;
        font-size: 13.5px !important;
        font-weight: 500 !important;
        line-height: 1.5 !important;
        overflow-wrap: anywhere;
    }

    /* Shared cards and panels */
    main.adm-wrap :is(.adm-panel, .adm-card, .adm-week-card, .adm-sub-panel, .adm-dhead, .adm-side, .adm-filter-card, .adm-table-card, .adm-table-wrap, .adm-prop-card, .pf-card) {
        border-color: var(--adm-border);
        border-radius: var(--adm-radius-lg);
        box-shadow: var(--adm-shadow);
    }

    main.adm-wrap :is(.adm-kpi, .adm-stat) {
        border-color: var(--adm-border);
        border-radius: var(--adm-radius);
        box-shadow: var(--adm-shadow);
    }

    main.adm-wrap :is(.adm-stats, .pf-stats) {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 210px), 1fr));
        gap: clamp(12px, 1vw, 18px);
    }

    main.adm-wrap :is(.adm-panel, .adm-week-card, .adm-kpi, .adm-stat) {
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }

    @media (hover: hover) {
        main.adm-wrap :is(.adm-panel, .adm-week-card, .adm-kpi, .adm-stat):hover {
            box-shadow: var(--adm-shadow-raised);
        }
    }

    /* Shared controls and button hierarchy */
    main.adm-wrap :is(input[type="text"], input[type="search"], input[type="email"], input[type="password"], input[type="date"], select, textarea) {
        min-height: 40px;
        border-color: var(--adm-border);
        border-radius: var(--adm-radius-sm);
        background-color: var(--adm-surface);
        color: var(--adm-slate-700);
        font-family: inherit;
    }

    main.adm-wrap :is(input, select, textarea):focus,
    main.adm-wrap :is(input, select, textarea):focus-visible {
        border-color: #93c5fd !important;
        box-shadow: var(--adm-focus) !important;
        outline: none !important;
    }

    main.adm-wrap :is(button, a, input, select, textarea):focus-visible {
        outline: 3px solid rgba(37, 99, 235, 0.32);
        outline-offset: 2px;
    }

    main.adm-wrap :is(.adm-btn, .adm-footer-btn, .adm-act-approve, .adm-review-btn) {
        min-height: 40px;
        border-radius: var(--adm-radius-sm);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        font-weight: 800;
    }

    main.adm-wrap :is(.adm-btn-ghost, .adm-footer-btn-outline, .adm-tool-btn, .adm-sort, .adm-reset, .adm-page-btn, .adm-icon-btn, .adm-view) {
        border-color: var(--adm-border);
        border-radius: var(--adm-radius-sm);
        background: var(--adm-surface);
        color: var(--adm-slate-600);
    }

    main.adm-wrap :is(.adm-btn-ghost, .adm-footer-btn-outline, .adm-tool-btn, .adm-sort, .adm-reset, .adm-page-btn, .adm-icon-btn, .adm-view):hover:not(:disabled) {
        border-color: #bfdbfe;
        background: var(--adm-primary-soft);
        color: var(--adm-primary-dark);
    }

    main.adm-wrap :is(.adm-filters, .adm-tools) {
        gap: 12px;
    }

    main.adm-wrap .adm-filters {
        border-color: var(--adm-border);
        border-radius: var(--adm-radius);
        background: var(--adm-surface);
        box-shadow: var(--adm-shadow);
        padding: 12px 14px;
        margin-bottom: 16px;
    }

    main.adm-wrap .adm-search {
        flex: 1 1 260px;
        min-width: min(100%, 220px);
        max-width: 560px;
    }

    main.adm-wrap :is(.adm-pill, .adm-badge, .adm-chip, .adm-wait, .adm-srow-tag, .adm-mode) {
        line-height: 1.25;
        letter-spacing: 0.035em;
    }

    main.adm-wrap :is(.adm-act-reject, .adm-btn-reject) {
        border-color: #fecaca;
        color: var(--adm-red);
    }

    main.adm-wrap :is(button, .adm-btn, .adm-page-btn):disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* Tables, lists and empty states */
    main.adm-wrap :is(.adm-table-wrap, .adm-table-scroll, .table-responsive) {
        max-width: 100%;
        overflow-x: auto;
        overscroll-behavior-inline: contain;
        -webkit-overflow-scrolling: touch;
    }

    main.adm-wrap table { margin-bottom: 0; }

    main.adm-wrap table thead th {
        background: var(--adm-surface-muted);
        color: var(--adm-slate-500);
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: 0.055em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    main.adm-wrap table tbody tr { transition: background-color 0.12s ease; }
    main.adm-wrap table tbody tr:hover { background: #f8fafc; }

    main.adm-wrap .adm-empty {
        min-height: 150px;
        padding: clamp(30px, 4vw, 52px) 22px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--adm-slate-500);
        text-align: center;
    }

    main.adm-wrap :is(.adm-empty-cell, .pf-empty) {
        min-height: 150px;
        padding: clamp(30px, 4vw, 52px) 22px;
        color: var(--adm-slate-500);
        text-align: center;
    }

    main.adm-wrap .adm-empty > i {
        color: var(--adm-slate-400);
        font-size: 32px;
    }

    main.adm-wrap .adm-pager {
        min-height: 44px;
        flex-wrap: wrap;
    }

    main.adm-wrap :is(.pf-btn, .pf-pic-save, .pf-pic-cancel, .pf-pic-remove) {
        min-height: 40px;
        border-radius: var(--adm-radius-sm);
        font-family: inherit;
        font-weight: 800;
    }

    /* Dashboard and dense workspaces grow with available desktop space. */
    main.adm-wrap--wide > .adm-kpis {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 210px), 1fr));
        gap: clamp(12px, 1.1vw, 18px);
    }

    main.adm-wrap--wide .adm-shell {
        gap: clamp(16px, 1.4vw, 24px);
        height: clamp(620px, calc(100vh - 178px), 920px);
    }

    main.adm-wrap--wide .adm-side { width: clamp(315px, 22vw, 390px); }

    main.adm-wrap--wide :is(.adm-filterbar, .adm-toolbar, .adm-controls, .adm-filter-row) {
        gap: 12px;
    }

    .modal-content {
        border: 1px solid var(--adm-border);
        border-radius: var(--adm-radius-lg);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
    }

    .modal-header,
    .modal-footer { border-color: #f1f5f9; }

    @media (max-width: 991.98px) {
        main.adm-wrap--wide .adm-shell {
            height: auto;
            min-height: 0;
        }

        main.adm-wrap--wide .adm-side { width: auto; }
    }

    @media (max-width: 767.98px) {
        main.adm-wrap .adm-head-row { align-items: flex-start !important; }

        main.adm-wrap .adm-head-icon {
            width: 46px !important;
            height: 46px !important;
            border-radius: 13px !important;
            font-size: 20px !important;
        }

        main.adm-wrap .adm-title { font-size: 21px !important; }

        main.adm-wrap :is(.adm-actions, .adm-toolbar, .adm-controls, .adm-filter-row) {
            width: 100%;
            flex-wrap: wrap;
        }

        main.adm-wrap .adm-search { max-width: none; }

        main.adm-wrap .adm-select {
            max-width: none;
            flex: 1 1 180px;
        }

        main.adm-wrap :is(.adm-panel-head, .adm-week-head, .adm-sub-head) {
            align-items: flex-start;
            flex-wrap: wrap;
        }
    }

    @media (max-width: 575.98px) {
        main.adm-wrap--wide > .adm-kpis { grid-template-columns: 1fr; }

        main.adm-wrap :is(.adm-btn, .adm-btn-ghost, .adm-act-approve, .adm-act-reject) {
            justify-content: center;
        }

    }

    @media (prefers-reduced-motion: reduce) {
        main.adm-wrap * {
            scroll-behavior: auto !important;
            transition-duration: 0.01ms !important;
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
        }
    }
</style>
