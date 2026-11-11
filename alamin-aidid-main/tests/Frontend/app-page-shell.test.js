import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const shellStyles = read('../../resources/views/components/app-page-shell-styles.blade.php');

const adminPages = [
    '../../resources/views/admin/dashboard.blade.php',
    '../../resources/views/admin/proposals/partials/content.blade.php',
    '../../resources/views/admin/activity/partials/content.blade.php',
    '../../resources/views/admin/courses/partials/content.blade.php',
    '../../resources/views/admin/directory/partials/content.blade.php',
    '../../resources/views/admin/analytics/partials/content.blade.php',
    '../../resources/views/admin/profile/partials/content.blade.php',
];

const trainerPages = [
    '../../resources/views/trainer/dashboard.blade.php',
    '../../resources/views/trainer/training/my-training.blade.php',
    '../../resources/views/trainer/training/analytics/partials/content.blade.php',
    '../../resources/views/trainer/feedback/partials/dynamic-region.blade.php',
    '../../resources/views/trainer/training/proposal/partials/content.blade.php',
    '../../resources/views/trainer/profile/partials/content.blade.php',
];

const workspacePages = [
    '../../resources/views/trainer/training/workspace-overview.blade.php',
    '../../resources/views/trainer/training/material.blade.php',
    '../../resources/views/trainer/training/assessment-builder.blade.php',
    '../../resources/views/trainer/training/submission.blade.php',
    '../../resources/views/trainer/training/attendance/partials/content.blade.php',
    '../../resources/views/trainer/training/participants/partials/content.blade.php',
    '../../resources/views/trainer/training/learner-tracking/partials/content.blade.php',
    '../../resources/views/trainer/teaching-plan/show.blade.php',
    '../../resources/views/trainer/simple/overview/partials/content.blade.php',
    '../../resources/views/trainer/simple/materials/partials/content.blade.php',
    '../../resources/views/trainer/simple/attendance/partials/content.blade.php',
    '../../resources/views/trainer/simple/participants/partials/content.blade.php',
];

test('canonical page shell preserves the approved wide Admin dimensions and responsive gutters', () => {
    assert.match(shellStyles, /--app-page-max-width:\s*1880px/);
    assert.match(shellStyles, /--app-page-top-space:\s*clamp\(22px, 2\.2vw, 36px\)/);
    assert.match(shellStyles, /--app-page-gutter:\s*clamp\(16px, 2\.4vw, 44px\)/);
    assert.match(shellStyles, /--app-page-bottom-space:\s*clamp\(44px, 4vw, 68px\)/);
    assert.match(shellStyles, /\.app-page-shell\s*\{[^}]*width:\s*min\(100%, var\(--app-page-max-width\)\)[^}]*margin-inline:\s*auto[^}]*padding:/s);
    assert.match(shellStyles, /\.app-page-shell\s*\{[^}]*min-width:\s*0/s);
    assert.match(shellStyles, /\.app-workspace-shell\s*\{[^}]*min-width:\s*0/s);
    assert.match(shellStyles, /@media \(max-width: 767\.98px\)[\s\S]*?--app-page-top-space:\s*20px[\s\S]*?--app-page-gutter:\s*16px[\s\S]*?--app-page-bottom-space:\s*44px/);
    assert.match(shellStyles, /@media \(max-width: 575\.98px\)[\s\S]*?--app-page-gutter:\s*12px/);
    assert.doesNotMatch(shellStyles, /overflow-x\s*:\s*hidden|100vw|zoom\s*:/);
});

test('shared shells harden zoom and narrow viewports without masking overflow', () => {
    assert.match(shellStyles, /\.app-page-shell > \*,\s*\.app-workspace-shell > \*\s*\{\s*min-width:\s*0/);
    assert.match(shellStyles, /:is\(\.app-page-shell, \.app-workspace-shell\) :is\(img, video, iframe, canvas\)\s*\{\s*max-width:\s*100%/);
    assert.match(shellStyles, /overflow-wrap:\s*anywhere/);
    assert.match(shellStyles, /@media \(prefers-reduced-motion: reduce\)/);
    assert.doesNotMatch(shellStyles, /devicePixelRatio|window\.screen|overflow-x\s*:\s*hidden|width\s*:\s*100vw|zoom\s*:/);
});

test('all ordinary Admin pages use one wide page-shell contract including Profile', () => {
    for (const path of adminPages) {
        const source = read(path);

        assert.match(source, /adm-wrap--wide/);
        assert.match(source, /app-page-shell/);
        assert.match(source, /data-admin-width="wide"/);
        assert.doesNotMatch(source, /adm-wrap--(?:standard|compact)|data-admin-width="(?:standard|compact)"/);
    }
});

test('all Trainer top-level pages opt into the same canonical page shell', () => {
    for (const path of trainerPages) {
        assert.match(read(path), /app-page-shell/);
    }

    for (const path of [
        '../../resources/views/trainer/dashboard.blade.php',
        '../../resources/views/trainer/training/my-training.blade.php',
        '../../resources/views/trainer/training/analytics/partials/styles.blade.php',
        '../../resources/views/trainer/training/proposal/partials/styles.blade.php',
        '../../resources/views/trainer/profile/partials/styles.blade.php',
    ]) {
        assert.doesNotMatch(read(path), /max-width:\s*1400px|width:\s*min\(100%,\s*1400px\)/);
    }
});

test('LMS and Simple workspace boundaries share the application gutter without flattening their structures', () => {
    const lmsHeader = read('../../resources/views/trainer/training/partials/workspace-header.blade.php');
    const simpleHeader = read('../../resources/views/trainer/simple/partials/header.blade.php');

    assert.match(lmsHeader, /lw-header-inner app-workspace-shell app-workspace-shell--header/);
    assert.match(simpleHeader, /sw-header-inner app-workspace-shell app-workspace-shell--header/);
    for (const path of workspacePages) {
        assert.match(read(path), /app-workspace-shell/);
    }
    assert.match(shellStyles, /\.app-workspace-shell\s*\{[^}]*padding-right:\s*var\(--app-page-gutter\)[^}]*padding-left:\s*var\(--app-page-gutter\)/s);
});
