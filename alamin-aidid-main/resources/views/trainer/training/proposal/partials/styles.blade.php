<style>
    :root {
        --pp-primary: #2563eb;
        --pp-primary-dark: #1d4ed8;
        --pp-primary-soft: #eff6ff;
        --pp-green: #059669;
        --pp-green-soft: #ecfdf5;
        --pp-amber: #d97706;
        --pp-red: #dc2626;
        --pp-slate-900: #0f172a;
        --pp-slate-700: #334155;
        --pp-slate-600: #475569;
        --pp-slate-500: #64748b;
        --pp-slate-400: #94a3b8;
        --pp-slate-100: #f1f5f9;
        --pp-border: #e2e8f0;
        --pp-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }

    body { background: #f8fafc; }
    .proposal-shell { color: var(--pp-slate-900); }
    .proposal-head { margin-bottom: 22px; }
    .proposal-title { margin: 0 0 5px; color: var(--pp-slate-900); font-size: 26px; font-weight: 850; letter-spacing: -.035em; line-height: 1.18; }
    .proposal-subtitle { max-width: 760px; margin: 0; color: var(--pp-slate-500); font-size: 14px; font-weight: 600; line-height: 1.5; }

    /* Exact standard Dashboard metric-card structure and dimensions. */
    .pp-stat-card { height: 100%; padding: 16px 17px; background: #fff; border: 1px solid var(--pp-border); border-radius: 16px; box-shadow: var(--pp-shadow); transition: .15s ease; }
    .pp-stat-card:hover { transform: translateY(-3px); border-color: #bfdbfe; box-shadow: 0 12px 24px rgba(15, 23, 42, .08); }
    .pp-stat-icon { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; margin-bottom: 12px; border-radius: 12px; font-size: 19px; }
    .pp-stat-icon-blue { background: var(--pp-primary-soft); color: var(--pp-primary); }
    .pp-stat-icon-amber { background: #fffbeb; color: var(--pp-amber); }
    .pp-stat-icon-green { background: var(--pp-green-soft); color: var(--pp-green); }
    .pp-stat-icon-red { background: #fef2f2; color: var(--pp-red); }
    .pp-stat-label { margin-bottom: 5px; color: var(--pp-slate-500); font-size: 11px; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
    .pp-stat-value { margin: 0; color: var(--pp-slate-900); font-size: 27px; font-weight: 900; line-height: 1; }

    .pp-card { margin-bottom: 20px; overflow: hidden; background: #fff; border: 1px solid var(--pp-border); border-radius: 18px; box-shadow: var(--pp-shadow); }
    .pp-card-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--pp-slate-100); }
    .pp-card-header .header-mark { display: flex; flex: 0 0 auto; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 12px; background: var(--pp-primary-soft); color: var(--pp-primary); font-size: 17px; }
    .pp-card-title { margin: 0; color: var(--pp-slate-900); font-size: 15px; font-weight: 850; }
    .pp-card-sub { margin-top: 2px; color: var(--pp-slate-500); font-size: 12px; font-weight: 600; }
    .pp-card-body { padding: 20px; }

    .pp-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .pp-field { display: flex; flex-direction: column; min-width: 0; }
    .pp-field.span-2 { grid-column: 1 / -1; }
    .pp-label, .pp-filter-field label { margin-bottom: 6px; color: var(--pp-slate-600); font-size: 11px; font-weight: 850; letter-spacing: .05em; text-transform: uppercase; }
    .pp-label .req { margin-left: 2px; color: var(--pp-red); }
    .pp-hint { margin-top: 5px; color: var(--pp-slate-400); font-size: 11px; font-weight: 600; line-height: 1.4; }
    .pp-input, .pp-select, .pp-textarea, .pp-filter-field input, .pp-filter-field select, .pp-rows-control select { width: 100%; min-height: 42px; padding: 9px 12px; border: 1px solid var(--pp-border); border-radius: 10px; outline: none; background: #fff; color: var(--pp-slate-700); font-family: inherit; font-size: 13px; font-weight: 650; transition: .15s ease; }
    .pp-select, .pp-filter-field select, .pp-rows-control select { cursor: pointer; }
    .pp-input:focus, .pp-select:focus, .pp-textarea:focus, .pp-filter-field input:focus, .pp-filter-field select:focus, .pp-rows-control select:focus { border-color: #bfdbfe; box-shadow: 0 0 0 3px rgba(37, 99, 235, .09); }
    .pp-textarea { min-height: 96px; border-radius: 11px; line-height: 1.5; resize: vertical; }
    .pp-input.is-invalid, .pp-select.is-invalid, .pp-textarea.is-invalid { border-color: #fecaca; background: #fff5f5; }
    .pp-file { width: 100%; padding: 12px; border: 1px dashed #bfdbfe; border-radius: 11px; background: #f8fbff; color: var(--pp-slate-600); cursor: pointer; font-size: 12px; font-weight: 650; }
    .pp-error { margin-top: 5px; color: var(--pp-red); font-size: 11px; font-weight: 800; }

    .pp-audience-group { display: flex; flex-wrap: wrap; gap: 10px; }
    .pp-audience { cursor: pointer; }
    .pp-audience input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .pp-audience-box { display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border: 1px solid var(--pp-border); border-radius: 11px; background: #fff; color: var(--pp-slate-600); font-size: 12px; font-weight: 850; transition: .15s ease; }
    .pp-audience:hover .pp-audience-box { border-color: #bfdbfe; }
    .pp-audience input:focus-visible + .pp-audience-box { outline: 3px solid rgba(37, 99, 235, .25); outline-offset: 2px; }
    .pp-audience input:checked + .pp-audience-box { border-color: var(--pp-primary); background: var(--pp-primary-soft); color: var(--pp-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .08); }

    .pp-form-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--pp-slate-100); }
    .pp-foot-note { display: flex; align-items: center; gap: 7px; color: var(--pp-slate-500); font-size: 12px; font-weight: 650; }
    .pp-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border: 0; border-radius: 11px; background: var(--pp-primary); color: #fff; cursor: pointer; font-family: inherit; font-size: 13px; font-weight: 850; transition: .15s ease; }
    .pp-btn:hover { background: var(--pp-primary-dark); }
    .pp-btn-ghost { display: inline-flex; align-items: center; gap: 6px; padding: 8px 13px; border: 1px solid var(--pp-border); border-radius: 10px; background: #fff; color: var(--pp-slate-600); cursor: pointer; font-family: inherit; font-size: 12px; font-weight: 850; transition: .15s ease; }
    .pp-btn-ghost:hover { border-color: #fecaca; background: #fef2f2; color: var(--pp-red); }

    .pp-report-controls { padding: 14px 20px 16px; border-bottom: 1px solid var(--pp-slate-100); background: #f8fafc; }
    .pp-status-tabs { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 14px; }
    .pp-status-tab { display: inline-flex; align-items: center; gap: 7px; padding: 6px 12px; border: 1px solid var(--pp-border); border-radius: 999px; background: #fff; color: var(--pp-slate-600); font-size: 12px; font-weight: 800; text-decoration: none; transition: .15s ease; }
    .pp-status-tab:hover { border-color: #bfdbfe; color: var(--pp-primary); }
    .pp-status-tab.active { border-color: var(--pp-primary); background: var(--pp-primary); color: #fff; }
    .pp-status-count { min-width: 20px; padding: 1px 5px; border-radius: 999px; background: var(--pp-slate-100); color: var(--pp-slate-500); font-size: 10px; text-align: center; }
    .pp-status-tab.active .pp-status-count { background: rgba(255, 255, 255, .2); color: #fff; }

    .pp-filter-toolbar { display: grid; grid-template-columns: minmax(220px, 1.7fr) minmax(160px, 1fr) minmax(160px, 1fr); align-items: end; gap: 12px; }
    .pp-filter-field { display: flex; flex-direction: column; min-width: 0; }
    .pp-filter-field label { margin-bottom: 5px; }
    .pp-search-control { position: relative; }
    .pp-search-control > i { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); color: var(--pp-slate-400); font-size: 14px; pointer-events: none; }
    .pp-search-control input { padding-right: 36px; padding-left: 34px; }
    .pp-search-control input::-webkit-search-cancel-button { display: none; }
    .pp-search-clear { position: absolute; top: 50%; right: 7px; display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; padding: 0; transform: translateY(-50%); border: 0; border-radius: 8px; background: transparent; color: var(--pp-slate-400); cursor: pointer; font-size: 18px; }
    .pp-search-clear:hover { background: var(--pp-slate-100); color: var(--pp-slate-700); }
    .pp-custom-dates { display: grid; grid-column: 1 / -1; grid-template-columns: repeat(2, minmax(160px, 220px)); gap: 12px; }
    .pp-custom-dates[hidden], .pp-search-clear[hidden], .pp-reset-link[hidden] { display: none !important; }
    .pp-reset-link { justify-self: start; padding: 0; border: 0; background: transparent; color: var(--pp-primary); cursor: pointer; font-family: inherit; font-size: 12px; font-weight: 750; text-decoration: underline; text-underline-offset: 3px; }
    .pp-reset-link:hover { color: var(--pp-primary-dark); }

    .pp-results-region { position: relative; min-height: 72px; }
    .pp-loading-label { position: absolute; z-index: 3; top: 10px; right: 20px; display: none; padding: 5px 9px; border: 1px solid #bfdbfe; border-radius: 999px; background: #fff; color: var(--pp-primary); box-shadow: var(--pp-shadow); font-size: 11px; font-weight: 800; }
    .pp-results-region.is-loading .pp-loading-label { display: block; }
    .pp-results-region.has-error .pp-loading-label { display: block; border-color: #fecaca; color: var(--pp-red); }
    .pp-results-region.is-loading .pp-proposal-list, .pp-results-region.is-loading .pp-pager, .pp-results-region.is-loading .proposal-empty { opacity: .55; pointer-events: none; }
    .pp-proposal-entry { background: #fff; border-bottom: 1px solid var(--pp-slate-100); transition: background .15s ease; }
    .pp-proposal-entry:hover, .pp-proposal-entry.is-expanded { background: #fbfcfe; }
    .pp-proposal-summary { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(250px, .9fr) auto; align-items: center; gap: 22px; padding: 16px 20px; }
    .pp-proposal-primary { min-width: 0; }
    .pp-proposal-primary h3 { overflow-wrap: anywhere; margin: 0; color: var(--pp-slate-900); font-size: 15px; font-weight: 850; line-height: 1.3; }
    .pp-proposal-primary p { margin: 5px 0 0; color: var(--pp-slate-500); font-size: 12px; font-weight: 650; }
    .pp-proposal-primary p span { padding: 0 4px; color: var(--pp-slate-400); }
    .pp-proposal-dates { display: grid; gap: 5px; color: var(--pp-slate-600); font-size: 12px; font-weight: 650; }
    .pp-proposal-dates strong { display: inline-block; width: 66px; color: var(--pp-slate-400); font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .pp-proposal-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
    .pp-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border: 1px solid; border-radius: 999px; white-space: nowrap; font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .pp-badge.pending { border-color: #fde68a; background: #fffbeb; color: var(--pp-amber); }
    .pp-badge.approved { border-color: #a7f3d0; background: var(--pp-green-soft); color: var(--pp-green); }
    .pp-badge.rejected { border-color: #fecaca; background: #fef2f2; color: var(--pp-red); }
    .pp-detail-toggle { display: inline-flex; align-items: center; gap: 6px; min-height: 34px; padding: 6px 10px; border: 1px solid #bfdbfe; border-radius: 9px; background: #fff; color: var(--pp-primary); font-size: 12px; font-weight: 800; text-decoration: none; transition: .15s ease; }
    .pp-detail-toggle:hover { background: var(--pp-primary-soft); color: var(--pp-primary-dark); }

    .pp-proposal-detail { padding: 20px; border-top: 1px solid var(--pp-border); background: #f8fafc; }
    .pp-detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px 28px; }
    .pp-detail-section { min-width: 0; }
    .pp-detail-section.span-2 { grid-column: 1 / -1; }
    .pp-detail-section h4 { margin: 0 0 9px; color: var(--pp-slate-500); font-size: 10px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; }
    .pp-detail-section > p { margin: 0; color: var(--pp-slate-700); font-size: 12.5px; font-weight: 600; line-height: 1.65; white-space: pre-line; }
    .pp-detail-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 18px; margin: 0; }
    .pp-detail-facts div.span-2 { grid-column: 1 / -1; }
    .pp-detail-facts dt { margin-bottom: 3px; color: var(--pp-slate-400); font-size: 10px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .pp-detail-facts dd { margin: 0; color: var(--pp-slate-700); font-size: 12.5px; font-weight: 750; }
    .pp-detail-pills { display: flex; flex-wrap: wrap; gap: 7px; }
    .pp-detail-pills span { display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px; border: 1px solid #bfdbfe; border-radius: 999px; background: var(--pp-primary-soft); color: var(--pp-primary); font-size: 11px; font-weight: 800; }
    .proposal-doc-link { display: inline-flex; align-items: center; gap: 7px; color: var(--pp-primary); font-size: 12.5px; font-weight: 800; text-decoration: none; }
    .proposal-doc-link:hover { color: var(--pp-primary-dark); text-decoration: underline; }
    .pp-review-section { padding-top: 17px; border-top: 1px solid var(--pp-border); }
    .pp-admin-comment { margin-top: 13px; padding: 11px 13px; border-left: 3px solid var(--pp-primary); border-radius: 0 9px 9px 0; background: #fff; }
    .pp-review-section.approved .pp-admin-comment { border-left-color: var(--pp-green); }
    .pp-review-section.rejected .pp-admin-comment { border-left-color: var(--pp-red); }
    .pp-admin-comment strong { color: var(--pp-slate-500); font-size: 10px; font-weight: 850; letter-spacing: .05em; text-transform: uppercase; }
    .pp-admin-comment p { margin: 4px 0 0; color: var(--pp-slate-700); font-size: 12.5px; font-weight: 650; line-height: 1.55; white-space: pre-line; }
    .pp-detail-footer { display: flex; justify-content: flex-end; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--pp-border); }

    .pp-pager { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 18px; padding: 14px 20px; background: #fff; }
    .pp-rows-control { display: flex; align-items: center; gap: 8px; margin: 0; color: var(--pp-slate-500); font-size: 12px; font-weight: 800; }
    .pp-rows-control select { width: 70px; min-height: 36px; padding: 6px 8px; }
    .pp-pager-info { color: var(--pp-slate-500); font-size: 12px; font-weight: 700; text-align: center; }
    .pp-pager-info strong { color: var(--pp-slate-900); font-weight: 850; }
    .pp-pager-controls { display: flex; align-items: center; gap: 6px; }
    .pp-pager-btn { display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-height: 36px; padding: 7px 12px; border: 1px solid var(--pp-border); border-radius: 9px; background: #fff; color: var(--pp-slate-600); cursor: pointer; font-family: inherit; font-size: 12px; font-weight: 800; text-decoration: none; transition: .15s ease; }
    .pp-pager-btn:hover:not(:disabled) { border-color: #bfdbfe; background: var(--pp-primary-soft); color: var(--pp-primary); }
    .pp-pager-btn:disabled { opacity: .45; cursor: not-allowed; }
    .pp-pager-page { min-width: 62px; padding: 7px 9px; border-radius: 9px; background: var(--pp-primary-soft); color: var(--pp-primary); font-size: 12px; font-weight: 850; text-align: center; }

    .proposal-empty, .setup-notice { padding: 44px 22px; color: var(--pp-slate-500); text-align: center; }
    .proposal-empty i, .setup-notice i { display: block; margin-bottom: 10px; color: var(--pp-slate-400); font-size: 34px; }
    .proposal-empty .empty-title, .setup-notice .empty-title { margin-bottom: 4px; color: var(--pp-slate-700); font-size: 14px; font-weight: 850; }
    .proposal-empty .empty-sub, .setup-notice .empty-sub { font-size: 12.5px; font-weight: 650; }

    .pp-btn:focus-visible, .pp-btn-ghost:focus-visible, .pp-status-tab:focus-visible, .pp-search-clear:focus-visible, .pp-reset-link:focus-visible, .pp-detail-toggle:focus-visible, .pp-pager-btn:focus-visible { outline: 3px solid rgba(37, 99, 235, .3); outline-offset: 2px; }

    @media (max-width: 991.98px) {
        .pp-filter-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pp-search-field { grid-column: 1 / -1; }
        .pp-proposal-summary { grid-template-columns: minmax(0, 1fr) auto; }
        .pp-proposal-dates { grid-column: 1 / 2; }
        .pp-proposal-actions { grid-column: 2; grid-row: 1 / span 2; }
    }

    @media (max-width: 767.98px) {
        .proposal-title { font-size: 21px; }
        .pp-form-grid, .pp-filter-toolbar, .pp-custom-dates, .pp-detail-grid { grid-template-columns: 1fr; }
        .pp-field.span-2, .pp-search-field, .pp-detail-section.span-2, .pp-detail-facts div.span-2, .pp-custom-dates { grid-column: auto; }
        .pp-card-header, .pp-card-body, .pp-report-controls, .pp-proposal-summary, .pp-proposal-detail { padding-right: 16px; padding-left: 16px; }
        .pp-proposal-summary { grid-template-columns: 1fr; gap: 12px; }
        .pp-proposal-dates, .pp-proposal-actions { grid-column: auto; grid-row: auto; }
        .pp-proposal-actions { flex-direction: row; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .pp-detail-facts { grid-template-columns: 1fr; }
        .pp-pager { grid-template-columns: 1fr; gap: 12px; }
        .pp-rows-control, .pp-pager-controls { justify-content: center; }
        .pp-pager-info { order: -1; }
    }

    @media (max-width: 479.98px) {
        .pp-form-foot { align-items: stretch; }
        .pp-btn { justify-content: center; width: 100%; }
        .pp-status-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pp-status-tab { justify-content: space-between; }
        .pp-pager-controls { display: grid; grid-template-columns: 1fr auto 1fr; }
    }
</style>
