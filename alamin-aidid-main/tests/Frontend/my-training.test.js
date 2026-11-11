import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const view = readFileSync(
    new URL('../../resources/views/trainer/training/my-training.blade.php', import.meta.url),
    'utf8',
);

function control(value = '') {
    const listeners = {};

    return {
        value,
        disabled: false,
        hidden: false,
        style: {},
        textContent: '',
        addEventListener(type, callback) {
            listeners[type] = callback;
        },
        trigger(type) {
            listeners[type]?.();
        },
    };
}

function item(index, overrides = {}) {
    const classes = new Set();

    return {
        dataset: {
            name: `training ${index} ${index <= 5 ? 'teacher training' : 'public training'}`,
            category: index <= 5 ? 'teacher training' : 'public training',
            mode: index % 2 === 0 ? 'lms' : 'standard',
            status: index === 6 ? 'completed' : 'active',
            ...overrides,
        },
        classList: {
            add(name) { classes.add(name); },
            remove(name) { classes.delete(name); },
            contains(name) { return classes.has(name); },
        },
    };
}

function fixture(size) {
    const items = Array.from({ length: size }, (_, index) => item(index + 1));
    const nodes = {
        courseSearch: control(),
        categoryFilter: control(),
        modeFilter: control(),
        statusFilter: control(),
        noResults: control(),
        workspaceCount: control(),
        workspacePager: control(),
        workspacePageRange: control(),
        workspacePageControls: control(),
        workspaceCurrentPage: control(),
        workspacePrevious: control(),
        workspaceNext: control(),
    };
    let ready;
    const document = {
        addEventListener(type, callback) {
            if (type === 'DOMContentLoaded') ready = callback;
        },
        getElementById(id) { return nodes[id] ?? null; },
        querySelectorAll(selector) { return selector === '.course-item' ? items : []; },
    };
    const script = view.match(/<script>([\s\S]*?)<\/script>/)?.[1];

    assert.ok(script, 'Expected the My Training script block.');
    new vm.Script(script).runInNewContext({ document });
    ready();

    return {
        items,
        nodes,
        visibleItems: () => items.filter((row) => !row.classList.contains('hidden-by-filter')),
    };
}

