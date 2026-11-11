import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const materialBlade = readFileSync(
    new URL('../../resources/views/trainer/training/material.blade.php', import.meta.url),
    'utf8',
);
const authoringStyles = readFileSync(
    new URL('../../resources/views/trainer/training/partials/material-authoring-styles.blade.php', import.meta.url),
    'utf8',
);
const authoringScripts = readFileSync(
    new URL('../../resources/views/trainer/training/partials/material-authoring-scripts.blade.php', import.meta.url),
    'utf8',
);
const appToastStyles = readFileSync(
    new URL('../../resources/views/components/app-toast-styles.blade.php', import.meta.url),
    'utf8',
);
const blade = [materialBlade, authoringStyles, authoringScripts].join('\n');
const blockPartial = readFileSync(
    new URL('../../resources/views/trainer/training/partials/block.blade.php', import.meta.url),
    'utf8',
);
const assessmentPicker = readFileSync(
    new URL('../../resources/views/trainer/training/partials/assessment-picker.blade.php', import.meta.url),
    'utf8',
);
const controller = readFileSync(
    new URL('../../app/Http/Controllers/CourseController.php', import.meta.url),
    'utf8',
);
const service = readFileSync(
    new URL('../../app/Services/Lms/TrainerLmsMaterialAuthoringService.php', import.meta.url),
    'utf8',
);

test('module cards and builder use one canonical seven-type backend collection', () => {
    for (const type of ['title', 'text', 'material', 'media', 'youtube', 'quiz', 'submission']) {
        assert.match(service, new RegExp(`'${type}'`));
    }
    assert.match(controller, /blocksForModules\(\$modules->pluck\('module_id'\)\)/);
    assert.match(controller, /'block_count' => count\(\$savedBlocks\)/);
    assert.match(blade, /data-module-grid-count/);
    assert.match(blade, /data-block-count-text-for/);
});

test('empty module placeholder reacts to panels without changing canonical counts or dirty state', () => {
    const refresh = authoringScripts.match(
        /function refreshModuleView\(moduleId\)[\s\S]*?function updateModuleGridCount/
    )?.[0] ?? '';
    const snapshot = authoringScripts.match(
        /function snapshotModuleState\(moduleId\)[\s\S]*?function captureOriginalState/
    )?.[0] ?? '';

    assert.match(refresh, /deriveModuleEmptyState/);
    assert.match(refresh, /\.lw-add-panel:not\(\.hidden\)/);
    assert.match(refresh, /const total = workspaceState\.realBlockCount/);
    assert.match(refresh, /workspaceState\.emptyVisible/);
    assert.doesNotMatch(snapshot, /panels:/);
    assert.doesNotMatch(snapshot, /lw-add-panel/);
});

test('module title remains an accessible heading-style input', () => {
    assert.match(blade, /class="lw-editor-title-input"/);
    assert.doesNotMatch(blade, /<input[^>]*autofocus[^>]*class="lw-editor-title-input"/s);
    assert.match(blade, /\.lw-editor-title-input \{[^}]*border: 0;[^}]*font-size: 20px;/s);
    assert.match(blade, /\.lw-editor-title-input:focus \{[^}]*border-bottom-color: var\(--lw-primary\)/s);
    assert.match(blade, /data-module-title-error/);
    assert.match(blade, /Module title is required\./);
    assert.match(blade, /class="lw-editor hidden".*?tabindex="-1"/);

    const modalOpen = blade.match(/function openModuleModal\(id\)[\s\S]*?function openModuleModalFromButton/)?.[0] ?? '';
    assert.match(modalOpen, /const focusTarget = isModuleEditorId\(id\)/);
    assert.doesNotMatch(modalOpen, /isAssessmentEditorId|assessmentModal/);
    assert.match(modalOpen, /focusTarget\.focus\(\{ preventScroll: true \}\)/);
    assert.doesNotMatch(modalOpen, /titleInput\.(focus|select)\(/);
});

