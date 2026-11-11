import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';
import { scoreBandCounts, wrapAnalyticsChartLabel } from '../../resources/js/analytics-charts.js';
import {
    createQuestionNavigationGate,
    createTrackingRequestGate,
    normalizeTrackingRegions,
    normalizeTrackingState,
    questionScrollBehavior,
    quizPercentage,
    shouldCloseTrackingAttempt,
    TRACKING_ACTION_REGIONS,
    TRACKING_REGION_NAMES,
    TRACKING_POPSTATE_REGIONS,
    trackingRegionsForAction,
    trackingSearchChanges,
    trackingRequestUrl,
    trackingUrl,
} from '../../resources/js/learner-tracking.js';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');

test('analytics inline script remains valid JavaScript', () => {
    const blade = read('../../resources/views/trainer/training/analytics/partials/scripts.blade.php');
    const script = blade.match(/<script>([\s\S]*?)<\/script>\s*$/)?.[1];
    assert.ok(script);
    assert.doesNotThrow(() => new vm.Script(script));
});

test('Trainer Analytics reuses the approved Admin metric and report-card hierarchy', () => {
    const metrics = read('../../resources/views/trainer/training/analytics/partials/metrics.blade.php');
    const reports = read('../../resources/views/trainer/training/analytics/partials/report-panels.blade.php');
    const styles = read('../../resources/views/trainer/training/analytics/partials/styles.blade.php');

    assert.match(metrics, /data-trainer-analytics-metric/);
    assert.match(metrics, /class="ana-metric-copy"/);
    assert.match(reports, /Training report/);
    assert.match(reports, /Attendance Record Coverage/);
    assert.match(reports, /LMS performance/);
    assert.match(styles, /\.ana-shell \.ana-metric\s*\{[^}]*flex-direction:\s*column;[^}]*padding:\s*17px 18px;[^}]*border-radius:\s*16px;/s);
    assert.match(styles, /\.ana-shell \.ana-metric-icon\s*\{[^}]*width:\s*40px;[^}]*height:\s*40px;[^}]*border-radius:\s*12px;/s);
    assert.match(styles, /\.ana-shell \.ana-metric:hover\s*\{[^}]*translateY\(-3px\);[^}]*border-color:\s*#bfdbfe;[^}]*box-shadow:\s*0 12px 24px rgba\(15, 23, 42, \.08\);/s);
    assert.match(styles, /\.ana-shell \.ana-report-container\s*\{\s*gap:\s*26px;\s*\}/);
    assert.doesNotMatch(styles, /\.ana-shell \.ana-panel:hover/);
});

