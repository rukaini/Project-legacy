import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const page = read('../../resources/views/trainer/feedback.blade.php');
const dynamic = read('../../resources/views/trainer/feedback/partials/dynamic-region.blade.php');
const scriptView = read('../../resources/views/trainer/feedback/partials/scripts.blade.php');
const script = scriptView.match(/<script>([\s\S]*?)<\/script>/)?.[1];
const summary = read('../../resources/views/trainer/feedback/partials/summary.blade.php');
const filters = read('../../resources/views/trainer/feedback/partials/filters.blade.php');
const workspace = read('../../resources/views/trainer/feedback/partials/course-list.blade.php');
const detail = read('../../resources/views/trainer/feedback/partials/training-detail.blade.php');
const question = read('../../resources/views/trainer/feedback/partials/question-results.blade.php');
const comments = read('../../resources/views/trainer/feedback/partials/comment-list.blade.php');
const styles = read('../../resources/views/trainer/feedback/partials/styles.blade.php');
const analyticsStyles = read('../../resources/views/trainer/training/analytics/partials/styles.blade.php');
const attendanceStyles = read('../../resources/views/trainer/training/attendance/partials/styles.blade.php');
const submissionStyles = read('../../resources/views/trainer/training/submission/partials/styles.blade.php');
const feedbackService = read('../../app/Services/Feedback/TrainerFeedbackReportService.php');

test('Trainer Feedback inline behavior contains valid JavaScript', () => {
    assert.ok(script);
    assert.doesNotThrow(() => new vm.Script(script));
});

