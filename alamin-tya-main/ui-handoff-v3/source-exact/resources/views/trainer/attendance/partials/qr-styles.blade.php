<style>
    .attendance-qr-panel { display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 1px solid #e2e8f0; }
    .attendance-qr-button { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 38px; padding: 8px 13px; border: 1px solid #2563eb; border-radius: 9px; background: #2563eb; color: #fff; font: inherit; font-size: 11.5px; font-weight: 850; cursor: pointer; transition: .15s ease; }
    .attendance-qr-button:hover { border-color: #1d4ed8; background: #1d4ed8; transform: translateY(-1px); }
    .attendance-qr-button:disabled { border-color: #cbd5e1; background: #e2e8f0; color: #64748b; cursor: not-allowed; opacity: 1; }
    .attendance-qr-button:disabled:hover { border-color: #cbd5e1; background: #e2e8f0; transform: none; }
    .attendance-qr-modal .modal-dialog { width: min(620px, calc(100% - 24px)); margin-right: auto; margin-left: auto; }
    .attendance-qr-modal .modal-content { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 18px; box-shadow: 0 24px 70px rgba(15, 23, 42, .22); }
    .attendance-qr-modal .modal-header { align-items: flex-start; padding: 17px 20px; border-bottom-color: #f1f5f9; }
    .attendance-qr-modal-kicker { margin: 0 0 3px; color: #2563eb; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    .attendance-qr-modal .modal-title { color: #0f172a; font-size: 19px; font-weight: 900; }
    .attendance-qr-modal .modal-body { padding: 20px; text-align: center; }
    .attendance-qr-session h3 { margin: 0; overflow-wrap: anywhere; color: #0f172a; font-size: 17px; font-weight: 850; }
    .attendance-qr-session-meta { display: flex; flex-wrap: wrap; justify-content: center; gap: 7px 16px; margin-top: 7px; color: #64748b; font-size: 12px; font-weight: 650; }
    .attendance-qr-session-meta > span { display: inline-flex; align-items: center; gap: 5px; }
    .attendance-qr-state-notice { align-items: flex-start; justify-content: center; gap: 8px; margin: 14px auto 0; padding: 10px 12px; border: 1px solid #bbf7d0; border-radius: 9px; background: #f0fdf4; color: #166534; font-size: 11.5px; }
    .attendance-qr-state-notice > div { display: grid; gap: 1px; text-align: left; }
    .attendance-qr-state-notice strong { font-weight: 850; }
    .attendance-qr-state-notice span:not(.attendance-qr-modal-state-dot) { color: inherit; font-weight: 650; }
    .attendance-qr-modal-state-dot { width: 7px; height: 7px; flex: 0 0 auto; margin-top: 5px; border-radius: 999px; background: #22c55e; }
    .attendance-qr-state-notice.upcoming { border-color: #bfdbfe; background: #eff6ff; color: #1e40af; }
    .attendance-qr-state-notice.upcoming .attendance-qr-modal-state-dot { background: #3b82f6; }
    .attendance-qr-state-notice.expired { border-color: #fde68a; background: #fffbeb; color: #92400e; }
    .attendance-qr-state-notice.expired .attendance-qr-modal-state-dot { background: #f59e0b; }
    .attendance-qr-state-notice.missing { border-color: #e2e8f0; background: #f8fafc; color: #475569; }
    .attendance-qr-state-notice.missing .attendance-qr-modal-state-dot { background: #94a3b8; }
    .attendance-qr-state-notice:not([hidden]) { display: flex; }
    .attendance-qr-modal [hidden] { display: none !important; }
    .attendance-qr-image-frame { display: flex; align-items: center; justify-content: center; width: min(380px, 100%); aspect-ratio: 1; margin: 18px auto 12px; overflow: hidden; border: 1px solid #dbeafe; border-radius: 16px; background: #fff; }
    .attendance-qr-image-frame img { display: block; width: 100%; height: 100%; object-fit: contain; }
    .attendance-qr-loading, .attendance-qr-image-error { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; padding: 20px; color: #64748b; font-size: 12px; font-weight: 700; }
    .attendance-qr-loading .spinner-border { width: 24px; height: 24px; border-width: 2px; color: #2563eb; }
    .attendance-qr-image-error i { color: #94a3b8; font-size: 28px; }
    .attendance-qr-instruction { margin: 0; color: #334155; font-size: 13px; font-weight: 750; }
    .attendance-qr-modal-expiry { margin: 5px 0 0; color: #64748b; font-size: 11.5px; font-weight: 650; }
    .attendance-qr-modal-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 9px; margin-top: 16px; }
    .attendance-qr-modal-action { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 38px; padding: 8px 13px; border: 1px solid #2563eb; border-radius: 9px; background: #2563eb; color: #fff; font: inherit; font-size: 11.5px; font-weight: 850; text-decoration: none; cursor: pointer; }
    .attendance-qr-modal-action:hover { border-color: #1d4ed8; background: #1d4ed8; color: #fff; }
    @media (max-width: 575.98px) { .attendance-qr-panel { align-items: stretch; } .attendance-qr-button { width: 100%; } .attendance-qr-modal .modal-body { padding: 17px 14px 18px; } .attendance-qr-image-frame { width: min(340px, 100%); margin-top: 15px; } .attendance-qr-modal-actions { align-items: stretch; flex-direction: column; } .attendance-qr-modal-action { width: 100%; } }
    @media (prefers-reduced-motion: reduce) { .attendance-qr-button { transition-duration: .01ms; } .attendance-qr-button:hover { transform: none; } }
</style>
