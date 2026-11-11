@php
    $calendarBaseDate = $selectedSession && ! empty($selectedSession->date) ? \Carbon\Carbon::parse($selectedSession->date) : now();
    $calendarSessionsData = [];
    foreach ($sessions as $session) {
        if (empty($session->date)) continue;
        $dateKey = \Carbon\Carbon::parse($session->date)->format('Y-m-d');
        $type = strtolower($session->session_type ?? '');
        $isOnline = str_contains($type, 'online') || str_contains($type, 'virtual');
        $calendarSessionsData[$dateKey] ??= [];
        $calendarSessionsData[$dateKey][] = [
            'id' => $session->session_id,
            'title' => $session->session_title ?? 'Untitled Session',
            'type_label' => $isOnline ? 'Online' : 'Physical',
            'is_online' => $isOnline,
            'time_label' => $session->time_label,
            'url' => route('trainer.courses.attendance', ['course_id' => $course->course_id, 'session_id' => $session->session_id]),
        ];
    }
    $initialCalendarDate = $calendarBaseDate->format('Y-m-d');
@endphp

@include('trainer.training.partials.workspace-header', ['activeLmsTab' => 'attendance'])

<main class="lw-page app-workspace-shell">
    @include('trainer.training.partials.workspace-page-heading', [
        'title' => 'Attendance',
        'description' => 'Review attendance records and display the session QR code during active sessions.',
    ])

    <div class="lw-att-row">
        <aside id="sessionSidebar" class="lw-att-sidebar">
            <div class="lw-sb-collapsed"><button type="button" data-session-sidebar-toggle class="lw-sb-collapsed-btn" aria-label="Show sessions" title="Show sessions" aria-expanded="false"><i class="bi bi-list-ul"></i></button></div>
            <div class="lw-sb-expanded">
                <div class="lw-sb-head"><div class="lw-min-0"><h2>Session List</h2><p>Select a session to view attendance.</p></div><button type="button" data-session-sidebar-toggle class="lw-sb-collapse-btn" aria-label="Hide sessions" title="Hide sessions" aria-expanded="true"><i class="bi bi-layout-sidebar-inset"></i></button></div>
                <div class="lw-sb-filter-bar"><div class="lw-sb-chips"><button type="button" data-session-filter="all" class="lw-sb-chip is-active">All</button><button type="button" data-session-filter="upcoming" class="lw-sb-chip">Upcoming</button><button type="button" data-session-filter="completed" class="lw-sb-chip">Completed</button></div><div class="lw-sb-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" data-session-search placeholder="Search sessions..." autocomplete="off"><button type="button" class="lw-search-clear" data-session-search-clear aria-label="Clear session search" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button></div></div>
                <div class="lw-sb-list" id="sessionList">
                    @forelse ($sessions as $session)
                        @php($isActive = $selectedSession && (string) $selectedSession->session_id === (string) $session->session_id)
                        @php($isOnline = str_contains(strtolower($session->session_type ?? ''), 'online') || str_contains(strtolower($session->session_type ?? ''), 'virtual'))
                        <a href="{{ route('trainer.courses.attendance', ['course_id' => $course->course_id, 'session_id' => $session->session_id]) }}" @class(['lw-session-card', 'is-active' => $isActive]) data-lms-session-link data-session-id="{{ $session->session_id }}" data-session-search="{{ strtolower(($session->session_title ?? '').' '.($session->session_type ?? '').' '.($session->date_label ?? '')) }}" data-session-group="{{ $session->status_group }}">
                            <div class="lw-session-top"><span class="lw-type-pill {{ $isOnline ? 'online' : 'physical' }}"><i class="bi {{ $isOnline ? 'bi-camera-video' : 'bi-geo-alt' }}"></i> {{ $isOnline ? 'Online' : 'Physical' }}</span><span class="lw-session-status {{ $session->display_status_class }}">{{ $session->display_status }}</span><span class="lw-session-short-date">{{ $session->short_date_label }}</span></div>
                            <h3 class="lw-session-title">{{ $session->session_title ?? 'Untitled Session' }}</h3><p class="lw-session-sub">{{ $course->course_name }}</p><div class="lw-session-line"><i class="bi bi-clock"></i><span>{{ $session->time_label }}</span></div>
                            @if ($session->location)<div class="lw-session-line"><i class="bi {{ $isOnline ? 'bi-link-45deg' : 'bi-pin-map' }}"></i><span class="loc">{{ $session->location }}</span></div>@endif
                        </a>
                    @empty
                        <div class="lw-sb-empty"><div class="lw-sb-empty-icon"><i class="bi bi-calendar-x"></i></div><h4>No session found</h4><p>Sessions must be created by the admin first.</p></div>
                    @endforelse
                    @if ($sessions->isNotEmpty())<div id="noSessionFilterResult" class="lw-sb-empty lw-hidden"><div class="lw-sb-empty-icon"><i class="bi bi-search"></i></div><h4>No matching sessions</h4><p>Try changing the search keyword or filter.</p></div>@endif
                </div>
            </div>
        </aside>

        <section class="lw-att-main">
            @include('trainer.training.attendance.partials.detail')
            @if ($selectedSession)
                <div class="lw-roster">
                    <form class="lw-roster-filters" action="{{ route('trainer.courses.attendance', $course->course_id) }}" method="get" data-lms-attendance-form>
                        <input type="hidden" name="session_id" value="{{ $attendanceFilters['session_id'] }}">
                        <input type="hidden" name="rows" value="{{ $attendanceFilters['rows'] }}">
                        <div class="lw-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $attendanceFilters['search'] }}" placeholder="Search participant..." autocomplete="off" data-lms-search><button type="button" class="lw-search-clear" data-lms-search-clear aria-label="Clear search" @if ($attendanceFilters['search'] === '') hidden @endif><i class="bi bi-x"></i></button></div>
                        <div class="lw-select-group"><select name="status" class="lw-select"><option value="all">All Records</option><option value="recorded" @selected($attendanceFilters['status'] === 'recorded')>Recorded</option><option value="not-recorded" @selected($attendanceFilters['status'] === 'not-recorded')>Not Recorded</option></select></div>
                    </form>
                    @include('trainer.training.attendance.partials.results')
                </div>
            @endif
        </section>
    </div>
