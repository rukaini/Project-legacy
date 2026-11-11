const TYPE_ALIASES = new Map([
    ['mcq', 'mcq'],
    ['multiple choice', 'mcq'],
    ['multiple_choice', 'mcq'],
    ['checkbox', 'checkbox'],
    ['multiple answer', 'checkbox'],
    ['true false', 'true_false'],
    ['true/false', 'true_false'],
    ['true / false', 'true_false'],
    ['true-false', 'true_false'],
    ['truefalse', 'true_false'],
    ['tf', 'true_false'],
    ['true_false', 'true_false'],
    ['short answer', 'short_answer'],
    ['short_answer', 'short_answer'],
    ['essay', 'essay'],
]);

export const QUESTION_TYPE_LABELS = {
    mcq: 'Multiple Choice',
    checkbox: 'Checkbox',
    true_false: 'True / False',
    short_answer: 'Short Answer',
    essay: 'Essay',
};

export const CSV_TEMPLATE_COLUMNS = [
    'type',
    'question',
    'marks',
    'option_a',
    'option_b',
    'option_c',
    'option_d',
    'correct_answer',
    'accepted_answer',
    'grading_rubric',
];

export const ASSESSMENT_TYPES = Object.freeze(['quiz', 'submission']);

const LOCKED_QUIZ_STRUCTURAL_FIELDS = new Set([
    'total_marks',
    'time_limit_minutes',
    'passing_grade',
    'weight_percent',
    'max_attempts',
]);

export function attemptedQuizMetadataEntries(entries) {
    return Array.from(entries).filter(([name]) => (
        !String(name).startsWith('questions[')
        && !LOCKED_QUIZ_STRUCTURAL_FIELDS.has(String(name))
    ));
}

export function normalizeQuestionType(type) {
    return TYPE_ALIASES.get(
        String(type || '')
            .trim()
            .toLowerCase()
            .replace(/\s+/g, ' '),
    ) || null;
}

export function normalizeTrueFalseAnswer(value) {
    const normalized = String(value ?? '').trim().toLowerCase();
    if (normalized === 'true') return 'True';
    if (normalized === 'false') return 'False';
    return null;
}

export function normalizeQuestion(question = {}) {
    const type = normalizeQuestionType(question.question_type || question.type) || 'mcq';
    const rawMarks = question.marks;
    const normalized = {
        ...question,
        question_type: type,
        question_text: String(question.question_text ?? question.question ?? ''),
        marks: rawMarks === undefined || rawMarks === null
            ? 0
            : (String(rawMarks).trim() === '' ? '' : Number(rawMarks)),
        options: Array.isArray(question.options) ? question.options.map((choice) => String(choice ?? '')) : [],
        correct_answer: String(question.correct_answer ?? ''),
        correct_answers: Array.isArray(question.correct_answers)
            ? question.correct_answers.map((answer) => String(answer ?? ''))
            : [],
        grading_rubric: String(question.grading_rubric ?? ''),
    };

    if (type === 'true_false') {
        normalized.options = ['True', 'False'];
        normalized.correct_answer = normalizeTrueFalseAnswer(normalized.correct_answer) || '';
        normalized.correct_answers = normalized.correct_answer ? [normalized.correct_answer] : [];
    }

    return normalized;
}

export function normalizeAssessmentType(type) {
    return type === 'submission' ? 'submission' : 'quiz';
}

export function initialAssessmentState(type = 'quiz') {
    return {
        type: normalizeAssessmentType(type),
        mode: 'new',
        selectedAssessmentId: null,
        loadingAssessmentId: null,
        loadingVisible: false,
        requestToken: 0,
        dirty: false,
        hasLearnerRecords: false,
        questions: [],
        activeQuestionId: null,
        importState: 'guide',
        templateState: 'closed',
    };
}

export function deriveAssessmentVisibility(state) {
    const quiz = normalizeAssessmentType(state.type) === 'quiz';
    const editing = state.mode === 'edit' && state.selectedAssessmentId !== null;
    const loading = state.loadingAssessmentId !== null;
    const locked = quiz && editing && !loading && state.hasLearnerRecords === true;

    return {
        quiz,
        submission: !quiz,
        quizTools: quiz,
        quizSettings: quiz,
        submissionSettings: !quiz,
        questionCollection: quiz,
        questionRail: quiz,
        learnerWarning: locked,
        structuralEditing: quiz && !locked,
        detailLoading: loading,
        questionEmpty: quiz && state.questions.length === 0,
    };
}