test('Trainer Analytics charts use the lighter Admin palette only in the Trainer shell', () => {
    const chartSource = read('../../resources/js/analytics-charts.js');
    const styles = read('../../resources/views/trainer/training/analytics/partials/styles.blade.php');
    const wrapped = wrapAnalyticsChartLabel('Advanced Classroom Facilitation Programme', 18);

    assert.ok(wrapped.length > 1);
    assert.ok(wrapped.every((line) => line.length <= 18));
    assert.equal(wrapped.join(' '), 'Advanced Classroom Facilitation Programme');
    assert.match(chartSource, /const colors = \['#16A34A', '#94A3B8'\]/);
    assert.match(chartSource, /const analyticsBlue = '#60A5FA'/);
    assert.match(chartSource, /const analyticsBlueHover = '#3B82F6'/);
    assert.match(chartSource, /const isTrainerAnalytics = \(root\)/);
    assert.match(chartSource, /if \(!isTrainerAnalytics\(root\)\) return/);
    assert.match(chartSource, /devicePixelRatio:\s*chartPixelRatio\(\)/);
    assert.match(chartSource, /Chart\.getChart\(canvas\)/);
    assert.match(chartSource, /grid:\s*\{\s*color:\s*chartGrid,\s*drawTicks:\s*false\s*\}/);
    assert.match(chartSource, /backgroundColor:\s*'#0F172A'/);
    assert.match(styles, /--trainer-analytics-blue:\s*#60a5fa/);
    assert.match(styles, /\.ana-attendance-legend \.recorded\s*\{\s*color:\s*var\(--ana-green\);\s*\}/);
    assert.match(styles, /\.ana-attendance-legend \.not-recorded\s*\{\s*color:\s*var\(--ana-slate-400\);\s*\}/);
});

test('Trainer Analytics tables and filters match Admin density without changing pagination', () => {
    const filters = read('../../resources/views/trainer/training/analytics/partials/header-filters.blade.php');
    const styles = read('../../resources/views/trainer/training/analytics/partials/styles.blade.php');

    assert.match(filters, /name="course_id"/);
    assert.match(filters, /name="report_date"/);
    assert.doesNotMatch(filters, /course_category/);
    assert.match(styles, /\.ana-shell \.ana-filter-row\s*\{[^}]*grid-template-columns:\s*minmax\(280px, 1\.4fr\) minmax\(190px, \.8fr\);[^}]*gap:\s*16px;/s);
    assert.match(styles, /\.ana-shell \.ana-table td\s*\{[^}]*padding:\s*14px 16px;[^}]*font-variant-numeric:\s*tabular-nums;/s);
    assert.match(styles, /\.ana-shell \.ana-table :is\(th, td\):first-child\s*\{[^}]*overflow-wrap:\s*anywhere;[^}]*white-space:\s*normal;/s);
    assert.match(styles, /\.ana-shell \.ana-table-wrap\s*\{[^}]*overflow-x:\s*auto;/s);
    assert.match(styles, /@media \(max-width:\s*991px\)[\s\S]*?\.ana-shell \.ana-attendance-charts\s*\{\s*grid-template-columns:\s*1fr;/);
    assert.deepEqual(scoreBandCounts([{ percentage: 39 }, { percentage: 40 }, { percentage: 60 }, { percentage: 80 }]), [1, 1, 1, 1]);
});

test('Learner Tracking normalizes authoritative URL state', () => {
    assert.deepEqual(normalizeTrackingState({}), { tab: 'overview', pivot: 'participant', rows: 10 });
    assert.deepEqual(normalizeTrackingState({ tab: 'assessment', pivot: 'module', rows: '25' }), { tab: 'quiz', pivot: 'module', rows: 25 });
    assert.deepEqual(normalizeTrackingState({ tab: 'bad', pivot: 'bad', rows: 999 }), { tab: 'overview', pivot: 'participant', rows: 10 });

    const url = trackingUrl('https://example.test/tracking?participant=4&fragment=1', { tab: 'engagement', participant: null, rows: 50 });
    assert.equal(url.searchParams.get('tab'), 'engagement');
    assert.equal(url.searchParams.get('rows'), '50');
    assert.equal(url.searchParams.has('participant'), false);
    assert.equal(url.searchParams.has('fragment'), false);

    const requestUrl = trackingRequestUrl(url.href);
    assert.equal(requestUrl.searchParams.get('fragment'), '1');
    assert.equal(requestUrl.searchParams.get('tab'), 'engagement');

    assert.deepEqual(normalizeTrackingRegions('navigator-list main main invalid'), ['navigator-list', 'main']);
    assert.deepEqual(normalizeTrackingRegions(['tabs', 'navigator', 'main']), ['tabs', 'navigator', 'main']);
    assert.deepEqual(normalizeTrackingRegions('', ['quiz-results']), ['quiz-results']);
    assert.deepEqual(normalizeTrackingRegions('invalid', 'also-invalid'), ['main']);
    assert.deepEqual(trackingRegionsForAction('tab', 'main'), ['tabs', 'navigator', 'main']);
    assert.deepEqual(trackingRegionsForAction('pivot', 'main'), ['navigator', 'main']);
    assert.deepEqual(trackingRegionsForAction('navigator-search', 'main'), ['navigator-count', 'navigator-list']);
    assert.deepEqual(trackingRegionsForAction('quiz-search', 'main'), ['quiz-results', 'attempt-detail']);
    assert.deepEqual(TRACKING_ACTION_REGIONS.history, TRACKING_POPSTATE_REGIONS);
    assert.deepEqual(TRACKING_POPSTATE_REGIONS, ['tabs', 'period', 'navigator', 'main']);

    assert.deepEqual(trackingSearchChanges('search', '  learner  '), {
        search: 'learner', activity_page: 1, engagement_page: 1, quiz_page: 1,
    });
    assert.deepEqual(trackingSearchChanges('participant_search', ''), {
        participant_search: null, quiz_page: 1, attempt: null,
    });
});

