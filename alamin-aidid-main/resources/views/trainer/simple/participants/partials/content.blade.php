<div class="sw-shell">
    @include('trainer.simple.partials.header')
    <main class="sw-page app-workspace-shell">
        <section class="sw-summary-cards" aria-label="Participant summary">
            <article class="sw-summary-card"><span class="sw-summary-icon blue"><i class="bi bi-people"></i></span><div class="sw-summary-copy"><p class="sw-summary-label">Total Participants</p><p class="sw-summary-value">{{ $participantStats['total'] }}</p></div></article>
            <article class="sw-summary-card"><span class="sw-summary-icon violet"><i class="bi bi-gender-ambiguous"></i></span><div class="sw-summary-copy"><p class="sw-summary-label">Gender Split</p><p class="sw-summary-value compact">{{ $participantStats['male'] }}M / {{ $participantStats['female'] }}F</p>@if ($participantStats['unspecified'] > 0)<p class="sw-summary-note">{{ $participantStats['unspecified'] }} unspecified</p>@endif</div></article>
        </section>

        <section class="sw-panel" aria-labelledby="participant-list-title">
            <div class="sw-panel-header"><div><h2 class="sw-panel-title" id="participant-list-title">Participant List</h2><p class="sw-panel-sub">Participant information for this training.</p></div><span class="sw-panel-count">{{ $participantStats['total'] }} enrolled</span></div>
            <form action="{{ route('trainer.simple.participants', $course->course_id) }}" method="GET" class="sw-participant-filters" data-simple-report-form data-results-id="simpleParticipantResults">
                <div class="sw-report-toolbar">
                    <label class="sw-filter-field sw-search-field"><span class="sw-filter-label">Search</span><span class="sw-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $participantFilters['search'] }}" placeholder="Search name, email or phone..." autocomplete="off" data-simple-search><button type="button" class="sw-search-clear" data-simple-search-clear aria-label="Clear participant search" @if ($participantFilters['search'] === '') hidden @endif>&times;</button></span></label>
                    <label class="sw-filter-field"><span class="sw-filter-label">Gender</span><select name="gender" class="sw-select"><option value="">All Gender</option><option value="Male" @selected($participantFilters['gender'] === 'Male')>Male</option><option value="Female" @selected($participantFilters['gender'] === 'Female')>Female</option>@if ($participantStats['unspecified'] > 0)<option value="Unspecified" @selected($participantFilters['gender'] === 'Unspecified')>Unspecified</option>@endif</select></label>
                </div>
                <input type="hidden" name="letter" value="{{ $participantFilters['letter'] }}">
                <div class="sw-az-filter"><span>Filter A–Z</span><div class="sw-az-buttons"><button type="button" data-simple-letter="" class="{{ $participantFilters['letter'] === '' ? 'active' : '' }}">All</button>@foreach (range('A', 'Z') as $letter)<button type="button" data-simple-letter="{{ $letter }}" class="{{ $participantFilters['letter'] === $letter ? 'active' : '' }}" @disabled(! $participantLetters->contains($letter))>{{ $letter }}</button>@endforeach</div></div>
            </form>
            @include('trainer.simple.participants.partials.results')
        </section>
    </main>
</div>
