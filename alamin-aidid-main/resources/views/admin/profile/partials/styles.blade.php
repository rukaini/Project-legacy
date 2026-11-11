<style>
    .pf-workspace {
        display: grid;
        gap: clamp(16px, 1.5vw, 22px);
    }

    .pf-card {
        overflow: hidden;
        border: 1px solid var(--adm-border);
        border-radius: var(--adm-radius-lg);
        background: var(--adm-surface);
        box-shadow: var(--adm-shadow);
    }

    .pf-identity {
        padding: 17px 20px;
    }

    .pf-identity-main { min-width: 0; }

    .pf-identity-name {
        margin: 0;
        color: var(--adm-slate-900);
        font-size: clamp(19px, 1.6vw, 23px);
        font-weight: 800;
        letter-spacing: -0.025em;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .pf-identity-email {
        margin: 3px 0 0;
        color: var(--adm-slate-500);
        font-size: 13px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .pf-section { padding: clamp(19px, 2vw, 25px); }

    .pf-section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 17px;
    }

    .pf-section-title {
        margin: 0;
        color: var(--adm-slate-900);
        font-size: 15.5px;
        font-weight: 800;
        letter-spacing: -0.015em;
        line-height: 1.35;
    }

    .pf-section-subtitle {
        margin: 3px 0 0;
        color: var(--adm-slate-500);
        font-size: 12px;
        font-weight: 500;
        line-height: 1.5;
    }

    .pf-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
    }

    .pf-stat {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        border: 1px solid var(--adm-border);
        border-radius: var(--adm-radius);
        background: var(--adm-surface-muted);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    @media (hover: hover) {
        .pf-stat:hover {
            transform: translateY(-3px);
            border-color: #bfdbfe;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
        }

        .pf-detail-item:hover {
            transform: translateY(-3px);
            border-color: #bfdbfe;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
        }
    }

    .pf-stat-icon {
        width: 38px;
        height: 38px;
        display: grid;
        flex: 0 0 auto;
        place-items: center;
        border-radius: 11px;
        background: var(--adm-primary-soft);
        color: var(--adm-primary);
        font-size: 17px;
    }

    .pf-stat.approved .pf-stat-icon { background: var(--adm-green-soft); color: var(--adm-green); }
    .pf-stat.rejected .pf-stat-icon { background: var(--adm-red-soft); color: var(--adm-red); }
    .pf-stat.rate .pf-stat-icon { background: var(--adm-indigo-soft); color: var(--adm-indigo); }

    .pf-stat-value {
        color: var(--adm-slate-900);
        font-size: clamp(18px, 1.6vw, 23px);
        font-weight: 800;
        line-height: 1.1;
        overflow-wrap: anywhere;
    }

    .pf-stat.rate .pf-stat-value { font-size: clamp(16px, 1.5vw, 22px); }

    .pf-stat-label {
        margin-top: 4px;
        color: var(--adm-slate-500);
        font-size: 10.5px;
        font-weight: 700;
        line-height: 1.35;
    }

    .pf-detail-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 11px;
        margin: 0;
    }

    .pf-detail-item {
        min-width: 0;
        margin: 0;
        padding: 13px 14px;
        border: 1px solid var(--adm-border);
        border-radius: 12px;
        background: var(--adm-surface-muted);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .pf-detail-item { grid-column: span 2; }
    .pf-detail-item--retirement,
    .pf-detail-item--credit { grid-column: span 3; }
    .pf-detail-item--retirement:last-child,
    .pf-detail-item--credit.full-row { grid-column: 1 / -1; }

    .pf-detail-label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0 0 5px;
        color: var(--adm-slate-400);
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: 0.055em;
        line-height: 1.35;
        text-transform: uppercase;
    }

    .pf-detail-label i { color: var(--adm-slate-500); font-size: 12px; }

    .pf-detail-value {
        margin: 0;
        color: var(--adm-slate-700);
        font-size: 13px;
        font-weight: 700;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .pf-detail-hint {
        display: block;
        margin-top: 2px;
        color: var(--adm-slate-500);
        font-size: 10.5px;
        font-weight: 600;
    }

    .pf-activity { padding-bottom: 6px; }
    .pf-activity-feed { margin-inline: calc(clamp(19px, 2vw, 25px) * -1); }

    .pf-activity-row {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr) minmax(108px, auto);
        align-items: center;
        gap: 14px;
        padding: 15px clamp(19px, 2vw, 25px);
        border-top: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }

    @media (hover: hover) {
        .pf-activity-row:hover { background: var(--adm-surface-muted); }
    }

    .pf-outcome-icon {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 800;
    }

    .pf-outcome-icon.approved { background: var(--adm-green-soft); color: var(--adm-green); }
    .pf-outcome-icon.rejected { background: var(--adm-red-soft); color: var(--adm-red); }
    .pf-activity-main { min-width: 0; }
    .pf-activity-heading { line-height: 1; }

    .pf-outcome-badge {
        display: inline-flex;
        padding: 3px 7px;
        border: 1px solid;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.045em;
        text-transform: uppercase;
    }

    .pf-outcome-badge.approved { border-color: #a7f3d0; background: var(--adm-green-soft); color: #047857; }
    .pf-outcome-badge.rejected { border-color: #fecaca; background: var(--adm-red-soft); color: #b91c1c; }

    .pf-activity-title {
        margin: 5px 0 0;
        color: var(--adm-slate-900);
        font-size: 13px;
        font-weight: 750;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }

    .pf-activity-meta { margin: 2px 0 0; color: var(--adm-slate-500); font-size: 10.5px; font-weight: 600; line-height: 1.4; }

    .pf-activity-time {
        padding-top: 2px;
        color: var(--adm-slate-500);
        font-size: 10.5px;
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
    }

    .pf-activity-time time { display: flex; flex-direction: column; gap: 2px; }
    .pf-activity-time small { color: var(--adm-slate-400); font-size: 9.5px; font-weight: 600; }

    .pf-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 38px 22px;
        border-top: 1px solid #f1f5f9;
        color: var(--adm-slate-500);
        text-align: center;
    }

    .pf-empty i { margin-bottom: 10px; color: var(--adm-slate-400); font-size: 30px; }
    .pf-empty-title { color: var(--adm-slate-700); font-size: 13.5px; font-weight: 800; }
    .pf-empty-sub { max-width: 320px; margin-top: 4px; font-size: 11.5px; font-weight: 500; line-height: 1.5; }

    @media (max-width: 991.98px) {
        .pf-detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pf-detail-item,
        .pf-detail-item--retirement,
        .pf-detail-item--credit { grid-column: auto; }
        .pf-detail-item--retirement:last-child,
        .pf-detail-item--credit.full-row { grid-column: 1 / -1; }
    }

    @media (max-width: 767.98px) {
        .pf-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 575.98px) {
        .pf-stats,
        .pf-detail-grid { grid-template-columns: 1fr; }

        .pf-detail-item,
        .pf-detail-item--retirement,
        .pf-detail-item--credit,
        .pf-detail-item--retirement:last-child,
        .pf-detail-item--credit.full-row { grid-column: auto; }

        .pf-activity-row {
            grid-template-columns: 34px minmax(0, 1fr);
        }

        .pf-activity-time {
            grid-column: 2;
            padding-top: 0;
            text-align: left;
        }

        .pf-activity-time time { display: block; }
        .pf-activity-time small::before { content: " · "; }
    }
</style>