test('Feedback keeps the filter form mounted while replacing scoped report regions', () => {
    assert.match(dynamic, /id="feedbackSummaryRegion"/);
    assert.match(dynamic, /class="fb-feedback-shell"[\s\S]*feedback\.partials\.filters[\s\S]*id="feedbackResultsRegion"/);
    assert.match(filters, /id="feedbackFilterForm"/);
    assert.ok(dynamic.indexOf("feedback.partials.filters") < dynamic.indexOf('id="feedbackResultsRegion"'));
    assert.match(script, /summary\.replaceWith\(nextSummary\)/);
    assert.match(script, /results\.replaceWith\(nextResults\)/);
    assert.doesNotMatch(script, /feedbackFilterForm[^\n]*replaceWith|replaceWith\([^)]*feedbackFilterForm/);
    assert.doesNotMatch(script, /refreshTarget\([^\n]*#feedbackFilterForm/);
});

test('Feedback async state cancels stale requests and preserves meaningful history', () => {
    assert.match(script, /activeRequest\?\.abort\(\)/);
    assert.match(script, /const sequence = \+\+requestSequence/);
    assert.match(script, /'X-Requested-With': 'XMLHttpRequest'/);
    assert.match(script, /history\.pushState\(\{\}, '', url\)/);
    assert.match(script, /history\.replaceState\(\{\}, '', url\)/);
    assert.match(script, /withCanonicalFilters\(new URL\(url\), parsed\)/);
    assert.match(script, /course_id/);
    assert.match(script, /question_id/);
    assert.match(script, /popstate[\s\S]*refreshReport\(window\.location\.href, 'none', true\)/);
    assert.match(script, /window\.scrollTo\(\{ top: scrollY/);
});

test('Comment search is debounced without replacing its input or adding history per term', () => {
    assert.match(script, /window\.clearTimeout\(searchTimer\)/);
    assert.match(script, /searchTimer = window\.setTimeout\(\(\) => \{[\s\S]*if \(filterRequestReady\(\)\) refreshReport\(filterUrl\(\), 'replace'\);[\s\S]*\}, 180\)/);
    assert.equal((script.match(/document\.addEventListener\('input'/g) || []).length, 1);
    assert.match(script, /if \(!event\.target\.matches\('#fbSearch'\)\) return/);
    assert.match(script, /activeRequest\?\.abort\(\);[\s\S]*event\.target\.value === lastRenderedSearchValue/);
    assert.match(script, /lastRenderedSearchValue = parsed\.querySelector\('#fbSearch'\)\?\.value \|\| ''/);
    assert.doesNotMatch(script, /searchTimer[\s\S]{0,180}refreshReport\(filterUrl\(\), 'push'\)/);
    assert.match(script, /if \(focusKey && focusKey !== 'fbSearch'\) document\.getElementById\(focusKey\)\?\.focus/);
});

test('Search clear and reset controls keep their distinct approved responsibilities', () => {
    assert.match(filters, /data-feedback-clear-search/);
    assert.match(filters, /aria-label="Clear comment search"/);
    assert.match(script, /clearSearch[\s\S]*search\.value = ''[\s\S]*search\.focus\(\{ preventScroll: true \}\)[\s\S]*refreshReport\(filterUrl\(\), 'replace'\)/);
    assert.match(filters, /\$nonSearchFiltersActive/);
    assert.match(filters, /data-feedback-reset @if \(! \$nonSearchFiltersActive\) hidden/);
    assert.match(script, /const nonSearchActive =[\s\S]*filter_course_id[\s\S]*period[\s\S]*rating[\s\S]*from[\s\S]*to/);
    assert.doesNotMatch(script.match(/const nonSearchActive =[\s\S]*?reset\.hidden = !nonSearchActive;/)?.[0] || '', /search\?\.value/);
    assert.match(script, /form\.elements\.search\.value = ''/);
});

test('Custom response-date controls wait for a complete valid range', () => {
    assert.match(filters, /<option value="custom"/);
    assert.match(filters, /data-feedback-custom-dates/);
    assert.match(filters, /name="date_from"/);
    assert.match(filters, /name="date_to"/);
    assert.match(script, /if \(event\.target\.value === 'custom'\) return/);
    assert.match(script, /return from !== '' && to !== '' && from <= to/);
    assert.match(script, /const filterRequestReady =[\s\S]*value !== 'custom'[\s\S]*customDatesReady\(\)/);
    assert.match(script, /#fbDateFrom, #fbDateTo[\s\S]*if \(customDatesReady\(\)\) refreshReport\(filterUrl\(\)\)/);
    assert.match(script, /\['filter_course_id', 'period', 'rating', 'search', 'date_from', 'date_to', \.\.\.removals\]/);
    assert.match(script, /\['filter_course_id', 'period', 'rating', 'search', 'date_from', 'date_to'\]/);
    assert.match(script, /data-feedback-select-training[\s\S]*filterUrl\(\{ course_id:/);
    assert.match(script, /data-feedback-question-selector[\s\S]*filterUrl\(\{ question_id:/);
});

test('Feedback KPI cards inherit the approved system hover treatment', () => {
    assert.match(page, /trainer\.training\.analytics\.partials\.styles/);
    assert.equal((summary.match(/class="ana-metric fb-summary-card"/g) || []).length, 4);
    assert.match(summary, /Written Comments/);
    assert.match(summary, /\$writtenComments/);
    assert.match(summary, /ana-metric-label/);
    assert.match(summary, /ana-metric-value/);
    assert.match(summary, /Across participant ratings/);
    assert.equal((summary.match(/class="ana-metric-icon fb-summary-icon-/g) || []).length, 4);
    assert.match(summary, /fb-summary-icon-blue[\s\S]*ana-metric-label[\s\S]*<h2 class="ana-metric-value"/);
    assert.match(styles, /\.fb-summary-card\s*\{[^}]*display:\s*block;[^}]*padding:\s*16px 17px;[^}]*border-radius:\s*16px;[^}]*box-shadow:\s*0 6px 20px rgba\(15, 23, 42, \.05\);[^}]*transition:\s*\.15s ease/s);
    assert.match(styles, /\.fb-summary-card \.ana-metric-icon\s*\{[^}]*width:\s*40px;[^}]*height:\s*40px;[^}]*margin:\s*0 0 12px;[^}]*border-radius:\s*12px;[^}]*font-size:\s*19px/s);
    assert.match(styles, /\.fb-summary-card \.ana-metric-value\s*\{[^}]*font-size:\s*27px;[^}]*font-weight:\s*900;/s);
    assert.match(analyticsStyles, /\.ana-metric:hover\s*\{[^}]*transform:\s*translateY\(-3px\);[^}]*border-color:\s*#bfdbfe;[^}]*box-shadow:\s*0 12px 24px rgba\(15,23,42,\.08\)/s);
    assert.doesNotMatch(styles, /\.fb-summary-card:hover/);
});

test('The filter toolbar is integrated into the Analytics-style workspace', () => {
    assert.match(dynamic, /id="feedbackDynamicRegion" class="ana-page app-page-shell"/);
    assert.match(dynamic, /<header class="ana-head"><div class="ana-head-left">/);
    assert.match(dynamic, /class="ana-title">Feedback/);
    assert.match(dynamic, /class="ana-subtitle"/);
    assert.match(dynamic, /class="fb-feedback-shell"/);
    assert.match(filters, /id="feedbackFilterForm" class="fb-filter-toolbar"/);
    assert.doesNotMatch(filters, /class="ana-toolbar fb-filter-toolbar"/);
    assert.equal((filters.match(/class="ana-flabel"/g) || []).length, 6);
    assert.equal((filters.match(/class="ana-input"/g) || []).length, 6);
    assert.match(filters, />Analysis Period</);
    assert.match(styles, /\.fb-filter-toolbar\s*\{[^}]*border:\s*1px solid var\(--ana-slate-200\)[^}]*border-radius:\s*15px[^}]*background:\s*#fff/s);
    assert.doesNotMatch(styles, /:root|--fb-/);
});

test('Feedback preserves the selected-training master-detail hierarchy without an accordion', () => {
    assert.match(workspace, /class="fb-workspace"/);
    assert.match(workspace, /fb-training-navigator/);
    assert.match(workspace, /data-feedback-select-training/);
    assert.match(workspace, /aria-selected=/);
    assert.match(workspace, /feedback\.partials\.training-detail/);
    assert.doesNotMatch(workspace + script, /expanded_course|data-feedback-expand|View feedback|Hide feedback/);
    assert.equal((script.match(/document\.addEventListener\('click'/g) || []).length, 1);
    assert.equal((script.match(/DOMContentLoaded/g) || []).length, 1);
});

test('Training navigator reuses the Attendance collapse controls and accessibility state', () => {
    assert.match(workspace, /class="fb-navigator-expand"[\s\S]*aria-label="Expand training list"[\s\S]*bi-list-ul/);
    assert.match(workspace, /class="fb-navigator-collapse"[\s\S]*aria-label="Collapse training list"[\s\S]*bi-layout-sidebar-inset/);
    assert.equal((workspace.match(/data-feedback-training-list-toggle/g) || []).length, 2);
    assert.match(script, /const setTrainingNavigatorCollapsed = \(collapsed, moveFocus = true\)/);
    assert.match(script, /toggleAttribute\('inert', trainingNavigatorCollapsed\)/);
    assert.match(script, /setAttribute\('aria-hidden', trainingNavigatorCollapsed \? 'true' : 'false'\)/);
    assert.match(script, /button\.setAttribute\('aria-expanded', trainingNavigatorCollapsed \? 'false' : 'true'\)/);
    assert.match(script, /\.fb-navigator-expand' : '\.fb-navigator-collapse'\)\?\.focus/);
});

test('Training navigator motion and compact rail match Trainer Attendance', () => {
    assert.match(styles, /\.fb-training-navigator\s*\{[^}]*position:\s*sticky;[^}]*top:\s*78px;[^}]*width:\s*360px;[^}]*border-radius:\s*14px;[^}]*box-shadow:\s*0 6px 20px rgba\(15, 23, 42, \.05\);[^}]*transition:\s*width \.18s ease, border-color \.18s ease, box-shadow \.18s ease/s);
    assert.match(styles, /\.fb-training-navigator\.is-collapsed\s*\{[^}]*width:\s*44px;[^}]*border-color:\s*transparent;[^}]*background:\s*transparent;[^}]*box-shadow:\s*none/s);
    assert.match(styles, /\.fb-navigator-expanded\s*\{[^}]*opacity:\s*1;[^}]*visibility:\s*visible;[^}]*transform:\s*translateX\(0\);[^}]*visibility 0s linear 0s/s);
    assert.match(styles, /\.fb-training-navigator\.is-collapsed \.fb-navigator-expanded\s*\{[^}]*opacity:\s*0;[^}]*visibility:\s*hidden;[^}]*translateX\(-6px\);[^}]*visibility 0s linear \.18s/s);
    assert.match(styles, /\.fb-navigator-collapsed\s*\{[^}]*translateX\(6px\);[^}]*visibility 0s linear \.18s/s);
    assert.match(styles, /\.fb-navigator-expand\s*\{[^}]*width:\s*44px;[^}]*height:\s*42px;[^}]*border-radius:\s*10px/s);
    assert.match(attendanceStyles, /\.lw-sb-collapsed-btn\s*\{[^}]*width:\s*44px;[^}]*height:\s*42px;[^}]*border-radius:\s*10px/s);
    assert.match(styles, /\.fb-training-navigator \.ana-panel-head\s*\{[^}]*padding:\s*14px 16px;[^}]*border-bottom:\s*1px solid var\(--ana-slate-100\)/s);
    assert.match(styles, /\.fb-training-navigator \.ana-panel-head h2\s*\{[^}]*font-size:\s*13px;[^}]*font-weight:\s*900/s);
    assert.match(styles, /prefers-reduced-motion:\s*reduce/);
});