test('all seven add-block controls remain and client-created blocks append', () => {
    const toolbar = blade.match(/<div class="lw-toolbar">([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>/)?.[1] ?? '';
    const labels = [...toolbar.matchAll(/<i class="bi [^"]+"><\/i>\s*(Title|Text|Media|YouTube|File|Quiz|Submission)/g)]
        .map((match) => match[1]);
    assert.deepEqual(labels, ['Title', 'Text', 'Media', 'YouTube', 'File', 'Quiz', 'Submission']);
    assert.match(blade, /blockList\.appendChild\(wrapper\)/);
    assert.match(blade, /reserveModuleBlockAppendSlot/);
    assert.match(blade, /moduleBlockAppendPosition/);
    assert.match(blade, /commitModuleBlockAppendSlot/);
    assert.match(blade, /formData\.set\(\s*'block_order'/s);
    assert.match(blade, /previousAdd[^;]*;[\s\S]*now - previousAdd < 400/);
});

test('block and module drag serialization follows current DOM order', () => {
    assert.match(blade, /new Sortable\(blockList/);
    assert.match(blade, /renumberModuleBlockInputs\(form\)/);
    assert.match(blade, /item\.querySelectorAll\('\[name\^="blocks\["\]'/);
    assert.match(blade, /new Sortable\(grid/);
    assert.match(blade, /grid\.querySelectorAll\('\[data-module-card\]'\)/);
    assert.match(blade, /JSON\.stringify\(\{ module_ids: moduleIds \}\)/);
    assert.doesNotMatch(blade, /draggable:\s*'\.lw-module-add'/);
});

test('module operations use localized async state, custom confirmation, and top-right toasts', () => {
    assert.match(blade, /js-ajax-module-create/);
    assert.match(blade, /function attachAjaxModuleCreate\(\)/);
    assert.match(blade, /function requestMaterialsConfirmation\(options\)/);
    assert.match(blade, /id="materialsConfirmModal"/);
    assert.match(blade, /data-confirm-choice="confirm"/);
    assert.match(appToastStyles, /\.app-toast-region\s*\{[\s\S]*?position:\s*fixed;[\s\S]*?top:\s*24px;[\s\S]*?right:\s*24px;/);
    assert.match(materialBlade, /id="materialsValidationToastJson"/);
    assert.match(authoringScripts, /window\.showAppToast\(type, message\)/);
    assert.doesNotMatch(blade, /lwToastBox|lw-toast-box|materialsFlashJson/);
    assert.doesNotMatch(blade, /<div class="lw-flash success">/);

    const moduleDelete = blade.match(/function deleteModuleFromButton[\s\S]*?function performDeleteModule/)?.[0] ?? '';
    const blockDelete = blade.match(/function handleMarkForDelete[\s\S]*?function markBlockForDelete/)?.[0] ?? '';
    assert.doesNotMatch(moduleDelete, /confirm\(/);
    assert.doesNotMatch(blockDelete, /confirm\(/);
});

test('dirty module work is confirmed only when state changed and focus returns', () => {
    assert.match(blade, /if \(isModuleDirty\(moduleId\)\)/);
    assert.match(blade, /title: 'Discard unsaved changes\?'/);
    assert.match(blade, /discardModuleChanges\(moduleId\)/);
    assert.match(blade, /modalTriggers\[id\]/);
    assert.match(blade, /trigger\.focus\(\)/);
    assert.match(blade, /if \(openDialog\.id === 'materialsConfirmModal'\)/);
});

test('authoring dialogs remain reachable on narrow viewports and respect reduced motion', () => {
    assert.match(authoringStyles, /@media \(max-width: 640px\)[\s\S]*?\.lw-modal,[\s\S]*?\.lw-editor\s*\{[^}]*padding:\s*12px/s);
    assert.match(authoringStyles, /max-height:\s*calc\(100dvh - 24px\)/);
    assert.match(authoringStyles, /\.lw-editor-head\s*\{[^}]*flex-wrap:\s*wrap/s);
    assert.match(authoringStyles, /@media \(prefers-reduced-motion: reduce\)/);
});

test('uploads have localized errors and missing stored files have a clear state', () => {
    assert.match(blade, /function setFormInlineError\(form, message\)/);
    assert.match(blade, /submitBtn\.disabled = true/);
    assert.match(blade, /setFormInlineError\(form, message\)/);
    assert.match(blockPartial, /File unavailable/);
    assert.match(blockPartial, /The uploaded media file could not be found\./);
    assert.match(controller, /'max:204800'/);
    assert.match(controller, /allowedUploadedBlockExtensions/);
});

test('module deletion preserves linked assessments while removing owned wrappers transactionally', () => {
    assert.match(controller, /DB::transaction\(function \(\) use[\s\S]*?training_content_block[\s\S]*?->delete\(\)/);
    assert.match(controller, /DB::table\('assessment'\)[\s\S]*?'module_id'\s*=>\s*null/);
    assert.doesNotMatch(controller, /DB::table\('assessment'\)[\s\S]{0,200}->delete\(\)/);
    assert.match(blade, /Linked assessments and learner records are preserved\./);
});

test('YouTube validation is host-scoped and submission closing time must be later', () => {
    assert.match(controller, /\['youtube\.com', 'www\.youtube\.com', 'm\.youtube\.com'\]/);
    assert.match(controller, /\$host === 'youtu\.be'/);
    assert.match(controller, /https:\/\/www\.youtube\.com\/embed\//);
    assert.match(blade, /until\.value <= from\.value/);
});

test('assessment pickers explain module attachment and blocks show canonical availability', () => {
    assert.match(materialBlade, /partials\.assessment-picker/);
    assert.match(assessmentPicker, /Already in this module/);
    assert.match(assessmentPicker, /Attached to: \{\{ \$assessment->attachment_module_title \}\}/);
    assert.match(assessmentPicker, /Not attached/);
    assert.match(assessmentPicker, /data-assessment-option/);
    assert.match(service, /attachment_source/);
    assert.match(service, /content_block/);
    assert.doesNotMatch(service, /attachment_source[\s\S]{0,160}assessment_module_id/);
    assert.match(authoringScripts, /deriveStagedAssessmentAttachment/);
    assert.match(authoringScripts, /recomputeAllAssessmentPickerStates/);
    assert.match(authoringScripts, /stagedRemovedAssessment/);
    assert.match(authoringScripts, /Assessment restored to this module\./);
    assert.match(authoringScripts, /countStagedVisibleBlocks/);
    assert.match(authoringScripts, /updateModuleGridCount\(moduleId, total\)/);
    for (const status of ['always', 'scheduled', 'open', 'closed']) {
        assert.match(authoringStyles, new RegExp(`lw-assessment-state-badge\\.is-${status}`));
    }
    assert.match(blockPartial, /assessment_question_count/);
    assert.match(blockPartial, /assessmentAvailability\['context'\]/);
    assert.match(controller, /assessment-already-embedded/);
});
