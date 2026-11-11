<main class="adm-wrap adm-wrap--wide app-page-shell" data-admin-page-shell data-admin-width="wide">
    <div class="adm-head">
        <div class="adm-head-row">
            <div class="adm-head-icon"><i class="bi bi-activity" aria-hidden="true"></i></div>
            <div>
                <h1 class="adm-title">Trainer Activity Log</h1>
                <p class="adm-subtitle">Review recorded trainer actions across the system, with focused controls for date, trainer, action, and order.</p>
            </div>
        </div>
    </div>

    @php
        $actionMap = collect(\App\Support\TrainerActivityLogger::meaningfulActionCodes())
            ->merge($activities->pluck('action'))
            ->unique()
            ->mapWithKeys(fn ($action) => [$action => \App\Support\TrainerActivityLogger::actionMeta($action)])
            ->all();
        $topActionLabel = ! empty($stats['topActionKey'])
            ? ($actionMap[$stats['topActionKey']][0] ?? ucfirst(str_replace(['.', '_'], ' ', $stats['topActionKey'])))
            : null;
        $rangeOptions = [
            'today' => ['Today', $activityPeriod['todayLabel']],
            '7' => ['Last 7 Days', $activityPeriod['weekLabel']],
            '30' => ['Last 30 Days', $activityPeriod['monthLabel']],
            'all' => ['All Time', 'All recorded dates'],
            'custom' => ['Custom Date', $activityPeriod['key'] === 'custom' ? $activityPeriod['label'] : 'Choose a date range'],
        ];
        $activeRange = $activityPeriod['key'] === 'custom' || ($errors->any() && old('period') === 'custom')
            ? 'custom'
            : 'all';
    @endphp

    @if ($errors->any())
        <div class="adm-alert error" role="alert">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    @if ($activities->isEmpty())
        <section class="act-panel" aria-labelledby="activity-feed-title">
            <div class="act-panel-head">
                <span class="act-panel-mark" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                <div>
                    <h2 id="activity-feed-title">Activity Feed</h2>
                    <p>Recorded trainer actions appear here in chronological order.</p>
                </div>
            </div>
            <div class="adm-empty">
                <i class="bi bi-activity" aria-hidden="true"></i>
                <div class="t">No activity recorded yet</div>
                <div class="s">As trainers use the system, their recorded actions will appear here.</div>
            </div>
        </section>
    @else
        <section class="act-stats" aria-label="Activity summary">
            <article class="adm-stat" data-activity-metric="today">
                <div class="adm-stat-icon adm-ic-blue" aria-hidden="true"><i class="bi bi-calendar-day"></i></div>
                <div class="adm-stat-label">Today</div>
                <div class="adm-stat-value">{{ $stats['today'] }}</div>
                <div class="adm-stat-note"><strong>Recorded actions</strong><span>{{ $activityPeriod['todayLabel'] }}</span></div>
            </article>
            <article class="adm-stat" data-activity-metric="week">
                <div class="adm-stat-icon adm-ic-indigo" aria-hidden="true"><i class="bi bi-calendar-week"></i></div>
                <div class="adm-stat-label">Last 7 Days</div>
                <div class="adm-stat-value">{{ $stats['week'] }}</div>
                <div class="adm-stat-note"><strong>Recorded actions</strong><span>{{ $activityPeriod['weekLabel'] }}</span></div>
            </article>
            <article class="adm-stat" data-activity-metric="trainer">
                <div class="adm-stat-icon adm-ic-green" aria-hidden="true"><i class="bi bi-person-check"></i></div>
                <div class="adm-stat-label">Most Active Trainer</div>
                <div class="adm-stat-value adm-stat-value--text">{{ $stats['topTrainerName'] ?? 'Not available' }}</div>
                <div class="adm-stat-note"><strong>{{ $stats['topTrainerName'] ? $stats['topTrainerN'].' recorded actions' : 'No recorded activity' }}</strong><span>{{ $activityPeriod['label'] }}</span></div>
            </article>
            <article class="adm-stat" data-activity-metric="action">
                <div class="adm-stat-icon adm-ic-amber" aria-hidden="true"><i class="bi bi-bar-chart"></i></div>
                <div class="adm-stat-label">Most Frequent Action</div>
                <div class="adm-stat-value adm-stat-value--text">{{ $topActionLabel ?? 'Not available' }}</div>
                <div class="adm-stat-note"><strong>{{ $topActionLabel ? $stats['topActionN'].' occurrences' : 'No recorded activity' }}</strong><span>{{ $activityPeriod['label'] }}</span></div>
            </article>
        </section>

        <section class="act-panel" aria-labelledby="activity-feed-title">
            <div class="act-panel-head">
                <span class="act-panel-mark" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                <div>
                    <h2 id="activity-feed-title">Activity Feed</h2>
                    <p>Browse the latest recorded trainer actions and their related context.</p>
                </div>
            </div>

            <div class="act-controls">
                <nav class="act-range-tabs" id="admRange" aria-label="Activity period">
                    @foreach ($rangeOptions as $rangeValue => [$rangeLabel, $rangeContext])
                        <button type="button"
                                class="act-range-btn {{ $rangeValue === $activeRange ? 'active' : '' }}"
                                data-range="{{ $rangeValue }}"
                                aria-pressed="{{ $rangeValue === $activeRange ? 'true' : 'false' }}">
                            <span>{{ $rangeLabel }}</span>
                            <small>{{ $rangeContext }}</small>
                        </button>
                    @endforeach
                </nav>

                <form method="GET"
                      action="{{ route('admin.activity') }}"
                      class="act-custom-period"
                      id="activityCustomPeriod"
                      data-activity-custom-period
                      @if ($activeRange !== 'custom' && ! $errors->any()) hidden @endif>
                    <input type="hidden" name="period" value="custom">
                    <label class="act-filter-field">
                        <span>From Date</span>
                        <input type="date" name="from" value="{{ old('from', $activityPeriod['from']) }}" required>
                    </label>
                    <label class="act-filter-field">
                        <span>To Date</span>
                        <input type="date" name="to" value="{{ old('to', $activityPeriod['to']) }}" required>
                    </label>
                    <button type="submit" class="act-apply-period"><i class="bi bi-funnel" aria-hidden="true"></i>Apply Dates</button>
                    @if ($activityPeriod['key'] === 'custom')
                        <a href="{{ route('admin.activity', array_filter([
                            'search' => $activityControls['search'],
                            'trainer' => $activityControls['trainer'] !== 'all' ? $activityControls['trainer'] : null,
                            'action' => $activityControls['action'] !== 'all' ? $activityControls['action'] : null,
                            'sort' => $activityControls['sort'] !== 'desc' ? $activityControls['sort'] : null,
                            'rows' => $activityControls['rows'] !== 10 ? $activityControls['rows'] : null,
                        ])) }}" class="act-clear-period">Clear custom dates</a>
                    @endif
                </form>

                <div class="act-period-context" id="activityPeriodContext" role="status">
                    <i class="bi bi-calendar-range" aria-hidden="true"></i>
                    <span>Showing period: <strong>{{ $activityPeriod['label'] }}</strong></span>
                </div>

                <div class="act-filter-toolbar" role="group" aria-label="Activity list controls">
                    <div class="act-filter-field act-search-field">
                        <label for="admSearch">Search Activity</label>
                        <div class="act-search-control">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search"
                                   id="admSearch"
                                   value="{{ $activityControls['search'] }}"
                                   placeholder="Search trainer, action, or detail..."
                                   autocomplete="off">
                            <button type="button"
                                    class="act-search-clear"
                                    data-admin-activity-search-clear
                                    aria-label="Clear activity search"
                                    hidden>
                                <i class="bi bi-x" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="act-filter-field">
                        <label for="admTrainerFilter">Trainer</label>
                        <select id="admTrainerFilter">
                            <option value="all">All trainers</option>
                            @foreach ($trainers as $trainer)
                                <option value="{{ $trainer->trainer_id }}" @selected((string) $activityControls['trainer'] === (string) $trainer->trainer_id)>{{ $trainer->trainer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="act-filter-field">
                        <label for="admActionFilter">Action</label>
                        <select id="admActionFilter">
                            <option value="all">All actions</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}" @selected($activityControls['action'] === $action)>{{ $actionMap[$action][0] ?? ucfirst(str_replace(['.', '_'], ' ', (string) $action)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="act-filter-field act-sort-field">
                        <label for="admSort">Sort By</label>
                        <select id="admSort">
                            <option value="desc" @selected($activityControls['sort'] === 'desc')>Newest first</option>
                            <option value="asc" @selected($activityControls['sort'] === 'asc')>Oldest first</option>
                        </select>
                    </div>

                    <button type="button" class="act-reset-btn" id="admResetBtn" disabled>
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        Reset
                    </button>
                </div>
            </div>

            <div class="adm-list-anchor"></div>
            <div id="admTimeline"
                 class="act-timeline"
                 aria-live="polite"
                 data-initial-period="{{ $activeRange }}"
                 data-custom-from="{{ $activityPeriod['from'] }}"
                 data-custom-to="{{ $activityPeriod['to'] }}"
                 data-app-today="{{ $activityPeriod['todayValue'] }}"
                 data-app-week-from="{{ $activityPeriod['weekFrom'] }}"
                 data-app-month-from="{{ $activityPeriod['monthFrom'] }}">
                @foreach ($activities as $activity)
                    @php
                        $meta = $actionMap[$activity->action] ?? [ucfirst(str_replace(['.', '_'], ' ', (string) $activity->action)), 'bi-dot', 'slate'];
                        $actionLabel = $meta[0];
                        $actionIcon = $meta[1];
                        $actionTone = $meta[2];
                        $timestamp = $activity->created_at ? \Carbon\Carbon::parse($activity->created_at) : null;
                    @endphp
                    <article class="act-row"
                             data-trainer-id="{{ $activity->trainer_id }}"
                             data-action="{{ $activity->action }}"
                             data-action-label="{{ $actionLabel }}"
                             data-trainer-name="{{ $activity->trainer_name }}"
                             data-ts="{{ $timestamp ? $timestamp->timestamp : 0 }}"
                             data-day="{{ $timestamp ? $timestamp->format('Y-m-d') : '' }}"
                             data-search="{{ $activity->trainer_name }} {{ $activity->detail }} {{ $actionLabel }} {{ $activity->course_name ?? '' }}">
                        <div class="act-row-icon adm-ic-{{ $actionTone }}" aria-hidden="true">
                            <i class="bi {{ $actionIcon }}"></i>
                        </div>

                        <div class="act-row-main">
                            <div class="act-row-title">
                                @if (! empty($activity->trainer_name))
                                    <a href="{{ route('admin.directory') }}?tab=trainers&amp;trainerName={{ urlencode($activity->trainer_name) }}" class="act-trainer-link">
                                        {{ $activity->trainer_name }}
                                    </a>
                                @else
                                    <strong>A trainer</strong>
                                @endif
                                <span class="act-type-badge {{ $actionTone }}">
                                    <i class="bi {{ $actionIcon }}" aria-hidden="true"></i>{{ $actionLabel }}
                                </span>
                            </div>

                            @if (! empty($activity->detail))
                                <p class="act-row-detail">{{ $activity->detail }}</p>
                            @endif

                            @if (! empty($activity->course_name) && ! empty($activity->course_id))
                                <a href="{{ route('admin.courses', ['course' => $activity->course_id]) }}" class="act-course-link">
                                    <i class="bi bi-mortarboard" aria-hidden="true"></i>{{ $activity->course_name }}
                                </a>
                            @endif
                        </div>

                        <time class="act-row-time" datetime="{{ $timestamp ? $timestamp->toIso8601String() : '' }}">
                            <span class="rel">{{ $timestamp ? $timestamp->diffForHumans() : 'Time unavailable' }}</span>
                            <span class="full">{{ $timestamp ? $timestamp->format('d M Y, g:i A') : '' }}</span>
                        </time>
                    </article>
                @endforeach
            </div>

            <div id="admEmpty" class="adm-empty act-filter-empty" role="status" hidden>
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <div class="t">No activity matches these controls</div>
                <div class="s">Try another period, trainer, action, sort order, or search term.</div>
            </div>

            <div id="admPager" class="act-pager" data-admin-activity-paginator>
                <label class="act-rows-control" for="admRows">
                    <span>Rows</span>
                    <select id="admRows" data-admin-activity-rows>
                        @foreach ([10, 25, 50] as $rowOption)
                        <option value="{{ $rowOption }}" @selected($activityControls['rows'] === $rowOption)>{{ $rowOption }}</option>
                        @endforeach
                    </select>
                </label>
                <span class="act-page-info" id="admCount" aria-live="polite"></span>
                <div class="act-pager-controls" id="admPagerControls"></div>
            </div>
        </section>
    @endif
</main>
