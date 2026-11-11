import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const main = read('../../resources/views/trainer/training/submission.blade.php');
const inbox = read('../../resources/views/trainer/training/submission/partials/task-inbox.blade.php');
const workspace = read('../../resources/views/trainer/training/submission/partials/workspace.blade.php');
const results = read('../../resources/views/trainer/training/submission/partials/roster-results.blade.php');
const modal = read('../../resources/views/trainer/training/submission/partials/file-preview-modal.blade.php');
const styles = read('../../resources/views/trainer/training/submission/partials/styles.blade.php');
const scripts = read('../../resources/views/trainer/training/submission/partials/scripts.blade.php');
const header = read('../../resources/views/trainer/training/partials/workspace-header.blade.php');
const paginator = read('../../resources/views/trainer/training/partials/report-paginator.blade.php');
const service = read('../../app/Services/Lms/TrainerLmsSubmissionReportService.php');
const provider = read('../../app/Providers/AppServiceProvider.php');
const learnerContent = read('../../resources/views/trainer/training/learner-tracking/partials/content.blade.php');

test('Submission Tasks is a scalable sticky navigator with one compact collapse control', () => {
    assert.match(styles, /\.lw-inbox\s*\{[^}]*max-height:\s*calc\(100vh[^}]*position:\s*sticky[^}]*top:/s);
    assert.match(styles, /\.lw-inbox-expanded\s*\{[^}]*min-height:\s*0[^}]*flex-direction:\s*column[^}]*overflow:\s*hidden/s);
    assert.match(styles, /\.lw-inbox-list\s*\{[^}]*flex:\s*1 1 auto[^}]*min-height:\s*0[^}]*overflow-y:\s*auto/s);
    assert.match(inbox, /aria-label="Show submission tasks"[^>]*title="Show submission tasks"/s);
    assert.equal((inbox.match(/aria-label="Show submission tasks"/g) || []).length, 1);
    assert.doesNotMatch(inbox, /writing-mode|rotate\(|Calendar View/i);
    assert.match(styles, /\.lw-inbox-expanded\s*\{[^}]*opacity:\s*1[^}]*visibility:\s*visible[^}]*transform:\s*translateX\(0\)[^}]*transition:[^}]*opacity[^}]*transform[^}]*visibility/s);
    assert.match(styles, /\.lw-inbox\.is-collapsed \.lw-inbox-expanded\s*\{[^}]*opacity:\s*0[^}]*visibility:\s*hidden[^}]*translateX\(-6px\)[^}]*visibility 0s linear \.18s/s);
    assert.doesNotMatch(styles, /\.lw-inbox\.is-collapsed \.lw-inbox-expanded\s*\{[^}]*display:\s*none/s);
    assert.match(scripts, /toggleAttribute\('inert', collapsed\)/);
    assert.match(styles, /prefers-reduced-motion:\s*reduce/);
    assert.match(styles, /@media \(max-width:\s*1024px\)[\s\S]*?\.lw-inbox[^}]*position:\s*static[^}]*max-height:\s*none[\s\S]*?\.lw-inbox-list[^}]*overflow-y:\s*visible/s);
});

test('task search stays mounted, has one custom clear, and provides both empty states', () => {
    assert.match(inbox, /input type="search"[^>]*data-task-search[^>]*placeholder="Search tasks\.\.\."/s);
    assert.equal((inbox.match(/data-task-search-clear/g) || []).length, 1);
    assert.match(styles, /\.lw-task-search input\[type="search"\]::\-webkit-search-cancel-button/);
    assert.match(inbox, /No submission tasks match your search\./);
    assert.match(inbox, /No submission tasks are available for this training\./);
    assert.match(scripts, /input\.value = ''; input\.focus\(\); filterTasks\(\)/);
});

test('task selection and roster reporting use narrow async regions with history restoration', () => {
    assert.match(main, /partials\.task-inbox/);
    assert.match(main, /partials\.workspace/);
    assert.match(workspace, /id="lmsSubmissionWorkspace"/);
    assert.match(results, /id="lmsSubmissionRosterResults"/);
    assert.match(scripts, /new AbortController\(\)/);
    assert.match(scripts, /requestNumber/);
    assert.match(scripts, /data-task-link[\s\S]*loadReport\(stateUrl\(\{assessment_id: taskLink\.dataset\.assessmentId\}\), 'workspace', 'push'\)/);
    assert.match(scripts, /history\.pushState/);
    assert.match(scripts, /history\.replaceState/);
    assert.match(scripts, /popstate/);
    assert.match(scripts, /__lmsSubmissionsInitialized/);
    assert.doesNotMatch(scripts, /window\.location\s*=|location\.reload/);
});

test('roster uses normal page flow, server pagination, stable search, and clear empty states', () => {
    assert.match(styles, /\.lw-table-wrap\s*\{[^}]*overflow-x:\s*auto[^}]*overflow-y:\s*visible/s);
    assert.doesNotMatch(styles, /\.lw-table-wrap\s*\{[^}]*overflow-y:\s*auto/s);
    assert.doesNotMatch(styles, /\.lw-detail\s*\{[^}]*max-height:\s*calc/s);
    assert.match(results, /partials\.report-paginator/);
    assert.match(paginator, /\[10, 25, 50\]/);
    assert.match(workspace, /input type="search"[^>]*data-submission-search[^>]*placeholder="Search participant\.\.\."/s);
    assert.equal((workspace.match(/data-submission-search-clear/g) || []).length, 1);
    assert.match(styles, /\.lw-roster-search input\[type="search"\]::\-webkit-search-cancel-button/);
    assert.match(scripts, /setTimeout\(\(\) => loadReport\(stateUrl\(\), 'results', 'replace'\), 300\)/);
    assert.match(results, /No participants match these filters\./);
    assert.match(results, /No participants are available for this task\./);
});

