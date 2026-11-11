import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';
import { initAdminAnalyticsTables, topEightRows, wrapChartLabel } from '../../resources/js/admin-analytics-charts.js';
import { initAnalyticsTables } from '../../resources/js/analytics-charts.js';
import { ANALYTICS_PAGE_SIZES } from '../../resources/js/analytics-table-pagination.js';

function paginationFixture(rowCount, noun, emptyState = false) {
    const listeners = new Map();
    const control = (name, value = '') => ({
        value,
        disabled: false,
        addEventListener(type, listener) {
            listeners.set(`${name}:${type}`, listener);
        },
        dispatch(type) {
            listeners.get(`${name}:${type}`)?.();
        },
    });
    const select = control('select', '10');
    const range = { textContent: '' };
    const previous = control('previous');
    const next = control('next');
    const pager = {
        className: '',
        innerHTML: '',
        querySelector(selector) {
            return {
                select,
                '.ana-page-range': range,
                '.ana-page-prev': previous,
                '.ana-page-next': next,
            }[selector];
        },
    };
    const container = {
        children: [],
        append(child) {
            this.children.push(child);
        },
    };
    const rows = Array.from({ length: rowCount }, () => ({
        hidden: false,
        querySelector: () => emptyState ? {} : null,
    }));
    const table = {
        dataset: { noun },
        tBodies: [{ rows }],
        closest: () => container,
    };
    const selectors = [];
    const root = {
        querySelectorAll(selector) {
            selectors.push(selector);
            return [table];
        },
    };

    return {
        root,
        table,
        rows,
        pager,
        container,
        selectors,
        select,
        range,
        previous,
        next,
        document: { createElement: () => pager },
    };
}

test('Admin Analytics inline behavior contains valid JavaScript', () => {
    const blade = readFileSync(new URL('../../resources/views/admin/analytics/partials/scripts.blade.php', import.meta.url), 'utf8');
    const script = blade.match(/<script>([\s\S]*?)<\/script>\s*$/)?.[1];

    assert.ok(script, 'Expected a final Admin Analytics inline script block.');
    assert.doesNotThrow(() => new vm.Script(script));
});

test('Admin Analytics keeps custom dates client-side until both dates are valid', () => {
    const source = readFileSync(new URL('../../resources/views/admin/analytics/partials/scripts.blade.php', import.meta.url), 'utf8');

    assert.match(source, /control\.value === 'custom'/);
    assert.match(source, /if \(!from \|\| !to \|\| to < from\)/);
    assert.match(source, /history\.pushState/);
    assert.match(source, /window\.addEventListener\('popstate'/);
});

test('Admin Analytics organization charts are capped at eight trainings', () => {
    const rows = Array.from({ length: 10 }, (_, index) => ({ title: `Training ${index + 1}`, rate: index + 1 }));
    const result = topEightRows(rows, 'rate');

    assert.equal(result.length, 8);
    assert.deepEqual(result.map((row) => row.rate), [10, 9, 8, 7, 6, 5, 4, 3]);
});

test('Admin Analytics wraps long chart labels without discarding training names', () => {
    const label = 'Advanced Inclusive Classroom Facilitation Programme';
    const lines = wrapChartLabel(label, 18);

    assert.ok(lines.length > 1);
    assert.ok(lines.every((line) => line.length <= 18));
    assert.equal(lines.join(' '), label);
});

test('Admin Analytics uses the approved Trainer analytics card and report language', () => {
    const organization = readFileSync(new URL('../../resources/views/admin/analytics/partials/organization-overview.blade.php', import.meta.url), 'utf8');
    const detail = readFileSync(new URL('../../resources/views/admin/analytics/partials/training-detail.blade.php', import.meta.url), 'utf8');
    const styles = readFileSync(new URL('../../resources/views/admin/analytics/partials/styles.blade.php', import.meta.url), 'utf8');

    for (const view of [organization, detail]) {
        assert.match(view, /data-admin-analytics-metric/);
        assert.match(view, /class="ana-metric-copy"/);
    }

    assert.match(styles, /\.admin-analytics-shell \.ana-metric\s*\{[^}]*flex-direction:\s*column;[^}]*padding:\s*17px 18px;[^}]*border-radius:\s*16px;/s);
    assert.match(styles, /\.admin-analytics-shell \.ana-metric-icon\s*\{[^}]*width:\s*40px;[^}]*height:\s*40px;[^}]*border-radius:\s*12px;[^}]*background:\s*var\(--ana-blue-soft\);/s);
    assert.match(styles, /\.admin-analytics-shell \.ana-metric:hover\s*\{[^}]*translateY\(-3px\);[^}]*border-color:\s*#bfdbfe;[^}]*box-shadow:\s*0 12px 24px rgba\(15, 23, 42, \.08\);/s);
    assert.match(styles, /\.admin-analytics-report-card\s*\{[^}]*border-radius:\s*18px;[^}]*box-shadow:/s);
    assert.doesNotMatch(styles, /\.admin-analytics-report-card:hover/);
});

