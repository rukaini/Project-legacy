<style>
    :root {
        --ana-blue: #2563eb;
        --ana-blue-dark: #1d4ed8;
        --ana-blue-soft: #eff6ff;
        --ana-green: #059669;
        --ana-green-soft: #ecfdf5;
        --ana-amber: #d97706;
        --ana-amber-soft: #fffbeb;
        --ana-red: #dc2626;
        --ana-slate-900: #0f172a;
        --ana-slate-700: #334155;
        --ana-slate-600: #475569;
        --ana-slate-500: #64748b;
        --ana-slate-400: #94a3b8;
        --ana-slate-300: #cbd5e1;
        --ana-slate-200: #e2e8f0;
        --ana-slate-100: #f1f5f9;
        --ana-slate-50: #f8fafc;
        --ana-shadow: 0 2px 9px rgba(15, 23, 42, 0.045);
    }

    body { background: var(--ana-slate-50); }
    .ana-shell *, .ana-shell *::before, .ana-shell *::after { box-sizing: border-box; }
    .ana-page { color: var(--ana-slate-900); }

    .ana-header { margin-bottom: 12px; }
    .ana-page-heading { margin-bottom: 14px; }
    .ana-title { margin: 0; color: var(--ana-slate-900); font-size: 26px; font-weight: 800; letter-spacing: -0.03em; line-height: 1.15; }
    .ana-subtitle { max-width: 760px; margin: 4px 0 0; color: var(--ana-slate-500); font-size: 13.5px; font-weight: 500; line-height: 1.45; }
    .ana-eyebrow { display: block; margin-bottom: 3px; color: var(--ana-blue); font-size: 9.5px; font-weight: 800; letter-spacing: 0.075em; text-transform: uppercase; }

    .ana-toolbar { display: flex; align-items: flex-end; gap: 10px; margin-bottom: 10px; padding: 11px 13px; border: 1px solid var(--ana-slate-200); border-radius: 12px; background: #fff; box-shadow: var(--ana-shadow); }
    .ana-filter { min-width: 170px; flex: 0 1 230px; }
    .ana-filter-training { min-width: 260px; flex: 1 1 360px; }
    .ana-filter label { display: block; margin-bottom: 4px; color: var(--ana-slate-500); font-size: 10px; font-weight: 750; letter-spacing: 0.035em; }
    .ana-select, .ana-sort-select { width: 100%; min-height: 40px; border: 1px solid var(--ana-slate-300); border-radius: 10px; background: #fff; color: var(--ana-slate-700); padding: 8px 32px 8px 11px; font: inherit; font-size: 12.5px; font-weight: 650; outline: none; }
    .ana-select:focus-visible, .ana-sort-select:focus-visible { border-color: var(--ana-blue); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.11); }
    .ana-select:disabled { background: var(--ana-slate-50); color: var(--ana-slate-400); cursor: not-allowed; }
    .ana-reset-link { min-height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; padding: 8px 10px; border-radius: 9px; color: var(--ana-blue); font-size: 11.5px; font-weight: 750; text-decoration: none; white-space: nowrap; }
    .ana-reset-link:hover { background: var(--ana-blue-soft); color: var(--ana-blue-dark); }

    .ana-btn { min-height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid transparent; border-radius: 10px; padding: 8px 13px; color: inherit; font-size: 12px; font-weight: 800; line-height: 1.2; text-decoration: none; white-space: nowrap; transition: 0.15s ease; }
    .ana-btn:focus-visible, .ana-text-link:focus-visible, .ana-reset-link:focus-visible { outline: 3px solid rgba(37, 99, 235, 0.2); outline-offset: 2px; }
    .ana-btn-secondary { border-color: var(--ana-slate-300); background: #fff; color: var(--ana-slate-700); }
    .ana-btn-secondary:hover { border-color: #bfdbfe; background: var(--ana-blue-soft); color: var(--ana-blue-dark); }
    .ana-btn-attention { border-color: #fbbf24; background: #fff; color: #92400e; }
    .ana-btn-attention:hover { background: var(--ana-amber-soft); color: #78350f; }

    .ana-identity { min-height: 74px; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 15px; border: 1px solid var(--ana-slate-200); border-radius: 12px; background: #fff; box-shadow: var(--ana-shadow); }
    .ana-identity-main { min-width: 0; }
    .ana-badges { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 5px; }
    .ana-type-badge, .ana-status-badge { border: 1px solid; border-radius: 999px; padding: 2px 7px; font-size: 8.5px; font-weight: 800; letter-spacing: 0.055em; text-transform: uppercase; }
    .ana-type-badge.lms { border-color: #bfdbfe; background: var(--ana-blue-soft); color: var(--ana-blue-dark); }
    .ana-type-badge.simple { border-color: #fde68a; background: var(--ana-amber-soft); color: #92400e; }
    .ana-status-badge { border-color: var(--ana-slate-200); background: var(--ana-slate-50); color: var(--ana-slate-600); }
    .ana-identity h2 { margin: 0; overflow: hidden; color: var(--ana-slate-900); font-size: 17px; font-weight: 800; letter-spacing: -0.02em; text-overflow: ellipsis; white-space: nowrap; }
    .ana-identity-meta { margin-top: 5px; display: flex; flex-wrap: wrap; gap: 5px 14px; color: var(--ana-slate-500); font-size: 11px; font-weight: 600; }
    .ana-identity-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .ana-identity-meta i { color: var(--ana-blue); font-size: 11px; }

    .ana-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }
    .ana-metric { min-width: 0; min-height: 70px; display: flex; align-items: flex-start; gap: 9px; padding: 11px 13px; border: 1px solid var(--ana-slate-200); border-radius: 12px; background: #fff; box-shadow: var(--ana-shadow); }
    .ana-metric-icon { width: 22px; height: 26px; display: flex; align-items: center; justify-content: center; flex: 0 0 22px; color: var(--ana-blue); font-size: 17px; }
    .ana-tone-green .ana-metric-icon { color: var(--ana-green); }
    .ana-tone-amber .ana-metric-icon { color: var(--ana-amber); }
    .ana-metric-label { color: var(--ana-slate-500); font-size: 12px; font-weight: 800; letter-spacing: 0.035em; text-transform: uppercase; }
    .ana-metric-value { margin-top: 2px; color: var(--ana-slate-900); font-size: 20px; font-weight: 850; letter-spacing: -0.025em; line-height: 1.05; }
    .ana-metric-note { margin-top: 4px; color: var(--ana-slate-400); font-size: 12px; font-weight: 550; line-height: 1.3; }

    .ana-action { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; padding: 12px 14px; border: 1px solid #fde68a; border-radius: 12px; background: var(--ana-amber-soft); }
    .ana-action-icon { flex: 0 0 auto; color: var(--ana-amber); font-size: 19px; }
    .ana-action-copy { min-width: 0; flex: 1; }
    .ana-action .ana-eyebrow { color: #92400e; }
    .ana-action h2 { margin: 0; color: #78350f; font-size: 13.5px; font-weight: 800; }
    .ana-action p { margin: 2px 0 0; color: #92400e; font-size: 11px; font-weight: 550; }

    .ana-report-stack { display: grid; gap: 12px; }
    .ana-async-report { transition: opacity .15s ease; }
    .ana-async-report.is-loading { opacity: .55; pointer-events: none; }
    .ana-dynamic-region { transition: opacity .16s ease; }
    .ana-dynamic-region.is-loading { opacity: .55; pointer-events: none; }
    .ana-overview-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .ana-mini-bar { display: flex; height: 14px; margin: 15px; overflow: hidden; border-radius: 99px; background: var(--ana-slate-100); }
    .ana-mini-bar span { min-width: 0; }.ana-mini-bar .present,.ana-session-track span { background: var(--ana-green); }.ana-mini-bar .late { background: var(--ana-amber); }.ana-mini-bar .absent { background: var(--ana-red); }.ana-mini-bar .not-recorded { background: var(--ana-slate-300); }.ana-mini-bar .reviewed { background: var(--ana-green); }.ana-mini-bar .pending { background: var(--ana-amber); }
    .ana-mini-legend { display: flex; flex-wrap: wrap; gap: 8px 14px; padding: 0 15px 15px; color: var(--ana-slate-600); font-size: 12px; }
    .ana-session-chart { display: flex; align-items: flex-end; gap: 18px; min-height: 190px; padding: 18px 20px 14px; border-top: 1px solid var(--ana-slate-100); overflow-x: auto; }
    .ana-session-column { display: grid; flex: 1 0 80px; min-width: 80px; height: 155px; grid-template-rows: 20px 1fr 30px 18px; gap: 4px; text-align: center; color: var(--ana-slate-600); font-size: 12px; }
    .ana-session-column strong { color: var(--ana-slate-900); font-size: 13px; }.ana-session-column small { color: var(--ana-slate-400); font-size: 11px; }.ana-session-track { display: flex; align-items: flex-end; justify-content: center; height: 100%; border-bottom: 1px solid var(--ana-slate-300); background: repeating-linear-gradient(to top, transparent 0, transparent 24%, var(--ana-slate-100) 25%); }.ana-session-track span { width: 30px; min-height: 2px; border-radius: 7px 7px 0 0; }
    .ana-panel { min-width: 0; overflow: hidden; border: 1px solid var(--ana-slate-200); border-radius: 12px; background: #fff; box-shadow: var(--ana-shadow); }
    .ana-panel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding: 12px 15px; border-bottom: 1px solid var(--ana-slate-100); }
    .ana-panel-head h2 { margin: 0; color: var(--ana-slate-900); font-size: 14px; font-weight: 800; }
    .ana-panel-head p { margin: 2px 0 0; color: var(--ana-slate-500); font-size: 11px; font-weight: 550; line-height: 1.4; }
    .ana-panel-tools { display: flex; align-items: flex-end; gap: 8px; }
    .ana-rate { border-radius: 999px; padding: 4px 9px; background: var(--ana-green-soft); color: #047857; font-size: 10px; font-weight: 800; white-space: nowrap; }
    .ana-sort-control { min-width: 150px; }
    .ana-sort-control span { display: block; margin-bottom: 3px; color: var(--ana-slate-400); font-size: 9px; font-weight: 750; }
    .ana-sort-select { min-height: 34px; padding-top: 5px; padding-bottom: 5px; font-size: 11px; }

    .ana-outcome-visual { display: grid; grid-template-columns: minmax(220px, 1.5fr) minmax(360px, 2fr); align-items: center; gap: 22px; padding: 15px; }
    .ana-attendance-bar { height: 18px; display: flex; overflow: hidden; border-radius: 999px; background: var(--ana-slate-100); box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.18); }
    .ana-attendance-segment.present { background: var(--ana-green); }
    .ana-attendance-segment.late { background: var(--ana-amber); }
    .ana-attendance-segment.absent { background: var(--ana-red); }
    .ana-attendance-segment.not-recorded { background: var(--ana-slate-300); }
    .ana-status-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 7px; }
    .ana-status-row { min-width: 0; display: flex; align-items: center; gap: 6px; padding: 8px 9px; border: 1px solid var(--ana-slate-100); border-radius: 9px; color: var(--ana-slate-600); font-size: 10.5px; font-weight: 650; }
    .ana-status-row strong { margin-left: auto; color: var(--ana-slate-900); font-size: 13px; }
    .ana-status-dot { width: 8px; height: 8px; border-radius: 3px; flex: 0 0 auto; }
    .ana-status-dot.present { background: var(--ana-green); }
    .ana-status-dot.late { background: var(--ana-amber); }
    .ana-status-dot.absent { background: var(--ana-red); }
    .ana-status-dot.not-recorded { background: var(--ana-slate-300); }

    .ana-table-block { border-top: 1px solid var(--ana-slate-100); }
    .ana-table-title { padding: 10px 15px 7px; color: var(--ana-slate-700); font-size: 10px; font-weight: 800; letter-spacing: 0.055em; text-transform: uppercase; }
    .ana-table-wrap { width: 100%; overflow-x: auto; }
    .ana-table { width: 100%; border-collapse: collapse; color: var(--ana-slate-600); font-size: 13px; }
    .ana-table th { padding: 9px 15px; background: var(--ana-slate-50); color: var(--ana-slate-500); font-size: 11px; font-weight: 800; letter-spacing: 0.04em; text-align: left; text-transform: uppercase; white-space: nowrap; }
    .ana-table td { padding: 10px 15px; border-top: 1px solid var(--ana-slate-100); font-weight: 600; white-space: nowrap; }
    .ana-table :is(th, td):not(:first-child) { text-align: center; }
    .ana-table td:first-child { max-width: 320px; overflow: hidden; color: var(--ana-slate-900); font-weight: 750; text-overflow: ellipsis; }

    .ana-bar-list { display: grid; gap: 12px; padding: 14px 15px; border-bottom: 1px solid var(--ana-slate-100); }
    .ana-bar-row { min-width: 0; }
    .ana-bar-label { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 5px; color: var(--ana-slate-600); font-size: 11px; font-weight: 650; }
    .ana-bar-label span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ana-bar-label strong { color: var(--ana-slate-900); font-size: 10.5px; font-weight: 800; white-space: nowrap; }
    .ana-score-track, .ana-submission-track { height: 9px; display: flex; overflow: hidden; border-radius: 999px; background: var(--ana-slate-100); }
    .ana-score-track span { border-radius: inherit; background: var(--ana-blue); }
    .ana-submission-track .reviewed { background: var(--ana-green); }
    .ana-submission-track .pending { background: var(--ana-amber); }
    .ana-grouped-chart { display: grid; gap: 15px; padding: 16px 15px 10px; }
    .ana-grouped-row { display: grid; grid-template-columns: minmax(110px, 0.7fr) minmax(260px, 2fr); align-items: center; gap: 14px; }
    .ana-grouped-label { overflow: hidden; color: var(--ana-slate-700); font-size: 12px; font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
    .ana-grouped-bars { display: grid; gap: 4px; }
    .ana-grouped-bars span { position: relative; display: block; min-width: 2px; height: 15px; border-radius: 0 6px 6px 0; }
    .ana-grouped-bars b { position: absolute; left: calc(100% + 7px); top: 50%; transform: translateY(-50%); color: var(--ana-slate-600); font-size: 10px; font-weight: 750; white-space: nowrap; }
    .ana-grouped-bars .average { background: var(--ana-blue); }.ana-grouped-bars .highest { background: var(--ana-green); }.ana-grouped-bars .lowest { background: var(--ana-amber); }
    .ana-chart-legend { display: flex; flex-wrap: wrap; gap: 8px 18px; padding: 0 15px 14px; color: var(--ana-slate-600); font-size: 11px; font-weight: 700; }
    .ana-chart-legend span::before { content: ''; display: inline-block; width: 9px; height: 9px; margin-right: 5px; border-radius: 3px; background: currentColor; vertical-align: -1px; }
    .ana-chart-legend .average { color: var(--ana-blue); }.ana-chart-legend .highest,.ana-chart-legend .reviewed { color: var(--ana-green); }.ana-chart-legend .lowest,.ana-chart-legend .pending { color: var(--ana-amber); }
    .ana-submission-report { display: grid; gap: 13px; padding: 15px; }
    .ana-inline-empty { color: var(--ana-slate-500); font-size: 11px; font-weight: 600; }

    .ana-text-link { display: inline-flex; align-items: center; gap: 5px; color: var(--ana-blue); font-size: 11px; font-weight: 750; text-decoration: none; white-space: nowrap; }
    .ana-text-link:hover { color: var(--ana-blue-dark); text-decoration: underline; }

    .ana-compact-empty { display: flex; align-items: center; gap: 8px; padding: 16px 15px; color: var(--ana-slate-500); font-size: 11.5px; font-weight: 600; }
    .ana-compact-empty i { color: var(--ana-slate-400); font-size: 16px; }
    .ana-empty-state { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 12px; padding: 24px; border: 1px dashed var(--ana-slate-300); border-radius: 12px; background: #fff; text-align: left; }
    .ana-empty-icon { color: var(--ana-blue); font-size: 20px; }
    .ana-empty-state h2 { margin: 0; color: var(--ana-slate-900); font-size: 14px; font-weight: 800; }
    .ana-empty-state p { margin: 3px 0 0; color: var(--ana-slate-500); font-size: 11.5px; font-weight: 550; }

    /* Historical Analytics presentation: spacious header, segmented report filter, and larger report cards. */
    .ana-head { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; flex-wrap:wrap; margin-bottom:22px; }
    .ana-head-left { min-width:0; flex:1; }.ana-head-actions { display:flex; align-items:center; gap:10px; }
    .ana-head .ana-title { font-size:26px; font-weight:850; letter-spacing:-.035em; margin:0 0 5px; }.ana-head .ana-subtitle { font-size:14px; font-weight:600; margin:0; }
    .ana-range-pill { display:inline-flex; align-items:center; gap:7px; background:#fff; border:1px solid #bfdbfe; color:#2563eb; border-radius:999px; padding:8px 14px; font-size:12px; font-weight:800; white-space:nowrap; box-shadow:0 2px 8px rgba(37,99,235,.08); }
    .ana-filter { background:#fff; border:1px solid #e2e8f0; border-radius:18px; padding:18px 20px; box-shadow:0 6px 20px rgba(15,23,42,.05); margin-bottom:22px; }
    .ana-filter-row { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; align-items:end; }.ana-filter-row-secondary { margin-top:16px; grid-template-columns:repeat(2,minmax(0,1fr)); max-width:760px; }
    .ana-flabel { display:block; font-size:10px; font-weight:900; color:var(--ana-slate-400); text-transform:uppercase; letter-spacing:.07em; margin-bottom:8px; }.ana-input { width:100%; border:1px solid var(--ana-slate-300); border-radius:11px; padding:10px 12px; font-size:13px; font-weight:700; color:var(--ana-slate-700); background:#fff; outline:none; min-height:42px; }.ana-input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
    .ana-daterange { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-top:16px; max-width:460px; }.ana-reset-link { margin-top:12px; }
    .ana-identity { margin-bottom:22px; }
    .ana-metric { border-radius:18px; padding:20px; min-height:105px; }.ana-metrics { margin-bottom:22px; }
    .ana-panel { border-radius:18px; box-shadow:0 6px 20px rgba(15,23,42,.05); }.ana-panel-head { padding:16px 18px; }.ana-panel-head h2{font-size:15px}.ana-panel-head p{font-size:12px}
    .ana-head { margin-bottom:20px; }.ana-head .ana-title { font-size:26px; font-weight:850; }.ana-head .ana-subtitle { font-size:14px; font-weight:600; }
    .ana-filter-card { overflow:hidden; margin-bottom:22px; }.ana-filter-card > .ana-filter { margin:0; border:0; border-radius:0; box-shadow:none; padding:18px 20px 12px; }
    .ana-filter-card > .ana-identity { margin:0; border:0; border-top:1px solid #f1f5f9; border-radius:0; box-shadow:none; padding:14px 20px 16px; }
    .ana-metric { min-height:120px; padding:16px 17px; border-radius:16px; transition:.15s ease; }.ana-metric:hover { transform:translateY(-3px); border-color:#bfdbfe; box-shadow:0 12px 24px rgba(15,23,42,.08); }.ana-metric-icon { width:40px; height:40px; border-radius:12px; margin-bottom:12px; background:var(--ana-blue-soft); font-size:19px; }.ana-metric-label { font-size:11px; }.ana-metric-value { font-size:27px; }.ana-metrics { gap:16px; margin-bottom:24px; }
    .ana-report-stack { gap:24px; }.ana-panel-head h2 { font-size:15px; font-weight:850; }.ana-panel-head p { font-size:12px; font-weight:600; }.ana-panel-head { padding:16px 20px; }
    .ana-outcome-visual { grid-template-columns: minmax(260px, 1fr) minmax(420px, 1.5fr); min-height:250px; padding:24px 20px; }.ana-donut-wrap { display:flex; justify-content:center; }.ana-donut { width:190px; height:190px; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:50%; background:conic-gradient(var(--ana-green) 0 var(--present), var(--ana-amber) var(--present) calc(var(--present) + var(--late)), var(--ana-red) calc(var(--present) + var(--late)) calc(var(--present) + var(--late) + var(--absent)), var(--ana-slate-300) calc(var(--present) + var(--late) + var(--absent)) 100%); position:relative; }.ana-donut::after { content:''; position:absolute; inset:24px; border-radius:50%; background:#fff; }.ana-donut strong,.ana-donut span { position:relative; z-index:1; }.ana-donut strong { font-size:28px; font-weight:900; color:var(--ana-slate-900); }.ana-donut span { font-size:11px; font-weight:700; color:var(--ana-slate-500); }.ana-status-grid { gap:10px; }.ana-status-row { font-size:12px; padding:10px 11px; }
    .ana-session-chart { min-height:300px; padding:26px 24px 18px; gap:24px; }.ana-session-column { height:250px; grid-template-rows:24px 1fr 34px 20px; font-size:12px; }.ana-session-column strong { font-size:13px; }.ana-session-track { background:repeating-linear-gradient(to top,transparent 0,transparent 19%,#f1f5f9 20%); }.ana-session-track span { width:42px; background:var(--ana-blue); border-radius:8px 8px 0 0; }
    .ana-table-title { padding:14px 20px 10px; font-size:11px; }.ana-table { font-size:13.5px; }.ana-table th { padding:11px 18px; font-size:11px; }.ana-table td { padding:14px 18px; }.ana-table tbody tr:hover { background:#fbfcfe; }
    .ana-grouped-chart { padding:24px 20px 14px; gap:20px; }.ana-grouped-row { grid-template-columns:150px 1fr; gap:18px; }.ana-grouped-bars { height:220px; display:flex; align-items:flex-end; gap:8px; padding:0 10px; border-bottom:1px solid var(--ana-slate-300); background:repeating-linear-gradient(to top,transparent 0,transparent 19%,#f1f5f9 20%); }.ana-grouped-bars span { width:24px; min-height:3px; border-radius:6px 6px 0 0; }.ana-grouped-bars b { left:50%; top:-18px; transform:translateX(-50%); font-size:10px; }.ana-grouped-bars .average,.ana-grouped-bars .highest,.ana-grouped-bars .lowest { height:var(--bar-height,0); }.ana-chart-legend { padding:0 20px 18px; font-size:12px; }
    .ana-attendance-charts { display:grid; grid-template-columns:minmax(260px,1fr) minmax(420px,1.4fr); gap:24px; padding:24px 20px; align-items:center; }.ana-attendance-legend { display:grid; gap:8px; margin-top:18px; padding:0; }.ana-attendance-legend .recorded::before{background:var(--ana-green)}.ana-attendance-legend .not-recorded::before{background:var(--ana-slate-300)}
    .ana-outcome-chart { min-height:280px; display:flex; align-items:flex-end; justify-content:space-around; gap:18px; padding:20px 14px 0; border-bottom:1px solid var(--ana-slate-300); background:repeating-linear-gradient(to top,transparent 0,transparent 19%,#f1f5f9 20%); }.ana-outcome-column { height:250px; flex:1; display:grid; grid-template-rows:22px 1fr 36px 20px; gap:5px; align-items:end; text-align:center; color:var(--ana-slate-600); font-size:12px; }.ana-outcome-column strong{font-size:13px;color:var(--ana-slate-900)}.ana-outcome-column b{font-size:11px;line-height:1.1}.ana-outcome-column small{font-size:11px;color:var(--ana-slate-500)}.ana-outcome-track{height:100%;display:flex;align-items:flex-end;justify-content:center}.ana-outcome-track span{width:42px;min-height:3px;border-radius:8px 8px 0 0;transition:height .25s ease}.ana-outcome-track .present{background:var(--ana-green)}.ana-outcome-track .late{background:var(--ana-amber)}.ana-outcome-track .absent{background:var(--ana-red)}.ana-outcome-track .not-recorded{background:var(--ana-slate-300)}
    .ana-table-divider{border-top:1px solid #f1f5f9;padding-top:4px}.ana-lms-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(300px,1fr);gap:24px;margin-top:24px;align-items:start}.ana-submission-counts{display:block;color:var(--ana-slate-500);font-size:12px;font-weight:600}.ana-submission-report{gap:22px;padding:22px 20px 12px}
    .ana-report-stack,.ana-quiz-section{display:grid;gap:24px}.ana-chart-box{position:relative;min-height:300px;padding:20px 24px}.ana-canvas-wrap{position:relative;width:240px;height:240px;margin:auto}.ana-quiz-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:20px 24px}.ana-quiz-summary .ana-quiz-metric{display:flex;flex-direction:column;gap:0;min-width:0;min-height:120px;padding:16px 17px}.ana-quiz-metric .ana-metric-value{margin-top:8px;overflow-wrap:anywhere}.ana-quiz-metric .ana-metric-value.is-title{font-size:18px;line-height:1.25}.ana-quiz-metric-detail{display:block;margin-top:7px;color:var(--ana-blue);font-size:13px;font-weight:850}.ana-quiz-chart-box{height:clamp(300px,calc(180px + 5vw),520px)}.ana-pagination{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:16px 20px;color:var(--ana-slate-600);font-size:12px}.ana-pagination label{display:inline-flex;align-items:center;gap:6px}.ana-pagination select,.ana-pagination button{border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:6px 9px;color:var(--ana-slate-700);font:inherit}.ana-pagination .ana-page-range{margin-left:auto}.ana-pagination button:disabled{opacity:.45;cursor:not-allowed}
    .ana-attendance-badge{display:inline-flex;align-items:center;justify-content:center;min-width:92px;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:800;line-height:1.2}.ana-attendance-badge.recorded{color:#15803d;background:rgba(22,163,74,.1)}.ana-attendance-badge.not-recorded{color:#64748b;background:rgba(148,163,184,.16)}

    @media (max-width: 991px) {
        .ana-toolbar { flex-wrap: wrap; }
        .ana-filter { flex: 1 1 calc(50% - 10px); }
        .ana-filter-training { flex-basis: 100%; }
        .ana-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ana-outcome-visual { grid-template-columns: 1fr; gap: 12px; }
        .ana-overview-grid { grid-template-columns: 1fr; }
        .ana-filter-row { grid-template-columns:1fr; }
        .ana-attendance-charts,.ana-lms-grid { grid-template-columns:1fr; }
    }

    @media (max-width: 767px) {
        .ana-page { padding: 18px 16px 32px; }
        .ana-title { font-size: 24px; }
        .ana-select, .ana-sort-select, .ana-btn, .ana-reset-link { min-height: 44px; }
        .ana-toolbar, .ana-identity, .ana-action, .ana-panel-head { align-items: stretch; flex-direction: column; }
        .ana-filter, .ana-filter-training { width: 100%; min-width: 0; flex-basis: auto; }
        .ana-reset-link, .ana-identity .ana-btn, .ana-action .ana-btn { width: 100%; }
        .ana-identity h2 { white-space: normal; }
        .ana-panel-tools { width: 100%; align-items: stretch; flex-direction: column; }
        .ana-sort-control { width: 100%; }
        .ana-status-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ana-grouped-row { grid-template-columns: 1fr; gap: 5px; }
        .ana-grouped-bars b { font-size: 9px; }
        .ana-quiz-summary { grid-template-columns:repeat(2,minmax(0,1fr)); margin:16px; }
    }

    @media (max-width: 480px) {
        .ana-metrics { grid-template-columns: 1fr; }
        .ana-metric { min-height: 0; }
        .ana-status-grid { grid-template-columns: 1fr; }
        .ana-quiz-summary { grid-template-columns:1fr; }
        .ana-empty-state { align-items: flex-start; }
    }
    .ana-report-container { display:grid; gap:24px; }
    .ana-report-head { align-items:flex-start; }
    .ana-report-control { min-width:220px; }
    .ana-report-control .ana-flabel { margin-bottom:5px; }
    .ana-quiz-chart-box.is-single { height:210px; min-height:180px; }
    .ana-quiz-chart-box.is-ranking { height:clamp(220px, calc(var(--quiz-count) * 56px), 520px); min-height:220px; }
    .ana-chart-note { margin:0 24px 16px; color:var(--ana-slate-500); font-size:12px; }
    @media (max-width:767px) { .ana-report-head { flex-direction:column; } .ana-report-control { width:100%; min-width:0; } .ana-report-control .ana-input { min-height:44px; } }

    /* Trainer Analytics follows the approved Admin Analytics visual system without affecting Admin pages. */
    .ana-shell {
        --trainer-analytics-blue: #60a5fa;
        --trainer-analytics-blue-hover: #3b82f6;
    }
    .ana-shell .ana-head { margin-bottom: 20px; }
    .ana-shell .ana-title { letter-spacing: -.035em; }
    .ana-shell .ana-subtitle { max-width: 720px; line-height: 1.5; }

    .ana-shell .ana-filter-card {
        margin-bottom: 24px;
        border-radius: 18px;
        box-shadow: 0 6px 20px rgba(15, 23, 42, .05);
    }
    .ana-shell .ana-filter-card > .ana-panel-head { padding: 17px 20px; }
    .ana-shell .ana-filter-card > .ana-filter { padding: 19px 20px 16px; }
    .ana-shell .ana-filter-card > .ana-identity { padding: 16px 20px 18px; }
    .ana-shell .ana-filter-row {
        grid-template-columns: minmax(280px, 1.4fr) minmax(190px, .8fr);
        gap: 16px;
    }
    .ana-shell .ana-filter-row > div { min-width: 0; }
    .ana-shell .ana-filter-row > div:only-child { grid-column: 1 / -1; }
    .ana-shell .ana-flabel { margin-bottom: 7px; font-size: 10.5px; }
    .ana-shell .ana-input { min-height: 42px; }
    .ana-shell .ana-identity h2 {
        overflow: visible;
        overflow-wrap: anywhere;
        text-overflow: clip;
        white-space: normal;
    }
    .ana-shell .ana-type-badge,
    .ana-shell .ana-status-badge { padding: 3px 8px; font-size: 9px; }
    .ana-shell .ana-identity-meta { font-size: 12px; }

    .ana-shell .ana-metrics { gap: 16px; margin-bottom: 24px; }
    .ana-shell .ana-metric {
        min-height: 148px;
        flex-direction: column;
        align-items: flex-start;
        gap: 0;
        padding: 17px 18px;
        border-radius: 16px;
        box-shadow: var(--ana-shadow);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .ana-shell .ana-metric:hover {
        transform: translateY(-3px);
        border-color: #bfdbfe;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
    }
    .ana-shell .ana-metric-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        margin: 0 0 13px;
        border-radius: 12px;
        background: var(--ana-blue-soft);
        color: var(--ana-blue);
        font-size: 19px;
    }
    .ana-shell .ana-tone-green .ana-metric-icon { background: var(--ana-green-soft); color: var(--ana-green); }
    .ana-shell .ana-tone-amber .ana-metric-icon { background: var(--ana-amber-soft); color: var(--ana-amber); }
    .ana-shell .ana-metric-copy { min-width: 0; }
    .ana-shell .ana-metric-label { font-size: 11px; line-height: 1.35; }
    .ana-shell .ana-metric-value { margin-top: 5px; font-size: 28px; line-height: 1; }
    .ana-shell .ana-metric-note { margin-top: 7px; color: var(--ana-slate-500); font-size: 12px; line-height: 1.4; }

    .ana-shell .ana-report-container { gap: 26px; }
    .ana-shell .ana-panel { border-radius: 18px; box-shadow: 0 6px 20px rgba(15, 23, 42, .05); }
    .ana-shell .ana-report-head { align-items: center; padding: 18px 20px; }
    .ana-shell .ana-report-head .ana-eyebrow { margin-bottom: 5px; }
    .ana-shell .ana-report-head h2 { font-size: 16px; }
    .ana-shell .ana-report-head p { margin-top: 4px; font-size: 12.5px; line-height: 1.45; }
    .ana-shell .ana-rate { padding: 6px 10px; font-size: 10.5px; }

    .ana-shell .ana-attendance-charts {
        grid-template-columns: minmax(300px, .85fr) minmax(500px, 1.55fr);
        align-items: stretch;
        gap: 28px;
        padding: 26px 24px 28px;
    }
    .ana-shell .trainer-analytics-donut-box,
    .ana-shell .trainer-analytics-bar-box { min-width: 0; min-height: 0; }
    .ana-shell .trainer-analytics-donut-box { padding: 0; }
    .ana-shell .trainer-analytics-bar-box {
        min-height: 320px;
        padding: 36px 0 0 28px;
        border-left: 1px solid var(--ana-slate-100);
    }
    .ana-shell .trainer-analytics-chart-title {
        margin: 0 0 16px;
        color: var(--ana-slate-700);
        font-size: 12px;
        font-weight: 850;
        letter-spacing: .045em;
        line-height: 1.35;
        text-align: center;
        text-transform: uppercase;
    }
    .ana-shell .trainer-analytics-bar-box .trainer-analytics-chart-title {
        position: absolute;
        top: 0;
        right: 0;
        left: 28px;
        text-align: left;
    }
    .ana-shell .ana-canvas-wrap { width: 248px; height: 248px; max-width: 100%; }
    .ana-shell .trainer-analytics-bar-box > canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
        max-width: 100%;
        max-height: 320px;
    }
    .ana-shell .ana-attendance-legend {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px 14px;
        margin: 17px auto 0;
        padding: 0;
        font-size: 11.5px;
    }
    .ana-shell .ana-attendance-legend span { min-width: 0; line-height: 1.4; }
    .ana-shell .ana-attendance-legend .recorded { color: var(--ana-green); }
    .ana-shell .ana-attendance-legend .not-recorded { color: var(--ana-slate-400); }

    .ana-shell .ana-table-divider { padding-top: 6px; }
    .ana-shell .ana-table-title { padding: 15px 20px 11px; color: var(--ana-slate-700); font-size: 11.5px; }
    .ana-shell .ana-table-wrap {
        max-width: 100%;
        overflow-x: auto;
        scrollbar-gutter: stable;
    }
    .ana-shell .ana-table { min-width: 720px; font-size: 13.5px; }
    .ana-shell .ana-table th {
        padding: 12px 16px;
        color: var(--ana-slate-600);
        font-size: 11px;
        line-height: 1.35;
    }
    .ana-shell .ana-table td {
        padding: 14px 16px;
        color: var(--ana-slate-600);
        font-variant-numeric: tabular-nums;
        line-height: 1.45;
    }
    .ana-shell .ana-table tbody tr { transition: background-color .15s ease; }
    .ana-shell .ana-table tbody tr:hover { background: #fbfdff; }
    .ana-shell .ana-table :is(th, td):first-child {
        min-width: 190px;
        max-width: 340px;
        overflow: visible;
        overflow-wrap: anywhere;
        text-overflow: clip;
        white-space: normal;
    }
    .ana-shell .ana-table td:first-child { color: var(--ana-slate-900); font-weight: 750; }
    .ana-shell .ana-pagination {
        min-height: 62px;
        padding: 14px 20px;
        border-top: 1px solid var(--ana-slate-100);
        background: #fbfdff;
        font-size: 12px;
    }
    .ana-shell .ana-pagination select,
    .ana-shell .ana-pagination button {
        min-height: 34px;
        padding: 6px 10px;
        border-radius: 9px;
        transition: border-color .15s ease, background-color .15s ease, color .15s ease;
    }
    .ana-shell .ana-pagination button:not(:disabled):hover {
        border-color: #bfdbfe;
        background: var(--ana-blue-soft);
        color: var(--ana-blue-dark);
    }
    .ana-shell .ana-pagination :is(select, button):focus-visible {
        outline: 3px solid rgba(37, 99, 235, .18);
        outline-offset: 2px;
    }

    .ana-shell .ana-quiz-summary { gap: 16px; margin: 20px 24px; }
    .ana-shell .ana-quiz-summary .ana-quiz-metric { min-height: 120px; padding: 16px 17px; }
    .ana-shell .ana-quiz-metric-detail { color: var(--trainer-analytics-blue-hover); }
    .ana-shell .ana-quiz-chart-box { margin: 0 24px 24px; padding: 0; }
    .ana-shell .ana-chart-note { line-height: 1.45; }

    @media (max-width: 1100px) {
        .ana-shell .ana-attendance-charts { grid-template-columns: minmax(260px, .8fr) minmax(420px, 1.4fr); gap: 22px; }
    }

    @media (max-width: 991px) {
        .ana-shell .ana-filter-row { grid-template-columns: 1fr; }
        .ana-shell .ana-attendance-charts { grid-template-columns: 1fr; }
        .ana-shell .trainer-analytics-bar-box {
            min-height: 320px;
            padding: 60px 0 0;
            border-top: 1px solid var(--ana-slate-100);
            border-left: 0;
        }
        .ana-shell .trainer-analytics-bar-box .trainer-analytics-chart-title { top: 24px; left: 0; }
    }

    @media (max-width: 767px) {
        .ana-shell .ana-head { margin-bottom: 16px; }
        .ana-shell .ana-filter-card > .ana-panel-head,
        .ana-shell .ana-filter-card > .ana-filter,
        .ana-shell .ana-filter-card > .ana-identity { padding-right: 16px; padding-left: 16px; }
        .ana-shell .ana-report-head { align-items: stretch; }
        .ana-shell .ana-attendance-charts { padding: 20px 16px; }
        .ana-shell .ana-table-title,
        .ana-shell .ana-pagination { padding-right: 16px; padding-left: 16px; }
        .ana-shell .ana-quiz-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 16px; }
        .ana-shell .ana-quiz-chart-box { margin: 0 16px 20px; }
    }

    @media (max-width: 520px) {
        .ana-shell .ana-metrics,
        .ana-shell .ana-quiz-summary { grid-template-columns: 1fr; }
        .ana-shell .ana-metric { min-height: 0; }
        .ana-shell .ana-attendance-legend { grid-template-columns: 1fr; }
        .ana-shell .ana-pagination .ana-page-range { width: 100%; margin-left: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        .ana-shell .ana-metric,
        .ana-shell .ana-table tbody tr,
        .ana-shell .ana-pagination :is(select, button) { transition: none; }
    }
</style>