</main>

@include('trainer.attendance.partials.qr-modal')

<div id="calendarModal" class="lw-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="calendarTitle" aria-hidden="true" tabindex="-1">
    <div class="lw-modal-box">
        <div class="lw-modal-head"><div><div class="lw-calendar-nav"><button type="button" data-calendar-month="-1" class="lw-btn" title="Previous month"><i class="bi bi-chevron-left"></i></button><button type="button" data-calendar-today class="lw-btn">Today</button><h2 id="calMonthYear">&nbsp;</h2><button type="button" data-calendar-month="1" class="lw-btn" title="Next month"><i class="bi bi-chevron-right"></i></button></div><p id="calendarTitle">Session calendar for {{ $course->course_name }} · Arrow keys to change month · Esc to close</p></div><button type="button" data-calendar-close class="lw-modal-close" aria-label="Close calendar"><i class="bi bi-x-lg"></i></button></div>
        <div class="lw-modal-body"><div class="lw-cal"><div class="lw-cal-head">@foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)<div class="lw-cal-head-day">{{ $dayName }}</div>@endforeach</div><div id="calendarGrid" class="lw-cal-grid"></div></div></div>
    </div>
</div>
<div id="dayDetailPopover" class="lw-day-pop-overlay is-hidden"><div class="lw-day-pop"><div class="lw-day-pop-head"><div><h3 id="dayDetailTitle">&nbsp;</h3><p id="dayDetailSub">&nbsp;</p></div><button type="button" data-day-detail-close class="lw-modal-close"><i class="bi bi-x-lg"></i></button></div><div id="dayDetailList" class="lw-day-pop-list"></div></div></div>

@include('trainer.training.attendance.partials.scripts')
