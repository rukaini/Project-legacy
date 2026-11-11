@include('trainer.training.partials.workspace-header', ['activeLmsTab' => 'participants'])

<main class="lw-page app-workspace-shell">
    @include('trainer.training.partials.workspace-page-heading', [
        'title' => 'Participants',
        'description' => 'View enrolled participants and their access information for this training.',
    ])

    <div class="lw-summary" data-lms-participant-metrics>
        <div class="lw-sum-card"><div class="lw-sum-icon blue"><i class="bi bi-people-fill"></i></div><div><p class="lw-sum-label">Total Participants</p><p class="lw-sum-value">{{ $participantStats['total'] }}</p><p class="lw-sum-sub">Enrolled in this course</p></div></div>
        <div class="lw-sum-card"><div class="lw-sum-icon green"><i class="bi bi-check2-circle"></i></div><div><p class="lw-sum-label">Accessed</p><p class="lw-sum-value">{{ $participantStats['accessed'] }}</p><p class="lw-sum-sub">{{ $participantStats['total'] > 0 ? round(($participantStats['accessed'] / $participantStats['total']) * 100) : 0 }}% of participants</p></div></div>
        <div class="lw-sum-card"><div class="lw-sum-icon amber"><i class="bi bi-hourglass-split"></i></div><div><p class="lw-sum-label">Never Accessed</p><p class="lw-sum-value">{{ $participantStats['never_accessed'] }}</p><p class="lw-sum-sub">Yet to engage with the course</p></div></div>
        <div class="lw-sum-card"><div class="lw-sum-icon violet"><i class="bi bi-gender-ambiguous"></i></div><div><p class="lw-sum-label">Gender Split</p><p class="lw-sum-value lw-gender-value"><span>{{ $participantStats['male'] }}M</span> / <span>{{ $participantStats['female'] }}F</span></p><p class="lw-sum-sub">{{ $participantStats['total'] - $participantStats['male'] - $participantStats['female'] }} unspecified</p></div></div>
    </div>

    <div class="lw-panel">
        <div class="lw-panel-head">
            <div class="lw-panel-head-row">
                <div><h2 class="lw-panel-title">Participant List</h2><p class="lw-panel-sub">Important participant information for this training course.</p></div>
                <form class="lw-controls" action="{{ route('trainer.courses.participants', $course->course_id) }}" method="get" data-lms-participant-form>
                    <div class="lw-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $participantFilters['search'] }}" placeholder="Search name or email..." autocomplete="off" data-lms-search><button type="button" class="lw-search-clear" data-lms-search-clear aria-label="Clear search" @if ($participantFilters['search'] === '') hidden @endif><i class="bi bi-x"></i></button></div>
                    <select name="gender" class="lw-select" aria-label="Gender"><option value="">All Gender</option><option value="Male" @selected($participantFilters['gender'] === 'Male')>Male</option><option value="Female" @selected($participantFilters['gender'] === 'Female')>Female</option></select>
                    <select name="access" class="lw-select" aria-label="Access"><option value="">All Access</option><option value="accessed" @selected($participantFilters['access'] === 'accessed')>Accessed</option><option value="never" @selected($participantFilters['access'] === 'never')>Never Accessed</option></select>
                    <input type="hidden" name="letter" value="{{ $participantFilters['letter'] }}" data-lms-letter-input>
                    <input type="hidden" name="rows" value="{{ $participantFilters['rows'] }}">
                </form>
            </div>
        </div>
        <div class="lw-alpha"><span class="lw-alpha-label">Filter A–Z</span><button type="button" data-lms-letter="" @class(['lw-alpha-btn', 'is-active' => $participantFilters['letter'] === ''])>All</button>@foreach (range('A', 'Z') as $letter)<button type="button" data-lms-letter="{{ $letter }}" @class(['lw-alpha-btn', 'is-active' => $participantFilters['letter'] === $letter, 'is-empty' => ! $participantLetters->contains($letter)]) @disabled(! $participantLetters->contains($letter))>{{ $letter }}</button>@endforeach</div>
        @include('trainer.training.participants.partials.results')
    </div>
</main>
