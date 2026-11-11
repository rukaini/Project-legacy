@if ($selectedQuestion)
    <div class="fb-question-detail" data-question-id="{{ $selectedQuestion['id'] }}">
        <div class="fb-question-prompt">
            <span>Selected question</span>
            <h3>{{ $selectedQuestion['question'] }}</h3>
        </div>
        <div class="fb-question-result">
            <div class="fb-score-summary" aria-label="Question rating summary">
                <strong>{{ $selectedQuestion['avg'] !== null ? number_format($selectedQuestion['avg'], 2) : '—' }} <span>/ 5</span></strong>
                <p>{{ $selectedQuestion['count'] }} {{ \Illuminate\Support\Str::plural('response', $selectedQuestion['count']) }}</p>
            </div>
            <div class="fb-rating-bars" aria-label="Rating distribution from 5 to 1 stars">
                @php($distributionTotal = array_sum($selectedQuestion['distribution']))
                @for ($rating = 5; $rating >= 1; $rating--)
                    @php($count = $selectedQuestion['distribution'][$rating] ?? 0)
                    @php($percentage = $distributionTotal > 0 ? ($count / $distributionTotal) * 100 : 0)
                    <div class="fb-rating-bar" role="group" aria-label="{{ $rating }} stars: {{ $count }} {{ \Illuminate\Support\Str::plural('response', $count) }}">
                        <span>{{ $rating }} <i class="bi bi-star-fill" aria-hidden="true"></i></span>
                        <span class="fb-rating-track"><span class="fb-rating-fill" data-width="{{ $percentage }}"></span></span>
                        <strong>{{ $count }}</strong>
                    </div>
                @endfor
            </div>
        </div>

        <section class="fb-comments-section" aria-labelledby="feedbackCommentsTitle">
            <div class="fb-comments-heading"><h4 id="feedbackCommentsTitle">Written Comments <span>({{ $selectedComments['commentTotal'] }})</span></h4></div>
            <div class="fb-comment-stream"
                 data-feedback-comment-stream
                 data-course-id="{{ $filters['course_id'] }}"
                 data-question-id="{{ $filters['question_id'] }}">
                @include('trainer.feedback.partials.comment-list', $selectedComments)
            </div>
        </section>
    </div>
@endif
