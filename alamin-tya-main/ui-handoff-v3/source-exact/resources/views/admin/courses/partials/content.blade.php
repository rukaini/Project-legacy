<main class="adm-wrap adm-wrap--wide app-page-shell" data-admin-page-shell data-admin-width="wide">
    <div class="adm-head">
        <div class="adm-head-row">
            <div class="adm-head-icon"><i class="bi bi-mortarboard" aria-hidden="true"></i></div>
            <div>
                <h1 class="adm-title">Training Monitoring</h1>
                <p class="adm-subtitle">Monitor every training, its assigned trainers, schedule, participants, and recorded attendance from one workspace.</p>
            </div>
        </div>
    </div>

    @if ($errors->has('allow_material_after_end'))
        <div class="tm-feedback error" role="alert">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <span>{{ $errors->first('allow_material_after_end') }}</span>
        </div>
    @endif

    @if ($errors->has('comment'))
        <div class="tm-feedback error" role="alert">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <span>{{ $errors->first('comment') }}</span>
        </div>
    @endif

    @if ($trainings->isEmpty())
        <section class="tm-empty-panel" aria-labelledby="training-monitoring-title">
            <div class="tm-panel-head">
                <span class="tm-panel-mark" aria-hidden="true"><i class="bi bi-collection"></i></span>
                <div>
                    <h2 id="training-monitoring-title">All Trainings</h2>
                    <p>Training records available to administrators will appear here.</p>
                </div>
            </div>
            <div class="adm-empty">
                <i class="bi bi-mortarboard" aria-hidden="true"></i>
                <div class="t">No trainings are available</div>
                <div class="s">No persisted training records are currently available for monitoring.</div>
            </div>
        </section>
    @else
        <div class="tm-workspace" id="trainingMonitoringWorkspace">
            <aside class="tm-master" id="trainingMaster" data-training-master aria-labelledby="training-list-title">
                <div class="tm-master-expanded" data-training-master-expanded>
                <div class="tm-panel-head">
                    <div>
                        <h2 id="training-list-title">All Trainings</h2>
                        <p>{{ $trainings->count() }} training{{ $trainings->count() === 1 ? '' : 's' }} available</p>
                    </div>
                    <button type="button"
                            class="tm-master-toggle tm-master-collapse"
                            data-training-master-toggle
                            aria-controls="trainingMaster"
                            aria-expanded="true"
                            aria-label="Collapse training list"
                            title="Collapse training list">
                        <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="tm-master-controls" role="group" aria-label="Training list controls">
                    <div class="tm-filter-field tm-search-field">
                        <label for="trainingSearch">Search Trainings</label>
                        <div class="tm-search-control">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search"
                                   id="trainingSearch"
                                   data-training-search
                                   placeholder="Search title, category, or trainer..."
                                   autocomplete="off">
                            <button type="button"
                                    class="tm-search-clear"
                                    data-training-search-clear
                                    aria-label="Clear training search"
                                    hidden>
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="button"
                            class="tm-filter-toggle"
                            data-training-filter-toggle
                            aria-controls="trainingFilterPanel"
                            aria-expanded="false">
                        <span><i class="bi bi-sliders" aria-hidden="true"></i>Filters</span>
                        <span class="tm-filter-toggle-meta">
                            <span class="tm-filter-count" data-training-filter-count hidden></span>
                            <i class="bi bi-chevron-down" data-training-filter-chevron aria-hidden="true"></i>
                        </span>
                    </button>

                    <div class="tm-master-filter-panel" id="trainingFilterPanel" data-training-filter-panel aria-hidden="true" inert>
                        <div class="tm-master-filter-panel-inner">
                            <div class="tm-master-filter-grid">
                                <div class="tm-filter-field">
                                    <label for="trainingScheduleFilter">Schedule</label>
                                    <select id="trainingScheduleFilter" data-training-schedule-filter data-training-filter="schedule">
                                        <option value="all">All periods</option>
                                        <option value="current">Ongoing</option>
                                        <option value="upcoming">Upcoming</option>
                                        <option value="past">Past</option>
                                        <option value="unscheduled">Unavailable</option>
                                    </select>
                                </div>

                                <div class="tm-filter-field">
                                    <label for="trainingModeFilter">Mode</label>
                                    <select id="trainingModeFilter" data-training-mode-filter data-training-filter="mode">
                                        <option value="all">All modes</option>
                                        @foreach ($trainingFilterOptions['modes'] as $option)
                                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="tm-filter-field">
                                    <label for="trainingTrainerFilter">Trainer</label>
                                    <select id="trainingTrainerFilter" data-training-trainer-filter data-training-filter="trainer">
                                        <option value="all">Any trainer</option>
                                        @foreach ($trainingFilterOptions['trainers'] as $option)
                                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <button type="button" class="tm-reset-btn" id="trainingReset" disabled>
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tm-training-list" id="trainingList">
                    @foreach ($trainings as $training)
                        <button type="button"
                                class="tm-training-row"
                                data-training-id="{{ $training->course_id }}"
                                data-search="{{ $training->course_name }} {{ $training->course_category }} {{ implode(' ', $training->trainer_names) }} {{ $training->mode_label }} {{ $training->status_label }}"
                                data-schedule="{{ $training->schedule_key }}"
                                data-status="{{ $training->status_key }}"
                                data-mode="{{ $training->mode_filter_key }}"
                                data-trainers=",{{ implode(',', collect($trainingData[(string) $training->course_id]['trainers'])->pluck('id')->all()) }},"
                                data-sort-date="{{ $training->sort_date }}"
                                aria-selected="false">
                            <span class="tm-training-row-top">
                                <span class="tm-training-identity">
                                    <span class="tm-training-initial" aria-hidden="true">{{ strtoupper(mb_substr($training->course_name, 0, 1)) }}</span>
                                    <strong>{{ $training->course_name }}</strong>
                                </span>
                                <span class="tm-mode-badge {{ $training->mode_key }}">{{ $training->mode_label }}</span>
                            </span>
                            <span class="tm-training-meta">
                                <span><i class="bi bi-person-badge" aria-hidden="true"></i>{{ $training->trainer_names ? implode(', ', $training->trainer_names) : 'Trainer unassigned' }}</span>
                                <span><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $training->date_range_label }}</span>
                            </span>
                            <span class="tm-training-row-foot">
                                <span class="tm-training-row-summary">
                                    <span>{{ (int) $training->enrolled }} participant{{ (int) $training->enrolled === 1 ? '' : 's' }} &middot; {{ (int) $training->total_sessions }} session{{ (int) $training->total_sessions === 1 ? '' : 's' }}</span>
                                    @if ($training->teaching_plan_attention > 0)
                                        <span class="tm-training-plan-alert"
                                              aria-label="{{ $training->teaching_plan_attention }} {{ $training->teaching_plan_attention === 1 ? 'teaching plan needs' : 'teaching plans need' }} Admin review"
                                              title="This training has Teaching Plans requiring Admin review">
                                            <i aria-hidden="true"></i>
                                            {{ $training->teaching_plan_attention }} {{ $training->teaching_plan_attention === 1 ? 'plan needs' : 'plans need' }} Admin review
                                        </span>
                                    @endif
                                </span>
                                <span class="tm-schedule-badge {{ $training->schedule_key }}">{{ $training->schedule_label }}</span>
                            </span>
                        </button>
                    @endforeach
                    <div id="trainingListEmpty" class="adm-empty tm-list-empty" role="status" hidden>
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <div class="t">No trainings match these controls</div>
                        <div class="s">Try another search, schedule, mode, or trainer.</div>
                    </div>
                </div>

                </div>

                <div class="tm-master-collapsed" data-training-master-collapsed aria-hidden="true" inert>
                    <button type="button"
                            class="tm-master-toggle tm-master-expand"
                            data-training-master-toggle
                            aria-controls="trainingMaster"
                            aria-expanded="false"
                            aria-label="Expand training list"
                            title="Expand training list">
                        <i class="bi bi-list-ul" aria-hidden="true"></i>
                    </button>
                </div>
            </aside>

            <section class="tm-detail" id="trainingDetail"
                     data-training-detail
                     data-directory-url="{{ route('admin.directory') }}"
                     data-csrf-token="{{ csrf_token() }}"
                     aria-live="polite">
                <div class="adm-empty tm-detail-empty">
                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                    <div class="t">Select a training to monitor</div>
                    <div class="s">Choose a training from the list to review its schedule, participants, and attendance.</div>
                </div>
            </section>
        </div>

        <script type="application/json" id="trainingData">@json($trainingData)</script>
    @endif
</main>
