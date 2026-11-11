import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const page = read('../../resources/views/trainer/training/proposal.blade.php');
const content = read('../../resources/views/trainer/training/proposal/partials/content.blade.php');
const stats = read('../../resources/views/trainer/training/proposal/partials/stats.blade.php');
const form = read('../../resources/views/trainer/training/proposal/partials/form.blade.php');
const list = read('../../resources/views/trainer/training/proposal/partials/list.blade.php');
const results = read('../../resources/views/trainer/training/proposal/partials/results.blade.php');
const detail = read('../../resources/views/trainer/training/proposal/partials/detail.blade.php');
const styles = read('../../resources/views/trainer/training/proposal/partials/styles.blade.php');
const scripts = read('../../resources/views/trainer/training/proposal/partials/scripts.blade.php');

test('Proposal summary uses exactly four standard Dashboard metric cards with semantic icons', () => {
    assert.equal((stats.match(/data-proposal-metric=/g) || []).length, 4);
    for (const metric of ['total', 'pending', 'approved', 'rejected']) {
        assert.match(stats, new RegExp(`data-proposal-metric="${metric}"`));
    }
    for (const tone of ['blue', 'amber', 'green', 'red']) {
        assert.match(stats, new RegExp(`pp-stat-icon-${tone}`));
    }
    assert.match(styles, /\.pp-stat-card\s*\{[^}]*padding:\s*16px 17px[^}]*background:\s*#fff[^}]*border-radius:\s*16px/s);
    assert.match(styles, /\.pp-stat-icon\s*\{[^}]*width:\s*40px[^}]*height:\s*40px[^}]*margin-bottom:\s*12px[^}]*border-radius:\s*12px[^}]*font-size:\s*19px/s);
    assert.match(styles, /\.pp-stat-label\s*\{[^}]*font-size:\s*11px[^}]*font-weight:\s*850/s);
    assert.match(styles, /\.pp-stat-value\s*\{[^}]*font-size:\s*27px[^}]*font-weight:\s*900/s);
    assert.doesNotMatch(stats, /db-next|gradient|promotional/i);
});

test('Proposal preserves heading, metrics, form, then history hierarchy', () => {
    const statsIndex = content.indexOf("partials.stats");
    const formIndex = content.indexOf("partials.form");
    const listIndex = content.indexOf("partials.list");
    assert.ok(content.indexOf('Training Proposal') < statsIndex);
    assert.ok(statsIndex < formIndex);
    assert.ok(formIndex < listIndex);
    assert.doesNotMatch(content, /workspace-columns|wizard/i);
});

test('Proposal form stays outside the asynchronously replaced history results region', () => {
    assert.match(form, /id="proposalSubmissionForm"/);
    assert.match(list, /id="proposalReportFilters"/);
    assert.match(list, /partials\.results/);
    assert.match(results, /id="proposalResultsRegion"/);
    assert.doesNotMatch(results, /proposalSubmissionForm|proposalSearch|proposalReportFilters/);
    assert.match(scripts, /current\.replaceWith\(next\)/);
    assert.doesNotMatch(scripts, /proposalSubmissionForm[^\n]*replace|reportForm\.replaceWith/);
    assert.doesNotMatch(form, /course_category|Course Category/);
    assert.doesNotMatch(list, /proposalCategory|All Categories/);
    assert.doesNotMatch(results, /course_category|Uncategorized/);
    assert.doesNotMatch(detail, />Category</);
});

