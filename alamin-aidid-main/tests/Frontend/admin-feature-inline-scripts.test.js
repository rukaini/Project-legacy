import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const scripts = [
    '../../resources/views/admin/proposals/partials/scripts.blade.php',
    '../../resources/views/admin/activity/partials/scripts.blade.php',
    '../../resources/views/admin/courses/partials/scripts.blade.php',
    '../../resources/views/admin/directory/partials/scripts.blade.php',
];

const adminLayout = readFileSync(
    new URL('../../resources/views/admin/layouts/app.blade.php', import.meta.url),
    'utf8',
);
const adminNavbar = readFileSync(
    new URL('../../resources/views/admin/partials/navbar.blade.php', import.meta.url),
    'utf8',
);
const adminSystemStyles = readFileSync(
    new URL('../../resources/views/admin/partials/system-styles.blade.php', import.meta.url),
    'utf8',
);
const appPageShellStyles = readFileSync(
    new URL('../../resources/views/components/app-page-shell-styles.blade.php', import.meta.url),
    'utf8',
);
const adminProposalContent = readFileSync(
    new URL('../../resources/views/admin/proposals/partials/content.blade.php', import.meta.url),
    'utf8',
);
const adminProposalStyles = readFileSync(
    new URL('../../resources/views/admin/proposals/partials/styles.blade.php', import.meta.url),
    'utf8',
);
const adminProposalScripts = readFileSync(
    new URL('../../resources/views/admin/proposals/partials/scripts.blade.php', import.meta.url),
    'utf8',
);
const adminActivityContent = readFileSync(
    new URL('../../resources/views/admin/activity/partials/content.blade.php', import.meta.url),
    'utf8',
);
const adminActivityStyles = readFileSync(
    new URL('../../resources/views/admin/activity/partials/styles.blade.php', import.meta.url),
    'utf8',
);
const adminActivityScripts = readFileSync(
    new URL('../../resources/views/admin/activity/partials/scripts.blade.php', import.meta.url),
    'utf8',
);
const adminTrainingContent = readFileSync(
    new URL('../../resources/views/admin/courses/partials/content.blade.php', import.meta.url),
    'utf8',
);
const adminTrainingStyles = readFileSync(
    new URL('../../resources/views/admin/courses/partials/styles.blade.php', import.meta.url),
    'utf8',
);
const adminTrainingScripts = readFileSync(
    new URL('../../resources/views/admin/courses/partials/scripts.blade.php', import.meta.url),
    'utf8',
);
const trainerAttendanceStyles = readFileSync(
    new URL('../../resources/views/trainer/training/attendance/partials/styles.blade.php', import.meta.url),
    'utf8',
);
const adminDirectoryContent = readFileSync(
    new URL('../../resources/views/admin/directory/partials/content.blade.php', import.meta.url),
    'utf8',
);
const adminDirectoryStyles = readFileSync(
    new URL('../../resources/views/admin/directory/partials/styles.blade.php', import.meta.url),
    'utf8',
);
const adminDirectoryScripts = readFileSync(
    new URL('../../resources/views/admin/directory/partials/scripts.blade.php', import.meta.url),
    'utf8',
);
const adminProfileView = readFileSync(
    new URL('../../resources/views/admin/profile.blade.php', import.meta.url),
    'utf8',
);
const adminProfileContent = readFileSync(
    new URL('../../resources/views/admin/profile/partials/content.blade.php', import.meta.url),
    'utf8',
);
const adminProfileStyles = readFileSync(
    new URL('../../resources/views/admin/profile/partials/styles.blade.php', import.meta.url),
    'utf8',
);

const adminPageShells = new Map([
    ['dashboard', '../../resources/views/admin/dashboard.blade.php'],
    ['proposals', '../../resources/views/admin/proposals/partials/content.blade.php'],
    ['activity', '../../resources/views/admin/activity/partials/content.blade.php'],
    ['trainings', '../../resources/views/admin/courses/partials/content.blade.php'],
    ['analytics', '../../resources/views/admin/analytics/partials/content.blade.php'],
    ['directory', '../../resources/views/admin/directory/partials/content.blade.php'],
    ['profile', '../../resources/views/admin/profile/partials/content.blade.php'],
]);

function cssRule(styles, selector) {
    const start = styles.indexOf(`${selector} {`);
    assert.notEqual(start, -1, `Expected CSS rule for ${selector}.`);
    return styles.slice(start, styles.indexOf('}', start) + 1);
}

function cssDeclaration(rule, property) {
    const value = rule.match(new RegExp(`${property}:\\s*([^;]+)`))?.[1]?.trim();
    assert.ok(value, `Expected ${property} declaration.`);
    return value;
}

for (const path of scripts) {
    test(`${path} contains valid JavaScript`, () => {
        const blade = readFileSync(new URL(path, import.meta.url), 'utf8');
        const script = blade.match(/<script>([\s\S]*?)<\/script>/)?.[1];

        assert.ok(script, `Expected a script block in ${path}.`);
        assert.doesNotThrow(() => new vm.Script(script));
    });
}

test('Admin layout loads its shared design system after page-specific styles', () => {
    const pageStylePosition = adminLayout.indexOf("@stack('styles')");
    const systemStylePosition = adminLayout.indexOf("@include('admin.partials.system-styles')");
    const headerStylePosition = adminLayout.indexOf("@include('components.app-signed-in-header-styles')");
    const pageShellPosition = adminLayout.indexOf("@include('components.app-page-shell-styles')");

    assert.ok(pageStylePosition >= 0);
    assert.ok(systemStylePosition > pageStylePosition);
    assert.ok(headerStylePosition > systemStylePosition);
    assert.ok(pageShellPosition > headerStylePosition);
    assert.match(adminNavbar, /class="admin-topbar-shell app-header-shell container-fluid"/);
});

