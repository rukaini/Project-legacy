@include('trainer.simple.partials.workspace-styles')
<style>
    .sw-participant-filters { background: var(--sw-slate-50); }
    .sw-summary-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sw-az-filter { display: flex; align-items: flex-start; gap: 14px; padding: 12px 20px; border-bottom: 1px solid var(--sw-slate-100); }
    .sw-az-filter > span { flex: 0 0 auto; padding-top: 7px; color: var(--sw-slate-500); font-size: 10px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; }
    .sw-az-buttons { display: flex; flex-wrap: wrap; gap: 5px; }
    .sw-az-buttons button { display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; padding: 0 7px; border: 1px solid var(--sw-border); border-radius: 8px; background: #fff; color: var(--sw-slate-600); cursor: pointer; font-family: inherit; font-size: 11px; font-weight: 850; }
    .sw-az-buttons button:hover:not(:disabled), .sw-az-buttons button.active { border-color: var(--sw-primary); background: var(--sw-primary); color: #fff; }
    .sw-az-buttons button:disabled { background: var(--sw-slate-100); color: var(--sw-slate-300); cursor: not-allowed; }
    .sw-report-error { margin: 12px 20px; padding: 10px 12px; border-radius: 9px; background: var(--sw-red-soft); color: var(--sw-red); font-size: 12px; font-weight: 750; }
    @media (max-width: 767.98px) { .sw-az-filter { flex-direction: column; gap: 7px; } .sw-az-filter > span { padding-top: 0; } .sw-summary-cards { grid-template-columns: 1fr; } .sw-table { min-width: 720px; } }
</style>
