import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const component = read('../../resources/views/components/app-toasts.blade.php');
const styles = read('../../resources/views/components/app-toast-styles.blade.php');
const scripts = read('../../resources/views/components/app-toast-scripts.blade.php');
const trainerLayout = read('../../resources/views/trainer/layouts/app.blade.php');
const adminLayout = read('../../resources/views/admin/layouts/app.blade.php');
const materialScripts = read('../../resources/views/trainer/training/partials/material-authoring-scripts.blade.php');
const learnerTracking = read('../../resources/js/learner-tracking.js');

const migratedActionViews = [
    '../../resources/views/trainer/profile/partials/content.blade.php',
    '../../resources/views/trainer/training/submission.blade.php',
    '../../resources/views/trainer/teaching-plan/partials/card.blade.php',
    '../../resources/views/trainer/training/proposal/partials/content.blade.php',
    '../../resources/views/trainer/simple/overview/partials/content.blade.php',
    '../../resources/views/trainer/simple/materials/partials/content.blade.php',
    '../../resources/views/trainer/simple/attendance/partials/content.blade.php',
    '../../resources/views/admin/proposals/partials/content.blade.php',
    '../../resources/views/admin/courses/partials/content.blade.php',
    '../../resources/views/admin/profile/partials/content.blade.php',
].map(read);

test('Trainer and Admin layouts load one shared toast component and behavior', () => {
    for (const layout of [trainerLayout, adminLayout]) {
        assert.match(layout, /@include\('components\.app-toast-styles'\)/);
        assert.match(layout, /@include\('components\.app-toasts'\)/);
        assert.match(layout, /@include\('components\.app-toast-scripts'\)/);
    }

    assert.match(component, /session\('success'\)/);
    assert.match(component, /session\('error'\)/);
    assert.match(component, /session\('status'\)/);
    assert.match(component, /@if \(\$appToasts->isNotEmpty\(\)\)/);
    assert.doesNotMatch(component, /\bid=/);
});

test('shared toast semantics distinguish success and error without relying on colour', () => {
    assert.match(component, /role="\{\{ \$toast\['type'\] === 'error' \? 'alert' : 'status' \}\}"/);
    assert.match(component, /bi-exclamation-triangle-fill/);
    assert.match(component, /bi-check-circle-fill/);
    assert.match(component, /aria-hidden="true"/);
    assert.match(component, /aria-live="polite"/);
    assert.match(component, /aria-atomic="false"/);
    assert.match(styles, /\.app-toast\.success/);
    assert.match(styles, /\.app-toast\.error/);
});

test('shared toast preserves the Materials position, motion, timing, and responsive width', () => {
    assert.match(styles, /\.app-toast-region\s*\{[\s\S]*?position:\s*fixed;[\s\S]*?top:\s*24px;[\s\S]*?right:\s*24px;[\s\S]*?z-index:\s*9999/);
    assert.match(styles, /border-radius:\s*11px/);
    assert.match(styles, /box-shadow:\s*0 12px 30px rgba\(15, 23, 42, 0\.16\)/);
    assert.match(styles, /min-width:\s*280px/);
    assert.match(styles, /max-width:\s*min\(380px, calc\(100vw - 48px\)\)/);
    assert.match(styles, /animation:\s*appToastIn 0\.25s ease-out forwards/);
    assert.match(styles, /\.app-toast\.is-leaving[^}]*transition:\s*opacity 0\.3s ease, transform 0\.3s ease/);
    assert.match(styles, /@media \(max-width:\s*575\.98px\)[\s\S]*?left:\s*16px;[\s\S]*?width:\s*auto/);
    assert.match(styles, /@media \(prefers-reduced-motion:\s*reduce\)/);
    assert.match(scripts, /var displayDuration = 3500/);
    assert.match(scripts, /var exitDuration = 320/);
});

test('shared JavaScript helper stacks and independently removes safe async toasts', () => {
    const script = scripts.match(/<script>([\s\S]*?)<\/script>/)?.[1];

    assert.ok(script);
    assert.doesNotThrow(() => new vm.Script(script));
    assert.match(scripts, /window\.showAppToast = function \(type, message\)/);
    assert.match(scripts, /toastRegion\(\)\.appendChild\(toast\)/);
    assert.match(scripts, /toastTimers = new WeakMap\(\)/);
    assert.match(scripts, /copy\.textContent = normalizedMessage/);
    assert.match(scripts, /scheduleToast\(toast\)/);
    assert.match(scripts, /toast\.remove\(\)/);
    assert.match(scripts, /removeEmptyRegion\(region\)/);
    assert.match(scripts, /document\.querySelectorAll\('\[data-app-toast\]'\)/);
});

test('action pages no longer render their own persistent global flash banners', () => {
    for (const view of migratedActionViews) {
        assert.doesNotMatch(view, /session\('(success|error|status)'\)/);
        assert.doesNotMatch(view, /class="(?:pf-alert|pp-alert|sw-flash|tp-flash|lw-flash)[^"]*"/);
    }
});

test('Materials and learner-tracking async failures use the shared toast implementation', () => {
    assert.match(materialScripts, /window\.showAppToast\(type, message\)/);
    assert.match(learnerTracking, /win\.showAppToast\?\.\('error', message\)/);
    assert.doesNotMatch(learnerTracking, /className = 'lt-toast'/);
});

test('contextual field and form validation remains inline', () => {
    assert.match(read('../../resources/views/trainer/profile/partials/certificates-panel.blade.php'), /@error\('certificate'\)/);
    assert.match(read('../../resources/views/trainer/teaching-plan/partials/card.blade.php'), /@error\('teaching_plan'\)/);
    assert.match(read('../../resources/views/trainer/training/proposal/partials/form.blade.php'), /@error\('proposed_title'\)/);
    assert.match(read('../../resources/views/admin/activity/partials/content.blade.php'), /\$errors->any\(\)/);
    assert.match(read('../../resources/views/admin/proposals/partials/content.blade.php'), /\$errors->first\(\)/);
});
