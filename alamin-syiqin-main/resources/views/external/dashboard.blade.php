<x-app-layout>
    <div class="app-page-shell">

        {{-- Page heading --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 mb-7">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24"
                        stroke-width="1.7" stroke="currentColor"
                        class="w-6 h-6 text-blue-600">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75a17.933 17.933 0 01-7.5-1.632z" />
                    </svg>
                </div>

                <div>
                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Dashboard
                    </h1>
                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Welcome back, {{ Auth::guard('teacher')->user()->teacherName }}.
                        Monitor assigned external observations and evaluation results.
                    </p>
                </div>
            </div>

            <a href="{{ route('external.list.evaluate') }}"
                class="app-button-primary">
                View Evaluations
            </a>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-7">

            {{-- Assigned teachers --}}
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
                            Evaluation in progress
                        </p>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7"
                            class="w-5 h-5 text-sky-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Repeat required --}}
            <div class="app-card p-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400">
                            Repeat Required
                        </p>
                        <p class="text-[28px] font-extrabold text-slate-900 mt-2 leading-none">
                            {{ $repeatCount }}
                        </p>
                        <p class="text-xs text-slate-500 mt-2">
                            Requires another attempt
                        </p>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7"
                            class="w-5 h-5 text-red-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v6h6M20 20v-6h-6M5.6 15a7 7 0 0012.4 2M18.4 9A7 7 0 006 7" />
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
                            Passed and completed
                        </p>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7"
                            class="w-5 h-5 text-emerald-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 12l4 4L19 6" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent evaluations --}}
        <div class="app-section-card mb-7">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Recent Evaluations
                    </h2>
                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Latest assigned external observation progress.
                    </p>
                </div>

                <a href="{{ route('external.list.evaluate') }}"
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
                            <th class="text-center">External Result</th>
                            <th class="text-center">Overall Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentEvaluations as $assignment)

                        @php
                        $externalResult = strtoupper(
                        (string) ($assignment->external_result ?? '')
                        );

                        $overallStatus = $assignment->overall_status
                        ?? 'In Progress';

                        $passedAt = $assignment->passed_at ?? null;

                        $pdpcStatus = $assignment->pdpc_status
                        ?? 'Pending';

                        $feedbackStatus = $assignment->feedback_status
                        ?? 'Pending';

                        $completedForms = (int) (
                        $assignment->completed_count ?? 0
                        );

                        $totalForms = (int) (
                        $assignment->total_forms ?? 2
                        );

                        $progress = max(
                        0,
                        min(100, (int) ($assignment->progress ?? 0))
                        );

                        $progressStyle = 'width: ' . $progress . '%;';
                        @endphp

                        <tr>
                            {{-- Teacher --}}
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

                            {{-- School --}}
                            <td>
                                {{ $assignment->schoolName ?? '-' }}
                            </td>

                            {{-- Completion dots --}}
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-5">

                                    <div class="flex flex-col items-center gap-1">
                                        <span class="w-2.5 h-2.5 rounded-full
                                                @if($pdpcStatus === 'Completed')
                                                    bg-emerald-500
                                                @elseif($pdpcStatus === 'Draft')
                                                    bg-amber-400
                                                @else
                                                    bg-slate-300
                                                @endif">
                                        </span>

                                        <span class="text-[9px] font-bold uppercase text-slate-500">
                                            PDPC
                                        </span>
                                    </div>

                                    <div class="flex flex-col items-center gap-1">
                                        <span class="w-2.5 h-2.5 rounded-full
                                                @if($feedbackStatus === 'Completed')
                                                    bg-emerald-500
                                                @elseif($feedbackStatus === 'Draft')
                                                    bg-amber-400
                                                @else
                                                    bg-slate-300
                                                @endif">
                                        </span>

                                        <span class="text-[9px] font-bold uppercase text-slate-500">
                                            Feedback
                                        </span>
                                    </div>

                                </div>
                            </td>

                            {{-- Progress --}}
                            <td>
                                <div class="w-[150px] max-w-full mx-auto">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-[11px] font-semibold text-slate-500">
                                            {{ $completedForms }}/{{ $totalForms }}
                                        </span>

                                        <span class="text-[11px] font-bold text-slate-700">
                                            {{ $progress }}%
                                        </span>
                                    </div>


                                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-blue-600 rounded-full"
                                            @style(['width: ' . $progress . ' %'])></div>
                                    </div>

                                </div>
                            </td>

                            {{-- Actual external result --}}
                            <td class="text-center">
                                @if($externalResult === 'PASS')
                                <span class="app-badge app-badge-success">
                                    PASS
                                </span>
                                @elseif($externalResult === 'REPEAT')
                                <span class="app-badge border border-red-200 bg-red-50 text-red-600">
                                    REPEAT
                                </span>
                                @elseif($assignment->has_draft ?? false)
                                <span class="app-badge app-badge-warning">
                                    Draft
                                </span>
                                @else
                                <span class="app-badge border border-slate-200 bg-slate-50 text-slate-500">
                                    Pending
                                </span>
                                @endif
                            </td>

                            {{-- Overall evaluation status --}}
                            <td class="text-center">
                                @if($overallStatus === 'Completed')
                                <span class="app-badge app-badge-success">
                                    Completed
                                </span>

                                @if($passedAt)
                                <p class="mt-1 text-[11px] font-medium text-slate-500">
                                    Passed at {{ $passedAt }}
                                </p>
                                @endif

                                @elseif($overallStatus === 'Repeat Required')
                                <span class="app-badge border border-red-200 bg-red-50 text-red-600">
                                    Repeat Required
                                </span>

                                @elseif($overallStatus === 'Pending')
                                <span class="app-badge border border-slate-200 bg-slate-50 text-slate-500">
                                    Pending
                                </span>

                                @else
                                <span class="app-badge app-badge-info">
                                    In Progress
                                </span>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td class="text-center">
                                <a href="{{ route('external.manage', $assignment->gn_id) }}"
                                    class="app-button-outline">
                                    Manage
                                </a>
                            </td>
                        </tr>

                        @empty
                        <tr>
                            <td colspan="7" class="!py-14 text-center">
                                <p class="font-semibold text-slate-700">
                                    No evaluations found
                                </p>

                                <p class="text-[12px] text-slate-400 mt-1">
                                    No assigned external evaluations are currently available.
                                </p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- External PDPC results --}}
        <div class="app-section-card">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                <h2 class="text-[17px] font-extrabold text-slate-900">
                    External PDPC Results
                </h2>

                <p class="text-[12.5px] text-slate-500 mt-1">
                    Review your submitted external observation results.
                </p>
            </div>

            {{-- Result filters --}}
            <div class="px-5 md:px-6 py-5 border-b border-slate-100 bg-slate-50/40">
                <form method="GET" action="{{ route('external.dashboard') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-4 items-end">

                        {{-- Teacher search --}}
                        <div class="xl:col-span-4">
                            <label class="app-label">
                                Teacher
                            </label>

                            <input type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Search teacher..."
                                class="app-input">
                        </div>

                        {{-- Month filter --}}
                        <div class="xl:col-span-2">
                            <label class="app-label">
                                Month
                            </label>

                            <select name="month" class="app-select">
                                <option value="all" @selected($month==='all' )>
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
                                    @selected((string) $month===(string) $monthNumber)>
                                    {{ $monthName }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Year filter --}}
                        <div class="xl:col-span-2">
                            <label class="app-label">
                                Year
                            </label>

                            <select name="year" class="app-select">
                                <option value="all" @selected($year==='all' )>
                                    All Years
                                </option>

                                @foreach($years as $availableYear)
                                <option value="{{ $availableYear }}"
                                    @selected((string) $year===(string) $availableYear)>
                                    {{ $availableYear }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Achievement level filter --}}
                        <div class="xl:col-span-2">
                            <label class="app-label">
                                Level
                            </label>

                            <select name="level" class="app-select">
                                <option value="all" @selected($level==='all' )>
                                    All Levels
                                </option>

                                @foreach([
                                'Weak',
                                'Satisfactory',
                                'Good',
                                'Very Good',
                                'Excellent'
                                ] as $achievementLevel)
                                <option value="{{ $achievementLevel }}"
                                    @selected($level===$achievementLevel)>
                                    {{ $achievementLevel }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filter actions --}}
                        <div class="xl:col-span-2 flex items-center gap-2">
                            <button type="submit"
                                class="app-button-primary flex-1 justify-center">
                                Filter
                            </button>

                            <a href="{{ route('external.dashboard') }}"
                                class="app-button-outline">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Submitted PDPC results table --}}
            <div class="app-table-wrap">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th>Teacher</th>
                            <th class="text-center">Attempt</th>
                            <th class="text-center">Date</th>
                            <th>Subject</th>
                            <th class="text-center">Percentage</th>
                            <th class="text-center">Level</th>
                            <th class="text-center">Result</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($pdpcResults as $result)
                        <tr>
                            {{-- Row number --}}
                            <td class="text-center">
                                {{ $pdpcResults->firstItem() + $loop->index }}
                            </td>

                            {{-- Teacher --}}
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                                        <span class="text-[11px] font-bold text-blue-600">
                                            {{ strtoupper(substr($result->gn_name, 0, 1)) }}
                                        </span>
                                    </div>

                                    <span class="font-semibold text-slate-800">
                                        {{ $result->gn_name }}
                                    </span>
                                </div>
                            </td>

                            {{-- Attempt --}}
                            <td class="text-center">
                                <span class="app-badge border border-slate-300 bg-white text-slate-600">
                                    Attempt {{ $result->attempt_no }}
                                </span>
                            </td>

                            {{-- Date --}}
                            <td class="text-center">
                                {{ $result->observation_date
                                        ? \Carbon\Carbon::parse($result->observation_date)->format('d/m/Y')
                                        : '-' }}
                            </td>

                            {{-- Subject --}}
                            <td>
                                {{ $result->subject_name ?? '-' }}
                            </td>

                            {{-- Percentage --}}
                            <td class="text-center">
                                <span class="font-bold text-slate-800">
                                    {{ $result->percentage !== null
                                            ? number_format((float) $result->percentage, 2) . '%'
                                            : '-' }}
                                </span>
                            </td>

                            {{-- Achievement level --}}
                            <td class="text-center">
                                @php
                                $achievement = $result->achievement_level ?? '';
                                @endphp

                                @if($achievement === 'Excellent')
                                <span class="app-badge app-badge-success">
                                    Excellent
                                </span>
                                @elseif($achievement === 'Very Good')
                                <span class="app-badge app-badge-success">
                                    Very Good
                                </span>
                                @elseif($achievement === 'Good')
                                <span class="app-badge app-badge-info">
                                    Good
                                </span>
                                @elseif($achievement === 'Satisfactory')
                                <span class="app-badge app-badge-warning">
                                    Satisfactory
                                </span>
                                @elseif($achievement === 'Weak')
                                <span class="app-badge border border-red-200 bg-red-50 text-red-600">
                                    Weak
                                </span>
                                @else
                                <span class="text-slate-400">
                                    {{ $achievement ?: '-' }}
                                </span>
                                @endif
                            </td>

                            {{-- Original external result --}}
                            <td class="text-center">
                                @php
                                $storedResult = strtoupper(
                                (string) ($result->result ?? '')
                                );
                                @endphp

                                @if($storedResult === 'PASS')
                                <span class="app-badge app-badge-success">
                                    PASS
                                </span>
                                @elseif($storedResult === 'REPEAT')
                                <span class="app-badge border border-red-200 bg-red-50 text-red-600">
                                    REPEAT
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
                            <td colspan="8" class="!py-14 text-center">
                                <p class="font-semibold text-slate-700">
                                    No PDPC results found
                                </p>

                                <p class="text-[12px] text-slate-400 mt-1">
                                    No submitted external PDPC results match the selected filters.
                                </p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($pdpcResults->hasPages())
            <div class="px-5 md:px-6 py-4 border-t border-slate-100">
                {{ $pdpcResults->links() }}
            </div>
            @endif

        </div>
    </div>
</x-app-layout>