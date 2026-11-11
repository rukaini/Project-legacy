<div class="sw-shell">
    @include('trainer.simple.partials.header')
    <main class="sw-page app-workspace-shell">
        <section class="sw-panel" aria-labelledby="attendance-title">
            <div class="sw-panel-header"><div><h2 class="sw-panel-title" id="attendance-title">Attendance</h2><p class="sw-panel-sub">Review attendance records and display the session QR code during active sessions.</p></div><span class="sw-read-only"><i class="bi bi-eye"></i> Read only</span></div>
            <form action="{{ route('trainer.simple.attendance', $course->course_id) }}" method="GET" class="sw-report-toolbar" data-simple-report-form data-results-id="simpleAttendanceResults">
                <label class="sw-filter-field"><span class="sw-filter-label">Viewing Session</span><select name="session_id" class="sw-select" @disabled($sessions->isEmpty())>@forelse ($sessions as $session)<option value="{{ $session->session_id }}" @selected((string) $attendanceFilters['session_id'] === (string) $session->session_id)>{{ $session->short_date_label }} — {{ $session->session_title ?: 'Training Session' }} · {{ $session->display_status }}</option>@empty<option value="">No sessions scheduled</option>@endforelse</select></label>
                <label class="sw-filter-field sw-search-field"><span class="sw-filter-label">Search</span><span class="sw-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $attendanceFilters['search'] }}" placeholder="Search by name or email..." autocomplete="off" data-simple-search><button type="button" class="sw-search-clear" data-simple-search-clear aria-label="Clear attendance search" @if ($attendanceFilters['search'] === '') hidden @endif>&times;</button></span></label>
                <label class="sw-filter-field"><span class="sw-filter-label">Status</span><select name="status" class="sw-select"><option value="all" @selected($attendanceFilters['status'] === 'all')>All Records</option><option value="recorded" @selected($attendanceFilters['status'] === 'recorded')>Recorded</option><option value="not-recorded" @selected($attendanceFilters['status'] === 'not-recorded')>Not Recorded</option></select></label>
            </form>
            @include('trainer.simple.attendance.partials.results')
        </section>
    </main>
    @include('trainer.attendance.partials.qr-modal')
</div>
