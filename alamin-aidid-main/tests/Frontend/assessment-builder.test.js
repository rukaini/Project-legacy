import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
    addQuestionsToState,
    assessmentCsvTemplate,
    assessmentLoadFailed,
    assessmentLoadSucceeded,
    attemptedQuizMetadataEntries,
    beginAssessmentLoad,
    canAddQuestionBatch,
    CSV_TEMPLATE_COLUMNS,
    deriveAssessmentVisibility,
    deriveAssessmentImportState,
    deriveAuthoringScrollState,
    deriveCustomQuestionMix,
    deriveCatalogState,
    deriveQuestionCollectionState,
    initialAssessmentState,
    clearQuestionsFromState,
    normalizeQuestion,
    normalizeQuestionType,
    normalizeTrueFalseAnswer,
    parseAssessmentCsv,
    questionsForTemplate,
    questionsForCustomMix,
    revealAssessmentLoading,
    startNewAssessment,
    shouldHandleMaterialsSoftNavigation,
    switchAssessmentType,
    updateAssessmentImportRow,
    validateAssessmentCsv,
    validateQuestion,
    validateQuiz,
} from '../../resources/js/assessment-builder.js';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const page = read('../../resources/views/trainer/training/assessment-builder.blade.php');
const workspace = read('../../resources/views/trainer/training/assessment-builder/partials/workspace.blade.php');
const dialogs = read('../../resources/views/trainer/training/assessment-builder/partials/dialogs.blade.php');
const scripts = read('../../resources/views/trainer/training/partials/material-authoring-scripts.blade.php');
const styles = read('../../resources/views/trainer/training/partials/material-authoring-styles.blade.php');
const materials = read('../../resources/views/trainer/training/material.blade.php');
const blockPartial = read('../../resources/views/trainer/training/partials/block.blade.php');
const routes = read('../../routes/web.php');
const controller = read('../../app/Http/Controllers/CourseController.php');
const request = read('../../app/Http/Requests/Trainer/SaveAssessmentRequest.php');
const service = read('../../app/Services/Lms/TrainerLmsMaterialAuthoringService.php');
const assessmentView = [page, workspace, dialogs, scripts, styles].join('\n');

