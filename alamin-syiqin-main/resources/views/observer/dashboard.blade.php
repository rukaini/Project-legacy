<x-app-layout>

    <div class="app-page-shell">

        {{-- PAGE HEADER --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 mb-7">

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
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75a17.933 17.933 0 01-7.5-1.632z" />
                    </svg>
                </div>

                <div>
                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Dashboard
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Welcome back, {{ Auth::guard('teacher')->user()->teacherName }}.
                        Review assigned teachers and manage observation progress.
                    </p>
                </div>

            </div>

            <a href="{{ route('observer.list.evaluate') }}"
                class="app-button-primary">
                View Evaluations
            </a>

        </div>


        {{-- SUMMARY CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-7">

            {{-- Assigned Teachers --}}
            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400">
                            Assigned Teachers
                        </p>

                        <p class="text-[28px] font-extrabold text-slate-900 mt-2 leading-none">
                            {{ $totalAssigned }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Teachers assigned to you
                        </p>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="w-5 h-5 text-blue-600">

                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />

                        </svg>

                    </div>

                </div>

            </div>


            {{-- Ongoing --}}
            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400">
                            Ongoing
                        </p>

                        <p class="text-[28px] font-extrabold text-slate-900 mt-2 leading-none">
                            {{ $ongoingCount }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Observation in progress
                        </p>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.7"
                            class="w-5 h-5 text-sky-600">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                </div>

            </div>


            {{-- Draft --}}
            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400">
                            Draft
                        </p>

                        <p class="text-[28px] font-extrabold text-slate-900 mt-2 leading-none">
                            {{ $draftCount }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Saved draft observations
                        </p>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.7"
                            class="w-5 h-5 text-amber-600">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                    </div>

                </div>

            </div>


            {{-- Completed --}}
            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400">
                            Completed
                        </p>

                        <p class="text-[28px] font-extrabold text-slate-900 mt-2 leading-none">
                            {{ $completedCount }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Fully completed assigned observations
                        </p>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.7"
                            class="w-5 h-5 text-emerald-600">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75l2.25 2.25L15 9.75" />
                        </svg>
                    </div>

                </div>

            </div>

        </div>


        {{-- RECENT OBSERVATIONS --}}
        <div class="app-section-card mb-7">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                <div>
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Recent Observations
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Latest assigned teacher observation progress.
                    </p>
                </div>

                <a href="{{ route('observer.list.evaluate') }}"
                    class="app-button-outline">
                    View All
                </a>

            </div>


            <div class="app-table-wrap">

                <table class="app-table">

                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>School</th>
                            <th class="text-center">Completion</th>
                            <th class="text-center">Progress</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($recentEvaluations as $assignment)

                        <tr>

                            {{-- TEACHER --}}
                            <td>

                                <div class="flex items-center gap-3">

                                    <div class="w-9 h-9 rounded-full bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">

                                        <span class="text-[12px] font-bold text-blue-600">
                                            {{ strtoupper(substr($assignment->gn_name, 0, 1)) }}
                                        </span>

                                    </div>

                                    <div>
                                        <p class="font-bold text-slate-800">
                                            {{ $assignment->gn_name }}
                                        </p>
                                    </div>

                                </div>

                            </td>


                            {{-- SCHOOL --}}
                            <td>
                                {{ $assignment->schoolName ?? '-' }}
                            </td>


                            {{-- COMPLETION --}}
                            <td>

                                <div class="flex items-center justify-center gap-5">

                                    {{-- PRE - ALWAYS DISPLAY --}}
                                    <div class="flex flex-col items-center gap-1.5">

                                        <span class="w-2.5 h-2.5 rounded-full
                                                {{ $assignment->pre_status === 'Completed'
                                                    ? 'bg-emerald-500'
                                                    : ($assignment->pre_status === 'Draft'
                                                        ? 'bg-amber-400'
                                                        : 'bg-slate-300') }}">
                                        </span>

                                        <span class="text-[9px] font-bold tracking-wide text-slate-500">
                                            PRE
                                        </span>

                                    </div>


                                    {{-- POST COMPONENTS --}}
                                    @if($assignment->has_post_assignment)

                                    {{-- PDPC --}}
                                    <div class="flex flex-col items-center gap-1.5">

                                        <span class="w-2.5 h-2.5 rounded-full
                                                    {{ $assignment->pdpc_status === 'Completed'
                                                        ? 'bg-emerald-500'
                                                        : ($assignment->pdpc_status === 'Draft'
                                                            ? 'bg-amber-400'
                                                            : 'bg-slate-300') }}">
                                        </span>

                                        <span class="text-[9px] font-bold tracking-wide text-slate-500">
                                            PDPC
                                        </span>

                                    </div>


                                    {{-- FEEDBACK --}}
                                    <div class="flex flex-col items-center gap-1.5">

                                        <span class="w-2.5 h-2.5 rounded-full
                                                    {{ $assignment->feedback_status === 'Completed'
                                                        ? 'bg-emerald-500'
                                                        : ($assignment->feedback_status === 'Draft'
                                                            ? 'bg-amber-400'
                                                            : 'bg-slate-300') }}">
                                        </span>

                                        <span class="text-[9px] font-bold tracking-wide text-slate-500">
                                            FEEDBACK
                                        </span>

                                    </div>

                                    @endif

                                </div>

                            </td>


                            {{-- PROGRESS --}}
                            <td>

                                <div class="w-[150px] max-w-full mx-auto">

                                    <div class="flex items-center justify-between mb-1.5">

                                        <span class="text-[11px] font-semibold text-slate-500">
                                            {{ $assignment->completed_count }}/{{ $assignment->total_forms }}
                                        </span>

                                        <span class="text-[11px] font-bold text-slate-700">
                                            {{ $assignment->progress }}%
                                        </span>

                                    </div>

                                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-blue-600 rounded-full"
                                            @style([ 'width: ' . ($assignment->progress ?? 0) . '%'
                                            ])>
                                        </div>
                                    </div>

                                </div>

                            </td>


                            {{-- STATUS --}}
                            <td class="text-center">

                                @if($assignment->is_completed)

                                <span class="app-badge app-badge-success">

                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>

                                    Completed

                                </span>

                                @elseif($assignment->has_draft)

                                <span class="app-badge app-badge-warning">

                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>

                                    Draft

                                </span>

                                @else

                                <span class="app-badge app-badge-info">

                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>

                                    Ongoing

                                </span>

                                @endif

                            </td>


                            {{-- ACTION --}}
                            <td class="text-center">

                                <a href="{{ route('observer.manage', $assignment->gn_id) }}"
                                    class="app-button-outline">
                                    Manage
                                </a>

                            </td>

                        </tr>

                        @empty

                        <tr>
                            <td colspan="6" class="!py-14 text-center">

                                <p class="font-semibold text-slate-700">
                                    No evaluations found
                                </p>

                                <p class="text-[12px] text-slate-400 mt-1">
                                    No evaluations are currently available.
                                </p>

                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- PRE OBSERVATION RESULTS --}}
        <div class="app-section-card">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                <h2 class="text-[17px] font-extrabold text-slate-900">
                    Pre Observation Results
                </h2>

                <p class="text-[12.5px] text-slate-500 mt-1">
                    Review submitted PRE observation results.
                </p>

            </div>


            {{-- FILTERS --}}
            <div class="px-5 md:px-6 py-5 border-b border-slate-100 bg-slate-50/40">

                <form method="GET"
                    action="{{ route('observer.dashboard') }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-4 items-end">

                        {{-- TEACHER --}}
                        <div class="xl:col-span-4">

                            <label class="app-label">
                                Teacher
                            </label>

                            <input type="text"
                                name="pre_search"
                                value="{{ $preSearch }}"
                                placeholder="Search teacher..."
                                class="app-input">

                        </div>


                        {{-- MONTH --}}
                        <div class="xl:col-span-2">

                            <label class="app-label">
                                Month
                            </label>

                            <select name="pre_month"
                                class="app-select">

                                <option value="all">
                                    All Months
                                </option>

                                @foreach([
                                1 => 'January',
                                2 => 'February',
                                3 => 'March',
                                4 => 'April',
                                5 => 'May',
                                6 => 'June',
                                7 => 'July',
                                8 => 'August',
                                9 => 'September',
                                10 => 'October',
                                11 => 'November',
                                12 => 'December'
                                ] as $monthNumber => $monthName)

                                <option value="{{ $monthNumber }}"
                                    {{ (string) $preMonth === (string) $monthNumber ? 'selected' : '' }}>

                                    {{ $monthName }}

                                </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- YEAR --}}
                        <div class="xl:col-span-2">

                            <label class="app-label">
                                Year
                            </label>

                            <select name="pre_year"
                                class="app-select">

                                <option value="all">
                                    All Years
                                </option>

                                @foreach($preYears as $availableYear)

                                <option value="{{ $availableYear }}"
                                    {{ (string) $preYear === (string) $availableYear ? 'selected' : '' }}>

                                    {{ $availableYear }}

                                </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- ACHIEVEMENT LEVEL --}}
                        <div class="xl:col-span-2">

                            <label class="app-label">
                                Level
                            </label>

                            <select name="pre_level"
                                class="app-select">

                                <option value="all"
                                    {{ $preLevel === 'all' ? 'selected' : '' }}>
                                    All Levels
                                </option>

                                <option value="Weak"
                                    {{ $preLevel === 'Weak' ? 'selected' : '' }}>
                                    Weak
                                </option>

                                <option value="Satisfactory"
                                    {{ $preLevel === 'Satisfactory' ? 'selected' : '' }}>
                                    Satisfactory
                                </option>

                                <option value="Good"
                                    {{ $preLevel === 'Good' ? 'selected' : '' }}>
                                    Good
                                </option>

                                <option value="Very Good"
                                    {{ $preLevel === 'Very Good' ? 'selected' : '' }}>
                                    Very Good
                                </option>

                                <option value="Excellent"
                                    {{ $preLevel === 'Excellent' ? 'selected' : '' }}>
                                    Excellent
                                </option>

                            </select>

                        </div>


                        {{-- FILTER BUTTONS --}}
                        <div class="xl:col-span-2 flex gap-2">

                            <button type="submit"
                                class="app-button-primary flex-1">
                                Filter
                            </button>

                            <a href="{{ route('observer.dashboard') }}"
                                class="app-button-outline">
                                Reset
                            </a>

                        </div>

                    </div>

                </form>

            </div>


            {{-- PRE RESULTS TABLE --}}
            <div class="app-table-wrap">

                <table class="app-table">

                    <thead>

                        <tr>
                            <th class="text-center">No.</th>
                            <th class="text-center">Date</th>
                            <th>Teacher</th>
                            <th class="text-center">Class</th>
                            <th>Subject</th>
                            <th class="text-center">Score</th>
                            <th class="text-center">Achievement Level</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($preResults as $result)

                        <tr>

                            {{-- NUMBER --}}
                            <td class="text-center text-slate-500">

                                {{ $preResults->firstItem() + $loop->index }}

                            </td>


                            {{-- DATE --}}
                            <td class="text-center whitespace-nowrap">

                                {{ $result->observation_date
                                        ? \Carbon\Carbon::parse($result->observation_date)->format('d/m/Y')
                                        : '-' }}

                            </td>


                            {{-- TEACHER --}}
                            <td>

                                <div class="flex items-center gap-3">

                                    <div class="w-8 h-8 rounded-full bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">

                                        <span class="text-[11px] font-bold text-blue-600">
                                            {{ strtoupper(substr($result->gn_name, 0, 1)) }}
                                        </span>

                                    </div>

                                    <span class="font-bold text-slate-800">
                                        {{ $result->gn_name }}
                                    </span>

                                </div>

                            </td>


                            {{-- CLASS --}}
                            <td class="text-center">
                                {{ $result->class_name ?? '-' }}
                            </td>


                            {{-- SUBJECT --}}
                            <td>
                                {{ $result->subject_name ?? '-' }}
                            </td>


                            {{-- SCORE --}}
                            <td class="text-center">

                                <span class="font-extrabold text-slate-800">

                                    {{ $result->percentage !== null
                                            ? number_format($result->percentage, 2) . '%'
                                            : '-' }}

                                </span>

                            </td>


                            {{-- ACHIEVEMENT LEVEL --}}
                            <td class="text-center">

                                @if($result->achievement_level === 'Excellent')

                                <span class="app-badge app-badge-success">
                                    Excellent
                                </span>

                                @elseif($result->achievement_level === 'Very Good')

                                <span class="app-badge border-violet-200 bg-violet-50 text-violet-600">
                                    Very Good
                                </span>

                                @elseif($result->achievement_level === 'Good')

                                <span class="app-badge app-badge-info">
                                    Good
                                </span>

                                @elseif($result->achievement_level === 'Satisfactory')

                                <span class="app-badge app-badge-warning">
                                    Satisfactory
                                </span>

                                @elseif($result->achievement_level === 'Weak')

                                <span class="app-badge app-badge-danger">
                                    Weak
                                </span>

                                @else

                                <span class="text-slate-400">
                                    -
                                </span>

                                @endif

                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td colspan="7"
                                class="!py-14 text-center">

                                <p class="font-semibold text-slate-700">
                                    No PRE observation results found
                                </p>

                                <p class="text-[12px] text-slate-400 mt-1">
                                    Submitted PRE observations will appear here.
                                </p>

                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- PAGINATION --}}
            @if($preResults->hasPages())

            <div class="px-5 md:px-6 py-4 border-t border-slate-100">
                {{ $preResults->links() }}
            </div>

            @endif

        </div>

    </div>

</x-app-layout>