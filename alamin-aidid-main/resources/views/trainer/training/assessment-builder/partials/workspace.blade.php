<section id="assessmentWorkspace"
         class="lw-assessment-page is-initial-{{ $initialAssessmentType }}"
         aria-labelledby="assessmentBuilderTitle"
         data-assessment-page>
        <div class="lw-assessment-page-head">
            <div>
                <a href="{{ route('trainer.courses.materials', $course->course_id) }}" class="lw-assessment-back" id="assessmentBackToMaterials" data-materials-soft-link>
                    <i class="bi bi-arrow-left"></i> Back to Materials
                </a>
                <h2 id="assessmentBuilderTitle" class="lw-assessment-page-title">Assessment Builder</h2>
                <p id="assessmentBuilderContext" class="lw-assessment-page-sub">Create and manage quizzes or submission tasks for this training.</p>
            </div>
            <button type="submit" form="assessmentForm" class="lw-btn primary" id="saveAssessmentButton">
                <i class="bi bi-check2"></i> Save Assessment
            </button>
        </div>

        <form method="POST"
              action="{{ route('trainer.courses.assessments.store', $course->course_id) }}"
              id="assessmentForm"
              data-store-url="{{ route('trainer.courses.assessments.store', $course->course_id) }}"
              enctype="multipart/form-data"
              novalidate
              class="lw-assessment-form js-ajax-assessment-form">
            @csrf
            <input type="hidden" name="_method" id="assessmentMethod" value="PATCH" disabled>

            <div class="lw-assess-body">
                <div class="lw-assess-main">
                    <div id="assessmentFormError" class="lw-form-error hidden" role="alert"></div>

                    <section class="lw-assess-flow-card" id="assessmentTypeStep" aria-labelledby="assessmentTypeStepTitle">
                        <h3 id="assessmentTypeStepTitle">What are you creating?</h3>
                        <div class="lw-assess-type-grid">
                            <button type="button" class="lw-assess-type-card" data-assessment-type-choice="quiz" aria-pressed="false">
                                <span class="lw-assess-type-icon"><i class="bi bi-ui-checks-grid"></i></span>
                                <span><strong>Quiz</strong><small>Build questions with marks and answers.</small></span>
                            </button>
                            <button type="button" class="lw-assess-type-card" data-assessment-type-choice="submission" aria-pressed="false">
                                <span class="lw-assess-type-icon submission"><i class="bi bi-cloud-arrow-up"></i></span>
                                <span><strong>Submission Task</strong><small>Learners upload work for Trainer review.</small></span>
                            </button>
                        </div>
                    </section>

                    {{-- One authoring-mode toolbar shared by Quiz and Submission. --}}
                    <div class="lw-assess-modebar" id="assessmentModeControls">
                        <div class="lw-assess-modebar-l">
                            <span class="lw-assess-modebar-hint">Authoring Mode</span>
                            <button type="button" id="assessmentStartNew" class="lw-btn ghost" title="Create a clean assessment of this type">
                                <i class="bi bi-file-earmark-plus"></i> <span id="assessmentStartNewLabel">New Quiz</span>
                            </button>
                            <span id="modeLabel" aria-live="polite">Creating new quiz</span>
                        </div>
                        <div class="lw-assess-modebar-r">
                            <label for="editQuizSelect" class="lw-assess-modebar-hint">Edit Existing:</label>
                            <select id="editQuizSelect" class="lw-assess-modebar-select">
                                <option value="" disabled selected>— choose a saved quiz —</option>
                                @foreach ($assessments as $assessment)
                                    <option value="{{ $assessment->assessment_id }}"
                                            data-load-url="{{ route('trainer.courses.assessments.authoring', [$course->course_id, $assessment->assessment_id]) }}"
                                            data-update-url="{{ route('trainer.courses.assessments.update', [$course->course_id, $assessment->assessment_id]) }}"
                                            data-assessment-type="{{ $assessment->type }}">{{ $assessment->title }}</option>
                                @endforeach
                            </select>
                            <span id="assessmentLoadSpinner" class="lw-assess-load-spinner" role="status" aria-label="Loading selected assessment" hidden><i class="bi bi-arrow-repeat"></i></span>
                            <span id="assessmentExistingEmpty" class="lw-assess-existing-empty" hidden></span>
                        </div>
                    </div>

                    <div id="assessmentAttemptBoundary" class="lw-assess-boundary" hidden>
                        <i class="bi bi-shield-lock"></i>
                        Learner attempts exist. Basic information and availability can still be edited. Questions and scoring are locked to protect learner results.
                    </div>

                    {{-- Basic Info --}}
                    <div class="lw-assess-section" data-assessment-editor-section data-assessment-shared-section>
                        <div class="lw-assess-section-head">
                            <div class="lw-assess-section-icon"><i class="bi bi-info-circle"></i></div>
                            <div>
                                <h3 class="lw-assess-section-title">Basic Info</h3>
                                <div class="lw-assess-section-sub">Name the assessment and provide clear learner instructions.</div>
                            </div>
                        </div>
                        <div class="lw-field">
                            <label for="assessmentTitleInput">Title</label>
                            <input id="assessmentTitleInput" type="text" name="title" placeholder="Example: Module 1 Quiz" required maxlength="255">
                        </div>
                        <input id="assessmentType" type="hidden" name="type" value="quiz">
                        <div class="lw-field" style="margin-top: 14px; margin-bottom: 0;">
                            <label for="assessmentInstructions">Instructions</label>
                            <textarea id="assessmentInstructions" name="instruction_text" rows="3" placeholder="Brief instructions shown to learners…"></textarea>
                        </div>
                    </div>

                    {{-- Availability --}}
                    <div class="lw-assess-section" data-assessment-editor-section data-assessment-shared-section>
                        <div class="lw-assess-section-head">
                            <div class="lw-assess-section-icon" style="background: var(--lw-amber-soft); color: var(--lw-amber);"><i class="bi bi-calendar-event"></i></div>
                            <div>
                                <h3 class="lw-assess-section-title">Availability</h3>
                                <div class="lw-assess-section-sub">When learners can take it. Leave empty for always-on.</div>
                            </div>
                        </div>
                        <div class="lw-field-grid-2">
                            <div class="lw-field" style="margin-bottom: 0;">
                                <label for="assessAvailFrom">Available From</label>
                                <input id="assessAvailFrom" type="datetime-local" name="available_from" onchange="lwSyncDateConstraints()">
                            </div>
                            <div class="lw-field" style="margin-bottom: 0;">
                                <label for="assessAvailUntil">Available Until</label>
                                <input id="assessAvailUntil" type="datetime-local" name="available_until" onchange="lwSyncDateConstraints()">
                            </div>
                        </div>
                        <div id="assessmentAvailabilityState" class="lw-availability-context">Always available</div>
                    </div>

                    {{-- Scoring (quiz only) --}}
                    <div class="lw-assess-section" id="quizScoringSection" data-assessment-editor-section data-assessment-quiz-section>
                        <div class="lw-assess-section-head">
                            <div class="lw-assess-section-icon" style="background: var(--lw-green-soft); color: var(--lw-green);"><i class="bi bi-bullseye"></i></div>
                            <div>
                                <h3 class="lw-assess-section-title">Scoring &amp; Time</h3>
                                <div class="lw-assess-section-sub">Total marks come from your questions. Set how much this quiz is worth.</div>
                            </div>
                        </div>

                        <input type="hidden" name="total_marks" id="hiddenTotalMarks" value="0">

                        <div class="lw-total-marks-card">
                            <span class="lw-total-marks-card-label">Auto-calculated total marks</span>
                            <span class="lw-total-marks-card-value" id="totalMarksDisplay">0</span>
                        </div>

                        <div class="lw-field-grid-3">
                            <div class="lw-field" id="quizTimeBox" style="margin-bottom: 0;">
                                <label>Time Limit (min)</label>
                                <input type="number" name="time_limit_minutes" min="1" placeholder="30">
                            </div>
                            <div class="lw-field" id="quizWeightBox" style="margin-bottom: 0;">
                                <label>Quiz Weight %</label>
                                <input type="number" name="weight_percent" min="0" max="100" step="0.01" placeholder="10">
                            </div>
                            <div class="lw-field" id="quizMaxAttemptsBox" style="margin-bottom: 0;">
                                <label>Max Attempts</label>
                                <input type="number" name="max_attempts" min="1" max="100" placeholder="Unlimited">
                            </div>
                        </div>
                        <div class="lw-scoring-hint-box">
                            <i class="bi bi-lightbulb"></i>
                            <strong>How scoring works:</strong> each question can be worth different marks, including 0 (use the +/− stepper). The sum is the quiz total. <strong>Quiz Weight %</strong> is what this quiz contributes to the learner's overall course grade — e.g. 10% means a perfect score earns the learner 10 percentage points toward the course. Their earned points = (earned marks ÷ total marks) × weight. Checkbox questions split their marks across correct answers (3 correct in a 2-mark question = 0.67 each). Short-answer awards full marks on any matched keyword. Essays are graded manually.
                        </div>
                    </div>

                    {{-- Submission Settings --}}
                    <div class="lw-assess-section" id="submissionSection" data-assessment-editor-section data-assessment-submission-section hidden>
                        <div class="lw-assess-section-head">
                            <div class="lw-assess-section-icon" style="background: var(--lw-rose-soft); color: var(--lw-rose);"><i class="bi bi-upload"></i></div>
                            <div>
                                <h3 class="lw-assess-section-title">Submission Settings</h3>
                                <div class="lw-assess-section-sub">The Available Until time above is the submission deadline.</div>
                            </div>
                        </div>
                        <div class="lw-field" style="margin-bottom: 0;">
                            <label for="assessAcceptedFormat">Accepted Format</label>
                            <select id="assessAcceptedFormat" name="accepted_format">
                                <option value="">Any Format</option>
                                <option value="PDF Only">PDF Only</option>
                                <option value="Document Only">Document Only</option>
                                <option value="Image Only">Image Only</option>
                                <option value="ZIP Only">ZIP Only</option>
                            </select>
                        </div>
                    </div>

                    {{-- Templates --}}
                    <div class="lw-assess-section" id="quizTemplatesSection" data-assessment-editor-section data-assessment-quiz-section>
                        <div class="lw-assess-section-head">
                            <div class="lw-assess-section-icon" style="background: #f3e8ff; color: #7c3aed;"><i class="bi bi-stars"></i></div>
                            <div>
                                <h3 class="lw-assess-section-title">Quick Templates</h3>
                                <div class="lw-assess-section-sub">Templates and CSV import add questions to the current quiz.</div>
                            </div>
                        </div>
                        <div class="lw-template-grid">
                            <button type="button" onclick="applyTemplate('5mcq')" class="lw-template-btn" data-quick-template="5mcq">
                                <div class="lw-template-btn-title">5 × Multiple Choice</div>
                                <div class="lw-template-btn-sub">Quick knowledge check.</div>
                            </button>
                            <button type="button" onclick="applyTemplate('10mcq')" class="lw-template-btn" data-quick-template="10mcq">
                                <div class="lw-template-btn-title">10 × Multiple Choice</div>
                                <div class="lw-template-btn-sub">Standard mid-module quiz.</div>
                            </button>
                            <button type="button" onclick="applyTemplate('20mcq')" class="lw-template-btn" data-quick-template="20mcq">
                                <div class="lw-template-btn-title">20 × Multiple Choice</div>
                                <div class="lw-template-btn-sub">End-of-chapter exam.</div>
                            </button>
                            <button type="button" onclick="applyTemplate('mixed10')" class="lw-template-btn" data-quick-template="mixed10">
                                <div class="lw-template-btn-title">Mixed 10</div>
                                <div class="lw-template-btn-sub">5 MCQ + 3 Short + 2 Essay.</div>
                            </button>
                            <button type="button" onclick="applyTemplate('tfshort')" class="lw-template-btn" data-quick-template="tfshort">
                                <div class="lw-template-btn-title">5 T/F + 5 Short</div>
                                <div class="lw-template-btn-sub">Fast verbal-style assessment.</div>
                            </button>
                            <button type="button" id="customTemplateButton" class="lw-template-btn">
                                <div class="lw-template-btn-title">Custom…</div>
                                <div class="lw-template-btn-sub">Pick count + type via dialog.</div>
                            </button>
                        </div>
                        <div class="lw-import-entry">
                            <div><strong>Bulk import questions</strong><span>Validate and add 50–100 questions from a CSV file.</span></div>
                            <button type="button" id="openQuestionImport" class="lw-btn ghost"><i class="bi bi-filetype-csv"></i> Import Questions</button>
                        </div>
                    </div>

                    {{-- Questions list --}}
                    <div id="questionSectionHeader" class="lw-question-section-head" data-assessment-editor-section data-assessment-quiz-section hidden>
                        <div><strong>Questions</strong> <span id="questionSectionCount">0 questions</span></div>
                        <button type="button" id="clearAllQuestions" class="lw-btn danger sm">Clear all questions</button>
                    </div>
                    <div id="questionEmptyState" class="lw-question-empty" data-assessment-editor-section data-assessment-quiz-section hidden>
                        <i class="bi bi-ui-checks"></i><strong>No questions yet.</strong>
                        <span>Choose a question type, use a Quick Template, or import questions.</span>
                    </div>
                    <div id="questionList" class="lw-assess-questions" data-assessment-editor-section data-assessment-quiz-section></div>

                </div>

                {{-- SIDEBAR --}}
                <div class="lw-assess-side" id="questionSidebar" hidden>
                    <div class="lw-assess-side-block" id="addQuestionPanel">
                        <p class="lw-assess-side-label">Add Question</p>
                        <button type="button" onclick="addQuestion('mcq')" class="lw-side-type-btn"><span class="lw-side-type-dot mcq"></span><span class="lw-side-type-text">Multiple Choice</span></button>
                        <button type="button" onclick="addQuestion('checkbox')" class="lw-side-type-btn"><span class="lw-side-type-dot checkbox"></span><span class="lw-side-type-text">Checkbox</span></button>
                        <button type="button" onclick="addQuestion('true_false')" class="lw-side-type-btn"><span class="lw-side-type-dot true_false"></span><span class="lw-side-type-text">True / False</span></button>
                        <button type="button" onclick="addQuestion('short_answer')" class="lw-side-type-btn"><span class="lw-side-type-dot short_answer"></span><span class="lw-side-type-text">Short Answer</span></button>
                        <button type="button" onclick="addQuestion('essay')" class="lw-side-type-btn"><span class="lw-side-type-dot essay"></span><span class="lw-side-type-text">Essay</span></button>
                    </div>

                    <div class="lw-assess-side-block" id="jumpToQuestionPanel">
                        <p class="lw-assess-side-label">Jump to Question</p>
                        <div id="questionNavigator" class="lw-question-jump-list"></div>
                    </div>

                    <div class="lw-assess-side-block" id="assessTotalsSummary" hidden>
                        <p class="lw-assess-side-label">Summary</p>
                        <div class="lw-assess-summary-row">Questions: <strong id="assessSummaryCount">0</strong></div>
                        <div class="lw-assess-summary-row">Sum of marks: <strong id="assessSummaryMarks">0</strong></div>
                        <div class="lw-assess-summary-row">Incomplete: <strong id="assessSummaryIncomplete">0</strong></div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <div id="assessmentImportModal" class="lw-modal hidden" role="dialog" aria-modal="true" aria-labelledby="assessmentImportTitle" tabindex="-1">
        <div class="lw-modal-box lw-import-modal-box">
            <div class="lw-modal-head">
                <div class="lw-modal-head-text">
                    <h2 class="lw-modal-title" id="assessmentImportTitle">Import Questions</h2>
                    <div class="lw-modal-sub">Review the spreadsheet before adding questions to this unsaved quiz.</div>
                </div>
                <button type="button" id="closeQuestionImport" class="lw-modal-close" aria-label="Close question import">&times;</button>
            </div>
            <div class="lw-modal-body">
                <div class="lw-import-guide">
                    <strong>How to prepare your CSV</strong>
                    <p>Create the spreadsheet in Microsoft Excel, Google Sheets, or LibreOffice Calc, then export or save it as <strong>CSV UTF-8 (.csv)</strong>.</p>
                    <ol>
                        <li>The first row contains column headers.</li>
                        <li>Each question uses one row.</li>
                        <li>Include the required columns <code>type</code>, <code>question</code>, and <code>marks</code>.</li>
                    </ol>
                    <p>Additional columns are <code>option_a</code>, <code>option_b</code>, <code>option_c</code>, <code>option_d</code>, <code>correct_answer</code>, <code>accepted_answer</code>, and <code>grading_rubric</code>. Unused columns may stay blank.</p>
                    <ul>
                        <li><strong>Multiple Choice:</strong> add at least two options; use A–D or exact option text for <code>correct_answer</code>.</li>
                        <li><strong>Checkbox:</strong> separate multiple correct answers with <code>|</code>, for example <code>A|C</code>.</li>
                        <li><strong>True / False:</strong> use <code>true_false</code> and enter only <code>True</code> or <code>False</code>.</li>
                        <li><strong>Short Answer:</strong> enter the expected response in <code>accepted_answer</code>.</li>
                        <li><strong>Essay:</strong> enter the marking guide in <code>grading_rubric</code>.</li>
                    </ul>
                    <div class="lw-import-guide-table-wrap">
                        <table class="lw-import-guide-table">
                            <thead><tr><th>Type</th><th>Question</th><th>Marks</th><th>Example answer fields</th></tr></thead>
                            <tbody>
                                <tr><td><code>mcq</code></td><td>Which colour is primary?</td><td>1</td><td><code>option_a=Red</code>, <code>option_b=Green</code>, <code>correct_answer=A</code></td></tr>
                                <tr><td><code>checkbox</code></td><td>Select primary colours.</td><td>2</td><td><code>option_a=Red</code>, <code>option_c=Blue</code>, <code>correct_answer=A|C</code></td></tr>
                                <tr><td><code>true_false</code></td><td>The Earth orbits the Sun.</td><td>0</td><td><code>correct_answer=True</code></td></tr>
                                <tr><td><code>short_answer</code></td><td>Capital of Malaysia?</td><td>1</td><td><code>accepted_answer=Kuala Lumpur</code></td></tr>
                                <tr><td><code>essay</code></td><td>Explain reflective teaching.</td><td>5</td><td><code>grading_rubric=Definition, example, reflection</code></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p>Marks may be 0 but cannot be negative. Incorrect True/False spelling such as <code>Ture</code> must be corrected. Unsupported question types are flagged. Question images are added after import.</p>
                    <p>The preview is editable: correct small mistakes there and the row status, counters, and Add Valid Questions action update immediately without re-uploading.</p>
                </div>
                <div class="lw-import-actions">
                    <button type="button" id="downloadQuestionTemplate" class="lw-btn ghost"><i class="bi bi-download"></i> Download CSV Template</button>
                    <label class="lw-btn primary" for="questionCsvInput"><i class="bi bi-upload"></i> Choose CSV</label>
                    <input id="questionCsvInput" type="file" accept=".csv,text/csv" hidden>
                </div>
                <div id="questionImportPreview" class="lw-import-preview">
                    <p>Select a CSV file to validate it before adding questions.</p>
                </div>
            </div>
            <div class="lw-modal-foot">
                <button type="button" id="cancelQuestionImport" class="lw-btn ghost">Cancel</button>
                <button type="button" id="addValidImportedQuestions" class="lw-btn primary" disabled>Add Valid Questions</button>
            </div>
        </div>
    </div>
