<style data-app-signed-in-header-styles>
    :root {
        --app-header-height: 58px;
        --app-header-max-width: 1880px;
        --app-header-gutter: clamp(16px, 2.25vw, 42px);
        --app-header-nav-padding: clamp(11px, 0.95vw, 16px);
        --app-header-radius: 10px;
    }

    .app-header {
        position: sticky;
        top: 0;
        z-index: 1020;
        height: var(--app-header-height);
        border-bottom: 1px solid #e2e8f0;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 1px 10px rgba(15, 23, 42, 0.04);
        backdrop-filter: blur(10px);
    }

    .app-header-shell {
        width: min(100%, var(--app-header-max-width));
        height: 100%;
        margin-inline: auto;
        padding-inline: var(--app-header-gutter) !important;
    }

    .app-header-primary-row {
        height: 100%;
        min-width: 0;
    }

    .app-header-brand {
        display: inline-flex;
        align-items: center;
        min-width: 0;
        flex: 0 0 auto;
        gap: 8px;
        margin-right: 24px;
        text-decoration: none;
    }

    .app-header-logo {
        display: block;
        width: auto;
        height: 48px;
        max-width: 52px;
        max-height: calc(var(--app-header-height) - 8px);
        object-fit: contain;
        object-position: left center;
        flex: 0 0 auto;
    }

    .app-header-nav {
        align-items: center;
        height: 100%;
        min-width: 0;
    }

    .app-header .nav-link-top {
        height: var(--app-header-height);
        border-bottom: 2px solid transparent;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 0;
        padding: 0 var(--app-header-nav-padding);
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
    }

    .app-header .nav-link-top:hover {
        background: #f8fafc;
        color: #111827;
    }

    .app-header .nav-link-top.active {
        border-bottom-color: #2563eb;
        background: #eef2ff;
        color: #2563eb;
    }

    .app-header-account-actions {
        display: flex;
        align-items: center;
        flex: 0 0 auto;
        gap: 16px;
        margin-left: auto;
    }

    .app-header-account-divider {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-left: 16px;
        border-left: 1px solid #dee2e6;
    }

    .app-header-profile-link {
        display: flex;
        align-items: center;
        min-width: 0;
        gap: 8px;
        border-radius: var(--app-header-radius);
        text-decoration: none;
    }

    .app-header-account-copy {
        min-width: 0;
        max-width: clamp(96px, 12vw, 180px);
    }

    .app-header-account-name {
        overflow: hidden;
        color: #212529;
        font-size: 14px;
        font-weight: 700;
        line-height: 1;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .app-header-account-role {
        color: #6c757d;
        font-size: 11px;
        font-weight: 600;
    }

    .app-header .profile-avatar {
        width: 34px;
        height: 34px;
        border: 1px solid #bfdbfe;
        border-radius: var(--app-header-radius);
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-weight: 700;
    }

    .app-header .profile-link-active .profile-avatar {
        border-color: #2563eb !important;
        background: #2563eb !important;
        color: #fff !important;
    }

    .app-header .logout-icon {
        width: 36px;
        height: 36px;
        padding: 0;
        border: 0;
        border-radius: var(--app-header-radius);
        background: transparent;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    .app-header .logout-icon:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    .app-header-mobile-toggle {
        min-height: 38px;
        padding: 6px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        background: #fff;
        color: #334155;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 700;
    }

    .app-header-mobile-toggle:hover {
        background: #f8fafc;
        color: #1d4ed8;
    }

    .app-header-mobile-toggle .bi {
        font-size: 19px;
        line-height: 1;
    }

    .app-header-mobile-navigation {
        padding: 4px 0 12px;
        border-top: 1px solid #e2e8f0;
    }

    .app-header-mobile-link {
        width: 100%;
        min-height: 44px;
        padding: 10px 12px;
        border-left: 3px solid transparent;
        border-radius: 8px;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
    }

    .app-header-mobile-link:hover {
        background: #f8fafc;
        color: #111827;
    }

    .app-header-mobile-link.active {
        border-left-color: #2563eb;
        background: #eef2ff;
        color: #1d4ed8;
    }

    .app-header :is(button, a):focus-visible {
        outline: 3px solid rgba(37, 99, 235, 0.35);
        outline-offset: 2px;
    }

    @media (max-width: 1199.98px) {
        :root { --app-header-nav-padding: 9px; }

        .app-header .nav-link-top { font-size: 13px; }
        .app-header-brand { margin-right: 16px; }
        .app-header-account-copy { max-width: 120px; }
    }

    @media (max-width: 991.98px) {
        .app-header { height: auto; }

        .app-header-shell {
            height: auto;
            padding-inline: 16px !important;
        }

        .app-header-primary-row { min-height: var(--app-header-height); }
        .app-header-account-actions { gap: 8px; }
    }

    @media (max-width: 575.98px) {
        .app-header-logo { height: 44px; }
        .app-header-account-actions { gap: 6px; }
        .app-header-account-divider { padding-left: 8px; }

        .app-header-mobile-toggle {
            min-width: 40px;
            padding-right: 8px;
            padding-left: 8px;
        }

        .app-header-mobile-toggle span {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .app-header * {
            scroll-behavior: auto !important;
            transition-duration: 0.01ms !important;
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
        }
    }
</style>
