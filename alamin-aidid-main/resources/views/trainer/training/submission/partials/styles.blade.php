@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root {
        --lw-primary: #2563eb;
        --lw-primary-dark: #1d4ed8;
        --lw-primary-soft: #eff6ff;
        --lw-indigo: #4338ca;
        --lw-indigo-soft: #eef2ff;
        --lw-green: #059669;
        --lw-green-soft: #ecfdf5;
        --lw-amber: #d97706;
        --lw-amber-soft: #fffbeb;
        --lw-rose: #e11d48;
        --lw-rose-soft: #fff1f2;
        --lw-red: #dc2626;
        --lw-red-soft: #fef2f2;
        --lw-slate-900: #0f172a;
        --lw-slate-700: #334155;
        --lw-slate-600: #475569;
        --lw-slate-500: #64748b;
        --lw-slate-400: #94a3b8;
        --lw-slate-300: #cbd5e1;
        --lw-border: #e2e8f0;
        --lw-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }

    body { background: #f8fafc; color: var(--lw-slate-900); margin: 0; }
    *, *::before, *::after { box-sizing: border-box; }

    /* ============================================================ Header (matches material page exactly) ============================================================ */
    .lw-header { background: #fff; border-bottom: 1px solid var(--lw-border); }
    .lw-page { padding-top: 24px; padding-bottom: 46px; }

    .lw-breadcrumb { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11px; font-weight: 850; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 15px; }
    .lw-breadcrumb a { color: var(--lw-slate-500); text-decoration: none; }
    .lw-breadcrumb a:hover { color: var(--lw-primary); }
    .lw-breadcrumb .lw-bc-current { color: var(--lw-slate-700); }

    .lw-title-row { display: flex; align-items: flex-start; gap: 16px; }
    .lw-icon { width: 58px; height: 58px; border-radius: 16px; flex-shrink: 0; background-image: radial-gradient(#93c5fd 1px, transparent 1px); background-size: 10px 10px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: var(--lw-primary); display: flex; align-items: center; justify-content: center; font-size: 23px; }
    .lw-title { font-size: 26px; font-weight: 850; letter-spacing: -0.035em; color: var(--lw-slate-900); margin: 0 0 9px; line-height: 1.18; }
    .lw-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .lw-pill { display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; color: var(--lw-slate-600); border: 1px solid var(--lw-border); border-radius: 999px; padding: 5px 11px; font-size: 12px; font-weight: 750; }
    .lw-pill i { color: var(--lw-slate-500); }
    .lw-pill.mode { background: var(--lw-indigo-soft); color: var(--lw-indigo); border-color: #c7d2fe; font-weight: 850; letter-spacing: 0.04em; text-transform: uppercase; font-size: 11px; }
    .lw-pill.mode i { color: var(--lw-indigo); }
    .lw-pill.lw-pill-pending { background: var(--lw-amber-soft); color: var(--lw-amber); border-color: #fde68a; }
    .lw-pill.lw-pill-pending i { color: var(--lw-amber); }

    .lw-tabs { display: flex; gap: 24px; margin-top: 22px; overflow-x: auto; }
    .lw-tabs a { position: relative; color: var(--lw-slate-500); text-decoration: none; font-size: 14px; font-weight: 650; padding-bottom: 13px; border-bottom: 2.5px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; transition: 0.15s ease; }
    .lw-tabs a:hover { color: var(--lw-slate-900); }
    .lw-tabs a.active { color: var(--lw-primary); border-bottom-color: var(--lw-primary); font-weight: 800; }
    .lw-tab-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 19px; padding: 0 7px;
        background: var(--lw-amber-soft); color: var(--lw-amber);
        border: 1px solid #fde68a;
        border-radius: 999px; font-size: 10.5px; font-weight: 900;
    }
    .lw-hidden { display: none !important; }
    [hidden] { display: none !important; }

    /* ============================================================ Submission grid layout ============================================================ */
    .lw-sub-grid { display: flex; align-items: flex-start; gap: 18px; }

    .lw-panel { background: #fff; border: 1px solid var(--lw-border); border-radius: 18px; box-shadow: var(--lw-shadow); overflow: hidden; display: flex; flex-direction: column; }

    /* ============================================================ Inbox sidebar ============================================================ */
    .lw-inbox { width: 320px; min-height: 42px; max-height: calc(100vh - 102px); flex-shrink: 0; align-self: flex-start; position: sticky; top: 78px; transition: width .18s ease; }
    .lw-inbox-expanded { width: 320px; max-height: inherit; min-height: 0; display: flex; flex-direction: column; overflow: hidden; opacity: 1; visibility: visible; transform: translateX(0); pointer-events: auto; transition: opacity .18s ease, transform .18s ease, visibility 0s linear 0s; }
    .lw-inbox.is-collapsed { width: 44px; }
    .lw-inbox.is-collapsed .lw-inbox-expanded { opacity: 0; visibility: hidden; transform: translateX(-6px); pointer-events: none; transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s; }
    .lw-show-tasks { position: absolute; inset: 0 auto auto 0; width: 44px; height: 42px; border: 1px solid var(--lw-border); border-radius: 10px; background: #fff; color: var(--lw-primary); box-shadow: var(--lw-shadow); display: inline-flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; font: inherit; opacity: 0; visibility: hidden; transform: translateX(6px); pointer-events: none; transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s, border-color .15s ease, box-shadow .15s ease; }
    .lw-inbox.is-collapsed .lw-show-tasks { opacity: 1; visibility: visible; transform: translateX(0); pointer-events: auto; transition-delay: 0s; }
    .lw-show-tasks span { display: none; }
    .lw-show-tasks:hover { border-color: #93c5fd; box-shadow: 0 8px 22px rgba(37, 99, 235, .12); transform: translateY(-1px); }
    .lw-inbox-head { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; background: #fafbfc; flex-shrink: 0; }
    .lw-inbox-head-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
    .lw-inbox-title { font-size: 13px; font-weight: 850; color: var(--lw-slate-900); margin: 0 0 2px; }
    .lw-inbox-subtitle { color: var(--lw-slate-500); font-size: 11px; font-weight: 600; line-height: 1.35; margin: 0; }
    .lw-collapse-btn { width: 28px; height: 28px; border-radius: 7px; background: #fff; border: 1px solid var(--lw-border); color: var(--lw-slate-500); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.15s; }
    .lw-collapse-btn:hover { background: var(--lw-primary-soft); color: var(--lw-primary); border-color: #bfdbfe; }

    .lw-search { position: relative; }
    .lw-search input { width: 100%; height: 38px; padding: 7px 34px 7px 32px; border: 1px solid var(--lw-border); border-radius: 9px; font-size: 12.5px; font-family: inherit; background: #fff; color: var(--lw-slate-900); outline: none; transition: 0.15s; }
    .lw-search input:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
    .lw-search i { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: var(--lw-slate-400); font-size: 14px; }
    .lw-search-clear { position: absolute; right: 7px; top: 50%; transform: translateY(-50%); width: 25px; height: 25px; padding: 0; border: 0; border-radius: 6px; background: transparent; color: var(--lw-slate-400); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .lw-search-clear:hover { background: #f1f5f9; color: var(--lw-slate-700); }
    .lw-search-clear i { position: static; transform: none; color: inherit; font-size: 12px; }
    .lw-task-search input[type="search"]::-webkit-search-cancel-button,
    .lw-task-search input[type="search"]::-webkit-search-decoration,
    .lw-roster-search input[type="search"]::-webkit-search-cancel-button,
    .lw-roster-search input[type="search"]::-webkit-search-decoration { appearance: none; -webkit-appearance: none; }

    .lw-inbox-list { flex: 1 1 auto; min-height: 0; overflow-x: hidden; overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; scrollbar-color: var(--lw-slate-300) transparent; }
    .lw-inbox-list::-webkit-scrollbar { width: 7px; }
    .lw-inbox-list::-webkit-scrollbar-thumb { background: var(--lw-slate-300); border-radius: 999px; border: 2px solid #fff; }
    .lw-inbox-card { display: block; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; border-left: 3px solid transparent; text-decoration: none; transition: 0.15s; }
    .lw-inbox-card:hover { background: #f8fafc; }
    .lw-inbox-card.is-active { background: var(--lw-primary-soft); border-left-color: var(--lw-primary); }

    .lw-inbox-type { display: inline-flex; align-items: center; gap: 6px; min-width: 0; font-size: 9.5px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.1em; color: var(--lw-slate-500); }
    .lw-inbox-type-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: start; gap: 10px; margin-bottom: 7px; }
    .lw-inbox-card.is-active .lw-inbox-type { color: var(--lw-primary); }
    .lw-inbox-card-title { font-size: 13.5px; font-weight: 800; color: var(--lw-slate-900); margin: 0 0 4px; line-height: 1.3; }
    .lw-inbox-card-sub { font-size: 11.5px; color: var(--lw-slate-500); margin-bottom: 10px; line-height: 1.3; font-weight: 600; }
    .lw-inbox-card-foot { display: flex; justify-content: space-between; align-items: center; font-size: 11px; gap: 6px; flex-wrap: wrap; }
    .lw-submitted-text { color: var(--lw-slate-600); font-weight: 700; }

    .lw-mini-pill { padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 800; border: 1px solid transparent; white-space: nowrap; }
    .lw-mini-pill.rose { background: var(--lw-amber-soft); color: var(--lw-amber); border-color: #fde68a; }
    .lw-mini-pill.success { background: var(--lw-green-soft); color: #047857; border-color: #a7f3d0; }
    .lw-mini-pill.muted { background: #f1f5f9; color: var(--lw-slate-500); border-color: var(--lw-border); }

    .lw-inbox-empty { padding: 40px 22px; text-align: center; }
    .lw-inbox-empty i { font-size: 32px; color: var(--lw-slate-300); margin-bottom: 8px; display: block; }
    .lw-inbox-empty h4 { font-size: 13.5px; font-weight: 800; color: var(--lw-slate-700); margin: 0 0 4px; }
    .lw-inbox-empty p { font-size: 12px; color: var(--lw-slate-500); margin: 0; line-height: 1.4; }

    /* ============================================================ Detail panel ============================================================ */
    .lw-detail { min-width: 0; flex: 1 1 auto; min-height: 520px; max-height: none; }
    .lw-detail.is-loading { position: relative; }
    .lw-detail.is-loading::after { content: ''; position: absolute; inset: 0; background: rgba(255,255,255,.64); pointer-events: none; z-index: 8; }

    .lw-detail-head { padding: 22px 26px 18px; border-bottom: 1px solid #f1f5f9; }
    .lw-detail-top { display: flex; gap: 16px; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; }
    .lw-detail-info { min-width: 0; flex: 1; }
    .lw-detail-badges { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 6px; }
    .lw-detail-type { display: inline-flex; align-items: center; gap: 6px; background: var(--lw-primary-soft); color: var(--lw-primary-dark); padding: 4px 9px; border-radius: 7px; font-size: 10.5px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.08em; border: 1px solid #bfdbfe; }
    .lw-detail-loc { font-size: 12.5px; font-weight: 700; color: var(--lw-slate-500); }
    .lw-detail-title { font-size: 21px; font-weight: 850; color: var(--lw-slate-900); margin: 10px 0 6px; line-height: 1.25; letter-spacing: -0.01em; }
    .lw-detail-instr { font-size: 13.5px; color: var(--lw-slate-600); line-height: 1.55; margin: 0 0 12px; max-width: 720px; }
    .lw-detail-meta { display: flex; gap: 8px; flex-wrap: wrap; }
    .lw-detail-download { flex-shrink: 0; }
    .lw-task-state { display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; border: 1px solid; padding: 4px 9px; font-size: 9.5px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; line-height: 1; white-space: nowrap; }
    .lw-task-state.is-open { color: #047857; background: #ecfdf5; border-color: #a7f3d0; }
    .lw-task-state.is-closed { color: #b91c1c; background: var(--lw-red-soft); border-color: #fecaca; }
    .lw-task-state.is-not-open { color: #b45309; background: var(--lw-amber-soft); border-color: #fde68a; }
    .lw-task-availability { display: flex; align-items: center; gap: 7px; margin: 10px 0 0; font-size: 12px; font-weight: 750; }
    .lw-task-availability.is-open { color: #047857; }
    .lw-task-availability.is-closed { color: #b91c1c; }
    .lw-task-availability.is-not-open { color: #b45309; }

    .lw-btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 9px; font-size: 12.5px; font-weight: 750; text-decoration: none; cursor: pointer; transition: 0.15s; border: 1px solid transparent; font-family: inherit; white-space: nowrap; }
    .lw-btn-primary { background: var(--lw-primary); color: #fff; border-color: var(--lw-primary); }
    .lw-btn-primary:hover { background: var(--lw-primary-dark); border-color: var(--lw-primary-dark); }
    .lw-btn-ghost { background: #fff; color: var(--lw-slate-700); border-color: var(--lw-border); }
    .lw-btn-ghost:hover { background: #f8fafc; color: var(--lw-slate-900); }
    .lw-btn-disabled { background: #f1f5f9; color: var(--lw-slate-400); border-color: var(--lw-border); cursor: not-allowed; }
    .lw-btn-sm { padding: 6px 11px; font-size: 11.5px; }

    /* ============================================================ Filter bar ============================================================ */
    .lw-filter-bar { padding: 14px 26px; background: #fafbfc; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
    .lw-roster-search { width: min(260px, 100%); }
    .lw-chips { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .lw-chips-label { font-size: 11px; font-weight: 750; color: var(--lw-slate-500); text-transform: uppercase; letter-spacing: 0.06em; margin-right: 4px; }
    .lw-chip { display: inline-flex; align-items: center; gap: 7px; padding: 6px 12px; border-radius: 999px; font-size: 11.5px; font-weight: 750; color: var(--lw-slate-600); background: #fff; border: 1px solid var(--lw-border); text-decoration: none; transition: 0.15s; cursor: pointer; }
    .lw-chip:hover { background: #f1f5f9; color: var(--lw-slate-900); }
    .lw-chip.is-active { background: var(--lw-primary); color: #fff; border-color: var(--lw-primary); }
    .lw-chip-rose.is-active { background: var(--lw-rose); border-color: var(--lw-rose); }
    .lw-chip-count { background: rgba(255, 255, 255, 0.25); padding: 1px 7px; border-radius: 999px; font-size: 10px; font-weight: 900; }
    .lw-chip:not(.is-active) .lw-chip-count { background: #f1f5f9; color: var(--lw-slate-600); }

    /* ============================================================ Table ============================================================ */
    .lw-roster-results { position: relative; min-height: 180px; }
    .lw-roster-results.is-loading::after { content: ''; position: absolute; inset: 0; background: rgba(255,255,255,.64); pointer-events: none; }
    .lw-table-wrap { overflow-x: auto; overflow-y: visible; }
    .lw-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .lw-table thead { background: #fff; }
    .lw-table thead::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 1px; background: var(--lw-border); }
    .lw-table th { text-align: left; font-size: 10px; font-weight: 900; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.09em; padding: 13px 22px; white-space: nowrap; }
    .lw-table th.t-center { text-align: center; }
    .lw-table th.t-right { text-align: right; }
    .lw-table td { padding: 11px 22px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .lw-table tbody tr { transition: background-color .1s ease, opacity .15s ease, transform .15s ease; }
    .lw-table tbody tr.is-review-removing { opacity: 0; transform: translateY(-3px); pointer-events: none; }
    .lw-table tbody tr:hover { background: #f8fafc; }
    .lw-table tbody tr.is-need-review { background: rgba(254, 243, 199, 0.22); }
    .lw-table tbody tr.is-need-review:hover { background: rgba(254, 243, 199, 0.5); }
    .lw-table tbody tr.is-awaiting { opacity: 0.75; }

    .lw-participant { display: flex; align-items: center; gap: 10px; }
    .lw-avatar { width: 30px; height: 30px; border-radius: 999px; background: #f1f5f9; color: var(--lw-slate-600); border: 1px solid var(--lw-border); font-size: 11px; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lw-pname { font-size: 12.5px; font-weight: 800; color: var(--lw-slate-900); line-height: 1.25; }
    .lw-pname.is-late { color: var(--lw-rose); }
    .lw-pmeta { font-size: 10.5px; font-weight: 600; color: var(--lw-slate-500); line-height: 1.25; margin-top: 1px; }

    .lw-submitted-cell { font-size: 11.5px; font-weight: 650; color: var(--lw-slate-600); }
    .lw-submitted-cell.is-awaiting { color: var(--lw-slate-400); font-style: italic; }
    .lw-submitted-cell.is-late { color: var(--lw-rose); }

    .lw-file-btn { display: inline-flex; align-items: center; gap: 6px; background: transparent; border: 0; padding: 0; cursor: pointer; color: var(--lw-primary); font-size: 11.5px; font-weight: 750; max-width: 240px; font-family: inherit; }
    .lw-file-btn:hover { color: var(--lw-primary-dark); text-decoration: underline; }
    .lw-file-btn .name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lw-file-unavailable { display: inline-flex; align-items: center; gap: 6px; color: var(--lw-slate-500); font-size: 11.5px; font-weight: 700; }
    .lw-no-file { color: var(--lw-slate-300); font-size: 13px; font-weight: 700; }

    .lw-status { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 7px; font-size: 9.5px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; border: 1px solid transparent; }
    .lw-status.s-awaiting { background: #f1f5f9; color: var(--lw-slate-500); border-color: var(--lw-border); }
    .lw-status.s-need-review { background: var(--lw-amber-soft); color: #92400e; border-color: #fde68a; }
    .lw-status.s-reviewed { background: var(--lw-green-soft); color: #047857; border-color: #a7f3d0; }
    .lw-late-note { display: block; margin-top: 4px; color: var(--lw-rose); font-size: 9px; font-weight: 850; text-transform: uppercase; letter-spacing: .05em; }

    .lw-action { min-width: 122px; justify-content: center; }
    .review-form.is-pending .lw-action { cursor: wait; opacity: .76; }

    .lw-table-empty { padding: 56px 24px; text-align: center; }
    .lw-table-empty i { font-size: 32px; color: var(--lw-slate-300); margin-bottom: 8px; display: block; }
    .lw-table-empty h4 { font-size: 14px; font-weight: 800; color: var(--lw-slate-700); margin: 0 0 4px; }
    .lw-table-empty p { font-size: 12.5px; color: var(--lw-slate-500); margin: 0; }

    .lw-report-pager { padding: 12px 20px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .lw-rows-control { display: inline-flex; align-items: center; gap: 7px; color: var(--lw-slate-600); font-size: 12px; font-weight: 750; }
    .lw-rows-control select { border: 1px solid var(--lw-slate-300); border-radius: 7px; background: #fff; color: var(--lw-slate-700); padding: 5px 24px 5px 8px; font: inherit; outline: none; }
    .lw-rows-control select:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    .lw-pager-info { color: var(--lw-slate-500); font-size: 11.5px; font-weight: 650; }
    .lw-pager-controls { display: flex; align-items: center; gap: 7px; }
    .lw-pager-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 11px; border: 1px solid var(--lw-border); border-radius: 7px; font-size: 11px; font-weight: 750; color: var(--lw-slate-600); background: #fff; cursor: pointer; text-decoration: none; }
    .lw-pager-btn:hover { border-color: #93c5fd; color: var(--lw-primary); }
    .lw-pager-btn:disabled { color: var(--lw-slate-400); cursor: not-allowed; background: #f8fafc; }
    .lw-pager-page { color: var(--lw-slate-500); font-size: 11px; font-weight: 800; }
    .lw-report-error { margin: 0 0 14px; padding: 10px 13px; border: 1px solid #fecaca; border-radius: 10px; background: #fef2f2; color: #991b1b; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .lw-roster-results > .lw-report-error { margin: 12px 20px 0; }

    /* ============================================================ No selection state ============================================================ */
    .lw-detail-empty { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; }
    .lw-detail-empty-inner { text-align: center; max-width: 380px; }
    .lw-detail-empty-icon { width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 999px; background: var(--lw-primary-soft); color: var(--lw-primary); display: inline-flex; align-items: center; justify-content: center; font-size: 26px; }
    .lw-detail-empty h2 { font-size: 17px; font-weight: 850; color: var(--lw-slate-900); margin: 0 0 8px; letter-spacing: -0.01em; }
    .lw-detail-empty p { font-size: 13px; color: var(--lw-slate-500); margin: 0; line-height: 1.55; }

    /* ============================================================ File preview modal ============================================================ */
    .lw-modal { position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(4px); padding: 20px; display: flex; align-items: center; justify-content: center; }
    .lw-modal.hidden { display: none; }
    .lw-modal-box { background: #fff; width: 100%; max-width: 1180px; height: calc(100vh - 40px); height: min(94vh, calc(100dvh - 40px)); border-radius: 18px; overflow: hidden; box-shadow: 0 24px 56px rgba(15, 23, 42, 0.32); display: flex; flex-direction: column; }
    .lw-modal-head { padding: 14px 22px; border-bottom: 1px solid var(--lw-border); display: flex; justify-content: space-between; align-items: center; gap: 16px; background: #fff; flex-shrink: 0; }
    .lw-modal-head > :first-child { min-width: 0; }
    .lw-modal-head h3 { font-size: 14px; font-weight: 850; color: var(--lw-slate-900); margin: 0 0 2px; }
    .lw-modal-head p { font-size: 11.5px; font-weight: 600; color: var(--lw-slate-500); margin: 0; }
    .lw-modal-actions { display: flex; flex: 0 0 auto; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
    .lw-modal-body { flex: 1 1 auto; background: #f1f5f9; padding: 14px; overflow: hidden; min-height: 0; }
    .lw-modal-frame { width: 100%; height: 100%; border: 0; border-radius: 12px; background: #fff; display: block; }
    .lw-preview-unavailable { height: 100%; border-radius: 12px; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: var(--lw-slate-500); }
    .lw-preview-unavailable i { font-size: 34px; color: var(--lw-slate-300); }
    .lw-preview-unavailable h4 { color: var(--lw-slate-800); font-size: 15px; margin: 10px 0 4px; }
    .lw-preview-unavailable p { margin: 0; font-size: 12.5px; }

    @media (max-width: 1024px) {
        .lw-sub-grid { flex-direction: column; }
        .lw-inbox, .lw-inbox.is-collapsed { position: static; width: 100%; max-height: none; }
        .lw-inbox-expanded, .lw-inbox.is-collapsed .lw-inbox-expanded { width: 100%; max-height: none; opacity: 1; visibility: visible; transform: none; pointer-events: auto; }
        .lw-show-tasks, .lw-inbox.is-collapsed .lw-show-tasks, .lw-collapse-btn { display: none; }
        .lw-inbox-list { overflow-y: visible; }
        .lw-detail { width: 100%; }
    }

    @media (prefers-reduced-motion: reduce) {
        .lw-inbox, .lw-inbox-expanded, .lw-show-tasks, .lw-table tbody tr { transition-duration: 0.01ms !important; }
    }
    @media (max-width: 700px) {
        .lw-filter-bar { padding: 13px 16px; align-items: stretch; }
        .lw-roster-search { width: 100%; }
        .lw-detail-head { padding: 18px 16px; }
        .lw-detail-download, .lw-detail-download .lw-btn { width: 100%; justify-content: center; }
        .lw-table th, .lw-table td { padding-left: 14px; padding-right: 14px; }
        .lw-report-pager { align-items: flex-start; }
        .lw-pager-info { width: 100%; order: -1; }
        .lw-modal { padding: 12px; }
        .lw-modal-box { height: calc(100vh - 24px); height: calc(100dvh - 24px); }
        .lw-modal-head { align-items: flex-start; flex-direction: column; padding: 14px 16px; }
        .lw-modal-actions { width: 100%; }
        .lw-modal-actions .lw-btn { flex: 1 1 auto; justify-content: center; }
    }

    @media (max-width: 479.98px) {
        .lw-modal-actions { display: grid; grid-template-columns: 1fr; }
        .lw-modal-body { padding: 10px; }
    }

    @keyframes lwToastIn { from { transform: translateY(-8px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
@endpush