export function startNewAssessment(state, type = state.type) {
    return {
        ...initialAssessmentState(type),
        requestToken: Number(state.requestToken || 0) + 1,
    };
}

export function switchAssessmentType(state, type) {
    return startNewAssessment(state, normalizeAssessmentType(type));
}

export function beginAssessmentLoad(state, assessmentId) {
    return {
        ...state,
        mode: 'edit',
        selectedAssessmentId: String(assessmentId),
        loadingAssessmentId: String(assessmentId),
        loadingVisible: false,
        hasLearnerRecords: false,
        questions: [],
        activeQuestionId: null,
        requestToken: Number(state.requestToken || 0) + 1,
    };
}

export function assessmentLoadSucceeded(state, assessment, requestToken = state.requestToken) {
    if (requestToken !== state.requestToken) return state;
    const type = normalizeAssessmentType(assessment.type);
    if (type !== state.type || String(assessment.assessment_id) !== state.loadingAssessmentId) {
        return state;
    }

    return {
        ...state,
        mode: 'edit',
        selectedAssessmentId: String(assessment.assessment_id),
        loadingAssessmentId: null,
        loadingVisible: false,
        dirty: false,
        hasLearnerRecords: type === 'quiz' && Boolean(assessment.has_learner_records),
        questions: type === 'quiz' && Array.isArray(assessment.questions)
            ? assessment.questions
            : [],
        activeQuestionId: null,
    };
}

export function assessmentLoadFailed(state, requestToken = state.requestToken) {
    if (requestToken !== state.requestToken) return state;

    return {
        ...startNewAssessment(state, state.type),
        requestToken: state.requestToken,
    };
}

export function revealAssessmentLoading(state, requestToken = state.requestToken) {
    if (requestToken !== state.requestToken || state.loadingAssessmentId === null) return state;
    return { ...state, loadingVisible: true };
}

export function deriveCatalogState(state, catalog) {
    const items = (Array.isArray(catalog) ? catalog : []).filter(
        (assessment) => normalizeAssessmentType(assessment.type) === state.type,
    );
    const loading = state.loadingAssessmentId !== null;

    return {
        items,
        empty: !loading && items.length === 0,
        selectorDisabled: items.length === 0,
        spinnerVisible: loading && state.loadingVisible,
    };
}

export function deriveQuestionCollectionState(state) {
    const quiz = normalizeAssessmentType(state.type) === 'quiz';
    const questions = quiz && Array.isArray(state.questions) ? state.questions : [];
    const validation = questions.map(validateQuestion);
    return {
        rendered: quiz,
        empty: quiz && questions.length === 0,
        count: questions.length,
        jumpCount: questions.length,
        clearAllVisible: quiz && questions.length > 0 && state.hasLearnerRecords !== true,
        totalMarks: questions.reduce((sum, question) => {
            const marks = Number(question.marks);
            return sum + (Number.isFinite(marks) ? marks : 0);
        }, 0),
        incompleteCount: validation.filter((result) => !result.complete).length,
    };
}

export function addQuestionsToState(state, questions, insertAt = null) {
    const additions = (Array.isArray(questions) ? questions : []).map(normalizeQuestion);
    if (normalizeAssessmentType(state.type) !== 'quiz' || additions.length === 0) {
        return { success: false, addedCount: 0, state };
    }
    if (!canAddQuestionBatch(state.questions.length, additions.length)) {
        return { success: false, addedCount: 0, state, error: 'question_limit' };
    }

    const nextQuestions = state.questions.slice();
    const index = Number.isInteger(insertAt)
        ? Math.max(0, Math.min(insertAt, nextQuestions.length))
        : nextQuestions.length;
    nextQuestions.splice(index, 0, ...additions);

    return {
        success: true,
        addedCount: additions.length,
        state: { ...state, questions: nextQuestions, dirty: true },
    };
}

export function removeQuestionFromState(state, index) {
    if (!Number.isInteger(index) || index < 0 || index >= state.questions.length) return state;
    const questions = state.questions.slice();
    questions.splice(index, 1);
    return { ...state, questions, dirty: true };
}

