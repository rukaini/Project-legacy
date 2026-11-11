<style data-app-page-shell-styles>
    html { scrollbar-gutter: stable; }

    :root {
        --app-page-max-width: 1880px;
        --app-page-top-space: clamp(22px, 2.2vw, 36px);
        --app-page-gutter: clamp(16px, 2.4vw, 44px);
        --app-page-bottom-space: clamp(44px, 4vw, 68px);
    }

    .app-page-shell {
        box-sizing: border-box;
        width: min(100%, var(--app-page-max-width)) !important;
        min-width: 0;
        max-width: none !important;
        margin-inline: auto !important;
        padding: var(--app-page-top-space) var(--app-page-gutter) var(--app-page-bottom-space) !important;
    }

    .app-workspace-shell {
        box-sizing: border-box;
        width: min(100%, var(--app-page-max-width)) !important;
        min-width: 0;
        max-width: none !important;
        margin-inline: auto !important;
        padding-right: var(--app-page-gutter) !important;
        padding-left: var(--app-page-gutter) !important;
    }

    .app-workspace-shell--header {
        padding-top: var(--app-page-top-space) !important;
    }

    .app-page-shell > *,
    .app-workspace-shell > * { min-width: 0; }

    :is(.app-page-shell, .app-workspace-shell) :is(h1, h2, h3, .adm-title, .lw-title, .sw-title) {
        overflow-wrap: anywhere;
    }

    :is(.app-page-shell, .app-workspace-shell) :is(img, video, iframe, canvas) {
        max-width: 100%;
    }

    @media (max-width: 767.98px) {
        :root {
            --app-page-top-space: 20px;
            --app-page-gutter: 16px;
            --app-page-bottom-space: 44px;
        }
    }

    @media (max-width: 575.98px) {
        :root { --app-page-gutter: 12px; }
    }

    @media (prefers-reduced-motion: reduce) {
        :is(.app-page-shell, .app-workspace-shell) *,
        :is(.app-page-shell, .app-workspace-shell) *::before,
        :is(.app-page-shell, .app-workspace-shell) *::after {
            scroll-behavior: auto !important;
            transition-duration: 0.01ms !important;
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
        }
    }
</style>
