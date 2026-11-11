@php
    $attendanceLabels = ['Recorded', 'Not Recorded'];
    $attendanceChartRows = $organizationAttendanceRows
        ->filter(fn ($training) => $training->attendance_summary['available'])
        ->sortByDesc(fn ($training) => $training->attendance_summary['rate'])
        ->values();
    $attendanceTrainingChartData = $attendanceChartRows->map(fn ($training) => [
        'title' => $training->course_name,
        'rate' => $training->attendance_summary['rate'],
        'participants' => $training->participant_count,
        'sessions' => $training->session_count,
    ])->values();
    $quizTrainingChartData = $organizationQuizRows->map(fn ($training) => [
        'title' => $training->course_name,
        'quizzes' => $training->quiz_count,
        'attempts' => $training->graded_attempts,
        'average' => $training->average_percent,
    ])->values();
    $attendanceTrainingChartHeight = max(320, min(380, min(8, $attendanceChartRows->count()) * 48 + 80));
    $quizTrainingChartHeight = max(280, min(360, min(8, $organizationQuizRows->count()) * 48 + 72));
@endphp

<section class="ana-metrics" aria-label="Organization analytics summary">
    @foreach ($organizationMetrics as $metric)
        <article class="ana-metric ana-tone-{{ $metric['tone'] }}" data-admin-analytics-metric>
            <div class="ana-metric-icon"><i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i></div>
            <div class="ana-metric-copy">
                <div class="ana-metric-label">{{ $metric['label'] }}</div>
                <div class="ana-metric-value">{{ $metric['value'] }}</div>
                <div class="ana-metric-note">{{ $metric['note'] }}</div>
            </div>
        </article>
    @endforeach
</section>

<section class="admin-analytics-report-stack" aria-label="Organization-wide analytics reports">
    <article class="ana-panel admin-analytics-report-card">
        <div class="ana-panel-head ana-report-head">
            <div>
                <span class="ana-eyebrow">Organization report</span>
                <h2>Attendance Overview</h2>
                <p>Canonical attendance records are read-only; row existence determines Recorded or Not Recorded.</p>
            </div>
            @if ($organizationAttendance['available'])
                <span class="ana-rate">{{ $organizationAttendance['rate'] }}% records captured</span>
            @endif
        </div>

        @if ($organizationAttendance['available'])
            <div class="admin-analytics-chart-grid">
                <div class="ana-chart-box admin-analytics-donut-box">
                    <h3 class="admin-analytics-chart-title">Attendance Record Coverage</h3>
                    <div class="admin-chart-canvas-wrap admin-chart-canvas-wrap--donut">
                        <canvas id="adminAttendanceDonutChart" role="img" aria-label="Organization attendance record coverage doughnut chart" data-values='@json(array_values($organizationAttendance['counts']))' data-labels='@json($attendanceLabels)'></canvas>
                    </div>
                    <div class="ana-chart-legend ana-attendance-legend">
                        @foreach ($attendanceLabels as $label)
                            <span class="{{ strtolower(str_replace(' ', '-', $label)) }}">{{ $label }}: {{ $organizationAttendance['counts'][$label] }} ({{ round($organizationAttendance['counts'][$label] / max(1, $organizationAttendance['total']) * 100) }}%)</span>
                        @endforeach
                    </div>
                </div>
                <div class="ana-chart-box admin-analytics-ranking-box">
                    <h3 class="admin-analytics-chart-title">Record Coverage by Training</h3>
                    @if ($attendanceChartRows->isNotEmpty())
                        <div class="admin-chart-canvas-wrap admin-chart-canvas-wrap--ranking" style="--admin-chart-height: {{ $attendanceTrainingChartHeight }}px">
                            <canvas id="adminAttendanceTrainingChart" role="img" aria-label="Attendance record coverage by training" data-results='@json($attendanceTrainingChartData)'></canvas>
                        </div>
                    @else
                        <div class="ana-compact-empty">No attendance record coverage is available for this selection.</div>
                    @endif
                </div>
            </div>
            @if ($attendanceChartRows->count() > 8)
                <p class="ana-chart-note">Chart shows the top 8 trainings by record coverage. All trainings are listed below.</p>
            @endif
        @else
            <div class="ana-compact-empty"><i class="bi bi-calendar2-minus" aria-hidden="true"></i><span>No enrolled participant-session attendance slots are available for this selection.</span></div>
        @endif

        <div class="ana-table-divider">
            <div class="ana-table-title">Attendance by training</div>
            <div class="ana-table-wrap">
                <table class="ana-table admin-analytics-table" data-noun="trainings">
                    <thead><tr><th>Training</th><th>Type</th><th>Participants</th><th>Sessions</th><th>Record coverage</th><th>Recorded</th><th>Not recorded</th></tr></thead>
                    <tbody>
                        @forelse ($organizationAttendanceRows as $training)
                            <tr>
                                <td>{{ $training->course_name }}</td>
                                <td>{{ $training->training_type === 'LMS' ? 'LMS' : 'Simple' }}</td>
                                <td>{{ $training->participant_count }}</td>
                                <td>{{ $training->session_count }}</td>
                                <td>{{ $training->attendance_summary['available'] ? $training->attendance_summary['rate'].'%' : '—' }}</td>
                                <td>{{ $training->attendance_summary['counts']['Recorded'] }}</td>
                                <td>{{ $training->attendance_summary['counts']['Not Recorded'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="ana-inline-empty">No trainings are available for this selection.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    @if ($organizationQuizRows->isNotEmpty())
        <article class="ana-panel admin-analytics-report-card">
            <div class="ana-panel-head ana-report-head">
                <div>
                    <span class="ana-eyebrow">LMS performance</span>
                    <h2>LMS Quiz Overview</h2>
                    <p>Compare average quiz marks across LMS trainings.</p>
                </div>
            </div>
            <div class="ana-chart-box admin-analytics-quiz-box">
                <h3 class="admin-analytics-chart-title">Average Quiz Mark by Training</h3>
                <div class="admin-chart-canvas-wrap admin-chart-canvas-wrap--ranking" style="--admin-chart-height: {{ $quizTrainingChartHeight }}px">
                    <canvas id="adminQuizTrainingChart" role="img" aria-label="Average quiz mark by LMS training" data-results='@json($quizTrainingChartData)'></canvas>
                </div>
            </div>
            @if ($organizationQuizRows->count() > 8)
                <p class="ana-chart-note">Chart shows the top 8 LMS trainings by average quiz mark. All LMS trainings are listed below.</p>
            @endif
            <div class="ana-table-divider">
                <div class="ana-table-title">Quiz performance by training</div>
                <div class="ana-table-wrap">
                    <table class="ana-table admin-analytics-table" data-noun="trainings">
                        <thead><tr><th>Training</th><th>Quizzes</th><th>Graded attempts</th><th>Average mark</th><th>Highest quiz average</th><th>Lowest quiz average</th></tr></thead>
                        <tbody>
                            @foreach ($organizationQuizRows as $training)
                                <tr>
                                    <td>{{ $training->course_name }}</td>
                                    <td>{{ $training->quiz_count }}</td>
                                    <td>{{ $training->graded_attempts }}</td>
                                    <td>{{ number_format($training->average_percent, 1) }}%</td>
                                    <td>{{ $training->highest_quiz_average !== null ? number_format($training->highest_quiz_average, 1).'%' : '—' }}</td>
                                    <td>{{ $training->lowest_quiz_average !== null ? number_format($training->lowest_quiz_average, 1).'%' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </article>
    @endif
</section>