test('task availability and whole-task status chips remain explicit', () => {
    for (const status of ['Not Open Yet', 'Closed', 'Open']) assert.match(service, new RegExp(status));
    assert.match(inbox, /display_state/);
    assert.match(inbox, /display_state_icon/);
    assert.match(workspace, /display_state/);
    assert.match(workspace, /display_state_message/);
    assert.match(styles, /\.lw-task-state\.is-open\s*\{[^}]*#047857[^}]*#ecfdf5[^}]*#a7f3d0/s);
    assert.match(styles, /\.lw-task-state\.is-closed\s*\{[^}]*#b91c1c[^}]*--lw-red-soft[^}]*#fecaca/s);
    assert.match(styles, /\.lw-task-state\.is-not-open\s*\{[^}]*#b45309[^}]*--lw-amber-soft[^}]*#fde68a/s);
    assert.match(styles, /\.lw-inbox-type-row\s*\{[^}]*display:\s*grid[^}]*grid-template-columns:\s*minmax\(0, 1fr\) auto/s);
    for (const status of ['All', 'Need Review', 'Reviewed', 'Awaiting', 'Late']) assert.match(workspace, new RegExp(status));
    assert.match(workspace, /data-submission-count/);
});

test('review actions are asynchronous and update counts plus the shared pending badge', () => {
    assert.match(results, /class="review-form"/);
    assert.match(results, /@csrf/);
    assert.match(results, /Mark Reviewed/);
    assert.match(results, /Undo Review/);
    assert.match(scripts, /fetch\(form\.action/);
    assert.match(scripts, /updateCounts\(data\.counts\)/);
    assert.match(scripts, /updatePendingBadge\(data\.pending_count\)/);
    assert.match(scripts, /form\.classList\.add\('is-pending'\)/);
    assert.match(scripts, /updateReviewRow\(row, data\.status\)/);
    assert.match(scripts, /loadReport\(cleanUrl\(window\.location\.href\), 'results', 'none', false\)/);
    assert.doesNotMatch(scripts, /results\.innerHTML\s*=\s*['"]{2}|target\.innerHTML\s*=\s*['"]{2}/);
    assert.doesNotMatch(scripts, /location\.reload|window\.location\s*=/);
    assert.match(header, /data-lms-submissions-pending/);
    assert.match(header, /data-lms-submissions-pending-count/);
    const identityBadges = header.match(/<div class="lw-meta">([\s\S]*?)<\/div>/)?.[1] || '';
    assert.doesNotMatch(identityBadges, /pending/i);
    assert.match(provider, /TrainerLmsSubmissionReportService/);
    assert.match(provider, /View::composer\('trainer\.training\.partials\.workspace-header'/);
    assert.doesNotMatch(header, /DB::table|Facades\\DB/);
    assert.doesNotMatch(learnerContent, /pendingSubmissionCount|assessment_submission\.status/);
});

test('preview, authorized download, and missing-file presentation remain available', () => {
    assert.match(results, /data-file-preview/);
    assert.match(results, /data-preview-url/);
    assert.match(results, /data-download-url/);
    assert.match(results, /File unavailable/);
    assert.match(modal, /Preview opens inline/);
    assert.match(modal, /filePreviewDownloadLink/);
    assert.match(modal, /filePreviewOpenLink/);
    assert.match(modal, /data-file-preview-close/);
    assert.match(modal, /This submission file could not be found in storage\./);
    assert.match(modal, /data-file-preview-error hidden/);
    assert.match(modal, /id="filePreviewOpenLink"/);
    assert.match(modal, /id="filePreviewDownloadLink"/);
    assert.match(scripts, /filePreviewOpenLink'\)\.href = previewUrl/);
    assert.match(scripts, /filePreviewDownloadLink'\)\.href = button\.dataset\.downloadUrl/);
    assert.match(scripts, /unavailable\.hidden = true/);
    assert.doesNotMatch(scripts, /fetch\(previewUrl|FileReader|readAsDataURL/);
    assert.match(scripts, /previewOpener\?\.focus\(\)/);
});

test('file preview actions wrap inside narrow and zoomed-in modal viewports', () => {
    assert.match(modal, /class="lw-modal-actions"/);
    assert.match(styles, /\.lw-modal-box\s*\{[^}]*height:\s*min\(94vh, calc\(100dvh - 40px\)\)/s);
    assert.match(styles, /\.lw-modal-actions\s*\{[^}]*flex-wrap:\s*wrap/s);
    assert.match(styles, /@media \(max-width: 479\.98px\)[\s\S]*?\.lw-modal-actions\s*\{[^}]*grid-template-columns:\s*1fr/s);
    assert.doesNotMatch(modal, /style="display:\s*flex;\s*gap:/);
});