test('Admin Analytics chart presentation is lighter, semantic, sharp, and responsive', () => {
    const organization = readFileSync(new URL('../../resources/views/admin/analytics/partials/organization-overview.blade.php', import.meta.url), 'utf8');
    const styles = readFileSync(new URL('../../resources/views/admin/analytics/partials/styles.blade.php', import.meta.url), 'utf8');
    const chartSource = readFileSync(new URL('../../resources/js/admin-analytics-charts.js', import.meta.url), 'utf8');

    assert.match(chartSource, /const attendanceColors = \['#16A34A', '#94A3B8'\]/);
    assert.match(chartSource, /const analyticsBlue = '#60A5FA'/);
    assert.match(chartSource, /const analyticsBlueHover = '#3B82F6'/);
    assert.equal(chartSource.match(/backgroundColor: analyticsBlue/g)?.length, 2);
    assert.match(chartSource, /function applyAdminDetailBlueTheme\(root\)/);
    assert.match(chartSource, /Chart\.getChart\(canvas\)/);
    assert.match(chartSource, /applyAdminDetailBlueTheme\(root\)/);
    assert.match(chartSource, /devicePixelRatio:\s*chartPixelRatio\(\)/);
    assert.match(chartSource, /ticks:\s*\{\s*autoSkip:\s*false,[^}]*font:\s*\{\s*size:\s*11\.5/s);
    assert.match(chartSource, /grid:\s*\{\s*color:\s*chartGrid,\s*drawTicks:\s*false\s*\}/);
    assert.match(chartSource, /backgroundColor:\s*'#0F172A'/);
    assert.equal(organization.match(/role="img"/g)?.length, 3);
    assert.match(styles, /\.ana-attendance-legend \.recorded\s*\{\s*color:\s*var\(--ana-green\);\s*\}/);
    assert.match(styles, /\.ana-attendance-legend \.not-recorded\s*\{\s*color:\s*var\(--ana-slate-400\);\s*\}/);
    assert.match(styles, /@media \(max-width:\s*991px\)[\s\S]*?\.admin-analytics-chart-grid\s*\{\s*grid-template-columns:\s*1fr;/);
});

test('Admin Analytics tables keep readable rows, safe long names, and existing pagination', () => {
    const organization = readFileSync(new URL('../../resources/views/admin/analytics/partials/organization-overview.blade.php', import.meta.url), 'utf8');
    const styles = readFileSync(new URL('../../resources/views/admin/analytics/partials/styles.blade.php', import.meta.url), 'utf8');

    assert.equal(organization.match(/class="ana-table admin-analytics-table" data-noun="trainings"/g)?.length, 2);
    assert.match(styles, /\.admin-analytics-table td\s*\{[^}]*padding:\s*14px 16px;[^}]*font-variant-numeric:\s*tabular-nums;/s);
    assert.match(styles, /\.admin-analytics-table :is\(th, td\):first-child\s*\{[^}]*overflow-wrap:\s*anywhere;[^}]*white-space:\s*normal;/s);
    assert.match(styles, /\.ana-table-wrap\s*\{[^}]*overflow-x:\s*auto;/s);
    assert.match(styles, /\.ana-pagination\s*\{[^}]*border-top:\s*1px solid/s);
    assert.doesNotMatch(organization, /Teacher Training/);
});

