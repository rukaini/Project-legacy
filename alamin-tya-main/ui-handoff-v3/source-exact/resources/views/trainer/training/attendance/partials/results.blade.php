<div id="lmsAttendanceRosterResults" class="lw-results-region" aria-live="polite">
    <span class="lw-loading-label">Updating roster…</span><div class="lw-report-error" data-lms-report-error hidden>Attendance could not be refreshed. Please try again.</div>
    @if ($attendancePaginator->total() > 0)
        <div class="lw-table-wrap"><table class="lw-table"><thead><tr><th>Participant Profile</th><th>Email</th><th class="t-center">Attendance Status</th><th>Recorded Date</th><th>Recorded Time</th></tr></thead><tbody>
            @foreach ($attendancePaginator as $participant)
                @php($status = strtolower($participant->attendance_status))
                <tr><td><div class="lw-cell-flex"><div class="lw-avatar">{{ $participant->initial }}</div><div class="lw-min-0"><p class="lw-cell-name">{{ $participant->participant_name }}</p></div></div></td><td><span class="lw-cell-email">{{ $participant->participant_email ?: '—' }}</span></td><td class="t-center"><div class="lw-att-toggle"><span class="lw-att-seg {{ $status === 'present' ? 'is-present' : '' }}">Present</span><span class="lw-att-seg {{ $status === 'late' ? 'is-late' : '' }}">Late</span><span class="lw-att-seg {{ $status === 'absent' ? 'is-absent' : '' }}">Absent</span></div>@if ($status === 'not recorded')<div class="lw-att-notrec">Not Recorded</div>@endif</td><td>@if ($participant->self_recorded_at)<span class="lw-cell-date">{{ \Carbon\Carbon::parse($participant->self_recorded_at)->format('d M Y') }}</span>@else<span class="lw-cell-dash">—</span>@endif</td><td>@if ($participant->self_recorded_at)<span class="lw-cell-time">{{ \Carbon\Carbon::parse($participant->self_recorded_at)->format('h:i A') }}</span>@else<span class="lw-cell-dash">—</span>@endif</td></tr>
            @endforeach
        </tbody></table></div>
        @include('trainer.training.partials.report-paginator', ['paginator' => $attendancePaginator, 'state' => $attendanceFilters, 'noun' => 'participants', 'reportPath' => route('trainer.courses.attendance', $course->course_id)])
    @else
        <div class="lw-empty-cell"><i class="bi {{ $attendanceCounts['total'] === 0 ? 'bi-people' : 'bi-funnel' }}"></i><h4>{{ $attendanceCounts['total'] === 0 ? 'No participants found' : 'No matching participants' }}</h4><p>{{ $attendanceCounts['total'] === 0 ? 'Attendance roster needs enrollment records.' : 'Try changing the search keyword or attendance status filter.' }}</p></div>
    @endif
</div>