test('Assessment Builder has a dedicated page and Materials contains no assessment workspace', () => {
    assert.match(controller, /view\('trainer\.training\.assessment-builder'/);
    assert.match(routes, /assessments\/builder[\s\S]*trainer\.courses\.assessments\.builder/);
    assert.match(page, /data-lms-authoring-page="assessment"/);
    assert.match(workspace, /id="assessmentWorkspace"[^>]*data-assessment-page/);
    assert.doesNotMatch(materials, /id="assessmentWorkspace"|What are you creating\?/);
    assert.match(materials, /data-create-assessment-link data-materials-soft-link/);
    assert.match(workspace, /assessmentBackToMaterials" data-materials-soft-link/);
    assert.doesNotMatch(workspace, /id="assessmentWorkspace"[^>]*role="dialog"/);
    assert.match(materials, /id="moduleModal/);
});

test('canonical state matrix isolates Quiz, locked Quiz, and Submission', () => {
    const newQuiz = initialAssessmentState();
    assert.deepEqual(deriveAssessmentVisibility(newQuiz), {
        quiz: true, submission: false, quizTools: true, quizSettings: true,
        submissionSettings: false, questionCollection: true, questionRail: true,
        learnerWarning: false, structuralEditing: true, detailLoading: false,
        questionEmpty: true,
    });

    const unlocked = assessmentLoadSucceeded(
        beginAssessmentLoad(newQuiz, 4),
        { assessment_id: 4, type: 'quiz', has_learner_records: false, questions: [{ question_id: 1 }] },
        1,
    );
    assert.equal(deriveAssessmentVisibility(unlocked).structuralEditing, true);
    assert.equal(deriveAssessmentVisibility(unlocked).learnerWarning, false);

    const locked = { ...unlocked, hasLearnerRecords: true };
    assert.equal(deriveAssessmentVisibility(locked).learnerWarning, true);
    assert.equal(deriveAssessmentVisibility(locked).structuralEditing, false);

    for (const state of [initialAssessmentState('submission'), { ...initialAssessmentState('submission'), mode: 'edit', selectedAssessmentId: '8' }]) {
        const visible = deriveAssessmentVisibility(state);
        assert.equal(visible.submissionSettings, true);
        assert.equal(visible.quizTools, false);
        assert.equal(visible.questionRail, false);
        assert.equal(visible.learnerWarning, false);
    }
});

test('new/type/load transitions clear stale learner, loading, question, and selection state', () => {
    const protectedQuiz = {
        ...initialAssessmentState(), mode: 'edit', selectedAssessmentId: '7',
        hasLearnerRecords: true, questions: [{ id: 1 }], dirty: true,
        loadingAssessmentId: '7', loadingVisible: true,
    };
    const fresh = startNewAssessment(protectedQuiz);
    assert.equal(fresh.mode, 'new');
    assert.equal(fresh.selectedAssessmentId, null);
    assert.equal(fresh.hasLearnerRecords, false);
    assert.equal(fresh.loadingAssessmentId, null);
    assert.deepEqual(fresh.questions, []);

    const submission = switchAssessmentType(protectedQuiz, 'submission');
    assert.equal(submission.type, 'submission');
    assert.equal(submission.hasLearnerRecords, false);
    assert.deepEqual(submission.questions, []);

    const loading = beginAssessmentLoad(initialAssessmentState(), 12);
    assert.equal(loading.loadingAssessmentId, '12');
    assert.equal(revealAssessmentLoading(loading, loading.requestToken).loadingVisible, true);
    assert.equal(revealAssessmentLoading(loading, loading.requestToken + 1), loading);
    assert.equal(assessmentLoadFailed(loading, loading.requestToken).mode, 'new');
});

test('catalog empty, selector, and delayed loading states are mutually exclusive', () => {
    const state = initialAssessmentState();
    const catalog = [{ assessment_id: 1, type: 'quiz' }, { assessment_id: 2, type: 'submission' }];
    assert.equal(deriveCatalogState(state, catalog).items.length, 1);
    assert.equal(deriveCatalogState(state, catalog).empty, false);
    assert.equal(deriveCatalogState(state, []).empty, true);
    const loading = revealAssessmentLoading(beginAssessmentLoad(state, 1), 1);
    const derived = deriveCatalogState(loading, []);
    assert.equal(derived.empty, false);
    assert.equal(derived.spinnerVisible, true);
    assert.match(scripts, /setTimeout\(function \(\) \{[\s\S]*\}, 150\)/);
    assert.doesNotMatch(workspace, />\s*Loading\s*</);
});

test('one renderer controls all type-specific sections through hidden state', () => {
    const render = scripts.match(/function renderAssessmentWorkspace\(\)[\s\S]*?function initializeNewAssessment/)?.[0] ?? '';
    assert.match(render, /deriveAssessmentVisibility\(assessmentState\)/);
    assert.match(render, /section\.hidden = !visibility\.quiz/);
    assert.match(render, /section\.hidden = !visibility\.submission/);
    assert.match(render, /sidebar\.hidden = !visibility\.questionRail/);
    assert.match(styles, /\[hidden\] \{ display: none !important; \}/);
    assert.match(workspace, /id="quizScoringSection"[^>]*data-assessment-quiz-section/);
    assert.match(workspace, /id="submissionSection"[^>]*data-assessment-submission-section hidden/);
});

test('question collection empty state follows canonical collection length', () => {
    assert.equal(deriveQuestionCollectionState(initialAssessmentState()).empty, true);
    assert.equal(deriveQuestionCollectionState({ ...initialAssessmentState(), questions: [{}] }).empty, false);
    assert.equal(deriveQuestionCollectionState(initialAssessmentState('submission')).rendered, false);
    assert.match(scripts, /deriveQuestionCollectionState\(assessmentState\)[\s\S]*empty\.hidden = !collection\.empty/);
    assert.match(scripts, /questionSectionHeader/);
    assert.match(scripts, /clearAllQuestions'[\s\S]*Remove all '[\s\S]*replaceChildren\(\)/);
});

test('True/False is canonical and is a first-class collection item', () => {
    assert.equal(normalizeQuestionType('true_false'), 'true_false');
    assert.equal(normalizeQuestionType('True / False'), 'true_false');
    assert.equal(normalizeQuestionType('true-false'), 'true_false');
    assert.equal(normalizeTrueFalseAnswer(' TRUE '), 'True');
    assert.equal(normalizeTrueFalseAnswer('false'), 'False');
    assert.equal(normalizeTrueFalseAnswer('Ture'), null);

    const result = addQuestionsToState(initialAssessmentState(), [{
        question_type: 'true_false', question_text: '', marks: 1,
    }]);
    assert.equal(result.success, true);
    assert.equal(result.addedCount, 1);
    assert.deepEqual(result.state.questions[0].options, ['True', 'False']);
    assert.deepEqual(deriveQuestionCollectionState(result.state), {
        rendered: true,
        empty: false,
        count: 1,
        jumpCount: 1,
        clearAllVisible: true,
        totalMarks: 1,
        incompleteCount: 1,
    });
    const cleared = clearQuestionsFromState(result.state);
    assert.equal(deriveQuestionCollectionState(cleared).count, 0);
    assert.equal(deriveQuestionCollectionState(cleared).empty, true);
    assert.equal(deriveQuestionCollectionState(cleared).clearAllVisible, false);
});

test('MCQ completeness reacts to blank, duplicate, correct, and removed choices', () => {
    const base = { question_type: 'mcq', question_text: '', marks: 0, options: ['', ''], correct_answers: [] };
    assert.equal(validateQuestion(base).complete, false);
    const text = { ...base, question_text: 'Question?', marks: 1 };
    assert.equal(validateQuestion(text).complete, false);
    const choices = { ...text, options: ['One', 'Two'] };
    assert.equal(validateQuestion(choices).complete, false);
    const complete = { ...choices, correct_answers: ['One'] };
    assert.equal(validateQuestion(complete).complete, true);
    assert.equal(validateQuestion({ ...complete, options: ['One', 'Two', ''] }).complete, false);
    assert.equal(validateQuestion({ ...complete, options: ['One', 'Two', 'Three'] }).complete, true);
    assert.equal(validateQuestion({ ...complete, options: ['One', 'Two', 'two'] }).complete, false);
    assert.equal(validateQuestion({ ...complete, correct_answers: ['Removed'] }).complete, false);
});

test('zero marks are valid for every complete question type and defaults are zero', () => {
    const valid = [
        { question_type: 'mcq', question_text: 'Pick', marks: 0, options: ['A', 'B'], correct_answers: ['A'] },
        { question_type: 'checkbox', question_text: 'Pick', marks: 0, options: ['A', 'B'], correct_answers: ['A'] },
        { question_type: 'true_false', question_text: 'True?', marks: 0, correct_answer: 'True' },
        { question_type: 'short_answer', question_text: 'Name', marks: 0, correct_answer: 'Answer' },
        { question_type: 'essay', question_text: 'Explain', marks: 0, grading_rubric: 'Rubric' },
    ];
    valid.forEach((question) => assert.equal(validateQuestion(question).complete, true, question.question_type));
    assert.equal(validateQuestion({ ...valid[0], marks: -1 }).complete, false);
    assert.match(validateQuestion({ ...valid[0], marks: -1 }).errors.join(' '), /cannot be negative/i);
    assert.match(validateQuestion({ ...valid[0], marks: 'abc' }).errors.join(' '), /valid number/i);
    assert.equal(normalizeQuestion({ question_type: 'mcq' }).marks, 0);
    assert.ok(questionsForTemplate('5mcq').every((question) => question.marks === 0));
});

test('Checkbox, True/False, Short Answer, and Essay use strict canonical rules', () => {
    assert.equal(validateQuestion({ question_type: 'checkbox', question_text: 'Pick', marks: 1, options: ['A', 'B'], correct_answers: ['A'] }).complete, true);
    assert.equal(validateQuestion({ question_type: 'checkbox', question_text: 'Pick', marks: 1, options: ['A', ''], correct_answers: ['A'] }).complete, false);
    assert.equal(validateQuestion({ question_type: 'true_false', question_text: 'True?', marks: 1, correct_answer: 'True' }).complete, true);
    assert.equal(validateQuestion({ question_type: 'true_false', question_text: 'False?', marks: 1, correct_answer: 'False' }).complete, true);
    assert.equal(validateQuestion({ question_type: 'true_false', question_text: 'True?', marks: 1 }).complete, false);
    assert.equal(validateQuestion({ question_type: 'true_false', question_text: 'True?', marks: 1, correct_answer: 'Yes' }).complete, false);
    assert.equal(validateQuestion({ question_type: 'true_false', question_text: 'True?', marks: 1, correct_answer: 'True', options: ['True', 'False', 'Maybe'] }).complete, false);
    assert.equal(validateQuestion({ question_type: 'short_answer', question_text: 'Name', marks: 1, correct_answer: 'Answer' }).complete, true);
    assert.equal(validateQuestion({ question_type: 'short_answer', question_text: 'Name', marks: 1, correct_answer: ' ' }).complete, false);
    assert.equal(validateQuestion({ question_type: 'essay', question_text: 'Explain', marks: 2, grading_rubric: 'Rubric' }).complete, true);
    assert.equal(validateQuestion({ question_type: 'essay', question_text: 'Explain', marks: 2 }).complete, false);
    assert.equal(validateQuiz([]).complete, false);
});

test('question card, Jump, Summary, and pre-save all consume the canonical validator', () => {
    assert.match(scripts, /function questionValidationState\(card\)[\s\S]*AssessmentBuilderUtils\.validateQuestion\(data\)/);
    assert.match(scripts, /lw-question-guidance/);
    assert.match(scripts, /updateQuestionJumpState\(card\)/);
    assert.match(scripts, /invalidCards[\s\S]*questionValidationState\(card\)\.complete/);
    assert.match(scripts, /need attention before this quiz can be saved/);
});

test('templates, Custom dialog, import, and 100-question limit are wired', () => {
    for (const template of ['5mcq', '10mcq', '20mcq', 'mixed10', 'tfshort']) {
        assert.match(workspace, new RegExp(`data-quick-template="${template}"`));
    }
    assert.equal(questionsForTemplate('5mcq').length, 5);
    assert.equal(questionsForTemplate('10mcq').length, 10);
    assert.equal(questionsForTemplate('20mcq').length, 20);
    assert.deepEqual(
        questionsForTemplate('mixed10').map((question) => question.question_type),
        [...Array(5).fill('mcq'), ...Array(3).fill('short_answer'), ...Array(2).fill('essay')],
    );
    assert.deepEqual(
        questionsForTemplate('tfshort').map((question) => question.question_type),
        [...Array(5).fill('true_false'), ...Array(5).fill('short_answer')],
    );
    assert.match(dialogs, /id="customTemplateModal"/);
    assert.doesNotMatch(workspace, /id="customTemplateControls"/);
    assert.match(scripts, /openModuleModal\('customTemplateModal'/);
    assert.match(scripts, /return \{ success: created\.length === questions\.length/);
    assert.match(scripts, /if \(!result\.success\) return;[\s\S]*closeModal\('customTemplateModal'\)/);
    assert.equal(canAddQuestionBatch(93, 7), true);
    assert.equal(canAddQuestionBatch(94, 7), false);
    assert.match(workspace, /id="assessmentImportModal"/);
    assert.match(scripts, /lw-import-table/);
});

test('custom batches register all five canonical types with one success contract', () => {
    let state = initialAssessmentState();
    for (const type of ['mcq', 'checkbox', 'true_false', 'short_answer', 'essay']) {
        const result = addQuestionsToState(state, Array(2).fill({ question_type: type }));
        assert.equal(result.success, true, type);
        assert.equal(result.addedCount, 2, type);
        state = result.state;
    }
    assert.equal(deriveQuestionCollectionState(state).count, 10);
    assert.equal(state.questions.filter((question) => question.question_type === 'true_false').length, 2);
    assert.match(scripts, /showToast\(result\.addedCount \+ ' question'/);
    assert.equal(questionsForTemplate('custom', 'true_false', 5).length, 5);
    assert.ok(questionsForTemplate('custom', 'true_false', 5).every((question) => question.question_type === 'true_false'));
});

test('custom question mix creates one deterministic 50-question zero-mark batch', () => {
    const requested = [
        { question_type: 'mcq', count: 20 },
        { question_type: 'checkbox', count: 10 },
        { question_type: 'true_false', count: 10 },
        { question_type: 'short_answer', count: 5 },
        { question_type: 'essay', count: 5 },
    ];
    const mix = deriveCustomQuestionMix(requested, 0);
    assert.equal(mix.valid, true);
    assert.equal(mix.total, 50);
    const questions = questionsForCustomMix(mix.rows);
    assert.equal(questions.length, 50);
    for (const [type, count] of [['mcq', 20], ['checkbox', 10], ['true_false', 10], ['short_answer', 5], ['essay', 5]]) {
        assert.equal(questions.filter((question) => question.question_type === type).length, count);
    }
    assert.ok(questions.every((question) => question.marks === 0));
    assert.equal(deriveCustomQuestionMix([{ type: 'mcq', count: 4 }, { type: 'mcq', count: 6 }]).rows[0].count, 10);
    const overLimit = deriveCustomQuestionMix([{ type: 'true_false', count: 31 }], 70);
    assert.equal(overLimit.valid, false);
    assert.equal(overLimit.remaining, 30);
    assert.ok(overLimit.errors.includes('question_limit'));
    assert.match(dialogs, /id="customMixRows"/);
    assert.match(scripts, /function appendCustomMixRow/);
    assert.match(scripts, /function applyCustomQuestionMix/);
});

test('CSV validates all five types and exposes a spreadsheet preview model', () => {
    const result = validateAssessmentCsv([
        CSV_TEMPLATE_COLUMNS.join(','),
        'mcq,"Question, with comma",1,A,B,C,D,A,,,',
        'checkbox,Choose colours,2,Red,Blue,Black,,A|B,,,',
        'true_false,Earth is round,1,,,,,True,,,',
        'short_answer,Capital,2,,,,,,Kuala Lumpur,,',
        'essay,Explain,3,,,,,,,Clear evidence,',
    ].join('\n'));
    assert.equal(result.questions.length, 5);
    assert.equal(result.rows.length, 5);
    assert.ok(result.rows.every((row) => row.valid));
});

test('CSV accepts zero marks and rejects negative or non-numeric marks', () => {
    const zero = validateAssessmentCsv(`${CSV_TEMPLATE_COLUMNS.join(',')}\ntrue_false,Statement,0,,,,,True,,,`);
    assert.equal(zero.rows[0].valid, true);
    assert.equal(zero.questions[0].marks, 0);
    const negative = validateAssessmentCsv(`${CSV_TEMPLATE_COLUMNS.join(',')}\ntrue_false,Statement,-1,,,,,True,,,`);
    assert.match(negative.rows[0].issues.join(' '), /cannot be negative/i);
    const text = validateAssessmentCsv(`${CSV_TEMPLATE_COLUMNS.join(',')}\ntrue_false,Statement,abc,,,,,True,,,`);
    assert.match(text.rows[0].issues.join(' '), /valid number/i);
});

test('CSV reports wrong headers, blank, malformed, invalid rows, and Excel guidance', () => {
    assert.match(validateAssessmentCsv('').errors[0], /empty/i);
    assert.match(validateAssessmentCsv('type,question,marks').errors[0], /no question rows/i);
    assert.match(validateAssessmentCsv('type,question,marks\nmcq,"broken,1').errors[0], /unclosed/i);
    assert.match(validateAssessmentCsv('wrong,headers\na,b').errors[0], /Missing required columns/);
    const invalid = validateAssessmentCsv(`${CSV_TEMPLATE_COLUMNS.join(',')}\nmcq,Question,1,A,B,,,Z,,,`);
    assert.equal(invalid.questions.length, 0);
    assert.equal(invalid.rows[0].valid, false);
    assert.match(scripts, /Excel files are not supported by this importer yet/);
});

test('True/False import normalizes safe casing and rejects ambiguous answers', () => {
    for (const answer of ['True', ' TRUE ', 'true', 'False', ' false ']) {
        const result = validateAssessmentCsv(`${CSV_TEMPLATE_COLUMNS.join(',')}\ntrue_false,Statement,1,,,,,${answer},,,`);
        assert.equal(result.rows[0].valid, true, answer);
        assert.ok(['True', 'False'].includes(result.questions[0].correct_answer));
    }
    for (const answer of ['Ture', 'Flase', 'Yes', 'No', '1', '0', 'Maybe', '✓']) {
        const result = validateAssessmentCsv(`${CSV_TEMPLATE_COLUMNS.join(',')}\ntrue_false,Statement,1,,,,,${answer},,,`);
        assert.equal(result.rows[0].valid, false, answer);
        assert.match(result.rows[0].issues.join(' '), /Correct answer must be True or False/);
    }
});

test('one imported True/False question becomes a canonical non-empty collection', () => {
    const imported = validateAssessmentCsv(`${CSV_TEMPLATE_COLUMNS.join(',')}\ntrue_false,The sky is blue.,1,,,,,True,,,`);
    const result = addQuestionsToState(initialAssessmentState(), imported.questions);
    const collection = deriveQuestionCollectionState(result.state);
    assert.equal(result.success, true);
    assert.equal(collection.count, 1);
    assert.equal(collection.empty, false);
    assert.equal(collection.clearAllVisible, true);
    assert.equal(collection.jumpCount, 1);
});

test('editable import correction revalidates rows and emits corrected valid payload', () => {
    const parsed = validateAssessmentCsv(`${CSV_TEMPLATE_COLUMNS.join(',')}\ntrue_false,The sky is blue.,1,,,,,Ture,,,`);
    assert.equal(deriveAssessmentImportState(parsed.rows).validCount, 0);
    const corrected = updateAssessmentImportRow(parsed.rows, 0, 'correct_answer', 'True');
    const correctedState = deriveAssessmentImportState(corrected);
    assert.equal(corrected[0].valid, true);
    assert.equal(correctedState.validCount, 1);
    assert.equal(correctedState.invalidCount, 0);
    assert.equal(correctedState.validQuestions[0].question_type, 'true_false');
    assert.equal(correctedState.validQuestions[0].correct_answer, 'True');

    const changedType = updateAssessmentImportRow(corrected, 0, 'type', 'essay');
    assert.equal(changedType[0].valid, false);
    const withRubric = updateAssessmentImportRow(changedType, 0, 'grading_rubric', 'Clear factual response');
    assert.equal(withRubric[0].valid, true);
});

test('editable spreadsheet preview exposes canonical fields and live row validation', () => {
    for (const field of ['type', 'question', 'marks', 'option_a', 'option_b', 'option_c', 'option_d', 'grading_rubric']) {
        assert.match(scripts, new RegExp(`importPreviewField\\(rowIndex, '${field}'`));
    }
    assert.match(scripts, /field === 'correct_answer' && type === 'true_false'/);
    assert.match(scripts, /updateAssessmentImportRow\(/);
    assert.match(scripts, /deriveAssessmentImportState\(lwImportRows\)/);
    assert.match(scripts, /Correct \/ Expected/);
    assert.match(scripts, /Column mapping/);
    assert.match(workspace, /use <code>true_false<\/code> and enter only <code>True<\/code> or <code>False<\/code>/);
});

test('CSV template is local, dependency-free, and supports 100-question batches', () => {
    assert.deepEqual(parseAssessmentCsv(assessmentCsvTemplate())[0], CSV_TEMPLATE_COLUMNS);
    const rows = [CSV_TEMPLATE_COLUMNS.join(',')];
    for (let index = 1; index <= 100; index += 1) rows.push(`true_false,Q${index},1,,,,,True,,,`);
    assert.equal(validateAssessmentCsv(rows.join('\n')).questions.length, 100);
    assert.doesNotMatch(controller, /PhpSpreadsheet|Maatwebsite|Excel::/);
});

test('CSV guide teaches spreadsheet export, all five types, and safe correction', () => {
    for (const product of ['Microsoft Excel', 'Google Sheets', 'LibreOffice Calc']) {
        assert.match(workspace, new RegExp(product));
    }
    assert.match(workspace, /CSV UTF-8 \(\.csv\)/);
    for (const type of ['mcq', 'checkbox', 'true_false', 'short_answer', 'essay']) {
        assert.match(workspace, new RegExp(`<code>${type}<\\/code>`));
    }
    assert.match(workspace, /correct_answer=A\|C/);
    assert.match(workspace, /Marks may be 0 but cannot be negative/);
    assert.match(workspace, /preview is editable/i);

    const incompatible = validateAssessmentCsv('Promptish,Scoreish,Unknown\nQuestion,1,value');
    assert.match(incompatible.errors.join(' '), /Missing required columns/);
    assert.match(incompatible.errors.join(' '), /Unrecognized imported columns/);
});

test('duplicate appends, Add Below inserts, clear all confirms, and structural events are delegated once', () => {
    const duplicate = scripts.match(/function duplicateQuestionCard\(card\)[\s\S]*?function addQuestionsBatch/)?.[0] ?? '';
    assert.match(duplicate, /addQuestion\(duplicate\.question_type, duplicate\)/);
    assert.doesNotMatch(duplicate, /insertAfterCard/);
    assert.match(scripts, /insertAfterCard\.insertAdjacentElement\('afterend', card\)/);
    assert.match(scripts, /removeQuestionFromState\(assessmentState, index\)/);
    assert.match(scripts, /clearQuestionsFromState\(assessmentState\)/);
    assert.match(scripts, /card\.dataset\.questionType === 'mcq' \|\| card\.dataset\.questionType === 'checkbox'/);
    assert.match(scripts, /workspace\.dataset\.builderReady === '1'/);
    assert.match(scripts, /workspace\.addEventListener\('click'/);
    assert.match(scripts, /workspace\.addEventListener\('input'/);
});

test('learner lock condition and disabled structural controls are exact', () => {
    assert.match(controller, /assessmentHasLearnerRecords/);
    assert.match(controller, /submittedQuizStructureMatchesExisting/);
    assert.match(scripts, /mode === 'edit'[\s\S]*selectedAssessmentId[\s\S]*hasLearnerRecords/);
    assert.match(scripts, /#addQuestionPanel button, #quizTemplatesSection button/);
    assert.match(scripts, /options\.loadingExisting/);
    assert.match(scripts, /allowLockedRender/);
    assert.match(scripts, /#quizScoringSection input:not/);
    assert.match(workspace, /Questions and scoring are locked to protect learner results/i);
    assert.match(scripts, /attemptedQuizMetadataEntries\(savePayload\.entries\(\)\)/);
    assert.deepEqual(attemptedQuizMetadataEntries([
        ['_method', 'PATCH'],
        ['title', 'Allowed title'],
        ['instruction_text', 'Allowed instructions'],
        ['available_from', '2026-08-20T09:00'],
        ['questions[0][question_text]', 'Locked question'],
        ['questions[0][marks]', '4'],
        ['total_marks', '4'],
        ['time_limit_minutes', '30'],
        ['weight_percent', '10'],
        ['max_attempts', '2'],
    ]), [
        ['_method', 'PATCH'],
        ['title', 'Allowed title'],
        ['instruction_text', 'Allowed instructions'],
        ['available_from', '2026-08-20T09:00'],
    ]);
});

test('Submission and Quiz payloads are isolated on client and server', () => {
    assert.match(request, /\$type === 'submission' \? \[\] : \$this->input\('questions', \[\]\)/);
    assert.match(request, /'accepted_format' => \$type === 'submission'/);
    assert.match(request, /'time_limit_minutes' => \$type === 'quiz'/);
    assert.match(scripts, /if \(type === 'submission'\)[\s\S]*questionList[\s\S]*total_marks/);
    assert.match(controller, /'accepted_format' => \$validated\['type'\] === 'submission'/);
});

test('catalog is course-scoped and type-filtered, including legitimate module-linked assessments', () => {
    assert.match(service, /function assessmentsForCourse\(string \$courseId\)/);
    assert.match(service, /where\('course_id', \$courseId\)/);
    assert.match(service, /LOWER\(TRIM\(type\)\)/);
    assert.match(workspace, /@foreach \(\$assessments as \$assessment\)/);
    assert.match(scripts, /assessment\.type === type/);
});

test('soft navigation is scoped, progressive, history-aware, and idempotent', () => {
    assert.match(materials, /data-materials-soft-link/);
    assert.match(blockPartial, /data-materials-soft-link/);
    assert.match(scripts, /link\.matches\('\[data-materials-soft-link\]'\)/);
    assert.match(scripts, /DOMParser/);
    assert.match(scripts, /history\.pushState/);
    assert.match(scripts, /addEventListener\('popstate'/);
    assert.match(scripts, /window\.location\.assign\(url\)/);
    assert.match(scripts, /materialsSoftNavigationReady/);
    assert.match(scripts, /requestAssessmentDiscard\('Leave Assessment Builder\?'/);
    assert.equal(shouldHandleMaterialsSoftNavigation({ button: 0 }, { href: '/assessment' }), true);
    assert.equal(shouldHandleMaterialsSoftNavigation({ button: 0, ctrlKey: true }, { href: '/assessment' }), false);
    assert.equal(shouldHandleMaterialsSoftNavigation({ button: 1 }, { href: '/assessment' }), false);
    assert.equal(shouldHandleMaterialsSoftNavigation({ button: 0 }, { href: '/assessment', target: '_blank' }), false);
    assert.equal(deriveAuthoringScrollState(1).locked, true);
    assert.equal(deriveAuthoringScrollState(0).locked, false);
    assert.match(scripts, /function destroyTransientAuthoringUi/);
    assert.match(scripts, /destroyTransientAuthoringUi\(\)[\s\S]*current\.replaceWith/);
    assert.match(scripts, /releaseAuthoringScrollLock\(true\)/);
});

test('server choice validation rejects blank visible choices and preserves authoritative rules', () => {
    assert.match(controller, /count\(\$options\) < 2 \|\| in_array\('', \$options, true\)/);
    assert.match(controller, /count\(\$uniqueOptions\) < 2/);
    assert.match(request, /questions\.\*\.marks[\s\S]*min:0/);
});