test('My Training keeps functional category/status filters while removing decorative category and status chips', () => {
    const rowBody = view.slice(view.indexOf('<div class="trn-body">'), view.indexOf('<div class="trn-side">'));

    assert.match(view, /id="categoryFilter"[\s\S]*?\$courses->pluck\('course_category'\)/);
    assert.match(view, /data-category="\{\{ strtolower\(\$course->course_category/);
    assert.match(view, /id="statusFilter"[\s\S]*?<option value="active">Active<\/option>/);
    assert.match(view, /data-status="\{\{ strtolower\(\$course->status/);
    assert.match(rowBody, /trn-mode/);
    assert.match(rowBody, /LMS/);
    assert.match(rowBody, /Simple/);
    assert.doesNotMatch(rowBody, /course_category|trn-status|bi-tag/);
});

test('My Training uses fixed five-item paging with no rows-per-page selector', () => {
    assert.match(view, /const pageSize = 5;/);
    assert.match(view, /aria-label="Training pagination"/);
    assert.match(view, /Previous training page/);
    assert.match(view, /Next training page/);
    assert.match(view, /\.trn-page-btn:focus-visible/);
    assert.match(view, /\.trn-page-controls\[hidden\]\s*\{\s*display:\s*none;/);
    assert.match(view, /@media \(max-width: 479\.98px\)[\s\S]*?grid-template-columns: 1fr auto 1fr/);
    assert.doesNotMatch(view, /Rows per page|trn-rows-control|data-training-rows/);

    const five = fixture(5);
    assert.equal(five.nodes.workspacePager.hidden, false);
    assert.equal(five.nodes.workspacePageControls.hidden, true);
    assert.equal(five.visibleItems().length, 5);
    assert.equal(five.nodes.workspaceCount.textContent, '5 training(s)');
    assert.equal(five.nodes.workspacePageRange.textContent, 'Showing 1–5 of 5 trainings · Page 1 of 1');

    const twelve = fixture(12);
    assert.equal(twelve.nodes.workspacePager.hidden, false);
    assert.equal(twelve.nodes.workspacePageControls.hidden, false);
    assert.equal(twelve.visibleItems().length, 5);
    assert.equal(twelve.nodes.workspacePageRange.textContent, 'Showing 1–5 of 12 trainings · Page 1 of 3');
    assert.equal(twelve.nodes.workspaceCurrentPage.textContent, '1 / 3');
    assert.equal(twelve.nodes.workspacePrevious.disabled, true);
    assert.equal(twelve.nodes.workspaceNext.disabled, false);

    twelve.nodes.workspaceNext.trigger('click');
    assert.equal(twelve.visibleItems().length, 5);
    assert.equal(twelve.visibleItems()[0], twelve.items[5]);
    assert.equal(twelve.nodes.workspacePageRange.textContent, 'Showing 6–10 of 12 trainings · Page 2 of 3');
    assert.equal(twelve.nodes.workspaceCurrentPage.textContent, '2 / 3');
    assert.equal(twelve.nodes.workspacePrevious.disabled, false);
    assert.equal(twelve.nodes.workspaceNext.disabled, false);
});

test('My Training filters page the matching result and reset safely to page one', () => {
    const page = fixture(6);

    page.nodes.workspaceNext.trigger('click');
    page.nodes.categoryFilter.value = 'teacher training';
    page.nodes.categoryFilter.trigger('change');
    assert.equal(page.visibleItems().length, 5);
    assert.equal(page.nodes.workspacePager.hidden, false);
    assert.equal(page.nodes.workspacePageControls.hidden, true);
    assert.equal(page.nodes.workspaceCurrentPage.textContent, '1 / 1');
    assert.equal(page.nodes.workspaceCount.textContent, '5 training(s)');
    assert.equal(page.nodes.workspacePageRange.textContent, 'Showing 1–5 of 5 trainings · Page 1 of 1');

    page.nodes.courseSearch.value = 'training 2';
    page.nodes.courseSearch.trigger('input');
    assert.equal(page.visibleItems().length, 1);
    assert.equal(page.nodes.workspacePager.hidden, false);
    assert.equal(page.nodes.workspacePageControls.hidden, true);
    assert.equal(page.nodes.workspaceCurrentPage.textContent, '1 / 1');
    assert.equal(page.nodes.workspacePageRange.textContent, 'Showing 1–1 of 1 trainings · Page 1 of 1');

    page.nodes.courseSearch.value = 'no matching training';
    page.nodes.courseSearch.trigger('input');
    assert.equal(page.visibleItems().length, 0);
    assert.equal(page.nodes.noResults.style.display, '');
    assert.equal(page.nodes.workspacePager.hidden, true);
    assert.equal(page.nodes.workspacePageControls.hidden, true);
    assert.equal(page.nodes.workspaceCount.textContent, '0 training(s)');
    assert.equal(page.nodes.workspacePageRange.textContent, '');

    const statusPage = fixture(6);
    statusPage.nodes.statusFilter.value = 'completed';
    statusPage.nodes.statusFilter.trigger('change');
    assert.equal(statusPage.visibleItems().length, 1);
    assert.equal(statusPage.visibleItems()[0], statusPage.items[5]);

    const modePage = fixture(6);
    modePage.nodes.modeFilter.value = 'lms';
    modePage.nodes.modeFilter.trigger('change');
    assert.equal(modePage.visibleItems().length, 3);
    assert.equal(modePage.nodes.workspacePager.hidden, false);
    assert.equal(modePage.nodes.workspacePageControls.hidden, true);
    assert.equal(modePage.nodes.workspacePageRange.textContent, 'Showing 1–3 of 3 trainings · Page 1 of 1');
});
