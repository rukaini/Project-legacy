<style>
    .dir-directory-shell {
        overflow: hidden;
        border: 1px solid var(--adm-border);
        border-radius: 18px;
        background: #fff;
        box-shadow: var(--adm-shadow);
    }

    .dir-section-tabs {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 9px 14px 0;
        overflow-x: auto;
        border-bottom: 1px solid var(--adm-border);
        background: #fff;
        scrollbar-width: thin;
    }
    .dir-section-tab {
        position: relative;
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 8px;
        min-height: 48px;
        margin: 0;
        padding: 10px 13px 11px;
        border: 0;
        border-bottom: 2px solid transparent;
        background: transparent;
        color: var(--adm-slate-500);
        cursor: pointer;
        font: inherit;
        font-size: 12.5px;
        font-weight: 800;
        white-space: nowrap;
        transition: color .15s ease, border-color .15s ease, background .15s ease;
    }
    .dir-section-tab:hover { color: var(--adm-slate-900); background: #f8fafc; }
    .dir-section-tab.active { border-bottom-color: var(--adm-primary); color: var(--adm-primary); }
    .dir-section-tab > i { font-size: 14px; }
    .dir-tab-count {
        min-width: 22px;
        padding: 2px 7px;
        border-radius: 999px;
        background: #f1f5f9;
        color: var(--adm-slate-500);
        font-size: 10px;
        font-weight: 850;
        text-align: center;
    }
    .dir-section-tab.active .dir-tab-count { background: var(--adm-primary-soft); color: var(--adm-primary); }

    .dir-tabpanel { display: none; }
    .dir-tabpanel.active { display: block; }

    .dir-toolbar {
        display: grid;
        grid-template-columns: minmax(280px, 1.7fr) repeat(3, minmax(145px, .8fr)) minmax(145px, .72fr) auto;
        align-items: end;
        gap: 11px;
        padding: 15px 18px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }
    .dir-control { display: flex; min-width: 0; flex-direction: column; }
    .dir-control > label,
    .dir-rows-control > span {
        margin: 0 0 5px;
        color: var(--adm-slate-500);
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .055em;
        text-transform: uppercase;
    }
    .dir-search-control { position: relative; }
    .dir-search-control > i {
        position: absolute;
        top: 50%;
        left: 12px;
        transform: translateY(-50%);
        color: var(--adm-slate-400);
        font-size: 14px;
        pointer-events: none;
    }
    main.adm-wrap .dir-control input,
    main.adm-wrap .dir-control select,
    main.adm-wrap .dir-rows-control select {
        width: 100%;
        min-height: 40px;
        padding: 8px 31px 8px 11px;
        border: 1px solid var(--adm-border);
        border-radius: 10px;
        outline: none;
        background: #fff;
        color: var(--adm-slate-700);
        font-family: inherit;
        font-size: 12.5px;
        font-weight: 650;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    main.adm-wrap .dir-search-control input { padding-right: 39px; padding-left: 35px; }
    main.adm-wrap .dir-search-control input[type="search"] { appearance: textfield; -webkit-appearance: textfield; }
    main.adm-wrap .dir-search-control input[type="search"]::-webkit-search-decoration,
    main.adm-wrap .dir-search-control input[type="search"]::-webkit-search-cancel-button { display: none; appearance: none; -webkit-appearance: none; }
    .dir-search-clear {
        position: absolute;
        top: 50%;
        right: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 27px;
        height: 27px;
        padding: 0;
        transform: translateY(-50%);
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: var(--adm-slate-400);
        cursor: pointer;
        font-size: 15px;
    }
    .dir-search-clear[hidden] { display: none; }
    .dir-search-clear:hover { background: #f1f5f9; color: var(--adm-slate-700); }
    .dir-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 40px;
        padding: 8px 12px;
        border: 1px solid var(--adm-border);
        border-radius: 10px;
        background: #fff;
        color: var(--adm-slate-600);
        cursor: pointer;
        font: inherit;
        font-size: 11.5px;
        font-weight: 800;
        white-space: nowrap;
        transition: .15s ease;
    }
    .dir-reset:hover:not(:disabled) { border-color: #fecaca; background: var(--adm-red-soft); color: var(--adm-red); }
    .dir-reset:disabled { cursor: not-allowed; opacity: .45; }

    .dir-results { padding: 16px 18px 18px; }
    .dir-table-frame {
        overflow: hidden;
        border: 1px solid var(--adm-border);
        border-radius: 14px;
        background: #fff;
    }
    .dir-table-frame[hidden] { display: none; }
    .dir-table-scroll { max-width: 100%; overflow-x: auto; overscroll-behavior-inline: contain; }
    .dir-table { width: 100%; min-width: 760px; border-collapse: collapse; }
    .dir-table thead th {
        padding: 11px 15px;
        border-bottom: 1px solid var(--adm-border);
        background: #f8fafc;
        color: var(--adm-slate-500);
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .055em;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .dir-table tbody td {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f5f9;
        color: var(--adm-slate-700);
        font-size: 12.5px;
        vertical-align: middle;
    }
    .dir-table tbody tr:last-child td { border-bottom: 0; }
    .dir-row { transition: background-color .12s ease; }
    .dir-row:hover { background: #f8fafc; }
    .dir-row[data-inspectable] { cursor: pointer; }
    .dir-row:focus-visible { outline: 3px solid rgba(37, 99, 235, .25); outline-offset: -3px; background: var(--adm-primary-soft); }
    .dir-action-heading,
    .dir-action-cell { text-align: right !important; }

    .dir-identity { display: inline-flex; align-items: center; gap: 10px; min-width: 190px; }
    .dir-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        font-size: 12px;
        font-weight: 900;
    }
    .dir-avatar.trainer { border-color: #a7f3d0; background: var(--adm-green-soft); color: var(--adm-green); }
    .dir-identity strong,
    .dir-session-title strong,
    .dir-date-value strong,
    .dir-schedule-value strong { display: block; color: var(--adm-slate-900); font-size: 12.75px; font-weight: 850; line-height: 1.3; }
    .dir-identity small,
    .dir-session-title small,
    .dir-date-value small,
    .dir-schedule-value small { display: block; margin-top: 2px; color: var(--adm-slate-500); font-size: 10.5px; font-weight: 650; line-height: 1.35; }
    .dir-session-title small { display: flex; align-items: center; gap: 4px; }
    .dir-expertise { display: block; max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dir-count-value { color: var(--adm-slate-900); font-weight: 850; }

    .dir-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border: 1px solid;
        border-radius: 999px;
        font-size: 9.5px;
        font-weight: 850;
        letter-spacing: .04em;
        line-height: 1.3;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .dir-badge.blue { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .dir-badge.indigo { border-color: #c7d2fe; background: var(--adm-indigo-soft); color: var(--adm-indigo); }
    .dir-badge.green { border-color: #a7f3d0; background: var(--adm-green-soft); color: var(--adm-green); }
    .dir-badge.amber { border-color: #fde68a; background: var(--adm-amber-soft); color: var(--adm-amber); }
    .dir-badge.slate { border-color: var(--adm-border); background: #f1f5f9; color: var(--adm-slate-600); }

    .dir-view,
    .dir-training-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-height: 34px;
        padding: 6px 11px;
        border: 1px solid var(--adm-border);
        border-radius: 9px;
        background: #fff;
        color: var(--adm-primary);
        cursor: pointer;
        font: inherit;
        font-size: 11.5px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        transition: .15s ease;
    }
    .dir-view:hover,
    .dir-training-link:hover { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary-dark); text-decoration: none; }

    .dir-empty-state {
        min-height: 250px;
        padding: 42px 22px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--adm-border);
        border-radius: 14px;
        background: #fff;
        text-align: center;
    }
    .dir-empty-state[hidden] { display: none; }
    .dir-empty-icon { display: inline-flex; align-items: center; justify-content: center; width: 52px; height: 52px; margin-bottom: 12px; border-radius: 999px; background: #f1f5f9; color: var(--adm-slate-400); font-size: 21px; }
    .dir-empty-state h3 { margin: 0 0 5px; color: var(--adm-slate-700); font-size: 14px; font-weight: 850; }
    .dir-empty-state p { max-width: 430px; margin: 0; color: var(--adm-slate-500); font-size: 12.5px; line-height: 1.5; }

    .dir-pager {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 14px;
        min-height: 60px;
        margin-top: 14px;
        padding: 11px 13px;
        border: 1px solid var(--adm-border);
        border-radius: 13px;
        background: #f8fafc;
    }
    .dir-rows-control { display: inline-flex; align-items: center; gap: 7px; margin: 0; }
    .dir-rows-control > span { margin: 0; }
    main.adm-wrap .dir-rows-control select { width: 68px; min-height: 36px; padding: 6px 8px; cursor: pointer; }
    .dir-page-info { color: var(--adm-slate-500); font-size: 11.5px; font-weight: 700; text-align: center; }
    .dir-page-info strong { color: var(--adm-slate-900); font-weight: 850; }
    .dir-page-controls { display: flex; align-items: center; gap: 5px; }
    .dir-page-btn {
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
        font: inherit;
        font-size: 11.5px;
        font-weight: 800;
        transition: .15s ease;
    }
    .dir-page-btn:hover:not(:disabled) { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .dir-page-btn:disabled { cursor: not-allowed; opacity: .45; }
    .dir-current-page { min-width: 54px; padding: 7px 8px; border-radius: 9px; background: var(--adm-primary-soft); color: var(--adm-primary); font-size: 11.5px; font-weight: 850; text-align: center; }

    .dir-section-tab:focus-visible,
    .dir-search-clear:focus-visible,
    .dir-reset:focus-visible,
    .dir-view:focus-visible,
    .dir-training-link:focus-visible,
    .dir-page-btn:focus-visible,
    .vm-close:focus-visible,
    .vm-certificate-view:focus-visible { outline: 3px solid rgba(37, 99, 235, .3); outline-offset: 2px; }

    #viewModal .modal-dialog { max-width: 680px; }
    #viewModal .modal-content { overflow: hidden; border: 1px solid var(--adm-border); border-radius: 18px; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, .2); }
    .vm-banner { position: relative; padding: 23px 24px 19px; border-bottom: 1px solid var(--adm-border); background: #fff; }
    .vm-close { position: absolute; top: 14px; right: 14px; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; padding: 0; border: 1px solid var(--adm-border); border-radius: 9px; background: #fff; color: var(--adm-slate-500); cursor: pointer; }
    .vm-close:hover { background: #f8fafc; color: var(--adm-slate-900); }
    .vm-top { display: flex; align-items: center; gap: 14px; padding-right: 40px; }
    .vm-avatar { display: flex; align-items: center; justify-content: center; width: 62px; height: 62px; flex: 0 0 62px; border: 1px solid #c7d2fe; border-radius: 999px; background: var(--adm-indigo-soft); color: var(--adm-indigo); font-size: 23px; font-weight: 900; }
    .vm-banner.teacher .vm-avatar { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary); }
    .vm-banner.guru .vm-avatar { border-color: #c7d2fe; background: var(--adm-indigo-soft); color: var(--adm-indigo); }
    .vm-banner.outsider .vm-avatar { border-color: #fde68a; background: var(--adm-amber-soft); color: var(--adm-amber); }
    .vm-banner.trainer .vm-avatar { border-color: #a7f3d0; background: var(--adm-green-soft); color: var(--adm-green); }
    .vm-headtxt { min-width: 0; }
    .vm-name { margin: 0; color: var(--adm-slate-900); font-size: 19px; font-weight: 850; line-height: 1.25; }
    .vm-sub { margin-top: 3px; color: var(--adm-slate-500); font-size: 12px; font-weight: 700; }
    .vm-tag { display: inline-flex; align-items: center; gap: 5px; margin-top: 7px; padding: 3px 8px; border: 1px solid var(--adm-border); border-radius: 999px; background: #f8fafc; color: var(--adm-slate-600); font-size: 9.5px; font-weight: 850; letter-spacing: .045em; text-transform: uppercase; }
    .vm-tag[hidden] { display: none; }
    .vm-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--adm-border); background: #f8fafc; }
    .vm-stat { display: flex; flex-direction: column; padding: 12px 13px; border: 1px solid var(--adm-border); border-radius: 11px; background: #fff; }
    .vm-stat-label { color: var(--adm-slate-500); font-size: 9.5px; font-weight: 850; letter-spacing: .055em; text-transform: uppercase; }
    .vm-stat-value { margin-top: 3px; color: var(--adm-slate-900); font-size: 19px; font-weight: 900; line-height: 1.1; }
    .vm-stat-sub { margin-top: 2px; color: var(--adm-slate-500); font-size: 10.5px; font-weight: 650; }
    .vm-body { padding: 18px 22px 22px; }
    .vm-section-title { margin: 0 0 8px; color: var(--adm-slate-500); font-size: 10px; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
    .vm-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 20px; margin: 0; }
    .vm-row { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
    .vm-row dt { margin: 0 0 3px; color: var(--adm-slate-400); font-size: 9.5px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .vm-row dd { margin: 0; color: var(--adm-slate-700); font-size: 12.5px; font-weight: 700; overflow-wrap: anywhere; }
    .vm-row a { color: var(--adm-primary); text-decoration: none; }
    .vm-row a:hover { text-decoration: underline; }
    .vm-empty { grid-column: 1 / -1; padding: 16px 0; color: var(--adm-slate-500); font-size: 12.5px; }
    .vm-certificates { margin-top: 18px; padding-top: 17px; border-top: 1px solid var(--adm-border); }
    .vm-certificates[hidden] { display: none; }
    .vm-certificate-list { display: grid; gap: 9px; }
    .vm-certificate-row { display: flex; align-items: center; gap: 11px; padding: 11px 12px; border: 1px solid var(--adm-border); border-radius: 11px; background: #fff; transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease; }
    .vm-certificate-row:hover { border-color: #bfdbfe; background: #fbfdff; box-shadow: 0 7px 18px rgba(15, 23, 42, .05); }
    .vm-certificate-icon { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; flex: 0 0 36px; border-radius: 9px; background: var(--adm-green-soft); color: var(--adm-green); font-size: 16px; }
    .vm-certificate-main { min-width: 0; flex: 1; }
    .vm-certificate-main h4 { margin: 0; color: var(--adm-slate-900); font-size: 12.5px; font-weight: 850; line-height: 1.35; overflow-wrap: anywhere; }
    .vm-certificate-main p { margin: 3px 0 0; color: var(--adm-slate-500); font-size: 10.5px; font-weight: 650; }
    .vm-certificate-view { display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-height: 32px; padding: 6px 10px; border: 1px solid var(--adm-border); border-radius: 8px; background: #fff; color: var(--adm-primary); font-size: 11px; font-weight: 800; text-decoration: none; }
    .vm-certificate-view:hover { border-color: #bfdbfe; background: var(--adm-primary-soft); color: var(--adm-primary-dark); }
    .vm-certificate-unavailable { display: inline-flex; align-items: center; gap: 5px; color: var(--adm-slate-400); font-size: 10.5px; font-weight: 750; white-space: nowrap; }
    .vm-certificate-empty { display: flex; align-items: center; gap: 8px; padding: 14px 12px; border: 1px dashed var(--adm-border-strong); border-radius: 11px; background: #f8fafc; color: var(--adm-slate-500); font-size: 12px; font-weight: 700; }

    @media (max-width: 1399.98px) {
        .dir-toolbar { grid-template-columns: minmax(260px, 1.4fr) repeat(2, minmax(150px, 1fr)); }
        .dir-reset { align-self: end; }
    }
    @media (max-width: 991.98px) {
        .dir-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dir-search-field { grid-column: 1 / -1; }
        .dir-results { padding: 14px; }
    }
    @media (max-width: 767.98px) {
        .dir-section-tabs { padding-right: 8px; padding-left: 8px; }
        .dir-section-tab { min-height: 44px; padding-right: 10px; padding-left: 10px; }
        .dir-section-tab > i { display: none; }
        .dir-toolbar { grid-template-columns: 1fr; padding: 13px 14px; }
        .dir-search-field { grid-column: auto; }
        .dir-reset { width: 100%; }
        .dir-pager { grid-template-columns: 1fr; gap: 10px; }
        .dir-page-info { order: -1; }
        .dir-rows-control,
        .dir-page-controls { justify-content: center; }
        .vm-grid,
        .vm-stats { grid-template-columns: 1fr; }
        .vm-certificate-row { align-items: flex-start; flex-wrap: wrap; }
        .vm-certificate-view,
        .vm-certificate-unavailable { margin-left: 47px; }
    }
    @media (max-width: 479.98px) {
        .dir-results { padding: 10px; }
        .dir-page-controls { display: grid; grid-template-columns: 1fr auto 1fr; }
        .dir-page-btn { padding-right: 8px; padding-left: 8px; }
        .vm-top { align-items: flex-start; }
        .vm-avatar { width: 50px; height: 50px; flex-basis: 50px; font-size: 19px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .dir-section-tab,
        .dir-row,
        .dir-reset,
        .dir-view,
        .dir-training-link,
        .dir-page-btn,
        .vm-certificate-row { transition-duration: .01ms !important; }
    }
</style>