export function clearQuestionsFromState(state) {
    return { ...state, questions: [], activeQuestionId: null, dirty: true };
}

function normalizedChoice(value) {
    return String(value ?? '').trim();
}

function uniqueChoiceCount(choices) {
    return new Set(choices.map((choice) => choice.toLocaleLowerCase())).size;
}

export function validateQuestion(question = {}) {
    const type = normalizeQuestionType(question.question_type || question.type) || 'mcq';
    const errors = [];
    const text = String(question.question_text ?? question.question ?? '').trim();
    const rawMarks = question.marks;
    const marks = String(rawMarks ?? '').trim() === '' ? Number.NaN : Number(rawMarks);
    const choices = (Array.isArray(question.options) ? question.options : [])
        .map(normalizedChoice);
    const correctAnswers = Array.isArray(question.correct_answers)
        ? question.correct_answers.map(normalizedChoice).filter(Boolean)
        : [];
    const correctAnswer = normalizedChoice(question.correct_answer);

    if (!text) errors.push('Enter the question text.');
    if (!Number.isFinite(marks)) errors.push('Marks must be a valid number.');
    else if (marks < 0) errors.push('Marks cannot be negative.');

    if (type === 'mcq' || type === 'checkbox') {
        if (choices.length < 2) errors.push('Add at least two choices.');
        if (choices.some((choice) => !choice)) errors.push('Fill in all choices.');
        const filled = choices.filter(Boolean);
        if (uniqueChoiceCount(filled) !== filled.length) {
            errors.push('Choice answers must be unique.');
        }

        const selected = type === 'mcq'
            ? (correctAnswers.length ? correctAnswers : [correctAnswer].filter(Boolean))
            : correctAnswers;
        if (type === 'mcq' && selected.length !== 1) {
            errors.push('Select one correct answer.');
        }
        if (type === 'checkbox' && selected.length < 1) {
            errors.push('Select at least one correct answer.');
        }
        if (selected.some((answer) => !filled.includes(answer))) {
            errors.push('Select a correct answer that matches an existing choice.');
        }
    } else if (type === 'true_false') {
        const selected = normalizeTrueFalseAnswer(correctAnswer || correctAnswers[0] || '');
        const fixedOptions = Array.isArray(question.options) && question.options.length > 0
            ? question.options.map(normalizedChoice)
            : ['True', 'False'];
        if (fixedOptions.length !== 2 || fixedOptions[0] !== 'True' || fixedOptions[1] !== 'False') {
            errors.push('True / False questions must use the fixed True and False choices.');
        }
        if (!selected) {
            errors.push('Select True or False as the correct answer.');
        }
    } else if (type === 'short_answer') {
        if (!correctAnswer) errors.push('Enter the expected answer.');
    } else if (type === 'essay') {
        if (!String(question.grading_rubric || '').trim()) {
            errors.push('Enter the grading rubric.');
        }
    }

    return { complete: errors.length === 0, errors, type };
}

export function validateQuiz(questions) {
    const results = (Array.isArray(questions) ? questions : []).map(validateQuestion);
    return {
        complete: results.length > 0 && results.every((result) => result.complete),
        empty: results.length === 0,
        incompleteCount: results.filter((result) => !result.complete).length,
        results,
    };
}

export function canAddQuestionBatch(currentCount, additionalCount, limit = 100) {
    const current = Number(currentCount);
    const additional = Number(additionalCount);
    return Number.isInteger(current)
        && Number.isInteger(additional)
        && current >= 0
        && additional > 0
        && current + additional <= limit;
}

export function questionsForTemplate(name, customType = null, customCount = null) {
    const templates = {
        '5mcq': Array(5).fill('mcq'),
        '10mcq': Array(10).fill('mcq'),
        '20mcq': Array(20).fill('mcq'),
        'mixed10': [...Array(5).fill('mcq'), ...Array(3).fill('short_answer'), ...Array(2).fill('essay')],
        'tfshort': [...Array(5).fill('true_false'), ...Array(5).fill('short_answer')],
    };
    let types = templates[name] || [];
    if (name === 'custom') {
        const type = normalizeQuestionType(customType);
        const count = Number(customCount);
        if (!type || !Number.isInteger(count) || count < 1 || count > 100) return [];
        types = Array(count).fill(type);
    }
    return types.map((type) => normalizeQuestion({ question_type: type }));
}