test('Training rows wrap long names and expose a restrained selected state', () => {
    assert.match(styles, /\.fb-training-row-copy strong\s*\{[^}]*overflow-wrap:\s*anywhere;[^}]*white-space:\s*normal;/s);
    assert.match(styles, /\.fb-training-row\s*\{[^}]*min-width:\s*0;/s);
    assert.match(styles, /\.fb-training-row\.is-selected\s*\{[^}]*border-left-color:[^}]*background:/s);
    assert.match(workspace, /aria-selected=/);
    assert.match(workspace, /average rating out of 5/);
    assert.match(styles, /\.fb-training-row\.is-selected\s*\{[^}]*border-left-color:\s*#4f46e5;[^}]*background:\s*#eef2ff;/s);
    assert.doesNotMatch(workspace + detail, /course_category|Teacher Training/);
});

test('Training navigator exposes every result through internal scrolling without pagination', () => {
    assert.match(workspace, /\$trainingResultCount/);
    assert.match(workspace, /with feedback/);
    assert.doesNotMatch(workspace + script + styles, /data-feedback-training-pagination|data-feedback-training-rows|data-feedback-training-page|fb-training-pagination/);
    assert.doesNotMatch(workspace, />Rows<|>Previous<|>Next<|Showing \{\{/);
    assert.doesNotMatch(feedbackService, /ROW_OPTIONS|paginateCollection|'rows'\s*=>|'page'\s*=>/);
    assert.match(styles, /\.fb-training-list\s*\{[^}]*min-height:\s*0;[^}]*flex:\s*1 1 auto;[^}]*overflow-x:\s*hidden;[^}]*overflow-y:\s*auto;[^}]*overscroll-behavior:\s*contain/s);
    assert.match(styles, /\.fb-training-list::\-webkit-scrollbar\s*\{[^}]*width:\s*6px/s);
});

