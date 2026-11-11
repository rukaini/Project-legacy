<x-app-layout>
    <div class="app-page-shell">
        <div class="max-w-8xl mx-auto">

            {{-- Page Header --}}
            <div class="flex items-start gap-4 mb-7">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200
                            flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                        class="w-6 h-6 text-blue-600">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0
                                 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>

                <div>
                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Evaluation Result
                    </h1>
                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        View your latest and previous evaluation results.
                    </p>
                </div>
            </div>


            {{-- Overall Evaluation Message --}}
            @php
            $preCompleted = $pre && $pre->status === 'Submitted';

            $externalPassed = $externalPdpc
            && $externalPdpc->status === 'Submitted'
            && $externalPdpc->result === 'PASS'
            && $externalFeedback
            && $externalFeedback->status === 'Submitted';

            $postPassed = $postPdpc
            && $postPdpc->status === 'Submitted'
            && $postPdpc->percentage !== null
            && (float) $postPdpc->percentage >= 85
            && $postFeedback
            && $postFeedback->status === 'Submitted';

            $evaluationPassed = $preCompleted && ($externalPassed || $postPassed);

            $externalRepeat = $externalPdpc
            && $externalPdpc->status === 'Submitted'
            && $externalPdpc->result === 'REPEAT'
            && $externalFeedback
            && $externalFeedback->status === 'Submitted';

            $postRepeat = $postPdpc
            && $postPdpc->status === 'Submitted'
            && $postPdpc->percentage !== null
            && (float) $postPdpc->percentage < 85
                && $postFeedback
                && $postFeedback->status === 'Submitted';

                $evaluationRepeat = !$evaluationPassed
                && ($externalRepeat || $postRepeat);
                @endphp

                @if($evaluationPassed)

                {{-- PASSED --}}
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 mt-0.5 shrink-0 text-emerald-600"
                        fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>

                    <div>
                        <p class="text-sm font-bold text-emerald-800">
                            You Have Successfully Passed Your Evaluation!
                        </p>
                        <p class="mt-1 text-[13px] text-emerald-700">
                            Congratulations! You have successfully completed your teacher
                            evaluation.
                        </p>
                    </div>
                </div>

                @elseif($evaluationRepeat)

                {{-- REPEAT REQUIRED --}}
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 mt-0.5 shrink-0 text-red-600"
                        fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.29 3.86 1.82 18.15A2.25 2.25 0 0 0 3.75 21h16.5a2.25 2.25 0 0 0 1.93-2.85L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    </svg>

                    <div>
                        <p class="text-sm font-bold text-red-800">
                            Further Evaluation Required
                        </p>
                        <p class="mt-1 text-[13px] text-red-700">
                            You have not yet passed the required observation assessment.
                            Please wait for further evaluation arrangements.
                        </p>
                    </div>
                </div>

                @else

                {{-- IN PROGRESS --}}
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 mt-0.5 shrink-0 text-blue-600"
                        fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>

                    <div>
                        <p class="text-sm font-bold text-blue-800">
                            Evaluation In Progress
                        </p>
                        <p class="mt-1 text-[13px] text-blue-700">
                            Your evaluation is currently in progress.
                        </p>
                    </div>
                </div>

                @endif


                {{-- Latest Evaluation Results --}}
                <div class="app-section-card mb-7">
                    <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100
                                    flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="w-[18px] h-[18px] text-blue-600">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25
                                         c.621 0 1.125.504 1.125 1.125v6.75
                                         C7.5 20.496 6.996 21 6.375 21h-2.25
                                         A1.125 1.125 0 013 19.875v-6.75ZM9.75
                                         8.625c0-.621.504-1.125 1.125-1.125h2.25
                                         c.621 0 1.125.504 1.125 1.125v11.25
                                         c0 .621-.504 1.125-1.125 1.125h-2.25
                                         a1.125 1.125 0 01-1.125-1.125V8.625ZM16.5
                                         4.125c0-.621.504-1.125 1.125-1.125h2.25
                                         C20.496 3 21 3.504 21 4.125v15.75
                                         c0 .621-.504 1.125-1.125 1.125h-2.25
                                         a1.125 1.125 0 01-1.125-1.125V4.125Z" />
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-[17px] font-extrabold text-slate-900">
                                    Latest Evaluation Results
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-blue-50 text-slate-600 uppercase text-[10.5px] tracking-[0.05em]">
                                    <th class="px-5 py-3.5 text-left font-extrabold whitespace-nowrap">Stage</th>
                                    <th class="px-5 py-3.5 text-left font-extrabold">Evaluation</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold whitespace-nowrap">Date</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold whitespace-nowrap">Score</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold whitespace-nowrap">Achievement Level</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold whitespace-nowrap">Status</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold whitespace-nowrap">Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                {{-- PRE --}}
                                @if($pre)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg
                                                     bg-blue-50 border border-blue-200
                                                     text-blue-700 text-[11px] font-extrabold">
                                            PRE
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-900">
                                            {{ $pre->form?->form_name ?? '-' }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-center text-slate-600 whitespace-nowrap">
                                        {{ $pre->observation_date
                                            ? \Carbon\Carbon::parse($pre->observation_date)->format('d/m/Y')
                                            : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center font-extrabold text-slate-900 whitespace-nowrap">
                                        {{ $pre->percentage !== null
                                            ? number_format($pre->percentage, 2) . '%'
                                            : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($pre->achievement_level)
                                        <span class="app-badge app-badge-success">
                                            {{ $pre->achievement_level }}
                                        </span>
                                        @else
                                        <span class="text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <span class="app-badge app-badge-success">
                                            Submitted
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <a href="{{ route('new_teacher.result.pre', $pre->responseID) }}"
                                            class="app-button-outline inline-flex items-center justify-center whitespace-nowrap">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @endif

                                {{-- EXTERNAL PDPC --}}
                                @if($externalPdpc)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg
                                                     bg-blue-50 border border-blue-200
                                                     text-blue-700 text-[11px] font-extrabold">
                                            EXTERNAL
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-900">
                                            {{ $externalPdpc->form?->form_name ?? '-' }}
                                        </p>
                                        <p class="text-[11.5px] text-slate-500 mt-1">
                                            Attempt {{ $externalPdpc->attempt_no }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-center text-slate-600 whitespace-nowrap">
                                        {{ $externalPdpc->observation_date
                                            ? \Carbon\Carbon::parse($externalPdpc->observation_date)->format('d/m/Y')
                                            : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center font-extrabold text-slate-900 whitespace-nowrap">
                                        {{ $externalPdpc->percentage !== null
                                            ? number_format($externalPdpc->percentage, 2) . '%'
                                            : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($externalPdpc->achievement_level)
                                        <span class="app-badge app-badge-info">
                                            {{ $externalPdpc->achievement_level }}
                                        </span>
                                        @else
                                        <span class="text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($externalPdpc->result === 'REPEAT')
                                        <span class="app-badge app-badge-danger">Repeat</span>
                                        @elseif($externalPdpc->result === 'PASS')
                                        <span class="app-badge app-badge-success">Pass</span>
                                        @else
                                        <span class="app-badge app-badge-info">Submitted</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <a href="{{ route('new_teacher.result.pdpc', $externalPdpc->responseID) }}"
                                            class="app-button-outline inline-flex items-center justify-center whitespace-nowrap">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @endif

                                {{-- EXTERNAL Feedback --}}
                                @if($externalFeedback)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg
                                                     bg-blue-50 border border-blue-200
                                                     text-blue-700 text-[11px] font-extrabold">
                                            EXTERNAL
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-900">
                                            {{ $externalFeedback->form?->form_name ?? '-' }}
                                        </p>
                                        <p class="text-[11.5px] text-slate-500 mt-1">
                                            Attempt {{ $externalFeedback->attempt_no }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-center text-slate-600 whitespace-nowrap">
                                        {{ $externalFeedback->observation_date
                                            ? \Carbon\Carbon::parse($externalFeedback->observation_date)->format('d/m/Y')
                                            : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center text-slate-400">-</td>
                                    <td class="px-5 py-4 text-center text-slate-400">-</td>

                                    <td class="px-5 py-4 text-center">
                                        <span class="app-badge app-badge-success">
                                            Submitted
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <a href="{{ route('new_teacher.result.post', $externalFeedback->responseID) }}"
                                            class="app-button-outline inline-flex items-center justify-center whitespace-nowrap">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @endif

                                {{-- POST PDPC --}}
                                @if($postPdpc)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg
                                                     bg-blue-50 border border-blue-200
                                                     text-blue-700 text-[11px] font-extrabold">
                                            POST
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-900">
                                            {{ $postPdpc->form?->form_name ?? '-' }}
                                        </p>
                                        @if(($postPdpc->attempt_no ?? 1) > 1)
                                        <p class="text-[11.5px] text-slate-500 mt-1">
                                            Attempt {{ $postPdpc->attempt_no }}
                                        </p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center text-slate-600 whitespace-nowrap">
                                        {{ $postPdpc->observation_date
                                            ? \Carbon\Carbon::parse($postPdpc->observation_date)->format('d/m/Y')
                                            : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center font-extrabold text-slate-900 whitespace-nowrap">
                                        {{ $postPdpc->percentage !== null
                                            ? number_format($postPdpc->percentage, 2) . '%'
                                            : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($postPdpc->achievement_level)
                                        <span class="app-badge app-badge-info">
                                            {{ $postPdpc->achievement_level }}
                                        </span>
                                        @else
                                        <span class="text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($postPdpc->percentage !== null && (float) $postPdpc->percentage >= 85)
                                        <span class="app-badge app-badge-success">Pass</span>
                                        @elseif($postPdpc->percentage !== null && (float) $postPdpc->percentage < 85)
                                            <span class="app-badge app-badge-danger">Repeat</span>
                                            @else
                                            <span class="app-badge app-badge-info">Submitted</span>
                                            @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <a href="{{ route('new_teacher.result.pdpc', $postPdpc->responseID) }}"
                                            class="app-button-outline inline-flex items-center justify-center whitespace-nowrap">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @endif

                                {{-- POST Feedback --}}
                                @if($postFeedback)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg
                                                     bg-blue-50 border border-blue-200
                                                     text-blue-700 text-[11px] font-extrabold">
                                            POST
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-900">
                                            {{ $postFeedback->form?->form_name ?? '-' }}
                                        </p>
                                        @if(($postFeedback->attempt_no ?? 1) > 1)
                                        <p class="text-[11.5px] text-slate-500 mt-1">
                                            Attempt {{ $postFeedback->attempt_no }}
                                        </p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center text-slate-600 whitespace-nowrap">
                                        {{ $postFeedback->observation_date
                                            ? \Carbon\Carbon::parse($postFeedback->observation_date)->format('d/m/Y')
                                            : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center text-slate-400">-</td>
                                    <td class="px-5 py-4 text-center text-slate-400">-</td>

                                    <td class="px-5 py-4 text-center">
                                        <span class="app-badge app-badge-success">
                                            Submitted
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <a href="{{ route('new_teacher.result.post', $postFeedback->responseID) }}"
                                            class="app-button-outline inline-flex items-center justify-center whitespace-nowrap">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @endif

                                {{-- No Results --}}
                                @if(!$pre && !$postPdpc && !$postFeedback && !$externalPdpc && !$externalFeedback)
                                <tr>
                                    <td colspan="7" class="px-6 py-14 text-center">
                                        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100
                                                    flex items-center justify-center mb-3">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.7"
                                                stroke="currentColor"
                                                class="w-6 h-6 text-slate-400">
                                                <path stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375
                                                         h-1.5V5.25A2.25 2.25 0 0 0 12.375 3h-4.5
                                                         A2.25 2.25 0 0 0 5.625 5.25v13.5
                                                         A2.25 2.25 0 0 0 7.875 21h8.25
                                                         a3.375 3.375 0 0 0 3.375-3.375V14.25Z" />
                                            </svg>
                                        </div>
                                        <p class="font-bold text-slate-700">
                                            No evaluation results available
                                        </p>
                                        <p class="text-[12.5px] text-slate-400 mt-1">
                                            Submitted evaluation results will appear here.
                                        </p>
                                    </td>
                                </tr>
                                @endif

                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Previous External Results --}}
                @if($externalHistory->isNotEmpty())
                <div class="app-section-card mb-7">
                    <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100
                                        flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="w-[18px] h-[18px] text-blue-600">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0
                                             9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-[17px] font-extrabold text-slate-900">
                                    Previous External Results
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-blue-50 text-slate-600 uppercase text-[10.5px] tracking-[0.05em]">
                                    <th class="px-5 py-3.5 text-center font-extrabold">Attempt</th>
                                    <th class="px-5 py-3.5 text-left font-extrabold">Evaluation</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Date</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Score</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Achievement Level</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Status</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach($externalHistory as $history)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-4 text-center">
                                        <span class="app-badge app-badge-info">
                                            Attempt {{ $history->attempt_no }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-900">
                                            {{ $history->form?->form_name ?? '-' }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-center text-slate-600 whitespace-nowrap">
                                        {{ $history->observation_date
                                                ? \Carbon\Carbon::parse($history->observation_date)->format('d/m/Y')
                                                : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center font-extrabold text-slate-900">
                                        {{ $history->percentage !== null
                                                ? number_format($history->percentage, 2) . '%'
                                                : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($history->achievement_level)
                                        <span class="app-badge app-badge-info">
                                            {{ $history->achievement_level }}
                                        </span>
                                        @else
                                        <span class="text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($history->result === 'REPEAT')
                                        <span class="app-badge app-badge-danger">Repeat</span>
                                        @elseif($history->result === 'PASS')
                                        <span class="app-badge app-badge-success">Pass</span>
                                        @else
                                        <span class="app-badge app-badge-info">Submitted</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('new_teacher.result.pdpc', $history->responseID) }}"
                                                class="app-button-outline whitespace-nowrap">
                                                View PDPC
                                            </a>

                                            @if($history->feedback)
                                            <a href="{{ route('new_teacher.result.post', $history->feedback->responseID) }}"
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

                {{-- Previous POST Results --}}
                @if(isset($postHistory) && $postHistory->isNotEmpty())
                <div class="app-section-card mb-7">
                    <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100
                                        flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="w-[18px] h-[18px] text-blue-600">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0
                                             9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-[17px] font-extrabold text-slate-900">
                                    Previous POST Results
                                </h2>
                                <p class="text-[12.5px] text-slate-500 mt-0.5">
                                    Earlier POST observation attempts.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-blue-50 text-slate-600 uppercase text-[10.5px] tracking-[0.05em]">
                                    <th class="px-5 py-3.5 text-center font-extrabold">Attempt</th>
                                    <th class="px-5 py-3.5 text-left font-extrabold">Evaluation</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Date</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Score</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Achievement Level</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Status</th>
                                    <th class="px-5 py-3.5 text-center font-extrabold">Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach($postHistory as $history)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-4 text-center">
                                        <span class="app-badge app-badge-info">
                                            Attempt {{ $history->attempt_no }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-900">
                                            {{ $history->form?->form_name ?? '-' }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-center text-slate-600 whitespace-nowrap">
                                        {{ $history->observation_date
                                                ? \Carbon\Carbon::parse($history->observation_date)->format('d/m/Y')
                                                : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center font-extrabold text-slate-900">
                                        {{ $history->percentage !== null
                                                ? number_format($history->percentage, 2) . '%'
                                                : '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($history->achievement_level)
                                        <span class="app-badge app-badge-info">
                                            {{ $history->achievement_level }}
                                        </span>
                                        @else
                                        <span class="text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($history->percentage !== null && (float) $history->percentage >= 85)
                                        <span class="app-badge app-badge-success">Pass</span>
                                        @elseif($history->percentage !== null && (float) $history->percentage < 85)
                                            <span class="app-badge app-badge-danger">Repeat</span>
                                            @else
                                            <span class="app-badge app-badge-info">Submitted</span>
                                            @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('new_teacher.result.pdpc', $history->responseID) }}"
                                                class="app-button-outline whitespace-nowrap">
                                                View PDPC
                                            </a>

                                            @if($history->feedback)
                                            <a href="{{ route('new_teacher.result.post', $history->feedback->responseID) }}"
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

        </div>
    </div>
</x-app-layout>