/**
 * Normalize the compact Custom Question Mix dialog into one deterministic
 * batch. Duplicate types are merged as a defensive fallback; the UI prevents
 * selecting them twice in normal use.
 */
export function deriveCustomQuestionMix(rows, currentQuestionCount = 0, limit = 100) {
    const counts = new Map();
    const errors = [];

    (Array.isArray(rows) ? rows : []).forEach((row, index) => {
        const type = normalizeQuestionType(row?.question_type || row?.type);
        const count = Number(row?.count);
        if (!type) errors.push(`Row ${index + 1} needs a valid question type.`);
        if (!Number.isInteger(count) || count < 1) {
            errors.push(`Row ${index + 1} count must be a whole number of at least 1.`);
        }
        if (type && Number.isInteger(count) && count >= 1) {
            counts.set(type, (counts.get(type) || 0) + count);
        }
    });

    const normalizedRows = Array.from(counts, ([question_type, count]) => ({
        question_type,
        count,
    }));
    const total = normalizedRows.reduce((sum, row) => sum + row.count, 0);
    const current = Number(currentQuestionCount);
    const remaining = Math.max(0, limit - (Number.isInteger(current) ? current : 0));

    if (normalizedRows.length === 0 && errors.length === 0) {
        errors.push('Add at least one question mix row.');
    }
    if (total > remaining) errors.push('question_limit');

    return {
        valid: errors.length === 0,
        rows: normalizedRows,
        total,
        remaining,
        errors,
    };
}

export function questionsForCustomMix(rows) {
    const mix = deriveCustomQuestionMix(rows, 0, Number.MAX_SAFE_INTEGER);
    if (!mix.valid) return [];
    return mix.rows.flatMap((row) =>
        Array.from({ length: row.count }, () => normalizeQuestion({
            question_type: row.question_type,
            marks: 0,
        })),
    );
}

export function shouldHandleMaterialsSoftNavigation(event = {}, link = {}) {
    return Boolean(link.href)
        && event.defaultPrevented !== true
        && Number(event.button ?? 0) === 0
        && !event.ctrlKey
        && !event.metaKey
        && !event.shiftKey
        && !event.altKey
        && link.target !== '_blank';
}

export function deriveAuthoringScrollState(openDialogCount = 0) {
    const count = Number(openDialogCount);
    return {
        locked: Number.isFinite(count) && count > 0,
        documentOverflow: Number.isFinite(count) && count > 0 ? 'hidden' : '',
        bodyOverflow: Number.isFinite(count) && count > 0 ? 'hidden' : '',
    };
}

export function parseAssessmentCsv(text) {
    const rows = [];
    let row = [];
    let value = '';
    let quoted = false;

    for (let index = 0; index < text.length; index += 1) {
        const character = text[index];

        if (quoted) {
            if (character === '"' && text[index + 1] === '"') {
                value += '"';
                index += 1;
            } else if (character === '"') {
                quoted = false;
            } else {
                value += character;
            }
            continue;
        }

        if (character === '"') quoted = true;
        else if (character === ',') {
            row.push(value);
            value = '';
        } else if (character === '\n') {
            row.push(value.replace(/\r$/, ''));
            rows.push(row);
            row = [];
            value = '';
        } else value += character;
    }

    row.push(value.replace(/\r$/, ''));
    if (row.some((cell) => cell.trim() !== '')) rows.push(row);

    if (quoted) {
        throw new Error('The CSV contains an unclosed quoted value.');
    }

    return rows;
}

function normalizedHeader(value) {
    const normalized = String(value || '')
        .replace(/^\uFEFF/, '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_');
    const aliases = {
        question_text: 'question',
        prompt: 'question',
        score: 'marks',
        mark: 'marks',
        answer: 'correct_answer',
        expected_answer: 'accepted_answer',
        rubric: 'grading_rubric',
    };
    return aliases[normalized] || normalized;
}