test('Feedback navigator follows Submission Tasks content-aware capped-height pattern', () => {
    assert.match(submissionStyles, /\.lw-sub-grid\s*\{[^}]*display:\s*flex;[^}]*align-items:\s*flex-start;/s);
    assert.match(submissionStyles, /\.lw-inbox\s*\{[^}]*max-height:\s*calc\(100vh - 102px\);[^}]*align-self:\s*flex-start;[^}]*position:\s*sticky;[^}]*top:\s*78px/s);
    assert.match(submissionStyles, /\.lw-inbox-expanded\s*\{[^}]*max-height:\s*inherit;[^}]*min-height:\s*0;[^}]*display:\s*flex;[^}]*flex-direction:\s*column;[^}]*overflow:\s*hidden/s);
    assert.match(styles, /\.fb-workspace\s*\{[^}]*min-height:\s*0;[^}]*display:\s*flex;[^}]*align-items:\s*flex-start;/s);
    assert.doesNotMatch(styles, /\.fb-workspace\s*\{[^}]*height:\s*clamp|\.fb-workspace\s*\{[^}]*align-items:\s*stretch/s);
    assert.match(styles, /\.fb-training-navigator\s*\{[^}]*max-height:\s*calc\(100vh - 102px\);[^}]*align-self:\s*flex-start;[^}]*display:\s*flex;[^}]*flex-direction:\s*column;[^}]*overflow:\s*hidden;/s);
    assert.match(styles, /\.fb-navigator-expanded\s*\{[^}]*min-height:\s*0;[^}]*max-height:\s*inherit;[^}]*flex:\s*1 1 auto;[^}]*display:\s*flex;[^}]*flex-direction:\s*column;[^}]*overflow:\s*hidden;/s);
    assert.ok(workspace.indexOf('class="ana-panel-head"') < workspace.indexOf('class="fb-training-list"'));
    assert.match(styles, /\.fb-training-list\s*\{[^}]*min-height:\s*0;[^}]*display:\s*block;[^}]*flex:\s*1 1 auto;[^}]*overflow-x:\s*hidden;[^}]*overflow-y:\s*auto;[^}]*scrollbar-gutter:\s*stable;/s);
    assert.doesNotMatch(styles, /\.fb-training-list\s*\{[^}]*display:\s*grid;/s);
    assert.match(styles, /\.fb-training-row\s*\{[^}]*height:\s*auto;[^}]*display:\s*flex;[^}]*flex:\s*0 0 auto;/s);
    assert.doesNotMatch(styles, /\.fb-training-row\s*\{[^}]*flex:\s*(1|auto)|\.fb-training-row\s*\{[^}]*height:\s*100%/s);
    assert.match(styles, /\.fb-training-detail\s*\{[^}]*flex:\s*1 1 auto;[^}]*overflow:\s*hidden;/s);
    assert.doesNotMatch(styles, /\.fb-training-detail\s*\{[^}]*overflow-y:\s*auto|\.fb-training-detail::\-webkit-scrollbar/s);
    assert.doesNotMatch(styles, /\.fb-comments-section\s*\{[^}]*overflow-y:/s);
    assert.match(styles, /@media \(max-width: 900px\)[\s\S]*\.fb-workspace\s*\{[^}]*flex-direction:\s*column/s);
    assert.match(styles, /@media \(max-width: 900px\)[\s\S]*\.fb-training-navigator,[^}]*position:\s*static;[^}]*max-height:\s*none/s);
});

