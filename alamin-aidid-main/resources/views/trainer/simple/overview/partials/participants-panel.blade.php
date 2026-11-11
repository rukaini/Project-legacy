<section class="sw-panel" aria-labelledby="overview-participants-title">
    <div class="sw-panel-header">
        <div><h2 class="sw-panel-title" id="overview-participants-title">Participants</h2><p class="sw-panel-sub">A compact view of current enrollment.</p></div>
        <a href="{{ route('trainer.simple.participants', $course->course_id) }}" class="sw-overview-link">View all <i class="bi bi-arrow-right"></i></a>
    </div>
    @if ($participantStats['total'] > 0)
        <div class="sw-participant-summary">
            <span><strong>{{ $participantStats['total'] }}</strong> enrolled</span>
            <span><strong>{{ $participantStats['male'] }}M / {{ $participantStats['female'] }}F</strong>@if ($participantStats['unspecified'] > 0) · {{ $participantStats['unspecified'] }} unspecified @endif</span>
            <span><strong>{{ $participantStats['teacher'] }}</strong> Teacher · <strong>{{ $participantStats['new_teacher'] }}</strong> New Teacher @if ($participantStats['public'] > 0) · <strong>{{ $participantStats['public'] }}</strong> Public @endif</span>
        </div>
        <div class="sw-participant-preview">
            @foreach ($participantsPreview as $participant)
                <div class="sw-preview-person"><div class="sw-avatar">{{ $participant->initial }}</div><div><div class="sw-preview-name">{{ $participant->participant_name }}</div><div class="sw-preview-meta">{{ $participant->participant_email ?: '—' }} · {{ $participant->participant_type_label }}</div></div></div>
            @endforeach
        </div>
    @else
        <div class="sw-empty"><i class="bi bi-people"></i><div class="sw-empty-title">No participants enrolled</div><div>Once participants enrol, they will appear here.</div></div>
    @endif
</section>
