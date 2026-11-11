import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');

const headerStyles = read('../../resources/views/components/app-signed-in-header-styles.blade.php');
const pageShellStyles = read('../../resources/views/components/app-page-shell-styles.blade.php');
const adminLayout = read('../../resources/views/admin/layouts/app.blade.php');
const trainerLayout = read('../../resources/views/trainer/layouts/app.blade.php');
const adminNavbar = read('../../resources/views/admin/partials/navbar.blade.php');
const trainerNavbar = read('../../resources/views/trainer/partials/navbar.blade.php');
const logoutConfirmation = read('../../resources/views/components/logout-confirmation.blade.php');

test('Admin and Trainer use one canonical signed-in header design contract', () => {
    assert.match(headerStyles, /--app-header-height:\s*58px/);
    assert.match(headerStyles, /--app-header-max-width:\s*1880px/);
    assert.match(headerStyles, /--app-header-gutter:\s*clamp\(16px, 2\.25vw, 42px\)/);
    assert.match(headerStyles, /--app-header-nav-padding:\s*clamp\(11px, 0\.95vw, 16px\)/);
    assert.match(headerStyles, /\.app-header-shell\s*\{[^}]*width:\s*min\(100%, var\(--app-header-max-width\)\)[^}]*padding-inline:\s*var\(--app-header-gutter\)/s);
    assert.match(headerStyles, /\.app-header \.nav-link-top\s*\{[^}]*height:\s*var\(--app-header-height\)[^}]*border-bottom:\s*2px solid transparent/s);
    assert.match(headerStyles, /\.app-header \.nav-link-top\.active\s*\{[^}]*border-bottom-color:\s*#2563eb/s);
    assert.match(headerStyles, /\.app-header-logo\s*\{[^}]*width:\s*auto[^}]*height:\s*48px[^}]*max-height:\s*calc\(var\(--app-header-height\) - 8px\)[^}]*object-fit:\s*contain/s);
    assert.match(headerStyles, /\.app-header \.profile-avatar\s*\{[^}]*width:\s*34px[^}]*height:\s*34px/s);
    assert.match(headerStyles, /\.app-header \.logout-icon\s*\{[^}]*width:\s*36px[^}]*height:\s*36px/s);
    assert.match(headerStyles, /\.app-header-account-divider\s*\{[^}]*gap:\s*8px[^}]*padding-left:\s*16px[^}]*border-left:/s);

    for (const layout of [adminLayout, trainerLayout]) {
        assert.match(layout, /@include\('components\.app-signed-in-header-styles'\)/);
    }

    for (const navbar of [adminNavbar, trainerNavbar]) {
        assert.match(navbar, /class="topbar app-header"/);
        assert.match(navbar, /app-header-shell container-fluid/);
        assert.match(navbar, /app-header-primary-row/);
        assert.match(navbar, /app-header-brand/);
        assert.match(navbar, /app-header-nav d-none d-lg-flex/);
        assert.match(navbar, /app-header-account-actions/);
        assert.match(navbar, /app-header-account-divider/);
        assert.match(navbar, /app-header-profile-link/);
        assert.match(navbar, /app-header-avatar profile-avatar/);
        assert.match(navbar, /app-header-logout logout-icon/);
        assert.match(navbar, /asset\('images\/branding\/ai-amin-edu-oasis-logo\.jpg'\)/);
        assert.match(navbar, /class="app-header-logo"[\s\S]*?width="447"[\s\S]*?height="447"[\s\S]*?alt="Al Amin Edu Oasis"/);
        assert.doesNotMatch(navbar, /class="brand-mark"|class="brand-name/);
        assert.doesNotMatch(navbar, /brand-subtitle|Edu Admin|Edu Trainer/i);
    }

    assert.doesNotMatch(trainerNavbar, /Lead Trainer|Trainer Lead|Senior Trainer|Head Trainer/i);
    assert.match(trainerNavbar, /<div class="app-header-account-role">\s*Trainer\s*<\/div>/);
    assert.doesNotMatch(adminLayout, /EDU Admin/i);
});

test('the official wordmark replaces the temporary initials and remains responsive', () => {
    assert.equal(existsSync(new URL('../../public/images/branding/ai-amin-edu-oasis-logo.jpg', import.meta.url)), true);

    for (const navbar of [adminNavbar, trainerNavbar]) {
        assert.match(navbar, /<img[\s\S]*?app-header-logo/);
        assert.doesNotMatch(navbar, />AE<|>AA</);
    }

    assert.match(headerStyles, /\.app-header-brand\s*\{[^}]*min-width:\s*0/s);
    assert.match(headerStyles, /@media \(max-width: 575\.98px\)[\s\S]*?\.app-header-logo\s*\{\s*height:\s*44px/);
    assert.match(headerStyles, /\.app-header-account-copy\s*\{[^}]*min-width:\s*0[^}]*max-width:\s*clamp\(/s);
    assert.match(headerStyles, /\.app-header-account-name\s*\{[^}]*overflow:\s*hidden[^}]*text-overflow:\s*ellipsis[^}]*white-space:\s*nowrap/s);
});

