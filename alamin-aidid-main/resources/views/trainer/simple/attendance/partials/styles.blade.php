@include('trainer.simple.partials.workspace-styles')
<style>
    .sw-read-only { display: inline-flex; align-items: center; gap: 5px; padding: 5px 9px; border: 1px solid var(--sw-border); border-radius: 999px; color: var(--sw-slate-500); font-size: 10px; font-weight: 850; text-transform: uppercase; }
    .sw-session-summary { padding: 18px 20px; border-bottom: 1px solid var(--sw-slate-100); }
    .sw-session-kicker { color: var(--sw-primary); font-size: 10px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; }
    .sw-session-heading { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 4px 0 8px; }
    .sw-session-summary h3 { margin: 0; font-size: 17px; font-weight: 850; }
    .sw-session-details { display: flex; flex-wrap: wrap; gap: 8px 16px; color: var(--sw-slate-500); font-size: 12px; font-weight: 650; }
    .sw-session-details span, .sw-session-details a { display: inline-flex; align-items: center; gap: 5px; color: inherit; }
    .sw-report-error { margin: 12px 20px; padding: 10px 12px; border-radius: 9px; background: var(--sw-red-soft); color: var(--sw-red); font-size: 12px; font-weight: 750; }
    @media (max-width: 767.98px) { .sw-table { min-width: 690px; } }
</style>
@include('trainer.attendance.partials.qr-styles')
