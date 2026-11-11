<div id="feedbackDynamicRegion" class="ana-page app-page-shell" aria-live="polite">
    <header class="ana-head"><div class="ana-head-left">
        <h1 class="ana-title">Feedback</h1>
        <p class="ana-subtitle">Review ratings and comments from your training participants.</p>
    </div></header>
    <div id="feedbackSummaryRegion">
        @include('trainer.feedback.partials.summary')
    </div>
    <section class="fb-feedback-shell" aria-label="Feedback workspace">
        @include('trainer.feedback.partials.filters')
        <div id="feedbackResultsRegion">
            @include('trainer.feedback.partials.course-list')
        </div>
    </section>
</div>