function choiceForAnswer(answer, choices) {
    const normalized = String(answer || '').trim();
    if (/^[a-d]$/i.test(normalized)) {
        return choices[normalized.toLowerCase().charCodeAt(0) - 97] || '';
    }
    return choices.find(
        (choice) => choice.toLowerCase() === normalized.toLowerCase(),
    ) || '';
}

function sourceToImportQuestion(source) {
    const type = normalizeQuestionType(source.type);
    const marks = String(source.marks ?? '').trim() === ''
        ? Number.NaN
        : Number(source.marks);
    const choices = ['option_a', 'option_b', 'option_c', 'option_d']
        .map((key) => String(source[key] || '').trim())
        .filter(Boolean);
    const issues = [];
    let correctAnswer = '';
    let correctAnswers = [];

    if (!type) issues.push(`Unsupported question type “${String(source.type || '').trim() || '(blank)'}”.`);
    if (!String(source.question || '').trim()) issues.push('Question text is required.');
    if (!Number.isFinite(marks)) issues.push('Marks must be a valid number.');
    else if (marks < 0) issues.push('Marks cannot be negative.');

    if (type === 'mcq' || type === 'checkbox') {
        const submittedAnswers = String(source.correct_answer || '')
            .split(/[|;]/)
            .map((answer) => answer.trim())
            .filter(Boolean);
        correctAnswers = submittedAnswers
            .map((answer) => choiceForAnswer(answer, choices))
            .filter(Boolean);
        if (correctAnswers.length !== submittedAnswers.length || correctAnswers.length === 0) {
            issues.push(`Correct answer “${String(source.correct_answer || '').trim() || '(blank)'}” does not match any provided choice.`);
        }
        correctAnswer = correctAnswers[0] || '';
    } else if (type === 'true_false') {
        const normalizedAnswer = normalizeTrueFalseAnswer(source.correct_answer);
        if (!normalizedAnswer) issues.push('Correct answer must be True or False.');
        correctAnswer = normalizedAnswer || '';
    } else if (type === 'short_answer') {
        correctAnswer = String(source.accepted_answer || source.correct_answer || '').trim();
    }

    const candidate = normalizeQuestion({
        question_type: type || source.type,
        question_text: source.question,
        marks,
        options: choices,
        correct_answer: correctAnswer,
        correct_answers: correctAnswers,
        grading_rubric: source.grading_rubric || '',
    });
    if (!type) candidate.question_type = String(source.type || '').trim();

    if (type) {
        validateQuestion(candidate).errors.forEach((issue) => {
            if (type === 'true_false' && issues.includes('Correct answer must be True or False.') && issue.includes('True or False')) {
                return;
            }
            if (!issues.includes(issue)) issues.push(issue);
        });
    }

    return { question: candidate, valid: issues.length === 0, issues };
}

export function validateAssessmentImportRow(row) {
    const source = { ...(row?.source || row || {}) };
    const validation = sourceToImportQuestion(source);
    return {
        row: Number(row?.row || 0),
        source,
        ...validation,
    };
}

export function updateAssessmentImportRow(rows, rowIndex, field, value) {
    return (Array.isArray(rows) ? rows : []).map((row, index) => {
        if (index !== rowIndex) return row;
        const source = { ...row.source, [field]: value };
        if (field === 'type') source.type = normalizeQuestionType(value) || value;
        return validateAssessmentImportRow({ ...row, source });
    });
}

export function deriveAssessmentImportState(rows) {
    const normalizedRows = Array.isArray(rows) ? rows : [];
    const validQuestions = normalizedRows.filter((row) => row.valid).map((row) => row.question);
    return {
        total: normalizedRows.length,
        validCount: validQuestions.length,
        invalidCount: normalizedRows.length - validQuestions.length,
        validQuestions,
    };
}

