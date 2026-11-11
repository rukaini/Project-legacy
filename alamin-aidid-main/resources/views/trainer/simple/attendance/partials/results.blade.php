@php
    $statusInfo ??= function ($value) {
        $label = $value ?: 'Not Recorded';
        return [$label, str_replace(' ', '-', strtolower($label))];
    };
    $locationFor ??= function ($session) {
        $location = trim((string) ($session->location ?? ''));
        $online = str_contains(strtolower((string) ($session->session_type ?? '')), 'online')
            || str_contains(strtolower((string) ($session->session_type ?? '')), 'virtual');
        $url = str_starts_with($location, 'http://') || str_starts_with($location, 'https://') ? $location : null;
        return [$url ? 'Join meeting' : ($location ?: ($online ? 'Online session' : 'Location not set')), $online || $url ? 'bi-camera-video' : 'bi-geo-alt', $url];
    };
@endphp
<div id="simpleAttendanceResults" class="sw-results-region">
    <span class="sw-loading-label">Updating attendance…</span>
    <div class="sw-report-error" data-simple-report-error hidden>Attendance could not be refreshed. Please try again.</div>
    @if (! $selectedSession)
        <div class="sw-empty"><i class="bi bi-calendar-x"></i><div class="sw-empty-title">No sessions scheduled</div><div>Sessions must be set up before attendance can be viewed.</div></div>
    @else
        @php($place = $locationFor($selectedSession))
        <div class="sw-session-summary">
            <div><span class="sw-session-kicker">Selected session</span><div class="sw-session-heading"><h3>{{ $selectedSession->session_title ?: 'Training Session' }}</h3><span class="sw-status-badge {{ $selectedSession->display_status_class }}">{{ $selectedSession->display_status }}</span></div><div class="sw-session-details"><span><i class="bi bi-calendar3"></i>{{ $selectedSession->date_label }}</span><span><i class="bi bi-clock"></i>{{ $selectedSession->time_label }}</span><span><i class="bi {{ $place[1] }}"></i>@if ($place[2])<a href="{{ $place[2] }}" target="_blank" rel="noopener">{{ $place[0] }}</a>@else{{ $place[0] }}@endif</span></div></div>
            @include('trainer.attendance.partials.qr-panel')
        </div>
        @if ($attendancePaginator->total() > 0)
            <div class="sw-table-wrap"><table class="sw-table"><thead><tr><th>Participant</th><th>Email</th><th>Status</th><th>Recorded</th></tr></thead><tbody>
                @foreach ($attendancePaginator as $participant)
                    @php([$statusLabel, $statusClass] = $statusInfo($participant->attendance_status))
                    <tr><td><div class="sw-participant-cell"><span class="sw-avatar">{{ $participant->initial }}</span><span class="sw-cell-name">{{ $participant->participant_name }}</span></div></td><td class="sw-cell-email">{{ $participant->participant_email ?: '—' }}</td><td><span class="sw-status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td><td class="sw-cell-date">{{ $participant->self_recorded_at ? \Carbon\Carbon::parse($participant->self_recorded_at)->format('d M Y, h:i A') : 'Not recorded' }}</td></tr>
                @endforeach
            </tbody></table></div>
            @include('trainer.simple.partials.paginator', ['paginator' => $attendancePaginator, 'state' => $attendanceFilters, 'noun' => 'participants', 'reportPath' => route('trainer.simple.attendance', $course->course_id)])
        @else
            <div class="sw-empty"><i class="bi bi-search"></i><div class="sw-empty-title">{{ $attendanceCounts['total'] === 0 ? 'No participants are enrolled in this training' : 'No participants match these filters' }}</div><div>{{ $attendanceCounts['total'] === 0 ? 'Attendance will appear when participants are enrolled.' : 'Try changing the selected status or search.' }}</div></div>
        @endif
    @endif
</div>
