<div id="lmsParticipantResults" class="lw-results-region" aria-live="polite">
    <span class="lw-loading-label">Updating participants…</span>
    <div class="lw-report-error" data-lms-report-error hidden>Participants could not be refreshed. Please try again.</div>
    @if ($participantPaginator->total() > 0)
        <div class="lw-table-wrap"><table class="lw-table"><thead><tr><th>Participant</th><th>Email</th><th class="t-center">Gender</th><th class="t-center">Access Status</th><th>Last Accessed</th></tr></thead><tbody>
            @foreach ($participantPaginator as $participant)
                @php($hasAccessed = ! empty($participant->last_accessed_at))
                <tr><td><div class="lw-cell-flex"><div class="lw-avatar">{{ $participant->initial }}</div><p class="lw-cell-name">{{ $participant->participant_name }}</p></div></td><td><span class="lw-cell-email">{{ $participant->participant_email ?: '—' }}</span></td><td class="t-center">@if ($participant->participant_gender === 'Male')<span class="lw-tag male"><i class="bi bi-gender-male"></i> Male</span>@elseif ($participant->participant_gender === 'Female')<span class="lw-tag female"><i class="bi bi-gender-female"></i> Female</span>@else<span class="lw-tag unknown">—</span>@endif</td><td class="t-center">@if ($hasAccessed)<span class="lw-tag accessed"><i class="bi bi-check2"></i> Accessed</span>@else<span class="lw-tag never"><i class="bi bi-hourglass"></i> Not Yet</span>@endif</td><td>@if ($participant->last_accessed_at)<div class="lw-cell-date">{{ \Carbon\Carbon::parse($participant->last_accessed_at)->format('d M Y') }}</div><div class="lw-cell-time">{{ \Carbon\Carbon::parse($participant->last_accessed_at)->format('h:i A') }}</div>@else<div class="lw-cell-date lw-muted-date">—</div>@endif</td></tr>
            @endforeach
        </tbody></table></div>
        @include('trainer.training.partials.report-paginator', ['paginator' => $participantPaginator, 'state' => $participantFilters, 'noun' => 'participants', 'reportPath' => route('trainer.courses.participants', $course->course_id)])
    @else
        <div class="lw-empty"><div class="lw-empty-icon"><i class="bi {{ $participantStats['total'] === 0 ? 'bi-people' : 'bi-funnel' }}"></i></div><h4>{{ $participantStats['total'] === 0 ? 'No participants yet' : 'No matching participants' }}</h4><p>{{ $participantStats['total'] === 0 ? "This course doesn't have any enrolment records yet. Once participants are enrolled they will appear here." : 'Try changing the search, gender, access, or A–Z filter.' }}</p></div>
    @endif
</div>