test('Question selector renders one detail and comments use asynchronous Show more batches', () => {
    assert.match(detail, /id="feedbackQuestionSelector"/);
    assert.match(detail, /data-feedback-question-selector/);
    assert.match(detail, /Question \{\{ \$feedbackQuestions->search/);
    assert.match(detail, />Question Analysis</);
    assert.match(question, /\$selectedQuestion\['question'\]/);
    assert.match(question, /class="fb-question-prompt"/);
    assert.equal((question.match(/class="fb-question-detail"/g) || []).length, 1);
    assert.doesNotMatch(question, /@foreach \(\$feedbackQuestions/);
    assert.match(question, /Written Comments/);
    assert.match(comments, /Show more comments/);
    assert.match(script, /feedback_fragment', 'comments'/);
    assert.doesNotMatch(comments, /Rows|Previous|Next|Showing/);
    assert.match(comments, /No written comments for this question\./);
    assert.match(comments, /No comments match your search\./);
    assert.doesNotMatch(comments, /bi-quote/);
});

test('Rating rows and anonymous comments retain readable accessible semantics', () => {
    assert.match(question, /aria-label="\{\{ \$rating \}\} stars:/);
    assert.match(question, /data-width="\{\{ \$percentage \}\}"/);
    assert.match(comments, /<article class="fb-comment-row">[\s\S]*\$comment\['comment'\][\s\S]*<time>/);
    assert.doesNotMatch(comments, /participant|respondent|anonymous_code/i);
    assert.match(styles, /\.fb-comment-row p\s*\{[^}]*font-size:\s*13\.5px;[^}]*line-height:\s*1\.65;[^}]*overflow-wrap:\s*anywhere;/s);
    assert.match(styles, /\.fb-score-summary strong\s*\{[^}]*color:\s*#3b82f6/s);
    assert.match(styles, /\.fb-rating-fill\s*\{[^}]*background:\s*#60a5fa/s);
    assert.match(styles, /\.fb-rating-bar > span:first-child i\s*\{[^}]*color:\s*#d97706/s);
});

test('Loading and responsive rules stay localized below the stable toolbar', () => {
    assert.match(styles, /#feedbackResultsRegion\.is-loading[^}]*opacity:/s);
    assert.match(styles, /\.fb-training-detail\.is-loading[^}]*opacity:/s);
    assert.doesNotMatch(styles, /\.fb-filter-toolbar\.is-loading|\.fb-page\.is-loading/);
    assert.match(styles, /@media \(max-width: 1100px\)[\s\S]*\.fb-filter-grid\s*\{[^}]*repeat\(2/s);
    assert.match(styles, /@media \(max-width: 900px\)[\s\S]*\.fb-summary-grid\s*\{[^}]*repeat\(2/s);
    assert.match(styles, /@media \(max-width: 900px\)[\s\S]*\.fb-workspace\s*\{[^}]*flex-direction:\s*column/s);
    assert.match(styles, /@media \(max-width: 900px\)[\s\S]*\.fb-training-navigator,[^}]*position:\s*static[^}]*width:\s*100%/s);
    assert.match(styles, /@media \(max-width: 900px\)[\s\S]*\.fb-navigator-collapsed,[\s\S]*\.fb-navigator-collapse\s*\{[^}]*display:\s*none/s);
    assert.match(styles, /@media \(max-width: 575px\)[\s\S]*\.fb-filter-grid,[^}]*grid-template-columns:\s*1fr/s);
    assert.match(styles, /@media \(max-width: 575px\)[\s\S]*\.fb-question-result\s*\{[^}]*grid-template-columns:\s*1fr/s);
});
