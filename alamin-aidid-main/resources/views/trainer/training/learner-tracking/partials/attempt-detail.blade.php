@php
    $attemptDetail = $quizTracking['detail'] ?? null;
@endphp

<div class="lt-attempt-layer {{ $attemptDetail ? 'is-open' : '' }}" data-tracking-region="attempt-detail">
    @if ($attemptDetail)
        @php
            $detailAttempt = $attemptDetail['attempt'];
            $answerStatusPresentation = [
                'correct' => ['Correct', 'bi-check-circle-fill'],
                'incorrect' => ['Incorrect', 'bi-x-circle-fill'],
                'unanswered' => ['Unanswered', 'bi-dash-circle'],
                'manual' => ['Manual review', 'bi-person-check'],
                'recorded_unavailable' => ['Participant answer unavailable', 'bi-question-circle'],
                'cannot_evaluate' => ['Answer cannot be evaluated', 'bi-info-circle'],
            ];
        @endphp
        <div class="lt-attempt-backdrop" aria-hidden="true" data-attempt-backdrop-close></div>
        <aside class="lt-attempt-drawer" id="quizAttemptDetail" role="dialog" aria-modal="true" aria-labelledby="quizAttemptDetailTitle" tabindex="-1" data-attempt-detail-panel>
            <header class="lt-attempt-drawer-head">
                <div>
                    <p class="lt-eyebrow">Quiz attempt details</p>
                    <h2 id="quizAttemptDetailTitle">{{ $attemptDetail['participant']?->participant_name ?? 'Participant' }}</h2>
                    <p>{{ $selectedQuiz->title }}</p>
                </div>
                <a href="{{ $trackingQuery(['attempt' => null]) }}" class="lt-detail-close" data-tracking-link data-tracking-regions="attempt-detail" data-attempt-close aria-label="Close attempt details" title="Close attempt details"><i class="bi bi-x-lg"></i></a>
            </header>

            <div class="lt-attempt-drawer-body">
                <div class="lt-attempt-switcher" aria-label="Quiz attempts">
                    @foreach ($attemptDetail['attempts'] as $attemptOption)
                        <a href="{{ $trackingQuery(['attempt' => $attemptOption->submission_id]) }}" class="{{ $attemptOption->selected ? 'is-active' : '' }}" data-tracking-link data-tracking-regions="attempt-detail" data-attempt-switch @if ($attemptOption->selected) aria-current="true" @endif @if (! $attemptOption->detail_available) title="Detailed answers unavailable" @endif>
                            <span>Attempt {{ $attemptOption->attempt_number }}</span>
                            @if (! $attemptOption->detail_available)
                                <small><i class="bi bi-info-circle" aria-hidden="true"></i> No detailed answers</small>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="lt-attempt-meta">
                    <div><span>Attempt</span><strong>Attempt {{ $attemptDetail['attempt_number'] }}</strong></div>
                    <div><span>Submitted</span><strong>{{ $detailAttempt->submitted_at ? \Carbon\Carbon::parse($detailAttempt->submitted_at)->format('d M Y, h:i A') : 'Not recorded' }}</strong></div>
                    <div><span>Recorded result</span><strong>
                        @if ($detailAttempt->grade === null)
                            Not graded
                        @elseif ((float) $selectedQuiz->total_marks <= 0)
                            {{ $detailAttempt->grade }} · Not scored
                        @else
                            {{ $detailAttempt->grade }} / {{ $selectedQuiz->total_marks }}
                        @endif
                    </strong></div>
                </div>

                @if (($attemptDetail['answer_review']['matches_recorded'] ?? null) === false)
                    <div class="lt-review-comparison" role="note" data-answer-review-mismatch>
                        <div><span>Recorded result</span><strong>{{ number_format((float) $detailAttempt->grade, 2) }} / {{ number_format((float) $selectedQuiz->total_marks, 2) }}</strong></div>
                        <div><span>Answer review</span><strong>{{ number_format($attemptDetail['answer_review']['earned'], 2) }} / {{ number_format($attemptDetail['answer_review']['maximum'], 2) }}</strong></div>
                        <p><i class="bi bi-info-circle" aria-hidden="true"></i>The recorded result differs from the score calculated from the currently reviewable answers.</p>
                    </div>
                @endif

                @if (! $attemptDetail['answers_available'])
                    <div class="lt-empty is-compact"><i class="bi bi-database-slash"></i><h3>Answer-level responses are not available</h3><p>Answer-level responses are not available from the connected assessment records. The persisted attempt metadata is shown above.</p></div>
                @elseif ($attemptDetail['questions']->isEmpty())
                    <div class="lt-empty is-compact"><i class="bi bi-ui-checks"></i><h3>No Quiz questions are available</h3><p>The attempt is preserved, but the current Quiz definition has no question records to display.</p></div>
                @elseif (! $attemptDetail['detailed_answers_available'])
                    <div class="lt-empty is-compact" data-detailed-answers-unavailable><i class="bi bi-card-checklist"></i><h3>Detailed answers unavailable</h3><p>The recorded attempt does not contain enough participant-answer data for a question-by-question review.</p></div>
                @else
                    @php
                        $answerStatusCounts = $attemptDetail['questions']->countBy('answer_status');
                    @endphp
                    <nav class="lt-question-nav" aria-label="Questions in this attempt" data-question-navigator>
                        <div class="lt-question-nav-summary">
                            <strong>{{ $attemptDetail['questions']->count() }} questions</strong>
                            <span>{{ $answerStatusCounts->get('correct', 0) }} correct &middot; {{ $answerStatusCounts->get('incorrect', 0) }} incorrect</span>
                        </div>
                        <div class="lt-question-nav-controls">
                            @foreach ($attemptDetail['questions'] as $question)
                                @php
                                    $questionNumber = $question->question_order ?: $loop->iteration;
                                    $navigationStatus = $answerStatusPresentation[$question->answer_status] ?? $answerStatusPresentation['manual'];
                                @endphp
                                <button type="button"
                                        class="lt-question-jump is-{{ $question->answer_status }} {{ $loop->first ? 'is-current' : '' }}"
                                        data-question-jump="#quiz-question-{{ $question->question_id }}"
                                        data-question-status="{{ $question->answer_status }}"
                                        aria-label="Question {{ $questionNumber }}: {{ $navigationStatus[0] }}"
                                        title="Question {{ $questionNumber }}: {{ $navigationStatus[0] }}"
                                        @if ($loop->first) aria-current="location" @endif>
                                    <span>Q{{ $questionNumber }}</span><i class="bi {{ $navigationStatus[1] }}" aria-hidden="true"></i>
                                </button>
                            @endforeach
                        </div>
                    </nav>
                    <div class="lt-answer-list">
                        @foreach ($attemptDetail['questions'] as $question)
                            @php
                                $questionTypeLabel = match ($question->question_type) {
                                    'mcq' => 'Multiple Choice',
                                    'checkbox' => 'Checkbox',
                                    'true_false' => 'True / False',
                                    'short_answer' => 'Short Answer',
                                    'essay' => 'Essay',
                                    default => ucfirst(str_replace('_', ' ', $question->question_type)),
                                };
                                $answerStatus = $answerStatusPresentation[$question->answer_status] ?? $answerStatusPresentation['manual'];
                            @endphp
                            <article class="lt-answer-card is-{{ $question->answer_status }}" id="quiz-question-{{ $question->question_id }}" data-question-card>
                                <header>
                                    <span>Question {{ $question->question_order ?: $loop->iteration }}</span>
                                    <span>{{ $questionTypeLabel }}</span>
                                    <strong>Maximum {{ number_format((float) $question->marks, 2) }} mark{{ (float) $question->marks === 1.0 ? '' : 's' }}</strong>
                                </header>
                                <div class="lt-answer-title-row">
                                    <h3>{{ $question->question_text }}</h3>
                                    <span class="lt-answer-status is-{{ $question->answer_status }}"><i class="bi {{ $answerStatus[1] }}"></i>{{ $answerStatus[0] }}</span>
                                </div>
                                @if ($question->image_url)
                                    <img src="{{ $question->image_url }}" alt="Image for Question {{ $question->question_order ?: $loop->iteration }}" class="lt-answer-image">
                                @endif
                                @if ($question->answer_options !== [])
                                    <div class="lt-answer-options">
                                        @foreach ($question->answer_options as $option)
                                            <div class="lt-answer-option is-{{ $option->state }} {{ $option->selected ? 'is-participant-selected' : '' }}" data-answer-option-state="{{ $option->state }}" @if ($option->selected) data-answer-source="participant" @elseif ($option->correct) data-answer-source="canonical" @endif>
                                                <span class="lt-answer-option-text">{{ $option->display }}</span>
                                                @if ($option->state === 'correct-selected')
                                                    <strong class="lt-answer-option-label is-participant"><span><i class="bi bi-person-fill"></i>Participant answer</span><span class="lt-answer-correctness is-correct"><i class="bi bi-check-circle-fill"></i>Correct</span></strong>
                                                @elseif ($option->state === 'wrong-selected')
                                                    <strong class="lt-answer-option-label is-participant"><span><i class="bi bi-person-fill"></i>Participant answer</span><span class="lt-answer-correctness is-incorrect"><i class="bi bi-x-circle-fill"></i>Incorrect</span></strong>
                                                @elseif ($option->state === 'correct-answer')
                                                    <strong class="lt-answer-option-label is-canonical"><i class="bi bi-check-circle-fill"></i>Correct answer</strong>
                                                @elseif ($option->state === 'correct-reference')
                                                    <strong class="lt-answer-option-label is-reference"><i class="bi bi-info-circle"></i>Correct answer <span>(reference)</span></strong>
                                                @elseif ($option->state === 'participant-selected-neutral')
                                                    <strong class="lt-answer-option-label is-participant"><i class="bi bi-person-fill"></i>Participant answer</strong>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if (in_array($question->question_type, ['mcq', 'checkbox', 'true_false'], true))
                                    @if ($question->answer_status === 'recorded_unavailable')
                                        <p class="lt-answer-note"><i class="bi bi-info-circle"></i>The saved response for this question cannot be read reliably.</p>
                                    @elseif ($question->answer_status === 'cannot_evaluate')
                                        <p class="lt-answer-note"><i class="bi bi-info-circle"></i>The canonical correct answer is missing or cannot be matched safely to the current options.</p>
                                    @endif
                                @else
                                    <div class="lt-answer-values">
                                        <div class="lt-answer-value is-{{ $question->answer_status }}"><span>Participant answer</span><strong>{{ $question->learner_answer ?? 'Not recorded' }}</strong></div>
                                    @if ($question->question_type === 'short_answer')
                                        <div><span>Expected answer</span><strong>{{ $question->display_correct_answer ?? 'Not recorded' }}</strong><small>Manual review is required; no automatic Short Answer grading contract is stored.</small></div>
                                    @elseif ($question->question_type === 'essay' && ! empty($question->grading_rubric))
                                        <div><span>Grading rubric</span><strong>{{ $question->grading_rubric }}</strong></div>
                                    @endif
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>
    @endif
</div>
