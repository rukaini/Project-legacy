@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root {
        --lw-primary: #2563eb;
        --lw-primary-dark: #1d4ed8;
        --lw-primary-soft: #eff6ff;
        --lw-indigo: #4338ca;
        --lw-indigo-soft: #eef2ff;
        --lw-indigo-border: #c7d2fe;
        --lw-blue-border: #bfdbfe;
        --lw-green: #059669;
        --lw-green-soft: #ecfdf5;
        --lw-green-border: #a7f3d0;
        --lw-amber: #d97706;
        --lw-amber-soft: #fffbeb;
        --lw-amber-border: #fde68a;
        --lw-rose: #e11d48;
        --lw-rose-soft: #fff1f2;
        --lw-rose-border: #fecdd3;
        --lw-pink: #db2777;
        --lw-pink-soft: #fdf2f8;
        --lw-pink-border: #fbcfe8;
        --lw-slate-900: #0f172a;
        --lw-slate-700: #334155;
        --lw-slate-600: #475569;
        --lw-slate-500: #64748b;
        --lw-slate-400: #94a3b8;
        --lw-slate-300: #cbd5e1;
        --lw-slate-200: #e2e8f0;
        --lw-slate-100: #f1f5f9;
        --lw-slate-50: #f8fafc;
        --lw-border: #e2e8f0;
        --lw-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }
    body { background: #f8fafc; color: var(--lw-slate-900); margin: 0; }
    *, *::before, *::after { box-sizing: border-box; }
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--lw-slate-300); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--lw-slate-400); }

    /* ============================== Header (matches other workspace pages) ============================== */
    .lw-header { background: #fff; border-bottom: 1px solid var(--lw-border); }
    .lw-breadcrumb { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11px; font-weight: 850; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 15px; }
    .lw-breadcrumb a { color: var(--lw-slate-500); text-decoration: none; transition: 0.15s; }
    .lw-breadcrumb a:hover { color: var(--lw-primary); }
    .lw-breadcrumb .lw-bc-current { color: var(--lw-slate-700); }
    .lw-title-row { display: flex; align-items: flex-start; gap: 16px; }
    .lw-title-left { display: flex; align-items: flex-start; gap: 16px; min-width: 0; flex: 1; }
    .lw-icon { width: 58px; height: 58px; border-radius: 16px; flex-shrink: 0; background-image: radial-gradient(#93c5fd 1px, transparent 1px); background-size: 10px 10px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: var(--lw-primary); display: flex; align-items: center; justify-content: center; font-size: 23px; }
    .lw-title { overflow-wrap: anywhere; font-size: 26px; font-weight: 850; letter-spacing: -0.035em; color: var(--lw-slate-900); margin: 0 0 9px; line-height: 1.18; }
    .lw-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .lw-pill { display: inline-flex; align-items: center; gap: 6px; background: var(--lw-slate-100); color: var(--lw-slate-600); border: 1px solid var(--lw-border); border-radius: 999px; padding: 5px 11px; font-size: 12px; font-weight: 750; }
    .lw-pill i { color: var(--lw-slate-500); }
    .lw-pill.mode { background: var(--lw-indigo-soft); color: var(--lw-indigo); border-color: var(--lw-indigo-border); font-weight: 850; letter-spacing: 0.04em; text-transform: uppercase; font-size: 11px; }
    .lw-pill.mode i { color: var(--lw-indigo); }
    .lw-pill.accessed { background: var(--lw-green-soft); color: #047857; border-color: var(--lw-green-border); }
    .lw-pill.accessed i { color: var(--lw-green); }
    .lw-pill.never { background: var(--lw-amber-soft); color: #92400e; border-color: var(--lw-amber-border); }
    .lw-pill.never i { color: var(--lw-amber); }
    .lw-tabs { display: flex; gap: 24px; margin-top: 22px; overflow-x: auto; }
    .lw-tabs a { color: var(--lw-slate-500); text-decoration: none; font-size: 14px; font-weight: 650; padding-bottom: 13px; border-bottom: 2.5px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; transition: 0.15s ease; }
    .lw-tabs a:hover { color: var(--lw-slate-900); }
    .lw-tabs a.active { color: var(--lw-primary); border-bottom-color: var(--lw-primary); font-weight: 800; }
    .lw-tab-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 19px; padding: 0 7px; margin-left: 2px;
        background: var(--lw-amber-soft); color: var(--lw-amber);
        border: 1px solid var(--lw-amber-border);
        border-radius: 999px; font-size: 10.5px; font-weight: 900; line-height: 1.4;
    }

    /* ============================== Page body ============================== */
    .lw-page { padding-top: 22px; padding-bottom: 40px; }

    /* ============================== Summary strip ============================== */
    .lw-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
    @media (max-width: 900px) { .lw-summary { grid-template-columns: repeat(2, 1fr); } }
    .lw-sum-card { min-width: 0; min-height: 120px; background: #fff; border: 1px solid var(--lw-border); border-radius: 16px; padding: 16px 17px; box-shadow: var(--lw-shadow); display: flex; flex-direction: column; align-items: flex-start; gap: 0; transition: 0.15s ease; cursor: default; }
    .lw-sum-card:hover { transform: translateY(-3px); border-color: #bfdbfe; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08); }
    .lw-sum-icon { width: 40px; height: 40px; margin-bottom: 12px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 19px; flex-shrink: 0; border: 1px solid; }
    .lw-sum-icon.blue { background: var(--lw-primary-soft); border-color: var(--lw-blue-border); color: var(--lw-primary); }
    .lw-sum-icon.green { background: var(--lw-green-soft); border-color: var(--lw-green-border); color: var(--lw-green); }
    .lw-sum-icon.amber { background: var(--lw-amber-soft); border-color: var(--lw-amber-border); color: var(--lw-amber); }
    .lw-sum-icon.pink { background: var(--lw-pink-soft); border-color: var(--lw-pink-border); color: var(--lw-pink); }
    .lw-sum-icon.violet { background: #f5f3ff; border-color: #ddd6fe; color: #7c3aed; }
    .lw-sum-label { font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.06em; color: var(--lw-slate-500); margin: 0 0 5px; }
    .lw-sum-value { font-size: 27px; font-weight: 900; color: var(--lw-slate-900); line-height: 1; letter-spacing: -0.02em; margin: 0; }
    .lw-sum-sub { font-size: 11px; font-weight: 700; color: var(--lw-slate-500); margin: 6px 0 0; }
    .lw-gender-value { font-size: 16px; }
    .lw-gender-value span:first-child { color: var(--lw-primary); }
    .lw-gender-value span:last-child { color: #be185d; }

    /* ============================== Main panel ============================== */
    .lw-panel { background: #fff; border: 1px solid var(--lw-border); border-radius: 14px; box-shadow: var(--lw-shadow); overflow: hidden; }

    .lw-panel-head { padding: 16px 20px; border-bottom: 1px solid var(--lw-slate-100); display: flex; flex-direction: column; gap: 14px; }
    .lw-panel-head-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap; }
    .lw-panel-title { font-size: 15px; font-weight: 900; color: var(--lw-slate-900); margin: 0; letter-spacing: -0.01em; }
    .lw-panel-sub { font-size: 12px; font-weight: 600; color: var(--lw-slate-500); margin: 3px 0 0; }

    .lw-controls { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .lw-search { position: relative; }
    .lw-search input { padding: 7px 34px 7px 32px; border: 1px solid var(--lw-slate-300); border-radius: 8px; font-size: 12.5px; font-family: inherit; background: #fff; color: var(--lw-slate-900); outline: none; transition: 0.15s; width: 240px; }
    .lw-search input:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
    .lw-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--lw-slate-400); font-size: 13px; }
    .lw-search-clear { position: absolute; right: 7px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; border: 0; border-radius: 6px; background: transparent; color: var(--lw-slate-400); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .lw-search-clear i { position: static; transform: none; font-size: 16px; }
    .lw-search-clear:hover { background: var(--lw-slate-100); color: var(--lw-slate-700); }
    .lw-controls .lw-search input[type="search"] { appearance: textfield; -webkit-appearance: textfield; }
    .lw-controls .lw-search input[type="search"]::-webkit-search-decoration,
    .lw-controls .lw-search input[type="search"]::-webkit-search-cancel-button { display: none; -webkit-appearance: none; appearance: none; }
    .lw-select { background: #fff; border: 1px solid var(--lw-slate-300); border-radius: 8px; padding: 7px 11px; font-size: 12px; font-weight: 700; color: var(--lw-slate-700); font-family: inherit; cursor: pointer; outline: none; transition: 0.15s; }
    .lw-select:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }

    /* Alphabet rail */
    .lw-alpha { display: flex; flex-wrap: wrap; gap: 4px; padding: 12px 20px 14px; background: var(--lw-slate-50); border-bottom: 1px solid var(--lw-slate-100); align-items: center; }
    .lw-alpha-label { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: var(--lw-slate-500); margin-right: 6px; }
    .lw-alpha-btn { min-width: 28px; height: 28px; padding: 0 7px; font-size: 11px; font-weight: 900; border-radius: 7px; border: 1px solid var(--lw-slate-200); background: #fff; color: var(--lw-slate-600); cursor: pointer; transition: 0.12s; font-family: inherit; display: inline-flex; align-items: center; justify-content: center; }
    .lw-alpha-btn:hover { background: var(--lw-primary-soft); color: var(--lw-primary); border-color: var(--lw-blue-border); }
    .lw-alpha-btn.is-active { background: var(--lw-primary); color: #fff; border-color: var(--lw-primary); box-shadow: 0 2px 6px rgba(37, 99, 235, 0.22); }
    .lw-alpha-btn.is-empty { color: var(--lw-slate-300); cursor: default; background: #fff; }
    .lw-alpha-btn.is-empty:hover { background: #fff; color: var(--lw-slate-300); border-color: var(--lw-slate-200); }

    /* Table */
    .lw-table-wrap { overflow-x: auto; }
    .lw-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .lw-table thead { background: var(--lw-slate-50); }
    .lw-table th { text-align: left; font-size: 10px; font-weight: 900; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.09em; padding: 12px 18px; white-space: nowrap; border-bottom: 1px solid var(--lw-slate-100); }
    .lw-table th.t-center { text-align: center; }
    .lw-table td { padding: 12px 18px; border-bottom: 1px solid var(--lw-slate-100); vertical-align: middle; }
    .lw-table td.t-center { text-align: center; }
    .lw-table tbody tr { transition: 0.1s; }
    .lw-table tbody tr:hover { background: var(--lw-slate-50); }

    .lw-cell-flex { display: flex; align-items: center; gap: 12px; }
    .lw-avatar { width: 36px; height: 36px; border-radius: 999px; background: var(--lw-primary-soft); border: 1px solid var(--lw-blue-border); color: var(--lw-primary); font-size: 13px; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lw-cell-name { font-size: 13px; font-weight: 850; color: var(--lw-slate-900); margin: 0; line-height: 1.25; }
    .lw-cell-email { font-size: 12.5px; font-weight: 650; color: var(--lw-slate-700); }
    .lw-cell-date { font-size: 12px; font-weight: 800; color: var(--lw-slate-700); line-height: 1.2; }
    .lw-cell-time { font-size: 10.5px; font-weight: 700; color: var(--lw-slate-500); margin: 2px 0 0; line-height: 1.2; }
    .lw-muted-date { color: var(--lw-slate-400); }

    .lw-tag { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 6px; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.07em; border: 1px solid; white-space: nowrap; }
    .lw-tag.male { background: var(--lw-primary-soft); color: var(--lw-primary-dark); border-color: var(--lw-blue-border); }
    .lw-tag.female { background: var(--lw-pink-soft); color: #be185d; border-color: var(--lw-pink-border); }
    .lw-tag.unknown { background: var(--lw-slate-100); color: var(--lw-slate-500); border-color: var(--lw-slate-200); }
    .lw-tag.accessed { background: var(--lw-green-soft); color: #047857; border-color: var(--lw-green-border); }
    .lw-tag.never { background: var(--lw-amber-soft); color: #92400e; border-color: var(--lw-amber-border); }

    /* Empty / no-result states */
    .lw-empty { padding: 64px 24px; text-align: center; }
    .lw-empty-icon { width: 56px; height: 56px; border-radius: 999px; background: var(--lw-slate-100); color: var(--lw-slate-400); font-size: 22px; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
    .lw-empty h4 { font-size: 14px; font-weight: 850; color: var(--lw-slate-700); margin: 0 0 5px; }
    .lw-empty p { font-size: 12.5px; color: var(--lw-slate-500); margin: 0; max-width: 380px; margin-left: auto; margin-right: auto; line-height: 1.5; }

    /* Footer */
    .lw-foot { padding: 12px 20px; background: var(--lw-slate-50); border-top: 1px solid var(--lw-slate-100); display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
    .lw-foot-text { font-size: 12px; font-weight: 700; color: var(--lw-slate-600); margin: 0; }
    .lw-foot-text strong { color: var(--lw-slate-900); font-weight: 900; }
    .lw-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; font-size: 12px; font-weight: 800; border-radius: 8px; border: 1px solid var(--lw-slate-300); background: #fff; color: var(--lw-slate-700); cursor: pointer; transition: 0.15s; font-family: inherit; white-space: nowrap; text-decoration: none; }
    .lw-btn:hover { background: var(--lw-slate-50); border-color: var(--lw-slate-400); color: var(--lw-slate-900); }

    .lw-results-region { position: relative; min-height: 90px; }
    .lw-loading-label { display: none; position: absolute; right: 18px; top: 12px; z-index: 8; padding: 5px 9px; border-radius: 999px; background: #fff; border: 1px solid var(--lw-border); box-shadow: var(--lw-shadow); color: var(--lw-slate-500); font-size: 10px; font-weight: 800; }
    .lw-results-region.is-loading { opacity: .72; }
    .lw-results-region.is-loading .lw-loading-label { display: inline-flex; }
    .lw-report-error { margin: 12px 18px; padding: 9px 11px; border-radius: 8px; background: var(--lw-rose-soft); border: 1px solid var(--lw-rose-border); color: #be123c; font-size: 12px; font-weight: 700; }
    .lw-report-pager { padding: 12px 20px; background: var(--lw-slate-50); border-top: 1px solid var(--lw-slate-100); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .lw-rows-control { display: inline-flex; align-items: center; gap: 7px; color: var(--lw-slate-600); font-size: 12px; font-weight: 750; }
    .lw-rows-control select { border: 1px solid var(--lw-slate-300); border-radius: 7px; background: #fff; color: var(--lw-slate-700); padding: 5px 24px 5px 8px; font: inherit; outline: none; }
    .lw-rows-control select:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    .lw-pager-info { color: var(--lw-slate-600); font-size: 12px; font-weight: 700; }
    .lw-pager-info strong { color: var(--lw-slate-900); font-weight: 900; }
    .lw-pager-controls { display: flex; align-items: center; gap: 7px; }
    .lw-pager-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; border: 1px solid var(--lw-slate-300); border-radius: 7px; background: #fff; color: var(--lw-slate-700); text-decoration: none; font-size: 11px; font-weight: 800; font-family: inherit; }
    .lw-pager-btn:hover { border-color: var(--lw-primary); color: var(--lw-primary); }
    .lw-pager-btn:disabled { cursor: not-allowed; opacity: .45; }
    .lw-pager-page { color: var(--lw-slate-500); font-size: 11px; font-weight: 800; }

    @media (max-width: 700px) {
        .lw-title-row { flex-wrap: wrap; }
        .lw-title { font-size: 22px; }
        .lw-summary { grid-template-columns: 1fr; }
        .lw-controls, .lw-search, .lw-search input, .lw-select { width: 100%; }
        .lw-report-pager { align-items: flex-start; }
    }

    .lw-hidden { display: none !important; }
    .lw-min-0 { min-width: 0; }
    .lw-flex-1 { flex: 1; }

    @media (prefers-reduced-motion: reduce) {
        .lw-sum-card,
        .lw-table tbody tr,
        .lw-search input,
        .lw-select,
        .lw-alpha-btn,
        .lw-btn { transition-duration: .01ms !important; }
    }
</style>
@endpush
