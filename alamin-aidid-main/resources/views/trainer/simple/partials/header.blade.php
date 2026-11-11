@php
    $activeSimpleTab = $activeSimpleTab ?? 'overview';
    $simpleTabs = [
        'overview' => ['label' => 'Overview', 'route' => route('trainer.simple.overview', $course->course_id)],
        'teaching-plan' => ['label' => 'Teaching Plan', 'route' => route('trainer.courses.teaching-plans.index', $course->course_id)],
        'materials' => ['label' => 'Materials', 'route' => route('trainer.simple.materials', $course->course_id)],
        'attendance' => ['label' => 'Attendance', 'route' => route('trainer.simple.attendance', $course->course_id)],
        'participants' => ['label' => 'Participants', 'route' => route('trainer.simple.participants', $course->course_id)],
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

<section class="sw-header" data-simple-training-header>
    <div class="sw-header-inner app-workspace-shell app-workspace-shell--header">
        <div class="sw-breadcrumb">
            <a href="{{ route('trainer.courses') }}">My Training</a>
            <span>/</span>
            <a href="{{ route('trainer.simple.overview', $course->course_id) }}">{{ $course->course_name }}</a>
            <span>/</span>
            <span class="sw-bc-current">{{ $simpleTabs[$activeSimpleTab]['label'] }}</span>
        </div>

        <div class="sw-title-row">
            <div class="sw-icon" aria-hidden="true"><i class="bi bi-easel"></i></div>
            <div class="sw-title-copy">
                <h1 class="sw-title">{{ $course->course_name }}</h1>
                <div class="sw-meta">
                    <span class="sw-pill mode"><i class="bi bi-easel"></i> Simple Training</span>
                </div>
            </div>
        </div>

        <nav class="sw-tabs" aria-label="Simple Training workspace">
            @foreach ($simpleTabs as $tabKey => $tab)
                <a href="{{ $tab['route'] }}"
                   class="{{ $activeSimpleTab === $tabKey ? 'active' : '' }}"
                   @if ($activeSimpleTab === $tabKey) aria-current="page" @endif>
                    {{ $tab['label'] }}
                    @if ($tabKey === 'teaching-plan' && $teachingPlanAttention)
                        <span class="tp-tab-attention {{ $teachingPlanAttention['tone'] }}"
                              data-teaching-plan-attention="{{ $teachingPlanAttention['tone'] === 'required' ? 'upload-required' : 'new-feedback' }}"
                              aria-label="{{ $teachingPlanAttention['label'] }}"
                              title="{{ $teachingPlanAttention['label'] }}">{{ $teachingPlanAttention['text'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
</section>