test('every reachable Admin workspace opts into the responsive page-shell contract', () => {
    for (const [page, path] of adminPageShells) {
        const view = readFileSync(new URL(path, import.meta.url), 'utf8');

        assert.match(view, /<main class="[^"]*adm-wrap[^"]*"[^>]*data-admin-page-shell/);
        assert.match(view, /adm-wrap--wide/);
        assert.match(view, /app-page-shell/);
        assert.match(view, /data-admin-width="wide"/);
        assert.match(view, /adm-head-icon[^>]*><i[^>]*aria-hidden="true"/);
    }
});

test('Admin dashboard keeps a structured empty schedule state', () => {
    const dashboard = readFileSync(
        new URL('../../resources/views/admin/dashboard.blade.php', import.meta.url),
        'utf8',
    );

    assert.match(dashboard, /The schedule is clear/);
    assert.match(dashboard, /No training sessions are scheduled/);
    assert.match(dashboard, /class="adm-empty"/);
});

test('Admin dashboard replaces Attendance KPI with Teaching Plans review awareness', () => {
    const dashboard = readFileSync(
        new URL('../../resources/views/admin/dashboard.blade.php', import.meta.url),
        'utf8',
    );
    const labels = [...dashboard.matchAll(/class="adm-kpi-label">([^<]+)/g)]
        .map((match) => match[1]);

    assert.deepEqual(labels, [
        'Trainers',
        'Trainings',
        'Participants',
        'Pending proposals',
        'Teaching Plans',
    ]);
    assert.match(dashboard, /bi-file-earmark-text/);
    assert.match(dashboard, /\$teachingPlansNeedReview/);
    assert.match(dashboard, /'need review'\s*:\s*'all reviewed'/);
    assert.doesNotMatch(dashboard, /class="adm-kpi-label">Attendance/);
    assert.doesNotMatch(dashboard, /\$attendanceRate/);
});