test('Admin Analytics responsive canvases use bounded dedicated wrappers', () => {
    const organization = readFileSync(new URL('../../resources/views/admin/analytics/partials/organization-overview.blade.php', import.meta.url), 'utf8');
    const trainingDetail = readFileSync(new URL('../../resources/views/admin/analytics/partials/training-detail.blade.php', import.meta.url), 'utf8');
    const sharedPanels = readFileSync(new URL('../../resources/views/trainer/training/analytics/partials/report-panels.blade.php', import.meta.url), 'utf8');
    const styles = readFileSync(new URL('../../resources/views/admin/analytics/partials/styles.blade.php', import.meta.url), 'utf8');

    for (const chartId of ['adminAttendanceDonutChart', 'adminAttendanceTrainingChart', 'adminQuizTrainingChart']) {
        assert.match(organization, new RegExp(`admin-chart-canvas-wrap[^>]*>\\s*<canvas id="${chartId}"`));
    }

    assert.match(trainingDetail, /\$isAdminAnalyticsReport = true/);
    for (const modifier of ['detail-donut', 'detail-attendance', 'detail-quiz']) {
        assert.match(sharedPanels, new RegExp(`admin-chart-canvas-wrap--${modifier}`));
    }

    assert.match(styles, /\.admin-chart-canvas-wrap\s*\{[^}]*position:\s*relative;[^}]*width:\s*100%;[^}]*height:\s*var\(--admin-chart-height\);[^}]*min-width:\s*0;[^}]*overflow:\s*hidden;/s);
    assert.match(styles, /\.admin-chart-canvas-wrap\s*>\s*canvas\s*\{[^}]*width:\s*100%\s*!important;[^}]*height:\s*100%\s*!important;/s);
    assert.doesNotMatch(styles, /\.admin-analytics-quiz-box\s*\{[^}]*height:\s*clamp\([^}]*vw/s);
});

test('Admin Analytics async refresh relies on one initializer-owned chart destruction', () => {
    const chartSource = readFileSync(new URL('../../resources/js/admin-analytics-charts.js', import.meta.url), 'utf8');
    const inlineSource = readFileSync(new URL('../../resources/views/admin/analytics/partials/scripts.blade.php', import.meta.url), 'utf8');

    assert.match(chartSource, /function initAdminAnalyticsCharts\([^)]*\)\s*\{\s*destroyAdminAnalyticsCharts\(\);/);
    assert.doesNotMatch(inlineSource, /destroyAdminAnalyticsCharts|destroyCharts/);
    assert.doesNotMatch(inlineSource, /setInterval|requestAnimationFrame/);
});

test('Admin organization and shared detail tables use the same pagination behavior', () => {
    assert.deepEqual(ANALYTICS_PAGE_SIZES, [10, 25, 50]);
    const originalDocument = globalThis.document;

    try {
        for (const [initializer, expectedSelector, noun] of [
            [initAdminAnalyticsTables, '.admin-analytics-table', 'trainings'],
            [initAnalyticsTables, '.ana-attendance-panel .ana-table, .ana-quiz-section .ana-table', 'attempts'],
        ]) {
            const fixture = paginationFixture(12, noun);
            globalThis.document = fixture.document;
            initializer(fixture.root);
            initializer(fixture.root);

            assert.equal(fixture.container.children.length, 1, 'Repeated initialization must not duplicate controls.');
            assert.equal(fixture.selectors[0], expectedSelector);
            assert.deepEqual([...fixture.pager.innerHTML.matchAll(/<option value="(\d+)">/g)].map((match) => Number(match[1])), [10, 25, 50]);
            assert.equal(fixture.rows.filter((row) => !row.hidden).length, 10);
            assert.equal(fixture.range.textContent, `Showing 1–10 of 12 ${noun}`);
            assert.equal(fixture.previous.disabled, true);
            assert.equal(fixture.next.disabled, false);

            fixture.next.dispatch('click');
            assert.equal(fixture.range.textContent, `Showing 11–12 of 12 ${noun}`);
            assert.equal(fixture.previous.disabled, false);
            assert.equal(fixture.next.disabled, true);

            fixture.select.value = '25';
            fixture.select.dispatch('change');
            assert.equal(fixture.range.textContent, `Showing 1–12 of 12 ${noun}`);
            assert.equal(fixture.previous.disabled, true);
            assert.equal(fixture.next.disabled, true);
        }

        for (const [rowCount, noun, initializer] of [
            [1, 'attempts', initAnalyticsTables],
            [8, 'sessions', initAnalyticsTables],
            [6, 'participants', initAnalyticsTables],
            [10, 'trainings', initAdminAnalyticsTables],
        ]) {
            const onePageFixture = paginationFixture(rowCount, noun);
            globalThis.document = onePageFixture.document;
            initializer(onePageFixture.root);
            initializer(onePageFixture.root);

            assert.equal(onePageFixture.container.children.length, 1, `${rowCount}-row tables must show one paginator.`);
            assert.equal(onePageFixture.range.textContent, `Showing 1–${rowCount} of ${rowCount} ${noun}`);
            assert.equal(onePageFixture.previous.disabled, true);
            assert.equal(onePageFixture.next.disabled, true);
        }

        const emptyFixture = paginationFixture(1, 'sessions', true);
        globalThis.document = emptyFixture.document;
        initAnalyticsTables(emptyFixture.root);
        initAnalyticsTables(emptyFixture.root);
        assert.equal(emptyFixture.container.children.length, 0, 'Empty-state-only tables must not show pagination controls.');
    } finally {
        globalThis.document = originalDocument;
    }
});

