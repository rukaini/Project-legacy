<style>
    .tm-workspace {
        display: flex;
        align-items: start;
        gap: 18px;
    }
    .tm-master,
    .tm-detail-card,
    .tm-empty-panel {
        overflow: hidden;
        border: 1px solid var(--adm-border);
        border-radius: 18px;
        background: #fff;
        box-shadow: var(--adm-shadow);
    }
    .tm-master {
        position: sticky;
        top: 84px;
        align-self: start;
        width: 360px;
        min-width: 0;
        flex: 0 0 360px;
        height: calc(100vh - 104px);
        min-height: min(520px, calc(100vh - 104px));
        max-height: calc(100vh - 104px);
        transition: width 0.18s ease, flex-basis 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }
    .tm-master-expanded {
        display: flex;
        flex-direction: column;
        width: 360px;
        min-width: 360px;
        min-height: 0;
        height: 100%;
        max-height: none;
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
        pointer-events: auto;
        transition: opacity .18s ease, transform .18s ease, visibility 0s linear 0s;
    }
    .tm-master-collapsed {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        flex-direction: column;
        padding: 14px 3px;
        background: transparent;
        opacity: 0;
        visibility: hidden;
        transform: translateX(6px);
        pointer-events: none;
        transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s;
    }
    .tm-master-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        flex: 0 0 32px;
        margin-left: auto;
        padding: 0;
        border: 1px solid var(--adm-border);
        border-radius: 7px;
        background: var(--adm-slate-50);
        color: var(--adm-slate-500);
        cursor: pointer;
        transition: border-color .15s ease, background .15s ease, color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .tm-master-toggle:hover { border-color: var(--adm-slate-300); background: var(--adm-slate-100); color: var(--adm-slate-900); }
    .tm-master-expand {
        width: 44px;
        height: 42px;
        flex-basis: 42px;
        margin-left: 0;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .08);
    }
    .tm-master-expand:hover { border-color: #cbd5e1; box-shadow: 0 8px 20px rgba(15, 23, 42, .11); transform: translateY(-1px); }
    .tm-master.is-collapsed {
        width: 44px;
        flex-basis: 44px;
        overflow: hidden;
        border-color: transparent;
        background: transparent;
        box-shadow: none;
    }
    .tm-master.is-collapsed .tm-master-expanded {
        opacity: 0;
        transform: translateX(-6px);
        visibility: hidden;
        pointer-events: none;
        transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s;
    }
    .tm-master.is-collapsed .tm-master-collapsed {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
        pointer-events: auto;
        transition-delay: 0s;
    }
    .tm-detail { min-width: 0; flex: 1 1 0; }
    .tm-detail-card { display: flex; flex-direction: column; }
    .tm-panel-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--adm-border);
        flex: 0 0 auto;
    }
    .tm-panel-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 11px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        font-size: 17px;
    }
    .tm-panel-head h2 {
        margin: 0;
        color: var(--adm-slate-900);
        font-size: 15px;
        font-weight: 850;
    }
    .tm-panel-head p {
        margin: 2px 0 0;
        color: var(--adm-slate-500);
        font-size: 12px;
        font-weight: 600;
    }
    main.adm-wrap .tm-empty-panel > .adm-empty,
    main.adm-wrap .tm-detail > .adm-empty {
        min-height: 280px;
        border: 0;
        background: #fff;
    }

    .tm-master-controls {
        padding: 11px 13px 10px;
        border-bottom: 1px solid var(--adm-border);
        background: #f8fafc;
        flex: 0 0 auto;
    }
    .tm-master-filter-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: end;
        gap: 9px;
        padding-top: 10px;
    }
    .tm-filter-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        min-height: 34px;
        margin-top: 8px;
        padding: 6px 9px;
        border: 1px solid var(--adm-border);
        border-radius: 8px;
        background: #fff;
        color: var(--adm-slate-600);
        cursor: pointer;
        font: inherit;
        font-size: 11.5px;
        font-weight: 800;
        transition: border-color .15s ease, background .15s ease, color .15s ease;
    }
    .tm-filter-toggle > span,
    .tm-filter-toggle-meta { display: inline-flex; align-items: center; gap: 7px; }
    .tm-filter-toggle:hover,
    .tm-filter-toggle.has-active-filters { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .tm-filter-count {
        min-width: 19px;
        padding: 2px 6px;
        border-radius: 999px;
        background: var(--adm-primary);
        color: #fff;
        font-size: 10px;
        line-height: 1.4;
        text-align: center;
    }
    .tm-master-filter-panel {
        display: grid;
        grid-template-rows: 0fr;
        opacity: 0;
        transition: grid-template-rows .22s ease, opacity .18s ease;
    }
    .tm-master-filter-panel.is-open { grid-template-rows: 1fr; opacity: 1; }
    .tm-master-filter-panel-inner { min-height: 0; overflow: hidden; }
    .tm-filter-field { min-width: 0; }
    .tm-filter-field label,
    .tm-rows-control > span {
        display: block;
        margin-bottom: 6px;
        color: var(--adm-slate-500);
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    main.adm-wrap .tm-filter-field select,
    main.adm-wrap .tm-search-control input,
    main.adm-wrap .tm-rows-control select {
        width: 100%;
        min-height: 38px;
        border: 1px solid var(--adm-border);
        border-radius: 10px;
        background: #fff;
        color: var(--adm-slate-700);
        font-family: inherit;
        font-size: 12.5px;
        font-weight: 650;
        outline: 0;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    main.adm-wrap .tm-filter-field select {
        padding: 8px 31px 8px 11px;
        cursor: pointer;
    }
    main.adm-wrap .tm-search-control input { padding: 8px 38px 8px 36px; }
    main.adm-wrap .tm-filter-field select:focus,
    main.adm-wrap .tm-search-control input:focus,
    main.adm-wrap .tm-rows-control select:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
    }
    .tm-search-control { position: relative; }
    .tm-search-control > i {
        position: absolute;
        top: 50%;
        left: 12px;
        z-index: 1;
        transform: translateY(-50%);
        color: var(--adm-slate-400);
        font-size: 14px;
        pointer-events: none;
    }
    .tm-search-control input[type="search"] { appearance: textfield; -webkit-appearance: textfield; }
    .tm-search-control input[type="search"]::-webkit-search-decoration,
    .tm-search-control input[type="search"]::-webkit-search-cancel-button {
        display: none;
        appearance: none;
        -webkit-appearance: none;
    }
    .tm-search-clear {
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
        font-size: 13px;
        transition: background .15s ease, color .15s ease;
    }
    .tm-search-clear:hover { background: #f1f5f9; color: var(--adm-slate-700); }
    .tm-search-clear[hidden] { display: none; }
    .tm-reset-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 42px;
        padding: 8px 13px;
        border: 1px solid var(--adm-border);
        border-radius: 10px;
        background: #fff;
        color: var(--adm-slate-600);
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
        transition: .15s ease;
    }
    .tm-reset-btn:hover:not(:disabled) {
        border-color: #fecaca;
        background: var(--adm-red-soft);
        color: var(--adm-red);
    }
    .tm-reset-btn:disabled { cursor: not-allowed; opacity: .45; }

    .tm-training-list {
        min-height: 0;
        flex: 1 1 auto;
        overflow-y: auto;
        overscroll-behavior: contain;
        background: #fff;
        scrollbar-color: #cbd5e1 transparent;
        scrollbar-width: thin;
    }
    .tm-training-row {
        display: block;
        width: 100%;
        padding: 15px 16px;
        border: 0;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        color: inherit;
        cursor: pointer;
        font-family: inherit;
        text-align: left;
        transition: background .15s ease, box-shadow .15s ease;
    }
    .tm-training-row:last-child { border-bottom: 0; }
    .tm-training-row:hover { background: #fbfcfe; }
    .tm-training-row.active {
        position: relative;
        background: var(--adm-primary-soft);
        box-shadow: inset 3px 0 0 var(--adm-primary);
    }
    .tm-training-row-top,
    .tm-training-identity,
    .tm-training-meta,
    .tm-training-row-foot {
        display: flex;
        align-items: center;
    }
    .tm-training-row-top { justify-content: space-between; gap: 10px; }
    .tm-training-identity { min-width: 0; gap: 10px; }
    .tm-training-initial {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        font-size: 13px;
        font-weight: 900;
    }
    .tm-training-row.active .tm-training-initial { border-color: var(--adm-primary); background: var(--adm-primary); color: #fff; }
    .tm-training-identity strong {
        display: block;
        min-width: 0;
        overflow: hidden;
        color: var(--adm-slate-900);
        font-size: 13.5px;
        font-weight: 850;
        line-height: 1.35;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .tm-training-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
        margin: 11px 0 0 46px;
        color: var(--adm-slate-500);
        font-size: 11.5px;
        font-weight: 650;
    }
    .tm-training-meta span { display: inline-flex; align-items: center; gap: 6px; min-width: 0; }
    .tm-training-meta i { color: var(--adm-slate-400); }
    .tm-training-row-foot {
        justify-content: space-between;
        gap: 10px;
        margin: 11px 0 0 46px;
        color: var(--adm-slate-500);
        font-size: 10.5px;
        font-weight: 750;
    }
    .tm-training-row-summary { display: flex; min-width: 0; align-items: center; gap: 8px; flex-wrap: wrap; }
    .tm-training-plan-alert { display: inline-flex; align-items: center; gap: 5px; color: #92400e; font-weight: 850; white-space: nowrap; }
    .tm-training-plan-alert i { width: 7px; height: 7px; flex: 0 0 7px; border-radius: 50%; background: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, .14); }

    .tm-mode-badge,
    .tm-schedule-badge,
    .tm-status-badge,
    .tm-type-badge,
    .tm-enrolment-status,
    .tm-attendance-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid var(--adm-border);
        border-radius: 999px;
        background: #f1f5f9;
        color: var(--adm-slate-600);
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .035em;
        line-height: 1;
        white-space: nowrap;
    }
    .tm-mode-badge,
    .tm-schedule-badge,
    .tm-status-badge,
    .tm-type-badge,
    .tm-attendance-badge { padding: 5px 9px; text-transform: uppercase; }
    .tm-mode-badge.lms { border-color: #c7d2fe; background: var(--adm-indigo-soft); color: var(--adm-indigo); }
    .tm-mode-badge.simple,
    .tm-mode-badge.standard { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .tm-schedule-badge.current,
    .tm-status-badge.success { border-color: #a7f3d0; background: var(--adm-green-soft); color: var(--adm-green); }
    .tm-schedule-badge.upcoming,
    .tm-schedule-badge.today,
    .tm-status-badge.warning { border-color: #fde68a; background: var(--adm-amber-soft); color: var(--adm-amber); }
    .tm-schedule-badge.past,
    .tm-schedule-badge.unscheduled,
    .tm-schedule-badge.unavailable,
    .tm-status-badge.neutral { border-color: var(--adm-border); background: #f1f5f9; color: var(--adm-slate-600); }
    .tm-status-badge.danger { border-color: #fecaca; background: var(--adm-red-soft); color: var(--adm-red); }
    .tm-type-badge.blue { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .tm-type-badge.indigo { border-color: #c7d2fe; background: var(--adm-indigo-soft); color: var(--adm-indigo); }
    .tm-type-badge.amber { border-color: #fde68a; background: var(--adm-amber-soft); color: var(--adm-amber); }
    .tm-attendance-badge.present { border-color: #a7f3d0; background: var(--adm-green-soft); color: var(--adm-green); }
    .tm-attendance-badge.late { border-color: #fde68a; background: var(--adm-amber-soft); color: var(--adm-amber); }
    .tm-attendance-badge.absent { border-color: #fecaca; background: var(--adm-red-soft); color: var(--adm-red); }

    main.adm-wrap .tm-master > .adm-empty { min-height: 190px; border: 0; border-radius: 0; }

    .tm-detail-head { flex: 0 0 auto; padding: 22px 24px 18px; }
    .tm-detail-title-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
    }
    .tm-eyebrow {
        display: block;
        margin-bottom: 5px;
        color: var(--adm-primary);
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .07em;
        text-transform: uppercase;
    }
    .tm-detail-head h2 {
        margin: 0;
        overflow-wrap: anywhere;
        color: var(--adm-slate-900);
        font-size: clamp(21px, 1.6vw, 28px);
        font-weight: 900;
        letter-spacing: -.025em;
        line-height: 1.2;
    }
    .tm-detail-badges { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 7px; }
    .tm-detail-identity {
        display: grid;
        grid-template-columns: minmax(220px, 1.2fr) minmax(180px, .8fr) minmax(180px, .8fr);
        gap: 13px;
        margin-top: 18px;
        padding: 14px 16px;
        border: 1px solid var(--adm-border);
        border-radius: 13px;
        background: #f8fafc;
    }
    .tm-detail-identity > div > span {
        display: block;
        margin-bottom: 5px;
        color: var(--adm-slate-400);
        font-size: 9.5px;
        font-weight: 850;
        letter-spacing: .055em;
        text-transform: uppercase;
    }
    .tm-detail-identity strong,
    .tm-unavailable {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--adm-slate-700);
        font-size: 12px;
        font-weight: 750;
    }
    .tm-trainer-list { display: flex; flex-wrap: wrap; gap: 6px; }
    .tm-trainer-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--adm-primary);
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
    }
    .tm-trainer-link:hover { color: var(--adm-primary-dark); text-decoration: underline; }
    .tm-description {
        margin-top: 15px !important;
        max-width: 95ch;
        color: var(--adm-slate-600) !important;
        font-size: 12.5px !important;
        line-height: 1.65;
    }

    .tm-detail-tabs {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 0 24px;
        border-top: 1px solid var(--adm-border);
        border-bottom: 1px solid var(--adm-border);
        background: #f8fafc;
        flex: 0 0 auto;
    }
    .tm-detail-tabs button {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 48px;
        padding: 10px 13px;
        border: 0;
        background: transparent;
        color: var(--adm-slate-500);
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
    }
    .tm-detail-tabs button::after {
        position: absolute;
        right: 10px;
        bottom: -1px;
        left: 10px;
        height: 2px;
        background: transparent;
        content: '';
    }
    .tm-detail-tabs button:hover { color: var(--adm-slate-900); }
    .tm-detail-tabs button.active { color: var(--adm-primary); }
    .tm-detail-tabs button.active::after { background: var(--adm-primary); }
    .tm-tab-panel {
        padding: 20px 24px 24px;
    }

    .tm-overview-metrics,
    .tm-attendance-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 11px;
    }
    .tm-metric {
        min-width: 0;
        padding: 14px 15px;
        border: 1px solid var(--adm-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .04);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .tm-metric:hover,
    .tm-info-section:not(.tm-attendance-section):hover {
        transform: translateY(-3px);
        border-color: #bfdbfe;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
    }
    .tm-feedback { display: flex; align-items: center; gap: 9px; margin-bottom: 16px; padding: 11px 13px; border: 1px solid; border-radius: 11px; font-size: 12.5px; font-weight: 750; }
    .tm-feedback.error { border-color: #fecaca; background: var(--adm-red-soft); color: #b91c1c; }
    .tm-material-access { margin-top: 15px; padding: 16px 17px; border: 1px solid var(--adm-border); border-radius: 13px; background: #fff; box-shadow: var(--adm-shadow); }
    .tm-access-state { display: inline-flex; align-items: center; padding: 4px 8px; border: 1px solid; border-radius: 999px; font-size: 9.5px; font-weight: 850; letter-spacing: .045em; text-transform: uppercase; }
    .tm-access-state.enabled { border-color: #a7f3d0; background: var(--adm-green-soft); color: var(--adm-green); }
    .tm-access-state.disabled { border-color: var(--adm-border-strong); background: #f8fafc; color: var(--adm-slate-500); }
    .tm-access-state.saving { border-color: #fde68a; background: var(--adm-amber-soft); color: #b45309; }
    .tm-material-access-control { display: flex; align-items: center; gap: 12px; margin-top: 13px; }
    .tm-access-switch { position: relative; width: 42px; height: 24px; flex: 0 0 42px; padding: 0; border: 1px solid var(--adm-border-strong); border-radius: 999px; background: var(--adm-slate-200); cursor: pointer; transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease; }
    .tm-access-switch span { position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(15, 23, 42, .28); transition: transform .15s ease; }
    .tm-access-switch.is-enabled { border-color: var(--adm-primary); background: var(--adm-primary); }
    .tm-access-switch.is-enabled span { transform: translateX(18px); }
    .tm-access-switch:disabled { cursor: wait; opacity: .68; }
    .tm-access-switch:focus-visible { outline: 3px solid rgba(37, 99, 235, .3); outline-offset: 2px; }
    .tm-access-copy { display: flex; min-width: 0; flex: 1; align-items: center; }
    .tm-access-copy strong { color: var(--adm-slate-800); font-size: 12.5px; font-weight: 850; }
    .tm-tab-count { display: inline-flex; min-width: 19px; height: 19px; align-items: center; justify-content: center; margin-left: 2px; padding: 0 5px; border: 1px solid #fcd34d; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 9px; font-weight: 900; }
    .tm-plan-tab-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 15px; }
    .tm-plan-tab-head h3 { margin: 0; color: var(--adm-slate-900); font-size: 13px; font-weight: 850; }
    .tm-plan-tab-head p { margin: 3px 0 0; color: var(--adm-slate-500); font-size: 10.5px; font-weight: 600; }
    .tm-plan-layout { display: grid; grid-template-columns: minmax(230px, .64fr) minmax(0, 1.7fr); gap: 16px; align-items: start; }
    .tm-plan-master { min-width: 0; height: max-content; align-self: start; overflow: hidden; border: 1px solid var(--adm-border); border-radius: 13px; background: #f8fafc; }
    .tm-plan-master-head { display: flex; flex-direction: column; padding: 13px 14px 11px; border-bottom: 1px solid var(--adm-border); }
    .tm-plan-master-head strong { color: var(--adm-slate-800); font-size: 11px; font-weight: 850; }
    .tm-plan-master-head span { margin-top: 2px; color: var(--adm-slate-500); font-size: 9.5px; font-weight: 650; }
    .tm-plan-trainer-list { display: flex; min-width: 0; max-height: min(560px, 65vh); flex-direction: column; gap: 7px; overflow-x: hidden; overflow-y: auto; padding: 8px; overscroll-behavior: contain; scrollbar-gutter: stable; }
    .tm-plan-trainer { display: flex; width: 100%; min-width: 0; max-width: 100%; align-items: flex-start; flex-direction: column; gap: 9px; box-sizing: border-box; padding: 12px 11px; border: 1px solid transparent; border-radius: 10px; background: transparent; color: inherit; text-align: left; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease; }
    .tm-plan-trainer:hover { border-color: #bfdbfe; background: #f8fbff; box-shadow: 0 5px 12px rgba(15, 23, 42, .06); }
    .tm-plan-trainer.is-selected { border-color: #93c5fd; background: #eff6ff; box-shadow: inset 3px 0 0 var(--adm-primary); }
    .tm-plan-trainer-copy { display: flex; min-width: 0; flex: 1; flex-direction: column; }
    .tm-plan-trainer-copy strong { min-width: 0; overflow-wrap: anywhere; color: var(--adm-slate-900); font-size: 12px; font-weight: 850; }
    .tm-plan-trainer-copy span, .tm-plan-trainer-copy small { min-width: 0; max-width: 100%; margin-top: 2px; color: var(--adm-slate-500); font-size: 10px; font-weight: 650; overflow-wrap: anywhere; white-space: normal; word-break: break-word; }
    .tm-plan-trainer-copy small { color: var(--adm-slate-600); font-weight: 750; }
    .tm-plan-row-state { display: inline-flex; flex: 0 0 auto; align-items: center; }
    .tm-plan-action { display: inline-flex; min-height: 32px; align-items: center; justify-content: center; gap: 6px; padding: 6px 10px; border: 1px solid var(--adm-border-strong); border-radius: 8px; background: #fff; color: var(--adm-slate-700); font-size: 10.5px; font-weight: 800; text-decoration: none; cursor: pointer; transition: border-color .15s ease, color .15s ease, box-shadow .15s ease; }
    .tm-plan-action:hover { border-color: #93c5fd; color: var(--adm-primary-dark); box-shadow: 0 4px 10px rgba(15, 23, 42, .07); }
    .tm-plan-trainer:focus-visible, .tm-plan-action:focus-visible, .tm-plan-feedback textarea:focus-visible, .tm-plan-feedback button:focus-visible { outline: 3px solid rgba(37, 99, 235, .28); outline-offset: 2px; }
    .tm-plan-review-shell { min-width: 0; }
    .tm-plan-review { overflow: hidden; border: 1px solid var(--adm-border); border-radius: 13px; background: #fff; box-shadow: var(--adm-shadow); }
    .tm-plan-review > .tm-section-head { padding: 15px 16px; border-bottom: 1px solid #f1f5f9; }
    .tm-plan-review > .tm-section-head h3 { margin: 0; }
    .tm-plan-state { display: inline-flex; flex: 0 0 auto; padding: 4px 8px; border: 1px solid; border-radius: 999px; font-size: 9px; font-weight: 850; letter-spacing: .035em; text-transform: uppercase; }
    .tm-plan-state.not-submitted { border-color: var(--adm-border-strong); background: #f8fafc; color: var(--adm-slate-500); }
    .tm-plan-state.needs-review { border-color: #fcd34d; background: var(--adm-amber-soft); color: #92400e; }
    .tm-plan-state.feedback-sent { border-color: #a7f3d0; background: var(--adm-green-soft); color: #047857; }
    .tm-plan-current-section { margin: 16px; padding: 15px 16px; border: 1px solid #bfdbfe; border-left: 4px solid var(--adm-primary); border-radius: 11px; background: #fbfdff; }
    .tm-plan-section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .tm-plan-section-heading h4 { margin: 0; color: var(--adm-slate-900); font-size: 12px; font-weight: 850; }
    .tm-plan-section-heading > div > span { display: block; margin-bottom: 3px; color: var(--adm-primary); font-size: 9px; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
    .tm-plan-section-heading p { margin: 3px 0 0; color: var(--adm-slate-500); font-size: 10px; font-weight: 600; }
    .tm-plan-current { display: flex; align-items: center; gap: 11px; margin-top: 12px; }
    .tm-plan-file-icon { display: inline-flex; width: 42px; height: 42px; flex: 0 0 42px; align-items: center; justify-content: center; border: 1px solid var(--adm-border); border-radius: 11px; background: #f8fafc; color: var(--adm-slate-600); font-size: 20px; line-height: 1; }
    .tm-plan-file-icon i { display: block; line-height: 1; }
    .tm-plan-file-icon.pdf { border-color: #fecaca; background: #fef2f2; color: #dc2626; }
    .tm-plan-file-icon.word { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .tm-plan-file-copy { display: flex; min-width: 0; flex: 1; flex-direction: column; }
    .tm-plan-current strong { min-width: 0; max-width: 100%; overflow-wrap: anywhere; color: var(--adm-slate-900); font-size: 11.5px; font-weight: 850; word-break: break-word; }
    .tm-plan-file-copy > span { margin-top: 2px; color: var(--adm-slate-500); font-size: 10px; font-weight: 650; }
    .tm-plan-actions { display: flex; flex: 0 0 auto; align-items: center; gap: 7px; flex-wrap: wrap; }
    .tm-plan-unavailable { display: inline-flex; align-items: center; gap: 5px; color: var(--adm-slate-400); font-size: 10px; font-weight: 750; }
    .tm-plan-feedback { margin: 0 16px 16px; padding: 15px 16px; border: 1px solid var(--adm-border); border-radius: 11px; }
    .tm-plan-comments { margin-top: 12px; border-top: 1px solid #dbeafe; }
    .tm-plan-comment { padding: 13px 1px; border-bottom: 1px solid var(--adm-border); }
    .tm-plan-comment:last-child { padding-bottom: 2px; border-bottom: 0; }
    .tm-plan-comment-meta { display: flex; align-items: flex-start; flex-direction: column; gap: 2px; }
    .tm-plan-comment-meta strong { color: var(--adm-slate-800); font-size: 10.5px; font-weight: 850; }
    .tm-plan-comment-meta time { color: var(--adm-slate-500); font-size: 9px; font-weight: 650; }
    .tm-plan-comments p { margin: 8px 0 0; color: var(--adm-slate-700); font-size: 11.5px; font-weight: 550; line-height: 1.6; white-space: pre-wrap; }
    .tm-plan-needs-review { display: flex; align-items: flex-start; flex-direction: column; gap: 2px; margin-top: 12px; padding: 10px 12px; border-left: 3px solid var(--adm-amber); background: #fffbeb; }
    .tm-plan-needs-review strong { color: #92400e; font-size: 10.5px; font-weight: 850; }
    .tm-plan-needs-review span { color: #a16207; font-size: 10px; font-weight: 650; }
    .tm-plan-feedback-form { display: flex; flex-direction: column; margin-top: 13px; padding-top: 13px; border-top: 1px solid #f1f5f9; }
    .tm-plan-form-heading { margin-bottom: 10px; }
    .tm-plan-form-heading h5 { margin: 0; color: var(--adm-slate-900); font-size: 11.5px; font-weight: 850; }
    .tm-plan-form-heading p { margin: 3px 0 0; color: var(--adm-slate-500); font-size: 10px; font-weight: 600; }
    .tm-plan-feedback label { margin-bottom: 5px; color: var(--adm-slate-700); font-size: 10px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .tm-plan-feedback textarea { width: 100%; min-height: 92px; resize: vertical; padding: 9px 10px; border: 1px solid var(--adm-border-strong); border-radius: 9px; background: #fff; color: var(--adm-slate-800); font: inherit; font-size: 11.5px; }
    .tm-plan-feedback-form > div:last-child { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 8px; }
    .tm-plan-feedback form small { color: var(--adm-slate-500); font-size: 9.5px; font-weight: 650; }
    .tm-plan-feedback button { display: inline-flex; min-height: 34px; align-items: center; justify-content: center; gap: 6px; padding: 7px 11px; border: 1px solid var(--adm-primary); border-radius: 8px; background: var(--adm-primary); color: #fff; font-size: 10.5px; font-weight: 850; cursor: pointer; transition: border-color .15s ease, background .15s ease, box-shadow .15s ease; }
    .tm-plan-feedback button:hover { border-color: var(--adm-primary-dark); background: var(--adm-primary-dark); box-shadow: 0 5px 12px rgba(37, 99, 235, .16); }
    .tm-plan-inline-empty { display: flex; align-items: center; gap: 11px; padding: 24px 16px; }
    .tm-plan-inline-empty > i { color: var(--adm-slate-400); font-size: 24px; }
    .tm-plan-inline-empty div { display: flex; flex-direction: column; }
    .tm-plan-inline-empty strong { color: var(--adm-slate-700); font-size: 11.5px; font-weight: 850; }
    .tm-plan-inline-empty span { margin-top: 2px; color: var(--adm-slate-500); font-size: 10px; font-weight: 650; }
    .tm-plan-review-empty { min-height: 150px; border: 1px dashed var(--adm-border-strong); border-radius: 13px; background: #f8fafc; }
    .tm-metric-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        margin-bottom: 10px;
        border-radius: 10px;
        font-size: 16px;
    }
    .tm-metric-icon.blue { background: var(--adm-primary-soft); color: var(--adm-primary); }
    .tm-metric-icon.indigo { background: var(--adm-indigo-soft); color: var(--adm-indigo); }
    .tm-metric-icon.amber { background: var(--adm-amber-soft); color: var(--adm-amber); }
    .tm-metric-icon.green { background: var(--adm-green-soft); color: var(--adm-green); }
    .tm-metric-icon.red { background: var(--adm-red-soft); color: var(--adm-red); }
    .tm-metric-label {
        display: block;
        margin-bottom: 4px;
        color: var(--adm-slate-400);
        font-size: 9.5px;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .tm-metric strong {
        display: block;
        overflow-wrap: anywhere;
        color: var(--adm-slate-900);
        font-size: 18px;
        font-weight: 900;
        line-height: 1.25;
    }
    .tm-metric small {
        display: block;
        margin-top: 4px;
        color: var(--adm-slate-500);
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.35;
    }

    .tm-overview-grid { display: grid; gap: 14px; margin-top: 15px; }
    .tm-info-section {
        overflow: hidden;
        padding: 16px;
        border: 1px solid var(--adm-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .04);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .tm-section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 14px;
    }
    .tm-section-head h3 {
        margin: 0;
        color: var(--adm-slate-900);
        font-size: 14px;
        font-weight: 850;
    }
    .tm-section-head p {
        margin: 2px 0 0;
        color: var(--adm-slate-500);
        font-size: 11.5px;
        font-weight: 600;
    }
    .tm-section-count {
        padding: 5px 9px;
        border-radius: 999px;
        background: #f1f5f9;
        color: var(--adm-slate-600);
        font-size: 10.5px;
        font-weight: 800;
        white-space: nowrap;
    }
    .tm-fact-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 13px 18px;
        margin: 0;
    }
    .tm-fact-grid div { min-width: 0; }
    .tm-fact-grid dt {
        margin-bottom: 4px;
        color: var(--adm-slate-400);
        font-size: 9.5px;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .tm-fact-grid dd {
        margin: 0;
        overflow-wrap: anywhere;
        color: var(--adm-slate-700);
        font-size: 12px;
        font-weight: 750;
    }
    .tm-attendance-summary { margin: 0; }
    .tm-inline-empty { min-height: 150px !important; }

    .tm-detail-controls {
        display: grid;
        grid-template-columns: minmax(240px, 1.5fr) repeat(2, minmax(150px, .7fr));
        align-items: end;
        gap: 11px;
        margin-bottom: 14px;
        padding: 14px;
        border: 1px solid var(--adm-border);
        border-radius: 13px;
        background: #f8fafc;
    }
    .tm-attendance-controls { margin-top: 15px; }
    .tm-attendance-session-picker {
        margin-bottom: 14px;
        padding: 14px;
        border: 1px solid var(--adm-border);
        border-radius: 13px;
        background: #f8fafc;
    }
    .tm-attendance-session-picker .tm-filter-field { max-width: 560px; }
    .tm-selected-session {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 14px;
        padding: 14px 16px;
        border: 1px solid #bfdbfe;
        border-radius: 13px;
        background: var(--adm-primary-soft);
    }
    .tm-selected-session h4 { margin: 0; color: var(--adm-slate-900); font-size: 14px; font-weight: 850; }
    .tm-selected-session-meta { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 7px 14px; color: var(--adm-slate-600); font-size: 11.5px; font-weight: 700; }
    .tm-selected-session-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .tm-selected-session-meta i { color: var(--adm-primary); }

    .tm-session-list,
    .tm-table-wrap {
        overflow: hidden;
        border: 1px solid var(--adm-border);
        border-radius: 14px;
        background: #fff;
    }
    .tm-session-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: flex-start;
        gap: 14px;
        padding: 15px 16px;
        border-bottom: 1px solid #f1f5f9;
        transition: background .15s ease;
    }
    .tm-session-row:last-child { border-bottom: 0; }
    .tm-session-row:hover { background: #fbfcfe; }
    .tm-session-date {
        overflow: hidden;
        width: 48px;
        flex: 0 0 48px;
        border: 1px solid var(--adm-border);
        border-radius: 11px;
        text-align: center;
    }
    .tm-session-date span {
        display: block;
        padding: 3px 4px;
        background: var(--adm-primary);
        color: #fff;
        font-size: 9px;
        font-weight: 850;
        letter-spacing: .05em;
    }
    .tm-session-date strong { display: block; padding: 5px 4px; color: var(--adm-slate-900); font-size: 17px; font-weight: 900; }
    .tm-session-main { min-width: 0; }
    .tm-session-title-line { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .tm-session-title-line h4 { margin: 0; overflow-wrap: anywhere; color: var(--adm-slate-900); font-size: 13.5px; font-weight: 850; }
    .tm-session-meta,
    .tm-session-secondary {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 14px;
        margin-top: 7px;
        color: var(--adm-slate-500);
        font-size: 11.5px;
        font-weight: 650;
    }
    .tm-session-meta span,
    .tm-session-secondary span { display: inline-flex; align-items: center; gap: 5px; }
    .tm-session-meta i,
    .tm-session-secondary i { color: var(--adm-slate-400); }
    .tm-session-attendance { color: var(--adm-green) !important; font-weight: 750; }
    .tm-session-attendance.unavailable { color: var(--adm-slate-400) !important; }
    .tm-session-actions { margin-top: 10px; }
    .tm-session-attendance-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 34px;
        padding: 7px 10px;
        border: 1px solid #bfdbfe;
        border-radius: 9px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        cursor: pointer;
        font-family: inherit;
        font-size: 11px;
        font-weight: 800;
        transition: border-color .15s ease, background .15s ease, color .15s ease;
    }
    .tm-session-attendance-btn:hover { border-color: var(--adm-primary); background: #dbeafe; color: var(--adm-primary-dark); }

    .tm-table-wrap { overflow-x: auto; }
    .tm-table { width: 100%; min-width: 720px; border-collapse: collapse; }
    .tm-table th {
        padding: 10px 14px;
        border-bottom: 1px solid var(--adm-border);
        background: #f8fafc;
        color: var(--adm-slate-500);
        font-size: 9.5px;
        font-weight: 850;
        letter-spacing: .055em;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .tm-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: var(--adm-slate-600);
        font-size: 12px;
        font-weight: 650;
        vertical-align: middle;
    }
    .tm-table tbody tr:last-child td { border-bottom: 0; }
    .tm-table tbody tr { transition: background .15s ease; }
    .tm-table tbody tr:hover { background: #fbfcfe; }
    .tm-person-cell { display: flex; align-items: center; gap: 10px; min-width: 190px; }
    .tm-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 10px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        font-size: 12px;
        font-weight: 900;
    }
    .tm-person-cell strong { display: block; color: var(--adm-slate-900); font-size: 12.5px; font-weight: 800; }
    .tm-person-cell small { display: block; margin-top: 2px; color: var(--adm-slate-400); font-size: 10px; font-weight: 650; }
    .tm-enrolment-status { padding: 5px 8px; letter-spacing: 0; text-transform: none; }
    .tm-enrolment-status.success { border-color: #a7f3d0; background: var(--adm-green-soft); color: var(--adm-green); }
    .tm-enrolment-status.warning { border-color: #fde68a; background: var(--adm-amber-soft); color: var(--adm-amber); }
    .tm-enrolment-status.danger { border-color: #fecaca; background: var(--adm-red-soft); color: var(--adm-red); }
    .tm-attendance-inline { color: var(--adm-slate-700); font-size: 11px; font-weight: 700; }
    .tm-muted { color: var(--adm-slate-400); font-size: 11px; font-weight: 650; }
    .tm-results-empty { min-height: 220px !important; border: 1px solid var(--adm-border) !important; border-radius: 14px !important; }
    .tm-attendance-empty { min-height: 150px !important; }

    .tm-pager {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 14px;
        min-height: 62px;
        margin-top: 14px;
        padding: 13px 14px;
        border: 1px solid var(--adm-border);
        border-radius: 13px;
        background: #fff;
    }
    .tm-pager[hidden] { display: none; }
    .tm-rows-control { display: flex; align-items: center; gap: 7px; margin: 0; }
    .tm-rows-control > span { margin: 0; }
    main.adm-wrap .tm-rows-control select { width: 68px; min-height: 36px; padding: 6px 8px; cursor: pointer; }
    .tm-page-info { color: var(--adm-slate-500); font-size: 11.5px; font-weight: 700; text-align: center; }
    .tm-page-info strong { color: var(--adm-slate-900); font-weight: 850; }
    .tm-pager-controls { display: flex; align-items: center; gap: 5px; }
    .tm-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-height: 36px;
        padding: 7px 11px;
        border: 1px solid var(--adm-border);
        border-radius: 9px;
        background: #fff;
        color: var(--adm-slate-600);
        cursor: pointer;
        font-family: inherit;
        font-size: 11.5px;
        font-weight: 800;
        transition: .15s ease;
    }
    .tm-page-btn:hover:not(:disabled) { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .tm-page-btn:disabled { cursor: not-allowed; opacity: .45; }
    .tm-page-current {
        min-width: 54px;
        padding: 7px 8px;
        border-radius: 9px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        font-size: 11.5px;
        font-weight: 850;
        text-align: center;
    }

    .tm-training-row:focus-visible,
    .tm-master-toggle:focus-visible,
    .tm-filter-toggle:focus-visible,
    .tm-search-clear:focus-visible,
    .tm-session-attendance-btn:focus-visible,
    .tm-reset-btn:focus-visible,
    .tm-page-btn:focus-visible,
    .tm-detail-tabs button:focus-visible,
    .tm-trainer-link:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .3);
        outline-offset: 2px;
    }

    @media (max-width: 1279.98px) {
        .tm-master { width: 320px; flex-basis: 320px; }
        .tm-master.is-collapsed { width: 44px; flex-basis: 44px; }
        .tm-master-expanded { width: 320px; min-width: 320px; }
        .tm-overview-metrics,
        .tm-attendance-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tm-detail-identity { grid-template-columns: 1fr 1fr; }
        .tm-detail-identity > div:first-child { grid-column: 1 / -1; }
        .tm-fact-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 991.98px) {
        .tm-workspace,
        .tm-workspace.is-master-collapsed { flex-direction: column; }
        .tm-master,
        .tm-master.is-collapsed {
            position: static;
            width: 100%;
            flex: 0 0 auto;
            height: auto;
            min-height: 0;
            max-height: none;
            overflow: hidden;
            border-color: var(--adm-border);
            background: #fff;
            box-shadow: var(--adm-shadow);
        }
        .tm-master-expanded,
        .tm-master.is-collapsed .tm-master-expanded { min-width: 0; height: auto; max-height: none; opacity: 1; transform: none; visibility: visible; }
        .tm-master-collapsed,
        .tm-master-toggle { display: none; }
        .tm-training-list,
        .tm-tab-panel { overflow: visible; overscroll-behavior: auto; }
        .tm-detail { width: 100%; }
        .tm-master-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .tm-plan-layout { grid-template-columns: 1fr; }
        .tm-plan-trainer-list { max-height: 360px; }
    }

    @media (max-width: 767.98px) {
        .tm-master-filter-grid,
        .tm-detail-controls { grid-template-columns: 1fr 1fr; }
        .tm-search-field { grid-column: 1 / -1; }
        .tm-panel-head,
        .tm-master-controls,
        .tm-training-row,
        .tm-detail-head,
        .tm-tab-panel { padding-right: 16px; padding-left: 16px; }
        .tm-detail-title-row { flex-direction: column; gap: 12px; }
        .tm-detail-badges { justify-content: flex-start; }
        .tm-detail-identity { grid-template-columns: 1fr; }
        .tm-detail-identity > div:first-child { grid-column: auto; }
        .tm-detail-tabs { overflow-x: auto; padding: 0 10px; }
        .tm-detail-tabs button { flex: 1 0 auto; }
        .tm-plan-tab-head { flex-direction: column; }
        .tm-plan-current { align-items: flex-start; flex-direction: column; }
        .tm-fact-grid { grid-template-columns: 1fr 1fr; }
        .tm-session-title-line { align-items: flex-start; flex-direction: column; gap: 7px; }
        .tm-selected-session { align-items: flex-start; flex-direction: column; }
        .tm-selected-session-meta { justify-content: flex-start; }
        .tm-pager { grid-template-columns: 1fr; gap: 11px; }
        .tm-page-info { order: -1; }
        .tm-rows-control,
        .tm-pager-controls { justify-content: center; }
    }

    @media (max-width: 479.98px) {
        .tm-master-filter-grid,
        .tm-detail-controls,
        .tm-overview-metrics,
        .tm-attendance-summary,
        .tm-fact-grid { grid-template-columns: 1fr; }
        .tm-search-field { grid-column: auto; }
        .tm-reset-btn { width: 100%; }
        .tm-training-meta,
        .tm-training-row-foot { margin-left: 0; }
        .tm-training-row-foot { align-items: flex-start; flex-direction: column; }
        .tm-detail-tabs { display: grid; grid-template-columns: repeat(2, 1fr); overflow: visible; }
        .tm-plan-trainer { align-items: flex-start; flex-direction: column; }
        .tm-plan-row-state { align-self: flex-start; }
        .tm-plan-comment-meta { align-items: flex-start; flex-direction: column; gap: 2px; }
        .tm-plan-feedback-form > div:last-child { align-items: stretch; flex-direction: column; }
        .tm-plan-feedback button { width: 100%; }
        .tm-session-row { gap: 11px; }
        .tm-pager-controls { display: grid; grid-template-columns: 1fr auto 1fr; }
    }

    @media (prefers-reduced-motion: reduce) {
        .tm-master,
        .tm-master-expanded,
        .tm-master-collapsed { transition-duration: 0.01ms !important; }
        .tm-master-filter-panel { transition-duration: .01ms !important; transition-delay: 0s !important; }
    }
</style>
