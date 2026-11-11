@include('trainer.simple.partials.workspace-styles')
<style>
    .sw-session-state { padding: 5px 10px; border: 1px solid #bfdbfe; border-radius: 999px; background: var(--sw-primary-soft); color: var(--sw-primary); font-size: 10px; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; }
    .sw-session-state.current { border-color: #a7f3d0; background: var(--sw-green-soft); color: var(--sw-green); }
    .sw-featured-session { display: grid; grid-template-columns: 76px 1fr; gap: 18px; align-items: center; padding: 20px; background: linear-gradient(135deg, #f8fafc, #eff6ff); }
    .sw-session-date-tile { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 76px; border: 1px solid #bfdbfe; border-radius: 14px; background: #fff; color: var(--sw-primary); text-align: center; }
    .sw-session-date-tile span, .sw-session-date-tile small { font-size: 10px; font-weight: 850; text-transform: uppercase; }
    .sw-session-date-tile strong { font-size: 25px; line-height: 1.05; }
    .sw-featured-copy h3 { margin: 0 0 9px; font-size: 18px; font-weight: 850; }
    .sw-session-meta { display: flex; flex-wrap: wrap; gap: 8px 15px; color: var(--sw-slate-600); font-size: 12px; font-weight: 650; }
    .sw-session-meta span, .sw-session-meta a { display: inline-flex; align-items: center; gap: 6px; color: inherit; }
    .sw-session-meta i { color: var(--sw-primary); }
    .sw-schedule-section { padding: 17px 20px 5px; border-top: 1px solid var(--sw-slate-100); }
    .sw-schedule-section h3 { margin: 0 0 10px; color: var(--sw-slate-500); font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    .sw-session-list { border: 1px solid var(--sw-slate-100); border-radius: 12px; overflow: hidden; }
    .sw-session-row { display: grid; grid-template-columns: 140px 1fr; gap: 18px; align-items: center; padding: 12px 14px; border-bottom: 1px solid var(--sw-slate-100); }
    .sw-session-row:last-child { border-bottom: 0; }
    .sw-session-row-date, .sw-session-row-main { display: flex; flex-direction: column; gap: 3px; }
    .sw-session-row strong { font-size: 12.5px; font-weight: 800; }
    .sw-session-row span { color: var(--sw-slate-500); font-size: 11.5px; font-weight: 650; }
    .sw-session-row-main span { display: flex; align-items: center; gap: 5px; }
    .sw-past-sessions { border-top: 1px solid var(--sw-slate-100); }
    .sw-past-toggle { display: flex; width: 100%; align-items: center; justify-content: space-between; padding: 14px 20px; border: 0; background: #fff; color: var(--sw-slate-600); cursor: pointer; font-size: 12px; font-weight: 850; }
    .sw-past-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
    .sw-past-list { margin: 0 20px 16px; overflow: hidden; border: 1px solid var(--sw-slate-100); border-radius: 12px; background: var(--sw-slate-50); }
    .sw-past-row { opacity: .78; }
    .sw-show-more-past { width: 100%; padding: 11px; border: 0; border-top: 1px solid var(--sw-slate-100); background: #fff; color: var(--sw-primary); font-size: 12px; font-weight: 800; }
    .sw-session-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 20px; border-top: 1px solid var(--sw-slate-100); color: var(--sw-slate-500); font-size: 12px; font-weight: 700; }
    .sw-session-footer a { color: var(--sw-primary); font-weight: 800; text-decoration: none; }
    .sw-participant-preview { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sw-preview-person { display: flex; align-items: center; gap: 11px; padding: 13px 20px; border-right: 1px solid var(--sw-slate-100); border-bottom: 1px solid var(--sw-slate-100); }
    .sw-preview-person:nth-child(2n) { border-right: 0; }
    .sw-preview-name { font-size: 12.5px; font-weight: 800; }
    .sw-preview-meta { color: var(--sw-slate-500); font-size: 11px; font-weight: 650; }
    .sw-participant-summary { display: flex; flex-wrap: wrap; gap: 10px 18px; padding: 12px 20px; color: var(--sw-slate-500); font-size: 11.5px; font-weight: 700; }
    .sw-participant-summary strong { color: var(--sw-slate-900); }
    .sw-overview-link { align-self: center; color: var(--sw-primary); font-size: 12px; font-weight: 850; text-decoration: none; }
    .sw-readiness, .sw-materials-card { margin-bottom: 22px; overflow: hidden; border: 1px solid var(--sw-border); border-radius: 18px; background: #fff; box-shadow: var(--sw-shadow); }
    .sw-readiness-head { padding: 17px 20px; border-bottom: 1px solid var(--sw-slate-100); }
    .sw-readiness-title { margin-bottom: 12px; font-size: 15px; font-weight: 850; }
    .sw-readiness-summary, .sw-check-item { display: flex; align-items: center; gap: 11px; }
    .sw-readiness-badge, .sw-check-icon { display: flex; flex: 0 0 auto; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; }
    .sw-readiness-badge.ready, .sw-check-icon.ok { background: var(--sw-green-soft); color: var(--sw-green); }
    .sw-readiness-badge.partial, .sw-check-icon.no { background: var(--sw-amber-soft); color: var(--sw-amber); }
    .sw-readiness-status, .sw-check-label { font-size: 12.5px; font-weight: 850; }
    .sw-readiness-note, .sw-check-sub { color: var(--sw-slate-500); font-size: 11px; font-weight: 650; }
    .sw-check-item { padding: 13px 20px; border-bottom: 1px solid var(--sw-slate-100); }
    .sw-about-body { padding: 18px 20px; }
    .sw-about-text { margin: 0 0 15px; color: var(--sw-slate-600); font-size: 12.5px; line-height: 1.7; }
    .sw-about-meta { display: flex; flex-wrap: wrap; gap: 7px; }
    .sw-materials-card { padding: 20px; }
    .sw-materials-icon { color: var(--sw-primary); font-size: 22px; }
    .sw-materials-title { margin-top: 8px; color: var(--sw-slate-500); font-size: 11px; font-weight: 900; text-transform: uppercase; }
    .sw-materials-value { margin: 3px 0; font-size: 25px; font-weight: 900; }
    .sw-materials-text { color: var(--sw-slate-500); font-size: 11.5px; }
    .sw-materials-btn { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--sw-slate-100); color: var(--sw-primary); font-size: 12px; font-weight: 850; text-decoration: none; }
    .sw-team { display: grid; gap: 10px; padding: 16px 20px; max-height: 240px; overflow-y: auto; }
    .sw-team-member { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .sw-team-avatar { display: inline-flex; flex: 0 0 auto; align-items: center; justify-content: center; width: 34px; height: 34px; border: 1px solid #bfdbfe; border-radius: 11px; background: var(--sw-primary-soft); color: var(--sw-primary); font-size: 12px; font-weight: 900; }
    .sw-team-name { min-width: 0; color: var(--sw-slate-700); font-size: 12.5px; font-weight: 800; line-height: 1.35; }
    .sw-team-you { display: inline-flex; margin-left: 5px; padding: 2px 7px; border-radius: 999px; background: var(--sw-green-soft); color: var(--sw-green); font-size: 9.5px; font-weight: 900; letter-spacing: .03em; text-transform: uppercase; vertical-align: 1px; }
    @media (max-width: 767.98px) { .sw-featured-session { grid-template-columns: 62px 1fr; padding: 16px; } .sw-session-row { grid-template-columns: 1fr; gap: 5px; } .sw-participant-preview { grid-template-columns: 1fr; } .sw-preview-person { border-right: 0; } }
</style>
