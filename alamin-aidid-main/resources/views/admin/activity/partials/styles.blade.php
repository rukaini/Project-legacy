<style>
    .act-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    main.adm-wrap .act-stats .adm-stat {
        min-width: 0;
        padding: 16px 17px;
        border: 1px solid var(--adm-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: var(--adm-shadow);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    main.adm-wrap .act-stats .adm-stat:hover {
        transform: translateY(-3px);
        border-color: #bfdbfe;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
    }
    main.adm-wrap .act-stats .adm-stat-icon {
        width: 40px;
        height: 40px;
        margin-bottom: 12px;
        border-radius: 12px;
        font-size: 19px;
    }
    main.adm-wrap .act-stats .adm-stat-label {
        margin-bottom: 5px;
        font-size: 11px;
        font-weight: 850;
    }
    main.adm-wrap .act-stats .adm-stat-value {
        font-size: 27px;
        font-weight: 900;
    }
    main.adm-wrap .act-stats .adm-stat-value--text {
        overflow: hidden;
        font-size: 15px;
        line-height: 1.3;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    main.adm-wrap .act-stats .adm-stat-note { display: grid; gap: 2px; margin-top: 6px; white-space: normal; }
    main.adm-wrap .act-stats .adm-stat-note strong { color: var(--adm-slate-600); font-size: 11px; font-weight: 750; }
    main.adm-wrap .act-stats .adm-stat-note span { color: var(--adm-slate-400); font-size: 10px; font-weight: 650; }
    main.adm-wrap .act-stats .adm-ic-blue,
    .act-row-icon.adm-ic-blue { background: var(--adm-primary-soft); color: var(--adm-primary); }
    main.adm-wrap .act-stats .adm-ic-indigo,
    .act-row-icon.adm-ic-indigo { background: var(--adm-indigo-soft); color: var(--adm-indigo); }
    main.adm-wrap .act-stats .adm-ic-green,
    .act-row-icon.adm-ic-green { background: var(--adm-green-soft); color: var(--adm-green); }
    main.adm-wrap .act-stats .adm-ic-amber,
    .act-row-icon.adm-ic-amber { background: var(--adm-amber-soft); color: var(--adm-amber); }
    .act-row-icon.adm-ic-red { background: var(--adm-red-soft); color: var(--adm-red); }
    .act-row-icon.adm-ic-slate { background: #f1f5f9; color: var(--adm-slate-600); }

    .act-panel {
        overflow: hidden;
        border: 1px solid var(--adm-border);
        border-radius: 18px;
        background: #fff;
        box-shadow: var(--adm-shadow);
    }
    .act-panel-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--adm-border);
    }
    .act-panel-mark {
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
    .act-panel-head h2 {
        margin: 0;
        color: var(--adm-slate-900);
        font-size: 15px;
        font-weight: 850;
    }
    .act-panel-head p {
        margin: 2px 0 0;
        color: var(--adm-slate-500);
        font-size: 12px;
        font-weight: 600;
    }

    .act-controls {
        padding: 16px 20px;
        border-bottom: 1px solid var(--adm-border);
        background: #f8fafc;
    }
    .act-range-tabs {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 14px;
    }
    .act-range-btn {
        display: inline-flex;
        align-items: flex-start;
        flex-direction: column;
        justify-content: center;
        min-height: 46px;
        padding: 6px 12px;
        border: 1px solid transparent;
        border-radius: 999px;
        background: transparent;
        color: var(--adm-slate-500);
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
        transition: .15s ease;
    }
    .act-range-btn span { line-height: 1.2; }
    .act-range-btn small { color: var(--adm-slate-400); font-size: 9.5px; font-weight: 650; line-height: 1.2; }
    .act-range-btn.active small { color: #60a5fa; }
    .act-range-btn:hover {
        border-color: var(--adm-border);
        background: #fff;
        color: var(--adm-slate-900);
    }
    .act-range-btn.active {
        border-color: #bfdbfe;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
    }

    .act-custom-period {
        display: grid;
        grid-template-columns: minmax(155px, .7fr) minmax(155px, .7fr) auto auto;
        align-items: end;
        gap: 10px;
        margin-bottom: 14px;
        padding: 13px;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        background: #fff;
    }
    .act-custom-period[hidden] { display: none; }
    .act-filter-field > span {
        display: block;
        margin-bottom: 6px;
        color: var(--adm-slate-500);
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    main.adm-wrap .act-custom-period input[type="date"] {
        width: 100%;
        min-height: 42px;
        padding: 8px 10px;
        border: 1px solid var(--adm-border);
        border-radius: 10px;
        background: #fff;
        color: var(--adm-slate-700);
        font-family: inherit;
        font-size: 12.5px;
        font-weight: 650;
        outline: 0;
    }
    main.adm-wrap .act-custom-period input[type="date"]:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
    }
    .act-apply-period,
    .act-clear-period {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 42px;
        padding: 8px 13px;
        border-radius: 10px;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
    }
    .act-apply-period { border: 1px solid var(--adm-primary); background: var(--adm-primary); color: #fff; cursor: pointer; }
    .act-apply-period:hover { border-color: var(--adm-primary-dark); background: var(--adm-primary-dark); }
    .act-clear-period { border: 1px solid var(--adm-border); background: #fff; color: var(--adm-slate-600); }
    .act-clear-period:hover { border-color: #cbd5e1; background: #f8fafc; color: var(--adm-slate-900); }
    .act-period-context {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 14px;
        color: var(--adm-slate-500);
        font-size: 11.5px;
        font-weight: 650;
    }
    .act-period-context i { color: var(--adm-primary); }
    .act-period-context strong { color: var(--adm-slate-700); font-weight: 800; }

    .act-filter-toolbar {
        display: grid;
        grid-template-columns: minmax(260px, 1.7fr) repeat(3, minmax(145px, .7fr)) auto;
        align-items: end;
        gap: 11px;
    }
    .act-filter-field { min-width: 0; }
    .act-filter-field label,
    .act-rows-control > span {
        display: block;
        margin-bottom: 6px;
        color: var(--adm-slate-500);
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    main.adm-wrap .act-filter-field select,
    main.adm-wrap .act-search-control input,
    main.adm-wrap .act-rows-control select {
        width: 100%;
        min-height: 42px;
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
    main.adm-wrap .act-filter-field select {
        padding: 8px 32px 8px 11px;
        cursor: pointer;
    }
    main.adm-wrap .act-search-control input { padding: 8px 38px 8px 36px; }
    main.adm-wrap .act-filter-field select:focus,
    main.adm-wrap .act-search-control input:focus,
    main.adm-wrap .act-rows-control select:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
    }
    .act-search-control { position: relative; }
    .act-search-control > i {
        position: absolute;
        top: 50%;
        left: 12px;
        z-index: 1;
        transform: translateY(-50%);
        color: var(--adm-slate-400);
        font-size: 14px;
        pointer-events: none;
    }
    .act-search-clear {
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
        font-size: 17px;
    }
    .act-search-clear:hover { background: #f1f5f9; color: var(--adm-slate-700); }
    .act-reset-btn {
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
    .act-reset-btn:hover:not(:disabled) {
        border-color: #fecaca;
        background: var(--adm-red-soft);
        color: var(--adm-red);
    }
    .act-reset-btn:disabled { cursor: not-allowed; opacity: .45; }

    .act-timeline { background: #fff; }
    .act-day-group + .act-day-group { border-top: 1px solid var(--adm-border); }
    .act-day-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 20px;
        background: #f8fafc;
    }
    .act-day-heading h3 {
        margin: 0;
        color: var(--adm-slate-500);
        font-size: 10.5px;
        font-weight: 850;
        letter-spacing: .055em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .act-day-heading .line { height: 1px; flex: 1; background: var(--adm-border); }
    .act-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: flex-start;
        gap: 14px;
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        transition: background .15s ease;
    }
    .act-row:last-child { border-bottom: 0; }
    .act-row:hover { background: #fbfcfe; }
    .act-row-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 11px;
        font-size: 17px;
    }
    .act-row-main { min-width: 0; }
    .act-row-title {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
        min-height: 24px;
        color: var(--adm-slate-900);
        font-size: 13.5px;
        font-weight: 800;
        line-height: 1.4;
    }
    .act-trainer-link { color: var(--adm-slate-900); text-decoration: none; }
    .act-trainer-link:hover { color: var(--adm-primary); text-decoration: underline; }
    .act-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        border: 1px solid var(--adm-border);
        border-radius: 999px;
        background: #f1f5f9;
        color: var(--adm-slate-600);
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .025em;
        line-height: 1;
        white-space: nowrap;
    }
    .act-type-badge.blue { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .act-type-badge.indigo { border-color: #c7d2fe; background: var(--adm-indigo-soft); color: var(--adm-indigo); }
    .act-type-badge.green { border-color: #a7f3d0; background: var(--adm-green-soft); color: var(--adm-green); }
    .act-type-badge.amber { border-color: #fde68a; background: var(--adm-amber-soft); color: var(--adm-amber); }
    .act-type-badge.red { border-color: #fecaca; background: var(--adm-red-soft); color: var(--adm-red); }
    .act-row-detail {
        margin: 3px 0 0;
        overflow-wrap: anywhere;
        color: var(--adm-slate-500);
        font-size: 12.5px;
        font-weight: 550;
        line-height: 1.5;
    }
    .act-course-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 7px;
        color: var(--adm-primary);
        font-size: 11.5px;
        font-weight: 800;
        text-decoration: none;
    }
    .act-course-link:hover { color: var(--adm-primary-dark); text-decoration: underline; }
    .act-row-time {
        display: block;
        min-width: 130px;
        padding-top: 2px;
        color: var(--adm-slate-500);
        font-size: 11.5px;
        font-style: normal;
        font-weight: 750;
        text-align: right;
        white-space: nowrap;
    }
    .act-row-time .full {
        display: block;
        margin-top: 3px;
        color: var(--adm-slate-400);
        font-size: 10px;
        font-weight: 650;
    }

    main.adm-wrap .act-panel > .adm-empty {
        min-height: 230px;
        border: 0;
        border-radius: 0;
        background: #fff;
    }
    .act-filter-empty { border-top: 0; }

    .act-pager {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 18px;
        min-height: 64px;
        padding: 14px 20px;
        border-top: 1px solid #f1f5f9;
        background: #fff;
    }
    .act-rows-control {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        color: var(--adm-slate-500);
    }
    .act-rows-control > span { margin: 0; }
    main.adm-wrap .act-rows-control select {
        width: 70px;
        min-height: 36px;
        padding: 6px 8px;
        cursor: pointer;
    }
    .act-page-info {
        color: var(--adm-slate-500);
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }
    .act-page-info strong { color: var(--adm-slate-900); font-weight: 850; }
    .act-pager-controls { display: flex; align-items: center; gap: 6px; }
    .act-page-btn {
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
    .act-page-btn:hover:not(:disabled) {
        border-color: #bfdbfe;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
    }
    .act-page-btn:disabled { cursor: not-allowed; opacity: .45; }
    .act-page-current {
        min-width: 62px;
        padding: 7px 9px;
        border-radius: 9px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        font-size: 12px;
        font-weight: 850;
        text-align: center;
    }

    .act-range-btn:focus-visible,
    .act-apply-period:focus-visible,
    .act-clear-period:focus-visible,
    .act-search-clear:focus-visible,
    .act-reset-btn:focus-visible,
    .act-page-btn:focus-visible,
    .act-trainer-link:focus-visible,
    .act-course-link:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .3);
        outline-offset: 2px;
    }

    @media (max-width: 1099.98px) {
        .act-filter-toolbar { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .act-search-field { grid-column: span 2; }
        .act-custom-period { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 767.98px) {
        .act-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .act-panel-head,
        .act-controls,
        .act-day-heading,
        .act-row,
        .act-pager { padding-right: 16px; padding-left: 16px; }
        .act-range-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .act-filter-toolbar { grid-template-columns: 1fr 1fr; }
        .act-search-field { grid-column: 1 / -1; }
        .act-reset-btn { align-self: end; }
        .act-row { grid-template-columns: auto minmax(0, 1fr); }
        .act-row-time {
            grid-column: 2;
            min-width: 0;
            padding-top: 0;
            text-align: left;
        }
        .act-row-time .full { display: inline; margin: 0 0 0 6px; }
        .act-pager { grid-template-columns: 1fr; gap: 12px; }
        .act-page-info { order: -1; }
        .act-rows-control,
        .act-pager-controls { justify-content: center; }
        .act-custom-period { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 479.98px) {
        .act-stats { grid-template-columns: 1fr; }
        .act-filter-toolbar { grid-template-columns: 1fr; }
        .act-search-field { grid-column: auto; }
        .act-reset-btn { width: 100%; }
        .act-row { gap: 11px; }
        .act-row-icon { width: 36px; height: 36px; flex-basis: 36px; }
        .act-row-title { align-items: flex-start; flex-direction: column; gap: 5px; }
        .act-row-time .full { display: block; margin: 2px 0 0; }
        .act-pager-controls { display: grid; grid-template-columns: 1fr auto 1fr; }
        .act-custom-period { grid-template-columns: 1fr; }
        .act-apply-period,
        .act-clear-period { width: 100%; }
    }
</style>
