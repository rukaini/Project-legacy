<x-app-layout>
    <div class="app-page-shell">
        <div class="max-w-8xl mx-auto">

            {{-- PAGE HEADING --}}
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 mb-7">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24"
                            stroke-width="1.7" stroke="currentColor"
                            class="w-6 h-6 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                            Manage Evaluation
                        </h1>
                        <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                            Manage observation and feedback forms.
                        </p>
                    </div>
                </div>
            </div>

            {{-- TEACHER INFORMATION --}}
            <div class="app-section-card mb-7">
                <div class="px-5 md:px-6 py-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">
                                <span class="text-[13px] font-extrabold text-blue-600">
                                    {{ strtoupper(substr($guruNew->gn_name, 0, 1)) }}
                                </span>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400">
                                    Teacher
                                </p>
                                <p class="text-[14px] font-extrabold text-slate-900 mt-1 uppercase">
                                    {{ $guruNew->gn_name }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.7" stroke="currentColor"
                                    class="w-5 h-5 text-slate-500">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400">
                                    School
                                </p>
                                <p class="text-[14px] font-extrabold text-slate-900 mt-1">
                                    {{ $guruNew->school?->schoolName ?? '-' }}
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            {{-- OBSERVER: PRE AND OPTIONAL POST                   --}}

            @if($isObserver)

            {{-- PRE OBSERVATION --}}
            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            Pre-Observation
                        </h2>

                        <span class="app-badge app-badge-info">
                            PRE
                        </span>
                    </div>
                </div>

                <div class="app-table-wrap">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>Form</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Achievement Level</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-800">
                                        {{ $preForm?->form_name ?? 'Pre-Observation Form' }}
                                    </p>
                                </td>

                                <td class="text-center text-slate-600">
                                    {{ $preResponse?->observation_date
                                            ? \Carbon\Carbon::parse($preResponse->observation_date)->format('d/m/Y')
                                            : '-' }}
                                </td>

                                <td class="text-center font-semibold text-slate-800">
                                    {{ $preResponse?->percentage !== null
                                            ? number_format($preResponse->percentage, 2).'%'
                                            : '-' }}
                                </td>

                                <td class="text-center">
                                    @if($preResponse?->achievement_level)
                                    <span class="app-badge app-badge-info">
                                        {{ $preResponse->achievement_level }}
                                    </span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if(!$preResponse)
                                    <span class="app-badge app-badge-warning">
                                        Pending
                                    </span>
                                    @elseif($preResponse->status === 'Draft')
                                    <span class="app-badge app-badge-info">
                                        Draft
                                    </span>
                                    @else
                                    <span class="app-badge app-badge-success">
                                        Completed
                                    </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($preResponse?->status === 'Submitted')

                                    <a href="{{ route('observer.pre.view', $preResponse->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>

                                    @elseif($canManagePre && !$preResponse)

                                    <a href="{{ route('observer.pre.create', $guruNew->gn_id) }}"
                                        class="app-button-primary">
                                        Add
                                    </a>

                                    @elseif($canManagePre && $preResponse?->status === 'Draft')

                                    <a href="{{ route('observer.pre.edit', $preResponse->responseID) }}"
                                        class="inline-flex items-center justify-center min-h-[36px] px-4 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 text-[12px] font-bold hover:bg-amber-100 transition">
                                        Edit
                                    </a>

                                    @else

                                    <span class="text-slate-400 text-xs">
                                        Not Available
                                    </span>

                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>


            {{-- OPTIONAL POST OBSERVATION                         --}}
            @if($canAccessPost)

            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-[17px] font-extrabold text-slate-900">
                                Post-Observation
                            </h2>

                            <p class="text-[12.5px] text-slate-500 mt-1">
                                Optional POST evaluation assigned by Admin.
                            </p>
                        </div>

                        <span class="app-badge app-badge-info">
                            POST
                            @if($postAttemptNo)
                            - Attempt {{ $postAttemptNo }}
                            @endif
                        </span>
                    </div>
                </div>

                <div class="app-table-wrap">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>Form</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Achievement Level</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            {{-- POST PDPC --}}
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-800">
                                        {{ $pdpcForm?->form_name ?? 'PDPC Observation Form' }}
                                    </p>
                                </td>

                                <td class="text-center text-slate-600">
                                    {{ $pdpcPostResponse?->observation_date
                                                ? \Carbon\Carbon::parse($pdpcPostResponse->observation_date)->format('d/m/Y')
                                                : '-' }}
                                </td>

                                <td class="text-center font-semibold text-slate-800">
                                    {{ $pdpcPostResponse?->percentage !== null
                                                ? number_format($pdpcPostResponse->percentage, 2).'%'
                                                : '-' }}
                                </td>

                                <td class="text-center">
                                    @if($pdpcPostResponse?->achievement_level)
                                    <span class="app-badge app-badge-info">
                                        {{ $pdpcPostResponse->achievement_level }}
                                    </span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if(!$pdpcPostResponse)
                                    <span class="app-badge app-badge-warning">
                                        Pending
                                    </span>
                                    @elseif($pdpcPostResponse->status === 'Draft')
                                    <span class="app-badge app-badge-info">
                                        Draft
                                    </span>
                                    @elseif($pdpcPostResponse->result === 'REPEAT')
                                    <span class="app-badge app-badge-danger">
                                        Repeat
                                    </span>
                                    @else
                                    <span class="app-badge app-badge-success">
                                        Completed
                                    </span>
                                    @endif
                                </td>

                                <td class="text-center">

                                    @if(!$pdpcPostResponse && $canManagePost)

                                    <a href="{{ route('observer.pdpc.create', $guruNew->gn_id) }}"
                                        class="app-button-primary">
                                        Add
                                    </a>

                                    @elseif($pdpcPostResponse?->status === 'Draft' && $canManagePost)

                                    <a href="{{ route('observer.pdpc.edit', $pdpcPostResponse->responseID) }}"
                                        class="inline-flex items-center justify-center min-h-[36px] px-4 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 text-[12px] font-bold hover:bg-amber-100 transition">
                                        Edit
                                    </a>

                                    @elseif($pdpcPostResponse?->status === 'Submitted')

                                    <a href="{{ route('observer.pdpc.view', $pdpcPostResponse->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>

                                    @else

                                    <span class="text-slate-400 text-xs">
                                        Not Available
                                    </span>

                                    @endif

                                </td>
                            </tr>

                            {{-- POST FEEDBACK --}}
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-800">
                                        {{ $postForm?->form_name ?? 'Feedback Observation Form' }}
                                    </p>
                                </td>

                                <td class="text-center text-slate-600">
                                    {{ $feedbackResponse?->observation_date
                                                ? \Carbon\Carbon::parse($feedbackResponse->observation_date)->format('d/m/Y')
                                                : '-' }}
                                </td>

                                <td class="text-center text-slate-400">-</td>
                                <td class="text-center text-slate-400">-</td>

                                {{-- STATUS --}}
                                <td class="text-center">
                                    @if(!$feedbackResponse)
                                    <span class="app-badge app-badge-warning">
                                        Pending
                                    </span>
                                    @elseif($feedbackResponse->status === 'Draft')
                                    <span class="app-badge app-badge-info">
                                        Draft
                                    </span>
                                    @else
                                    <span class="app-badge app-badge-success">
                                        Completed
                                    </span>
                                    @endif
                                </td>

                                {{-- ACTION --}}
                                <td class="text-center">

                                    @if($feedbackResponse?->status === 'Submitted')

                                    <a href="{{ route('observer.post.view', $feedbackResponse->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>

                                    @elseif(
                                    $feedbackResponse?->status === 'Draft'
                                    && $canAccessPost
                                    && $postAssignment
                                    && strcasecmp((string) $postAssignment->status, 'Active') === 0
                                    && $pdpcPostResponse?->status === 'Submitted'
                                    )

                                    <a href="{{ route('observer.post.edit', $feedbackResponse->responseID) }}"
                                        class="inline-flex items-center justify-center min-h-[36px] px-4 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 text-[12px] font-bold hover:bg-amber-100 transition">
                                        Edit
                                    </a>

                                    @elseif(
                                    !$feedbackResponse
                                    && $canAccessPost
                                    && $postAssignment
                                    && strcasecmp((string) $postAssignment->status, 'Active') === 0
                                    && $pdpcPostResponse?->status === 'Submitted'
                                    )

                                    <a href="{{ route('observer.post.create', $guruNew->gn_id) }}"
                                        class="app-button-primary">
                                        Add
                                    </a>

                                    @elseif(!$feedbackResponse && $canManagePost)

                                    <button type="button" disabled
                                        title="Submit POST PdPc first"
                                        class="inline-flex items-center justify-center min-h-[36px] px-4 py-2 rounded-lg bg-slate-200 text-slate-400 text-[12px] font-bold cursor-not-allowed opacity-70">
                                        Add
                                    </button>

                                    @else

                                    <span class="text-slate-400 text-xs">
                                        Not Available
                                    </span>

                                    @endif

                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

            @endif

            @endif


            @if($isExternal && !empty($evaluationRemark))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>

                    <div>
                        <p class="text-sm font-bold text-emerald-800">
                            Evaluation Completed
                        </p>
                        <p class="text-sm text-emerald-700 mt-1">
                            {{ $evaluationRemark }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- ================================================= --}}
            {{-- EXTERNAL OBSERVATION                              --}}
            {{-- ================================================= --}}

            @if($isExternal && !$isObserver)

            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            External-Observation
                        </h2>
                    </div>

                    <span class="app-badge app-badge-info">
                        Attempt {{ $externalAttemptNo ?? 1 }}
                    </span>
                </div>

                <div class="app-table-wrap">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>Form</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Achievement Level</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            {{-- EXTERNAL PDPC --}}
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-800">
                                        {{ $pdpcForm?->form_name ?? 'PDPC Observation Form' }}
                                    </p>
                                </td>

                                <td class="text-center text-slate-600">
                                    {{ $latestExternalResponse?->observation_date
                                            ? \Carbon\Carbon::parse($latestExternalResponse->observation_date)->format('d/m/Y')
                                            : '-' }}
                                </td>

                                <td class="text-center font-semibold text-slate-800">
                                    {{ $latestExternalResponse?->percentage !== null
                                            ? number_format($latestExternalResponse->percentage, 2).'%'
                                            : '-' }}
                                </td>

                                <td class="text-center">
                                    @if($latestExternalResponse?->achievement_level)
                                    <span class="app-badge app-badge-info">
                                        {{ $latestExternalResponse->achievement_level }}
                                    </span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if(!$latestExternalResponse)
                                    <span class="app-badge app-badge-warning">
                                        Pending
                                    </span>
                                    @elseif($latestExternalResponse->status === 'Draft')
                                    <span class="app-badge app-badge-info">
                                        Draft
                                    </span>
                                    @elseif($latestExternalResponse->result === 'REPEAT')
                                    <span class="app-badge app-badge-danger">
                                        Repeat
                                    </span>
                                    @else
                                    <span class="app-badge app-badge-success">
                                        Completed
                                    </span>
                                    @endif
                                </td>

                                <td class="text-center">

                                    @if(!$latestExternalResponse && $canManageExternal)

                                    <a href="{{ route('external.pdpc.create', $guruNew->gn_id) }}"
                                        class="app-button-primary">
                                        Add
                                    </a>

                                    @elseif($latestExternalResponse?->status === 'Draft' && $canManageExternal)

                                    <a href="{{ route('external.pdpc.edit', $latestExternalResponse->responseID) }}"
                                        class="inline-flex items-center justify-center min-h-[36px] px-4 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 text-[12px] font-bold hover:bg-amber-100 transition">
                                        Edit
                                    </a>

                                    @elseif($latestExternalResponse?->status === 'Submitted')

                                    <a href="{{ route('external.pdpc.view', $latestExternalResponse->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>

                                    @else

                                    <span class="text-slate-400 text-xs">
                                        Not Available
                                    </span>

                                    @endif

                                </td>
                            </tr>

                            {{-- EXTERNAL FEEDBACK --}}
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-800">
                                        {{ $postForm?->form_name ?? 'Feedback Observation Form' }}
                                    </p>
                                </td>

                                <td class="text-center text-slate-600">
                                    {{ $feedbackResponse?->observation_date
                                            ? \Carbon\Carbon::parse($feedbackResponse->observation_date)->format('d/m/Y')
                                            : '-' }}
                                </td>

                                <td class="text-center text-slate-400">-</td>
                                <td class="text-center text-slate-400">-</td>

                                {{-- STATUS --}}
                                <td class="text-center">
                                    @if(!$feedbackResponse)
                                    <span class="app-badge app-badge-warning">
                                        Pending
                                    </span>
                                    @elseif($feedbackResponse->status === 'Draft')
                                    <span class="app-badge app-badge-info">
                                        Draft
                                    </span>
                                    @else
                                    <span class="app-badge app-badge-success">
                                        Completed
                                    </span>
                                    @endif
                                </td>

                                {{-- ACTION --}}
                                <td class="text-center">

                                    @if($feedbackResponse?->status === 'Submitted')

                                    <a href="{{ route('external.post.view', $feedbackResponse->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>

                                    @elseif(
                                    $feedbackResponse?->status === 'Draft'
                                    && $canManageExternalFeedback
                                    && $latestExternalResponse?->status === 'Submitted'
                                    )

                                    <a href="{{ route('external.post.edit', $feedbackResponse->responseID) }}"
                                        class="inline-flex items-center justify-center min-h-[36px] px-4 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 text-[12px] font-bold hover:bg-amber-100 transition">
                                        Edit
                                    </a>

                                    @elseif(
                                    !$feedbackResponse
                                    && $canManageExternalFeedback
                                    && $latestExternalResponse?->status === 'Submitted'
                                    )

                                    <a href="{{ route('external.post.create', $guruNew->gn_id) }}"
                                        class="app-button-primary">
                                        Add
                                    </a>

                                    @elseif(!$feedbackResponse && $canManageExternalFeedback)

                                    <button type="button" disabled
                                        title="Submit EXTERNAL PdPc first"
                                        class="inline-flex items-center justify-center min-h-[36px] px-4 py-2 rounded-lg bg-slate-200 text-slate-400 text-[12px] font-bold cursor-not-allowed opacity-70">
                                        Add
                                    </button>

                                    @else

                                    <span class="text-slate-400 text-xs">
                                        Not Available
                                    </span>

                                    @endif

                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

            @endif

            {{-- ================================================= --}}
            {{-- EVALUATION HISTORY - OBSERVER AND EXTERNAL          --}}
            {{-- SAME GN - SUBMITTED EXTERNAL RESPONSES ONLY        --}}
            {{-- ================================================= --}}

            @if(
            ($isExternal || ($isObserver && $canAccessPost)) &&
            ($externalHistory ?? collect())->isNotEmpty()
            )

            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Evaluation History
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Previous submitted External Observation attempts
                        for this teacher. Historical forms are view-only.
                    </p>
                </div>

                <div class="app-table-wrap">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th class="text-center">Attempt</th>
                                <th class="text-center">Date</th>
                                <th>Evaluator</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Achievement Level</th>
                                <th class="text-center">Result</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($externalHistory as $history)

                            <tr>
                                <td class="text-center">
                                    <span class="app-badge app-badge-info">
                                        Attempt {{ $history->attempt_no }}
                                    </span>
                                </td>

                                <td class="text-center text-slate-600">
                                    {{ $history->observation_date
                                                ? \Carbon\Carbon::parse($history->observation_date)->format('d/m/Y')
                                                : '-' }}
                                </td>

                                <td>
                                    <p class="font-semibold text-slate-800">
                                        {{ $history->evaluator_name ?? '-' }}
                                    </p>
                                </td>

                                <td class="text-center font-semibold text-slate-800">
                                    {{ $history->percentage !== null
                                                ? number_format($history->percentage, 2).'%'
                                                : '-' }}
                                </td>

                                <td class="text-center">
                                    @if($history->achievement_level)
                                    <span class="app-badge app-badge-info">
                                        {{ $history->achievement_level }}
                                    </span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($history->result === 'REPEAT')
                                    <span class="app-badge app-badge-danger">
                                        Repeat
                                    </span>
                                    @elseif($history->result === 'PASS')
                                    <span class="app-badge app-badge-success">
                                        Pass
                                    </span>
                                    @else
                                    <span class="text-slate-400">
                                        -
                                    </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div class="flex flex-wrap items-center justify-center gap-2">

                                        {{-- HISTORICAL PDPC --}}
                                        <a href="{{ route(
                                                    $isObserver ? 'observer.pdpc.view' : 'external.pdpc.view',
                                                    $history->responseID
                                                ) }}"
                                            class="app-button-outline">
                                            View PDPC
                                        </a>

                                        {{-- HISTORICAL FEEDBACK --}}
                                        @if(!empty($history->feedback_responseID))

                                        <a href="{{ route(
                                                        $isObserver ? 'observer.post.view' : 'external.post.view',
                                                        $history->feedback_responseID
                                                    ) }}"
                                            class="app-button-outline">
                                            View Feedback
                                        </a>

                                        @else

                                        <span class="text-slate-400 text-xs">
                                            No Feedback
                                        </span>

                                        @endif

                                    </div>
                                </td>
                            </tr>

                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @endif

            {{-- BACK BUTTON --}}
            <div class="flex justify-center mt-7">
                <a href="{{ $isObserver
                    ? route('observer.list.evaluate')
                    : route('external.list.evaluate') }}"
                    class="app-button-outline">
                    Back
                </a>
            </div>

        </div>
    </div>
</x-app-layout>