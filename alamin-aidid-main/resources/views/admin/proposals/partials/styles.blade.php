<style>
    .adm-alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 18px;
        padding: 13px 15px;
        border: 1px solid;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 750;
    }
    .adm-alert.error { border-color: #fecaca; background: var(--adm-red-soft); color: #991b1b; }
    .adm-alert i { font-size: 16px; line-height: 1.3; }

    /* Uses the approved Trainer Proposal metric-card dimensions and motion. */
    .adm-proposal-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    main.adm-wrap .adm-proposal-stats .adm-stat {
        height: 100%;
        padding: 16px 17px;
        overflow: visible;
        border: 1px solid var(--adm-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: var(--adm-shadow);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    main.adm-wrap .adm-proposal-stats .adm-stat:hover {
        transform: translateY(-3px);
        border-color: #bfdbfe;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
    }
    .adm-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        margin-bottom: 12px;
        border-radius: 12px;
        font-size: 19px;
    }
    .adm-stat-icon.total { background: var(--adm-primary-soft); color: var(--adm-primary); }
    .adm-stat-icon.pending { background: var(--adm-amber-soft); color: var(--adm-amber); }
    .adm-stat-icon.approved { background: var(--adm-green-soft); color: var(--adm-green); }
    .adm-stat-icon.rejected { background: var(--adm-red-soft); color: var(--adm-red); }
    .adm-proposal-stats .adm-stat-label {
        margin-bottom: 5px;
        color: var(--adm-slate-500);
        font-size: 11px;
        font-weight: 850;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .adm-proposal-stats .adm-stat-value {
        color: var(--adm-slate-900);
        font-size: 27px;
        font-weight: 900;
        line-height: 1;
    }

    .adm-proposals-panel {
        margin-bottom: 20px;
        overflow: hidden;
        border: 1px solid var(--adm-border);
        border-radius: 18px;
        background: #fff;
        box-shadow: var(--adm-shadow);
    }
    .adm-proposals-panel-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
    }
    .adm-proposals-panel-mark {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        font-size: 17px;
    }
    .adm-proposals-panel-head h2 {
        margin: 0;
        color: var(--adm-slate-900);
        font-size: 15px;
        font-weight: 850;
    }
    .adm-proposals-panel-head p {
        margin: 2px 0 0;
        color: var(--adm-slate-500);
        font-size: 12px;
        font-weight: 600;
    }

    .adm-report-controls {
        padding: 14px 20px 16px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }
    .adm-status-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-bottom: 14px;
    }
    .adm-filter-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 12px;
        border: 1px solid var(--adm-border);
        border-radius: 999px;
        background: #fff;
        color: var(--adm-slate-600);
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
        transition: .15s ease;
    }
    .adm-filter-btn:hover { border-color: #bfdbfe; color: var(--adm-primary); }
    .adm-filter-btn.active { border-color: var(--adm-primary); background: var(--adm-primary); color: #fff; }
    .adm-status-count {
        min-width: 20px;
        padding: 1px 5px;
        border-radius: 999px;
        background: #f1f5f9;
        color: var(--adm-slate-500);
        font-size: 10px;
        text-align: center;
    }
    .adm-filter-btn.active .adm-status-count { background: rgba(255, 255, 255, .2); color: #fff; }

    .adm-filter-toolbar {
        display: grid;
        grid-template-columns: minmax(260px, 1.7fr) minmax(180px, .8fr);
        align-items: end;
        gap: 12px;
    }
    .adm-filter-field {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .adm-filter-field label,
    .adm-rows-control {
        color: var(--adm-slate-600);
        font-size: 11px;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .adm-filter-field label { margin-bottom: 5px; }
    .adm-search-control { position: relative; }
    .adm-search-control > i {
        position: absolute;
        top: 50%;
        left: 12px;
        transform: translateY(-50%);
        color: var(--adm-slate-400);
        font-size: 14px;
        pointer-events: none;
    }
    main.adm-wrap .adm-search-control input,
    main.adm-wrap .adm-filter-field select,
    main.adm-wrap .adm-rows-control select {
        width: 100%;
        min-height: 42px;
        padding: 9px 12px;
        border: 1px solid var(--adm-border);
        border-radius: 10px;
        outline: none;
        background: #fff;
        color: var(--adm-slate-700);
        font-family: inherit;
        font-size: 13px;
        font-weight: 650;
        transition: .15s ease;
    }
    .adm-search-control input { padding-right: 38px; padding-left: 34px; }
    .adm-search-control input::-webkit-search-cancel-button { display: none; }
    .adm-search-control input:focus,
    .adm-filter-field select:focus,
    .adm-rows-control select:focus {
        border-color: #bfdbfe;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .09);
    }
    .adm-search-clear {
        position: absolute;
        top: 50%;
        right: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        padding: 0;
        transform: translateY(-50%);
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--adm-slate-400);
        cursor: pointer;
        font-size: 18px;
    }
    .adm-search-clear[hidden] { display: none; }
    .adm-search-clear:hover { background: #f1f5f9; color: var(--adm-slate-700); }

    .adm-proposal-list { position: relative; }
    main.adm-wrap .adm-proposals-panel .adm-prop-card {
        margin: 0;
        padding: 0;
        border: 0;
        border-bottom: 1px solid #f1f5f9;
        border-radius: 0;
        background: #fff;
        box-shadow: none;
        transition: background .15s ease;
    }
    main.adm-wrap .adm-proposals-panel .adm-prop-card:last-child { border-bottom: 0; }
    main.adm-wrap .adm-proposals-panel .adm-prop-card:hover,
    main.adm-wrap .adm-proposals-panel .adm-prop-card.details-expanded { background: #fbfcfe; }

    .adm-prop-head {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(250px, .9fr) auto;
        align-items: center;
        gap: 22px;
        padding: 16px 20px;
    }
    .adm-prop-primary { min-width: 0; }
    .adm-prop-primary h3 {
        margin: 0;
        overflow-wrap: anywhere;
        color: var(--adm-slate-900);
        font-size: 15px;
        font-weight: 850;
        line-height: 1.3;
    }
    .adm-prop-primary p {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px 8px;
        margin: 5px 0 0;
        color: var(--adm-slate-500);
        font-size: 12px;
        font-weight: 650;
    }
    .adm-prop-primary p span { display: inline-flex; align-items: center; gap: 5px; }
    .adm-prop-primary p i { color: var(--adm-slate-400); }
    .adm-prop-dates {
        display: grid;
        gap: 5px;
        color: var(--adm-slate-600);
        font-size: 12px;
        font-weight: 650;
    }
    .adm-prop-dates span { display: flex; align-items: baseline; }
    .adm-prop-dates strong {
        display: inline-block;
        width: 70px;
        flex: 0 0 70px;
        color: var(--adm-slate-400);
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .adm-head-side {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 7px;
        flex-shrink: 0;
    }
    .adm-badge,
    .adm-wait {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid;
        border-radius: 999px;
        white-space: nowrap;
        font-weight: 850;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .adm-badge { padding: 4px 10px; font-size: 10px; }
    .adm-badge.pending { border-color: #fde68a; background: var(--adm-amber-soft); color: var(--adm-amber); }
    .adm-badge.approved { border-color: #a7f3d0; background: var(--adm-green-soft); color: var(--adm-green); }
    .adm-badge.rejected { border-color: #fecaca; background: var(--adm-red-soft); color: var(--adm-red); }
    .adm-wait { padding: 3px 9px; font-size: 9.5px; }
    .adm-wait.fresh { border-color: var(--adm-border); background: #f1f5f9; color: var(--adm-slate-600); }
    .adm-wait.warn { border-color: #fde68a; background: var(--adm-amber-soft); color: var(--adm-amber); }
    .adm-wait.urgent { border-color: #fecaca; background: var(--adm-red-soft); color: var(--adm-red); }
    .adm-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 34px;
        padding: 6px 10px;
        border: 1px solid #bfdbfe;
        border-radius: 9px;
        background: #fff;
        color: var(--adm-primary);
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
        transition: .15s ease;
    }
    .adm-toggle:hover { background: var(--adm-primary-soft); color: var(--adm-primary-dark); }

    .adm-body {
        padding: 20px;
        border-top: 1px solid var(--adm-border);
        background: #f8fafc;
    }
    .adm-body.collapsed { display: none; }
    .adm-meta-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px 18px;
        margin: 0;
    }
    .adm-meta-label,
    .adm-block-label {
        margin: 0 0 4px;
        color: var(--adm-slate-400);
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .adm-meta-value { color: var(--adm-slate-700); font-size: 12.5px; font-weight: 750; }
    .adm-pills { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 16px; }
    .adm-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        font-size: 11px;
        font-weight: 800;
    }
    .adm-block { margin-top: 17px; }
    .adm-block-text { color: var(--adm-slate-700); font-size: 12.5px; font-weight: 600; line-height: 1.65; white-space: pre-line; }
    .adm-doc {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 17px;
        color: var(--adm-primary);
        font-size: 12.5px;
        font-weight: 800;
        text-decoration: none;
    }
    .adm-doc:hover { color: var(--adm-primary-dark); text-decoration: underline; }

    .adm-comment {
        margin: 0 20px 14px;
        padding: 11px 13px;
        border: 0;
        border-left: 3px solid;
        border-radius: 0 9px 9px 0;
        background: #f8fafc;
    }
    .adm-comment.approved { border-left-color: var(--adm-green); }
    .adm-comment.rejected { border-left-color: var(--adm-red); }
    .adm-comment-label { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; font-size: 10px; font-weight: 850; letter-spacing: .05em; text-transform: uppercase; }
    .adm-comment.approved .adm-comment-label { color: var(--adm-green); }
    .adm-comment.rejected .adm-comment-label { color: var(--adm-red); }
    .adm-comment-text { color: var(--adm-slate-700); font-size: 12.5px; font-weight: 650; line-height: 1.55; }
    .adm-prop-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 14px 20px;
        border-top: 1px solid #f1f5f9;
    }
    .adm-submitted { display: inline-flex; align-items: center; gap: 6px; color: var(--adm-slate-400); font-size: 11px; font-weight: 700; }
    .adm-actions { display: flex; gap: 8px; }
    .adm-act-approve,
    .adm-act-reject {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 38px;
        padding: 8px 15px;
        border: 1px solid;
        border-radius: 10px;
        cursor: pointer;
        font-family: inherit;
        font-size: 12.5px;
        font-weight: 800;
        transition: .15s ease;
    }
    .adm-act-approve { border-color: var(--adm-green); background: var(--adm-green); color: #fff; }
    .adm-act-approve:hover { border-color: #047857; background: #047857; }
    .adm-act-reject { border-color: #fecaca; background: #fff; color: var(--adm-red); }
    .adm-act-reject:hover { background: var(--adm-red-soft); }

    main.adm-wrap .adm-proposals-panel > .adm-empty {
        min-height: 220px;
        border: 0;
        background: #fff;
    }
    #admFilterEmpty { display: none; }

    .adm-pager {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 18px;
        min-height: 64px;
        margin: 0;
        padding: 14px 20px;
        border-top: 1px solid #f1f5f9;
        background: #fff;
    }
    .adm-rows-control { display: flex; align-items: center; gap: 8px; margin: 0; color: var(--adm-slate-500); }
    .adm-rows-control select { width: 70px; min-height: 36px; padding: 6px 8px; cursor: pointer; }
    .adm-page-info { color: var(--adm-slate-500); font-size: 12px; font-weight: 700; text-align: center; }
    .adm-page-info strong { color: var(--adm-slate-900); font-weight: 850; }
    .adm-pager-controls { display: flex; align-items: center; gap: 6px; }
    .adm-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-height: 36px;
        padding: 7px 12px;
        border: 1px solid var(--adm-border);
        border-radius: 9px;
        background: #fff;
        color: var(--adm-slate-600);
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
        transition: .15s ease;
    }
    .adm-page-btn:hover:not(:disabled) { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .adm-page-btn:disabled { cursor: not-allowed; opacity: .45; }
    .adm-page-current { min-width: 62px; padding: 7px 9px; border-radius: 9px; background: var(--adm-primary-soft); color: var(--adm-primary); font-size: 12px; font-weight: 850; text-align: center; }

    .adm-review-modal .modal-dialog { max-width: 560px; }
    .adm-review-modal .modal-content { overflow: hidden; border: 1px solid var(--adm-border); border-radius: 18px; }
    .adm-review-modal .modal-header { align-items: flex-start; padding: 18px 20px; }
    .adm-review-modal .modal-title { margin: 2px 0 0; color: var(--adm-slate-900); font-size: 16px; font-weight: 850; }
    .adm-modal-eyebrow { color: var(--adm-slate-400); font-size: 10px; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
    .adm-review-modal .modal-body { padding: 20px; }
    .adm-review-modal .modal-footer { gap: 8px; padding: 14px 20px; }
    .adm-modal-prop { margin-bottom: 14px; padding: 11px 13px; border: 1px solid var(--adm-border); border-radius: 10px; background: #f8fafc; color: var(--adm-slate-900); font-size: 13px; font-weight: 750; }
    .adm-label { display: block; margin-bottom: 6px; color: var(--adm-slate-600); font-size: 11px; font-weight: 850; letter-spacing: .05em; text-transform: uppercase; }
    .adm-textarea { width: 100%; min-height: 110px; padding: 10px 12px; border: 1px solid var(--adm-border); border-radius: 11px; outline: none; color: var(--adm-slate-700); font-family: inherit; font-size: 13px; font-weight: 500; line-height: 1.5; resize: vertical; }
    .adm-textarea:focus { border-color: #bfdbfe; box-shadow: 0 0 0 3px rgba(37, 99, 235, .09); }
    .adm-modal-hint { margin-top: 6px; color: var(--adm-slate-400); font-size: 11px; font-weight: 600; }
    .adm-btn,
    .adm-btn-ghost { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 40px; padding: 9px 18px; border-radius: 10px; cursor: pointer; font-family: inherit; font-size: 13px; font-weight: 800; transition: .15s ease; }
    .adm-btn { border: 1px solid var(--adm-primary); background: var(--adm-primary); color: #fff; }
    .adm-btn-ghost { border: 1px solid var(--adm-border); background: #fff; color: var(--adm-slate-600); }
    .adm-btn-ghost:hover { border-color: #cbd5e1; background: #f8fafc; color: var(--adm-slate-900); }
    .adm-btn-reject { border-color: var(--adm-red); background: var(--adm-red); }
    .adm-btn-reject:hover { border-color: #b91c1c; background: #b91c1c; }

    .adm-filter-btn:focus-visible,
    .adm-search-clear:focus-visible,
    .adm-toggle:focus-visible,
    .adm-page-btn:focus-visible,
    .adm-act-approve:focus-visible,
    .adm-act-reject:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .3);
        outline-offset: 2px;
    }

    @media (max-width: 991.98px) {
        .adm-prop-head { grid-template-columns: minmax(0, 1fr) auto; }
        .adm-prop-dates { grid-column: 1 / 2; }
        .adm-head-side { grid-column: 2; grid-row: 1 / span 2; }
    }

    @media (max-width: 767.98px) {
        .adm-proposal-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .adm-filter-toolbar { grid-template-columns: 1fr; }
        .adm-proposals-panel-head,
        .adm-report-controls,
        .adm-prop-head,
        .adm-body,
        .adm-prop-foot { padding-right: 16px; padding-left: 16px; }
        .adm-prop-head { grid-template-columns: 1fr; gap: 12px; }
        .adm-prop-dates,
        .adm-head-side { grid-column: auto; grid-row: auto; }
        .adm-head-side { flex-direction: row; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .adm-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .adm-comment { margin-right: 16px; margin-left: 16px; }
        .adm-pager { grid-template-columns: 1fr; gap: 12px; }
        .adm-page-info { order: -1; }
        .adm-rows-control,
        .adm-pager-controls { justify-content: center; }
    }

    @media (max-width: 479.98px) {
        .adm-status-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .adm-filter-btn { justify-content: space-between; }
        .adm-proposal-stats { grid-template-columns: 1fr; }
        .adm-meta-grid { grid-template-columns: 1fr; }
        .adm-actions { width: 100%; }
        .adm-act-approve,
        .adm-act-reject { flex: 1; justify-content: center; }
        .adm-pager-controls { display: grid; grid-template-columns: 1fr auto 1fr; }
        .adm-review-modal .modal-footer > * { flex: 1; }
    }
</style>