test('Learner Tracking request gate aborts stale work and accepts only latest response', () => {
    const gate = createTrackingRequestGate();
    const first = gate.begin();
    const second = gate.begin();
    assert.equal(first.signal.aborted, true);
    assert.equal(gate.isCurrent(first.id), false);
    assert.equal(gate.isCurrent(second.id), true);
    gate.abort();
    assert.equal(second.signal.aborted, true);
    assert.equal(gate.isCurrent(second.id), false);
});

test('question navigation keeps a clicked destination active until scrolling reaches it', () => {
    const navigation = createQuestionNavigationGate();

    assert.equal(navigation.begin('quiz-question-3'), 'quiz-question-3');
    assert.equal(navigation.active('quiz-question-1'), 'quiz-question-3');
    assert.equal(navigation.target(), 'quiz-question-3');
    assert.equal(navigation.active('quiz-question-3', true), 'quiz-question-3');
    assert.equal(navigation.target(), null);
    assert.equal(navigation.active('quiz-question-4'), 'quiz-question-4');

    navigation.begin('quiz-question-2');
    navigation.release();
    assert.equal(navigation.active('quiz-question-5'), 'quiz-question-5');
});

test('zero-total and ungraded quizzes never create a fabricated percentage', () => {
    assert.equal(quizPercentage(8, 10), 80);
    assert.equal(quizPercentage(0, 0), null);
    assert.equal(quizPercentage(null, 10), null);
    assert.equal(quizPercentage('bad', 10), null);
});

