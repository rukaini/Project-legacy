<x-app-layout>
    <div class="app-page-shell">
        <div class="max-w-7xl mx-auto">

            {{-- Page heading --}}
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 mb-7">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                            class="w-6 h-6 text-blue-600">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25
                                   c.621 0 1.125.504 1.125 1.125v6.75
                                   C7.5 20.496 6.996 21 6.375 21h-2.25
                                   A1.125 1.125 0 013 19.875v-6.75z
                                   M9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25
                                   c.621 0 1.125.504 1.125 1.125v11.25
                                   c0 .621-.504 1.125-1.125 1.125h-2.25
                                   a1.125 1.125 0 01-1.125-1.125V8.625z
                                   M16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25
                                   C20.496 3 21 3.504 21 4.125v15.75
                                   c0 .621-.504 1.125-1.125 1.125h-2.25
                                   a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                            Evaluation Result
                        </h1>
                        <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                            View teacher evaluation results.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Teacher information --}}
            <div class="app-section-card mb-7">
                <div class="p-5 md:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                        <div>
                            <p class="app-label">Teacher Name</p>
                            <p class="text-[14px] font-extrabold text-slate-900">
                                {{ $guru->gn_name }}
                            </p>
                        </div>

                        <div>
                            <p class="app-label">Status</p>
                            <p class="text-[14px] font-extrabold text-slate-900">
                                {{ $guru->current_status }}
                            </p>
                        </div>

                        <div>
                            <p class="app-label">Overall Status</p>
                            <div class="mt-1">
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
                        </div>

                    </div>
                </div>
            </div>

            {{-- Evaluation Progress --}}
            <div class="app-section-card mb-7">
                <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Evaluation Progress
                    </h2>
                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Completion summary for each evaluation stage.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 divide-y sm:divide-y-0 lg:divide-x divide-slate-100">

                    {{-- PRE --}}
                    <div class="px-5 py-5">
                        <p class="app-label">PRE</p>

                        @if($preCompleted === $preTotal)
                        <span class="app-badge app-badge-success">
                            {{ $preCompleted }}/{{ $preTotal }}
                        </span>
                        @elseif($preCompleted > 0)
                        <span class="app-badge app-badge-info">
                            {{ $preCompleted }}/{{ $preTotal }}
                        </span>
                        @else
                        <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                            {{ $preCompleted }}/{{ $preTotal }}
                        </span>
                        @endif
                    </div>

                    {{-- EXTERNAL --}}
                    <div class="px-5 py-5">
                        <p class="app-label">External</p>

                        @if($externalPdpc?->result === 'REPEAT')
                        <span class="app-badge app-badge-danger">
                            {{ $externalCompleted }}/{{ $externalTotal }}
                        </span>
                        @elseif(
                        $externalCompleted === $externalTotal &&
                        $externalPdpc?->result === 'PASS'
                        )
                        <span class="app-badge app-badge-success">
                            {{ $externalCompleted }}/{{ $externalTotal }}
                        </span>
                        @elseif($externalCompleted > 0)
                        <span class="app-badge app-badge-info">
                            {{ $externalCompleted }}/{{ $externalTotal }}
                        </span>
                        @else
                        <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                            {{ $externalCompleted }}/{{ $externalTotal }}
                        </span>
                        @endif
                    </div>

                    {{-- POST --}}
                    <div class="px-5 py-5">
                        <p class="app-label">POST</p>

                        @if($postPdpc?->result === 'REPEAT')
                        <span class="app-badge app-badge-danger">
                            {{ $postCompleted }}/{{ $postTotal }}
                        </span>
                        @elseif(
                        $postCompleted === $postTotal &&
                        $postPdpc?->result === 'PASS'
                        )
                        <span class="app-badge app-badge-success">
                            {{ $postCompleted }}/{{ $postTotal }}
                        </span>
                        @elseif($postCompleted > 0)
                        <span class="app-badge app-badge-info">
                            {{ $postCompleted }}/{{ $postTotal }}
                        </span>
                        @else
                        <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                            {{ $postCompleted }}/{{ $postTotal }}
                        </span>
                        @endif
                    </div>

                    {{-- OVERALL PROGRESS --}}
                    <div class="px-5 py-5">
                        <p class="app-label">Progress</p>

                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full {{ $evaluationStatus === 'Completed' ? 'bg-emerald-500' : 'bg-blue-600' }}"
                                    @style(['width: ' . $overallProgress . ' %'])>
                                </div>
                            </div>

                            <span class="text-[12px] font-extrabold text-slate-900">
                                {{ $overallProgress }}%
                            </span>
                        </div>

                        <p class="text-[11px] text-slate-500 mt-2">
                            {{ $completedForms }}/{{ $totalForms }} evaluation components completed
                        </p>
                    </div>

                    {{-- OVERALL STATUS --}}
                    <div class="px-5 py-5">
                        <p class="app-label">Status</p>

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

                </div>
            </div>

            {{-- Latest Evaluation Results --}}
            <div class="app-section-card mb-7">
                <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Latest Evaluation Results
                    </h2>
                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Latest submitted results for each evaluation stage.
                    </p>
                </div>

                <div class="app-table-wrap">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>Stage</th>
                                <th>Evaluation</th>
                                <th class="!text-center">Date</th>
                                <th class="!text-center">Score</th>
                                <th class="!text-center">Achievement Level</th>
                                <th class="!text-center">Status</th>
                                <th class="!text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            {{-- PRE --}}
                            @if($pre)
                            <tr>
                                <td class="font-bold text-slate-900">
                                    PRE
                                </td>

                                <td>
                                    <p class="font-semibold text-slate-900">
                                        {{ $pre->form_name ?? '-' }}
                                    </p>
                                </td>

                                <td class="!text-center text-slate-600">
                                    {{ $pre->observation_date
                                            ? \Carbon\Carbon::parse($pre->observation_date)->format('d/m/Y')
                                            : '-' }}
                                </td>

                                <td class="!text-center font-semibold text-slate-800">
                                    {{ $pre->percentage !== null
                                            ? number_format($pre->percentage, 2).'%'
                                            : '-' }}
                                </td>

                                <td class="!text-center">
                                    @if($pre->achievement_level)
                                    <span class="app-badge app-badge-success">
                                        {{ $pre->achievement_level }}
                                    </span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="!text-center">
                                    <span class="app-badge app-badge-success">
                                        Submitted
                                    </span>
                                </td>

                                <td class="!text-center">
                                    <a href="{{ route('principal.result.pre', $pre->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endif

                            {{-- EXTERNAL PDPC --}}
                            @if($externalPdpc)
                            <tr>
                                <td class="font-bold text-slate-900">
                                    EXTERNAL
                                </td>

                                <td>
                                    <p class="font-semibold text-slate-900">
                                        {{ $externalPdpc->form_name ?? '-' }}
                                    </p>

                                    @if(($externalPdpc->attempt_no ?? 1) > 1)
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Attempt {{ $externalPdpc->attempt_no }}
                                    </p>
                                    @endif
                                </td>

                                <td class="!text-center text-slate-600">
                                    {{ $externalPdpc->observation_date
                                            ? \Carbon\Carbon::parse($externalPdpc->observation_date)->format('d/m/Y')
                                            : '-' }}
                                </td>

                                <td class="!text-center font-semibold text-slate-800">
                                    {{ $externalPdpc->percentage !== null
                                            ? number_format($externalPdpc->percentage, 2).'%'
                                            : '-' }}
                                </td>

                                <td class="!text-center">
                                    @if($externalPdpc->achievement_level)
                                    <span class="app-badge app-badge-info">
                                        {{ $externalPdpc->achievement_level }}
                                    </span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="!text-center">
                                    @if($externalPdpc->result === 'REPEAT')
                                    <span class="app-badge app-badge-danger">
                                        Repeat
                                    </span>
                                    @elseif($externalPdpc->result === 'PASS')
                                    <span class="app-badge app-badge-success">
                                        Pass
                                    </span>
                                    @else
                                    <span class="app-badge app-badge-info">
                                        Submitted
                                    </span>
                                    @endif
                                </td>

                                <td class="!text-center">
                                    <a href="{{ route('principal.result.pdpc', $externalPdpc->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endif

                            {{-- EXTERNAL FEEDBACK --}}
                            @if($externalFeedback)
                            <tr>
                                <td class="font-bold text-slate-900">
                                    EXTERNAL
                                </td>

                                <td>
                                    <p class="font-semibold text-slate-900">
                                        {{ $externalFeedback->form_name ?? '-' }}
                                    </p>

                                    @if(($externalFeedback->attempt_no ?? 1) > 1)
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Attempt {{ $externalFeedback->attempt_no }}
                                    </p>
                                    @endif
                                </td>

                                <td class="!text-center text-slate-600">
                                    {{ $externalFeedback->observation_date
                                            ? \Carbon\Carbon::parse($externalFeedback->observation_date)->format('d/m/Y')
                                            : '-' }}
                                </td>

                                <td class="!text-center text-slate-400">-</td>
                                <td class="!text-center text-slate-400">-</td>

                                <td class="!text-center">
                                    <span class="app-badge app-badge-success">
                                        Submitted
                                    </span>
                                </td>

                                <td class="!text-center">
                                    <a href="{{ route('principal.result.post', $externalFeedback->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endif

                            {{-- POST PDPC --}}
                            @if($postPdpc)
                            <tr>
                                <td class="font-bold text-slate-900">
                                    POST
                                </td>

                                <td>
                                    <p class="font-semibold text-slate-900">
                                        {{ $postPdpc->form_name ?? '-' }}
                                    </p>

                                    @if(($postPdpc->attempt_no ?? 1) > 1)
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Attempt {{ $postPdpc->attempt_no }}
                                    </p>
                                    @endif
                                </td>

                                <td class="!text-center text-slate-600">
                                    {{ $postPdpc->observation_date
                                            ? \Carbon\Carbon::parse($postPdpc->observation_date)->format('d/m/Y')
                                            : '-' }}
                                </td>

                                <td class="!text-center font-semibold text-slate-800">
                                    {{ $postPdpc->percentage !== null
                                            ? number_format($postPdpc->percentage, 2).'%'
                                            : '-' }}
                                </td>

                                <td class="!text-center">
                                    @if($postPdpc->achievement_level)
                                    <span class="app-badge app-badge-info">
                                        {{ $postPdpc->achievement_level }}
                                    </span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="!text-center">
                                    @if($postPdpc->percentage !== null && (float) $postPdpc->percentage < 85)
                                        <span class="app-badge app-badge-danger">
                                        Repeat
                                        </span>
                                        @elseif($postPdpc->percentage !== null && (float) $postPdpc->percentage >= 85)
                                        <span class="app-badge app-badge-success">
                                            Pass
                                        </span>
                                        @else
                                        <span class="app-badge app-badge-info">
                                            Submitted
                                        </span>
                                        @endif
                                </td>

                                <td class="!text-center">
                                    <a href="{{ route('principal.result.pdpc', $postPdpc->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endif

                            {{-- POST FEEDBACK --}}
                            @if($postFeedback)
                            <tr>
                                <td class="font-bold text-slate-900">
                                    POST
                                </td>

                                <td>
                                    <p class="font-semibold text-slate-900">
                                        {{ $postFeedback->form_name ?? '-' }}
                                    </p>

                                    @if(($postFeedback->attempt_no ?? 1) > 1)
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Attempt {{ $postFeedback->attempt_no }}
                                    </p>
                                    @endif
                                </td>

                                <td class="!text-center text-slate-600">
                                    {{ $postFeedback->observation_date
                                            ? \Carbon\Carbon::parse($postFeedback->observation_date)->format('d/m/Y')
                                            : '-' }}
                                </td>

                                <td class="!text-center text-slate-400">-</td>
                                <td class="!text-center text-slate-400">-</td>

                                <td class="!text-center">
                                    <span class="app-badge app-badge-success">
                                        Submitted
                                    </span>
                                </td>

                                <td class="!text-center">
                                    <a href="{{ route('principal.result.post', $postFeedback->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endif

                            {{-- EMPTY --}}
                            @if(!$pre && !$postPdpc && !$postFeedback && !$externalPdpc && !$externalFeedback)
                            <tr>
                                <td colspan="7" class="!py-14 !text-center">
                                    <p class="font-semibold text-slate-700">
                                        No evaluation results available
                                    </p>
                                    <p class="text-[12px] text-slate-400 mt-1">
                                        Submitted evaluation results will appear here.
                                    </p>
                                </td>
                            </tr>
                            @endif

                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Previous External Attempts --}}
            @if($externalHistory->isNotEmpty())
            <div class="app-section-card mb-7">
                <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Previous External Results
                    </h2>
                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Previous external observation attempts.
                    </p>
                </div>

                <div class="app-table-wrap">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th class="!text-center">Attempt</th>
                                <th>Evaluation</th>
                                <th class="!text-center">Date</th>
                                <th class="!text-center">Score</th>
                                <th class="!text-center">Achievement Level</th>
                                <th class="!text-center">Status</th>
                                <th class="!text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($externalHistory as $history)
                            <tr>
                                <td class="!text-center">
                                    <span class="app-badge border-violet-200 bg-violet-50 text-violet-700">
                                        Attempt {{ $history->attempt_no }}
                                    </span>
                                </td>

                                <td>
                                    <p class="font-semibold text-slate-900">
                                        {{ $history->form_name ?? '-' }}
                                    </p>
                                </td>

                                <td class="!text-center text-slate-600">
                                    {{ $history->observation_date
                                                ? \Carbon\Carbon::parse($history->observation_date)->format('d/m/Y')
                                                : '-' }}
                                </td>

                                <td class="!text-center font-semibold text-slate-800">
                                    {{ $history->percentage !== null
                                                ? number_format($history->percentage, 2).'%'
                                                : '-' }}
                                </td>

                                <td class="!text-center">
                                    @if($history->achievement_level)
                                    <span class="app-badge app-badge-info">
                                        {{ $history->achievement_level }}
                                    </span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="!text-center">
                                    @if($history->result === 'REPEAT')
                                    <span class="app-badge app-badge-danger">
                                        Repeat
                                    </span>
                                    @elseif($history->result === 'PASS')
                                    <span class="app-badge app-badge-success">
                                        Pass
                                    </span>
                                    @else
                                    <span class="app-badge app-badge-info">
                                        Submitted
                                    </span>
                                    @endif
                                </td>

                                <td class="!text-center">
                                    <div class="flex items-center justify-center gap-2">

                                        <a href="{{ route('principal.result.pdpc', $history->responseID) }}"
                                            class="app-button-outline whitespace-nowrap">
                                            View PDPC
                                        </a>

                                        @if($history->feedback)
                                        <a href="{{ route('principal.result.post', $history->feedback->responseID) }}"
                                            class="app-button-outline whitespace-nowrap">
                                            View Feedback
                                        </a>
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

            {{-- Previous POST Attempts --}}
            @if(isset($postHistory) && $postHistory->isNotEmpty())
            <div class="app-section-card mb-7">
                <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Previous POST Results
                    </h2>
                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Previous POST observation attempts.
                    </p>
                </div>

                <div class="app-table-wrap">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th class="!text-center">Attempt</th>
                                <th>Evaluation</th>
                                <th class="!text-center">Date</th>
                                <th class="!text-center">Score</th>
                                <th class="!text-center">Achievement Level</th>
                                <th class="!text-center">Status</th>
                                <th class="!text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($postHistory as $history)
                            <tr>
                                <td class="!text-center">
                                    <span class="app-badge border-violet-200 bg-violet-50 text-violet-700">
                                        Attempt {{ $history->attempt_no }}
                                    </span>
                                </td>

                                <td>
                                    <p class="font-semibold text-slate-900">
                                        {{ $history->form_name ?? '-' }}
                                    </p>
                                </td>

                                <td class="!text-center text-slate-600">
                                    {{ $history->observation_date
                                                ? \Carbon\Carbon::parse($history->observation_date)->format('d/m/Y')
                                                : '-' }}
                                </td>

                                <td class="!text-center font-semibold text-slate-800">
                                    {{ $history->percentage !== null
                                                ? number_format($history->percentage, 2).'%'
                                                : '-' }}
                                </td>

                                <td class="!text-center">
                                    @if($history->achievement_level)
                                    <span class="app-badge app-badge-info">
                                        {{ $history->achievement_level }}
                                    </span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="!text-center">
                                    @if($history->result === 'REPEAT')
                                    <span class="app-badge app-badge-danger">
                                        Repeat
                                    </span>
                                    @elseif($history->result === 'PASS')
                                    <span class="app-badge app-badge-success">
                                        Pass
                                    </span>
                                    @else
                                    <span class="app-badge app-badge-info">
                                        Submitted
                                    </span>
                                    @endif
                                </td>

                                <td class="!text-center">
                                    <a href="{{ route('principal.result.pdpc', $history->responseID) }}"
                                        class="app-button-outline">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- BACK BUTTON --}}
            <div class="flex justify-center">
                <a href="{{ route('principal.result') }}"
                    class="app-button-outline">
                    Back
                </a>
            </div>

        </div>
    </div>
</x-app-layout>