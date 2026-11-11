<section class="lw-panel lw-detail" id="lmsSubmissionWorkspace" data-selected-assessment-id="{{ $selectedTask->assessment_id ?? '' }}">
    @if ($selectedTask)
        <div class="lw-detail-head">
            <div class="lw-detail-top">
                <div class="lw-detail-info">
                    <div class="lw-detail-badges">
                        <span class="lw-detail-type"><i class="bi bi-folder-symlink"></i> File Upload Task</span>
                        <span class="lw-task-state {{ $selectedTask->display_state_class }}">
                            <i class="bi {{ $selectedTask->display_state_icon }}" aria-hidden="true"></i>
                            {{ $selectedTask->display_state }}
                        </span>
                    </div>
                    <div class="lw-detail-loc">In: {{ $selectedTask->module_title ?? 'Unassigned Module' }}</div>
                    <h2 class="lw-detail-title">{{ $selectedTask->title }}</h2>
                    <p class="lw-detail-instr">{{ $selectedTask->instruction_text ?: 'No instructions provided.' }}</p>
                    <div class="lw-detail-meta">
                        <span class="lw-pill"><i class="bi bi-file-earmark-arrow-up"></i> {{ $selectedTask->accepted_format ?: 'Any format' }}</span>
                    </div>
                    <p class="lw-task-availability {{ $selectedTask->display_state_class }}">
                        <i class="bi {{ $selectedTask->display_state_icon }}" aria-hidden="true"></i>
                        {{ $selectedTask->display_state_message }}
                    </p>
                </div>
                <div class="lw-detail-download">
                    @if (($selectedTask->downloadable_file_count ?? 0) > 0)
                        <a href="{{ route('trainer.courses.submissions.zip', ['course_id' => $course->course_id, 'assessment_id' => $selectedTask->assessment_id]) }}" class="lw-btn lw-btn-primary">
                            <i class="bi bi-cloud-arrow-down-fill"></i> Download all (ZIP)
                        </a>
                    @else
                        <button type="button" class="lw-btn lw-btn-disabled" title="No submission files are currently available in storage." disabled>
                            <i class="bi bi-cloud-arrow-down"></i> Download all (ZIP)
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <form action="{{ route('trainer.courses.submissions', $course->course_id) }}" method="GET" class="lw-filter-bar" data-submission-report-form>
            <input type="hidden" name="assessment_id" value="{{ $selectedTask->assessment_id }}">
            <input type="hidden" name="status" value="{{ $submissionFilters['status'] }}" data-submission-status-input>
            <div class="lw-chips" aria-label="Submission status filter">
                <span class="lw-chips-label">Filter:</span>
                @foreach ([
                    'all' => ['All', 'all'],
                    'need_review' => ['Need Review', 'need_review'],
                    'reviewed' => ['Reviewed', 'reviewed'],
                    'awaiting' => ['Awaiting', 'awaiting'],
                    'late' => ['Late', 'late'],
                ] as $status => [$label, $countKey])
                    @php
                        $filterQuery = array_filter([
                            'assessment_id' => $selectedTask->assessment_id,
                            'status' => $status,
                            'search' => $submissionFilters['search'],
                            'rows' => $submissionFilters['rows'],
                        ], fn ($value) => $value !== '' && $value !== 'all' && $value !== 10);
                    @endphp
                    <a href="{{ route('trainer.courses.submissions', $course->course_id).'?'.http_build_query($filterQuery) }}"
                       data-submission-status="{{ $status }}"
                       @class(['lw-chip', 'lw-chip-rose' => $status === 'late', 'is-active' => $submissionFilters['status'] === $status])>
                        {{ $label }} <span class="lw-chip-count" data-submission-count="{{ $countKey }}">{{ $taskCounts[$countKey] }}</span>
                    </a>
                @endforeach
            </div>
            <div class="lw-search lw-roster-search" data-submission-search-control>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search"
                       name="search"
                       value="{{ $submissionFilters['search'] }}"
                       data-submission-search
                       placeholder="Search participant..."
                       autocomplete="off"
                       aria-label="Search participants by name or email">
                <button type="button" class="lw-search-clear" data-submission-search-clear aria-label="Clear participant search" @if ($submissionFilters['search'] === '') hidden @endif>
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </form>

        @include('trainer.training.submission.partials.roster-results')
    @else
        <div class="lw-detail-empty">
            <div class="lw-detail-empty-inner">
                <div class="lw-detail-empty-icon"><i class="bi bi-inbox"></i></div>
                <h2>No submission task selected</h2>
                <p>{{ $submissionTasks->isEmpty() ? 'No submission tasks are available for this training.' : 'Select an authorized task from the task navigator.' }}</p>
            </div>
        </div>
    @endif
</section>
