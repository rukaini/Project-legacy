<section class="sw-panel sw-session-panel mb-4" aria-labelledby="session-priority-title">
    <div class="sw-panel-header">
        <div>
            <h2 class="sw-panel-title" id="session-priority-title">{{ $featuredLabel ?: 'Session Schedule' }}</h2>
            <p class="sw-panel-sub">Your assigned schedule for this training.</p>
        </div>
        @if ($featuredSession)
            <span class="sw-session-state {{ $featuredSession->schedule_group }}">{{ $featuredSession->schedule_group === 'current' ? 'Happening now' : 'Upcoming' }}</span>
        @endif
    </div>

    @if ($featuredSession)
        @php($featuredPlace = $sessionPlace($featuredSession))
        <div class="sw-featured-session">
            <div class="sw-session-date-tile">
                @if ($featuredSession->date)
                    <span>{{ \Carbon\Carbon::parse($featuredSession->date)->format('M') }}</span>
                    <strong>{{ \Carbon\Carbon::parse($featuredSession->date)->format('d') }}</strong>
                    <small>{{ \Carbon\Carbon::parse($featuredSession->date)->format('Y') }}</small>
                @else
                    <strong>—</strong><small>Date not set</small>
                @endif
            </div>
            <div class="sw-featured-copy">
                <h3>{{ $featuredSession->session_title ?: 'Training Session' }}</h3>
                <div class="sw-session-meta">
                    <span><i class="bi bi-calendar-event"></i>{{ $featuredSession->date_label }}</span>
                    <span><i class="bi bi-clock"></i>{{ $featuredSession->time_label }}</span>
                    <span>
                        <i class="bi {{ $featuredPlace['icon'] }}"></i>
                        @if ($featuredPlace['url'])
                            <a href="{{ $featuredPlace['url'] }}" target="_blank" rel="noopener">{{ $featuredPlace['label'] }} <i class="bi bi-box-arrow-up-right"></i></a>
                        @else
                            {{ $featuredPlace['label'] }}
                        @endif
                    </span>
                    @if ($featuredSession->session_type)<span><i class="bi bi-camera-video"></i>{{ $featuredSession->session_type }}</span>@endif
                </div>
            </div>
        </div>
    @else
        <div class="sw-empty sw-no-upcoming">
            <i class="bi bi-calendar2-check"></i>
            <div class="sw-empty-title">No upcoming session</div>
            <div>{{ $pastSessions->isNotEmpty() ? 'Previous sessions remain available below.' : 'No session has been scheduled for this training yet.' }}</div>
        </div>
    @endif

    @if ($scheduleUpcoming->isNotEmpty())
        <div class="sw-schedule-section">
            <h3>Upcoming</h3>
            <div class="sw-session-list">
                @foreach ($scheduleUpcoming as $session)
                    @php($place = $sessionPlace($session))
                    <div class="sw-session-row">
                        <div class="sw-session-row-date"><strong>{{ $session->date_label }}</strong><span>{{ $session->time_label }}</span></div>
                        <div class="sw-session-row-main"><strong>{{ $session->session_title ?: 'Training Session' }}</strong><span><i class="bi {{ $place['icon'] }}"></i>{{ $place['label'] }}</span></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($pastSessions->isNotEmpty())
        <div class="sw-past-sessions" data-past-sessions>
            <button type="button" class="sw-past-toggle" data-past-toggle aria-expanded="false" aria-controls="pastSessionList">
                <span>Past Sessions ({{ $pastSessions->count() }})</span><i class="bi bi-chevron-down"></i>
            </button>
            <div id="pastSessionList" class="sw-past-list" hidden>
                @foreach ($pastSessions as $index => $session)
                    @php($place = $sessionPlace($session))
                    <div class="sw-session-row sw-past-row" data-past-row @if ($index >= 5) hidden @endif>
                        <div class="sw-session-row-date"><strong>{{ $session->date_label }}</strong><span>{{ $session->time_label }}</span></div>
                        <div class="sw-session-row-main"><strong>{{ $session->session_title ?: 'Training Session' }}</strong><span><i class="bi {{ $place['icon'] }}"></i>{{ $place['label'] }}</span></div>
                    </div>
                @endforeach
                @if ($pastSessions->count() > 5)
                    <button type="button" class="sw-show-more-past" data-show-more-past data-batch-size="5">Show more past sessions</button>
                @endif
            </div>
        </div>
    @endif

    <div class="sw-session-footer">
        <span><i class="bi bi-people"></i>{{ $participantStats['total'] }} participant(s) expected</span>
        <a href="{{ $attendanceUrl }}">View attendance <i class="bi bi-arrow-right"></i></a>
    </div>
</section>
