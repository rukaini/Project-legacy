<style>
    body { background: #f8fafc; color: var(--lw-slate-900); }
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    .lt-workspace, .lt-workspace *, .lt-workspace *::before, .lt-workspace *::after { box-sizing: border-box; }
    .lt-workspace { padding-top: 20px; padding-bottom: 46px; color: var(--lw-slate-900); font-family: inherit; }

    .lt-page-head { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 12px; }
    .lt-eyebrow, .lt-label { margin: 0; color: var(--lw-slate-500); font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    .lt-source-note { display: flex; align-items: flex-start; gap: 8px; max-width: 470px; padding: 9px 12px; border: 1px solid #bfdbfe; border-radius: 9px; background: var(--lw-primary-soft); color: #1e40af; font-size: 11.5px; font-weight: 650; line-height: 1.45; }
    .lt-source-note.lt-inline-note { max-width: none; margin: 12px 18px; }

    .lt-tabs { display: flex; gap: 20px; margin-bottom: 14px; border-bottom: 1px solid var(--lw-border); overflow-x: auto; }
    .lt-tab { display: inline-flex; align-items: center; min-height: 38px; padding: 0 1px 9px; border-bottom: 2px solid transparent; color: var(--lw-slate-500); font-size: 12.5px; font-weight: 700; text-decoration: none; white-space: nowrap; transition: color .15s ease, border-color .15s ease; }
    .lt-tab:hover { color: var(--lw-slate-900); }
    .lt-tab.is-active { border-color: var(--lw-primary); color: var(--lw-primary); font-weight: 850; }

    .lt-period-bar { display: flex; align-items: center; gap: 10px; min-height: 42px; margin-bottom: 16px; padding: 8px 12px; border: 1px solid var(--lw-border); border-radius: 10px; background: #fff; box-shadow: 0 2px 8px rgba(15, 23, 42, .025); }
    .lt-period-bar label, .lt-filter { display: inline-flex; align-items: center; gap: 7px; margin: 0; color: var(--lw-slate-600); font-size: 11.5px; font-weight: 750; }
    .lt-period-bar select, .lt-period-bar input, .lt-filter select { height: 34px; padding: 0 28px 0 9px; border: 1px solid var(--lw-slate-300); border-radius: 7px; background: #fff; color: var(--lw-slate-700); font: inherit; outline: none; }
    .lt-period-bar select:focus, .lt-period-bar input:focus, .lt-filter select:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    .lt-custom-period { display: flex; align-items: center; gap: 8px; }
    .lt-custom-period.is-hidden { display: none; }
    .lt-custom-period button { min-height: 34px; padding: 0 12px; border: 1px solid var(--lw-primary); border-radius: 8px; background: var(--lw-primary); color: #fff; font: inherit; font-size: 11.5px; font-weight: 800; cursor: pointer; }
    .lt-custom-period > span { color: var(--lw-red); font-size: 11px; font-weight: 750; }

    .lt-layout { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 18px; align-items: start; transition: grid-template-columns .18s ease; }
    .lt-layout.is-collapsed { grid-template-columns: 44px minmax(0, 1fr); }
    .lt-navigator { position: sticky; top: 78px; width: 320px; max-height: calc(100vh - 102px); min-height: 42px; align-self: start; transition: width .18s ease; }
    .lt-nav-expanded { display: flex; width: 320px; max-height: inherit; min-height: 0; flex-direction: column; overflow: hidden; border: 1px solid var(--lw-border); border-radius: 18px; background: #fff; box-shadow: var(--lw-shadow); opacity: 1; visibility: visible; transform: translateX(0); pointer-events: auto; transition: opacity .18s ease, transform .18s ease, visibility 0s linear 0s; }
    .lt-navigator.is-collapsed { width: 44px; }
    .lt-navigator.is-collapsed .lt-nav-expanded { opacity: 0; visibility: hidden; transform: translateX(-6px); pointer-events: none; transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s; }
    .lt-nav-show { position: absolute; inset: 0 auto auto 0; display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 42px; border: 1px solid var(--lw-border); border-radius: 10px; background: #fff; color: var(--lw-primary); box-shadow: var(--lw-shadow); font: inherit; cursor: pointer; opacity: 0; visibility: hidden; transform: translateX(6px); pointer-events: none; transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s, border-color .15s ease, box-shadow .15s ease; }
    .lt-navigator.is-collapsed .lt-nav-show { opacity: 1; visibility: visible; transform: translateX(0); pointer-events: auto; transition-delay: 0s; }
    .lt-nav-show:hover { border-color: #93c5fd; box-shadow: 0 8px 22px rgba(37, 99, 235, .12); transform: translateY(-1px); }
    .lt-nav-head { flex-shrink: 0; padding: 14px 16px; border-bottom: 1px solid var(--lw-slate-100); background: #fafbfc; }
    .lt-nav-title-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .lt-nav-title-row > div { display: flex; align-items: center; gap: 7px; min-width: 0; }
    .lt-nav-count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 20px; padding: 0 6px; border-radius: 999px; background: var(--lw-slate-200); color: var(--lw-slate-700); font-size: 10px; font-weight: 850; }
    .lt-collapse { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; flex-shrink: 0; padding: 0; border: 1px solid var(--lw-border); border-radius: 7px; background: var(--lw-slate-50); color: var(--lw-slate-500); cursor: pointer; transition: .15s ease; }
    .lt-collapse:hover { background: var(--lw-slate-100); color: var(--lw-slate-900); }
    .lt-pivot { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; margin-top: 10px; padding: 3px; border-radius: 8px; background: var(--lw-slate-100); }
    .lt-pivot a { padding: 7px 5px; border-radius: 6px; color: var(--lw-slate-500); font-size: 10.5px; font-weight: 800; text-align: center; text-decoration: none; }
    .lt-pivot a.is-active { background: #fff; color: var(--lw-primary); box-shadow: 0 1px 3px rgba(15, 23, 42, .09); }

    .lt-search { position: relative; display: block; margin-top: 10px; }
    .lt-search > i { position: absolute; top: 50%; left: 10px; z-index: 1; transform: translateY(-50%); color: var(--lw-slate-400); font-size: 13px; pointer-events: none; }
    .lt-search input { width: 100%; height: 38px; padding: 7px 34px 7px 32px; border: 1px solid var(--lw-border); border-radius: 9px; background: #fff; color: var(--lw-slate-900); font: inherit; font-size: 12.5px; outline: none; transition: .15s ease; }
    .lt-search input:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    .lt-search input[type="search"]::-webkit-search-cancel-button, .lt-search input[type="search"]::-webkit-search-decoration { display: none; appearance: none; -webkit-appearance: none; }
    .lt-search-clear { position: absolute; top: 50%; right: 7px; display: inline-flex; align-items: center; justify-content: center; width: 25px; height: 25px; padding: 0; transform: translateY(-50%); border: 0; border-radius: 6px; background: transparent; color: var(--lw-slate-400); cursor: pointer; }
    .lt-search-clear:hover { background: var(--lw-slate-100); color: var(--lw-slate-700); }
    .lt-search-clear.is-hidden { display: none; }
    .lt-search-clear i { position: static; transform: none; color: inherit; font-size: 12px; }
    .lt-result-search { width: min(260px, 100%); margin: 0 auto 0 0; }

    .lt-nav-list { position: relative; flex: 1 1 auto; min-height: 0; overflow-x: hidden; overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; scrollbar-color: var(--lw-slate-300) transparent; }
    .lt-nav-item { display: flex; align-items: center; gap: 9px; padding: 11px 14px; border-bottom: 1px solid var(--lw-slate-100); border-left: 3px solid transparent; color: inherit; text-decoration: none; transition: background-color .15s ease; }
    .lt-nav-item:hover { background: var(--lw-slate-50); }
    .lt-nav-item.is-active { border-left-color: var(--lw-primary); background: var(--lw-primary-soft); }
    .lt-nav-icon, .lt-avatar { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; flex: 0 0 auto; border: 1px solid var(--lw-border); border-radius: 9px; background: var(--lw-slate-100); color: var(--lw-slate-700); font-size: 11px; font-weight: 850; }
    .lt-avatar { border-radius: 999px; }
    .lt-avatar.is-large { width: 44px; height: 44px; border-color: #bfdbfe; background: var(--lw-primary-soft); color: var(--lw-primary-dark); font-size: 15px; }
    .lt-nav-copy { min-width: 0; flex: 1; }
    .lt-nav-copy strong { display: block; overflow: hidden; color: var(--lw-slate-900); font-size: 12.5px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
    .lt-nav-copy small { display: block; overflow: hidden; margin-top: 2px; color: var(--lw-slate-500); font-size: 10.5px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
    .lt-nav-empty { padding: 30px 16px; color: var(--lw-slate-500); text-align: center; }
    .lt-nav-empty > i { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background: var(--lw-slate-100); color: var(--lw-slate-500); font-size: 16px; }
    .lt-nav-empty h3 { margin: 9px 0 3px; color: var(--lw-slate-700); font-size: 12.5px; font-weight: 850; line-height: 1.35; }
    .lt-nav-empty p { margin: 0; font-size: 10.5px; font-weight: 600; line-height: 1.45; }
    .lt-nav-limit { margin: 0; padding: 10px 14px; border-top: 1px solid var(--lw-slate-100); color: var(--lw-slate-500); font-size: 10.5px; line-height: 1.4; }
    .lt-module { border-bottom: 1px solid var(--lw-slate-100); }
    .lt-module summary { display: flex; align-items: center; gap: 9px; padding: 11px 14px; list-style: none; cursor: pointer; }
    .lt-module summary::-webkit-details-marker { display: none; }
    .lt-module summary > i { color: var(--lw-slate-500); font-size: 10px; transition: transform .18s ease; }
    .lt-module[open] summary { background: var(--lw-slate-50); }
    .lt-module[open] summary > i { transform: rotate(180deg); }
    .lt-module-items { padding: 4px 9px 10px 43px; }
    .lt-module-items a { display: block; overflow: hidden; padding: 7px 8px; border-radius: 6px; color: var(--lw-slate-700); font-size: 11px; font-weight: 650; text-decoration: none; text-overflow: ellipsis; white-space: nowrap; }
    .lt-module-items a:hover, .lt-module-items a.is-active { background: var(--lw-primary-soft); color: var(--lw-primary-dark); }
    .lt-module-items p { margin: 3px 0; color: var(--lw-slate-500); font-size: 10.5px; line-height: 1.4; }

    .lt-main { position: relative; min-width: 0; min-height: 260px; display: flex; flex-direction: column; gap: 14px; }
    [data-tracking-region="activity-results"], [data-tracking-region="engagement-results"], [data-tracking-region="quiz-results"], [data-tracking-region="attempt-detail"] { position: relative; min-height: 1px; }
    [data-tracking-region].is-loading::after { content: ''; position: absolute; inset: 0; z-index: 20; border-radius: inherit; background: rgba(255, 255, 255, .34); pointer-events: none; }
    .lt-card { overflow: hidden; border: 1px solid var(--lw-border); border-radius: 18px; background: #fff; box-shadow: var(--lw-shadow); }
    .lt-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 16px 20px; border-bottom: 1px solid var(--lw-slate-100); }
    .lt-card-head h2, .lt-person-head h2 { margin: 0; color: var(--lw-slate-900); font-size: 15px; font-weight: 850; line-height: 1.3; }
    .lt-card-head p, .lt-person-head p { margin: 3px 0 0; color: var(--lw-slate-500); font-size: 11.5px; font-weight: 600; }
    .lt-person-head { display: flex; align-items: center; gap: 12px; padding: 16px 20px; }
    .lt-person-head > div { min-width: 0; flex: 1; }
    .lt-button { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; padding: 0 13px; border: 1px solid var(--lw-primary); border-radius: 8px; background: var(--lw-primary); color: #fff; font-size: 12px; font-weight: 800; text-decoration: none; transition: background-color .15s ease; }
    .lt-button:hover { background: var(--lw-primary-dark); color: #fff; }

    .lt-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .lt-metrics.is-inside { padding: 14px 18px; border-bottom: 1px solid var(--lw-slate-100); background: var(--lw-slate-50); }
    .lt-metric { min-width: 0; padding: 14px 15px; border: 1px solid var(--lw-border); border-radius: 14px; background: #fff; box-shadow: 0 3px 12px rgba(15, 23, 42, .025); transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease; }
    .lt-metric:hover { transform: translateY(-2px); border-color: #bfdbfe; box-shadow: 0 10px 22px rgba(15, 23, 42, .07); }
    .lt-metric > span { display: block; color: var(--lw-slate-500); font-size: 10px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; }
    .lt-metric strong { display: block; margin-top: 7px; color: var(--lw-slate-900); font-size: 22px; font-weight: 900; letter-spacing: -.02em; }
    .lt-metric strong.is-date { font-size: 16px; }
    .lt-metric small { display: block; margin-top: 3px; color: var(--lw-slate-500); font-size: 10.5px; font-weight: 600; }

    .lt-activity-list { display: block; }
    .lt-activity { display: grid; grid-template-columns: 32px minmax(0, 1fr) auto; align-items: start; gap: 10px; padding: 12px 20px; border-bottom: 1px solid var(--lw-slate-100); }
    .lt-activity:last-child { border-bottom: 0; }
    .lt-activity-icon { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 9px; background: var(--lw-primary-soft); color: var(--lw-primary); }
    .lt-activity strong { color: var(--lw-slate-900); font-size: 12.5px; font-weight: 800; }
    .lt-activity p { margin: 2px 0 0; color: var(--lw-slate-500); font-size: 11px; }
    .lt-activity time { color: var(--lw-slate-500); font-size: 10.5px; white-space: nowrap; }

    .lt-result-head { align-items: center; }
    .lt-toolbar { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 12px 20px; border-bottom: 1px solid var(--lw-slate-100); background: var(--lw-slate-50); }
    .lt-table-scroll { overflow-x: auto; overflow-y: visible; }
    .lt-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .lt-table th { padding: 13px 18px; border-bottom: 1px solid var(--lw-border); background: #fff; color: var(--lw-slate-400); font-size: 10px; font-weight: 900; letter-spacing: .09em; text-align: left; text-transform: uppercase; white-space: nowrap; }
    .lt-table th.t-center, .lt-table td.t-center { text-align: center; }
    .lt-table td { padding: 11px 18px; border-bottom: 1px solid var(--lw-slate-100); color: var(--lw-slate-700); vertical-align: middle; }
    .lt-table tbody tr:hover { background: var(--lw-slate-50); }
    .lt-person-cell { display: flex; align-items: center; gap: 9px; min-width: 180px; }
    .lt-person-cell > span:last-child { min-width: 0; }
    .lt-person-cell strong, .lt-table td > strong { display: block; color: var(--lw-slate-900); font-size: 12.5px; font-weight: 800; }
    .lt-person-cell small, .lt-cell-sub { display: block; margin-top: 2px; color: var(--lw-slate-500); font-size: 10.5px; font-weight: 600; }
    .lt-badge { display: inline-flex; align-items: center; justify-content: center; padding: 3px 8px; border: 1px solid transparent; border-radius: 999px; background: var(--lw-slate-100); color: var(--lw-slate-600); font-size: 9.5px; font-weight: 900; white-space: nowrap; }
    .lt-badge.is-open { border-color: #a7f3d0; background: var(--lw-green-soft); color: #047857; }
    .lt-badge.is-closed { border-color: #fecaca; background: var(--lw-red-soft); color: #b91c1c; }
    .lt-badge.is-scheduled, .lt-badge.is-always { border-color: #bfdbfe; background: var(--lw-primary-soft); color: var(--lw-primary-dark); }
    .lt-badge.is-neutral { border-color: var(--lw-border); background: var(--lw-slate-100); color: var(--lw-slate-500); }
    .lt-detail-link { color: var(--lw-primary); font-size: 11.5px; font-weight: 800; text-decoration: none; white-space: nowrap; }
    .lt-detail-link:hover { color: var(--lw-primary-dark); text-decoration: underline; }
    .lt-attempt-layer { position: static; }
    html.lt-attempt-scroll-locked, body.lt-attempt-scroll-locked { overflow: hidden; }
    .lt-attempt-layer.is-open, .lt-attempt-layer.is-opening, .lt-attempt-layer.is-closing { position: fixed; inset: 0; z-index: 10020; }
    .lt-attempt-layer.is-opening::before, .lt-attempt-backdrop { content: ''; position: absolute; inset: 0; background: rgba(15, 23, 42, .42); backdrop-filter: blur(1px); animation: lt-backdrop-in .16s ease-out both; }
    .lt-attempt-backdrop { cursor: pointer; }
    .lt-attempt-layer.is-opening::after { content: ''; position: absolute; inset: 0 0 0 auto; width: min(820px, calc(100vw - 32px)); background: #fff; box-shadow: -20px 0 55px rgba(15, 23, 42, .2); animation: lt-drawer-in .18s ease-out both; }
    .lt-attempt-drawer { position: absolute; inset: 0 0 0 auto; display: flex; width: min(820px, calc(100vw - 32px)); max-width: 100%; flex-direction: column; overflow: hidden; background: #fff; box-shadow: -20px 0 55px rgba(15, 23, 42, .22); outline: none; animation: lt-drawer-in .18s ease-out both; }
    .lt-attempt-layer.is-continuing .lt-attempt-drawer, .lt-attempt-layer.is-continuing .lt-attempt-backdrop { animation: none; }
    .lt-attempt-layer.is-closing .lt-attempt-drawer { animation: lt-drawer-out .16s ease-in both; pointer-events: none; }
    .lt-attempt-layer.is-closing .lt-attempt-backdrop { animation: lt-backdrop-out .16s ease-in both; pointer-events: none; }
    .lt-attempt-drawer-head { display: flex; align-items: center; justify-content: space-between; gap: 18px; flex: 0 0 auto; padding: 18px 22px; border-bottom: 1px solid var(--lw-border); background: #fff; }
    .lt-attempt-drawer-head h2 { margin: 2px 0 0; color: var(--lw-slate-900); font-size: 17px; font-weight: 900; line-height: 1.3; }
    .lt-attempt-drawer-head p:not(.lt-eyebrow) { margin: 3px 0 0; color: var(--lw-slate-500); font-size: 11.5px; font-weight: 650; }
    .lt-attempt-drawer-body { min-height: 0; flex: 1 1 auto; overflow-y: auto; overscroll-behavior: contain; }
    .lt-detail-close { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid var(--lw-border); border-radius: 8px; background: #fff; color: var(--lw-slate-500); text-decoration: none; }
    .lt-detail-close:hover { background: var(--lw-slate-100); color: var(--lw-slate-900); }
    .lt-attempt-switcher { display: flex; gap: 7px; padding: 12px 18px; border-bottom: 1px solid var(--lw-slate-100); overflow-x: auto; }
    .lt-attempt-switcher a { display: flex; flex: 0 0 auto; flex-direction: column; gap: 1px; padding: 6px 10px; border: 1px solid var(--lw-slate-300); border-radius: 7px; background: #fff; color: var(--lw-slate-600); font-size: 11px; font-weight: 800; text-decoration: none; }
    .lt-attempt-switcher a.is-active { border-color: #bfdbfe; background: var(--lw-primary-soft); color: var(--lw-primary-dark); }
    .lt-attempt-switcher small { color: var(--lw-slate-500); font-size: 8.5px; font-weight: 750; }
    .lt-attempt-meta { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--lw-slate-100); background: var(--lw-slate-50); }
    .lt-attempt-meta div, .lt-answer-values div { min-width: 0; }
    .lt-attempt-meta span, .lt-answer-values span { display: block; margin-bottom: 3px; color: var(--lw-slate-500); font-size: 9.5px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; }
    .lt-attempt-meta strong, .lt-answer-values strong { color: var(--lw-slate-700); font-size: 11.5px; font-weight: 800; line-height: 1.45; overflow-wrap: anywhere; }
    .lt-review-comparison { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 14px; padding: 12px 18px; border-bottom: 1px solid #fde68a; background: #fffbeb; }
    .lt-review-comparison div span { display: block; color: #92400e; font-size: 9px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
    .lt-review-comparison div strong { color: #78350f; font-size: 12px; font-weight: 900; }
    .lt-review-comparison p { display: flex; grid-column: 1 / -1; align-items: flex-start; gap: 6px; margin: 0; color: #92400e; font-size: 10.5px; font-weight: 650; line-height: 1.4; }
    .lt-answer-values small { display: block; margin-top: 4px; color: var(--lw-slate-500); font-size: 10px; font-weight: 600; line-height: 1.4; }
    .lt-question-nav { position: sticky; top: 0; z-index: 3; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 18px; border-bottom: 1px solid var(--lw-border); background: rgba(255, 255, 255, .97); box-shadow: 0 4px 12px rgba(15, 23, 42, .035); backdrop-filter: blur(8px); }
    .lt-question-nav-summary { flex: 0 0 auto; }
    .lt-question-nav-summary strong { display: block; color: var(--lw-slate-700); font-size: 11px; font-weight: 900; }
    .lt-question-nav-summary span { display: block; margin-top: 2px; color: var(--lw-slate-500); font-size: 9.5px; font-weight: 700; }
    .lt-question-nav-controls { display: flex; align-items: center; justify-content: flex-end; gap: 6px; flex-wrap: wrap; }
    .lt-question-jump { display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 42px; height: 31px; padding: 0 7px; border: 1px solid var(--lw-slate-300); border-radius: 7px; background: var(--lw-slate-100); color: var(--lw-slate-600); font: inherit; font-size: 9.5px; font-weight: 900; cursor: pointer; }
    .lt-question-jump.is-correct { border-color: #a7f3d0; background: var(--lw-green-soft); color: #047857; }
    .lt-question-jump.is-incorrect { border-color: #fecaca; background: var(--lw-red-soft); color: #b91c1c; }
    .lt-question-jump.is-unanswered { border-color: var(--lw-slate-300); background: var(--lw-slate-100); color: var(--lw-slate-600); }
    .lt-question-jump.is-manual { border-color: #fde68a; background: #fffbeb; color: #a16207; }
    .lt-question-jump.is-recorded_unavailable, .lt-question-jump.is-cannot_evaluate { border-color: var(--lw-slate-300); background: #fff; color: var(--lw-slate-600); }
    .lt-question-jump.is-current { box-shadow: 0 0 0 2px #fff, 0 0 0 4px currentColor; }
    .lt-answer-list { display: flex; flex-direction: column; gap: 10px; padding: 14px 18px 18px; }
    .lt-answer-card { padding: 14px; border: 1px solid var(--lw-border); border-radius: 12px; background: #fff; scroll-margin-top: 66px; }
    .lt-answer-card header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: var(--lw-slate-500); font-size: 9.5px; font-weight: 850; text-transform: uppercase; }
    .lt-answer-card header strong { margin-left: auto; color: var(--lw-slate-700); }
    .lt-answer-title-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
    .lt-answer-card h3 { margin: 0; color: var(--lw-slate-900); font-size: 13px; font-weight: 800; line-height: 1.45; }
    .lt-answer-status { display: inline-flex; align-items: center; gap: 5px; flex: 0 0 auto; padding: 4px 8px; border: 1px solid var(--lw-border); border-radius: 999px; background: var(--lw-slate-100); color: var(--lw-slate-600); font-size: 9.5px; font-weight: 900; white-space: nowrap; }
    .lt-answer-status.is-correct { border-color: #a7f3d0; background: var(--lw-green-soft); color: #047857; }
    .lt-answer-status.is-incorrect { border-color: #fecaca; background: var(--lw-red-soft); color: #b91c1c; }
    .lt-answer-status.is-manual { border-color: #fde68a; background: #fffbeb; color: #a16207; }
    .lt-answer-status.is-recorded_unavailable, .lt-answer-status.is-cannot_evaluate { border-color: var(--lw-slate-300); background: var(--lw-slate-100); color: var(--lw-slate-600); }
    .lt-answer-value.is-correct strong { color: #047857; }
    .lt-answer-value.is-incorrect strong { color: #b91c1c; }
    .lt-answer-image { display: block; max-width: min(100%, 520px); max-height: 300px; margin: 0 0 12px; border: 1px solid var(--lw-border); border-radius: 9px; object-fit: contain; }
    .lt-answer-options { display: grid; gap: 7px; margin-bottom: 12px; }
    .lt-answer-option { display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 38px; padding: 8px 10px; border: 1px solid var(--lw-border); border-radius: 8px; background: var(--lw-slate-50); color: var(--lw-slate-700); }
    .lt-answer-option-text { font-size: 11.5px; font-weight: 700; line-height: 1.4; }
    .lt-answer-option strong { display: inline-flex; align-items: center; gap: 5px; flex: 0 0 auto; font-size: 10px; font-weight: 900; }
    .lt-answer-option.is-correct-selected { border-color: #bfdbfe; background: var(--lw-primary-soft); color: var(--lw-primary-dark); }
    .lt-answer-option.is-correct-answer { border-color: #a7f3d0; background: var(--lw-green-soft); color: #047857; }
    .lt-answer-option.is-correct-reference { border-color: var(--lw-slate-300); background: #fff; color: var(--lw-slate-600); }
    .lt-answer-option.is-wrong-selected { border-color: #fecaca; background: var(--lw-red-soft); color: #b91c1c; }
    .lt-answer-option.is-participant-selected-neutral { border-color: #bfdbfe; background: var(--lw-primary-soft); color: var(--lw-primary-dark); }
    .lt-answer-option-label.is-participant { flex-direction: column; align-items: flex-end; gap: 3px; color: inherit; }
    .lt-answer-option-label.is-participant > span { display: inline-flex; align-items: center; gap: 5px; }
    .lt-answer-correctness { padding: 2px 6px; border: 1px solid currentColor; border-radius: 999px; background: #fff; font-size: 9px; line-height: 1.2; }
    .lt-answer-correctness.is-correct { color: #047857; }
    .lt-answer-correctness.is-incorrect { color: #b91c1c; }
    .lt-answer-note { display: flex; align-items: flex-start; gap: 7px; margin: 0; padding: 9px 10px; border-radius: 8px; background: var(--lw-slate-100); color: var(--lw-slate-600); font-size: 10.5px; font-weight: 650; line-height: 1.45; }
    .lt-answer-values { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; padding-top: 11px; border-top: 1px solid var(--lw-slate-100); }

    @keyframes lt-drawer-in { from { transform: translateX(32px); opacity: .75; } to { transform: translateX(0); opacity: 1; } }
    @keyframes lt-drawer-out { from { transform: translateX(0); opacity: 1; } to { transform: translateX(32px); opacity: 0; } }
    @keyframes lt-backdrop-in { from { opacity: 0; } to { opacity: 1; } }
    @keyframes lt-backdrop-out { from { opacity: 1; } to { opacity: 0; } }

    .lt-pagination { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 12px 18px; border-top: 1px solid var(--lw-slate-100); background: var(--lw-slate-50); }
    .lt-rows-control { display: inline-flex; align-items: center; gap: 7px; color: var(--lw-slate-600); font-size: 12px; font-weight: 750; }
    .lt-rows-control select { padding: 5px 24px 5px 8px; border: 1px solid var(--lw-slate-300); border-radius: 7px; background: #fff; color: var(--lw-slate-700); font: inherit; outline: none; }
    .lt-rows-control select:focus { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    .lt-pager-info { color: var(--lw-slate-600); font-size: 12px; font-weight: 700; }
    .lt-pager-info strong { color: var(--lw-slate-900); font-weight: 900; }
    .lt-pager-controls { display: flex; align-items: center; gap: 7px; }
    .lt-pager-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; border: 1px solid var(--lw-slate-300); border-radius: 7px; background: #fff; color: var(--lw-slate-700); font: inherit; font-size: 11px; font-weight: 800; text-decoration: none; }
    .lt-pager-btn:hover { border-color: var(--lw-primary); color: var(--lw-primary); }
    .lt-pager-btn:disabled { opacity: .45; cursor: not-allowed; }
    .lt-pager-page { color: var(--lw-slate-500); font-size: 11px; font-weight: 800; }

    .lt-empty { padding: 52px 22px; color: var(--lw-slate-500); text-align: center; }
    .lt-empty > i { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 12px; background: var(--lw-slate-100); font-size: 18px; }
    .lt-empty h2, .lt-empty h3 { margin: 10px 0 4px; color: var(--lw-slate-700); font-size: 14px; font-weight: 850; }
    .lt-empty p { max-width: 540px; margin: 0 auto; font-size: 11.5px; line-height: 1.5; }
    .lt-empty.is-compact { padding: 36px 20px; }
    .lt-tab:focus-visible, .lt-nav-item:focus-visible, .lt-collapse:focus-visible, .lt-nav-show:focus-visible, .lt-button:focus-visible, .lt-pager-btn:focus-visible, .lt-detail-link:focus-visible, .lt-detail-close:focus-visible, .lt-attempt-switcher a:focus-visible, .lt-question-jump:focus-visible { outline: 3px solid rgba(37, 99, 235, .22); outline-offset: 2px; }

    @media (max-width: 1050px) {
        .lt-layout { grid-template-columns: 280px minmax(0, 1fr); }
        .lt-navigator, .lt-nav-expanded { width: 280px; }
        .lt-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 760px) {
        .lt-workspace { padding: 16px 14px 38px; }
        .lt-page-head { align-items: flex-start; flex-direction: column; }
        .lt-source-note { max-width: none; }
        .lt-period-bar, .lt-custom-period { align-items: flex-start; flex-wrap: wrap; }
        .lt-layout, .lt-layout.is-collapsed { grid-template-columns: 1fr; }
        .lt-navigator, .lt-navigator.is-collapsed { position: static; width: 100%; max-height: none; }
        .lt-nav-expanded, .lt-navigator.is-collapsed .lt-nav-expanded { width: 100%; max-height: none; opacity: 1; visibility: visible; transform: none; pointer-events: auto; }
        .lt-nav-show, .lt-collapse { display: none; }
        .lt-nav-list { max-height: 320px; }
        .lt-person-head { align-items: flex-start; flex-wrap: wrap; }
        .lt-person-head .lt-button { width: 100%; }
        .lt-toolbar { align-items: stretch; flex-direction: column; }
        .lt-result-search, .lt-filter, .lt-filter select { width: 100%; }
        .lt-activity { grid-template-columns: 32px minmax(0, 1fr); }
        .lt-activity time { grid-column: 2; }
        .lt-pagination { align-items: flex-start; }
        .lt-pager-info { width: 100%; order: -1; }
        .lt-attempt-drawer, .lt-attempt-layer.is-opening::after { width: 100%; }
        .lt-question-nav { align-items: flex-start; flex-direction: column; }
        .lt-question-nav-controls { justify-content: flex-start; }
    }
    @media (max-width: 460px) {
        .lt-metrics { grid-template-columns: 1fr; }
        .lt-tabs { gap: 15px; }
        .lt-attempt-meta, .lt-answer-values { grid-template-columns: 1fr; }
        .lt-answer-title-row { flex-direction: column; }
    }
    @media (prefers-reduced-motion: reduce) {
        .lt-layout, .lt-navigator, .lt-nav-expanded, .lt-nav-show, .lt-attempt-drawer, .lt-attempt-backdrop { transition-duration: .01ms !important; animation-duration: .01ms !important; }
    }
</style>
