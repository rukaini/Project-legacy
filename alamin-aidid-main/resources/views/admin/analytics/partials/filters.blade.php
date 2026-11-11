<section class="ana-panel ana-filter-card" aria-labelledby="admin-report-filters-title">
    <div class="ana-panel-head">
        <div>
            <h2 id="admin-report-filters-title">Report Filters</h2>
            <p>Choose the training scope, type, and reporting period.</p>
        </div>
    </div>
    <form id="adminAnalyticsFilterForm" method="GET" action="{{ route('admin.analytics') }}" class="ana-filter" aria-label="Admin analytics report filters">
        <div class="admin-analytics-filter-row">
            <div>
                <label class="ana-flabel" for="adminTrainingSelector">Training</label>
                <select id="adminTrainingSelector" name="course_id" class="ana-input">
                    <option value="0">All trainings</option>
                    @foreach ($trainings as $training)
                        <option value="{{ $training->course_id }}" @selected($selectedTraining && (string) $selectedTraining->course_id === (string) $training->course_id)>
                            {{ $training->course_name }} - {{ $training->training_type === 'LMS' ? 'LMS' : 'Simple' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ana-flabel" for="adminTrainingTypeSelector">Training Type</label>
                <select id="adminTrainingTypeSelector" name="training_type" class="ana-input">
                    <option value="all" @selected($trainingType === 'all')>All types</option>
                    <option value="simple" @selected($trainingType === 'simple')>Simple</option>
                    <option value="lms" @selected($trainingType === 'lms')>LMS</option>
                </select>
            </div>
            <div>
                <label class="ana-flabel" for="adminReportDateSelector">Analysis Period</label>
                <select id="adminReportDateSelector" name="report_date" class="ana-input">
                    <option value="all" @selected($reportDate === 'all')>All dates</option>
                    <option value="7" @selected($reportDate === '7')>Last 7 days</option>
                    <option value="30" @selected($reportDate === '30')>Last 30 days</option>
                    <option value="custom" @selected($reportDate === 'custom')>Custom date range</option>
                </select>
            </div>
        </div>
        <div class="ana-daterange" @if ($reportDate !== 'custom') hidden @endif>
            <div>
                <label class="ana-flabel" for="adminDateFrom">From</label>
                <input id="adminDateFrom" name="date_from" type="date" value="{{ $dateFrom }}" class="ana-input">
            </div>
            <div>
                <label class="ana-flabel" for="adminDateTo">To</label>
                <input id="adminDateTo" name="date_to" type="date" value="{{ $dateTo }}" class="ana-input">
            </div>
        </div>
        @if ($contextFilterActive)
            <a href="{{ route('admin.analytics') }}" class="ana-reset-link">Reset</a>
        @endif
    </form>

    @if ($selectedTraining)
        <div class="ana-identity" aria-labelledby="admin-selected-training-name">
            <div class="ana-identity-main">
                <div class="ana-badges">
                    <span class="ana-type-badge {{ $selectedTraining->training_type === 'LMS' ? 'lms' : 'simple' }}">{{ $selectedTraining->training_type === 'LMS' ? 'LMS' : 'Simple Training' }}</span>
                    @if ($selectedTraining->status)<span class="ana-status-badge">{{ $selectedTraining->status }}</span>@endif
                </div>
                <h2 id="admin-selected-training-name">{{ $selectedTraining->course_name }}</h2>
                <div class="ana-identity-meta">
                    <span>{{ $selectedTraining->period_label ?: 'Date not available' }}</span>
                    <span>{{ $selectedTraining->participant_count }} participants</span>
                    <span>{{ $selectedTraining->session_count }} sessions</span>
                </div>
            </div>
        </div>
    @endif
</section>
