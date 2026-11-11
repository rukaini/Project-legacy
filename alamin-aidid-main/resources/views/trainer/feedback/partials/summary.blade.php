<section class="ana-metrics fb-summary-grid" aria-label="Feedback summary">
    <article class="ana-metric fb-summary-card">
        <div class="ana-metric-icon fb-summary-icon-blue" aria-hidden="true"><i class="bi bi-star-fill"></i></div>
        <div class="ana-metric-label">Average Rating</div>
        <h2 class="ana-metric-value">{{ $liveRating !== null ? number_format($liveRating, 2) : '—' }} <span class="fb-metric-unit">/ 5.00</span></h2>
        <div class="ana-metric-note">Across participant ratings</div>
    </article>
    <article class="ana-metric fb-summary-card">
        <div class="ana-metric-icon fb-summary-icon-green" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
        <div class="ana-metric-label">Total Respondents</div>
        <h2 class="ana-metric-value">{{ $totalRespondents }}</h2>
        <div class="ana-metric-note">Across {{ $totalCourses }} {{ \Illuminate\Support\Str::plural('training', $totalCourses) }}</div>
    </article>
    <article class="ana-metric fb-summary-card">
        <div class="ana-metric-icon fb-summary-icon-amber" aria-hidden="true"><i class="bi bi-folder2-open"></i></div>
        <div class="ana-metric-label">Trainings With Feedback</div>
        <h2 class="ana-metric-value">{{ $totalCourses }}</h2>
        <div class="ana-metric-note">Feedback reports available</div>
    </article>
    <article class="ana-metric fb-summary-card">
        <div class="ana-metric-icon fb-summary-icon-blue" aria-hidden="true"><i class="bi bi-chat-left-text-fill"></i></div>
        <div class="ana-metric-label">Written Comments</div>
        <h2 class="ana-metric-value">{{ $writtenComments }}</h2>
        <div class="ana-metric-note">Written responses received</div>
    </article>
</section>
