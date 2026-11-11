<style>
    .app-toast-region {
        position: fixed;
        top: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        align-items: flex-end;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }

    .app-toast {
        display: flex;
        min-width: 280px;
        max-width: min(380px, calc(100vw - 48px));
        align-items: flex-start;
        gap: 10px;
        box-sizing: border-box;
        padding: 13px 16px;
        border-left: 4px solid;
        border-radius: 11px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
        color: #334155;
        font-size: 13.5px;
        font-weight: 700;
        line-height: 1.45;
        overflow-wrap: anywhere;
        animation: appToastIn 0.25s ease-out forwards;
        pointer-events: auto;
    }

    .app-toast.success { border-left-color: #059669; }
    .app-toast.success i { color: #059669; }
    .app-toast.error { border-left-color: #dc2626; }
    .app-toast.error i { color: #dc2626; }
    .app-toast i { flex: 0 0 auto; margin-top: 1px; font-size: 17px; line-height: 1.25; }
    .app-toast.is-leaving { opacity: 0; transform: translateX(20px); transition: opacity 0.3s ease, transform 0.3s ease; }

    @keyframes appToastIn {
        from { opacity: 0; transform: translateX(30px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @media (max-width: 575.98px) {
        .app-toast-region {
            top: 16px;
            right: 16px;
            left: 16px;
            width: auto;
        }

        .app-toast {
            width: 100%;
            min-width: 0;
            max-width: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .app-toast { animation: none; }
        .app-toast.is-leaving { transition: none; }
    }
</style>
