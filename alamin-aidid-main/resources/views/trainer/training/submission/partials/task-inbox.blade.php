<aside class="lw-inbox" id="lwInbox" data-task-sidebar>
    <div class="lw-panel lw-inbox-expanded">
        <div class="lw-inbox-head">
            <div class="lw-inbox-head-row">
                <div>
                    <h2 class="lw-inbox-title">Submission Tasks</h2>
                    <p class="lw-inbox-subtitle">Select a task to review participant files.</p>
                </div>
                <button type="button"
                        class="lw-collapse-btn"
                        data-task-sidebar-toggle
                        aria-label="Hide submission tasks"
                        title="Hide submission tasks"
                        aria-expanded="true">
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>
            </div>
            <div class="lw-search lw-task-search" data-task-search-control>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search"
                       id="assignmentSearch"
                       data-task-search
                       placeholder="Search tasks..."
                       autocomplete="off"
                       aria-label="Search submission tasks">
                <button type="button" class="lw-search-clear" data-task-search-clear aria-label="Clear task search" hidden>
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <div class="lw-inbox-list" id="assignmentList" data-task-list>
            @forelse ($submissionTasks as $task)
                @php
                    $isActive = $selectedTask && (int) $selectedTask->assessment_id === (int) $task->assessment_id;
                    $taskQuery = array_filter([
                        'assessment_id' => $task->assessment_id,
                        'status' => $submissionFilters['status'],
                        'search' => $submissionFilters['search'],
                        'rows' => $submissionFilters['rows'],
                    ], fn ($value) => $value !== '' && $value !== 'all' && $value !== 10);
                @endphp
                <a href="{{ route('trainer.courses.submissions', ['course_id' => $course->course_id]).'?'.http_build_query($taskQuery) }}"
                   data-task-link
                   data-assessment-id="{{ $task->assessment_id }}"
                   data-task-search-text="{{ strtolower($task->title.' '.($task->module_title ?? '').' '.$task->display_state) }}"
                   @class(['lw-inbox-card', 'is-active' => $isActive])
                   @if ($isActive) aria-current="true" @endif>
                    <div class="lw-inbox-type-row">
                        <span class="lw-inbox-type"><i class="bi bi-folder-symlink"></i> File Upload Task</span>
                        <span class="lw-task-state {{ $task->display_state_class }}">
                            <i class="bi {{ $task->display_state_icon }}" aria-hidden="true"></i>
                            {{ $task->display_state }}
                        </span>
                    </div>
                    <h3 class="lw-inbox-card-title">{{ $task->title }}</h3>
                    <p class="lw-inbox-card-sub">{{ $task->module_title ?? 'No module assigned' }}</p>
                    <div class="lw-inbox-card-foot">
                        <span class="lw-submitted-text">
                            <span class="sidebar-submitted-count">{{ $task->submitted_count }}</span>/<span class="sidebar-total-count">{{ $totalParticipants }}</span> submitted
                        </span>
                        <span class="sidebar-review-badge">
                            @if ($task->need_review_count > 0)
                                <span class="lw-mini-pill rose"><span class="sidebar-need-review-count">{{ $task->need_review_count }}</span> Need Review</span>
                            @elseif ($task->submitted_count > 0)
                                <span class="lw-mini-pill success">All Reviewed</span>
                            @else
                                <span class="lw-mini-pill muted">No file yet</span>
                            @endif
                        </span>
                    </div>
                </a>
            @empty
                <div class="lw-inbox-empty" data-no-tasks>
                    <i class="bi bi-folder-x"></i>
                    <h4>No submission tasks are available for this training.</h4>
                    <p>Submission tasks created for this LMS course will appear here.</p>
                </div>
            @endforelse

            @if ($submissionTasks->isNotEmpty())
                <div class="lw-inbox-empty lw-hidden" data-no-task-matches>
                    <i class="bi bi-search"></i>
                    <h4>No submission tasks match your search.</h4>
                    <p>Try a different task title or module.</p>
                </div>
            @endif
        </div>
    </div>

    <button type="button"
            class="lw-show-tasks"
            data-task-sidebar-toggle
            aria-label="Show submission tasks"
            title="Show submission tasks"
            aria-expanded="false">
        <i class="bi bi-list-task"></i>
        <span>Tasks</span>
    </button>
</aside>
