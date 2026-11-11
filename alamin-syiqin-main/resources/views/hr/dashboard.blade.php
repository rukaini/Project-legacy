<x-app-layout>

    <div class="app-page-shell">

        {{-- PAGE HEADING --}}
        <div class="flex flex-col lg:flex-row lg:items-center
                    lg:justify-between gap-5 mb-7">

            <div class="flex items-start gap-4">

                <div class="w-12 h-12 rounded-2xl bg-blue-50
                            border border-blue-200 flex items-center
                            justify-center shrink-0">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.7"
                        stroke="currentColor"
                        class="w-6 h-6 text-blue-600">

                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M18 18.72a9.094 9.094 0 003.741-.479
                               3 3 0 00-4.682-2.72m.94 3.198.001.031
                               c0 .225-.012.447-.037.666A11.944
                               11.944 0 0112 21c-2.17 0-4.203-.576
                               -5.963-1.584A6.062 6.062 0 016
                               18.719m12 0a5.971 5.971 0 00-.941
                               -3.197m0 0A5.995 5.995 0 0012
                               12.75a5.995 5.995 0 00-5.058
                               2.772m0 0a3 3 0 00-4.681 2.72
                               9.094 9.094 0 003.74.477m.94
                               -3.197A5.971 5.971 0 006
                               18.719m6-8.969a3 3 0 100-6
                               3 3 0 000 6zm6 3a2.25 2.25
                               0 100-4.5 2.25 2.25 0 000
                               4.5zm-12 0a2.25 2.25 0
                               100-4.5 2.25 2.25 0 000 4.5z" />
                    </svg>

                </div>

                <div>

                    <h1 class="text-[26px] md:text-[30px]
                               font-extrabold tracking-tight
                               text-slate-900">
                        Dashboard
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Welcome back,
                        {{ Auth::guard('hr')->user()->username }}.
                        Manage new teacher accounts and their current status.
                    </p>

                </div>

            </div>

            {{-- ACTION BUTTONS --}}
            <div class="flex flex-wrap gap-2">

                <a href="{{ route('hr.gurunew.create') }}"
                    class="app-button-primary">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                        class="w-4 h-4">

                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M18 7.5v3m0 0v3m0-3h3m-3
                               0h-3M6.75 6.75a3 3 0
                               116 0 3 3 0 01-6
                               0zM3 20.25a6.75 6.75
                               0 0113.5 0v.75H3v-.75z" />
                    </svg>

                    Register Teacher

                </a>

                <a href="{{ route('hr.gurunew.index') }}"
                    class="app-button-outline">
                    Manage Teachers
                </a>

            </div>

        </div>

        {{-- SUMMARY CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2
                    xl:grid-cols-4 gap-4 mb-7">

            {{-- TOTAL TEACHERS --}}
            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-[11px] font-bold uppercase
                                  tracking-[0.06em] text-slate-400">
                            Total Teachers
                        </p>

                        <p class="text-[28px] font-extrabold
                                  text-slate-900 mt-2 leading-none">
                            {{ $totalTeachers }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Registered new teachers
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-xl bg-blue-50
                                border border-blue-100 flex items-center
                                justify-center shrink-0">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 text-blue-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 21v-2a4 4 0 00-4-4H6a4
                                   4 0 00-4 4v2m18 0v-2a4
                                   4 0 00-3-3.87M9 11a4 4
                                   0 100-8 4 4 0 000 8zm8
                                   0a4 4 0 00-3-3.87" />
                        </svg>

                    </div>

                </div>

            </div>

            {{-- ACTIVE --}}
            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-[11px] font-bold uppercase
                                  tracking-[0.06em] text-slate-400">
                            Active
                        </p>

                        <p class="text-[28px] font-extrabold
                                  text-slate-900 mt-2 leading-none">
                            {{ $activeTeachers }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Currently active
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-xl bg-emerald-50
                                border border-emerald-100 flex items-center
                                justify-center shrink-0">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                            class="w-5 h-5 text-emerald-600">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75l2.25 2.25L15 9.75" />
                        </svg>

                    </div>

                </div>

            </div>

            {{-- INACTIVE --}}
            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-[11px] font-bold uppercase
                                  tracking-[0.06em] text-slate-400">
                            Inactive
                        </p>

                        <p class="text-[28px] font-extrabold
                                  text-slate-900 mt-2 leading-none">
                            {{ $inactiveTeachers }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Not currently active
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-xl bg-amber-50
                                border border-amber-100 flex items-center
                                justify-center shrink-0">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                            class="w-5 h-5 text-amber-600">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m9.303 3.376c.866
                                   1.5-.217 3.374-1.948
                                   3.374H4.645c-1.73 0-2.813
                                   -1.874-1.948-3.374L10.052
                                   3.38c.866-1.5 3.03-1.5
                                   3.896 0l7.355 12.746z" />
                        </svg>

                    </div>

                </div>

            </div>

            {{-- COMPLETE --}}
            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-[11px] font-bold uppercase
                                  tracking-[0.06em] text-slate-400">
                            Complete
                        </p>

                        <p class="text-[28px] font-extrabold
                                  text-slate-900 mt-2 leading-none">
                            {{ $completeTeachers }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Marked complete by HR
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-xl bg-indigo-50
                                border border-indigo-100 flex items-center
                                justify-center shrink-0">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                            class="w-5 h-5 text-indigo-600">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75l2.25 2.25L15
                                   9.75m5.25-4.5l-9 9-4.5-4.5" />
                        </svg>

                    </div>

                </div>

            </div>

        </div>

        {{-- COMPLETED OBSERVATION AND TRAINING LIST --}}
        <div class="app-section-card mb-7">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100
                        flex flex-col md:flex-row md:items-center
                        md:justify-between gap-4">

                <div>

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Completed Observation & Training List
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        New teachers who have completed all observation,
                        training and feedback requirements.
                    </p>

                </div>

                <a href="{{ route('hr.teacher.progress') }}"
                    class="app-button-outline">
                    View All
                </a>

            </div>

            <div class="app-table-wrap">

                <table class="app-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Teacher</th>
                            <th>School</th>
                            <th class="text-center">
                                Observation Completion
                            </th>
                            <th class="text-center">
                                Training Completion
                            </th>
                            <th class="text-center">
                                Feedback Completion
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($completedTeachers as $teacher)

                        <tr>

                            {{-- NO --}}
                            <td>
                                {{ $loop->iteration }}
                            </td>

                            {{-- TEACHER --}}
                            <td>

                                <div class="flex items-center gap-3">

                                    <div class="w-9 h-9 rounded-full
                                                    bg-blue-50 border
                                                    border-blue-200
                                                    flex items-center
                                                    justify-center shrink-0">

                                        <span class="text-[12px]
                                                         font-bold text-blue-600">
                                            {{ strtoupper(substr($teacher->gn_name, 0, 1)) }}
                                        </span>

                                    </div>

                                    <div class="min-w-0">

                                        <p class="font-bold text-slate-800">
                                            {{ $teacher->gn_name }}
                                        </p>

                                        <p class="text-[11px]
                                                      text-slate-400 mt-0.5">
                                            {{ $teacher->gn_id }}
                                        </p>

                                    </div>

                                </div>

                            </td>

                            {{-- SCHOOL --}}
                            <td>
                                {{ $teacher->school?->schoolName ?? '-' }}
                            </td>

                            {{-- OBSERVATION --}}
                            <td class="text-center align-middle">

                                <span class="text-xs font-bold
                                                 text-emerald-700">

                                    {{ $teacher->observation_count }}/{{ $teacher->observation_total }}
                                    Completed

                                </span>

                            </td>

                            {{-- TRAINING --}}
                            <td class="text-center align-middle">

                                <span class="text-xs font-bold
                                                 text-emerald-700">

                                    {{ $teacher->completed_training }}/{{ $teacher->total_training }}
                                    Completed

                                </span>

                            </td>

                            {{-- FEEDBACK --}}
                            <td class="text-center align-middle">

                                <span class="text-xs font-bold
                                                 text-emerald-700">

                                    {{ $teacher->completed_feedback }}/{{ $teacher->total_feedback }}
                                    Completed

                                </span>

                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td colspan="6"
                                class="text-center !py-14 text-slate-400">

                                No teachers have completed all observation,
                                training and feedback requirements yet.

                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        {{-- LATEST NEW TEACHERS --}}
        <div class="app-section-card">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100
                        flex flex-col md:flex-row md:items-center
                        md:justify-between gap-4">

                <div>

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Latest New Teachers
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Most recently registered teacher accounts.
                    </p>

                </div>

                <a href="{{ route('hr.gurunew.index') }}"
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
                            <th>Appointed Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($recentTeachers as $guru)

                        <tr>

                            {{-- TEACHER --}}
                            <td>

                                <div class="flex items-center gap-3">

                                    <div class="w-9 h-9 rounded-full
                                                    bg-blue-50 border
                                                    border-blue-200
                                                    flex items-center
                                                    justify-center shrink-0">

                                        <span class="text-[12px]
                                                         font-bold text-blue-600">
                                            {{ strtoupper(substr($guru->gn_name, 0, 1)) }}
                                        </span>

                                    </div>

                                    <div class="min-w-0">

                                        <p class="font-bold text-slate-800">
                                            {{ $guru->gn_name }}
                                        </p>

                                        <p class="text-[11px]
                                                      text-slate-400 mt-0.5">
                                            New Teacher
                                        </p>

                                    </div>

                                </div>

                            </td>

                            {{-- SCHOOL --}}
                            <td>
                                {{ $guru->school?->schoolName ?? '-' }}
                            </td>

                            {{-- APPOINTED DATE --}}
                            <td class="whitespace-nowrap">

                                @if($guru->appointed_date)

                                {{ \Carbon\Carbon::parse($guru->appointed_date)->format('d M Y') }}

                                @else

                                -

                                @endif

                            </td>

                            {{-- STATUS --}}
                            <td>

                                @if($guru->current_status === 'Active')

                                <span class="app-badge app-badge-success">

                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>

                                    Active

                                </span>

                                @elseif($guru->current_status === 'Complete')

                                <span class="app-badge app-badge-info">

                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>

                                    Complete

                                </span>

                                @else

                                <span class="app-badge app-badge-danger">

                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>

                                    Inactive

                                </span>

                                @endif

                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td colspan="4"
                                class="text-center !py-14 text-slate-400">

                                No new teachers found

                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-app-layout>