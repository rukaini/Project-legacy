<section class="ana-panel ana-filter-card" aria-labelledby="report-filters-title">
    <div class="ana-panel-head">
        <div><h2 id="report-filters-title">Report Filters</h2><p>Choose the training and period data to display.</p></div>
    </div>
    <form id="analyticsFilterForm" method="GET" action="{{ route('trainer.analytics') }}" class="ana-filter" aria-label="Analytics report filters">
        <input type="hidden" name="section" value="{{ $section }}">
        <div class="ana-filter-row">
            <div><span class="ana-flabel">Training</span><select id="trainingSelector" name="course_id" class="ana-input" @disabled($trainings->isEmpty())>@forelse ($trainings as $training)<option value="{{ $training->course_id }}" @selected($selectedTraining && (string) $selectedTraining->course_id === (string) $training->course_id)>{{ $training->course_name }} - {{ $training->training_type }}{{ $training->period_label ? ' · ' . $training->period_label : '' }}</option>@empty<option>No assigned training</option>@endforelse</select></div>
            @if ($selectedTraining)<div><span class="ana-flabel">Analysis Period</span><select id="reportDateSelector" name="report_date" class="ana-input"><option value="all" @selected($reportDate === 'all')>All dates</option><option value="7" @selected($reportDate === '7')>Last 7 days</option><option value="30" @selected($reportDate === '30')>Last 30 days</option><option value="custom" @selected($reportDate === 'custom')>Custom date range</option></select></div>@endif
        </div>
        @if ($selectedTraining)<div class="ana-daterange" @if ($reportDate !== 'custom') hidden @endif><div><span class="ana-flabel">From</span><input id="dateFrom" name="date_from" type="date" value="{{ $dateFrom }}" class="ana-input"></div><div><span class="ana-flabel">To</span><input id="dateTo" name="date_to" type="date" value="{{ $dateTo }}" class="ana-input"></div></div>@endif
        @if ($selectedTraining && $contextFilterActive)<a href="{{ route('trainer.analytics', ['course_id' => $selectedTraining->course_id]) }}" class="ana-reset-link">Reset</a>@endif
    </form>
    @if ($selectedTraining)<div class="ana-identity" aria-labelledby="selected-training-name"><div class="ana-identity-main"><div class="ana-badges"><span class="ana-type-badge {{ $selectedTraining->training_type === 'LMS' ? 'lms' : 'simple' }}">{{ $selectedTraining->training_type }}</span>@if ($selectedTraining->status)<span class="ana-status-badge">{{ $selectedTraining->status }}</span>@endif</div><h2 id="selected-training-name">{{ $selectedTraining->course_name }}</h2><div class="ana-identity-meta"><span>{{ $selectedTraining->period_label ?: 'Date not available' }}</span><span>{{ $selectedTraining->participant_count }} participants</span><span>{{ $selectedTraining->session_count }} assigned sessions</span></div></div></div>@endif
</section>
