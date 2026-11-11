<style data-lms-workspace-header-styles>
    :root {
        --lw-primary: #2563eb;
        --lw-primary-dark: #1d4ed8;
        --lw-primary-soft: #eff6ff;
        --lw-indigo: #4338ca;
        --lw-indigo-soft: #eef2ff;
        --lw-amber: #d97706;
        --lw-amber-soft: #fffbeb;
        --lw-green: #059669;
        --lw-green-soft: #ecfdf5;
        --lw-red: #dc2626;
        --lw-red-soft: #fef2f2;
        --lw-slate-900: #0f172a;
        --lw-slate-700: #334155;
        --lw-slate-600: #475569;
        --lw-slate-500: #64748b;
        --lw-slate-400: #94a3b8;
        --lw-slate-300: #cbd5e1;
        --lw-slate-200: #e2e8f0;
        --lw-slate-100: #f1f5f9;
        --lw-slate-50: #f8fafc;
        --lw-border: #e2e8f0;
        --lw-shadow: 0 6px 20px rgba(15, 23, 42, .05);
    }

    .lw-header { background: #fff; border-bottom: 1px solid var(--lw-border); }
    .lw-header *, .lw-header *::before, .lw-header *::after { box-sizing: border-box; }
    .lw-breadcrumb { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; color: var(--lw-slate-400); font-size: 11px; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
    .lw-breadcrumb a { color: var(--lw-slate-500); text-decoration: none; transition: color .15s ease; }
    .lw-breadcrumb a:hover { color: var(--lw-primary); }
    .lw-breadcrumb .lw-bc-current { color: var(--lw-slate-700); }
    .lw-breadcrumb :is(a, .lw-bc-current) { min-width: 0; overflow-wrap: anywhere; }
    .lw-title-row, .lw-title-left { display: flex; align-items: flex-start; gap: 16px; }
    .lw-title-row { justify-content: space-between; }
    .lw-title-left { min-width: 0; flex: 1; }
    .lw-icon { display: flex; align-items: center; justify-content: center; width: 58px; height: 58px; flex-shrink: 0; border: 1px solid #bfdbfe; border-radius: 16px; background-color: var(--lw-primary-soft); background-image: radial-gradient(#93c5fd 1px, transparent 1px); background-size: 10px 10px; color: var(--lw-primary); font-size: 23px; }
    .lw-title { margin: 0 0 9px; overflow-wrap: anywhere; color: var(--lw-slate-900); font-size: 26px; font-weight: 850; line-height: 1.18; letter-spacing: -.035em; }
    .lw-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .lw-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border: 1px solid var(--lw-border); border-radius: 999px; background: var(--lw-slate-100); color: var(--lw-slate-600); font-size: 12px; font-weight: 750; }
    .lw-pill i { color: var(--lw-slate-500); }
    .lw-pill.mode { border-color: #c7d2fe; background: var(--lw-indigo-soft); color: var(--lw-indigo); font-size: 11px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .lw-pill.mode i { color: var(--lw-indigo); }
    .lw-tabs { display: flex; max-width: 100%; gap: 24px; margin-top: 22px; overflow-x: auto; overscroll-behavior-inline: contain; scrollbar-width: thin; }
    .lw-tabs a { position: relative; display: inline-flex; align-items: center; gap: 6px; padding-bottom: 13px; border-bottom: 2.5px solid transparent; color: var(--lw-slate-500); font-size: 14px; font-weight: 650; text-decoration: none; white-space: nowrap; transition: color .15s ease, border-color .15s ease; }
    .lw-tabs a:hover { color: var(--lw-slate-900); }
    .lw-tabs a.active { border-bottom-color: var(--lw-primary); color: var(--lw-primary); font-weight: 800; }
    .lw-tab-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 19px; padding: 0 7px; border: 1px solid #fde68a; border-radius: 999px; background: var(--lw-amber-soft); color: var(--lw-amber); font-size: 10.5px; font-weight: 900; }
    .lw-hidden, .lw-header [hidden] { display: none !important; }
    .lw-min-0 { min-width: 0; }
    .lw-flex-1 { flex: 1; }
    .lw-breadcrumb a:focus-visible, .lw-tabs a:focus-visible { outline: 3px solid rgba(37, 99, 235, .22); outline-offset: 2px; }

    @media (max-width: 700px) {
        .lw-title-row { flex-wrap: wrap; }
        .lw-title { font-size: 22px; }
    }
</style>
