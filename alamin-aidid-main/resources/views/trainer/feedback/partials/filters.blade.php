@if ($courseOptions->isNotEmpty())
    @php($nonSearchFiltersActive = $filters['filter_course_id'] > 0 || $filters['period'] !== 'all' || $filters['rating'] !== 'all' || $filters['date_from'] !== '' || $filters['date_to'] !== '')
    <form id="feedbackFilterForm" class="fb-filter-toolbar" action="{{ route('trainer.feedback') }}" method="GET" aria-label="Feedback filters">
        <div class="fb-filter-grid">
        <div class="fb-filter-field fb-filter-training">
            <label class="ana-flabel" for="fbCourseFilter">Training</label>
            <select id="fbCourseFilter" class="ana-input" name="filter_course_id">
                <option value="0">All trainings</option>
                @foreach ($courseOptions as $course)
                    <option value="{{ $course['course_id'] }}" @selected($filters['filter_course_id'] === $course['course_id'])>{{ $course['course_name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="fb-filter-field">
            <label class="ana-flabel" for="fbPeriodFilter">Analysis Period</label>
            <select id="fbPeriodFilter" class="ana-input" name="period">
                <option value="all" @selected($filters['period'] === 'all')>All time</option>
                <option value="30" @selected($filters['period'] === '30')>Last 30 days</option>
                <option value="90" @selected($filters['period'] === '90')>Last 3 months</option>
                <option value="365" @selected($filters['period'] === '365')>Last year</option>
                <option value="custom" @selected($filters['period'] === 'custom')>Custom</option>
            </select>
        </div>
        <div class="fb-filter-field">
            <label class="ana-flabel" for="fbRatingFilter">Training Rating</label>
            <select id="fbRatingFilter" class="ana-input" name="rating">
                <option value="all" @selected($filters['rating'] === 'all')>All ratings</option>
                <option value="high" @selected($filters['rating'] === 'high')>High (4.0+)</option>
                <option value="mid" @selected($filters['rating'] === 'mid')>Mid (3.0–3.99)</option>
                <option value="low" @selected($filters['rating'] === 'low')>Low (below 3.0)</option>
            </select>
        </div>
        <div class="fb-filter-field fb-search-filter">
            <label class="ana-flabel" for="fbSearch">Search Comments</label>
            <div class="fb-search-control">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="fbSearch" class="ana-input" name="search" value="{{ $filters['search'] }}" placeholder="Search written comments" autocomplete="off">
                <button type="button" class="fb-search-clear" data-feedback-clear-search aria-label="Clear comment search" @if ($filters['search'] === '') hidden @endif><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
        </div>
        </div>
        <div class="ana-daterange fb-custom-dates" data-feedback-custom-dates @if ($filters['period'] !== 'custom') hidden @endif>
            <div><label class="ana-flabel" for="fbDateFrom">From</label><input id="fbDateFrom" name="date_from" type="date" value="{{ $filters['date_from'] }}" class="ana-input"></div>
            <div><label class="ana-flabel" for="fbDateTo">To</label><input id="fbDateTo" name="date_to" type="date" value="{{ $filters['date_to'] }}" class="ana-input"></div>
        </div>
        <button type="button" class="ana-reset-link fb-reset" data-feedback-reset @if (! $nonSearchFiltersActive) hidden @endif><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset Filters</button>
        <button type="submit" class="fb-visually-hidden">Apply filters</button>
    </form>
@endif