test('Learner Tracking workspace uses scoped async navigation and scalable document flow', () => {
    const source = read('../../resources/js/learner-tracking.js');
    const page = read('../../resources/views/trainer/training/learner-tracking.blade.php');
    const content = read('../../resources/views/trainer/training/learner-tracking/partials/content.blade.php');
    const styles = read('../../resources/views/trainer/training/learner-tracking/partials/styles.blade.php');
    const workspaceHeaderStyles = read('../../resources/views/trainer/training/partials/workspace-header-styles.blade.php');
    const overview = read('../../resources/views/trainer/training/learner-tracking/partials/overview-panel.blade.php');
    const engagement = read('../../resources/views/trainer/training/learner-tracking/partials/engagement-panel.blade.php');
    const assessment = read('../../resources/views/trainer/training/learner-tracking/partials/assessment-panel.blade.php');
    const detail = read('../../resources/views/trainer/training/learner-tracking/partials/attempt-detail.blade.php');
    const sidebar = read('../../resources/views/trainer/training/learner-tracking/partials/sidebar.blade.php');

    assert.match(source, /AbortController/);
    assert.match(source, /history\.pushState/);
    assert.match(source, /history\.replaceState/);
    assert.match(source, /popstate/);
    assert.match(source, /__learnerTrackingInitialized/);
    assert.match(source, /300/);
    assert.match(content, /data-tracking-workspace/);
    assert.match(content, /data-tracking-link/);
    assert.deepEqual(TRACKING_REGION_NAMES, ['tabs', 'period', 'navigator', 'navigator-count', 'navigator-list', 'main', 'activity-results', 'engagement-results', 'quiz-results', 'attempt-detail']);
    const regionMarkup = [content, sidebar, overview, engagement, assessment, detail].join('\n');
    for (const region of TRACKING_REGION_NAMES) assert.match(regionMarkup, new RegExp(`data-tracking-region="${region}"`));
    assert.match(page, /trainer\.training\.partials\.workspace-header-styles/);
    assert.match(page, /trainer\.training\.learner-tracking\.partials\.header/);
    assert.doesNotMatch(content, /data-lms-workspace-header|aria-label="LMS workspace"/);
    assert.match(workspaceHeaderStyles, /data-lms-workspace-header-styles/);
    assert.match(workspaceHeaderStyles, /\.lw-tabs a\.active/);
    assert.match(styles, /::-webkit-search-cancel-button/);
    assert.match(styles, /\.lt-nav-list\s*\{[^}]*flex:\s*1 1 auto[^}]*min-height:\s*0[^}]*overflow-y:\s*auto/);
    assert.match(styles, /\.lt-main\s*\{[^}]*min-width:\s*0/);
    assert.doesNotMatch(styles, /\.lt-main\s*\{[^}]*overflow-y:\s*auto/);
    assert.match(styles, /@media \(max-width: 760px\)[\s\S]*\.lt-navigator[^}]*position:\s*static/);
    assert.match(source, /replaceRegions/);
    assert.match(source, /trackingRequestUrl\(destination\.href\)/);
    assert.doesNotMatch(source, /trackingUrl\(destination\.href,\s*\{\s*fragment:\s*1/);
    assert.match(source, /historyMode: 'replace'/);
    assert.match(source, /setTimeout\([\s\S]*160/);
    assert.match(source, /trackingBusyRequest === busyRequest/);
    assert.match(source, /delete target\.dataset\.trackingBusyRequest/);
    assert.doesNotMatch(source, /current\.replaceWith\(next\)|(?:win|window)\.scrollTo\s*\(/);
    assert.doesNotMatch(source, /location\.assign/);
    assert.match(overview, /Materials accessed/);
    assert.doesNotMatch(overview, /Course Progress|Modules Accessed|Assessments Done/);
    assert.match(engagement, /Accessed/);
    assert.doesNotMatch(engagement, /Completed|In Progress/);
    assert.match(sidebar, /bi-layout-sidebar-inset/);
    assert.match(sidebar, /bi-list-ul/);
    assert.match(sidebar, /title="Hide navigator"/);
    assert.match(sidebar, /title="Show navigator"/);
    assert.match(assessment, /data-tracking-regions="quiz-results attempt-detail"/);
    assert.match(assessment, /data-tracking-regions="attempt-detail"/);
    assert.match(detail, /Answer-level responses are not available from the connected assessment records/);
    assert.match(detail, /Detailed answers unavailable/);
    assert.match(detail, /Recorded result/);
    assert.match(detail, /Answer review/);
    assert.match(detail, /Attempt \{\{ \$attemptOption->attempt_number \}\}/);
    assert.match(detail, /role="dialog" aria-modal="true"/);
    assert.match(detail, /data-attempt-close/);
    assert.match(detail, /data-attempt-backdrop-close/);
    assert.match(detail, /Correct/);
    assert.match(detail, /Incorrect/);
    assert.match(detail, /Manual review/);
    assert.match(detail, /Participant answer/);
    assert.doesNotMatch(detail, /Your answer/);
    assert.match(detail, /Correct answer/);
    assert.match(detail, /is-participant-selected/);
    assert.match(detail, /data-answer-source="participant"/);
    assert.match(detail, /lt-answer-correctness is-correct/);
    assert.match(detail, /lt-answer-correctness is-incorrect/);
    assert.match(detail, /data-question-jump/);
    assert.match(detail, /id="quiz-question-/);
    assert.match(detail, /data-question-status/);
    assert.match(styles, /\.lt-question-nav/);
    assert.match(styles, /\.lt-question-jump\.is-correct/);
    assert.match(styles, /\.lt-question-jump\.is-incorrect/);
    assert.match(styles, /\.lt-question-jump\.is-unanswered/);
    assert.match(styles, /\.lt-question-jump\.is-manual/);
    assert.match(styles, /\.lt-question-jump\.is-recorded_unavailable/);
    assert.match(styles, /\.lt-question-jump\.is-cannot_evaluate/);
    assert.match(styles, /\.lt-answer-option\.is-correct-selected\s*\{/);
    assert.match(styles, /\.lt-answer-option\.is-correct-answer\s*\{/);
    assert.match(styles, /\.lt-answer-option\.is-wrong-selected\s*\{/);
    assert.match(styles, /\.lt-answer-correctness\.is-correct\s*\{/);
    assert.match(styles, /\.lt-answer-correctness\.is-incorrect\s*\{/);
    assert.doesNotMatch(styles, /\.lt-answer-option\.is-correct-selected\s*,\s*\.lt-answer-option\.is-correct-answer/);
    assert.match(styles, /\.lt-attempt-drawer\s*\{[^}]*width:\s*min\(820px/);
    assert.match(styles, /\.lt-attempt-layer\.is-closing/);
    assert.match(styles, /@keyframes lt-drawer-out/);
    assert.match(styles, /@keyframes lt-backdrop-out/);
    assert.match(styles, /\.lt-attempt-scroll-locked/);
    assert.match(source, /event\.key === 'Escape'/);
    assert.match(source, /attemptOpener\.focus/);
    assert.match(source, /waitForAttemptExit/);
    assert.match(source, /syncAttemptScrollLock/);
    assert.match(source, /participant_search'\) invalidateAttemptImmediately\(\)/);
    assert.match(source, /drawerBody\.scrollTo/);
    assert.match(source, /data-question-jump/);
    assert.match(source, /questionNavigationGate\.begin/);
    assert.match(source, /questionNavigationGate\.active/);
    assert.match(source, /wheel/);
    assert.match(source, /touchstart/);
    assert.equal(questionScrollBehavior(false), 'smooth');
    assert.equal(questionScrollBehavior(true), 'auto');
});

test('Learner Tracking controls replace every dependent region without refreshing the page', () => {
    const content = read('../../resources/views/trainer/training/learner-tracking/partials/content.blade.php');
    const sidebar = read('../../resources/views/trainer/training/learner-tracking/partials/sidebar.blade.php');
    const overview = read('../../resources/views/trainer/training/learner-tracking/partials/overview-panel.blade.php');
    const engagement = read('../../resources/views/trainer/training/learner-tracking/partials/engagement-panel.blade.php');
    const assessment = read('../../resources/views/trainer/training/learner-tracking/partials/assessment-panel.blade.php');

    assert.match(sidebar, /data-tracking-search="search"[^>]*data-tracking-action="navigator-search"[^>]*data-tracking-regions="navigator-list"/);
    assert.match(sidebar, /data-tracking-action="selection"[^>]*data-tracking-regions="main"[^>]*data-tracking-selection/);
    assert.match(content, /data-tracking-action="tab"[^>]*data-tracking-regions="tabs navigator main"/);
    assert.match(sidebar, /data-tracking-action="pivot"[^>]*data-tracking-regions="navigator main"/);
    assert.match(sidebar, /No participants found/);
    assert.match(sidebar, /No modules or materials found/);
    assert.match(sidebar, /No quizzes found/);
    assert.match(overview, /trackingRegions' => 'activity-results'/);
    assert.match(engagement, /data-tracking-filter="module" data-tracking-regions="engagement-results"/);
    assert.match(engagement, /trackingRegions' => 'engagement-results'/);
    assert.match(assessment, /trackingRegions' => 'quiz-results'/);
    assert.match(assessment, /data-tracking-action="quiz-search" data-tracking-regions="quiz-results attempt-detail"/);
    assert.match(assessment, /data-tracking-filter="status" data-tracking-regions="quiz-results attempt-detail"/);
    assert.match(content, /data-tracking-regions="main"/);
});

test('attempt invalidation follows URL state for search, status, quiz selection, and history', () => {
    assert.equal(shouldCloseTrackingAttempt('https://example.test/tracking?tab=quiz', ['quiz-results', 'attempt-detail'], true), true);
    assert.equal(shouldCloseTrackingAttempt('https://example.test/tracking?tab=quiz&attempt=9', ['attempt-detail'], true), false);
    assert.equal(shouldCloseTrackingAttempt('https://example.test/tracking?tab=quiz', ['main'], true), true);
    assert.equal(shouldCloseTrackingAttempt('https://example.test/tracking?tab=quiz', ['quiz-results'], true), false);
    assert.equal(shouldCloseTrackingAttempt('https://example.test/tracking?tab=quiz', ['attempt-detail'], false), false);

    const participantSearch = trackingSearchChanges('participant_search', 'learner');
    assert.equal(participantSearch.attempt, null);
    assert.deepEqual(TRACKING_ACTION_REGIONS['quiz-search'], ['quiz-results', 'attempt-detail']);
    assert.ok(TRACKING_ACTION_REGIONS.selection.includes('main'));
    assert.ok(TRACKING_POPSTATE_REGIONS.includes('main'));

    const source = read('../../resources/js/learner-tracking.js');
    assert.match(source, /filter\.dataset\.trackingFilter === 'status'\) changes\.attempt = null/);
    assert.doesNotMatch(source, /location\.assign|location\.replace|window\.location\s*=/);
});

test('LMS workspace pages share one compact page-heading contract', () => {
    const heading = read('../../resources/views/trainer/training/partials/workspace-page-heading.blade.php');
    const overview = read('../../resources/views/trainer/training/workspace-overview.blade.php');
    const submissions = read('../../resources/views/trainer/training/submission.blade.php');
    const attendance = read('../../resources/views/trainer/training/attendance/partials/content.blade.php');
    const participants = read('../../resources/views/trainer/training/participants/partials/content.blade.php');
    const tracking = read('../../resources/views/trainer/training/learner-tracking/partials/content.blade.php');

    assert.match(heading, /data-lms-page-heading-styles/);
    assert.match(heading, /lw-page-heading/);
    assert.match(overview, /'title' => 'Overview'/);
    assert.match(submissions, /'title' => 'Submissions'/);
    assert.match(attendance, /'title' => 'Attendance'/);
    assert.match(participants, /'title' => 'Participants'/);
    assert.match(tracking, /'title' => 'Learner Tracking'/);
});

test('Learner Tracking has no demo generators, fabricated statuses, or tracking writes', () => {
    const service = read('../../app/Services/Lms/TrainerLmsLearnerTrackingReportService.php');
    const content = read('../../resources/views/trainer/training/learner-tracking/partials/content.blade.php');
    const views = [
        content,
        read('../../resources/views/trainer/training/learner-tracking/partials/overview-panel.blade.php'),
        read('../../resources/views/trainer/training/learner-tracking/partials/engagement-panel.blade.php'),
        read('../../resources/views/trainer/training/learner-tracking/partials/assessment-panel.blade.php'),
    ].join('\n');

    assert.doesNotMatch(views, /dummy|demo-file|learning_progress|progress_status|20 minutes|1200/);
    assert.doesNotMatch(service, /->insert\(|->update\(|->delete\(/);
    assert.match(service, /Schema::hasTable\('training_content_interaction_log'\)/);
    assert.match(service, /interactiveMediaExtensions\(\)/);
    assert.match(service, /whereIn\('training_content_block\.block_type', \['material', 'youtube'\]\)/);
    assert.match(service, /paginateBuilder\(\$query, \$rows/);
});

test('selected-quiz score distribution uses the approved score-band boundaries', () => {
    const counts = scoreBandCounts([
        { percentage: 0 }, { percentage: 39.9 }, { percentage: 40 }, { percentage: 59.9 },
        { percentage: 60 }, { percentage: 79.9 }, { percentage: 80 }, { percentage: 100 },
    ]);
    assert.deepEqual(counts, [2, 2, 2, 2]);
});
