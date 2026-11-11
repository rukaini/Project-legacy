<div id="simpleParticipantResults" class="sw-results-region">
    <span class="sw-loading-label">Updating participants…</span>
    <div class="sw-report-error" data-simple-report-error hidden>Participants could not be refreshed. Please try again.</div>
    @if ($participantPaginator->total() > 0)
        <div class="sw-table-wrap"><table class="sw-table"><thead><tr><th>Participant</th><th>Email</th><th>Phone Number</th><th>Organisation / Department</th></tr></thead><tbody>
            @foreach ($participantPaginator as $participant)
                <tr><td><div class="sw-participant-cell"><span class="sw-avatar">{{ $participant->initial }}</span><span class="sw-cell-name">{{ $participant->participant_name }}</span></div></td><td class="sw-cell-email">{{ $participant->participant_email ?: 'Not available' }}</td><td>{{ \App\Support\PhoneNumberPresenter::display($participant->participant_phone) }}</td><td>{{ $participant->participant_organisation ?: 'Not available' }}</td></tr>
            @endforeach
        </tbody></table></div>
        @include('trainer.simple.partials.paginator', ['paginator' => $participantPaginator, 'state' => $participantFilters, 'noun' => 'participants', 'reportPath' => route('trainer.simple.participants', $course->course_id)])
    @else
        <div class="sw-empty"><i class="bi {{ $participantStats['total'] === 0 ? 'bi-people' : 'bi-search' }}"></i><div class="sw-empty-title">{{ $participantStats['total'] === 0 ? 'No participants are enrolled in this training' : 'No participants match these filters' }}</div>@if ($participantStats['total'] > 0)<div>Try changing the search, gender, or A–Z filter.</div>@endif</div>
    @endif
</div>
