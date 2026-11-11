@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root {
        --lw-primary: #2563eb;
        --lw-primary-dark: #1d4ed8;
        --lw-primary-soft: #eff6ff;
        --lw-indigo: #4338ca;
        --lw-indigo-mid: #4f46e5;
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
        --lw-violet: #7c3aed;
        --lw-violet-soft: #f5f3ff;
        --lw-violet-border: #ddd6fe;
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

    .lw-header { background: #fff; border-bottom: 1px solid var(--lw-border); }
    .lw-breadcrumb { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11px; font-weight: 850; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 15px; }
    .lw-breadcrumb a { color: var(--lw-slate-500); text-decoration: none; transition: 0.15s; }
    .lw-breadcrumb a:hover { color: var(--lw-primary); }
    .lw-breadcrumb .lw-bc-current { color: var(--lw-slate-700); }
    .lw-title-row { display: flex; align-items: flex-start; gap: 16px; justify-content: space-between; }
    .lw-title-left { display: flex; align-items: flex-start; gap: 16px; min-width: 0; flex: 1; }
    .lw-icon { width: 58px; height: 58px; border-radius: 16px; flex-shrink: 0; background-image: radial-gradient(#93c5fd 1px, transparent 1px); background-size: 10px 10px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: var(--lw-primary); display: flex; align-items: center; justify-content: center; font-size: 23px; }
    .lw-title { font-size: 26px; font-weight: 850; letter-spacing: -0.035em; color: var(--lw-slate-900); margin: 0 0 9px; line-height: 1.18; }
    .lw-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .lw-pill { display: inline-flex; align-items: center; gap: 6px; background: var(--lw-slate-100); color: var(--lw-slate-600); border: 1px solid var(--lw-border); border-radius: 999px; padding: 5px 11px; font-size: 12px; font-weight: 750; }
    .lw-pill i { color: var(--lw-slate-500); }
    .lw-pill.mode { background: var(--lw-indigo-soft); color: var(--lw-indigo); border-color: var(--lw-indigo-border); font-weight: 850; letter-spacing: 0.04em; text-transform: uppercase; font-size: 11px; }
    .lw-pill.mode i { color: var(--lw-indigo); }
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

    .lw-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; font-size: 12px; font-weight: 800; border-radius: 8px; border: 1px solid var(--lw-slate-300); background: #fff; color: var(--lw-slate-700); cursor: pointer; transition: 0.15s; font-family: inherit; white-space: nowrap; text-decoration: none; }
    .lw-btn:hover { background: var(--lw-slate-50); border-color: var(--lw-slate-400); color: var(--lw-slate-900); }

    .lw-page { padding-top: 16px; padding-bottom: 24px; }
    .lw-att-row { display: flex; align-items: flex-start; gap: 16px; min-height: 0; }
    @media (max-width: 900px) { .lw-att-row { flex-direction: column; } }

    .lw-att-sidebar { width: 360px; min-height: 42px; max-height: calc(100vh - 102px); flex-shrink: 0; align-self: flex-start; position: sticky; top: 78px; background: #fff; border: 1px solid var(--lw-border); border-radius: 14px; box-shadow: var(--lw-shadow); display: flex; flex-direction: column; overflow: hidden; transition: width 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease; }
    .lw-att-sidebar.is-collapsed { width: 44px; max-height: none; border-color: transparent; background: transparent; box-shadow: none; overflow: visible; }
    .lw-sb-expanded { width: 360px; display: flex; flex-direction: column; min-height: 0; max-height: inherit; opacity: 1; visibility: visible; transform: translateX(0); pointer-events: auto; transition: opacity .18s ease, transform .18s ease, visibility 0s linear 0s; }
    .lw-att-sidebar.is-collapsed .lw-sb-expanded { opacity: 0; visibility: hidden; transform: translateX(-6px); pointer-events: none; transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s; }
    .lw-sb-collapsed { position: absolute; inset: 0 auto auto 0; display: flex; align-items: flex-start; justify-content: flex-start; opacity: 0; visibility: hidden; transform: translateX(6px); pointer-events: none; transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s; }
    .lw-att-sidebar.is-collapsed .lw-sb-collapsed { opacity: 1; visibility: visible; transform: translateX(0); pointer-events: auto; transition-delay: 0s; }
    .lw-sb-collapsed-btn { width: 44px; height: 42px; border: 1px solid var(--lw-border); border-radius: 10px; background: #fff; color: var(--lw-primary); box-shadow: var(--lw-shadow); display: inline-flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; font: inherit; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
    .lw-sb-collapsed-btn:hover { border-color: #93c5fd; box-shadow: 0 8px 22px rgba(37, 99, 235, .12); transform: translateY(-1px); }

    .lw-sb-head { padding: 14px 16px; background: #fff; border-bottom: 1px solid var(--lw-slate-100); flex-shrink: 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
    .lw-sb-head h2 { font-size: 13px; font-weight: 900; color: var(--lw-slate-900); margin: 0 0 3px; }
    .lw-sb-head p { font-size: 11px; font-weight: 600; color: var(--lw-slate-500); margin: 0; }
    .lw-sb-collapse-btn { background: var(--lw-slate-50); border: 1px solid var(--lw-border); border-radius: 7px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; color: var(--lw-slate-500); cursor: pointer; transition: 0.15s; flex-shrink: 0; }
    .lw-sb-collapse-btn:hover { background: var(--lw-slate-100); color: var(--lw-slate-900); }
    .lw-sb-filter-bar { padding: 12px 16px; background: var(--lw-slate-50); border-bottom: 1px solid var(--lw-slate-100); flex-shrink: 0; }
    .lw-sb-chips { display: flex; gap: 6px; margin-bottom: 10px; }
    .lw-sb-chip { flex: 1; padding: 7px 10px; font-size: 11px; font-weight: 800; border-radius: 7px; border: 1px solid var(--lw-slate-300); background: #fff; color: var(--lw-slate-600); cursor: pointer; transition: 0.15s; font-family: inherit; text-transform: uppercase; letter-spacing: 0.03em; }
    .lw-sb-chip:hover { background: var(--lw-slate-100); color: var(--lw-slate-900); }
    .lw-sb-chip.is-active { background: var(--lw-primary); border-color: var(--lw-primary); color: #fff; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2); }
    .lw-sb-search { position: relative; }
    .lw-sb-search input { width: 100%; padding: 8px 34px 8px 32px; border: 1px solid var(--lw-slate-300); border-radius: 8px; font-size: 12.5px; font-family: inherit; background: #fff; color: var(--lw-slate-900); outline: none; transition: 0.15s; }
    .lw-sb-search input:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
    .lw-sb-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--lw-slate-400); font-size: 13px; }
    .lw-sb-search input[type="search"] { appearance: textfield; -webkit-appearance: textfield; }
    .lw-sb-search input[type="search"]::-webkit-search-decoration,
    .lw-sb-search input[type="search"]::-webkit-search-cancel-button { display: none; -webkit-appearance: none; appearance: none; }

    .lw-sb-list { flex: 1 1 auto; min-height: 0; overflow-x: hidden; overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; scrollbar-color: var(--lw-slate-300) transparent; }
    .lw-sb-list::-webkit-scrollbar { width: 6px; }
    .lw-sb-list::-webkit-scrollbar-track { background: transparent; }
    .lw-sb-list::-webkit-scrollbar-thumb { border-radius: 999px; background: var(--lw-slate-300); }
    .lw-sb-list::-webkit-scrollbar-thumb:hover { background: var(--lw-slate-400); }
    .lw-session-card { display: block; padding: 14px 16px; cursor: pointer; transition: 0.12s; border: 0; border-left: 3px solid transparent; background: #fff; text-align: left; text-decoration: none; font-family: inherit; border-bottom: 1px solid var(--lw-slate-100); }
    .lw-session-card:hover { background: var(--lw-slate-50); text-decoration: none; }
    .lw-session-card.is-active { background: var(--lw-indigo-soft); border-left-color: var(--lw-indigo-mid); }
    .lw-session-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px; }
    .lw-type-pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; border-radius: 5px; font-size: 9.5px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; border: 1px solid; }
    .lw-type-pill.online { background: var(--lw-violet-soft); color: var(--lw-violet); border-color: var(--lw-violet-border); }
    .lw-type-pill.physical { background: var(--lw-amber-soft); color: #92400e; border-color: var(--lw-amber-border); }
    .lw-session-short-date { font-size: 11px; font-weight: 800; color: var(--lw-slate-500); white-space: nowrap; }
    .lw-session-status { display: inline-flex; align-items: center; padding: 2px 7px; border: 1px solid; border-radius: 999px; font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .lw-session-status.completed { color: var(--lw-slate-600); background: var(--lw-slate-100); border-color: var(--lw-slate-200); }
    .lw-session-status.ongoing { color: #047857; background: var(--lw-green-soft); border-color: var(--lw-green-border); }
    .lw-session-status.upcoming { color: var(--lw-primary); background: var(--lw-primary-soft); border-color: var(--lw-blue-border); }
    .lw-session-title { font-size: 14px; font-weight: 900; color: var(--lw-slate-900); line-height: 1.3; margin: 0 0 4px; }
    .lw-session-sub { font-size: 11px; font-weight: 650; color: var(--lw-slate-500); margin: 0 0 8px; line-height: 1.3; }
    .lw-session-line { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 650; color: var(--lw-slate-500); margin-top: 4px; }
    .lw-session-line i { color: var(--lw-slate-400); font-size: 12px; }
    .lw-session-line .loc { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .lw-sb-empty { padding: 36px 16px; text-align: center; }
    .lw-sb-empty-icon { width: 44px; height: 44px; border-radius: 999px; background: var(--lw-slate-100); color: var(--lw-slate-400); display: inline-flex; align-items: center; justify-content: center; font-size: 16px; margin: 0 auto 10px; }
    .lw-sb-empty h4 { font-size: 13px; font-weight: 800; color: var(--lw-slate-700); margin: 0 0 4px; }
    .lw-sb-empty p { font-size: 11.5px; color: var(--lw-slate-500); margin: 0; }

    .lw-att-main { flex: 1; display: flex; flex-direction: column; gap: 12px; min-width: 0; }
    .lw-detail-card { background: #fff; border: 1px solid var(--lw-border); border-radius: 14px; box-shadow: var(--lw-shadow); padding: 16px 20px; flex-shrink: 0; }
    .lw-detail-pill { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.07em; background: var(--lw-indigo-soft); color: var(--lw-indigo); border: 1px solid var(--lw-indigo-border); margin-bottom: 6px; }
    .lw-detail-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .lw-detail-title { font-size: 18px; font-weight: 900; color: var(--lw-slate-900); margin: 0 0 12px; line-height: 1.25; letter-spacing: -0.015em; }
    .lw-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 700px) { .lw-detail-grid { grid-template-columns: 1fr; } }
    .lw-detail-item { display: flex; align-items: flex-start; gap: 10px; }
    .lw-detail-item-icon { padding: 6px; border-radius: 8px; font-size: 16px; border: 1px solid; flex-shrink: 0; }
    .lw-detail-item-icon.calendar { background: var(--lw-slate-50); border-color: var(--lw-border); color: var(--lw-slate-600); }
    .lw-detail-item-icon.online { background: var(--lw-violet-soft); border-color: var(--lw-violet-border); color: var(--lw-violet); }
    .lw-detail-item-icon.physical { background: var(--lw-amber-soft); border-color: var(--lw-amber-border); color: #92400e; }
    .lw-detail-item-info { min-width: 0; flex: 1; }
    .lw-detail-item-label { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; margin: 0; color: var(--lw-slate-400); }
    .lw-detail-item-label.online { color: var(--lw-violet); }
    .lw-detail-item-label.physical { color: #92400e; }
    .lw-detail-item-value { font-size: 13px; font-weight: 800; color: var(--lw-slate-900); margin: 3px 0 0; }
    .lw-detail-item-sub { font-size: 11.5px; font-weight: 650; color: var(--lw-slate-500); margin: 2px 0 0; }
    .lw-detail-item-link { font-size: 11.5px; font-weight: 700; color: var(--lw-primary); text-decoration: none; margin: 3px 0 0; display: inline-flex; align-items: center; gap: 4px; }
    .lw-detail-item-link:hover { text-decoration: underline; }

    .lw-roster { background: #fff; border: 1px solid var(--lw-border); border-radius: 14px; box-shadow: var(--lw-shadow); display: flex; flex-direction: column; overflow: hidden; }
    .lw-roster-head { padding: 12px 18px; border-bottom: 1px solid var(--lw-slate-100); background: #fff; display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }
    @media (min-width: 900px) { .lw-roster-head { flex-direction: row; justify-content: space-between; align-items: center; } }
    .lw-roster-title { font-size: 13px; font-weight: 900; color: var(--lw-slate-900); margin: 0; }
    .lw-roster-sub { font-size: 11.5px; font-weight: 600; color: var(--lw-slate-500); margin: 2px 0 0; }
    .lw-roster-stats { display: flex; flex-wrap: wrap; gap: 6px; }
    .lw-stat-pill { padding: 4px 9px; border-radius: 6px; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; border: 1px solid; }
    .lw-stat-pill.present { background: var(--lw-green-soft); color: #047857; border-color: var(--lw-green-border); }
    .lw-stat-pill.late { background: var(--lw-amber-soft); color: #92400e; border-color: var(--lw-amber-border); }
    .lw-stat-pill.absent { background: var(--lw-rose-soft); color: #be123c; border-color: var(--lw-rose-border); }
    .lw-stat-pill.not-recorded { background: var(--lw-slate-100); color: var(--lw-slate-600); border-color: var(--lw-slate-200); }
    .lw-roster-filters { padding: 10px 18px; background: var(--lw-slate-50); border-bottom: 1px solid var(--lw-slate-100); display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
    @media (min-width: 700px) { .lw-roster-filters { flex-direction: row; align-items: center; justify-content: space-between; } }
    .lw-search { position: relative; }
    .lw-search input { width: 100%; padding: 7px 34px 7px 30px; border: 1px solid var(--lw-slate-300); border-radius: 7px; font-size: 12px; font-family: inherit; background: #fff; color: var(--lw-slate-900); outline: none; transition: 0.15s; }
    .lw-search input:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
    .lw-search i { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: var(--lw-slate-400); font-size: 13px; }
    .lw-search-clear { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; border: 0; border-radius: 6px; background: transparent; color: var(--lw-slate-400); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .lw-search-clear i { position: static; transform: none; font-size: 16px; }
    .lw-search-clear:hover { background: var(--lw-slate-100); color: var(--lw-slate-700); }
    .lw-roster-filters .lw-search input[type="search"] { appearance: textfield; -webkit-appearance: textfield; }
    .lw-roster-filters .lw-search input[type="search"]::-webkit-search-decoration,
    .lw-roster-filters .lw-search input[type="search"]::-webkit-search-cancel-button { display: none; -webkit-appearance: none; appearance: none; }
    @media (min-width: 700px) { .lw-search { width: 260px; } }
    .lw-select-group { display: flex; gap: 6px; }
    .lw-select { background: #fff; border: 1px solid var(--lw-slate-300); border-radius: 7px; padding: 6px 10px; font-size: 12px; font-weight: 700; color: var(--lw-slate-700); font-family: inherit; cursor: pointer; outline: none; transition: 0.15s; }
    .lw-select:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }

    .lw-table-wrap { overflow-x: auto; overflow-y: visible; background: #fff; }
    .lw-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .lw-table thead { background: #fff; box-shadow: 0 1px 0 var(--lw-slate-200); }
    .lw-table th { text-align: left; font-size: 10px; font-weight: 900; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.09em; padding: 11px 14px; white-space: nowrap; }
    .lw-table th.t-center { text-align: center; }
    .lw-table td { padding: 10px 14px; border-bottom: 1px solid var(--lw-slate-100); vertical-align: middle; white-space: nowrap; }
    .lw-table td.t-center { text-align: center; }
    .lw-table tbody tr { transition: 0.1s; }
    .lw-table tbody tr:hover { background: var(--lw-slate-50); }
    .lw-cell-flex { display: flex; align-items: center; gap: 10px; }
    .lw-avatar { width: 30px; height: 30px; border-radius: 999px; background: var(--lw-indigo-soft); border: 1px solid var(--lw-indigo-border); color: var(--lw-indigo); font-size: 11px; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lw-cell-name { font-size: 12.5px; font-weight: 800; color: var(--lw-slate-900); margin: 0; line-height: 1.2; }
    .lw-cell-meta { font-size: 10.5px; font-weight: 600; color: var(--lw-slate-500); margin: 1px 0 0; line-height: 1.2; }
    .lw-cell-email { font-size: 12px; font-weight: 600; color: var(--lw-slate-700); }
    .lw-cell-time { font-size: 11.5px; font-weight: 800; color: var(--lw-primary); background: var(--lw-primary-soft); border: 1px solid var(--lw-blue-border); padding: 3px 9px; border-radius: 6px; display: inline-block; }
    .lw-cell-date { font-size: 12px; font-weight: 800; color: var(--lw-slate-700); }
    .lw-cell-dash { font-size: 12px; color: var(--lw-slate-400); font-weight: 700; }

    .lw-att-toggle { display: inline-flex; border-radius: 7px; overflow: hidden; border: 1px solid var(--lw-slate-200); }
    .lw-att-seg { padding: 5px 11px; font-size: 11px; font-weight: 800; background: #fff; color: var(--lw-slate-500); border-right: 1px solid var(--lw-slate-200); }
    .lw-att-seg:last-child { border-right: 0; }
    .lw-att-seg.is-present { background: var(--lw-green); color: #fff; }
    .lw-att-seg.is-late { background: var(--lw-amber); color: #fff; }
    .lw-att-seg.is-absent { background: var(--lw-rose); color: #fff; }
    .lw-att-notrec { font-size: 10px; font-weight: 800; color: var(--lw-slate-400); margin-top: 4px; }

    .lw-empty-cell { padding: 56px 24px; text-align: center; }
    .lw-empty-cell i { font-size: 28px; color: var(--lw-slate-300); margin-bottom: 8px; display: block; }
    .lw-empty-cell h4 { font-size: 13px; font-weight: 800; color: var(--lw-slate-700); margin: 0 0 4px; }
    .lw-empty-cell p { font-size: 11.5px; color: var(--lw-slate-500); margin: 0; }

    .lw-no-session { background: #fff; border: 1px solid var(--lw-border); border-radius: 14px; box-shadow: var(--lw-shadow); flex: 1; display: flex; align-items: center; justify-content: center; padding: 60px 30px; }
    .lw-no-session-inner { text-align: center; }
    .lw-no-session-icon { width: 56px; height: 56px; border-radius: 999px; background: var(--lw-slate-100); color: var(--lw-slate-400); font-size: 22px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; }
    .lw-no-session h3 { font-size: 16px; font-weight: 900; color: var(--lw-slate-900); margin: 0 0 5px; }
    .lw-no-session p { font-size: 13px; color: var(--lw-slate-500); margin: 0; }

    .lw-modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 24px; opacity: 0; visibility: hidden; pointer-events: none; transition: opacity 0.18s ease, visibility 0.18s ease; }
    .lw-modal-overlay.is-open { opacity: 1; visibility: visible; pointer-events: auto; }
    .lw-modal-box { background: #fff; width: 100%; max-width: 1280px; max-height: calc(100vh - 20px); max-height: min(95vh, calc(100dvh - 20px)); border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--lw-border); opacity: 0; transform: translateY(8px) scale(.99); transition: opacity .18s ease, transform .18s ease; }
    .lw-modal-overlay.is-open .lw-modal-box { opacity: 1; transform: translateY(0) scale(1); }
    .lw-modal-head { padding: 14px 20px; background: var(--lw-slate-50); border-bottom: 1px solid var(--lw-border); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
    .lw-modal-head h2 { font-size: 18px; font-weight: 900; color: var(--lw-slate-900); margin: 0; }
    .lw-modal-head p { font-size: 11.5px; font-weight: 650; color: var(--lw-slate-500); margin: 3px 0 0; }
    .lw-calendar-nav { display: flex; align-items: center; gap: 10px; margin-bottom: 3px; }
    .lw-calendar-nav h2 { min-width: 170px; text-align: center; }
    .lw-modal-close { background: transparent; border: 0; padding: 8px; border-radius: 7px; color: var(--lw-slate-400); cursor: pointer; transition: 0.15s; font-size: 16px; }
    .lw-modal-close:hover { background: var(--lw-slate-200); color: var(--lw-slate-900); }
    .lw-modal-body { flex: 1; overflow-y: auto; padding: 20px; background: var(--lw-slate-100); }
    .lw-cal { background: #fff; border: 1px solid var(--lw-border); border-radius: 12px; overflow: hidden; }
    .lw-cal-head { display: grid; grid-template-columns: repeat(7, 1fr); background: var(--lw-slate-50); border-bottom: 1px solid var(--lw-border); }
    .lw-cal-head-day { padding: 9px; text-align: center; font-size: 11px; font-weight: 900; color: var(--lw-slate-500); text-transform: uppercase; letter-spacing: 0.06em; }
    .lw-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); background: var(--lw-slate-200); gap: 1px; border-top: 1px solid var(--lw-border); }
    .lw-cal-day { background: #fff; min-height: 96px; padding: 6px 6px 8px; display: flex; flex-direction: column; gap: 2px; transition: background 0.12s; cursor: pointer; }
    .lw-cal-day:hover { background: #f8fafc; }
    .lw-cal-day.out { background: var(--lw-slate-50); }
    .lw-cal-day.out:hover { background: #f1f5f9; }
    .lw-cal-day.no-events { cursor: default; }
    .lw-cal-day.no-events:hover { background: #fff; }
    .lw-cal-day.out.no-events:hover { background: var(--lw-slate-50); }
    .lw-cal-events { flex: 1; min-height: 0; display: flex; flex-direction: column; gap: 2px; }
    .lw-cal-more { font-size: 10px; font-weight: 800; color: var(--lw-primary); margin: 2px 0 0; padding: 3px 6px; background: var(--lw-primary-soft); border: 1px solid var(--lw-blue-border); border-radius: 5px; cursor: pointer; text-align: left; font-family: inherit; transition: 0.12s; }
    .lw-cal-more:hover { background: #dbeafe; color: var(--lw-primary-dark); }
    .lw-cal-day-num { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px; }
    .lw-cal-num { font-size: 11.5px; font-weight: 800; color: var(--lw-slate-700); padding: 1px 5px; border-radius: 4px; }
    .lw-cal-day.out .lw-cal-num { color: var(--lw-slate-400); }
    .lw-cal-num.today { background: var(--lw-primary); color: #fff; min-width: 22px; height: 22px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; padding: 0; }
    .lw-cal-count { font-size: 9px; font-weight: 900; padding: 1px 6px; border-radius: 5px; background: var(--lw-primary-soft); color: var(--lw-primary); border: 1px solid var(--lw-blue-border); }
    .lw-cal-event { display: block; padding: 4px 6px; border-radius: 5px; border: 1px solid; text-decoration: none; transition: 0.12s; }
    .lw-cal-event:hover { filter: brightness(0.96); transform: translateX(1px); }
    .lw-cal-event.online { background: var(--lw-violet-soft); border-color: var(--lw-violet-border); }
    .lw-cal-event.physical { background: var(--lw-amber-soft); border-color: var(--lw-amber-border); }
    .lw-cal-event-type { font-size: 8.5px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; line-height: 1.2; margin: 0; }
    .lw-cal-event.online .lw-cal-event-type { color: var(--lw-violet); }
    .lw-cal-event.physical .lw-cal-event-type { color: #92400e; }
    .lw-cal-event-title { font-size: 10.5px; font-weight: 700; line-height: 1.25; margin: 1px 0 0; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; }
    .lw-cal-event.online .lw-cal-event-title { color: #4c1d95; }
    .lw-cal-event.physical .lw-cal-event-title { color: #78350f; }
    .lw-cal-event-time { font-size: 9px; font-weight: 600; line-height: 1.2; margin: 1px 0 0; }
    .lw-cal-event.online .lw-cal-event-time { color: var(--lw-violet); }
    .lw-cal-event.physical .lw-cal-event-time { color: #b45309; }

    /* ---- Day detail popover ---- */
    .lw-day-pop-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 24px; animation: lwFadeIn 0.18s ease-out; }
    .lw-day-pop-overlay.is-hidden { display: none !important; }
    .lw-day-pop { background: #fff; width: 100%; max-width: 480px; max-height: 80vh; border-radius: 14px; box-shadow: 0 25px 50px rgba(0,0,0,0.3); display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--lw-border); animation: lwSlide 0.22s cubic-bezier(0.16, 1, 0.3, 1); }
    .lw-day-pop-head { padding: 14px 18px; background: var(--lw-slate-50); border-bottom: 1px solid var(--lw-border); display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-shrink: 0; }
    .lw-day-pop-head h3 { font-size: 15px; font-weight: 900; color: var(--lw-slate-900); margin: 0; letter-spacing: -0.01em; }
    .lw-day-pop-head p { font-size: 11px; font-weight: 700; color: var(--lw-slate-500); margin: 2px 0 0; text-transform: uppercase; letter-spacing: 0.06em; }
    .lw-day-pop-list { padding: 12px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
    .lw-day-pop-event { padding: 10px 12px; border-radius: 8px; border: 1px solid; text-decoration: none; display: block; transition: 0.12s; }
    .lw-day-pop-event:hover { filter: brightness(0.97); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06); }
    .lw-day-pop-event.online { background: var(--lw-violet-soft); border-color: var(--lw-violet-border); }
    .lw-day-pop-event.physical { background: var(--lw-amber-soft); border-color: var(--lw-amber-border); }
    .lw-day-pop-event-type { font-size: 9.5px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.07em; margin: 0; }
    .lw-day-pop-event.online .lw-day-pop-event-type { color: var(--lw-violet); }
    .lw-day-pop-event.physical .lw-day-pop-event-type { color: #92400e; }
    .lw-day-pop-event-title { font-size: 14px; font-weight: 800; line-height: 1.3; margin: 4px 0 6px; word-break: break-word; }
    .lw-day-pop-event.online .lw-day-pop-event-title { color: #4c1d95; }
    .lw-day-pop-event.physical .lw-day-pop-event-title { color: #78350f; }
    .lw-day-pop-event-time { font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
    .lw-day-pop-event.online .lw-day-pop-event-time { color: var(--lw-violet); }
    .lw-day-pop-event.physical .lw-day-pop-event-time { color: #b45309; }

    .lw-results-region { position: relative; min-height: 90px; }
    .lw-loading-label { display: none; position: absolute; right: 18px; top: 12px; z-index: 8; padding: 5px 9px; border-radius: 999px; background: #fff; border: 1px solid var(--lw-border); box-shadow: var(--lw-shadow); color: var(--lw-slate-500); font-size: 10px; font-weight: 800; }
    .lw-results-region.is-loading { opacity: .72; }
    .lw-results-region.is-loading .lw-loading-label { display: inline-flex; }
    .lw-report-error { margin: 12px 18px; padding: 9px 11px; border-radius: 8px; background: var(--lw-rose-soft); border: 1px solid var(--lw-rose-border); color: #be123c; font-size: 12px; font-weight: 700; }
    .lw-report-pager { padding: 12px 18px; background: var(--lw-slate-50); border-top: 1px solid var(--lw-slate-100); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
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

    @media (max-width: 900px) {
        .lw-att-sidebar, .lw-att-sidebar.is-collapsed { position: static; width: 100%; max-height: none; background: #fff; border-color: var(--lw-border); box-shadow: var(--lw-shadow); overflow: hidden; }
        .lw-sb-expanded, .lw-att-sidebar.is-collapsed .lw-sb-expanded { width: 100%; max-height: none; opacity: 1; visibility: visible; transform: none; pointer-events: auto; }
        .lw-sb-collapsed, .lw-att-sidebar.is-collapsed .lw-sb-collapsed, .lw-sb-collapse-btn { display: none; }
        .lw-sb-list { flex: none; min-height: auto; overflow: visible; overscroll-behavior: auto; }
        .lw-att-main { width: 100%; }
    }
    @media (max-width: 700px) {
        .lw-title-row { flex-wrap: wrap; }
        .lw-title { font-size: 22px; }
        .lw-search, .lw-select-group, .lw-select { width: 100%; }
        .lw-modal-overlay { padding: 10px; }
        .lw-modal-head { align-items: flex-start; flex-wrap: wrap; gap: 10px; }
        .lw-modal-head > div:first-child { min-width: 0; flex: 1 1 220px; }
        .lw-calendar-nav { flex-wrap: wrap; }
        .lw-calendar-nav h2 { order: -1; width: 100%; min-width: 0; }
    }

    .lw-hidden { display: none !important; }
    .lw-min-0 { min-width: 0; }

    @media (prefers-reduced-motion: reduce) {
        .lw-att-sidebar, .lw-sb-expanded, .lw-sb-collapsed { transition-duration: 0.01ms !important; }
    }
    .lw-flex-1 { flex: 1; }
</style>
@include('trainer.attendance.partials.qr-styles')
@endpush
