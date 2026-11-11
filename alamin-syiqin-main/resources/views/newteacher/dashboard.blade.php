<x-app-layout>
    <div class="app-page-shell">

        {{-- PAGE HEADING --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 mb-7">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.7"
                        stroke="currentColor" class="w-6 h-6 text-blue-600">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75a17.933 17.933 0 01-7.5-1.632z" />
                    </svg>
                </div>

                <div>
                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Dashboard
                    </h1>
                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Welcome back, {{ $guru->gn_name }}.
                        Track your observation and evaluation progress.
                    </p>
                </div>
            </div>
        </div>

        {{-- EVALUATION OVERVIEW --}}
        <div class="app-section-card mb-7">
            <div class="px-5 md:px-6 py-5">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                            <span class="text-[16px] font-extrabold text-blue-600">
                                {{ strtoupper(substr($guru->gn_name ?? 'N', 0, 1)) }}
                            </span>
                        </div>

                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400">
                                Evaluation Progress
                            </p>

                            <h2 class="text-[18px] font-extrabold text-slate-900 mt-1">
                                {{ $guru->gn_name }}
                            </h2>

                            <p class="text-[12.5px] text-slate-500 mt-1">
                                {{ $submittedForms }} of {{ $totalForms }} evaluation components completed
                            </p>

                            @if(isset($evaluationStatus))
                            <div class="mt-3">
                                @if($evaluationStatus === 'Completed')
                                <span class="app-badge app-badge-success">
                                    Completed
                                </span>
                                @elseif($evaluationStatus === 'Repeat Required')
                                <span class="app-badge app-badge-danger">
                                    Repeat Required
                                </span>
                                @elseif($evaluationStatus === 'In Progress')
                                <span class="app-badge app-badge-info">
                                    In Progress
                                </span>
                                @else
                                <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                                    Pending
                                </span>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="w-full lg:w-[330px]">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-slate-500">
                                Completion
                            </span>
                            <span class="text-[12px] font-extrabold text-slate-800">
                                {{ $submittedForms }}/{{ $totalForms }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            @for($i = 1; $i <= $totalForms; $i++)
                                <div class="h-2 flex-1 rounded-full
                                    {{ $i <= $submittedForms ? 'bg-blue-600' : 'bg-slate-200' }}">
                        </div>
                        @endfor
                    </div>

                    <p class="text-right text-[11px] font-semibold text-slate-500 mt-2">
                        {{ $submittedPercentage }}%
                    </p>
                </div>

            </div>
        </div>
    </div>

    {{-- EVALUATION PROGRESS --}}
    <div class="app-section-card">

        <div class="px-5 md:px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-[17px] font-extrabold text-slate-900">
                    Evaluation Progress
                </h2>
            </div>

            <a href="{{ route('new_teacher.result') }}"
                class="app-button-outline">
                View Results
            </a>
        </div>

        <div class="p-5 md:p-6">

            {{-- STAGE INDICATOR --}}
            <div class="relative mb-8">
                <div class="hidden md:block absolute top-5 left-[16.5%] right-[16.5%] h-[2px] bg-slate-200 rounded-full"></div>

                <div class="relative grid grid-cols-1 md:grid-cols-3 gap-5">

                    {{-- STAGE 01 --}}
                    <div>
                        <div class="flex md:flex-col md:items-center gap-4">
                            <div class="relative z-10 shrink-0">
                                <div class="w-10 h-10 rounded-full border-4 border-white shadow-sm flex items-center justify-center text-[12px] font-extrabold
                                        {{ $preStatus === 'completed' ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500' }}">
                                    @if($preStatus === 'completed')
                                    ✓
                                    @else
                                    1
                                    @endif
                                </div>
                            </div>

                            <div class="md:text-center">
                                <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400">
                                    Stage 01
                                </p>
                                <h3 class="text-[13.5px] font-extrabold text-slate-900 mt-1">
                                    Pre-Observation
                                </h3>
                            </div>
                        </div>
                    </div>

                    {{-- STAGE 02 --}}
                    <div>
                        <div class="flex md:flex-col md:items-center gap-4">
                            <div class="relative z-10 shrink-0">
                                <div class="w-10 h-10 rounded-full border-4 border-white shadow-sm flex items-center justify-center text-[12px] font-extrabold
                                        {{
                                            $externalStatus === 'completed'
                                                ? 'bg-emerald-500 text-white'
                                                : (
                                                    $externalStatus === 'repeat'
                                                        ? 'bg-red-500 text-white'
                                                        : (
                                                            $externalStatus === 'in_progress'
                                                                ? 'bg-blue-600 text-white'
                                                                : 'bg-slate-200 text-slate-500'
                                                        )
                                                )
                                        }}">
                                    @if($externalStatus === 'completed')
                                    ✓
                                    @elseif($externalStatus === 'repeat')
                                    ↻
                                    @else
                                    2
                                    @endif
                                </div>
                            </div>

                            <div class="md:text-center">
                                <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400">
                                    Stage 02
                                </p>
                                <h3 class="text-[13.5px] font-extrabold text-slate-900 mt-1">
                                    External Observation
                                </h3>
                            </div>
                        </div>
                    </div>

                    {{-- STAGE 03 --}}
                    <div>
                        <div class="flex md:flex-col md:items-center gap-4">
                            <div class="relative z-10 shrink-0">
                                <div class="w-10 h-10 rounded-full border-4 border-white shadow-sm flex items-center justify-center text-[12px] font-extrabold
                                        {{
                                            $postStatus === 'completed'
                                                ? 'bg-emerald-500 text-white'
                                                : (
                                                    $postStatus === 'in_progress'
                                                        ? 'bg-blue-600 text-white'
                                                        : 'bg-slate-200 text-slate-500'
                                                )
                                        }}">
                                    @if($postStatus === 'completed')
                                    ✓
                                    @else
                                    3
                                    @endif
                                </div>
                            </div>

                            <div class="md:text-center">
                                <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400">
                                    Stage 03
                                </p>
                                <h3 class="text-[13.5px] font-extrabold text-slate-900 mt-1">
                                    Post-Observation
                                </h3>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- EVALUATION STAGE CARDS --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

                {{-- PRE-OBSERVATION --}}
                <div class="border border-slate-200 rounded-2xl p-5 bg-white">

                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400">
                                Stage 01
                            </p>
                            <h3 class="text-[14px] font-extrabold text-slate-900 mt-1">
                                Pre-Observation
                            </h3>
                        </div>

                        <span class="app-badge
                                {{ $preStatus === 'completed'
                                    ? 'app-badge-success'
                                    : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                            {{ $preStatus === 'completed' ? 'Completed' : 'Pending' }}
                        </span>
                    </div>

                    <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-[12.5px] font-semibold text-slate-700">
                                {{ $pre?->form?->form_name ?? 'Pre-Observation Form' }}
                            </span>

                            @if($pre?->percentage !== null)
                            <span class="text-[13px] font-extrabold text-slate-900">
                                {{ number_format((float) $pre->percentage, 2) }}%
                            </span>
                            @elseif(!$pre)
                            <span class="text-[11px] font-bold text-slate-400">
                                Pending
                            </span>
                            @endif
                        </div>
                    </div>

                </div>

                {{-- EXTERNAL OBSERVATION --}}
                <div class="border rounded-2xl p-5
                        {{ $externalStatus === 'repeat'
                            ? 'border-red-200 bg-red-50/40'
                            : 'border-slate-200 bg-white' }}">

                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400">
                                Stage 02
                            </p>
                            <h3 class="text-[14px] font-extrabold text-slate-900 mt-1">
                                External Observation
                            </h3>
                        </div>

                        <span class="app-badge
                                {{
                                    $externalStatus === 'completed'
                                        ? 'app-badge-success'
                                        : (
                                            $externalStatus === 'repeat'
                                                ? 'app-badge-danger'
                                                : (
                                                    $externalStatus === 'in_progress'
                                                        ? 'app-badge-info'
                                                        : 'border-slate-200 bg-slate-50 text-slate-500'
                                                )
                                        )
                                }}">
                            @if($externalStatus === 'completed')
                            Completed
                            @elseif($externalStatus === 'repeat')
                            Repeat Required
                            @elseif($externalStatus === 'in_progress')
                            In Progress
                            @else
                            Pending
                            @endif
                        </span>
                    </div>

                    <div class="space-y-2">

                        {{-- EXTERNAL PDPC --}}
                        <div class="bg-white border border-slate-200 rounded-xl px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-[12.5px] font-semibold text-slate-700">
                                    {{ $externalPdpc?->form?->form_name ?? 'PDPC Observation Form' }}
                                </span>

                                @if($externalPdpc?->percentage !== null)
                                <span class="text-[13px] font-extrabold text-slate-900">
                                    {{ number_format((float) $externalPdpc->percentage, 2) }}%
                                </span>
                                @elseif(!$externalPdpc)
                                <span class="text-[11px] font-bold text-slate-400">
                                    Pending
                                </span>
                                @endif
                            </div>
                        </div>

                        {{-- EXTERNAL FEEDBACK --}}
                        <div class="bg-white border border-slate-200 rounded-xl px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-[12.5px] font-semibold text-slate-700">
                                    {{ $externalFeedback?->form?->form_name ?? 'Feedback Form' }}
                                </span>

                                @if(!$externalFeedback)
                                <span class="text-[11px] font-bold text-slate-400">
                                    Pending
                                </span>
                                @elseif(
                                $externalPdpc &&
                                (int) $externalFeedback->attempt_no !==
                                (int) $externalPdpc->attempt_no
                                )
                                <span class="text-[11px] font-bold text-slate-400">
                                    Pending
                                </span>
                                @endif
                            </div>
                        </div>

                    </div>

                    {{-- PREVIOUS EXTERNAL ATTEMPTS --}}
                    @if($externalAttempts->count() > 1)
                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400 mb-3">
                            Previous Evaluation
                        </p>

                        <div class="space-y-2">
                            @foreach($externalAttempts->skip(1) as $previous)
                            <div class="flex items-center justify-between gap-4 bg-white border border-slate-200 rounded-xl px-4 py-3">
                                <span class="text-[12.5px] font-semibold text-slate-700">
                                    Score
                                </span>

                                <div class="flex items-center gap-3">
                                    @if($previous->percentage !== null)
                                    <span class="text-[13px] font-extrabold text-slate-900">
                                        {{ number_format((float) $previous->percentage, 2) }}%
                                    </span>
                                    @endif

                                    @if($previous->result)
                                    <span class="app-badge
                                                        {{ $previous->result === 'PASS'
                                                            ? 'app-badge-success'
                                                            : 'app-badge-danger' }}">
                                        {{ $previous->result }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>

                {{-- POST-OBSERVATION --}}
                <div class="border border-slate-200 rounded-2xl p-5 bg-white">

                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400">
                                Stage 03
                            </p>
                            <h3 class="text-[14px] font-extrabold text-slate-900 mt-1">
                                Post-Observation
                            </h3>
                        </div>

                        <span class="app-badge
                                {{
                                    $postStatus === 'completed'
                                        ? 'app-badge-success'
                                        : (
                                            $postStatus === 'in_progress'
                                                ? 'app-badge-info'
                                                : 'border-slate-200 bg-slate-50 text-slate-500'
                                        )
                                }}">
                            @if($postStatus === 'completed')
                            Completed
                            @elseif($postStatus === 'in_progress')
                            In Progress
                            @else
                            Pending
                            @endif
                        </span>
                    </div>

                    <div class="space-y-2">

                        {{-- POST PDPC --}}
                        <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-[12.5px] font-semibold text-slate-700">
                                    {{ $postPdpc?->form?->form_name ?? 'PDPC Observation Form' }}
                                </span>

                                @if($postPdpc?->percentage !== null)
                                <span class="text-[13px] font-extrabold text-slate-900">
                                    {{ number_format((float) $postPdpc->percentage, 2) }}%
                                </span>
                                @elseif(!$postPdpc)
                                <span class="text-[11px] font-bold text-slate-400">
                                    Pending
                                </span>
                                @endif
                            </div>
                        </div>

                        {{-- POST FEEDBACK --}}
                        <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-[12.5px] font-semibold text-slate-700">
                                    {{ $postFeedback?->form?->form_name ?? 'Feedback Observation Form' }}
                                </span>

                                @if(!$postFeedback)
                                <span class="text-[11px] font-bold text-slate-400">
                                    Pending
                                </span>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- TRAINING PROGRESS - NEW SECTION --}}
    {{-- ===================================================== --}}

    <div class="app-section-card mt-7">

        <div class="px-5 md:px-6 py-5 border-b border-slate-100">
            <h2 class="text-[17px] font-extrabold text-slate-900">
                Training Progress
            </h2>

            <p class="text-[12.5px] text-slate-500 mt-1">
                Track your LMS course attendance, feedback and training completion.
            </p>
        </div>

        <div class="p-5 md:p-6">

            {{-- TRAINING SUMMARY CARDS --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        Total Courses
                    </p>
                    <p class="text-[28px] font-extrabold text-slate-900 mt-2">
                        {{ $totalTrainingCourses }}
                    </p>
                </div>

                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                        Completed Courses
                    </p>
                    <p class="text-[28px] font-extrabold text-emerald-700 mt-2">
                        {{ $completedTrainingCourses }}
                    </p>
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-xl p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-blue-700">
                        Overall Progress
                    </p>
                    <p class="text-[28px] font-extrabold text-blue-700 mt-2">
                        {{ $trainingPercentage }}%
                    </p>
                </div>

            </div>

            {{-- OVERALL TRAINING COMPLETION --}}
            <div class="border border-slate-200 rounded-xl p-5 mb-6">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-3">

                    <div>
                        <h3 class="text-[13.5px] font-extrabold text-slate-900">
                            Overall Training Completion
                        </h3>

                        <p class="text-[12px] text-slate-500 mt-1">
                            {{ $completedTrainingCourses }} of {{ $totalTrainingCourses }} LMS courses completed
                        </p>
                    </div>

                    <div>
                        @if($trainingStatus === 'Completed')
                        <span class="app-badge app-badge-success">
                            Completed
                        </span>
                        @elseif($trainingStatus === 'In Progress')
                        <span class="app-badge app-badge-info">
                            In Progress
                        </span>
                        @else
                        <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                            Not Started
                        </span>
                        @endif
                    </div>

                </div>

                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-blue-600 h-2.5 rounded-full"
                        @style(['width: ' . $trainingPercentage . ' %'])>
                    </div>
                </div>

                <p class="text-right text-[11px] font-semibold text-slate-500 mt-2">
                    {{ $trainingPercentage }}%
                </p>

            </div>

            {{-- LMS COURSE PROGRESS --}}
            <h3 class="text-[14px] font-extrabold text-slate-900 mb-4">
                LMS Course Progress
            </h3>

            <div class="space-y-3">

                @forelse($trainingCourses as $course)

                @php
                $courseTotalSessions = $course['total_sessions'];
                $courseApprovedSessions = $course['approved_sessions'];

                $coursePercentage = $courseTotalSessions > 0
                ? round(($courseApprovedSessions / $courseTotalSessions) * 100)
                : 0;

                $courseStarted =
                $courseApprovedSessions > 0 ||
                $course['feedback_completed'];

                $courseStatus = $course['completed']
                ? 'Completed'
                : ($courseStarted ? 'In Progress' : 'Not Started');
                @endphp

                <div class="border border-slate-200 rounded-2xl p-5 bg-white">

                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">

                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                LMS Course
                            </p>

                            <h4 class="text-[14px] font-extrabold text-slate-900 mt-1">
                                {{ $course['course_name'] }}
                            </h4>
                        </div>

                        <div>
                            @if($courseStatus === 'Completed')
                            <span class="app-badge app-badge-success">
                                Completed
                            </span>
                            @elseif($courseStatus === 'In Progress')
                            <span class="app-badge app-badge-info">
                                In Progress
                            </span>
                            @else
                            <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                                Not Started
                            </span>
                            @endif
                        </div>

                    </div>

                    {{-- COURSE DETAILS --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">

                        <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                            <p class="text-[11px] text-slate-500">
                                Approved Attendance
                            </p>

                            <p class="text-[13px] font-extrabold text-slate-900 mt-1">
                                {{ $courseApprovedSessions }}/{{ $courseTotalSessions }} Sessions
                            </p>
                        </div>

                        <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                            <p class="text-[11px] text-slate-500">
                                Course Feedback
                            </p>

                            <div class="mt-2">
                                @if($course['feedback_completed'])
                                <span class="app-badge app-badge-success">
                                    Completed
                                </span>
                                @else
                                <span class="app-badge border-slate-200 bg-white text-slate-500">
                                    Pending
                                </span>
                                @endif
                            </div>
                        </div>

                    </div>

                    {{-- ATTENDANCE PROGRESS --}}
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500">
                            Attendance Progress
                        </span>

                        <span class="text-[12px] font-extrabold text-slate-800">
                            {{ $coursePercentage }}%
                        </span>
                    </div>

                    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                        <div class="bg-blue-600 h-2 rounded-full"
                            @style(['width: ' . $coursePercentage . ' %'])>
                        </div>
                    </div>

                </div>

                @empty

                <div class="border border-dashed border-slate-200 rounded-xl p-8 text-center">
                    <p class="font-semibold text-slate-700">
                        No LMS courses available
                    </p>

                    <p class="text-[12px] text-slate-400 mt-1">
                        Training progress will appear when LMS courses are available.
                    </p>
                </div>

                @endforelse

            </div>

        </div>
    </div>

    </div>
</x-app-layout>