test('My Proposals is a compact structured list and the giant always-expanded card is gone', () => {
    assert.match(results, /data-compact-proposal-list/);
    assert.match(results, /class="pp-proposal-entry/);
    assert.match(results, /pp-proposal-summary/);
    assert.doesNotMatch(results, /class="proposal-card|proposal-meta-grid|proposal-card-foot/);
    assert.match(results, /\@if \(\$isExpanded\)[\s\S]*partials\.detail/);
    assert.match(detail, /Training Objective/);
    assert.match(detail, /Description \/ Justification/);
    assert.match(detail, /Admin Review/);
});

test('Only one proposal detail is selected through URL and history state', () => {
    assert.match(results, /proposal_id/);
    assert.match(results, /View details/);
    assert.match(results, /Hide details/);
    assert.match(results, /aria-expanded/);
    assert.match(detail, /data-expanded-proposal/);
    assert.match(scripts, /history\.pushState/);
    assert.match(scripts, /popstate/);
    assert.match(scripts, /syncControlsFromUrl/);
});

test('Proposal pagination is server-backed, supports only 10 25 50, and remains for one-page results', () => {
    assert.match(results, /\$proposalPaginator->total\(\) === 0/);
    assert.match(results, /data-proposal-paginator/);
    assert.doesNotMatch(results, /total\(\) > 10|hasMorePages\(\).*data-proposal-paginator/s);
    assert.match(results, /\[10, 25, 50\]/);
    assert.match(results, /Previous/);
    assert.match(results, /Next/);
    assert.match(results, /Showing/);
    assert.match(scripts, /\[data-proposal-rows\]/);
    assert.doesNotMatch(scripts, /PAGE_SIZE|\.slice\(/);
});

test('Proposal search is debounced, abortable, caret-safe, and has a scoped clear control', () => {
    assert.match(scripts, /searchDebounceMs\s*=\s*300/);
    assert.match(scripts, /setTimeout/);
    assert.match(scripts, /AbortController/);
    assert.match(scripts, /requestSequence/);
    assert.match(scripts, /history\.replaceState/);
    assert.match(list, /data-proposal-search-clear/);
    assert.match(scripts, /searchInput\(\)\.value\s*=\s*''/);
    assert.match(scripts, /searchInput\(\)\.focus\(\)/);
    assert.doesNotMatch(scripts, /selectionStart|setSelectionRange|searchInput\(\)\.replaceWith/);
});

test('Reset Filters is governed only by non-search filters and resets the approved state set', () => {
    assert.match(list, /\$nonSearchFiltersActive/);
    assert.match(scripts, /const nonSearchActive = \(statusInput/);
    assert.doesNotMatch(scripts.match(/const nonSearchActive[\s\S]*?;/)?.[0] || '', /searchInput/);
    assert.match(scripts, /statusInput\(\)\.value = 'all'/);
    assert.match(scripts, /periodInput\(\)\.value = 'all'/);
    assert.match(scripts, /fromInput\(\)\.value = ''/);
    assert.match(scripts, /toInput\(\)\.value = ''/);
});

test('Custom submitted dates wait for two valid inclusive endpoints and remain URL-backed', () => {
    assert.match(list, /Submitted Period/);
    assert.match(list, /Last 30 days/);
    assert.match(list, /Last 3 months/);
    assert.match(list, /Last year/);
    assert.match(list, /name="submitted_from"/);
    assert.match(list, /name="submitted_to"/);
    assert.match(scripts, /customDatesReady/);
    assert.match(scripts, /from !== '' && to !== '' && from <= to/);
    assert.match(scripts, /if \(customDatesReady\(\)\) requestFromControls/);
    assert.match(scripts, /submitted_from/);
    assert.match(scripts, /submitted_to/);
});

test('Async initialization is guarded and loading stays localized to proposal results', () => {
    assert.match(scripts, /window\.__trainerProposalPageInitialized/);
    assert.equal((scripts.match(/document\.addEventListener\('click'/g) || []).length, 1);
    assert.equal((scripts.match(/document\.addEventListener\('change'/g) || []).length, 1);
    assert.match(scripts, /resultsRegion/);
    assert.match(scripts, /aria-busy/);
    assert.match(styles, /\.pp-results-region\.is-loading/);
    assert.doesNotMatch(scripts, /document\.body\.classList.*loading|proposalSubmissionForm.*disabled/);
    assert.doesNotMatch(scripts, /window\.location\.(assign|replace)|window\.location\.href\s*=/);
});

test('Proposal layout has explicit tablet and mobile structural behavior without horizontal scrolling', () => {
    assert.match(styles, /@media \(max-width: 991\.98px\)/);
    assert.match(styles, /@media \(max-width: 767\.98px\)/);
    assert.match(styles, /\.pp-proposal-summary\s*\{[^}]*grid-template-columns:\s*1fr/s);
    assert.match(styles, /\.pp-form-grid[^}]*grid-template-columns:\s*1fr/s);
    assert.doesNotMatch(styles, /min-width:\s*[8-9]\d\dpx|overflow-x:\s*scroll/);
    assert.match(page, /trainer\.layouts\.app/);
});