test('Admin page shells use the canonical fluid wide-screen sizing without zoom hacks', () => {
    assert.match(appPageShellStyles, /--app-page-max-width:\s*1880px/);
    assert.match(appPageShellStyles, /width:\s*min\(100%,\s*var\(--app-page-max-width\)\)/);
    assert.match(appPageShellStyles, /padding:\s*var\(--app-page-top-space\) var\(--app-page-gutter\) var\(--app-page-bottom-space\)/);
    assert.doesNotMatch(adminSystemStyles, /--adm-page-max|adm-wrap--standard|adm-wrap--compact/);
    assert.match(adminSystemStyles, /repeat\(auto-fit,\s*minmax\(min\(100%,\s*210px\),\s*1fr\)\)/);
    assert.doesNotMatch(adminSystemStyles + appPageShellStyles, /(^|[;{]\s*)zoom\s*:/m);
});

test('Admin shared design system covers controls, tables, empty states, focus, and reduced motion', () => {
    assert.match(adminSystemStyles, /Shared controls and button hierarchy/);
    assert.match(adminSystemStyles, /\.adm-btn-ghost/);
    assert.match(adminSystemStyles, /\.adm-btn-reject/);
    assert.match(adminSystemStyles, /\.adm-table-wrap/);
    assert.match(adminSystemStyles, /main\.adm-wrap \.adm-empty/);
    assert.match(adminSystemStyles, /:focus-visible/);
    assert.match(adminSystemStyles, /prefers-reduced-motion:\s*reduce/);
    assert.match(adminSystemStyles, /overflow-x:\s*auto/);
    assert.match(adminSystemStyles, /@media \(max-width:\s*767\.98px\)/);
});

test('Admin Proposals follows the approved proposal list and control contract', () => {
    assert.match(adminProposalContent, /class="adm-proposals-panel"/);
    assert.match(adminProposalContent, /id="admProposalList" class="adm-proposal-list"/);
    assert.match(adminProposalContent, /class="adm-prop-card"/);
    assert.match(adminProposalStyles, /\.adm-prop-card:hover/);
    assert.match(adminProposalStyles, /\.adm-prop-card\.details-expanded/);
    assert.match(adminProposalContent, /data-admin-proposal-search-clear/);
    assert.match(adminProposalContent, /aria-pressed=/);
    assert.match(adminProposalContent, /data-admin-proposal-rows/);
    assert.match(adminProposalContent, /data-admin-proposal-paginator/);
    assert.match(adminProposalContent, /class="adm-badge \{\{ \$statusKey \}\}"/);
    assert.match(adminProposalContent, /class="modal fade adm-review-modal"/);
});

test('Admin Proposal interactions keep controls and review details accessible', () => {
    assert.match(adminProposalScripts, /setAttribute\('aria-pressed', 'true'\)/);
    assert.match(adminProposalScripts, /setAttribute\('aria-expanded', open \? 'true' : 'false'\)/);
    assert.match(adminProposalScripts, /classList\.toggle\('details-expanded', open\)/);
    assert.match(adminProposalScripts, /searchInput\.value = ''/);
    assert.match(adminProposalScripts, /rowsSelect\.addEventListener\('change'/);
    assert.match(adminProposalScripts, /prefers-reduced-motion: reduce/);
    assert.match(adminProposalScripts, /approvalForm\.action = btn\.dataset\.url/);
    assert.match(adminProposalScripts, /form\.action = btn\.dataset\.url/);
});

test('Admin Activity uses the shared control, feed, and pagination language', () => {
    assert.match(adminActivityContent, /class="act-panel"/);
    assert.match(adminActivityContent, /id="admTimeline"[\s\S]*?class="act-timeline"/);
    assert.match(adminActivityContent, /class="act-row"/);
    assert.match(adminActivityContent, /class="act-type-badge \{\{ \$actionTone \}\}"/);
    assert.match(adminActivityContent, /data-admin-activity-search-clear/);
    assert.match(adminActivityContent, /aria-pressed=/);
    assert.match(adminActivityContent, /data-admin-activity-rows/);
    assert.match(adminActivityContent, /data-admin-activity-paginator/);
    assert.match(adminActivityContent, /No activity recorded yet/);
    assert.match(adminActivityContent, /No activity matches these controls/);
    assert.match(adminActivityStyles, /\.act-row:hover/);
    assert.match(adminActivityStyles, /@media \(max-width: 767\.98px\)/);
    assert.match(adminActivityStyles, /\.act-stats \.adm-stat:hover[\s\S]*?translateY\(-3px\)[\s\S]*?0 12px 24px/);
    assert.match(adminActivityContent, /TrainerActivityLogger::meaningfulActionCodes/);
    assert.doesNotMatch(adminActivityContent, /'training\.open'\s*=>/);
});

test('Admin Activity keeps filtering, grouping, sorting, and paging client-side and accessible', () => {
    assert.match(adminActivityScripts, /function getMatches\(\)/);
    assert.match(adminActivityScripts, /function createDayGroup\(dateValue\)/);
    assert.match(adminActivityScripts, /sortSelect\.addEventListener\('change'/);
    assert.match(adminActivityScripts, /rowsSelect\.addEventListener\('change'/);
    assert.match(adminActivityScripts, /setAttribute\('aria-pressed', 'true'\)/);
    assert.match(adminActivityScripts, /searchInput\.value = ''/);
    assert.match(adminActivityScripts, /prefers-reduced-motion: reduce/);
    assert.doesNotMatch(adminActivityScripts, /fetch\(|XMLHttpRequest|window\.location/);
});

test('Admin Activity supports an inclusive custom period with clear query and reset behavior', () => {
    assert.match(adminActivityContent, /data-activity-custom-period/);
    assert.match(adminActivityContent, /name="from"/);
    assert.match(adminActivityContent, /name="to"/);
    assert.match(adminActivityContent, /data-initial-period/);
    assert.match(adminActivityContent, /Recorded actions/);
    assert.match(adminActivityContent, /occurrences/);
    assert.match(adminActivityScripts, /function inSelectedRange\(timestamp, dayValue\)/);
    assert.match(adminActivityScripts, /dayValue >= customFrom && dayValue <= customTo/);
    assert.match(adminActivityScripts, /setCustomValidity/);
    assert.match(adminActivityScripts, /function syncQueryState\(\)/);
    assert.match(adminActivityScripts, /customPeriodForm\.appendChild\(hidden\)/);
    assert.match(adminActivityScripts, /resetFrom\.value = ''/);
    assert.match(adminActivityScripts, /resetTo\.value = ''/);
});

test('Admin Training Monitoring exposes a searchable master-detail workspace', () => {
    const masterHeader = adminTrainingContent.slice(
        adminTrainingContent.indexOf('data-training-master-expanded'),
        adminTrainingContent.indexOf('class="tm-master-controls"'),
    );
    const collapsedRail = adminTrainingContent.slice(
        adminTrainingContent.indexOf('data-training-master-collapsed'),
        adminTrainingContent.indexOf('</aside>'),
    );

    assert.match(adminTrainingContent, /data-training-master/);
    assert.match(adminTrainingContent, /data-training-search/);
    assert.match(adminTrainingContent, /data-training-schedule-filter/);
    assert.doesNotMatch(adminTrainingContent, /data-training-status-filter/);
    assert.match(adminTrainingContent, /data-training-mode-filter/);
    assert.match(adminTrainingContent, /data-training-trainer-filter/);
    assert.doesNotMatch(adminTrainingContent, /data-training-sort|Most recent schedule|Sort By/);
    assert.match(adminTrainingContent, /data-training-filter-toggle/);
    assert.match(adminTrainingContent, /data-training-filter-panel/);
    assert.match(adminTrainingContent, /data-training-filter-count/);
    assert.match(adminTrainingContent, /data-training-filter-panel aria-hidden="true" inert/);
    assert.match(adminTrainingContent, /data-training-detail/);
    assert.match(adminTrainingContent, /data-search="\{\{ \$training->course_name \}\} \{\{ \$training->course_category/);
    assert.doesNotMatch(adminTrainingContent, /<small>\{\{ \$training->course_category/);
    assert.doesNotMatch(adminTrainingScripts, /<p>' \+ esc\(training\.category/);
    assert.doesNotMatch(adminTrainingScripts, /<dt>Category<\/dt>|training\.category/);
    assert.match(adminTrainingScripts, /function selectTraining\(id, updateUrl\)/);
    assert.match(adminTrainingScripts, /history\.replaceState/);
    assert.match(adminTrainingContent, /data-training-master-toggle/);
    assert.doesNotMatch(masterHeader, /tm-panel-mark|bi-list-ul/);
    assert.match(masterHeader, /aria-label="Collapse training list"[\s\S]*?bi-layout-sidebar-inset/);
    assert.match(collapsedRail, /aria-label="Expand training list"[\s\S]*?bi-list-ul/);
    assert.doesNotMatch(collapsedRail, />\s*Trainings\s*<\/span>/i);
    assert.match(adminTrainingScripts, /function setMasterCollapsed\(collapsed, moveFocus\)/);
    assert.match(adminTrainingScripts, /isExpandControl \? 'Expand training list' : 'Collapse training list'/);
    assert.match(adminTrainingScripts, /masterCollapsePreference/);
    assert.match(adminTrainingStyles, /\.tm-workspace\.is-master-collapsed/);
    assert.match(adminTrainingStyles, /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(adminTrainingStyles, /\.tm-workspace\s*\{[^}]*align-items:\s*start/);
    assert.match(adminTrainingStyles, /\.tm-master\s*\{[\s\S]*?position:\s*sticky;[\s\S]*?align-self:\s*start;[\s\S]*?height:\s*max-content;[\s\S]*?max-height:\s*calc\(100vh - 104px\)/);
    assert.match(adminTrainingStyles, /\.tm-training-list[\s\S]*?overflow-y:\s*auto/);
    assert.match(adminTrainingStyles, /\.tm-master\.is-collapsed\s*\{[\s\S]*?overflow:\s*hidden;[\s\S]*?border-color:\s*transparent;[\s\S]*?background:\s*transparent;[\s\S]*?box-shadow:\s*none/);
    assert.match(adminTrainingStyles, /\.tm-master-collapsed\s*\{[\s\S]*?background:\s*transparent/);
    assert.match(adminTrainingStyles, /\.tm-master-toggle\s*\{[\s\S]*?width:\s*32px;[\s\S]*?height:\s*32px;[\s\S]*?border-radius:\s*7px/);
    assert.match(adminTrainingStyles, /\.tm-master-expand\s*\{[\s\S]*?width:\s*44px;[\s\S]*?height:\s*42px;[\s\S]*?border-radius:\s*10px/);
    assert.match(adminTrainingStyles, /\.tm-master-filter-panel\s*\{[\s\S]*?grid-template-rows:\s*0fr;[\s\S]*?opacity:\s*0/);
    assert.match(adminTrainingStyles, /\.tm-master-filter-panel\.is-open\s*\{\s*grid-template-rows:\s*1fr;\s*opacity:\s*1/);
    assert.match(adminTrainingScripts, /filterPanel\.toggleAttribute\('inert', !filterPanelOpen\)/);
    assert.doesNotMatch(adminTrainingStyles, /\.tm-tab-panel\s*\{[^}]*overflow-y:\s*auto/s);
});

test('Admin Training includes trainer-first Teaching Plans review and feedback without approval controls', () => {
    const teachingPlanStart = adminTrainingScripts.indexOf('function teachingPlanFileActions(plan)');
    const teachingPlanEnd = adminTrainingScripts.indexOf('function renderActiveTab()');
    const teachingPlanUi = adminTrainingScripts.slice(teachingPlanStart, teachingPlanEnd);

    assert.ok(teachingPlanStart >= 0 && teachingPlanEnd > teachingPlanStart);
    assert.match(adminTrainingScripts, /\['teaching-plans', 'bi-file-earmark-text', 'Teaching Plans'\]/);
    assert.match(adminTrainingScripts, /tm-tab-count/);
    assert.match(adminTrainingScripts, /teachingPlanAttention > 0/);
    assert.match(adminTrainingScripts, /teachingPlanAttentionLabel/);
    assert.match(adminTrainingScripts, /aria-label="' \+ teachingPlanAttentionLabel/);
    assert.match(adminTrainingContent, /tm-training-plan-alert/);
    assert.match(adminTrainingContent, /Teaching Plans requiring Admin review/);
    assert.match(adminTrainingStyles, /\.tm-training-plan-alert/);
    assert.doesNotMatch(teachingPlanUi, /tm-plan-summary/);
    assert.match(teachingPlanUi, /function preferredTeachingPlanTrainer\(training\)/);
    assert.match(teachingPlanUi, /teachingPlanAdminState\(trainer\)\.key === 'needs-review'/);
    assert.match(teachingPlanUi, /String\(trainer\.id\) === String\(selectedTeachingPlanTrainerId\)/);
    assert.match(teachingPlanUi, /training\.trainers\.map/);
    assert.match(teachingPlanUi, /Not Submitted/);
    assert.match(teachingPlanUi, /Needs Review/);
    assert.match(teachingPlanUi, /Current Submission/);
    assert.match(teachingPlanUi, /label: trainer\.teachingPlan\.stateLabel/);
    assert.doesNotMatch(teachingPlanUi, /Awaiting Feedback|tm-plan-review-dot/);
    assert.match(teachingPlanUi, /tm-plan-master-head/);
    assert.match(teachingPlanUi, /aria-pressed/);
    assert.match(teachingPlanUi, /Review teaching plans submitted by assigned trainers and provide feedback/);
    assert.doesNotMatch(teachingPlanUi, /tm-section-count.*assigned/);
    assert.match(teachingPlanUi, /Admin Feedback/);
    assert.match(teachingPlanUi, /This submission has not received Admin feedback yet/);
    assert.match(teachingPlanUi, /Add Feedback/);
    assert.match(teachingPlanUi, /Share clear feedback with the trainer/);
    assert.match(teachingPlanUi, /name="comment"/);
    assert.match(teachingPlanUi, /maxlength="2000" required/);
    assert.match(teachingPlanUi, /Send Feedback/);
    assert.match(teachingPlanUi, /File unavailable/);
    assert.match(teachingPlanUi, /teachingPlan\.current/);
    assert.match(teachingPlanUi, /plan\.comments\.map/);
    assert.match(teachingPlanUi, /<time>/);
    assert.doesNotMatch(teachingPlanUi, /Previous Submissions|Version |versions|latest|tm-plan-history|tm-plan-version/i);
    assert.doesNotMatch(teachingPlanUi, /Approve|Reject|Request Changes|status dropdown/i);
    assert.doesNotMatch(teachingPlanUi, /\bEDU\b/i);
    assert.match(adminTrainingStyles, /\.tm-plan-layout/);
    assert.match(adminTrainingStyles, /\.tm-plan-trainer:hover/);
    assert.match(adminTrainingStyles, /\.tm-plan-trainer\.is-selected/);
    assert.match(adminTrainingStyles, /\.tm-plan-trainer-list[\s\S]*?overflow-x:\s*hidden[\s\S]*?overflow-y:\s*auto/);
    assert.match(adminTrainingStyles, /\.tm-plan-master\s*\{[^}]*height:\s*max-content;[^}]*align-self:\s*start/);
    assert.match(adminTrainingStyles, /\.tm-plan-trainer\s*\{[^}]*min-width:\s*0;[^}]*max-width:\s*100%/);
    assert.match(adminTrainingStyles, /\.tm-plan-trainer-copy span,[^{]+\{[^}]*overflow-wrap:\s*anywhere;[^}]*white-space:\s*normal/);
    assert.doesNotMatch(adminTrainingStyles, /tm-plan-history|tm-plan-version|tm-plan-no-feedback/);
    assert.match(adminTrainingStyles, /\.tm-plan-comment-meta[\s\S]*?flex-direction:\s*column/);
});

test('Admin Teaching Plan current submission uses deliberate file-type indicators', () => {
    const teachingPlanStart = adminTrainingScripts.indexOf('function teachingPlanFileActions(plan)');
    const teachingPlanEnd = adminTrainingScripts.indexOf('function renderActiveTab()');
    const teachingPlanUi = adminTrainingScripts.slice(teachingPlanStart, teachingPlanEnd);
    const iconHelperStart = teachingPlanUi.indexOf('function teachingPlanFileIcon(fileName)');
    const iconHelperEnd = teachingPlanUi.indexOf('function teachingPlanComments(plan)');
    const iconContext = {};

    vm.runInNewContext(
        `${teachingPlanUi.slice(iconHelperStart, iconHelperEnd)}\nicons = [
            teachingPlanFileIcon('plan.PDF'),
            teachingPlanFileIcon('plan.doc'),
            teachingPlanFileIcon('plan.DOCX'),
            teachingPlanFileIcon('plan.bin'),
        ];`,
        iconContext,
    );

    assert.match(teachingPlanUi, /function teachingPlanFileIcon\(fileName\)/);
    assert.match(iconContext.icons[0], /tm-plan-file-icon pdf[\s\S]*?bi-file-earmark-pdf/);
    assert.match(iconContext.icons[1], /tm-plan-file-icon word[\s\S]*?bi-file-earmark-word/);
    assert.match(iconContext.icons[2], /tm-plan-file-icon word[\s\S]*?bi-file-earmark-word/);
    assert.match(iconContext.icons[3], /tm-plan-file-icon generic[\s\S]*?bi-file-earmark-text/);
    assert.match(teachingPlanUi, /teachingPlanFileIcon\(current\.originalName\)/);
    assert.match(teachingPlanUi, /target="_blank" rel="noopener"/);
    assert.match(teachingPlanUi, />Download</);
    assert.doesNotMatch(teachingPlanUi, /<img\b|thumbnail|broken-image/i);
    assert.match(adminTrainingStyles, /\.tm-plan-file-icon\s*\{[^}]*width:\s*42px;[^}]*height:\s*42px;[^}]*border:\s*1px solid/);
    assert.match(adminTrainingStyles, /\.tm-plan-file-icon\.pdf/);
    assert.match(adminTrainingStyles, /\.tm-plan-file-icon\.word/);
    assert.match(adminTrainingStyles, /\.tm-plan-file-copy\s*\{[^}]*min-width:\s*0;[^}]*flex:\s*1/);
    assert.match(adminTrainingStyles, /\.tm-plan-current strong\s*\{[^}]*overflow-wrap:\s*anywhere/);
    assert.doesNotMatch(adminTrainingStyles, /\.tm-plan-current span\s*\{/);
});

test('Admin Training navigator motion matches the approved Trainer Attendance contract', () => {
    const trainerSidebarTransition = cssDeclaration(cssRule(trainerAttendanceStyles, '.lw-att-sidebar'), 'transition');
    const trainerWidthMotion = trainerSidebarTransition.match(/width\s+([^,]+)/)?.[1];
    const trainerSurfaceMotion = trainerSidebarTransition.replace(/^width\s+[^,]+,\s*/, '');
    const trainerReducedDuration = trainerAttendanceStyles.match(/transition-duration:\s*([^;]+)!important/)?.[1]?.trim();
    const adminMasterTransition = cssDeclaration(cssRule(adminTrainingStyles, '.tm-master'), 'transition');

    assert.ok(trainerWidthMotion);
    assert.ok(trainerReducedDuration);
    assert.equal(cssDeclaration(cssRule(adminTrainingStyles, '.tm-workspace'), 'display'), 'flex');
    assert.equal(cssDeclaration(cssRule(adminTrainingStyles, '.tm-master'), 'width'), '360px');
    assert.equal(cssDeclaration(cssRule(adminTrainingStyles, '.tm-master.is-collapsed'), 'width'), '44px');
    assert.match(adminMasterTransition, new RegExp(`width ${trainerWidthMotion.replace('.', '\\.')}`));
    assert.match(adminMasterTransition, new RegExp(`flex-basis ${trainerWidthMotion.replace('.', '\\.')}`));
    trainerSurfaceMotion.split(',').forEach((motion) => {
        assert.match(adminMasterTransition, new RegExp(motion.trim().replaceAll('.', '\\.')));
    });
    assert.equal(
        cssDeclaration(cssRule(adminTrainingStyles, '.tm-master-expanded'), 'transition'),
        cssDeclaration(cssRule(trainerAttendanceStyles, '.lw-sb-expanded'), 'transition'),
    );
    assert.equal(
        cssDeclaration(cssRule(adminTrainingStyles, '.tm-master.is-collapsed .tm-master-expanded'), 'transition'),
        cssDeclaration(cssRule(trainerAttendanceStyles, '.lw-att-sidebar.is-collapsed .lw-sb-expanded'), 'transition'),
    );
    assert.equal(
        cssDeclaration(cssRule(adminTrainingStyles, '.tm-master-collapsed'), 'transition'),
        cssDeclaration(cssRule(trainerAttendanceStyles, '.lw-sb-collapsed'), 'transition'),
    );
    assert.equal(
        cssDeclaration(cssRule(adminTrainingStyles, '.tm-master.is-collapsed .tm-master-collapsed'), 'transition-delay'),
        cssDeclaration(cssRule(trainerAttendanceStyles, '.lw-att-sidebar.is-collapsed .lw-sb-collapsed'), 'transition-delay'),
    );
    assert.match(adminTrainingStyles, /\.tm-master\.is-collapsed \.tm-master-expanded\s*\{[\s\S]*?translateX\(-6px\)/);
    assert.match(adminTrainingStyles, /\.tm-master-collapsed\s*\{[\s\S]*?translateX\(6px\)/);
    assert.doesNotMatch(cssRule(adminTrainingStyles, '.tm-master-expanded'), /display:\s*none/);
    assert.doesNotMatch(cssRule(adminTrainingStyles, '.tm-master.is-collapsed .tm-master-expanded'), /display:\s*none/);
    assert.match(adminTrainingScripts, /expanded\.toggleAttribute\('inert', shouldCollapse\)/);
    assert.match(adminTrainingScripts, /compact\.toggleAttribute\('inert', !shouldCollapse\)/);
    assert.match(adminTrainingContent, /data-training-master-collapsed aria-hidden="true" inert/);
    assert.match(adminTrainingStyles, new RegExp(`transition-duration:\\s*${trainerReducedDuration.replace('.', '\\.')}\\s*!important`));
});

test('Admin Training Monitoring provides complete real-data detail sections and approved paging controls', () => {
    assert.match(adminTrainingScripts, /\['overview', 'bi-grid', 'Overview'\]/);
    assert.match(adminTrainingScripts, /\['sessions', 'bi-calendar3', 'Sessions'\]/);
    assert.match(adminTrainingScripts, /\['participants', 'bi-people', 'Participants'\]/);
    assert.match(adminTrainingScripts, /\['attendance', 'bi-person-check', 'Attendance'\]/);
    assert.match(adminTrainingScripts, /training\.trainers/);
    assert.match(adminTrainingScripts, /training\.sessions/);
    assert.match(adminTrainingScripts, /training\.participants/);
    assert.match(adminTrainingScripts, /training\.attendanceRecords/);
    assert.match(adminTrainingScripts, /Detailed attendance unavailable/);
    assert.match(adminTrainingScripts, /No attendance records/);
    assert.match(adminTrainingScripts, /data-detail-rows/);
    assert.doesNotMatch(adminTrainingContent, /data-training-master-rows/);
    assert.doesNotMatch(adminTrainingScripts, /rowsSelect|data-training-master-rows|masterPerPage|masterPage|renderMasterPager|data-master-page/);
    assert.doesNotMatch(adminTrainingContent, /data-training-master-paginator|trainingCount|trainingPagerControls/);
    assert.match(adminTrainingScripts, /masterRows\.forEach\(function \(row\) \{ row\.hidden = true; \}\);[\s\S]*?matches\.forEach\(function \(row\)/);
    assert.doesNotMatch(adminTrainingScripts, /matches\.slice\(/);
    assert.match(adminTrainingScripts, /return secondDate - firstDate;/);
    assert.match(adminTrainingContent, />Ongoing<\/option>/);
    assert.match(adminTrainingScripts, /Enrolled \/ Capacity/);
    assert.doesNotMatch(adminTrainingScripts, /tm-status-badge/);
    assert.match(adminTrainingScripts, /Name A-Z/);
    assert.match(adminTrainingScripts, /Name Z-A/);
    assert.match(adminTrainingScripts, /Recently enrolled/);
    assert.match(adminTrainingScripts, /Earliest enrolled/);
    assert.doesNotMatch(adminTrainingScripts, /participant\.approvalStatus|participant\.enrollmentType/);
    assert.match(adminTrainingStyles, /@media \(max-width: 1279\.98px\)/);
    assert.match(adminTrainingStyles, /@media \(max-width: 767\.98px\)/);
    assert.doesNotMatch(adminTrainingStyles, /(^|[;{]\s*)zoom\s*:/m);
});

test('Admin Training searches use one neutral custom clear affordance and restore results immediately', () => {
    assert.equal((adminTrainingContent.match(/data-training-search-clear/g) || []).length, 1);
    assert.match(adminTrainingScripts, /function detailSearchClear\(scope, hasValue, label\)/);
    assert.match(adminTrainingStyles, /\.tm-search-control input\[type="search"\]::-webkit-search-cancel-button[\s\S]*?display:\s*none/);
    assert.match(adminTrainingStyles, /\.tm-search-clear\s*\{[\s\S]*?right:\s*7px[\s\S]*?color:\s*var\(--adm-slate-400\)/);
    assert.match(adminTrainingScripts, /searchClear\.addEventListener\('click'[\s\S]*?searchInput\.value = '';[\s\S]*?renderMaster\(\)/);
    assert.match(adminTrainingScripts, /clearState\.search = '';[\s\S]*?clearState\.page = 1;[\s\S]*?renderSessionResults/);
    assert.match(adminTrainingScripts, /searchInput\.addEventListener\('search'[\s\S]*?renderMaster\(\)/);
    assert.match(adminTrainingScripts, /detail\.addEventListener\('input', handleDetailSearch\)/);
    assert.match(adminTrainingScripts, /detail\.addEventListener\('search', handleDetailSearch\)/);
    assert.match(adminTrainingScripts, /state\.search = event\.target\.value;[\s\S]*?state\.page = 1/);
});

test('Admin Training attendance is session-first and session actions open the selected attendance view', () => {
    assert.match(adminTrainingScripts, /<option value="">Select a session<\/option>/);
    assert.match(adminTrainingScripts, /function selectedAttendanceSession\(training\)/);
    assert.match(adminTrainingScripts, /String\(record\.sessionId\) === String\(attendanceState\.session\)/);
    assert.doesNotMatch(adminTrainingScripts, /attendanceState\.session === 'all'/);
    assert.match(adminTrainingScripts, /data-view-session-attendance/);
    assert.match(adminTrainingScripts, /activeTab = 'attendance'/);
    assert.match(adminTrainingScripts, /detailTabs\.scrollIntoView\(\{ behavior: reducedMotion\(\) \? 'auto' : 'smooth', block: 'start' \}\)/);
    assert.doesNotMatch(adminTrainingScripts, /attendanceSummaryMarkup|tm-attendance-summary/);
    assert.match(adminTrainingScripts, /Selected Session/);
});

test('Admin Training renders one compact zero-attendance state without record controls or paging', () => {
    const attendanceStart = adminTrainingScripts.indexOf('function attendanceControls(training)');
    const attendanceEnd = adminTrainingScripts.indexOf('function renderAttendanceResults(training)');
    const attendanceControls = adminTrainingScripts.slice(attendanceStart, attendanceEnd);
    const zeroStart = attendanceControls.indexOf('if (selectedRecords.length === 0)');
    const recordsStart = attendanceControls.indexOf('tm-detail-controls tm-attendance-controls');
    const zeroBranch = attendanceControls.slice(zeroStart, recordsStart);

    assert.ok(attendanceStart >= 0 && attendanceEnd > attendanceStart);
    assert.ok(zeroStart >= 0 && recordsStart > zeroStart);
    assert.match(zeroBranch, /No participants for this session/);
    assert.match(zeroBranch, /canonical enrollment data/);
    assert.doesNotMatch(zeroBranch, /tm-attendance-controls|attendanceResults|attendancePager|data-detail-rows/);
    assert.match(attendanceControls.slice(recordsStart), /tm-attendance-controls[\s\S]*?attendanceResults[\s\S]*?attendancePager/);
    assert.doesNotMatch(adminTrainingScripts, /attendanceSummaryMarkup|tm-attendance-summary|selectedSession\.attendance\.recorded \+ ' recorded'/);
    assert.match(adminTrainingScripts, /tm-attendance-badge ' \+ esc\(record\.statusKey\)[\s\S]*?record\.statusLabel/);
    assert.match(adminTrainingStyles, /\.tm-attendance-badge\.recorded/);
    assert.match(adminTrainingStyles, /\.tm-attendance-badge\.not-recorded/);
    assert.match(attendanceControls, /data-key="status"[\s\S]*?>Recorded<[\s\S]*?>Not Recorded</);
    assert.doesNotMatch(attendanceControls, />Approved<|>Pending<|>Rejected</);
    assert.doesNotMatch(attendanceControls, />Present<|>Late<|>Absent<|Attendance Rate/);
});

test('Admin Training cards share the approved informational hover without click affordance', () => {
    assert.match(adminTrainingStyles, /\.tm-metric:hover,[\s\S]*?\.tm-info-section:not\(\.tm-attendance-section\):hover[\s\S]*?translateY\(-3px\)/);
    assert.doesNotMatch(adminTrainingStyles, /\.tm-metric\s*\{[^}]*cursor:\s*pointer/s);
});

test('Admin participant classification displays Public while preserving the Outsider filter contract', () => {
    assert.match(adminDirectoryContent, /<option value="Outsider">Public<\/option>/);
    assert.match(adminDirectoryContent, /<option value="Guru Baru">New Teacher<\/option>/);
    assert.match(adminDirectoryContent, /data-type="\{\{ \$p->type_value \}\}"/);
    assert.doesNotMatch(adminDirectoryContent, />Outsider<\/option>/);
});

test('Admin Profile is a responsive account and review workspace without photo, security, or tab remnants', () => {
    assert.match(adminProfileContent, /class="pf-card pf-identity"/);
    assert.match(adminProfileContent, /pf-identity-name[\s\S]*?pf-identity-email/);
    assert.match(adminProfileContent, /Review Summary/);
    assert.match(adminProfileContent, /Account Details/);
    assert.match(adminProfileContent, /Recent Review Activity/);
    assert.ok(adminProfileContent.indexOf('Review Summary') < adminProfileContent.indexOf('Account Details'));
    assert.ok(adminProfileContent.indexOf('Account Details') < adminProfileContent.indexOf('Recent Review Activity'));
    assert.equal((adminProfileContent.match(/data-review-metric=/g) || []).length, 4);
    assert.match(adminProfileContent, /No proposal reviews yet/);
    assert.doesNotMatch(adminProfileContent, /Administrator account|Active account|Account Status/);
    assert.doesNotMatch(adminProfileContent, /managed by HR or the system|Latest review/);
    assert.doesNotMatch(adminProfileContent, /pf-eyebrow|pf-managed-note|pf-last-review|pf-inline-status/);
    assert.doesNotMatch(adminProfileContent, /pf-avatar|pfCamBtn|staff_pic|profile\.picture/);
    assert.doesNotMatch(adminProfileContent, /Security|Change Password|Update Password|current_password|new_password/);
    assert.doesNotMatch(adminProfileContent, /role="tab"|pf-tab-/);
    assert.doesNotMatch(adminProfileContent, /pf-badge|pf-content-grid/);
    assert.doesNotMatch(adminProfileView, /profile\.partials\.scripts/);

    assert.match(adminProfileStyles, /\.pf-detail-grid\s*\{[\s\S]*?grid-template-columns:/);
    assert.match(adminProfileStyles, /@media \(max-width: 991\.98px\)/);
    assert.match(adminProfileStyles, /@media \(max-width: 767\.98px\)/);
    assert.match(adminProfileStyles, /@media \(max-width: 575\.98px\)/);
    assert.doesNotMatch(adminProfileStyles, /pf-badge|pf-content-grid/);
    assert.doesNotMatch(cssRule(adminProfileStyles, '.pf-empty'), /min-height/);
    const approvedKpi = cssRule(adminProposalStyles, 'main.adm-wrap .adm-proposal-stats .adm-stat:hover');
    const profileKpi = cssRule(adminProfileStyles, '.pf-stat:hover');
    const profileDetail = cssRule(adminProfileStyles, '.pf-detail-item:hover');
    const approvedTransition = cssDeclaration(
        cssRule(adminProposalStyles, 'main.adm-wrap .adm-proposal-stats .adm-stat'),
        'transition',
    );

    assert.equal(cssDeclaration(cssRule(adminProfileStyles, '.pf-stat'), 'transition'), approvedTransition);
    assert.equal(cssDeclaration(cssRule(adminProfileStyles, '.pf-detail-item'), 'transition'), approvedTransition);

    for (const property of ['transform', 'border-color', 'box-shadow']) {
        assert.equal(cssDeclaration(profileKpi, property), cssDeclaration(approvedKpi, property));
        assert.equal(cssDeclaration(profileDetail, property), cssDeclaration(approvedKpi, property));
    }

    assert.match(adminProfileStyles, /\.pf-activity-row:hover/);
    assert.doesNotMatch(adminProfileStyles, /cursor:\s*pointer|pf-eyebrow|pf-managed-note|pf-last-review|pf-inline-status|pf-avatar|pf-pass|pf-strength|pf-tab/);
});

test('Admin Directory exposes four accessible sections with one professional control and table contract', () => {
    assert.equal((adminDirectoryContent.match(/data-directory-panel/g) || []).length, 4);
    assert.equal((adminDirectoryContent.match(/role="tab"/g) || []).length, 4);
    assert.match(adminDirectoryContent, /role="tablist" aria-label="Directory sections"/);
    assert.match(adminDirectoryScripts, /setAttribute\('aria-selected', active \? 'true' : 'false'\)/);
    assert.match(adminDirectoryScripts, /\['ArrowLeft', 'ArrowRight', 'Home', 'End'\]/);

    assert.equal((adminDirectoryContent.match(/class="dir-search"/g) || []).length, 4);
    assert.equal((adminDirectoryContent.match(/data-directory-search-clear/g) || []).length, 4);
    assert.equal((adminDirectoryContent.match(/class="dir-sort"/g) || []).length, 4);
    assert.equal((adminDirectoryContent.match(/data-directory-table>/g) || []).length, 4);
    assert.equal((adminDirectoryContent.match(/data-directory-empty role="status"/g) || []).length, 4);
    assert.equal((adminDirectoryContent.match(/data-directory-pager/g) || []).length, 4);

    assert.match(adminDirectoryContent, /Name A–Z/);
    assert.match(adminDirectoryContent, /Name Z–A/);
    assert.match(adminDirectoryContent, /Title A–Z/);
    assert.match(adminDirectoryContent, /Newest date/);
    assert.match(adminDirectoryContent, /Oldest date/);
    assert.match(adminDirectoryScripts, /\[10, 25, 50\]\.map/);
    assert.match(adminDirectoryScripts, /data-directory-rows/);
    assert.match(adminDirectoryScripts, /Showing <strong>/);
    assert.match(adminDirectoryScripts, /data-directory-page="prev"/);
    assert.match(adminDirectoryScripts, /data-directory-page="next"/);
});

test('Admin Directory search, reset, sorting, empty states, and details remain client-side and accessible', () => {
    assert.match(adminDirectoryStyles, /input\[type="search"\]::-webkit-search-cancel-button[\s\S]*?display:\s*none/);
    assert.match(adminDirectoryScripts, /searchClear\.addEventListener\('click'[\s\S]*?search\.value = '';[\s\S]*?page = 1;[\s\S]*?render\(\)/);
    assert.match(adminDirectoryScripts, /search\.addEventListener\('search', resetPage\)/);
    assert.match(adminDirectoryScripts, /function compareRows\(first, second, sort\)/);
    assert.match(adminDirectoryScripts, /sort\.addEventListener\('change', resetPage\)/);
    assert.match(adminDirectoryContent, /data-no-results-title="No matching participants"/);
    assert.match(adminDirectoryContent, /data-empty-title="No participants recorded"/);
    assert.match(adminDirectoryContent, /class="modal fade dir-detail-modal"/);
    assert.match(adminDirectoryContent, /aria-labelledby="vmName"/);
    assert.match(adminDirectoryContent, /data-d-certificates=/);
    assert.match(adminDirectoryContent, /id="vmCertificatesSection"[\s\S]*?>Certificates</);
    assert.match(adminDirectoryScripts, /new bootstrap\.Modal/);
    assert.match(adminDirectoryScripts, /row\.querySelector\('\.dir-view'\)\.click\(\)/);
    assert.doesNotMatch(adminDirectoryScripts, /fetch\(|XMLHttpRequest/);
    assert.match(adminDirectoryStyles, /\.dir-row:hover/);
    assert.match(adminDirectoryStyles, /@media \(max-width: 767\.98px\)/);
});

test('Admin Directory trainer details render certificates read-only with safe empty and unavailable states', () => {
    assert.match(adminDirectoryScripts, /JSON\.parse\(button\.dataset\.dCertificates \|\| '\[\]'\)/);
    assert.match(adminDirectoryScripts, /No certificates uploaded\./);
    assert.match(adminDirectoryScripts, /File unavailable/);
    assert.match(adminDirectoryScripts, /certificate\.available && certificate\.view_url/);
    assert.match(adminDirectoryScripts, /class="vm-certificate-view"[\s\S]*?target="_blank"[\s\S]*?rel="noopener"/);
    assert.doesNotMatch(adminDirectoryContent, /certificate\.remove|Upload certificate|Remove certificate/);
    assert.match(adminDirectoryStyles, /\.vm-certificate-row:hover/);
    assert.match(adminDirectoryStyles, /\.vm-certificate-view:focus-visible/);
});

test('Admin Directory presents truthful schedule semantics and approved display terminology', () => {
    assert.match(adminDirectoryContent, /Session Schedule/);
    assert.match(adminDirectoryContent, /elapsed \/ total by session date/);
    assert.doesNotMatch(adminDirectoryContent, />Progress<\/th>|data-progress|adm-tbar|adm-tprog/);
    assert.match(adminDirectoryContent, /<option value="Outsider">Public<\/option>/);
    assert.match(adminDirectoryContent, /<option value="Guru Baru">New Teacher<\/option>/);
    assert.match(adminDirectoryContent, /<option value="Simple">Simple<\/option>/);
    assert.doesNotMatch(adminDirectoryContent, />Outsider<|>Guru New<|>Standard</);
});

test('Admin page navigation remains complete without decorative page icons', () => {
    for (const label of ['Dashboard', 'Proposals', 'Activity', 'Trainings', 'Analytics', 'Directory']) {
        assert.match(adminNavbar, new RegExp(`'label' => '${label}'`));
    }
    assert.doesNotMatch(adminNavbar, /navigationItem\['icon'\]/);
    assert.match(adminNavbar, /aria-current="page"/);
    assert.ok(adminNavbar.indexOf("'label' => 'Directory'") < adminNavbar.indexOf("'label' => 'Analytics'"));
    assert.ok(adminNavbar.indexOf("'label' => 'Analytics'") > adminNavbar.indexOf("'label' => 'Trainings'"));
});
