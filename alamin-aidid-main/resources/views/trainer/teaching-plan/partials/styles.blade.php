<style data-teaching-plan-styles>
    .tp-page { padding-top: 24px; padding-bottom: 48px; color: #0f172a; }
    .tp-workspace { display: grid; gap: 16px; }
    .tp-page-heading { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 4px 2px 2px; }
    .tp-page-title { display: flex; min-width: 0; align-items: center; gap: 14px; }
    .tp-page-icon { display: inline-flex; width: 44px; height: 44px; flex: 0 0 44px; align-items: center; justify-content: center; border: 1px solid #bfdbfe; border-radius: 12px; background: #eff6ff; color: #2563eb; font-size: 19px; }
    .tp-page-heading h1 { margin: 0; color: #0f172a; font-size: 24px; font-weight: 900; letter-spacing: -.025em; }
    .tp-page-heading p { margin: 4px 0 0; color: #64748b; font-size: 12.5px; font-weight: 600; }
    .tp-state { display: inline-flex; flex: 0 0 auto; align-items: center; padding: 6px 11px; border: 1px solid; border-radius: 999px; font-size: 10px; font-weight: 850; letter-spacing: .035em; text-transform: uppercase; }
    .tp-state.upload-required { border-color: #fcd34d; background: #fffbeb; color: #a16207; }
    .tp-state.awaiting-feedback { border-color: #cbd5e1; background: #f8fafc; color: #475569; }
    .tp-state.new-feedback { border-color: #93c5fd; background: #eff6ff; color: #1d4ed8; }
    .tp-state.up-to-date { border-color: #a7f3d0; background: #ecfdf5; color: #047857; }
    .tp-status-message { display: flex; align-items: center; gap: 9px; min-height: 42px; padding: 10px 13px; border: 1px solid; border-radius: 11px; font-size: 12px; font-weight: 720; }
    .tp-status-message.upload-required { border-color: #fde68a; background: #fffbeb; color: #92400e; }
    .tp-status-message.awaiting-feedback { border-color: #e2e8f0; background: #f8fafc; color: #475569; }
    .tp-status-message.new-feedback { border-color: #bfdbfe; background: #eff6ff; color: #1e40af; }
    .tp-status-message.up-to-date { border-color: #d1fae5; background: #f0fdf4; color: #047857; }

    .tp-panel { overflow: hidden; padding: 20px 22px; border: 1px solid #e2e8f0; border-radius: 15px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .04); }
    .tp-current-panel { border-left: 4px solid #2563eb; box-shadow: 0 7px 22px rgba(37, 99, 235, .07); }
    .tp-section-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 15px; }
    .tp-section-head h2, .tp-first-submission h2 { margin: 0; color: #0f172a; font-size: 15px; font-weight: 850; }
    .tp-section-head p, .tp-first-submission p { margin: 4px 0 0; color: #64748b; font-size: 11.5px; font-weight: 600; }
    .tp-section-kicker { display: block; margin-bottom: 4px; color: #2563eb; font-size: 9.5px; font-weight: 850; letter-spacing: .07em; text-transform: uppercase; }

    .tp-current { display: flex; min-width: 0; align-items: center; gap: 14px; padding: 4px 0 2px; }
    .tp-file-icon { display: inline-flex; width: 46px; height: 46px; flex: 0 0 46px; align-items: center; justify-content: center; border-radius: 12px; background: #eff6ff; color: #2563eb; font-size: 20px; }
    .tp-file-main { display: flex; min-width: 0; flex: 1; flex-direction: column; }
    .tp-file-main strong { overflow: hidden; color: #0f172a; font-size: 13.5px; font-weight: 850; overflow-wrap: anywhere; }
    .tp-file-main > span { margin-top: 4px; color: #64748b; font-size: 10.5px; font-weight: 650; }
    .tp-actions { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
    .tp-actions a { display: inline-flex; min-height: 34px; align-items: center; justify-content: center; gap: 6px; padding: 7px 11px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #334155; font-size: 10.5px; font-weight: 800; text-decoration: none; transition: border-color .15s ease, color .15s ease, box-shadow .15s ease; }
    .tp-actions a:hover { border-color: #93c5fd; color: #1d4ed8; box-shadow: 0 4px 10px rgba(15, 23, 42, .07); }
    .tp-unavailable { display: inline-flex; align-items: center; gap: 5px; color: #94a3b8; font-size: 10.5px; font-weight: 750; }

    .tp-first-submission { display: flex; align-items: center; gap: 13px; border-left: 4px solid #f59e0b; }
    .tp-empty-icon { display: inline-flex; width: 42px; height: 42px; flex: 0 0 42px; align-items: center; justify-content: center; border-radius: 11px; background: #fffbeb; color: #d97706; font-size: 18px; }
    .tp-feedback-head { align-items: center; }
    .tp-awaiting-feedback { display: flex; align-items: center; gap: 8px; padding: 11px 13px; border-left: 3px solid #cbd5e1; background: #f8fafc; color: #64748b; font-size: 11.5px; font-weight: 700; }
    .tp-feedback-list { border-top: 1px solid #dbeafe; }
    .tp-feedback-item { padding: 15px 2px; border-bottom: 1px solid #e2e8f0; }
    .tp-feedback-item:last-child { border-bottom: 0; padding-bottom: 3px; }
    .tp-feedback-item.is-unseen { border-left: 3px solid #3b82f6; padding-left: 13px; }
    .tp-feedback-meta { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
    .tp-feedback-meta > div { display: flex; min-width: 0; align-items: center; gap: 7px; }
    .tp-reviewer-avatar { display: inline-flex; width: 24px; height: 24px; flex: 0 0 24px; align-items: center; justify-content: center; border-radius: 50%; background: #eff6ff; color: #2563eb; font-size: 11px; }
    .tp-feedback-meta strong { overflow: hidden; color: #1e293b; font-size: 11px; font-weight: 850; text-overflow: ellipsis; white-space: nowrap; }
    .tp-feedback-meta time { flex: 0 0 auto; color: #64748b; font-size: 9.5px; font-weight: 650; }
    .tp-feedback-item p { margin: 10px 0 0 31px; color: #334155; font-size: 12px; font-weight: 550; line-height: 1.65; white-space: pre-wrap; }
    .tp-new-label { display: inline-flex; padding: 2px 6px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 8.5px; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .tp-seen-button { display: inline-flex; min-height: 34px; align-items: center; justify-content: center; gap: 6px; padding: 7px 11px; border: 1px solid #93c5fd; border-radius: 8px; background: #eff6ff; color: #1d4ed8; font-size: 10.5px; font-weight: 850; cursor: pointer; }
    .tp-seen-button:hover { border-color: #60a5fa; background: #dbeafe; }
    .tp-seen-button:disabled { opacity: .65; cursor: wait; }
    .tp-seen-result { margin: 12px 0 0; font-size: 10.5px; font-weight: 750; }
    .tp-seen-result.success { color: #047857; }
    .tp-seen-result.error { color: #b91c1c; }
    .tp-seen-result[hidden] { display: none; }

    .tp-remove-panel { background: #fffdfd; }
    .tp-remove-blocked { display: flex; align-items: flex-start; gap: 8px; margin: 0; padding: 11px 13px; border-left: 3px solid #3b82f6; background: #eff6ff; color: #1e40af; font-size: 11px; font-weight: 700; line-height: 1.5; }
    .tp-remove-blocked[hidden], .tp-remove-panel form[hidden] { display: none; }
    .tp-remove-button { display: inline-flex; min-height: 38px; align-items: center; justify-content: center; gap: 7px; padding: 8px 13px; border: 1px solid #fecaca; border-radius: 9px; background: #fff; color: #b91c1c; font-size: 11px; font-weight: 850; cursor: pointer; transition: border-color .15s ease, background .15s ease; }
    .tp-remove-button:hover { border-color: #fca5a5; background: #fef2f2; }

    .tp-upload-panel { background: #fbfdff; }
    .tp-upload-form { display: flex; align-items: flex-start; gap: 11px; }
    .tp-file-field { min-width: 0; flex: 1; }
    .tp-file-picker { position: relative; display: flex; min-width: 0; min-height: 42px; align-items: center; gap: 10px; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; transition: border-color .15s ease, box-shadow .15s ease; }
    .tp-file-picker:focus-within { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(37, 99, 235, .08); }
    .tp-file-picker input { position: absolute; width: 1px; height: 1px; overflow: hidden; margin: -1px; padding: 0; border: 0; clip: rect(0 0 0 0); white-space: nowrap; }
    .tp-file-trigger { display: inline-flex; min-height: 28px; flex: 0 0 auto; align-items: center; gap: 6px; margin: 0; padding: 5px 10px; border-radius: 7px; background: #eff6ff; color: #1d4ed8; font-size: 10.5px; font-weight: 850; cursor: pointer; }
    .tp-selected-file { min-width: 0; flex: 1; overflow: hidden; color: #94a3b8; font-size: 11px; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
    .tp-selected-file.has-file { color: #334155; font-weight: 750; }
    .tp-file-clear { display: inline-flex; width: 27px; height: 27px; flex: 0 0 27px; align-items: center; justify-content: center; padding: 0; border: 0; border-radius: 6px; background: transparent; color: #64748b; cursor: pointer; }
    .tp-file-clear:hover { background: #f1f5f9; color: #0f172a; }
    .tp-file-clear[hidden] { display: none; }
    .tp-field-error { display: block; margin-top: 5px; color: #b91c1c; font-size: 10.5px; font-weight: 750; }
    .tp-upload-button { display: inline-flex; min-height: 42px; flex: 0 0 auto; align-items: center; justify-content: center; gap: 7px; padding: 8px 14px; border: 1px solid #2563eb; border-radius: 9px; background: #2563eb; color: #fff; font-size: 11px; font-weight: 850; cursor: pointer; transition: border-color .15s ease, background .15s ease, box-shadow .15s ease; }
    .tp-upload-button:hover { border-color: #1d4ed8; background: #1d4ed8; box-shadow: 0 5px 12px rgba(37, 99, 235, .16); }

    .tp-actions a:focus-visible, .tp-upload-button:focus-visible, .tp-seen-button:focus-visible, .tp-remove-button:focus-visible, .tp-file-picker:focus-within .tp-file-trigger, .tp-file-clear:focus-visible { outline: 3px solid rgba(37, 99, 235, .28); outline-offset: 2px; }

    @media (max-width: 767.98px) {
        .tp-page { padding-top: 20px; padding-bottom: 40px; }
        .tp-page-heading, .tp-current, .tp-feedback-meta { align-items: flex-start; flex-direction: column; }
        .tp-page-heading { gap: 13px; }
        .tp-state { margin-left: 58px; }
        .tp-feedback-head { align-items: flex-start; }
        .tp-upload-form { align-items: stretch; flex-direction: column; }
        .tp-upload-button, .tp-actions { width: 100%; }
        .tp-feedback-item p { margin-left: 0; }
    }
    @media (max-width: 479.98px) {
        .tp-page-title { align-items: flex-start; }
        .tp-page-heading h1 { font-size: 21px; }
        .tp-page-icon { width: 40px; height: 40px; flex-basis: 40px; }
        .tp-state { margin-left: 0; }
        .tp-panel { padding: 17px 16px; }
        .tp-current-panel { padding-left: 15px; }
        .tp-file-picker { align-items: stretch; flex-wrap: wrap; }
        .tp-selected-file { flex-basis: calc(100% - 37px); padding: 3px 2px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .tp-actions a, .tp-upload-button, .tp-file-picker { transition-duration: .01ms !important; }
    }
</style>