test('Admin and shared training-detail tables declare their pagination nouns', () => {
    const organization = readFileSync(new URL('../../resources/views/admin/analytics/partials/organization-overview.blade.php', import.meta.url), 'utf8');
    const attendanceResults = readFileSync(new URL('../../resources/views/trainer/training/analytics/partials/attendance-results.blade.php', import.meta.url), 'utf8');
    const sharedPanels = readFileSync(new URL('../../resources/views/trainer/training/analytics/partials/report-panels.blade.php', import.meta.url), 'utf8');
    const sharedStyles = readFileSync(new URL('../../resources/views/trainer/training/analytics/partials/styles.blade.php', import.meta.url), 'utf8');

    assert.equal(organization.match(/class="ana-table admin-analytics-table" data-noun="trainings"/g)?.length, 2);
    assert.match(attendanceResults, /class="ana-table" data-noun="sessions"/);
    assert.match(attendanceResults, /class="ana-table" data-noun="participants"/);
    assert.match(sharedPanels, /class="ana-table" data-noun="quizzes"/);
    assert.match(sharedPanels, /class="ana-table" data-noun="attempts"/);
    assert.match(sharedStyles, /\.ana-table-wrap\s*\{[^}]*overflow-x:\s*auto;/s);
});

test('Participant attendance badges use the approved status palette', () => {
    const attendanceResults = readFileSync(new URL('../../resources/views/trainer/training/analytics/partials/attendance-results.blade.php', import.meta.url), 'utf8');
    const styles = readFileSync(new URL('../../resources/views/trainer/training/analytics/partials/styles.blade.php', import.meta.url), 'utf8');

    assert.match(attendanceResults, /Participant Attendance/);
    assert.match(attendanceResults, /No participants are enrolled in this training\./);
    assert.match(attendanceResults, /ana-attendance-badge/);
    assert.match(styles, /\.ana-attendance-badge\.recorded\{[^}]*rgba\(22,163,74,/s);
    assert.match(styles, /\.ana-attendance-badge\.not-recorded\{[^}]*rgba\(148,163,184,/s);
});

test('Trainer and Admin quiz summaries share the approved metric-card treatment', () => {
    const quizSummary = readFileSync(new URL('../../resources/views/trainer/training/analytics/partials/quiz-summary.blade.php', import.meta.url), 'utf8');
    const reportPanels = readFileSync(new URL('../../resources/views/trainer/training/analytics/partials/report-panels.blade.php', import.meta.url), 'utf8');
    const adminDetail = readFileSync(new URL('../../resources/views/admin/analytics/partials/training-detail.blade.php', import.meta.url), 'utf8');
    const styles = readFileSync(new URL('../../resources/views/trainer/training/analytics/partials/styles.blade.php', import.meta.url), 'utf8');

    assert.match(quizSummary, /class="ana-metric ana-quiz-metric"/);
    assert.equal(reportPanels.match(/analytics\.partials\.quiz-summary/g)?.length, 2);
    assert.match(adminDetail, /analytics\.partials\.report-panels/);
    assert.match(styles, /\.ana-metric:hover\s*\{[^}]*transform:\s*translateY\(-3px\);[^}]*box-shadow:/s);
    assert.match(styles, /\.ana-quiz-summary \.ana-quiz-metric\{[^}]*min-height:[^}]*padding:/s);
});
