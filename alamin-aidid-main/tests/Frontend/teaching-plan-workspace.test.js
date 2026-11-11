import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const readView = (path) => readFileSync(new URL(`../../resources/views/${path}`, import.meta.url), 'utf8');
const card = readView('trainer/teaching-plan/partials/card.blade.php');
const styles = readView('trainer/teaching-plan/partials/styles.blade.php');
const attentionStyles = readView('trainer/teaching-plan/partials/tab-attention-styles.blade.php');
const teachingPlanPage = readView('trainer/teaching-plan/show.blade.php');
const lmsHeader = readView('trainer/training/partials/workspace-header.blade.php');
const simpleHeader = readView('trainer/simple/partials/header.blade.php');
const lmsOverview = readView('trainer/training/workspace-overview.blade.php');
const simpleOverview = readView('trainer/simple/overview/partials/content.blade.php');
const simpleOverviewShell = readView('trainer/simple/overview.blade.php');
const trainerDashboard = readView('trainer/dashboard.blade.php');
const teachingPlanService = readFileSync(new URL('../../app/Services/TeachingPlanService.php', import.meta.url), 'utf8');

test('Teaching Plan is a dedicated tab in both workspaces and no longer renders in Overview', () => {
    assert.match(lmsHeader, /'overview' => 'Overview',\s*'teaching-plan' => 'Teaching Plan',\s*'materials' => 'Materials'/);
    assert.match(simpleHeader, /'overview' => \[.*?\],\s*'teaching-plan' => \[.*?\],\s*'materials' => \[.*?\]/s);
    assert.match(lmsHeader, /trainer\.courses\.teaching-plans\.index/);
    assert.match(simpleHeader, /trainer\.courses\.teaching-plans\.index/);
    assert.match(teachingPlanPage, /activeLmsTab' => 'teaching-plan'/);
    assert.match(teachingPlanPage, /activeSimpleTab' => 'teaching-plan'/);
    assert.doesNotMatch(lmsOverview, /trainer\.teaching-plan\.partials\.(?:card|styles)/);
    assert.doesNotMatch(simpleOverview, /trainer\.teaching-plan\.partials\.card/);
    assert.doesNotMatch(simpleOverviewShell, /trainer\.teaching-plan\.partials\.styles/);
    assert.match(lmsOverview, /Manage Materials/);
    assert.match(lmsOverview, /Review Submissions/);
    assert.match(lmsOverview, /Track Learning/);
    assert.match(lmsOverview, /View Attendance/);
    assert.match(lmsOverview, /View Participants/);
});

test('Teaching Plan tab attention is derived from the current submission state', () => {
    for (const header of [lmsHeader, simpleHeader]) {
        assert.match(header, /'upload-required' => \['tone' => 'required', 'label' => 'Teaching plan upload required', 'text' => 'Required'\]/);
        assert.match(header, /'new-feedback' => \['tone' => 'feedback', 'label' => 'New teaching plan feedback', 'text' => 'New feedback'\]/);
        assert.match(header, /data-teaching-plan-attention/);
        assert.match(header, /aria-label="\{\{ \$teachingPlanAttention\['label'\] \}\}"/);
        assert.match(header, /\{\{ \$teachingPlanAttention\['text'\] \}\}/);
        assert.doesNotMatch(header, /'awaiting-feedback' => \['tone'|'up-to-date' => \['tone'/);
    }
    assert.match(attentionStyles, /\.tp-tab-attention\.required/);
    assert.match(attentionStyles, /\.tp-tab-attention\.feedback/);
    assert.match(attentionStyles, /border-radius:\s*999px/);
});

test('Dedicated Teaching Plan page exposes only current submission, feedback, upload, and removal', () => {
    assert.match(teachingPlanPage, /trainer\.teaching-plan\.partials\.card/);
    assert.match(card, />Teaching Plan</);
    assert.match(card, /Upload your teaching plan for Admin review and feedback\./);
    assert.match(card, /Upload Required/);
    assert.match(teachingPlanService, /'label' => 'Awaiting Admin Feedback'/);
    assert.match(teachingPlanService, /'label' => 'New Feedback'/);
    assert.match(teachingPlanService, /'label' => 'Up to Date'/);
    assert.match(teachingPlanService, /'message' => 'New Admin feedback is available\.'/);
    assert.match(card, /data-teaching-plan-state/);
    assert.match(card, /\$teachingPlanState\['message'\]/);
    assert.match(card, /Current Submission/);
    assert.match(card, /Admin Feedback/);
    assert.match(card, /Awaiting Admin feedback\./);
    assert.match(card, /\$currentComments/);
    assert.match(card, /data-teaching-plan-seen-form/);
    assert.match(card, /Mark as seen/);
    assert.match(card, /trainer\.courses\.teaching-plans\.feedback\.seen/);
    assert.match(card, /Upload Teaching Plan/);
    assert.match(card, /Remove Submission/);
    assert.match(card, /trainer\.courses\.teaching-plans\.destroy/);
    assert.match(card, /Review and mark the latest Admin feedback as seen before replacing this submission/);
    assert.match(card, /data-teaching-plan-remove-blocked/);
    assert.match(card, /data-teaching-plan-remove-form/);
    assert.doesNotMatch(card, /Upload Revised Plan|Previous Submissions|Version \{|teachingPlanHistory|tp-history/);
    assert.doesNotMatch(styles, /tp-history|tp-no-feedback/);
    assert.doesNotMatch(teachingPlanService, /history|version/i);
    assert.match(card, /trainer\.courses\.teaching-plans\.view/);
    assert.match(card, /trainer\.courses\.teaching-plans\.download/);
    assert.doesNotMatch(card, /Approve|Reject|Request Changes|workspace unavailable|disabled Materials/i);
    assert.doesNotMatch(card, /\bEDU\b/i);
    assert.doesNotMatch(teachingPlanService, /\bEDU\b/i);
});

test('Mark as seen uses persisted async action and updates the active Trainer UI without a reload', () => {
    const script = card.match(/<script>([\s\S]*?)<\/script>/)?.[1];

    assert.ok(script);
    assert.doesNotThrow(() => new vm.Script(script));
    assert.match(card, /@method\('PATCH'\)/);
    assert.match(script, /fetch\(form\.action/);
    assert.match(script, /'Accept': 'application\/json'/);
    assert.match(script, /new FormData\(form\)/);
    assert.match(script, /payload\.state\.key/);
    assert.match(script, /indicator\.dataset\.teachingPlanAttention === 'new-feedback'/);
    assert.match(script, /blockedMessage\.hidden = true/);
    assert.match(script, /removeForm\.hidden = false/);
    assert.match(script, /form\.hidden = true/);
});

test('Remove Submission requires confirmation and retains a normal secure form submission', () => {
    const script = card.match(/<script>([\s\S]*?)<\/script>/)?.[1];

    assert.ok(script);
    assert.match(card, /@method\('DELETE'\)/);
    assert.match(script, /window\.confirm\('Remove this Teaching Plan submission and all feedback attached to it\?'\)/);
    assert.match(script, /event\.preventDefault\(\)/);
});

test('Teaching Plan workspace is responsive and static information panels do not lift on hover', () => {
    assert.match(styles, /data-teaching-plan-styles/);
    assert.match(teachingPlanPage, /class="tp-page app-workspace-shell"/);
    assert.doesNotMatch(styles, /\.tp-page\s*\{[^}]*max-width:/);
    assert.match(styles, /\.tp-actions a:focus-visible/);
    assert.match(styles, /\.tp-upload-button:focus-visible/);
    assert.match(styles, /\.tp-file-picker:focus-within/);
    assert.match(styles, /\.tp-remove-button:focus-visible/);
    assert.match(styles, /@media \(max-width: 767\.98px\)/);
    assert.doesNotMatch(styles, /\.tp-(?:workspace|panel|current|feedback-item|upload-panel|history):hover/);
    assert.doesNotMatch(styles, /translateY\(/);
});

test('Teaching Plan custom file selector keeps accessible native selection and clearing behavior', () => {
    const script = card.match(/<script>([\s\S]*?)<\/script>/)?.[1];

    assert.ok(script);
    assert.doesNotThrow(() => new vm.Script(script));
    assert.match(script, /input\.files && input\.files\[0\]/);
    assert.match(script, /input\.value = ''/);
    assert.match(card, /type="file"/);
    assert.match(card, /accept="\.pdf,\.doc,\.docx"/);
    assert.match(card, /required data-teaching-plan-file/);
    assert.match(card, /data-teaching-plan-file-name/);
    assert.match(card, /data-teaching-plan-file-clear/);
    assert.match(card, /No file selected/);
});

test('Trainer Dashboard Teaching Plans KPI remains submitted-per-assigned-training', () => {
    assert.match(trainerDashboard, /Teaching Plans/);
    assert.match(trainerDashboard, /\{\{ \$teachingPlansSubmitted \}\} \/ \{\{ \$totalAssignedCourses \}\}/);
    assert.match(trainerDashboard, /Submitted \/ assigned/);
    assert.doesNotMatch(trainerDashboard, /Participants Enrolled/);
});
