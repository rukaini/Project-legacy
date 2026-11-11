<div id="lmsSubmissionRosterResults" class="lw-roster-results" aria-live="polite">
    <div class="lw-report-error" data-submission-results-error hidden>
        <i class="bi bi-exclamation-circle"></i>
        <span>Submissions could not be refreshed. Please try again.</span>
    </div>

    @if ($submissionPaginator->total() > 0)
        <div class="lw-table-wrap">
            <table class="lw-table">
                <thead>
                    <tr>
                        <th>Participant</th>
                        <th>Submitted</th>
                        <th>File</th>
                        <th class="t-center">Status</th>
                        <th class="t-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($submissionPaginator as $participant)
                        @php
                            $statusClass = match ($participant->submission_status) {
                                'Need Review' => 's-need-review',
                                'Reviewed' => 's-reviewed',
                                default => 's-awaiting',
                            };
                            $rowClass = match ($participant->submission_status) {
                                'Need Review' => 'is-need-review',
                                'Awaiting' => 'is-awaiting',
                                default => '',
                            };
                        @endphp
                        <tr data-submission-row data-status="{{ $participant->submission_status }}" class="{{ $rowClass }}">
                            <td>
                                <div class="lw-participant">
                                    <div class="lw-avatar">{{ strtoupper(mb_substr($participant->participant_name ?? 'U', 0, 1)) }}</div>
                                    <div>
                                        <div class="lw-pname {{ $participant->is_late ? 'is-late' : '' }}">{{ $participant->participant_name }}</div>
                                        <div class="lw-pmeta">{{ $participant->participant_email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="lw-submitted-cell {{ $participant->submission_status === 'Awaiting' ? 'is-awaiting' : '' }} {{ $participant->is_late ? 'is-late' : '' }}">
                                    {{ $participant->submitted_label }}
                                </span>
                            </td>
                            <td>
                                @if ($participant->file_available)
                                    <button type="button"
                                            data-file-preview
                                            data-preview-url="{{ $participant->preview_url }}"
                                            data-download-url="{{ $participant->download_url }}"
                                            data-file-name="{{ $participant->file_name }}"
                                            class="lw-file-btn"
                                            title="Preview {{ $participant->file_name }}">
                                        <i class="bi bi-file-earmark-text"></i>
                                        <span class="name">{{ $participant->file_name }}</span>
                                    </button>
                                @elseif ($participant->submitted_at && $participant->submitted_file_path)
                                    <span class="lw-file-unavailable" title="This submission file could not be found in storage.">
                                        <i class="bi bi-file-earmark-x"></i> File unavailable
                                    </span>
                                @else
                                    <span class="lw-no-file">&mdash;</span>
                                @endif
                            </td>
                            <td class="t-center">
                                <span class="lw-status submission-status-badge {{ $statusClass }}">{{ $participant->submission_status }}</span>
                                @if ($participant->is_late)
                                    <span class="lw-late-note">Late</span>
                                @endif
                            </td>
                            <td class="t-right">
                                @if ($participant->submission_id && $participant->submission_status === 'Need Review')
                                    <form method="POST" class="review-form" action="{{ route('trainer.courses.submissions.review', ['course_id' => $course->course_id, 'submission_id' => $participant->submission_id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="review_status" value="Reviewed">
                                        <button type="submit" class="lw-btn lw-btn-primary lw-btn-sm lw-action"><i class="bi bi-check2-circle"></i> Mark Reviewed</button>
                                    </form>
                                @elseif ($participant->submission_id && $participant->submission_status === 'Reviewed')
                                    <form method="POST" class="review-form" action="{{ route('trainer.courses.submissions.review', ['course_id' => $course->course_id, 'submission_id' => $participant->submission_id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="review_status" value="Submitted">
                                        <button type="submit" class="lw-btn lw-btn-ghost lw-btn-sm lw-action"><i class="bi bi-arrow-counterclockwise"></i> Undo Review</button>
                                    </form>
                                @else
                                    <button type="button" class="lw-btn lw-btn-disabled lw-btn-sm lw-action" disabled><i class="bi bi-hourglass"></i> No File Yet</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('trainer.training.partials.report-paginator', [
            'paginator' => $submissionPaginator,
            'state' => $submissionFilters,
            'noun' => 'participants',
            'reportPath' => route('trainer.courses.submissions', $course->course_id),
        ])
    @else
        <div class="lw-table-empty">
            <i class="bi {{ $taskCounts['all'] === 0 ? 'bi-people' : 'bi-funnel' }}"></i>
            <h4>{{ $taskCounts['all'] === 0 ? 'No participants are available for this task.' : 'No participants match these filters.' }}</h4>
            @if ($taskCounts['all'] > 0)
                <p>Try changing the selected status or participant search.</p>
            @endif
        </div>
    @endif
</div>
