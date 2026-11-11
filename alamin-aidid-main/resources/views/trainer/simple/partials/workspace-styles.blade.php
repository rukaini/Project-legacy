<style>
    :root {
        --sw-primary: #2563eb;
        --sw-primary-dark: #1d4ed8;
        --sw-primary-soft: #eff6ff;
        --sw-green: #059669;
        --sw-green-soft: #ecfdf5;
        --sw-amber: #d97706;
        --sw-amber-soft: #fffbeb;
        --sw-red: #dc2626;
        --sw-red-soft: #fef2f2;
        --sw-pink: #db2777;
        --sw-slate-900: #0f172a;
        --sw-slate-700: #334155;
        --sw-slate-600: #475569;
        --sw-slate-500: #64748b;
        --sw-slate-400: #94a3b8;
        --sw-slate-300: #cbd5e1;
        --sw-slate-100: #f1f5f9;
        --sw-slate-50: #f8fafc;
        --sw-border: #e2e8f0;
        --sw-shadow: 0 6px 20px rgba(15, 23, 42, .05);
    }

    body { background: #f8fafc; }
    .sw-shell { color: var(--sw-slate-900); }
    .sw-header { background: #fff; border-bottom: 1px solid var(--sw-border); }
    .sw-page { padding-top: 24px; padding-bottom: 46px; }
    .sw-breadcrumb { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 15px; color: var(--sw-slate-400); font-size: 11px; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
    .sw-breadcrumb a { color: var(--sw-slate-500); text-decoration: none; }
    .sw-breadcrumb a:hover { color: var(--sw-primary); }
    .sw-breadcrumb .sw-bc-current { color: var(--sw-slate-700); }
    .sw-breadcrumb :is(a, .sw-bc-current) { min-width: 0; overflow-wrap: anywhere; }
    .sw-title-row { display: flex; align-items: flex-start; gap: 16px; }
    .sw-title-copy { flex: 1; min-width: 0; }
    .sw-icon { display: flex; flex: 0 0 auto; align-items: center; justify-content: center; width: 58px; height: 58px; border: 1px solid #bfdbfe; border-radius: 16px; background-color: var(--sw-primary-soft); background-image: radial-gradient(#93c5fd 1px, transparent 1px); background-size: 10px 10px; color: var(--sw-primary); font-size: 23px; }
    .sw-title { margin: 0 0 9px; overflow-wrap: anywhere; color: var(--sw-slate-900); font-size: 26px; font-weight: 850; letter-spacing: -.035em; line-height: 1.18; }
    .sw-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .sw-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border: 1px solid var(--sw-border); border-radius: 999px; background: var(--sw-slate-100); color: var(--sw-slate-600); font-size: 12px; font-weight: 750; }
    .sw-pill i { color: var(--sw-slate-500); }
    .sw-pill.mode { border-color: #c7d2fe; background: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .sw-pill.mode i { color: #4338ca; }
    .sw-tabs { display: flex; max-width: 100%; gap: 24px; margin-top: 22px; overflow-x: auto; overscroll-behavior-inline: contain; scrollbar-width: thin; }
    .sw-tabs a { display: inline-flex; flex: 0 0 auto; align-items: center; gap: 6px; padding-bottom: 13px; border-bottom: 2.5px solid transparent; color: var(--sw-slate-500); font-size: 14px; font-weight: 650; text-decoration: none; white-space: nowrap; transition: .15s ease; }
    .sw-tabs a:hover { color: var(--sw-slate-900); }
    .sw-tabs a.active { border-bottom-color: var(--sw-primary); color: var(--sw-primary); font-weight: 800; }

    .sw-panel { margin-bottom: 22px; overflow: hidden; border: 1px solid var(--sw-border); border-radius: 18px; background: #fff; box-shadow: var(--sw-shadow); }
    .sw-panel-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--sw-slate-100); }
    .sw-panel-title { margin: 0; color: var(--sw-slate-900); font-size: 15px; font-weight: 850; }
    .sw-panel-sub { margin: 3px 0 0; color: var(--sw-slate-500); font-size: 12px; font-weight: 600; }
    .sw-panel-count { padding: 4px 10px; border: 1px solid #bfdbfe; border-radius: 999px; background: var(--sw-primary-soft); color: var(--sw-primary); font-size: 11px; font-weight: 850; white-space: nowrap; }

    .sw-report-toolbar { display: grid; grid-template-columns: minmax(220px, 1.6fr) repeat(2, minmax(150px, .8fr)); align-items: end; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--sw-slate-100); background: var(--sw-slate-50); }
    .sw-filter-field { display: flex; flex-direction: column; min-width: 0; }
    .sw-filter-label { margin-bottom: 5px; color: var(--sw-slate-500); font-size: 10px; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
    .sw-search { position: relative; }
    .sw-search > i { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); color: var(--sw-slate-400); font-size: 14px; pointer-events: none; }
    .sw-search input, .sw-select { width: 100%; min-height: 42px; padding: 9px 12px; border: 1px solid var(--sw-border); border-radius: 10px; outline: none; background: #fff; color: var(--sw-slate-700); font-family: inherit; font-size: 13px; font-weight: 650; transition: .15s ease; }
    .sw-search input { padding-right: 38px; padding-left: 34px; }
    .sw-search input::-webkit-search-cancel-button { display: none; }
    .sw-select { cursor: pointer; }
    .sw-search input:focus, .sw-select:focus { border-color: #bfdbfe; box-shadow: 0 0 0 3px rgba(37, 99, 235, .09); }
    .sw-search-clear { position: absolute; top: 50%; right: 7px; display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; padding: 0; transform: translateY(-50%); border: 0; border-radius: 8px; background: transparent; color: var(--sw-slate-400); cursor: pointer; font-size: 18px; }
    .sw-search-clear:hover { background: var(--sw-slate-100); color: var(--sw-slate-700); }
    .sw-search-clear[hidden] { display: none; }

    .sw-results-region { position: relative; min-height: 70px; }
    .sw-loading-label { position: absolute; z-index: 5; top: 10px; right: 20px; display: none; padding: 5px 9px; border: 1px solid #bfdbfe; border-radius: 999px; background: #fff; color: var(--sw-primary); box-shadow: var(--sw-shadow); font-size: 11px; font-weight: 800; }
    .sw-results-region.is-loading .sw-loading-label, .sw-results-region.has-error .sw-loading-label { display: block; }
    .sw-results-region.is-loading > :not(.sw-loading-label) { opacity: .55; pointer-events: none; }
    .sw-results-region.has-error .sw-loading-label { border-color: #fecaca; color: var(--sw-red); }

    .sw-pager { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 18px; padding: 14px 20px; border-top: 1px solid var(--sw-slate-100); background: #fff; }
    .sw-rows-control { display: flex; align-items: center; gap: 8px; margin: 0; color: var(--sw-slate-500); font-size: 12px; font-weight: 800; }
    .sw-rows-control select { width: 70px; min-height: 36px; padding: 6px 8px; border: 1px solid var(--sw-border); border-radius: 9px; background: #fff; color: var(--sw-slate-700); font-family: inherit; font-size: 12px; font-weight: 750; }
    .sw-pager-info { color: var(--sw-slate-500); font-size: 12px; font-weight: 700; text-align: center; }
    .sw-pager-info strong { color: var(--sw-slate-900); font-weight: 850; }
    .sw-pager-controls { display: flex; align-items: center; gap: 6px; }
    .sw-pager-btn { display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-height: 36px; padding: 7px 12px; border: 1px solid var(--sw-border); border-radius: 9px; background: #fff; color: var(--sw-slate-600); cursor: pointer; font-family: inherit; font-size: 12px; font-weight: 800; text-decoration: none; transition: .15s ease; }
    .sw-pager-btn:hover:not(:disabled) { border-color: #bfdbfe; background: var(--sw-primary-soft); color: var(--sw-primary); }
    .sw-pager-btn:disabled { opacity: .45; cursor: not-allowed; }
    .sw-pager-page { min-width: 62px; padding: 7px 9px; border-radius: 9px; background: var(--sw-primary-soft); color: var(--sw-primary); font-size: 12px; font-weight: 850; text-align: center; }

    .sw-summary-cards { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
    .sw-summary-card { display: flex; align-items: center; gap: 12px; min-height: 94px; padding: 14px 16px; border: 1px solid var(--sw-border); border-radius: 12px; background: #fff; box-shadow: var(--sw-shadow); cursor: default; transition: .15s ease; }
    .sw-summary-card:hover { transform: translateY(-3px); border-color: #bfdbfe; box-shadow: 0 12px 24px rgba(15, 23, 42, .08); }
    .sw-summary-icon { display: inline-flex; flex: 0 0 auto; align-items: center; justify-content: center; width: 44px; height: 44px; border: 1px solid; border-radius: 10px; font-size: 18px; }
    .sw-summary-icon.blue { border-color: #bfdbfe; background: var(--sw-primary-soft); color: var(--sw-primary); }
    .sw-summary-icon.green { border-color: #a7f3d0; background: var(--sw-green-soft); color: var(--sw-green); }
    .sw-summary-icon.amber { border-color: #fde68a; background: var(--sw-amber-soft); color: var(--sw-amber); }
    .sw-summary-icon.violet { border-color: #ddd6fe; background: #f5f3ff; color: #7c3aed; }
    .sw-summary-copy { min-width: 0; }
    .sw-summary-label { margin: 0 0 3px; color: var(--sw-slate-500); font-size: 10.5px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; }
    .sw-summary-value { margin: 0; color: var(--sw-slate-900); font-size: 22px; font-weight: 900; letter-spacing: -.02em; line-height: 1.1; }
    .sw-summary-value.compact { font-size: 16px; line-height: 1.3; }
    .sw-summary-note { margin: 3px 0 0; color: var(--sw-slate-500); font-size: 11px; font-weight: 700; }

    .sw-table-wrap { overflow-x: auto; }
    .sw-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .sw-table thead { background: var(--sw-slate-50); }
    .sw-table th { padding: 12px 18px; border-bottom: 1px solid var(--sw-slate-100); color: var(--sw-slate-400); font-size: 10px; font-weight: 900; letter-spacing: .09em; text-align: left; text-transform: uppercase; white-space: nowrap; }
    .sw-table td { padding: 12px 18px; border-bottom: 1px solid var(--sw-slate-100); vertical-align: middle; }
    .sw-table tbody tr:hover { background: var(--sw-slate-50); }
    .sw-participant-cell { display: flex; align-items: center; gap: 12px; }
    .sw-avatar { display: inline-flex; flex: 0 0 auto; align-items: center; justify-content: center; width: 36px; height: 36px; border: 1px solid #bfdbfe; border-radius: 999px; background: var(--sw-primary-soft); color: var(--sw-primary); font-size: 13px; font-weight: 900; }
    .sw-cell-name { margin: 0; color: var(--sw-slate-900); font-size: 13px; font-weight: 850; }
    .sw-cell-email, .sw-cell-date { color: var(--sw-slate-700); font-size: 12.5px; font-weight: 650; }
    .sw-type-badge, .sw-status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 9px; border: 1px solid var(--sw-border); border-radius: 7px; background: var(--sw-slate-50); color: var(--sw-slate-600); font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
    .sw-status-badge.recorded { border-color: #a7f3d0; background: var(--sw-green-soft); color: var(--sw-green); }
    .sw-status-badge.not-recorded { color: var(--sw-slate-500); }
    .sw-status-badge.completed { border-color: var(--sw-border); background: var(--sw-slate-100); color: var(--sw-slate-600); }
    .sw-status-badge.ongoing { border-color: #a7f3d0; background: var(--sw-green-soft); color: var(--sw-green); }
    .sw-status-badge.upcoming { border-color: #bfdbfe; background: var(--sw-primary-soft); color: var(--sw-primary); }

    .sw-empty { padding: 46px 20px; color: var(--sw-slate-500); font-size: 13px; font-weight: 650; text-align: center; }
    .sw-empty > i { display: block; margin-bottom: 10px; color: var(--sw-slate-400); font-size: 34px; }
    .sw-empty-title { margin-bottom: 4px; color: var(--sw-slate-700); font-size: 14px; font-weight: 850; }

    .sw-search-clear:focus-visible, .sw-pager-btn:focus-visible, .sw-tabs a:focus-visible { outline: 3px solid rgba(37, 99, 235, .3); outline-offset: 2px; }

    @media (max-width: 991.98px) {
        .sw-report-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sw-report-toolbar .sw-search-field { grid-column: 1 / -1; }
        .sw-summary-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .sw-page { padding-top: 20px; padding-bottom: 40px; }
        .sw-icon { width: 48px; height: 48px; border-radius: 14px; font-size: 20px; }
        .sw-title-row { gap: 12px; }
        .sw-title { font-size: 21px; }
        .sw-tabs { gap: 20px; }
        .sw-report-toolbar { grid-template-columns: 1fr; padding-right: 16px; padding-left: 16px; }
        .sw-report-toolbar .sw-search-field { grid-column: auto; }
        .sw-pager { grid-template-columns: 1fr; gap: 12px; }
        .sw-pager-info { order: -1; }
        .sw-rows-control, .sw-pager-controls { justify-content: center; }
    }
    @media (max-width: 519.98px) {
        .sw-summary-cards { grid-template-columns: 1fr; }
        .sw-pager-controls { display: grid; grid-template-columns: 1fr auto 1fr; }
    }

    @media (prefers-reduced-motion: reduce) {
        .sw-shell * {
            scroll-behavior: auto !important;
            transition-duration: .01ms !important;
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
        }
    }
</style>
