<section id="feedbackTrainingDetail" class="fb-training-detail" aria-labelledby="feedbackDetailTitle">
    @if ($selectedTraining)
        <div class="fb-detail-header">
            <div class="fb-detail-identity">
                <span class="ana-eyebrow">Selected training</span>
                <h2 id="feedbackDetailTitle">{{ $selectedTraining['course_name'] }}</h2>
                <p>{{ $selectedTraining['form_title'] }}</p>
            </div>
            <div class="fb-detail-stats">
                <div><strong>{{ $selectedTraining['respondent_count'] }}</strong><span>Respondents</span></div>
                <div><strong>{{ $selectedTraining['avg_rating'] !== null ? number_format($selectedTraining['avg_rating'], 2) : '—' }}</strong><span>Average rating</span></div>
            </div>
        </div>

        @if ($feedbackQuestions->isNotEmpty())
            <section class="fb-question-toolbar" aria-labelledby="feedbackQuestionAnalysisTitle">
                <div class="fb-question-heading">
                    <div>
                        <h3 id="feedbackQuestionAnalysisTitle">Question Analysis</h3>
                        <p>Review ratings and written responses for each question.</p>
                    </div>
                    <span>Question {{ $feedbackQuestions->search(fn ($question) => $question['id'] === $filters['question_id']) + 1 }} of {{ $feedbackQuestions->count() }}</span>
                </div>
                <div class="fb-question-filter">
                    <label class="ana-flabel" for="feedbackQuestionSelector">Question</label>
                    <select id="feedbackQuestionSelector" class="ana-input" data-feedback-question-selector>
                        @foreach ($feedbackQuestions as $index => $question)
                            <option value="{{ $question['id'] }}" @selected($filters['question_id'] === $question['id'])>Question {{ $index + 1 }} — {{ $question['question'] }}</option>
                        @endforeach
                    </select>
                </div>
            </section>
            @include('trainer.feedback.partials.question-results')
        @else
            <div class="ana-compact-empty fb-detail-empty"><i class="bi bi-chat-square-text" aria-hidden="true"></i><span>No usable feedback questions are available for this selection.</span></div>
        @endif
    @endif
</section>