test('Admin and Trainer logout use one accessible confirmation dialog', () => {
    for (const navbar of [adminNavbar, trainerNavbar]) {
        assert.match(navbar, /<form id="aididLogoutForm" method="POST" action="\{\{ route\('logout'\) \}\}"/);
        assert.match(navbar, /@csrf/);
        assert.match(navbar, /type="button"[\s\S]*?data-logout-confirm-open[\s\S]*?aria-controls="logoutConfirmationDialog"/);
        assert.match(navbar, /@include\('components\.logout-confirmation'\)/);
        assert.doesNotMatch(navbar, /Switch System|Back to Portal/);
    }

    assert.match(logoutConfirmation, /<dialog id="logoutConfirmationDialog"[\s\S]*?aria-labelledby="logoutConfirmationTitle"[\s\S]*?aria-describedby="logoutConfirmationMessage"/);
    assert.match(logoutConfirmation, /Are you sure you want to log out\?/);
    assert.match(logoutConfirmation, /type="button"[^>]*data-logout-confirm-cancel>Cancel<\/button>/);
    assert.match(logoutConfirmation, /type="submit" form="aididLogoutForm"[^>]*>Logout<\/button>/);
    assert.match(logoutConfirmation, /dialog\.showModal\(\)/);
    assert.match(logoutConfirmation, /cancelButton\.focus\(\)/);
    assert.match(logoutConfirmation, /addEventListener\('cancel'/);
    assert.match(logoutConfirmation, /event\.preventDefault\(\)/);
    assert.match(logoutConfirmation, /addEventListener\('close', \(\) => opener\.focus\(\)\)/);
    assert.doesNotMatch(logoutConfirmation, /window\.location|fetch\(|redirect/i);
});

test('Trainer drops its obsolete 1400px header cap and follows Admin responsive behavior', () => {
    assert.doesNotMatch(trainerNavbar, /max-width:\s*1400px|style="[^"]*max-width|\bpx-4\b|\bh-100\b/);
    assert.doesNotMatch(trainerLayout, /max-width:\s*1400px|\.topbar\s*>\s*\.container-fluid|\.nav-link-top\s*\{[^}]*height:\s*42px/s);
    assert.doesNotMatch(trainerNavbar, /d-md-(?:none|flex)/);
    assert.match(headerStyles, /@media \(max-width: 991\.98px\)[\s\S]*?\.app-header\s*\{\s*height:\s*auto/);
    assert.match(headerStyles, /@media \(max-width: 991\.98px\)[\s\S]*?\.app-header-shell\s*\{[^}]*padding-inline:\s*16px/s);
    assert.match(headerStyles, /@media \(max-width: 575\.98px\)[\s\S]*?\.app-header-mobile-toggle span/);

    assert.match(trainerNavbar, /\['label' => 'Proposal', 'url' => url\('\/trainer\/proposals'\)/);
    assert.doesNotMatch(trainerNavbar, /Training Proposal/);
});

test('the document scroll root reserves one stable scrollbar gutter for every signed-in page', () => {
    assert.match(pageShellStyles, /html\s*\{\s*scrollbar-gutter:\s*stable;\s*\}/);
    assert.doesNotMatch(pageShellStyles, /scrollbar-gutter:\s*stable\s+both-edges/);
    assert.doesNotMatch(pageShellStyles, /overflow-y:\s*scroll|width:\s*100vw/);

    const trainerProfileStyles = read('../../resources/views/trainer/profile/partials/styles.blade.php');
    const adminProfileStyles = read('../../resources/views/admin/profile/partials/styles.blade.php');

    for (const styles of [trainerProfileStyles, adminProfileStyles]) {
        assert.doesNotMatch(styles, /scrollbar-gutter|overflow-y:\s*scroll|width:\s*100vw/);
    }
});

test('the approved application page-shell dimensions remain unchanged', () => {
    assert.match(pageShellStyles, /--app-page-max-width:\s*1880px/);
    assert.match(pageShellStyles, /--app-page-top-space:\s*clamp\(22px, 2\.2vw, 36px\)/);
    assert.match(pageShellStyles, /--app-page-gutter:\s*clamp\(16px, 2\.4vw, 44px\)/);
    assert.match(pageShellStyles, /--app-page-bottom-space:\s*clamp\(44px, 4vw, 68px\)/);
});
