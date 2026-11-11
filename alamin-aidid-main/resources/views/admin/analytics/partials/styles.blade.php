<style>
    .admin-analytics-shell {
        --admin-analytics-blue: #60a5fa;
        --admin-analytics-blue-hover: #3b82f6;
        color: var(--ana-slate-900);
    }

    .admin-analytics-shell .adm-head { margin-bottom: 20px; }
    .admin-analytics-shell .adm-title { letter-spacing: -.035em; }
    .admin-analytics-shell .adm-subtitle { max-width: 720px; line-height: 1.5; }

    .admin-analytics-shell .ana-filter-card {
        margin-bottom: 24px;
        border-radius: 18px;
        box-shadow: 0 6px 20px rgba(15, 23, 42, .05);
    }
    .admin-analytics-shell .ana-filter-card > .ana-panel-head { padding: 17px 20px; }
    .admin-analytics-shell .ana-filter-card > .ana-filter {
        margin: 0;
        padding: 19px 20px 16px;
        border: 0;
        border-radius: 0;
        box-shadow: none;
    }
    .admin-analytics-shell .ana-filter-card > .ana-identity {
        margin: 0;
        padding: 16px 20px 18px;
        border: 0;
        border-top: 1px solid var(--ana-slate-100);
        border-radius: 0;
        box-shadow: none;
    }
    .admin-analytics-filter-row {
        display: grid;
        grid-template-columns: minmax(280px, 1.4fr) repeat(2, minmax(190px, .8fr));
        gap: 16px;
    }
    .admin-analytics-filter-row > div { min-width: 0; }
    .admin-analytics-filter-row .ana-flabel { display: block; margin-bottom: 7px; }
    .admin-analytics-shell .ana-flabel { font-size: 10.5px; }
    .admin-analytics-shell .ana-input { min-height: 42px; }
    .admin-analytics-shell .ana-identity h2 {
        overflow: visible;
        overflow-wrap: anywhere;
        text-overflow: clip;
        white-space: normal;
    }
    .admin-analytics-shell .ana-type-badge,
    .admin-analytics-shell .ana-status-badge { padding: 3px 8px; font-size: 9px; }
    .admin-analytics-shell .ana-identity-meta { font-size: 12px; }

    .admin-analytics-shell .ana-metrics { gap: 16px; margin-bottom: 24px; }
    .admin-analytics-shell .ana-metric {
        min-height: 148px;
        flex-direction: column;
        align-items: flex-start;
        gap: 0;
        padding: 17px 18px;
        border-radius: 16px;
        box-shadow: var(--ana-shadow);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .admin-analytics-shell .ana-metric:hover {
        transform: translateY(-3px);
        border-color: #bfdbfe;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
    }
    .admin-analytics-shell .ana-metric-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        margin: 0 0 13px;
        border-radius: 12px;
        background: var(--ana-blue-soft);
        color: var(--ana-blue);
        font-size: 19px;
    }
    .admin-analytics-shell .ana-tone-green .ana-metric-icon {
        background: var(--ana-green-soft);
        color: var(--ana-green);
    }
    .admin-analytics-shell .ana-tone-amber .ana-metric-icon {
        background: var(--ana-amber-soft);
        color: var(--ana-amber);
    }
    .admin-analytics-shell .ana-metric-copy { min-width: 0; }
    .admin-analytics-shell .ana-metric-label { font-size: 11px; line-height: 1.35; }
    .admin-analytics-shell .ana-metric-value { margin-top: 5px; font-size: 28px; line-height: 1; }
    .admin-analytics-shell .ana-metric-note { margin-top: 7px; color: var(--ana-slate-500); font-size: 12px; line-height: 1.4; }

    .admin-analytics-report-stack { display: grid; gap: 26px; }
    .admin-analytics-report-card {
        border-radius: 18px;
        box-shadow: 0 6px 20px rgba(15, 23, 42, .05);
    }
    .admin-analytics-shell .ana-report-head { align-items: center; padding: 18px 20px; }
    .admin-analytics-shell .ana-report-head .ana-eyebrow { margin-bottom: 5px; }
    .admin-analytics-shell .ana-report-head h2 { font-size: 16px; }
    .admin-analytics-shell .ana-report-head p { margin-top: 4px; font-size: 12.5px; line-height: 1.45; }
    .admin-analytics-shell .ana-rate { padding: 6px 10px; font-size: 10.5px; }
    .admin-analytics-shell .ana-quiz-metric-detail { color: var(--admin-analytics-blue-hover); }

    .admin-analytics-chart-grid {
        display: grid;
        grid-template-columns: minmax(300px, .85fr) minmax(500px, 1.55fr);
        align-items: stretch;
        gap: 28px;
        padding: 26px 24px 28px;
    }
    .admin-analytics-donut-box,
    .admin-analytics-ranking-box,
    .admin-analytics-quiz-box { min-width: 0; }
    .admin-analytics-chart-grid > .ana-chart-box { min-height: 0; padding: 0; }
    .admin-analytics-ranking-box,
    .admin-analytics-quiz-box { display: flex; flex-direction: column; }
    .admin-analytics-ranking-box {
        padding-left: 28px !important;
        border-left: 1px solid var(--ana-slate-100);
    }
    .admin-analytics-quiz-box { padding: 26px 24px 28px; }
    .admin-chart-canvas-wrap {
        position: relative;
        width: 100%;
        height: var(--admin-chart-height);
        min-width: 0;
        overflow: hidden;
    }
    .admin-chart-canvas-wrap--donut {
        --admin-chart-height: 248px;
        max-width: 270px;
        margin: 0 auto;
    }
    .admin-chart-canvas-wrap--detail-donut { --admin-chart-height: 300px; }
    .admin-chart-canvas-wrap--detail-attendance { --admin-chart-height: 320px; min-height: 0; }
    .admin-chart-canvas-wrap--detail-quiz.is-specific { --admin-chart-height: 320px; min-height: 0; }
    .admin-chart-canvas-wrap--detail-quiz.is-ranking {
        --admin-chart-height: clamp(240px, calc(var(--quiz-count) * 50px + 60px), 420px);
        min-height: 0;
    }
    .admin-chart-canvas-wrap > canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
        max-width: 100%;
        max-height: 100%;
    }
    .admin-analytics-chart-title {
        margin: 0 0 16px;
        color: var(--ana-slate-700);
        font-size: 12px;
        font-weight: 850;
        letter-spacing: .045em;
        line-height: 1.35;
        text-transform: uppercase;
    }
    .admin-analytics-donut-box .admin-analytics-chart-title { text-align: center; }
    .admin-analytics-shell .ana-attendance-legend {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px 14px;
        margin: 17px auto 0;
        padding: 0;
        font-size: 11.5px;
    }
    .admin-analytics-shell .ana-attendance-legend span { min-width: 0; line-height: 1.4; }
    .admin-analytics-shell .ana-attendance-legend .recorded { color: var(--ana-green); }
    .admin-analytics-shell .ana-attendance-legend .not-recorded { color: var(--ana-slate-400); }
    .admin-analytics-shell .ana-chart-note { margin: -8px 24px 20px; line-height: 1.45; }

    .admin-analytics-shell .ana-table-divider { padding-top: 6px; }
    .admin-analytics-shell .ana-table-title {
        padding: 15px 20px 11px;
        color: var(--ana-slate-700);
        font-size: 11.5px;
    }
    .admin-analytics-shell .ana-table-wrap {
        max-width: 100%;
        overflow-x: auto;
        scrollbar-gutter: stable;
    }
    .admin-analytics-shell .admin-analytics-table { min-width: 940px; font-size: 13.5px; }
    .admin-analytics-shell .admin-analytics-table th {
        padding: 12px 16px;
        color: var(--ana-slate-600);
        font-size: 11px;
        line-height: 1.35;
    }
    .admin-analytics-shell .admin-analytics-table td {
        padding: 14px 16px;
        color: var(--ana-slate-600);
        font-variant-numeric: tabular-nums;
        line-height: 1.45;
    }
    .admin-analytics-shell .admin-analytics-table tbody tr { transition: background-color .15s ease; }
    .admin-analytics-shell .admin-analytics-table tbody tr:hover { background: #fbfdff; }
    .admin-analytics-shell .admin-analytics-table :is(th, td):first-child {
        min-width: 230px;
        max-width: 360px;
        overflow: visible;
        overflow-wrap: anywhere;
        text-overflow: clip;
        white-space: normal;
    }
    .admin-analytics-shell .admin-analytics-table td:first-child { color: var(--ana-slate-900); font-weight: 750; }
    .admin-analytics-shell .ana-pagination {
        min-height: 62px;
        padding: 14px 20px;
        border-top: 1px solid var(--ana-slate-100);
        background: #fbfdff;
        font-size: 12px;
    }
    .admin-analytics-shell .ana-pagination select,
    .admin-analytics-shell .ana-pagination button {
        min-height: 34px;
        padding: 6px 10px;
        border-radius: 9px;
        transition: border-color .15s ease, background-color .15s ease, color .15s ease;
    }
    .admin-analytics-shell .ana-pagination button:not(:disabled):hover {
        border-color: #bfdbfe;
        background: var(--ana-blue-soft);
        color: var(--ana-blue-dark);
    }
    .admin-analytics-shell .ana-pagination :is(select, button):focus-visible {
        outline: 3px solid rgba(37, 99, 235, .18);
        outline-offset: 2px;
    }

    @media (max-width: 1100px) {
        .admin-analytics-chart-grid { grid-template-columns: minmax(260px, .8fr) minmax(420px, 1.4fr); gap: 22px; }
    }

    @media (max-width: 991px) {
        .admin-analytics-filter-row { grid-template-columns: 1fr 1fr; }
        .admin-analytics-filter-row > div:first-child { grid-column: 1 / -1; }
        .admin-analytics-chart-grid { grid-template-columns: 1fr; }
        .admin-analytics-ranking-box { padding: 24px 0 0 !important; border-top: 1px solid var(--ana-slate-100); border-left: 0; }
    }

    @media (max-width: 767px) {
        .admin-analytics-shell .adm-head { margin-bottom: 16px; }
        .admin-analytics-filter-row { grid-template-columns: 1fr; }
        .admin-analytics-filter-row > div:first-child { grid-column: auto; }
        .admin-analytics-shell .ana-filter-card > .ana-panel-head,
        .admin-analytics-shell .ana-filter-card > .ana-filter,
        .admin-analytics-shell .ana-filter-card > .ana-identity { padding-right: 16px; padding-left: 16px; }
        .admin-analytics-shell .ana-report-head { align-items: stretch; }
        .admin-analytics-chart-grid,
        .admin-analytics-quiz-box { padding: 20px 16px; }
        .admin-analytics-shell .ana-table-title,
        .admin-analytics-shell .ana-pagination { padding-right: 16px; padding-left: 16px; }
    }

    @media (max-width: 520px) {
        .admin-analytics-shell .ana-metrics { grid-template-columns: 1fr; }
        .admin-analytics-shell .ana-metric { min-height: 0; }
        .admin-analytics-shell .ana-attendance-legend { grid-template-columns: 1fr; }
        .admin-analytics-shell .ana-pagination .ana-page-range { width: 100%; margin-left: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        .admin-analytics-shell .ana-metric,
        .admin-analytics-shell .admin-analytics-table tbody tr,
        .admin-analytics-shell .ana-pagination :is(select, button) { transition: none; }
    }
</style>
