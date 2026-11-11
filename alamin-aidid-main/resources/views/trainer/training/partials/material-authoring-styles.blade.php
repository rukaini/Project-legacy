@push('styles')
<style>
    [hidden] { display: none !important; }
    /* ================================ LW TOKENS ================================ */
    :root {
        --lw-primary: #2563eb;
        --lw-primary-dark: #1d4ed8;
        --lw-primary-soft: #eff6ff;
        --lw-indigo: #4338ca;
        --lw-indigo-soft: #eef2ff;
        --lw-green: #059669;
        --lw-green-soft: #ecfdf5;
        --lw-amber: #d97706;
        --lw-amber-soft: #fffbeb;
        --lw-rose: #e11d48;
        --lw-rose-soft: #fff1f2;
        --lw-red: #dc2626;
        --lw-red-soft: #fef2f2;
        --lw-slate-900: #0f172a;
        --lw-slate-700: #334155;
        --lw-slate-600: #475569;
        --lw-slate-500: #64748b;
        --lw-slate-400: #94a3b8;
        --lw-slate-300: #cbd5e1;
        --lw-border: #e2e8f0;
        --lw-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }

    body { background: #f6f8fb; }

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* ================================ HEADER + TABS ================================ */
    .lw-header { background: #fff; border-bottom: 1px solid var(--lw-border); }
    .lw-page { padding-top: 24px; padding-bottom: 46px; }

    .lw-breadcrumb { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11px; font-weight: 850; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 15px; }
    .lw-breadcrumb a { color: var(--lw-slate-500); text-decoration: none; }
    .lw-breadcrumb a:hover { color: var(--lw-primary); }
    .lw-breadcrumb .lw-bc-current { color: var(--lw-slate-700); }

    .lw-title-row { display: flex; align-items: flex-start; gap: 16px; }
    .lw-icon { width: 58px; height: 58px; border-radius: 16px; flex-shrink: 0; background-image: radial-gradient(#93c5fd 1px, transparent 1px); background-size: 10px 10px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: var(--lw-primary); display: flex; align-items: center; justify-content: center; font-size: 23px; }
    .lw-title { font-size: 26px; font-weight: 850; letter-spacing: -0.035em; color: var(--lw-slate-900); margin: 0 0 9px; line-height: 1.18; }
    .lw-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .lw-pill { display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; color: var(--lw-slate-600); border: 1px solid var(--lw-border); border-radius: 999px; padding: 5px 11px; font-size: 12px; font-weight: 750; }
    .lw-pill i { color: var(--lw-slate-500); }
    .lw-pill.mode { background: var(--lw-indigo-soft); color: var(--lw-indigo); border-color: #c7d2fe; font-weight: 850; letter-spacing: 0.04em; text-transform: uppercase; font-size: 11px; }
    .lw-pill.mode i { color: var(--lw-indigo); }

    .lw-tabs { display: flex; gap: 24px; margin-top: 22px; overflow-x: auto; }
    .lw-tabs a { position: relative; color: var(--lw-slate-500); text-decoration: none; font-size: 14px; font-weight: 650; padding-bottom: 13px; border-bottom: 2.5px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; transition: 0.15s ease; }
    .lw-tabs a:hover { color: var(--lw-slate-900); }
    .lw-tabs a.active { color: var(--lw-primary); border-bottom-color: var(--lw-primary); font-weight: 800; }
    .lw-tab-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 19px; padding: 0 7px;
        background: var(--lw-amber-soft); color: var(--lw-amber);
        border: 1px solid #fde68a;
        border-radius: 999px; font-size: 10.5px; font-weight: 900;
    }

    /* ================================ SECTION HEAD + FLASH ================================ */
    .lw-section-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .lw-section-title { font-size: 19px; font-weight: 850; color: var(--lw-slate-900); margin: 0; }
    .lw-section-sub { font-size: 12.5px; font-weight: 600; color: var(--lw-slate-500); margin: 4px 0 0; }

    /* ================================ BUTTONS ================================ */
    .lw-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: 10px; font-size: 13px; font-weight: 850; cursor: pointer; transition: 0.15s ease; border: 1px solid; background: #fff; text-decoration: none; line-height: 1.2; font-family: inherit; }
    .lw-btn.primary { background: var(--lw-primary); color: #fff; border-color: var(--lw-primary); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.18); }
    .lw-btn.primary:hover { background: var(--lw-primary-dark); border-color: var(--lw-primary-dark); color: #fff; transform: translateY(-1px); }
    .lw-btn.ghost { background: #fff; color: var(--lw-slate-600); border-color: var(--lw-border); }
    .lw-btn.ghost:hover { background: #f1f5f9; color: var(--lw-slate-900); }
    .lw-btn.danger { background: #fff; color: var(--lw-rose); border-color: #fecaca; }
    .lw-btn.danger:hover { background: var(--lw-rose-soft); border-color: #fca5a5; }
    .lw-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }
    .lw-btn.sm { padding: 6px 12px; font-size: 12px; }

    /* ================================ MODULE GRID ================================ */
    .lw-module-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 18px; }

    .lw-module-card { position: relative; border-radius: 16px; overflow: hidden; cursor: pointer; background: #fff; border: 1px solid var(--lw-border); box-shadow: var(--lw-shadow); display: flex; flex-direction: column; transition: 0.18s ease; outline: none; }
    .lw-module-card:hover { transform: translateY(-3px); border-color: #bfdbfe; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12); }
    .lw-module-card:focus-visible { border-color: var(--lw-primary); box-shadow: 0 0 0 4px rgba(37,99,235,.16), 0 14px 30px rgba(15,23,42,.12); }

    .lw-module-image { position: relative; aspect-ratio: 16 / 10; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f1f5f9; }
    .lw-module-image-photo { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: 0.4s ease; }
    .lw-module-card:hover .lw-module-image-photo { transform: scale(1.04); }

    .lw-module-image-deco { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .lw-module-image-deco i { font-size: 54px; color: rgba(255, 255, 255, 0.97); filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.22)); position: relative; z-index: 2; transition: 0.3s ease; }
    .lw-module-card:hover .lw-module-image-deco i { transform: scale(1.08) rotate(-4deg); }
    .lw-module-drag { position: absolute; top: 10px; left: 10px; z-index: 4; width: 32px; height: 32px; border: 1px solid rgba(255,255,255,.72); border-radius: 9px; background: rgba(255,255,255,.92); color: var(--lw-slate-600); box-shadow: 0 4px 12px rgba(15,23,42,.13); display: inline-flex; align-items: center; justify-content: center; cursor: grab; }
    .lw-module-drag:active { cursor: grabbing; }
    .lw-module-image-deco::before { content: ""; position: absolute; top: -28px; right: -28px; width: 96px; height: 96px; border-radius: 50%; background: rgba(255, 255, 255, 0.14); z-index: 1; }
    .lw-module-image-deco::after { content: ""; position: absolute; bottom: -36px; left: -36px; width: 110px; height: 110px; border-radius: 50%; background: rgba(255, 255, 255, 0.10); z-index: 1; }

    .lw-module-edit-btn { position: absolute; top: 10px; right: 10px; z-index: 5; width: 32px; height: 32px; border-radius: 9px; background: rgba(255, 255, 255, 0.32); border: 1px solid rgba(255, 255, 255, 0.45); backdrop-filter: blur(6px); color: #fff; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.15s ease; }
    .lw-module-edit-btn:hover { background: rgba(255, 255, 255, 0.55); border-color: rgba(255, 255, 255, 0.65); transform: scale(1.05); }

    .lw-module-body { padding: 14px 16px 15px; background: #fff; }
    .lw-module-title { font-size: 15px; font-weight: 800; line-height: 1.3; margin: 0; letter-spacing: -0.01em; color: var(--lw-slate-900); display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .lw-module-meta { font-size: 11.5px; font-weight: 750; margin-top: 7px; color: var(--lw-slate-500); display: inline-flex; align-items: center; gap: 5px; }
    .lw-module-meta i { color: var(--lw-primary); font-size: 12px; }

    .lw-module-add { aspect-ratio: auto; min-height: 100%; border-radius: 16px; border: 2px dashed #cbd5e1; background: #f8fafc; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: var(--lw-slate-500); cursor: pointer; transition: 0.18s ease; padding: 16px; }
    .lw-module-add:hover { border-color: var(--lw-primary); background: var(--lw-primary-soft); color: var(--lw-primary); transform: translateY(-3px); }
    .lw-module-add-icon { width: 48px; height: 48px; border-radius: 50%; border: 2px solid currentColor; display: flex; align-items: center; justify-content: center; font-size: 22px; transition: 0.18s ease; }
    .lw-module-add:hover .lw-module-add-icon { transform: scale(1.08); }
    .lw-module-add-text { font-size: 13px; font-weight: 850; }

    /* ================================ LW MODAL ================================ */
    .lw-modal { position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 5vh 20px; overflow-y: auto; animation: lwFadeIn 0.18s ease-out; }
    .lw-modal.hidden { display: none !important; }
    .lw-modal-box { background: #fff; border: 1px solid var(--lw-border); border-radius: 18px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22); max-width: 540px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; animation: lwSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1); }
    .lw-modal-head { padding: 18px 22px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-shrink: 0; }
    .lw-modal-head-text { flex: 1; min-width: 0; }
    .lw-modal-title { font-size: 17px; font-weight: 850; margin: 0; color: var(--lw-slate-900); }
    .lw-modal-sub { font-size: 11px; font-weight: 800; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 3px; }
    .lw-modal-close { background: transparent; border: 0; color: var(--lw-slate-400); font-size: 22px; cursor: pointer; padding: 4px; line-height: 1; transition: 0.15s ease; }
    .lw-modal-close:hover { color: var(--lw-slate-900); }
    .lw-modal-body { padding: 20px 22px; overflow-y: auto; flex: 1 1 auto; min-height: 0; }
    .lw-modal-foot { padding: 16px 22px; border-top: 1px solid #f1f5f9; background: #fafbfc; display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0; }
    .lw-modal-foot.split { justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }

    /* Reset-to-default cover button (in edit modal) */
    .lw-cover-reset-row { display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: #fafbfc; border: 1px solid var(--lw-border); border-radius: 10px; }
    .lw-cover-reset-row i.bi-info-circle { color: var(--lw-slate-400); font-size: 15px; }
    .lw-cover-reset-row .lw-cover-reset-text { flex: 1; font-size: 12.5px; font-weight: 700; color: var(--lw-slate-600); }
    .lw-cover-reset-btn { background: #fff; border: 1px solid #fecaca; color: var(--lw-rose); padding: 6px 11px; border-radius: 8px; font-size: 11.5px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: 0.15s ease; }
    .lw-cover-reset-btn:hover { background: var(--lw-rose-soft); border-color: #fca5a5; }
    .lw-cover-reset-row.pending { background: #fef3c7; border-color: #fbbf24; }
    .lw-cover-reset-row.pending .lw-cover-reset-text { color: #92400e; }
    .lw-cover-reset-row.pending i.bi-info-circle { color: #d97706; }
    .lw-cover-reset-row.pending .lw-cover-reset-btn { background: #fff; border-color: #fbbf24; color: #d97706; }
    .lw-cover-reset-row.pending .lw-cover-reset-btn:hover { background: #fffbeb; }

    .lw-field { margin-bottom: 14px; }
    .lw-field:last-child { margin-bottom: 0; }
    .lw-field label { display: block; font-size: 11px; font-weight: 800; color: var(--lw-slate-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
    .lw-field input[type="text"], .lw-field input[type="file"], .lw-field input[type="number"], .lw-field input[type="datetime-local"], .lw-field select, .lw-field textarea { width: 100%; border: 1px solid var(--lw-border); border-radius: 9px; padding: 9px 12px; font-size: 13.5px; font-weight: 600; color: var(--lw-slate-900); background: #fff; outline: none; transition: 0.15s ease; font-family: inherit; }
    .lw-field input:focus, .lw-field select:focus, .lw-field textarea:focus { border-color: #bfdbfe; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }
    .lw-field-hint { font-size: 11.5px; font-weight: 650; color: var(--lw-slate-400); margin-top: 5px; }

    /* Edit-modal cover preview (no longer cropped) */
    .lw-cover-preview { width: 100%; max-height: 240px; border-radius: 12px; object-fit: contain; background: #f1f5f9; border: 1px solid var(--lw-border); padding: 6px; display: block; }
    .lw-cover-preview-deco { width: 100%; height: 200px; border-radius: 12px; border: 1px solid var(--lw-border); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
    .lw-cover-preview-deco i { font-size: 64px; color: rgba(255, 255, 255, 0.97); filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.22)); position: relative; z-index: 2; }
    .lw-cover-preview-deco::before { content: ""; position: absolute; top: -34px; right: -34px; width: 120px; height: 120px; border-radius: 50%; background: rgba(255, 255, 255, 0.14); z-index: 1; }
    .lw-cover-preview-deco::after { content: ""; position: absolute; bottom: -40px; left: -40px; width: 130px; height: 130px; border-radius: 50%; background: rgba(255, 255, 255, 0.10); z-index: 1; }

    /* File picker — friendlier */
    .lw-field input[type="file"] { padding: 8px 10px; cursor: pointer; }
    .lw-field input[type="file"]::file-selector-button { margin-right: 12px; padding: 6px 12px; border-radius: 7px; border: 1px solid var(--lw-border); background: var(--lw-primary-soft); color: var(--lw-primary); font-weight: 750; font-size: 12px; cursor: pointer; transition: 0.15s ease; }
    .lw-field input[type="file"]::file-selector-button:hover { background: var(--lw-primary); color: #fff; border-color: var(--lw-primary); }

    /* Preview of just-picked new cover image */
    .lw-cover-newpreview-wrap { margin-top: 10px; display: none; }
    .lw-cover-newpreview-wrap.show { display: block; }
    .lw-cover-newpreview-label { font-size: 10.5px; font-weight: 800; color: var(--lw-slate-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
    .lw-cover-newpreview-img { width: 100%; max-height: 200px; border-radius: 10px; object-fit: contain; background: #f1f5f9; border: 1px solid var(--lw-border); padding: 4px; }

    /* ================================ MODULE EDITOR (big modal) ================================ */
    .lw-editor { position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 5vh 20px; overflow-y: auto; animation: lwFadeIn 0.18s ease-out; }
    .lw-editor:focus { outline: none; }
    .lw-editor.hidden { display: none !important; }
    .lw-editor-box { background: #f8fafc; border: 1px solid var(--lw-border); border-radius: 18px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22); width: 100%; max-width: 920px; max-height: 88vh; display: flex; flex-direction: column; overflow: hidden; animation: lwSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1); }
    .lw-editor-head { background: #fff; padding: 16px 22px; border-bottom: 1px solid var(--lw-border); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-shrink: 0; }
    .lw-editor-head-l { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
    .lw-editor-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--lw-indigo-soft); color: var(--lw-indigo); display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
    .lw-editor-title-wrap { flex: 1; min-width: 0; }
    .lw-editor-title-input { width: 100%; border: 0; border-radius: 6px; background: transparent; font: inherit; font-size: 20px; line-height: 1.25; font-weight: 850; color: var(--lw-slate-900); padding: 3px 5px; margin-left: -5px; outline: none; border-bottom: 2px solid transparent; transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease; }
    .lw-editor-title-input:hover { background: #f8fafc; }
    .lw-editor-title-input:focus { background: var(--lw-primary-soft); border-bottom-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
    .lw-editor-title-error { min-height: 16px; margin-top: 2px; color: var(--lw-red); font-size: 11px; font-weight: 700; }
    .lw-editor-sub { font-size: 10.5px; font-weight: 800; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; }
    .lw-editor-actions { display: flex; gap: 8px; flex-shrink: 0; }
    .lw-editor-body { flex: 1; overflow-y: auto; padding: 24px 28px; }
    .lw-editor-foot { background: #fff; border-top: 1px solid var(--lw-border); padding: 14px 16px; flex-shrink: 0; }

    /* ================================ BLOCKS ================================ */
    .lw-block-list { display: flex; flex-direction: column; gap: 12px; max-width: 720px; margin: 0 auto; }
    .lw-block { position: relative; background: #fff; border: 1px solid var(--lw-border); border-radius: 12px; padding: 16px 48px 16px 44px; box-shadow: var(--lw-shadow); transition: 0.15s ease; }
    .lw-block:hover { border-color: var(--lw-slate-300); box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07); }
    .lw-block.sortable-ghost { opacity: 0.4; border-style: dashed; }
    .lw-block.sortable-chosen { box-shadow: 0 14px 30px rgba(15, 23, 42, 0.15); }
    .lw-block--title { padding-top: 18px; }
    .lw-block--quiz { border-left: 4px solid var(--lw-primary); background: #fafcff; }
    .lw-block--submission { border-left: 4px solid var(--lw-rose); }

    .lw-block-handle { position: absolute; left: 12px; top: 16px; width: 22px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--lw-slate-400); cursor: grab; transition: 0.15s ease; font-size: 16px; }
    .lw-block-handle:hover { background: #f1f5f9; color: var(--lw-slate-700); }
    .lw-block-handle:active { cursor: grabbing; }

    .lw-block-delete { position: absolute; top: 12px; right: 12px; width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--lw-border); background: #fff; color: var(--lw-slate-400); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.15s ease; font-size: 12px; z-index: 5; }
    .lw-block-delete:hover { background: var(--lw-rose-soft); color: var(--lw-rose); border-color: #fca5a5; }

    .lw-block-type-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 850; color: var(--lw-slate-500); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px; }
    .lw-block-type-badge.title { color: var(--lw-indigo); }
    .lw-block-type-badge.text { color: var(--lw-slate-600); }
    .lw-block-type-badge.material { color: var(--lw-rose); }
    .lw-block-type-badge.media { color: var(--lw-green); }
    .lw-block-type-badge.youtube { color: var(--lw-red); }
    .lw-block-type-badge.quiz { color: var(--lw-primary); }
    .lw-block-type-badge.submission { color: var(--lw-rose); }

    .lw-block input[type="text"], .lw-block input[type="datetime-local"], .lw-block textarea, .lw-block select { width: 100%; border: 1px solid var(--lw-border); border-radius: 8px; padding: 8px 11px; font-size: 13.5px; font-weight: 600; color: var(--lw-slate-900); background: #fff; outline: none; transition: 0.15s ease; font-family: inherit; }
    .lw-block input[type="text"] + input[type="text"], .lw-block input[type="text"] + textarea, .lw-block textarea + input[type="text"], .lw-block textarea + textarea, .lw-block input[type="text"] + .lw-block-grid, .lw-block textarea + .lw-block-grid { margin-top: 8px; }
    .lw-block input:focus, .lw-block textarea:focus, .lw-block select:focus { border-color: #bfdbfe; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }
    .lw-block textarea { resize: vertical; min-height: 56px; }

    .lw-block-title-input { width: 100%; border: 0 !important; background: transparent !important; font-size: 20px !important; font-weight: 850 !important; padding: 4px 2px !important; color: var(--lw-slate-900); outline: none; border-bottom: 2px solid transparent !important; box-shadow: none !important; border-radius: 0 !important; transition: 0.15s ease; }
    .lw-block-title-input:focus { border-bottom-color: var(--lw-primary) !important; }

    .lw-block-row { display: flex; gap: 14px; align-items: flex-start; }
    .lw-block-row-body { flex: 1; min-width: 0; }
    .lw-block-icon-tag { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; background: var(--lw-primary-soft); color: var(--lw-primary); border-radius: 10px; font-size: 18px; flex-shrink: 0; }
    .lw-block-icon-tag.material { background: var(--lw-rose-soft); color: var(--lw-rose); }
    .lw-block-icon-tag.quiz { background: var(--lw-primary-soft); color: var(--lw-primary); }
    .lw-block-icon-tag.submission { background: var(--lw-rose-soft); color: var(--lw-rose); }

    .lw-block-meta { font-size: 11.5px; font-weight: 700; color: var(--lw-slate-500); margin-top: 8px; display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .lw-block-meta a { color: var(--lw-primary); text-decoration: none; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; }
    .lw-block-meta a:hover { text-decoration: underline; }
    .lw-block-meta-sep { color: var(--lw-slate-300); }
    .lw-block-heading { font-size: 15px; font-weight: 850; color: var(--lw-slate-900); line-height: 1.3; margin-top: 2px; }
    .lw-block-description { margin-top: 5px; color: var(--lw-slate-600); font-size: 12.5px; font-weight: 600; line-height: 1.5; white-space: pre-wrap; }
    .lw-block-mini-label { display: block; font-size: 10.5px; font-weight: 800; color: var(--lw-slate-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 5px; }
    .lw-block-assessment-topline { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 5px; }
    .lw-block-assessment-topline .lw-block-mini-label { margin-bottom: 0; }
    .lw-assessment-state-badge { display: inline-flex; align-items: center; border: 1px solid; border-radius: 999px; padding: 3px 8px; font-size: 9.5px; font-weight: 900; letter-spacing: .045em; line-height: 1.1; text-transform: uppercase; white-space: nowrap; }
    .lw-assessment-state-badge.is-always { color: #1d4ed8; background: #eff6ff; border-color: #bfdbfe; }
    .lw-assessment-state-badge.is-scheduled { color: #6d28d9; background: #f5f3ff; border-color: #ddd6fe; }
    .lw-assessment-state-badge.is-open { color: #047857; background: #ecfdf5; border-color: #a7f3d0; }
    .lw-assessment-state-badge.is-closed { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
    .lw-block-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    @media (max-width: 600px) { .lw-block-grid { grid-template-columns: 1fr; } }

    .lw-block-media { width: 100%; border-radius: 10px; overflow: hidden; border: 1px solid var(--lw-border); background: #0f172a; margin-bottom: 10px; max-height: 360px; display: flex; align-items: center; justify-content: center; }
    .lw-block-media img { display: block; width: auto; height: auto; max-width: 100%; max-height: 360px; object-fit: contain; }
    .lw-block-media video { display: block; width: 100%; max-height: 360px; }
    .lw-block-media audio { width: 100%; padding: 16px; background: #f8fafc; }
    .lw-block-media-yt { aspect-ratio: 16/9; max-height: none; padding: 0; }
    .lw-block-media-yt iframe { width: 100%; height: 100%; border: 0; display: block; }
    .lw-block-media-fallback { width: 100%; padding: 36px 16px; background: #f1f5f9; color: var(--lw-slate-500); font-size: 12.5px; font-weight: 700; text-align: center; border-radius: 10px; }
    .lw-inline-action { display: inline-flex; align-items: center; gap: 5px; margin-top: 10px; padding: 6px 9px; border: 1px solid var(--lw-border); border-radius: 8px; background: #fff; color: var(--lw-slate-600); font-family: inherit; font-size: 11px; font-weight: 800; cursor: pointer; text-decoration: none; transition: .15s ease; }
    .lw-inline-action:hover { background: #f1f5f9; color: var(--lw-slate-900); }
    .lw-inline-action.danger { border-color: #fecaca; background: var(--lw-rose-soft); color: var(--lw-rose); }
    .lw-file-replace { margin-top: 11px; }
    .lw-file-replace-form { display: grid; gap: 10px; margin-top: 9px; padding: 12px; border: 1px solid var(--lw-border); border-radius: 10px; background: #f8fafc; }
    .lw-file-replace-form.hidden { display: none; }
    .lw-file-replace-form label > span { display: block; margin-bottom: 6px; color: var(--lw-slate-500); font-size: 10px; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
    .lw-file-replace-form input[type="file"] { width: 100%; color: var(--lw-slate-600); font-size: 12px; }
    .lw-file-replace-actions { display: flex; justify-content: flex-end; gap: 7px; }
    .lw-inline-error { min-height: 0; margin: 0; color: var(--lw-red); font-size: 11px; font-weight: 700; }
    .lw-form-error { display: flex; align-items: flex-start; gap: 8px; padding: 11px 13px; border: 1px solid #fecaca; border-radius: 10px; background: var(--lw-red-soft); color: #991b1b; font-size: 12px; font-weight: 750; }
    .lw-form-error.hidden { display: none; }

    :where(.lw-page, .lw-modal, .lw-editor) button:focus-visible,
    :where(.lw-page, .lw-modal, .lw-editor) a:focus-visible,
    :where(.lw-page, .lw-modal, .lw-editor) input:focus-visible,
    :where(.lw-page, .lw-modal, .lw-editor) select:focus-visible,
    :where(.lw-page, .lw-modal, .lw-editor) textarea:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .24);
        outline-offset: 2px;
    }
    .lw-block:focus-visible { border-color: var(--lw-primary); box-shadow: 0 0 0 4px rgba(37,99,235,.13); outline: none; }

    /* ================================ EMPTY STATE ================================ */
    .lw-empty-state { max-width: 720px; margin: 0 auto; background: #fff; border: 1px dashed var(--lw-slate-300); border-radius: 12px; padding: 36px 24px; text-align: center; }
    .lw-empty-icon { width: 56px; height: 56px; border-radius: 50%; background: var(--lw-primary-soft); color: var(--lw-primary); display: inline-flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; }
    .lw-empty-title { font-size: 14px; font-weight: 850; color: var(--lw-slate-900); margin: 0 0 4px; }
    .lw-empty-sub { font-size: 12.5px; font-weight: 600; color: var(--lw-slate-500); margin: 0; }

    /* ================================ ADD PANELS (stackable) ================================ */
    .lw-add-panels { max-width: 720px; margin: 14px auto 0; display: flex; flex-direction: column; gap: 12px; }
    .lw-add-panel { background: #fff; border: 1px solid var(--lw-border); border-radius: 12px; box-shadow: var(--lw-shadow); padding: 16px 18px; animation: lwSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1); }
    .lw-add-panel.hidden { display: none !important; }
    .lw-add-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
    .lw-add-panel-title { font-size: 13px; font-weight: 850; color: var(--lw-slate-900); display: inline-flex; align-items: center; gap: 8px; }
    .lw-add-panel-title i { font-size: 16px; }
    .lw-add-panel-title.media i { color: var(--lw-green); }
    .lw-add-panel-title.youtube i { color: var(--lw-red); }
    .lw-add-panel-title.file i { color: var(--lw-rose); }
    .lw-add-panel-title.quiz i { color: var(--lw-primary); }
    .lw-add-panel-title.submission i { color: var(--lw-rose); }
    .lw-add-panel-close { background: transparent; border: 0; color: var(--lw-slate-400); font-size: 18px; cursor: pointer; padding: 2px 6px; line-height: 1; transition: 0.15s ease; border-radius: 6px; }
    .lw-add-panel-close:hover { color: var(--lw-slate-900); background: #f1f5f9; }
    .lw-add-panel-body { display: flex; flex-direction: column; gap: 10px; }
    .lw-add-panel-body input[type="text"], .lw-add-panel-body input[type="file"], .lw-add-panel-body textarea, .lw-add-panel-body select { width: 100%; border: 1px solid var(--lw-border); border-radius: 8px; padding: 9px 12px; font-size: 13.5px; font-weight: 600; color: var(--lw-slate-900); background: #fff; outline: none; font-family: inherit; transition: 0.15s ease; }
    .lw-add-panel-body input:focus, .lw-add-panel-body textarea:focus, .lw-add-panel-body select:focus { border-color: #bfdbfe; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }
    .lw-add-panel-body textarea { resize: vertical; min-height: 56px; }
    .lw-add-panel-foot { margin-top: 4px; display: flex; justify-content: flex-end; }
    .lw-add-panel-hint { font-size: 11.5px; font-weight: 650; color: var(--lw-slate-400); }
    .lw-assessment-picker { display: grid; gap: 8px; max-height: 250px; overflow-y: auto; padding: 1px; }
    .lw-assessment-picker-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 11px; border: 1px solid var(--lw-border); border-radius: 10px; background: #fff; cursor: pointer; transition: .15s ease; }
    .lw-assessment-picker-item:hover { border-color: #bfdbfe; background: #f8fbff; }
    .lw-assessment-picker-item:has(input:checked) { border-color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .09); }
    .lw-assessment-picker-item.is-attached { cursor: not-allowed; background: #f8fafc; }
    .lw-assessment-picker-item input { margin-top: 3px; accent-color: var(--lw-primary); }
    .lw-assessment-picker-copy { min-width: 0; flex: 1; display: grid; gap: 4px; }
    .lw-assessment-picker-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
    .lw-assessment-picker-heading strong { min-width: 0; color: var(--lw-slate-800); font-size: 12px; line-height: 1.4; }
    .lw-assessment-picker-meta { color: var(--lw-slate-500); font-size: 10.5px; font-weight: 700; }
    .lw-assessment-picker-item.is-attached .lw-assessment-picker-meta { color: #92400e; }

    /* ================================ TOOLBAR ================================ */
    .lw-toolbar-wrap { display: flex; justify-content: center; }
    .lw-toolbar { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 4px; background: #f8fafc; border: 1px solid var(--lw-border); border-radius: 14px; padding: 6px; }
    .lw-toolbar-label { font-size: 10px; font-weight: 850; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.08em; padding: 0 10px 0 8px; align-self: center; }
    .lw-tool-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; background: transparent; color: var(--lw-slate-700); border: 1px solid transparent; border-radius: 9px; font-size: 12px; font-weight: 750; cursor: pointer; transition: 0.15s ease; font-family: inherit; }
    .lw-tool-btn:hover { background: #fff; border-color: var(--lw-border); color: var(--lw-slate-900); }
    .lw-tool-btn.accent-quiz { background: var(--lw-primary-soft); color: var(--lw-primary); border-color: #bfdbfe; }
    .lw-tool-btn.accent-quiz:hover { background: #dbeafe; color: var(--lw-primary-dark); }
    .lw-tool-btn.accent-submission { background: var(--lw-rose-soft); color: var(--lw-rose); border-color: #fecaca; }
    .lw-tool-btn.accent-submission:hover { background: #fee2e2; }
    .lw-tool-btn.active { background: #fff; border-color: var(--lw-primary); color: var(--lw-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }
    .lw-toolbar-sep { width: 1px; height: 22px; background: var(--lw-border); margin: 0 4px; align-self: center; }

    .lw-confirm-copy { margin: 0; color: var(--lw-slate-600); font-size: 13.5px; font-weight: 600; line-height: 1.65; white-space: pre-line; }
    .lw-confirm-warning { margin-top: 12px; color: #991b1b; font-size: 12px; font-weight: 800; }

    @keyframes lwFadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes lwSlideUp { from { opacity: 0; transform: translateY(12px) scale(0.985); } to { opacity: 1; transform: translateY(0) scale(1); } }
    @media (max-width: 768px) {
        .lw-title { font-size: 22px; }
        .lw-editor-body { padding: 16px; }
    }

    /* ================================ ASSESSMENT BUILDER ================================ */
    .lw-assess-body { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 22px; padding: 22px 26px; }
    .lw-assess-body.submission-mode { grid-template-columns: minmax(0, 1fr); }
    @media (max-width: 980px) { .lw-assess-body { grid-template-columns: 1fr; } }
    .lw-assess-main { min-width: 0; display: flex; flex-direction: column; gap: 16px; }
    .lw-assess-side { display: flex; flex-direction: column; gap: 12px; }
    @media (min-width: 981px) { .lw-assess-side { position: sticky; top: 0; align-self: start; max-height: calc(88vh - 110px); overflow-y: auto; } }

    .lw-assess-section { background: #fff; border: 1px solid var(--lw-border); border-radius: 14px; padding: 18px 20px; box-shadow: var(--lw-shadow); }
    .lw-assess-section-head { display: flex; align-items: center; gap: 11px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    .lw-assess-section-icon { width: 34px; height: 34px; border-radius: 9px; background: var(--lw-primary-soft); color: var(--lw-primary); display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .lw-assess-section-title { font-size: 14px; font-weight: 850; color: var(--lw-slate-900); margin: 0; line-height: 1.2; }
    .lw-assess-section-sub { font-size: 11.5px; font-weight: 650; color: var(--lw-slate-500); margin-top: 3px; }

    .lw-field-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .lw-field-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
    @media (max-width: 640px) { .lw-field-grid-2, .lw-field-grid-3 { grid-template-columns: 1fr; } }

    .lw-template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; }
    .lw-template-btn { background: #fff; border: 1px solid var(--lw-border); border-radius: 11px; padding: 14px; text-align: left; cursor: pointer; transition: 0.15s ease; font-family: inherit; }
    .lw-template-btn:hover { border-color: var(--lw-primary); background: var(--lw-primary-soft); transform: translateY(-1px); }
    .lw-template-btn-title { font-size: 13px; font-weight: 850; color: var(--lw-slate-900); margin-bottom: 4px; }
    .lw-template-btn-sub { font-size: 11px; font-weight: 650; color: var(--lw-slate-500); }
    .lw-custom-mix-rows { display: grid; gap: 10px; }
    .lw-custom-mix-row { display: grid; grid-template-columns: minmax(0, 1fr) 110px 34px; align-items: end; gap: 10px; padding: 10px; border: 1px solid var(--lw-border); border-radius: 10px; background: #f8fafc; }
    .lw-custom-mix-row .lw-field { margin: 0; }
    .lw-custom-mix-row .lw-question-icon-btn { margin-bottom: 4px; }
    .lw-custom-mix-summary { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; padding: 11px 12px; border-radius: 9px; background: var(--lw-primary-soft); color: var(--lw-primary-dark); font-size: 12px; font-weight: 800; }
    @media (max-width: 560px) { .lw-custom-mix-row { grid-template-columns: 1fr 90px; } .lw-custom-mix-row .lw-question-icon-btn { grid-column: 2; justify-self: end; } }

    .lw-assess-questions { display: flex; flex-direction: column; gap: 14px; }
    .lw-assess-question { background: #fff; border: 1px solid var(--lw-border); border-radius: 13px; overflow: hidden; box-shadow: var(--lw-shadow); transition: 0.18s ease; }
    .lw-assess-question:hover { border-color: var(--lw-slate-300); box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07); }
    .lw-assess-question.ring-highlight { box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.28); border-color: var(--lw-primary); }
    .lw-assess-question-head { display: flex; align-items: center; gap: 10px; padding: 11px 14px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
    .lw-assess-question-num { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; color: var(--lw-slate-600); background: #fff; border: 1px solid var(--lw-border); border-radius: 6px; padding: 4px 9px; }
    .lw-assess-question-type-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 850; padding: 4px 9px; border-radius: 6px; background: var(--lw-primary-soft); color: var(--lw-primary); border: 1px solid #bfdbfe; text-transform: uppercase; letter-spacing: 0.04em; }
    .lw-assess-question-type-badge.checkbox { background: var(--lw-green-soft); color: var(--lw-green); border-color: #a7f3d0; }
    .lw-assess-question-type-badge.true_false { background: var(--lw-indigo-soft); color: var(--lw-indigo); border-color: #c7d2fe; }
    .lw-assess-question-type-badge.short_answer { background: var(--lw-amber-soft); color: var(--lw-amber); border-color: #fcd34d; }
    .lw-assess-question-type-badge.essay { background: #f3e8ff; color: #7c3aed; border-color: #ddd6fe; }
    .lw-assess-question-body { padding: 14px 16px; }
    .lw-assess-question-body > * + * { margin-top: 12px; }
    .lw-assess-question-body textarea, .lw-assess-question-body input[type="text"], .lw-assess-question-body input[type="file"] { width: 100%; border: 1px solid var(--lw-border); border-radius: 8px; padding: 9px 12px; font-size: 13px; font-weight: 600; color: var(--lw-slate-900); background: #fff; outline: none; transition: 0.15s ease; font-family: inherit; }
    .lw-assess-question-body textarea:focus, .lw-assess-question-body input:focus { border-color: #bfdbfe; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }
    .lw-assess-question-body textarea { resize: vertical; min-height: 60px; }

    .lw-stepper { display: inline-flex; align-items: stretch; border: 1px solid var(--lw-border); border-radius: 8px; overflow: hidden; background: #fff; }
    .lw-stepper-btn { width: 28px; background: #f8fafc; border: 0; color: var(--lw-slate-600); font-size: 14px; font-weight: 900; cursor: pointer; transition: 0.15s ease; display: inline-flex; align-items: center; justify-content: center; padding: 0; }
    .lw-stepper-btn:hover { background: var(--lw-primary-soft); color: var(--lw-primary); }
    .lw-stepper-input { width: 56px !important; border: 0 !important; text-align: center !important; font-size: 13px !important; font-weight: 800 !important; color: var(--lw-slate-900) !important; background: #fff !important; outline: none !important; border-left: 1px solid var(--lw-border) !important; border-right: 1px solid var(--lw-border) !important; border-radius: 0 !important; padding: 6px 4px !important; -moz-appearance: textfield; appearance: textfield; }
    .lw-stepper-input::-webkit-inner-spin-button, .lw-stepper-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .lw-question-marks-wrap { display: inline-flex; align-items: center; gap: 8px; }
    .lw-question-marks-label { font-size: 10.5px; font-weight: 900; color: var(--lw-slate-500); text-transform: uppercase; letter-spacing: 0.06em; }
    .lw-question-remove-btn { width: 30px; height: 30px; background: #fff; border: 1px solid #fecaca; border-radius: 7px; color: var(--lw-rose); font-size: 12px; cursor: pointer; transition: 0.15s ease; display: inline-flex; align-items: center; justify-content: center; padding: 0; }
    .lw-question-remove-btn:hover { background: var(--lw-rose-soft); border-color: #fca5a5; }

    .lw-question-image-preview { display: none; margin-top: 8px; }
    .lw-question-image-preview.show { display: block; }
    .lw-question-image-preview img { max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid var(--lw-border); padding: 4px; background: #f8fafc; object-fit: contain; display: block; }

    .lw-choice-list { display: flex; flex-direction: column; gap: 7px; margin-bottom: 10px; }
    .lw-choice-row { display: flex; align-items: center; gap: 10px; }
    .lw-choice-correct { width: 18px; height: 18px; accent-color: var(--lw-primary); flex-shrink: 0; cursor: pointer; }
    .lw-choice-text { flex: 1; border: 1px solid var(--lw-border) !important; border-radius: 8px !important; padding: 8px 11px !important; font-size: 13px !important; font-weight: 600 !important; color: var(--lw-slate-900) !important; background: #fff !important; }
    .lw-choice-text:focus { border-color: #bfdbfe !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09) !important; }
    .lw-choice-remove { width: 28px; height: 28px; background: #fff; border: 1px solid #fecaca; border-radius: 7px; color: var(--lw-rose); font-size: 13px; font-weight: 900; cursor: pointer; transition: 0.15s ease; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; padding: 0; }
    .lw-choice-remove:hover { background: var(--lw-rose-soft); border-color: #fca5a5; }
    .lw-choice-add { background: transparent; color: var(--lw-primary); border: 1px dashed #93c5fd; border-radius: 8px; padding: 7px 12px; font-size: 12px; font-weight: 800; cursor: pointer; transition: 0.15s ease; display: inline-flex; align-items: center; gap: 5px; font-family: inherit; }
    .lw-choice-add:hover { background: var(--lw-primary-soft); border-style: solid; }

    .lw-tf-options { display: flex; flex-direction: column; gap: 8px; }
    .lw-tf-label { display: flex; align-items: center; gap: 10px; padding: 9px 12px; background: #f8fafc; border: 1px solid var(--lw-border); border-radius: 8px; cursor: pointer; transition: 0.15s ease; }
    .lw-tf-label:hover { border-color: var(--lw-primary); background: var(--lw-primary-soft); }
    .lw-tf-text { font-size: 13px; font-weight: 800; color: var(--lw-slate-700); }

    .lw-answer-block { background: #f8fafc; border: 1px solid var(--lw-border); border-radius: 10px; padding: 14px; }
    .lw-answer-block-label { display: block; font-size: 10.5px; font-weight: 900; color: var(--lw-slate-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 7px; }
    .lw-answer-block textarea { width: 100%; border: 1px solid #a7f3d0; background: #ecfdf5; border-radius: 8px; padding: 9px 11px; font-size: 13px; font-weight: 600; color: var(--lw-slate-900); outline: none; resize: vertical; min-height: 70px; font-family: inherit; transition: 0.15s ease; }
    .lw-answer-block textarea:focus { border-color: var(--lw-green); box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12); }
    .lw-answer-block-hint { font-size: 11px; font-weight: 650; color: var(--lw-slate-400); margin-top: 6px; }

    .lw-assess-side-block { background: #fff; border: 1px solid var(--lw-border); border-radius: 12px; padding: 13px 14px; box-shadow: var(--lw-shadow); }
    .lw-assess-side-label { font-size: 10px; font-weight: 900; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 10px; }
    .lw-side-type-btn { display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 11px; background: transparent; border: 1px solid var(--lw-border); border-radius: 8px; cursor: pointer; transition: 0.15s ease; font-family: inherit; margin-bottom: 6px; text-align: left; }
    .lw-side-type-btn:last-child { margin-bottom: 0; }
    .lw-side-type-btn:hover { border-color: var(--lw-primary); background: var(--lw-primary-soft); }
    .lw-side-type-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .lw-side-type-dot.mcq { background: var(--lw-primary); }
    .lw-side-type-dot.checkbox { background: var(--lw-green); }
    .lw-side-type-dot.true_false { background: var(--lw-indigo); }
    .lw-side-type-dot.short_answer { background: var(--lw-amber); }
    .lw-side-type-dot.essay { background: #7c3aed; }
    .lw-side-type-text { font-size: 12.5px; font-weight: 800; color: var(--lw-slate-700); }
    .lw-side-type-btn:hover .lw-side-type-text { color: var(--lw-primary); }

    .lw-question-jump-list { display: flex; flex-direction: column; gap: 5px; max-height: 240px; overflow-y: auto; padding-right: 2px; }
    .lw-question-jump-btn { display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%; padding: 7px 10px; background: #fff; border: 1px solid var(--lw-border); border-radius: 7px; cursor: pointer; transition: 0.15s ease; font-family: inherit; text-align: left; }
    .lw-question-jump-btn:hover { border-color: var(--lw-primary); background: var(--lw-primary-soft); }
    .lw-question-jump-btn-num { font-size: 11.5px; font-weight: 850; color: var(--lw-slate-700); }
    .lw-question-jump-btn-type { font-size: 9.5px; font-weight: 800; color: var(--lw-slate-400); text-transform: uppercase; letter-spacing: 0.04em; }
    .lw-question-jump-empty { font-size: 11.5px; font-weight: 700; color: var(--lw-slate-400); padding: 4px 0; margin: 0; }

    .lw-assess-summary-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; font-weight: 700; color: var(--lw-slate-600); }
    .lw-assess-summary-row strong { font-weight: 900; color: var(--lw-slate-900); }

    /* Mode bar at top of assessment modal */
    .lw-assess-modebar { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 16px; background: linear-gradient(135deg, var(--lw-indigo-soft), var(--lw-primary-soft)); border: 1px solid #c7d2fe; border-radius: 12px; flex-wrap: wrap; }
    .lw-assess-modebar-l { display: inline-flex; align-items: center; gap: 9px; font-size: 13px; font-weight: 850; color: var(--lw-indigo); flex-wrap: wrap; }
    .lw-assess-modebar-l i { font-size: 16px; }
    .lw-assess-modebar-r { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .lw-assess-modebar-hint { font-size: 11px; font-weight: 700; color: var(--lw-slate-500); text-transform: uppercase; letter-spacing: 0.05em; }
    .lw-assess-modebar-select { border: 1px solid var(--lw-border); border-radius: 8px; padding: 7px 11px; font-size: 12.5px; font-weight: 650; color: var(--lw-slate-700); background: #fff; outline: none; min-width: 180px; }
    .lw-assess-modebar-select:focus { border-color: var(--lw-indigo); }

    /* Total marks read-only display */
    .lw-total-marks-card { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--lw-green-soft); border: 1px solid #a7f3d0; border-radius: 9px; margin-bottom: 14px; }
    .lw-total-marks-card-label { font-size: 11px; font-weight: 850; color: #065f46; text-transform: uppercase; letter-spacing: 0.06em; }
    .lw-total-marks-card-value { font-size: 18px; font-weight: 900; color: #064e3b; }
    .lw-scoring-hint-box { padding: 10px 13px; background: var(--lw-primary-soft); border: 1px solid #bfdbfe; border-radius: 9px; font-size: 11.5px; font-weight: 650; color: var(--lw-primary); line-height: 1.5; margin-top: 10px; }

    /* Dedicated Assessment Builder page */
    .lw-assessment-page { min-width: 0; }
    .lw-assessment-page.is-initial-submission [data-assessment-quiz-section],
    .lw-assessment-page.is-initial-submission #questionSidebar { display: none !important; }
    .lw-assessment-page.is-initial-quiz [data-assessment-submission-section] { display: none !important; }
    .lw-assessment-page-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin-bottom: 22px; }
    .lw-assessment-page-title { margin: 7px 0 5px; color: var(--lw-slate-900); font-size: 25px; font-weight: 900; letter-spacing: -.03em; }
    .lw-assessment-page-sub { margin: 0; color: var(--lw-slate-500); font-size: 13px; font-weight: 650; }
    .lw-assessment-back { display: inline-flex; align-items: center; gap: 6px; color: var(--lw-primary); font-size: 12px; font-weight: 850; text-decoration: none; }
    .lw-assessment-back:hover { color: var(--lw-primary-dark); }
    .lw-assessment-form { min-width: 0; }
    .lw-assessment-page .lw-assess-body { padding: 0; align-items: start; overflow: visible; }
    .lw-assessment-page .lw-assess-side { top: 24px; max-height: none; overflow: visible; }
    .lw-assess-flow-card { background: #fff; border: 1px solid var(--lw-border); border-radius: 14px; padding: 20px; box-shadow: var(--lw-shadow); }
    .lw-assess-flow-card h3 { margin: 3px 0 14px; color: var(--lw-slate-900); font-size: 17px; font-weight: 900; }
    .lw-assess-type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .lw-assess-type-card { display: flex; align-items: center; gap: 13px; min-height: 92px; padding: 16px; border: 1px solid var(--lw-border); border-radius: 12px; background: #fff; text-align: left; cursor: pointer; transition: .15s ease; font-family: inherit; }
    .lw-assess-type-card:hover { border-color: var(--lw-primary); box-shadow: 0 8px 20px rgba(15,23,42,.08); transform: translateY(-1px); }
    .lw-assess-type-card.is-selected { border-color: var(--lw-primary); background: var(--lw-primary-soft); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
    .lw-assess-type-card strong, .lw-assess-type-card small { display: block; }
    .lw-assess-type-card strong { color: var(--lw-slate-900); font-size: 14px; font-weight: 900; }
    .lw-assess-type-card small { margin-top: 4px; color: var(--lw-slate-500); font-size: 11.5px; font-weight: 650; }
    .lw-assess-type-icon { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 42px; border-radius: 11px; background: var(--lw-primary-soft); color: var(--lw-primary); font-size: 19px; }
    .lw-assess-type-icon.submission { background: var(--lw-rose-soft); color: var(--lw-rose); }
    .lw-assess-existing-empty { color: var(--lw-slate-500); font-size: 11px; font-weight: 750; }
    .lw-assess-load-spinner { display: inline-flex; align-items: center; gap: 5px; color: var(--lw-primary); font-size: 11px; font-weight: 800; }
    .lw-assess-load-spinner i { display: inline-block; animation: lwSpin .8s linear infinite; }
    @keyframes lwSpin { to { transform: rotate(360deg); } }
    .lw-assess-boundary { display: flex; align-items: flex-start; gap: 9px; padding: 12px 14px; border: 1px solid #fcd34d; border-radius: 10px; background: var(--lw-amber-soft); color: #92400e; font-size: 12px; font-weight: 750; line-height: 1.5; }
    .lw-availability-context { margin-top: 12px; display: inline-flex; align-items: center; padding: 6px 10px; border: 1px solid #bfdbfe; border-radius: 999px; background: var(--lw-primary-soft); color: var(--lw-primary); font-size: 11px; font-weight: 850; }
    .lw-template-note { margin: -3px 0 12px; color: var(--lw-slate-500); font-size: 11px; font-weight: 700; }
    .lw-import-entry { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 14px; padding: 13px 14px; border: 1px solid #bfdbfe; border-radius: 11px; background: var(--lw-primary-soft); }
    .lw-import-entry strong, .lw-import-entry span { display: block; }
    .lw-import-entry strong { color: var(--lw-primary); font-size: 12.5px; font-weight: 900; }
    .lw-import-entry span { margin-top: 3px; color: var(--lw-slate-600); font-size: 11px; font-weight: 650; }
    .lw-import-modal-box { width: min(1120px, calc(100vw - 32px)); max-width: 1120px; }
    .lw-import-guide { padding: 11px 13px; border-radius: 10px; background: #f8fafc; color: var(--lw-slate-600); font-size: 12px; line-height: 1.55; }
    .lw-import-guide p { margin: 5px 0 0; }
    .lw-import-guide ul { display: grid; gap: 2px; margin: 7px 0 0; padding-left: 18px; }
    .lw-import-guide ol { display: grid; gap: 2px; margin: 7px 0 0; padding-left: 18px; }
    .lw-import-guide-table-wrap { margin-top: 9px; overflow-x: auto; border: 1px solid var(--lw-border); border-radius: 8px; background: #fff; }
    .lw-import-guide-table { width: 100%; min-width: 720px; border-collapse: collapse; }
    .lw-import-guide-table th, .lw-import-guide-table td { padding: 7px 8px; border-bottom: 1px solid var(--lw-border); text-align: left; vertical-align: top; }
    .lw-import-guide-table tr:last-child td { border-bottom: 0; }
    .lw-import-guide-table th { color: var(--lw-slate-700); background: #f8fafc; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
    .lw-import-actions { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; margin-top: 14px; }
    .lw-import-preview { margin-top: 14px; min-height: 140px; max-height: 340px; overflow-y: auto; border: 1px solid var(--lw-border); border-radius: 10px; padding: 14px; background: #fff; color: var(--lw-slate-600); font-size: 12px; }
    .lw-import-preview-summary { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
    .lw-import-preview-summary span { padding: 5px 9px; border-radius: 999px; background: #f1f5f9; font-weight: 850; }
    .lw-import-errors { margin: 0; padding-left: 20px; color: #991b1b; line-height: 1.6; }
    .lw-question-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 28px 20px; border: 1px dashed var(--lw-slate-300); border-radius: 13px; background: #fff; text-align: center; color: var(--lw-slate-500); }
    .lw-question-empty i { color: var(--lw-primary); font-size: 24px; }
    .lw-question-empty strong { color: var(--lw-slate-800); font-size: 13px; }
    .lw-question-empty span { font-size: 11.5px; font-weight: 650; }
    .lw-question-section-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 4px 2px; }
    .lw-question-section-head strong { color: var(--lw-slate-900); font-size: 16px; font-weight: 900; }
    .lw-question-section-head span { margin-left: 7px; color: var(--lw-slate-500); font-size: 11.5px; font-weight: 750; }
    .lw-question-guidance { margin-top: 10px; padding: 9px 11px; border: 1px solid #fde68a; border-radius: 8px; background: var(--lw-amber-soft); color: #92400e; font-size: 11px; font-weight: 700; }
    .lw-question-guidance ul { margin: 0; padding-left: 17px; }
    .lw-import-table-wrap { overflow: auto; max-height: 260px; border: 1px solid var(--lw-border); border-radius: 9px; }
    .lw-import-table { width: 100%; border-collapse: collapse; min-width: 1500px; }
    .lw-import-table th, .lw-import-table td { padding: 8px 9px; border-bottom: 1px solid var(--lw-border); text-align: left; vertical-align: top; }
    .lw-import-table th { position: sticky; top: 0; background: #f8fafc; color: var(--lw-slate-600); font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
    .lw-import-table input, .lw-import-table select { width: 140px; min-height: 34px; padding: 6px 8px; border: 1px solid var(--lw-border); border-radius: 7px; background: #fff; color: var(--lw-slate-800); font: inherit; }
    .lw-import-table td:nth-child(3) input { width: 230px; }
    .lw-import-table td:nth-child(4) input { width: 75px; }
    .lw-import-row-status { font-weight: 900; white-space: nowrap; }
    .lw-import-row-status.is-valid { color: #047857; }
    .lw-import-row-status.is-invalid, .lw-import-row-issues { color: #991b1b; }
    .lw-import-mapping { margin-bottom: 10px; border: 1px solid var(--lw-border); border-radius: 8px; background: #f8fafc; }
    .lw-import-mapping summary { padding: 8px 10px; cursor: pointer; font-weight: 850; }
    .lw-import-mapping > div { display: flex; gap: 7px; flex-wrap: wrap; padding: 0 10px 10px; }
    .lw-import-mapping span { display: inline-flex; align-items: center; gap: 5px; padding: 4px 7px; border-radius: 6px; background: #fff; }
    .lw-assess-question.is-collapsed .lw-assess-question-body { display: none; }
    .lw-assess-question.is-collapsed .lw-assess-question-head { border-bottom: 0; }
    .lw-question-preview { flex: 1 1 220px; min-width: 0; overflow: hidden; color: var(--lw-slate-600); font-size: 11.5px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .lw-question-status { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border: 1px solid #fcd34d; border-radius: 999px; background: var(--lw-amber-soft); color: #92400e; font-size: 9.5px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
    .lw-question-status.is-complete { border-color: #a7f3d0; background: var(--lw-green-soft); color: #047857; }
    .lw-question-icon-btn { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--lw-border); border-radius: 7px; background: #fff; color: var(--lw-slate-500); cursor: pointer; transition: .15s ease; }
    .lw-question-icon-btn:hover { border-color: var(--lw-primary); color: var(--lw-primary); background: var(--lw-primary-soft); }
    .lw-question-insert { position: relative; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9; }
    .lw-question-insert-menu { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .lw-question-insert-menu button { padding: 6px 8px; border: 1px solid var(--lw-border); border-radius: 7px; background: #fff; color: var(--lw-slate-600); font-size: 10.5px; font-weight: 800; cursor: pointer; }
    .lw-question-jump-btn-status { font-size: 9px; font-weight: 850; color: #b45309; }
    .lw-question-jump-btn-status.is-complete { color: #047857; }
    .lw-assess-question.is-locked .lw-assess-question-body :is(input, textarea, button), .lw-assess-question.is-locked .lw-assess-question-head .lw-question-marks-wrap, .lw-assess-question.is-locked .lw-question-duplicate, .lw-assess-question.is-locked .lw-question-remove-btn, .lw-assess-question.is-locked .lw-question-insert { pointer-events: none; opacity: .62; }
    .lw-question-drag { cursor: grab; color: var(--lw-slate-400); }
    @media (max-width: 980px) {
        .lw-assessment-page .lw-assess-side { position: static; }
        .lw-assessment-page-head { align-items: stretch; flex-direction: column; }
        .lw-assessment-page-head .lw-btn { justify-content: center; }
    }
    @media (max-width: 640px) {
        .lw-modal,
        .lw-editor { align-items: flex-start; padding: 12px; }
        .lw-modal-box,
        .lw-editor-box { max-height: calc(100vh - 24px); max-height: calc(100dvh - 24px); border-radius: 14px; }
        .lw-modal-head,
        .lw-modal-body,
        .lw-modal-foot { padding-right: 16px; padding-left: 16px; }
        .lw-modal-foot { flex-wrap: wrap; }
        .lw-modal-foot .lw-btn { flex: 1 1 auto; justify-content: center; }
        .lw-cover-reset-row { align-items: stretch; flex-direction: column; }
        .lw-cover-reset-btn { justify-content: center; }
        .lw-editor-head { align-items: flex-start; flex-wrap: wrap; padding-right: 16px; padding-left: 16px; }
        .lw-editor-head-l { flex-basis: 100%; }
        .lw-editor-actions { width: 100%; flex-wrap: wrap; }
        .lw-editor-actions .lw-btn { flex: 1 1 auto; justify-content: center; }
        .lw-editor-body { padding: 18px 16px; }
        .lw-assess-type-grid { grid-template-columns: 1fr; }
        .lw-import-entry { align-items: stretch; flex-direction: column; }
        .lw-assess-question-head { align-items: flex-start; }
        .lw-question-preview { order: 10; flex-basis: 100%; }
    }

    @media (prefers-reduced-motion: reduce) {
        .lw-modal,
        .lw-modal-box,
        .lw-editor,
        .lw-editor-box,
        .lw-module-card,
        .lw-module-image-photo,
        .lw-module-image-deco i,
        .lw-module-add,
        .lw-module-add-icon {
            transition-duration: .01ms !important;
            animation-duration: .01ms !important;
        }
    }
</style>
@endpush
