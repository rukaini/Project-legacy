@php
    $activeLmsTab ??= 'overview';
    $lmsTabLabels = [
        'overview' => 'Overview',
        'teaching-plan' => 'Teaching Plan',
        'materials' => 'Materials',
        'submissions' => 'Submissions',
        'learner-tracking' => 'Learner Tracking',
        'attendance' => 'Attendance',
        'participants' => 'Participants',
    ];
    $lmsTabRoutes = [
        'overview' => route('trainer.courses.show', $course->course_id),
        'teaching-plan' => route('trainer.courses.teaching-plans.index', $course->course_id),
        'materials' => route('trainer.courses.materials', $course->course_id),
        'submissions' => route('trainer.courses.submissions', $course->course_id),
        'learner-tracking' => route('trainer.courses.learner-tracking', $course->course_id),
        'attendance' => route('trainer.courses.attendance', $course->course_id),
        'participants' => route('trainer.courses.participants', $course->course_id),
    ];
    $teachingPlanAttention = match ($teachingPlanNavigationState['key'] ?? null) {
        'upload-required' => ['tone' => 'required', 'label' => 'Teaching plan upload required', 'text' => 'Required'],
        'new-feedback' => ['tone' => 'feedback', 'label' => 'New teaching plan feedback', 'text' => 'New feedback'],
        default => null,
    };
@endphp

@once
    @include('trainer.teaching-plan.partials.tab-attention-styles')
@endonce

<section class="lw-header" data-lms-workspace-header>
    <div class="lw-header-inner app-workspace-shell app-workspace-shell--header">
        <div class="lw-breadcrumb">
            <a href="{{ route('trainer.courses') }}">My Training</a>
            <span>/</span>
            <a href="{{ route('trainer.courses.show', $course->course_id) }}">{{ $course->course_name }}</a>
            <span>/</span>
            @if (! empty($lmsSubpageTitle))
                <a href="{{ $lmsTabRoutes[$activeLmsTab] }}">{{ $lmsTabLabels[$activeLmsTab] }}</a>
                <span>/</span>
                <span class="lw-bc-current">{{ $lmsSubpageTitle }}</span>
            @else
                <span class="lw-bc-current">{{ $lmsTabLabels[$activeLmsTab] }}</span>
            @endif
        </div>

        <div class="lw-title-row">
            <div class="lw-title-left" style="display:flex;align-items:flex-start;gap:16px;min-width:0;flex:1;">
                <div class="lw-icon"><i class="bi bi-mortarboard"></i></div>
                <div class="lw-min-0 lw-flex-1" style="min-width:0;flex:1;">
                    <h1 class="lw-title">{{ $course->course_name }}</h1>
                    <div class="lw-meta">
                        <span class="lw-pill mode"><i class="bi bi-diagram-3"></i> LMS</span>
                        <span class="lw-pill"><i class="bi bi-person-badge"></i> {{ \App\Support\ParticipantTypeLabel::audience($course->target_participant ?? null, 'Target not set') }}</span>
                    </div>
                </div>
            </div>
            @if ($activeLmsTab === 'attendance')
                <button type="button" id="calendarViewButton" class="lw-btn" data-calendar-open>
                    <i class="bi bi-calendar3"></i> Calendar View
                </button>
            @endif
        </div>

        <nav class="lw-tabs" aria-label="LMS workspace">
            @foreach ($lmsTabLabels as $tab => $label)
                <a href="{{ $lmsTabRoutes[$tab] }}" @class(['active' => $activeLmsTab === $tab]) @if ($activeLmsTab === $tab) aria-current="page" @endif>
                    {{ $label }}
                    @if ($tab === 'teaching-plan' && $teachingPlanAttention)
                        <span class="tp-tab-attention {{ $teachingPlanAttention['tone'] }}"
                              data-teaching-plan-attention="{{ $teachingPlanAttention['tone'] === 'required' ? 'upload-required' : 'new-feedback' }}"
                              aria-label="{{ $teachingPlanAttention['label'] }}"
                              title="{{ $teachingPlanAttention['label'] }}">{{ $teachingPlanAttention['text'] }}</span>
                    @endif
                    @if ($tab === 'submissions')
                        <span id="lmsSubmissionsPendingBadge"
                              data-lms-submissions-pending
                              @class(['lw-tab-badge', 'lw-hidden' => ($lmsPendingReviewCount ?? 0) < 1])
                              @if (($lmsPendingReviewCount ?? 0) < 1) hidden @endif>
                            <span data-lms-submissions-pending-count>{{ $lmsPendingReviewCount ?? 0 }}</span> pending
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
</section>
