@include('trainer.simple.partials.workspace-styles')
<style>
    .sw-upload-body { padding: 20px; }
    .sw-upload-row { display: flex; align-items: stretch; gap: 10px; }
    .sw-native-file { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); }
    .sw-file-select { display: flex; flex: 1; align-items: center; gap: 9px; min-width: 0; min-height: 44px; padding: 9px 13px; border: 1px dashed var(--sw-slate-300); border-radius: 10px; background: var(--sw-slate-50); color: var(--sw-slate-600); cursor: pointer; font-size: 13px; font-weight: 700; }
    .sw-file-select span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sw-native-file:focus-visible + .sw-file-select { outline: 3px solid rgba(37, 99, 235, .25); outline-offset: 2px; }
    .sw-primary-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 44px; padding: 9px 17px; border: 1px solid var(--sw-primary); border-radius: 10px; background: var(--sw-primary); color: #fff; cursor: pointer; font-family: inherit; font-size: 12.5px; font-weight: 850; }
    .sw-hint { margin: 9px 0 0; color: var(--sw-slate-500); font-size: 11.5px; font-weight: 600; }
    .sw-error, .sw-report-error { margin-top: 10px; color: var(--sw-red); font-size: 12px; font-weight: 750; }
    .sw-report-error { margin: 12px 20px; padding: 10px 12px; border-radius: 9px; background: var(--sw-red-soft); }
    .sw-material-row { display: grid; grid-template-columns: 42px minmax(0, 1fr) auto; align-items: center; gap: 13px; padding: 14px 20px; border-bottom: 1px solid var(--sw-slate-100); }
    .sw-material-row:hover { background: var(--sw-slate-50); }
    .sw-material-icon { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #bfdbfe; border-radius: 10px; background: var(--sw-primary-soft); color: var(--sw-primary); font-size: 17px; }
    .sw-material-main { min-width: 0; }
    .sw-material-main h3 { margin: 0 0 5px; overflow: hidden; color: var(--sw-slate-900); font-size: 13px; font-weight: 850; text-overflow: ellipsis; white-space: nowrap; }
    .sw-material-main > div { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; color: var(--sw-slate-500); font-size: 11px; font-weight: 650; }
    .sw-material-actions { display: flex; align-items: center; gap: 7px; }
    .sw-material-actions form { margin: 0; }
    .sw-row-action { display: inline-flex; align-items: center; gap: 5px; padding: 7px 9px; border: 1px solid var(--sw-border); border-radius: 8px; background: #fff; color: var(--sw-primary); font-family: inherit; font-size: 11.5px; font-weight: 800; text-decoration: none; }
    .sw-row-action.danger { color: var(--sw-red); cursor: pointer; }
    @media (max-width: 575.98px) { .sw-upload-row { flex-direction: column; } .sw-material-row { grid-template-columns: 40px minmax(0, 1fr); } .sw-material-actions { grid-column: 1 / -1; padding-left: 53px; } }
</style>