export function validateAssessmentCsv(text) {
    let parsed;
    try {
        parsed = parseAssessmentCsv(text);
    } catch (error) {
        return {
            questions: [],
            errors: [error.message || 'The CSV is malformed.'],
            total: 0,
            rows: [],
        };
    }
    if (parsed.length === 0) {
        return { questions: [], errors: ['The CSV file is empty.'], total: 0, rows: [] };
    }

    const importedHeaders = parsed[0].map((header) => String(header || '').trim());
    const headers = importedHeaders.map(normalizedHeader);
    const headerMappings = importedHeaders.map((header, index) => ({
        imported: header,
        canonical: headers[index],
        recognized: CSV_TEMPLATE_COLUMNS.includes(headers[index]),
    }));
    const unrecognizedHeaders = headerMappings
        .filter((mapping) => mapping.imported && !mapping.recognized)
        .map((mapping) => mapping.imported);
    const missing = ['type', 'question', 'marks'].filter(
        (required) => !headers.includes(required),
    );
    if (missing.length > 0) {
        return {
            questions: [],
            errors: [
                `This file does not match the Assessment Question import format. Missing required columns: ${missing.join(', ')}.`,
                ...(unrecognizedHeaders.length > 0
                    ? [`Unrecognized imported columns: ${unrecognizedHeaders.join(', ')}.`]
                    : []),
                `Expected columns: ${CSV_TEMPLATE_COLUMNS.join(', ')}.`,
            ],
            total: Math.max(0, parsed.length - 1),
            rows: [],
            headerMappings,
        };
    }

    if (parsed.length === 1) {
        return {
            questions: [],
            errors: ['The CSV contains headers but no question rows.'],
            total: 0,
            rows: [],
        };
    }

    const errors = [];
    const rows = [];
    parsed.slice(1).forEach((cells, rowIndex) => {
        if (!cells.some((cell) => String(cell).trim() !== '')) return;
        const source = {};
        headers.forEach((header, columnIndex) => {
            source[header] = String(cells[columnIndex] || '').trim();
        });
        const csvRow = rowIndex + 2;
        const row = validateAssessmentImportRow({ row: csvRow, source });
        rows.push(row);
        if (!row.valid) errors.push(`Row ${csvRow}: ${row.issues.join(' ')}`);
    });

    const importState = deriveAssessmentImportState(rows);

    return {
        questions: importState.validQuestions,
        errors,
        total: parsed.slice(1).filter((row) =>
            row.some((cell) => String(cell).trim() !== ''),
        ).length,
        rows,
        headerMappings,
    };
}

function csvCell(value) {
    const text = String(value ?? '');
    return /[",\r\n]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
}

export function assessmentCsvTemplate() {
    const examples = [
        ['mcq', 'Which colour is primary?', 1, 'Red', 'Green', 'Black', 'White', 'A', '', ''],
        ['checkbox', 'Select the primary colours.', 2, 'Red', 'Blue', 'Green', 'Black', 'A|B|C', '', ''],
        ['true_false', 'The Earth orbits the Sun.', 1, '', '', '', '', 'True', '', ''],
        ['short_answer', 'Name the capital of Malaysia.', 2, '', '', '', '', '', 'Kuala Lumpur', ''],
        ['essay', 'Explain reflective teaching.', 5, '', '', '', '', '', '', 'Award marks for definition, example, and reflection.'],
    ];
    return [CSV_TEMPLATE_COLUMNS, ...examples]
        .map((row) => row.map(csvCell).join(','))
        .join('\r\n');
}

if (typeof window !== 'undefined') {
    window.AssessmentBuilderUtils = {
        ASSESSMENT_TYPES,
        QUESTION_TYPE_LABELS,
        CSV_TEMPLATE_COLUMNS,
        attemptedQuizMetadataEntries,
        normalizeAssessmentType,
        normalizeQuestionType,
        normalizeTrueFalseAnswer,
        normalizeQuestion,
        initialAssessmentState,
        deriveAssessmentVisibility,
        startNewAssessment,
        switchAssessmentType,
        beginAssessmentLoad,
        assessmentLoadSucceeded,
        assessmentLoadFailed,
        revealAssessmentLoading,
        deriveCatalogState,
        deriveQuestionCollectionState,
        addQuestionsToState,
        removeQuestionFromState,
        clearQuestionsFromState,
        validateQuestion,
        validateQuiz,
        canAddQuestionBatch,
        questionsForTemplate,
        deriveCustomQuestionMix,
        questionsForCustomMix,
        shouldHandleMaterialsSoftNavigation,
        deriveAuthoringScrollState,
        parseAssessmentCsv,
        validateAssessmentCsv,
        validateAssessmentImportRow,
        updateAssessmentImportRow,
        deriveAssessmentImportState,
        assessmentCsvTemplate,
    };
}
