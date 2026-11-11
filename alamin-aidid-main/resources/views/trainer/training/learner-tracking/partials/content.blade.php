@php
    $trackingQuery = function (array $changes = []) use ($course): string {
        $query = array_filter(array_merge(request()->query(), $changes), static fn ($value) => $value !== null && $value !== '');
        unset($query['fragment']);

        return route('trainer.courses.learner-tracking', $course->course_id).($query ? '?'.http_build_query($query) : '');
    };
@endphp

<div id="learnerTrackingWorkspace" class="lt-workspace app-workspace-shell" data-tracking-workspace data-tab="{{ $tab }}">
    <section class="lt-page-head">
        @include('trainer.training.partials.workspace-page-heading', [
            'title' => 'Learner Tracking',
            'description' => 'Inspect recorded material access and Quiz activity for this training.',
            'embedded' => true,
        ])
        @if (! $tracking['available'])
            <div class="lt-source-note" role="status">
                <i class="bi bi-info-circle"></i>
                <span><strong>Material tracking unavailable.</strong> {{ $tracking['reason'] }}</span>
            </div>
        @endif
    </section>

    <nav class="lt-tabs" aria-label="Learner Tracking views" data-tracking-region="tabs">
        <a class="lt-tab {{ $tab === 'overview' ? 'is-active' : '' }}" href="{{ $trackingQuery(['tab' => 'overview', 'search' => $tab === 'quiz' ? null : $search, 'participant_search' => null, 'attempt' => null]) }}" data-tracking-link data-tracking-action="tab" data-tracking-regions="tabs navigator main" data-tracking-active-group="tabs">Overview</a>
        <a class="lt-tab {{ $tab === 'engagement' ? 'is-active' : '' }}" href="{{ $trackingQuery(['tab' => 'engagement', 'search' => $tab === 'quiz' ? null : $search, 'participant_search' => null, 'attempt' => null]) }}" data-tracking-link data-tracking-action="tab" data-tracking-regions="tabs navigator main" data-tracking-active-group="tabs">Engagement Logs</a>
        <a class="lt-tab {{ $tab === 'quiz' ? 'is-active' : '' }}" href="{{ $trackingQuery(['tab' => 'quiz', 'search' => $tab === 'quiz' ? $search : null, 'participant_search' => null, 'attempt' => null]) }}" data-tracking-link data-tracking-action="tab" data-tracking-regions="tabs navigator main" data-tracking-active-group="tabs">Quiz Tracking</a>
    </nav>

    <form class="lt-period-bar" data-tracking-period-form data-tracking-region="period">
        <label>Activity period
            <select data-tracking-period data-tracking-action="period" data-tracking-regions="main">
                <option value="all" {{ $period['key'] === 'all' ? 'selected' : '' }}>All time</option>
                <option value="7" {{ $period['key'] === '7' ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ $period['key'] === '30' ? 'selected' : '' }}>Last 30 days</option>
                <option value="custom" {{ $period['key'] === 'custom' ? 'selected' : '' }}>Custom</option>
            </select>
        </label>
        <div class="lt-custom-period {{ $period['key'] === 'custom' ? '' : 'is-hidden' }}" data-custom-period>
            <label>From <input type="date" name="from" value="{{ request('from') }}" required></label>
            <label>To <input type="date" name="until" value="{{ request('until') }}" required></label>
            <button type="submit">Apply</button>
            @if ($period['key'] === 'custom' && ! $period['valid'])<span>Choose a valid From and To date.</span>@endif
        </div>
    </form>

    <div class="lt-layout">
        @include('trainer.training.learner-tracking.partials.sidebar')

        <main class="lt-main" aria-live="polite" data-tracking-region="main">
            @if ($tab === 'overview')
                @include('trainer.training.learner-tracking.partials.overview-panel')
            @elseif ($tab === 'engagement')
                @include('trainer.training.learner-tracking.partials.engagement-panel')
            @else
                @include('trainer.training.learner-tracking.partials.assessment-panel')
            @endif
        </main>
    </div>
</div>
