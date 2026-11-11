<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-8xl mx-auto">

            {{-- Page heading --}}
            <div class="flex items-start gap-4 mb-7">

                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200
                            flex items-center justify-center shrink-0">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.7"
                        stroke="currentColor"
                        class="w-6 h-6 text-blue-600">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />

                    </svg>

                </div>

                <div>

                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Evaluation List
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Manage and monitor assigned teacher evaluations.
                    </p>

                </div>

            </div>


            {{-- Tabs --}}
            <div class="flex flex-wrap items-center gap-2 mb-5">

                <a
                    href="{{ route($listRoute, ['status' => 'active']) }}"
                    class="inline-flex items-center justify-center min-h-[38px] px-4 rounded-lg
                           text-[12.5px] font-bold border transition
                           {{
                               $status === 'active'
                                   ? 'bg-blue-50 border-blue-200 text-blue-600'
                                   : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700'
                           }}">

                    Latest Evaluation

                </a>


                <a
                    href="{{ route($listRoute, ['status' => 'completed']) }}"
                    class="inline-flex items-center justify-center min-h-[38px] px-4 rounded-lg
                           text-[12.5px] font-bold border transition
                           {{
                               $status === 'completed'
                                   ? 'bg-blue-50 border-blue-200 text-blue-600'
                                   : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700'
                           }}">

                    Completed Evaluation

                </a>


                @if(!$isObserver)

                <a
                    href="{{ route($listRoute, ['status' => 'repeat']) }}"
                    class="inline-flex items-center justify-center min-h-[38px] px-4 rounded-lg
                               text-[12.5px] font-bold border transition
                               {{
                                   $status === 'repeat'
                                       ? 'bg-red-50 border-red-200 text-red-600'
                                       : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700'
                               }}">

                    Repeat Evaluation

                </a>

                @endif

            </div>


            {{-- Evaluation list --}}
            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">

                        @if($status === 'completed')

                        Completed Evaluation List

                        @elseif(!$isObserver && $status === 'repeat')

                        Repeat Evaluation List

                        @else

                        Latest Evaluation List

                        @endif

                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Click Manage to access evaluation forms.
                    </p>

                </div>


                <div class="app-table-wrap">

                    <table class="app-table">

                        <thead>

                            <tr>

                                <th class="w-20">
                                    No
                                </th>

                                <th>
                                    Teacher
                                </th>

                                <th>
                                    School
                                </th>

                                <th class="text-center">
                                    Completion
                                </th>

                                <th class="text-center">
                                    Status
                                </th>

                                <th class="text-center w-32">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($assignments as $assignment)

                            @php
                            $assignmentType = strtolower(
                            (string) ($assignment->assignmentType ?? '')
                            );

                            $attemptNo = $assignment->attempt_no
                            ?? $assignment->attemptNo
                            ?? 1;
                            @endphp

                            <tr>

                                {{-- Number --}}
                                <td class="text-slate-500">
                                    {{ $assignments->firstItem() + $loop->index }}
                                </td>


                                {{-- Teacher --}}
                                <td>

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-full bg-blue-50 border border-blue-200
                                                        flex items-center justify-center shrink-0">

                                            <span class="text-[11px] font-extrabold text-blue-600">
                                                {{ strtoupper(substr($assignment->gn_name, 0, 1)) }}
                                            </span>

                                        </div>


                                        <div>

                                            <p class="font-bold text-slate-800 uppercase">
                                                {{ $assignment->gn_name }}
                                            </p>

                                            <p class="text-[11px] text-slate-400 mt-0.5">

                                                {{ strtoupper($assignmentType) }}

                                                @if(in_array($assignmentType, ['external', 'post']))

                                                - Attempt {{ $attemptNo }}

                                                @endif

                                            </p>

                                        </div>

                                    </div>

                                </td>


                                {{-- School --}}
                                <td class="text-slate-600">
                                    {{ $assignment->schoolName ?? '-' }}
                                </td>


                                {{-- Completion --}}
                                <td>

                                    @if($assignmentType === 'pre')

                                    {{-- PRE assignment: PRE only --}}
                                    <div class="flex items-center justify-center gap-5">

                                        <div class="flex flex-col items-center gap-1.5">

                                            <span
                                                title="Pre-Observation Form"
                                                class="w-2.5 h-2.5 rounded-full
                                                        {{
                                                            $assignment->pre_status === 'Completed'
                                                                ? 'bg-emerald-500'
                                                                : (
                                                                    $assignment->pre_status === 'Draft'
                                                                        ? 'bg-amber-400'
                                                                        : 'bg-slate-300'
                                                                )
                                                        }}">
                                            </span>

                                            <span class="text-[9px] font-bold text-slate-500 uppercase">
                                                PRE
                                            </span>

                                        </div>

                                    </div>


                                    @elseif($assignmentType === 'post')

                                    {{-- POST assignment: PDPC + Feedback --}}
                                    <div class="flex flex-col items-center gap-2">

                                        <span class="inline-flex px-2.5 py-1 rounded-full
                                                             bg-slate-50 border border-slate-200
                                                             text-slate-500 text-[9px] font-bold uppercase">

                                            Attempt {{ $attemptNo }}

                                        </span>

                                        <div class="flex items-center justify-center gap-6">

                                            {{-- POST PDPC --}}
                                            <div class="flex flex-col items-center gap-1.5">

                                                <span
                                                    title="Post-Observation PDPC Form"
                                                    class="w-2.5 h-2.5 rounded-full
                                                            {{
                                                                $assignment->pdpc_status === 'Completed'
                                                                    ? 'bg-emerald-500'
                                                                    : (
                                                                        $assignment->pdpc_status === 'Draft'
                                                                            ? 'bg-amber-400'
                                                                            : 'bg-slate-300'
                                                                    )
                                                            }}">
                                                </span>

                                                <span class="text-[9px] font-bold text-slate-500 uppercase">
                                                    PDPC
                                                </span>

                                            </div>


                                            {{-- POST Feedback --}}
                                            <div class="flex flex-col items-center gap-1.5">

                                                <span
                                                    title="Post-Observation Feedback Form"
                                                    class="w-2.5 h-2.5 rounded-full
                                                            {{
                                                                $assignment->feedback_status === 'Completed'
                                                                    ? 'bg-emerald-500'
                                                                    : (
                                                                        $assignment->feedback_status === 'Draft'
                                                                            ? 'bg-amber-400'
                                                                            : 'bg-slate-300'
                                                                    )
                                                            }}">
                                                </span>

                                                <span class="text-[9px] font-bold text-slate-500 uppercase">
                                                    FEEDBACK
                                                </span>

                                            </div>

                                        </div>

                                    </div>


                                    @elseif($assignmentType === 'external')

                                    {{-- EXTERNAL assignment: PDPC + Feedback --}}
                                    <div class="flex flex-col items-center gap-2">

                                        <span class="inline-flex px-2.5 py-1 rounded-full
                                                             bg-slate-50 border border-slate-200
                                                             text-slate-500 text-[9px] font-bold uppercase">

                                            Attempt {{ $attemptNo }}

                                        </span>

                                        <div class="flex items-center justify-center gap-6">

                                            {{-- External PDPC --}}
                                            <div class="flex flex-col items-center gap-1.5">

                                                <span
                                                    title="External PDPC Observation Form"
                                                    class="w-2.5 h-2.5 rounded-full
                                                    {{
                                                        $assignment->pdpc_status === 'Completed'
                                                            ? 'bg-emerald-500'
                                                            : ($assignment->pdpc_status === 'Draft'
                                                                ? 'bg-amber-400'
                                                                : 'bg-slate-300')
                                                    }}">
                                                </span>

                                                <span class="text-[9px] font-bold text-slate-500 uppercase">
                                                    PDPC
                                                </span>

                                            </div>


                                            {{-- External Feedback --}}
                                            <div class="flex flex-col items-center gap-1.5">

                                                <span
                                                    title="External Feedback Observation Form"
                                                    class="w-2.5 h-2.5 rounded-full
                                                            {{
                                                                $assignment->feedback_status === 'Completed'
                                                                    ? 'bg-emerald-500'
                                                                    : (
                                                                        $assignment->feedback_status === 'Draft'
                                                                            ? 'bg-amber-400'
                                                                            : 'bg-slate-300'
                                                                    )
                                                            }}">
                                                </span>

                                                <span class="text-[9px] font-bold text-slate-500 uppercase">
                                                    FEEDBACK
                                                </span>

                                            </div>

                                        </div>

                                    </div>

                                    @else

                                    <span class="text-slate-400 text-xs">
                                        -
                                    </span>

                                    @endif

                                </td>


                                {{-- Status --}}

                                <td class="text-center">

                                    @if(
                                    $assignmentType === 'external'
                                    && ($assignment->overall_completed ?? false)
                                    )

                                    <span class="app-badge app-badge-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Completed
                                    </span>


                                    @elseif(
                                    $assignmentType === 'external'
                                    && ($assignment->is_repeat ?? false)
                                    && $status === 'repeat'
                                    )

                                    <span class="app-badge app-badge-danger">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Repeat Required
                                    </span>

                                    @elseif($assignment->is_fully_completed)

                                    <span class="app-badge app-badge-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $assignment->completed_count }}/{{ $assignment->total_forms }}
                                        Completed
                                    </span>

                                    @elseif($assignment->has_draft)

                                    <span class="app-badge app-badge-warning">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        {{ $assignment->completed_count }}/{{ $assignment->total_forms }}
                                        Completed
                                    </span>

                                    @else

                                    <span class="app-badge app-badge-info">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        {{ $assignment->completed_count }}/{{ $assignment->total_forms }}
                                        Completed
                                    </span>

                                    @endif

                                </td>



                                {{-- Action --}}
                                <td class="text-center">

                                    <a
                                        href="{{ route(
                                                $assignmentType === 'external'
                                                    ? 'external.manage'
                                                    : 'observer.manage',
                                                $assignment->gn_id
                                            ) }}"
                                        class="app-button-outline">

                                        Manage

                                    </a>

                                </td>

                            </tr>


                            @empty

                            <tr>

                                <td colspan="6" class="!py-14 text-center">

                                    <div class="flex flex-col items-center">

                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100
                                                        flex items-center justify-center mb-3">

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.6"
                                                stroke="currentColor"
                                                class="w-6 h-6 text-slate-400">

                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M9 12h6m-6 4h6m2 5H7
                                                           a2 2 0 01-2-2V5
                                                           a2 2 0 012-2h5.586
                                                           a1 1 0 01.707.293
                                                           l5.414 5.414
                                                           a1 1 0 01.293.707V19
                                                           a2 2 0 01-2 2z" />

                                            </svg>

                                        </div>

                                        <h3 class="text-[13px] font-bold text-slate-700">
                                            No Evaluation Found
                                        </h3>

                                        <p class="text-[12px] text-slate-400 mt-1">

                                            @if(!$isObserver && $status === 'repeat')

                                            There are currently no repeat evaluations.

                                            @elseif($status === 'completed')

                                            There are currently no completed evaluations.

                                            @else

                                            There are currently no active evaluation assignments.

                                            @endif

                                        </p>

                                    </div>

                                </td>

                            </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if($assignments->hasPages())

                <div class="px-5 md:px-6 py-4 border-t border-slate-100 flex justify-end">

                    {{ $assignments->appends(request()->query())->links() }}

                </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>