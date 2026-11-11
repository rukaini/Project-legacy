<style>
    #feedbackDynamicRegion > .ana-head { margin-bottom: 18px; }
    .fb-feedback-shell { display: grid; gap: 16px; }
    .fb-metric-unit { color: var(--ana-slate-400); font-size: 12px; font-weight: 650; letter-spacing: 0; }

    .fb-summary-grid { gap: 14px; margin-bottom: 18px; }
    .fb-summary-card { min-height: 120px; height: 100%; display: block; padding: 16px 17px; border: 1px solid var(--ana-slate-200); border-radius: 16px; background: #fff; box-shadow: 0 6px 20px rgba(15, 23, 42, .05); transition: .15s ease; }
    .fb-summary-card .ana-metric-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin: 0 0 12px; border-radius: 12px; font-size: 19px; }
    .fb-summary-icon-blue { background: var(--ana-blue-soft); color: var(--ana-blue); }
    .fb-summary-icon-green { background: var(--ana-green-soft); color: var(--ana-green); }
    .fb-summary-icon-amber { background: var(--ana-amber-soft); color: var(--ana-amber); }
    .fb-summary-card .ana-metric-label { margin-bottom: 5px; color: var(--ana-slate-500); font-size: 11px; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
    .fb-summary-card .ana-metric-value { margin: 0; color: var(--ana-slate-900); font-size: 27px; font-weight: 900; line-height: 1; }
    .fb-summary-card .ana-metric-note { margin-top: 5px; color: var(--ana-slate-500); font-size: 10.5px; font-weight: 650; line-height: 1.4; }

    .fb-filter-toolbar { padding: 18px 20px 16px; border: 1px solid var(--ana-slate-200); border-radius: 15px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .04); }
    .fb-filter-grid { display: grid; grid-template-columns: minmax(220px, 1.35fr) minmax(160px, .85fr) minmax(160px, .85fr) minmax(220px, 1.2fr); align-items: end; gap: 16px; }
    .fb-filter-field { min-width: 0; }
    .fb-filter-toolbar .ana-flabel { margin-bottom: 7px; }
    .fb-filter-toolbar .ana-input { min-height: 42px; border-radius: 10px; font-size: 12.5px; }
    .fb-filter-toolbar .ana-input:focus-visible { border-color: var(--ana-blue); box-shadow: 0 0 0 3px rgba(37, 99, 235, .11); outline: none; }
    .fb-search-control { position: relative; }
    .fb-search-control > i { position: absolute; z-index: 1; top: 50%; left: 12px; color: var(--ana-slate-400); transform: translateY(-50%); pointer-events: none; }
    .fb-search-control .ana-input { padding-right: 40px; padding-left: 36px; }
    .fb-search-control input[type="search"]::-webkit-search-cancel-button { appearance: none; }
    .fb-search-clear { position: absolute; top: 50%; right: 7px; width: 29px; height: 29px; display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; background: transparent; color: var(--ana-slate-400); cursor: pointer; transform: translateY(-50%); }
    .fb-search-clear:hover { background: var(--ana-slate-100); color: var(--ana-slate-700); }
    .fb-search-clear:focus-visible { outline: 3px solid rgba(37, 99, 235, .16); outline-offset: 1px; }
    .fb-search-clear[hidden], .fb-reset[hidden] { display: none; }
    .fb-custom-dates { margin-top: 16px; }
    .fb-reset { width: auto; margin-top: 10px; border: 0; background: transparent; cursor: pointer; }

    #feedbackResultsRegion { min-width: 0; }
    #feedbackResultsRegion.is-loading, .fb-workspace.is-loading, .fb-training-detail.is-loading { opacity: .58; pointer-events: none; transition: opacity .15s ease; }
    .fb-workspace { min-width: 0; min-height: 0; display: flex; align-items: flex-start; gap: 16px; }
    .fb-training-navigator { position: sticky; top: 78px; width: 360px; min-width: 0; min-height: 42px; max-height: calc(100vh - 102px); flex: 0 0 auto; align-self: flex-start; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--ana-slate-200); border-radius: 14px; background: #fff; box-shadow: 0 6px 20px rgba(15, 23, 42, .05); transition: width .18s ease, border-color .18s ease, box-shadow .18s ease; }
    .fb-training-navigator.is-collapsed { width: 44px; max-height: none; overflow: visible; border-color: transparent; background: transparent; box-shadow: none; }
    .fb-navigator-expanded { width: 360px; min-height: 0; max-height: inherit; flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; opacity: 1; visibility: visible; transform: translateX(0); pointer-events: auto; transition: opacity .18s ease, transform .18s ease, visibility 0s linear 0s; }
    .fb-training-navigator.is-collapsed .fb-navigator-expanded { opacity: 0; visibility: hidden; transform: translateX(-6px); pointer-events: none; transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s; }
    .fb-navigator-collapsed { position: absolute; inset: 0 auto auto 0; display: flex; align-items: flex-start; justify-content: flex-start; opacity: 0; visibility: hidden; transform: translateX(6px); pointer-events: none; transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s; }
    .fb-training-navigator.is-collapsed .fb-navigator-collapsed { opacity: 1; visibility: visible; transform: translateX(0); pointer-events: auto; transition-delay: 0s; }
    .fb-navigator-expand { width: 44px; height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--ana-slate-200); border-radius: 10px; background: #fff; color: var(--ana-blue); box-shadow: 0 6px 20px rgba(15, 23, 42, .05); cursor: pointer; font: inherit; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
    .fb-navigator-expand:hover { border-color: #93c5fd; box-shadow: 0 8px 22px rgba(37, 99, 235, .12); transform: translateY(-1px); }
    .fb-navigator-expand:focus-visible, .fb-navigator-collapse:focus-visible { outline: 3px solid rgba(37, 99, 235, .2); outline-offset: 2px; }
    .fb-training-navigator .ana-panel-head { flex: 0 0 auto; padding: 14px 16px; border-bottom: 1px solid var(--ana-slate-100); background: #fff; }
    .fb-training-navigator .ana-panel-head > div { min-width: 0; }
    .fb-training-navigator .ana-panel-head h2 { margin: 0 0 3px; font-size: 13px; font-weight: 900; }
    .fb-training-navigator .ana-panel-head p { margin: 0; font-size: 11px; font-weight: 600; }
    .fb-navigator-collapse { width: 32px; height: 32px; display: inline-flex; flex: 0 0 auto; align-items: center; justify-content: center; border: 1px solid var(--ana-slate-200); border-radius: 7px; background: var(--ana-slate-50); color: var(--ana-slate-500); cursor: pointer; transition: .15s; }
    .fb-navigator-collapse:hover { background: var(--ana-slate-100); color: var(--ana-slate-900); }
    .fb-search-state { display: flex; align-items: flex-start; gap: 7px; padding: 10px 16px; border-bottom: 1px solid #dbeafe; background: var(--ana-blue-soft); color: var(--ana-blue-dark); font-size: 11px; font-weight: 700; line-height: 1.4; }
    .fb-search-state i { margin-top: 1px; flex: 0 0 auto; }
    .fb-search-state span { min-width: 0; overflow-wrap: anywhere; }
    .fb-training-list { min-height: 0; display: block; flex: 1 1 auto; overflow-x: hidden; overflow-y: auto; overscroll-behavior: contain; scrollbar-gutter: stable; scrollbar-width: thin; scrollbar-color: var(--ana-slate-300) transparent; }
    .fb-training-list::-webkit-scrollbar { width: 6px; }
    .fb-training-list::-webkit-scrollbar-track { background: transparent; }
    .fb-training-list::-webkit-scrollbar-thumb { border-radius: 999px; background: var(--ana-slate-300); }
    .fb-training-list::-webkit-scrollbar-thumb:hover { background: var(--ana-slate-400); }
    .fb-training-row { width: 100%; min-width: 0; height: auto; display: flex; flex: 0 0 auto; flex-direction: column; align-items: stretch; gap: 10px; padding: 14px 16px; border: 0; border-bottom: 1px solid var(--ana-slate-100); border-left: 3px solid transparent; background: #fff; color: inherit; cursor: pointer; text-align: left; transition: .12s; }
    .fb-training-row:hover { background: var(--ana-slate-50); }
    .fb-training-row:focus-visible { position: relative; z-index: 1; outline: 3px solid rgba(37, 99, 235, .2); outline-offset: -3px; }
    .fb-training-row.is-selected { border-left-color: #4f46e5; background: #eef2ff; }
    .fb-training-row-copy, .fb-training-row-meta { min-width: 0; }
    .fb-training-row-copy { display: flex; flex-direction: column; gap: 4px; }
    .fb-training-row-copy strong { color: var(--ana-slate-900); font-size: 14px; font-weight: 900; line-height: 1.3; overflow-wrap: anywhere; white-space: normal; }
    .fb-training-row-meta { display: flex; align-items: center; justify-content: space-between; gap: 12px; color: var(--ana-slate-500); }
    .fb-training-row-meta small { min-width: 0; font-size: 11px; font-weight: 650; line-height: 1.35; }
    .fb-training-row-meta strong { display: inline-flex; flex: 0 0 auto; align-items: center; gap: 4px; color: var(--ana-slate-700); font-size: 13px; font-weight: 850; }
    .fb-training-row-meta strong i { color: #d97706; font-size: 10px; }
    .fb-training-detail { min-width: 0; flex: 1 1 auto; overflow: hidden; border: 1px solid var(--ana-slate-200); border-radius: 15px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .04); }
    .fb-detail-header { display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 20px 22px; border-bottom: 1px solid var(--ana-slate-100); }
    .fb-detail-identity { min-width: 0; }
    .fb-detail-identity h2 { margin: 0; color: var(--ana-slate-900); font-size: 19px; font-weight: 850; letter-spacing: -.025em; line-height: 1.35; overflow-wrap: anywhere; }
    .fb-detail-identity p { margin: 6px 0 0; color: var(--ana-slate-500); font-size: 12px; font-weight: 600; line-height: 1.45; overflow-wrap: anywhere; }
    .fb-detail-identity p span { padding: 0 4px; color: var(--ana-slate-300); }
    .fb-detail-stats { display: flex; flex: 0 0 auto; align-items: stretch; }
    .fb-detail-stats > div { min-width: 92px; padding: 2px 0 2px 18px; text-align: right; }
    .fb-detail-stats > div + div { margin-left: 18px; border-left: 1px solid var(--ana-slate-200); }
    .fb-detail-stats strong { display: block; color: var(--ana-slate-900); font-size: 19px; font-weight: 850; line-height: 1.1; }
    .fb-detail-stats > div:last-child strong { color: #3b82f6; }
    .fb-detail-stats span { display: block; margin-top: 5px; color: var(--ana-slate-500); font-size: 9.5px; font-weight: 800; letter-spacing: .055em; text-transform: uppercase; }

    .fb-question-toolbar { padding: 18px 22px 20px; border-bottom: 1px solid var(--ana-slate-100); background: var(--ana-slate-50); }
    .fb-question-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin-bottom: 14px; }
    .fb-question-heading h3 { margin: 0; color: var(--ana-slate-900); font-size: 15px; font-weight: 850; }
    .fb-question-heading p { margin: 3px 0 0; color: var(--ana-slate-500); font-size: 11.5px; font-weight: 550; line-height: 1.4; }
    .fb-question-heading > span { flex: 0 0 auto; padding-top: 2px; color: var(--ana-slate-500); font-size: 10.5px; font-weight: 750; white-space: nowrap; }
    .fb-question-filter { min-width: 0; }
    .fb-question-filter .ana-input { min-height: 43px; border-radius: 10px; font-size: 12.5px; }
    .fb-question-filter .ana-input:focus-visible { border-color: var(--ana-blue); box-shadow: 0 0 0 3px rgba(37, 99, 235, .11); outline: none; }
    .fb-question-detail { padding: 22px; }
    .fb-question-prompt { margin-bottom: 18px; }
    .fb-question-prompt > span { display: block; margin-bottom: 5px; color: var(--ana-blue); font-size: 9.5px; font-weight: 850; letter-spacing: .065em; text-transform: uppercase; }
    .fb-question-prompt h3 { margin: 0; color: var(--ana-slate-900); font-size: 16px; font-weight: 800; line-height: 1.5; overflow-wrap: anywhere; }
    .fb-question-result { display: grid; grid-template-columns: minmax(145px, .4fr) minmax(280px, 1.6fr); align-items: center; gap: 24px; padding: 18px; border-radius: 12px; background: var(--ana-slate-50); }
    .fb-score-summary { align-self: stretch; display: flex; flex-direction: column; justify-content: center; padding-right: 24px; border-right: 1px solid var(--ana-slate-200); }
    .fb-score-summary strong { color: #3b82f6; font-size: 28px; font-weight: 850; letter-spacing: -.03em; line-height: 1; }
    .fb-score-summary strong span { color: var(--ana-slate-400); font-size: 13px; font-weight: 650; letter-spacing: 0; }
    .fb-score-summary p { margin: 8px 0 0; color: var(--ana-slate-500); font-size: 11.5px; font-weight: 650; }
    .fb-rating-bars { display: grid; gap: 9px; }
    .fb-rating-bar { display: grid; grid-template-columns: 43px minmax(0, 1fr) 30px; align-items: center; gap: 10px; }
    .fb-rating-bar > span:first-child { color: var(--ana-slate-600); font-size: 11px; font-weight: 750; white-space: nowrap; }
    .fb-rating-bar > span:first-child i { color: #d97706; font-size: 9px; }
    .fb-rating-track { height: 8px; overflow: hidden; border-radius: 999px; background: var(--ana-slate-200); }
    .fb-rating-fill { display: block; width: 0; height: 100%; border-radius: inherit; background: #60a5fa; transition: width .28s ease; }
    .fb-rating-bar > strong { color: var(--ana-slate-700); font-size: 11px; font-weight: 800; text-align: right; }

    .fb-comments-section { margin-top: 24px; padding-top: 19px; border-top: 1px solid var(--ana-slate-200); }
    .fb-comments-heading { margin-bottom: 3px; }
    .fb-comments-heading h4 { margin: 0; color: var(--ana-slate-900); font-size: 14px; font-weight: 850; }
    .fb-comments-heading h4 span { color: var(--ana-slate-500); font-weight: 700; }
    .fb-comment-stream.is-loading { opacity: .58; pointer-events: none; }
    [data-feedback-comment-batch] { display: grid; }
    .fb-comment-row { min-width: 0; padding: 16px 0; border-bottom: 1px solid var(--ana-slate-100); }
    .fb-comment-row p { margin: 0; color: var(--ana-slate-700); font-size: 13.5px; font-weight: 500; line-height: 1.65; overflow-wrap: anywhere; }
    .fb-comment-row time { display: flex; align-items: center; gap: 6px; margin-top: 8px; color: var(--ana-slate-500); font-size: 10.5px; font-weight: 650; }
    .fb-comment-row time i { color: var(--ana-slate-400); }
    .fb-show-more { justify-self: start; margin-top: 14px; }
    .fb-no-comments { display: flex; align-items: center; gap: 9px; margin: 12px 0 0; padding: 14px 0 2px; color: var(--ana-slate-500); font-size: 12px; font-weight: 600; }
    .fb-no-comments i { color: var(--ana-slate-400); font-size: 15px; }
    .fb-detail-empty { min-height: 150px; }
    .fb-empty-workspace { border: 1px solid var(--ana-slate-200); border-radius: 15px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .04); }
    .fb-empty-state { min-height: 120px; }
    .fb-empty-state strong { display: block; margin-bottom: 2px; color: var(--ana-slate-700); }
    .fb-visually-hidden { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0, 0, 0, 0) !important; white-space: nowrap !important; border: 0 !important; }

    @media (max-width: 1100px) {
        .fb-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 900px) {
        .fb-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .fb-workspace { flex-direction: column; }
        .fb-training-navigator, .fb-training-navigator.is-collapsed { position: static; top: auto; width: 100%; max-height: none; overflow: hidden; border-color: var(--ana-slate-200); background: #fff; box-shadow: 0 6px 20px rgba(15, 23, 42, .05); }
        .fb-navigator-expanded, .fb-training-navigator.is-collapsed .fb-navigator-expanded { width: 100%; max-height: none; opacity: 1; visibility: visible; transform: none; pointer-events: auto; }
        .fb-navigator-collapsed, .fb-training-navigator.is-collapsed .fb-navigator-collapsed, .fb-navigator-collapse { display: none; }
        .fb-training-list { max-height: 420px; }
        .fb-training-detail { width: 100%; }
    }
    @media (max-width: 767px) {
        .fb-detail-header { align-items: flex-start; }
        .fb-question-result { grid-template-columns: 125px minmax(0, 1fr); gap: 18px; }
    }
    @media (max-width: 575px) {
        .fb-summary-grid, .fb-filter-grid, .fb-custom-dates { grid-template-columns: 1fr; }
        .fb-filter-toolbar { padding: 16px; }
        .fb-filter-grid, .fb-custom-dates { gap: 14px; }
        .fb-detail-header { align-items: flex-start; flex-direction: column; padding: 18px; }
        .fb-detail-stats { width: 100%; }
        .fb-detail-stats > div { flex: 1; padding-left: 0; text-align: left; }
        .fb-detail-stats > div + div { margin-left: 14px; padding-left: 14px; }
        .fb-question-toolbar, .fb-question-detail { padding: 18px; }
        .fb-question-heading { flex-direction: column; gap: 7px; }
        .fb-question-result { grid-template-columns: 1fr; gap: 15px; padding: 16px; }
        .fb-score-summary { display: grid; grid-template-columns: auto 1fr; align-items: baseline; gap: 10px; padding-right: 0; border-right: 0; }
        .fb-score-summary p { margin: 0; text-align: right; }
        .fb-training-list { max-height: 360px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .fb-training-navigator, .fb-navigator-expanded, .fb-navigator-collapsed { transition-duration: .01ms !important; }
    }
</style>
