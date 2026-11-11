<x-app-layout>

    <div class="app-page-shell">

        {{-- Page heading --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 mb-7">

            <div class="flex items-start gap-4">

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
                            d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m9.5 0 .5 1.5M9 9l2.25 2.25L15 6.75" />

                    </svg>

                </div>

                <div>


                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Dashboard
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Welcome back, {{ $admin->staffName }}. Manage evaluation forms and review observation activity.
                    </p>

                </div>

            </div>


            <a
                href="{{ route('admin.manage.form') }}"
                class="app-button-primary shrink-0">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                    class="w-4 h-4">

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v12m6-6H6" />

                </svg>

                Manage Forms

            </a>

        </div>


        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-7">

            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400">
                            Total Forms
                        </p>

                        <p class="text-[28px] font-extrabold text-slate-900 mt-2 leading-none">
                            {{ $totalForms }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            All evaluation form versions
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100
                                flex items-center justify-center shrink-0">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                            class="w-5 h-5 text-blue-600">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m0 12.75h7.5m-7.5 3h4.5M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.625a9.375 9.375 0 00-9.375-9.375z" />

                        </svg>

                    </div>

                </div>

            </div>


            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400">
                            Active Forms
                        </p>

                        <p class="text-[28px] font-extrabold text-slate-900 mt-2 leading-none">
                            {{ $activeForms }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Forms currently available
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100
                                flex items-center justify-center shrink-0">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                            class="w-5 h-5 text-emerald-600">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75l2.25 2.25L15 9.75m6-3.75a11.952 11.952 0 01-3.219-.441 4.51 4.51 0 00-2.431.193 4.5 4.5 0 01-3.7 0 4.51 4.51 0 00-2.431-.193A11.952 11.952 0 016 6m15 0v9a9 9 0 11-18 0V6" />

                        </svg>

                    </div>

                </div>

            </div>


            <div class="app-card p-5">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400">
                            Observation Records
                        </p>

                        <p class="text-[28px] font-extrabold text-slate-900 mt-2 leading-none">
                            {{ $totalRecords }}
                        </p>

                        <p class="text-xs text-slate-500 mt-2">
                            Submitted observation activity
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-xl bg-violet-50 border border-violet-100
                                flex items-center justify-center shrink-0">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                            class="w-5 h-5 text-violet-600">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />

                        </svg>

                    </div>

                </div>

            </div>

        </div>


        {{-- Observation forms --}}
        <div class="app-section-card mb-7">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            Observation Forms
                        </h2>

                        <p class="text-[12.5px] text-slate-500 mt-1">
                            Manage the active versions used throughout the evaluation process.
                        </p>

                    </div>

                    <span class="app-badge app-badge-info">
                        {{ $totalForms }} total
                    </span>

                </div>

            </div>


            <div class="grid grid-cols-1 lg:grid-cols-3">

                {{-- PRE --}}
                <a
                    href="{{ route('admin.pre.form') }}"
                    class="group p-5 md:p-6 border-b lg:border-b-0 lg:border-r border-slate-100
                           hover:bg-slate-50/70 transition">

                    <div class="flex items-start justify-between gap-4">

                        <div class="flex items-start gap-3 min-w-0">

                            <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100
                                        flex items-center justify-center shrink-0">

                                <span class="text-[11px] font-extrabold text-blue-600">
                                    PRE
                                </span>

                            </div>

                            <div class="min-w-0">

                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.05em]">
                                    Pre Observation
                                </p>

                                <h3 class="text-[14px] font-bold text-slate-900 mt-1 truncate">
                                    {{ $activePreForm->form_name ?? 'Not Created Yet' }}
                                </h3>

                                <p class="text-[12px] text-slate-500 mt-1">
                                    {{ $preFormCount }}
                                    {{ $preFormCount === 1 ? 'version' : 'versions' }}
                                </p>

                            </div>

                        </div>

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="w-4 h-4 text-slate-300 group-hover:text-blue-600 transition shrink-0">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8.25 4.5l7.5 7.5-7.5 7.5" />

                        </svg>

                    </div>

                </a>


                {{-- PDPC --}}
                <a
                    href="{{ route('admin.pdpc.form') }}"
                    class="group p-5 md:p-6 border-b lg:border-b-0 lg:border-r border-slate-100
                           hover:bg-slate-50/70 transition">

                    <div class="flex items-start justify-between gap-4">

                        <div class="flex items-start gap-3 min-w-0">

                            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100
                                        flex items-center justify-center shrink-0">

                                <span class="text-[10px] font-extrabold text-indigo-600">
                                    PDPC
                                </span>

                            </div>

                            <div class="min-w-0">

                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.05em]">
                                    PDPC Observation
                                </p>

                                <h3 class="text-[14px] font-bold text-slate-900 mt-1 truncate">
                                    {{ $activePdpcForm->form_name ?? 'Not Created Yet' }}
                                </h3>

                                <p class="text-[12px] text-slate-500 mt-1">
                                    {{ $pdpcFormCount }}
                                    {{ $pdpcFormCount === 1 ? 'version' : 'versions' }}
                                </p>

                            </div>

                        </div>

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="w-4 h-4 text-slate-300 group-hover:text-blue-600 transition shrink-0">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8.25 4.5l7.5 7.5-7.5 7.5" />

                        </svg>

                    </div>

                </a>


                {{-- Feedback --}}
                <a
                    href="{{ route('admin.post.form') }}"
                    class="group p-5 md:p-6 hover:bg-slate-50/70 transition">

                    <div class="flex items-start justify-between gap-4">

                        <div class="flex items-start gap-3 min-w-0">

                            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100
                                        flex items-center justify-center shrink-0">

                                <span class="text-[10px] font-extrabold text-emerald-600">
                                    POST
                                </span>

                            </div>

                            <div class="min-w-0">

                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.05em]">
                                    Feedback
                                </p>

                                <h3 class="text-[14px] font-bold text-slate-900 mt-1 truncate">
                                    {{ $activePostForm->form_name ?? 'Not Created Yet' }}
                                </h3>

                                <p class="text-[12px] text-slate-500 mt-1">
                                    {{ $postFormCount }}
                                    {{ $postFormCount === 1 ? 'version' : 'versions' }}
                                </p>

                            </div>

                        </div>

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="w-4 h-4 text-slate-300 group-hover:text-blue-600 transition shrink-0">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8.25 4.5l7.5 7.5-7.5 7.5" />

                        </svg>

                    </div>

                </a>

            </div>

        </div>


        {{-- Audit --}}
        <div class="app-section-card">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100
                        flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                <div>

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Observation Audit
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Latest submitted observation records.
                    </p>

                </div>


                <a
                    href="{{ route('admin.audit.observation') }}"
                    class="app-button-outline">

                    View Audit

                </a>

            </div>


            <div class="app-table-wrap">

                <table class="app-table">

                    <thead>

                        <tr>

                            <th>No.</th>

                            <th>Date</th>

                            <th>Time</th>

                            <th>Observed By</th>

                            <th>Role</th>

                            <th>Teacher Observed</th>

                            <th>Form</th>

                            <th>Stage</th>

                            <th class="text-center">Action</th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($recentAudits as $audit)

                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>


                            <td class="whitespace-nowrap">

                                {{ $audit->audit_date
                                        ? \Carbon\Carbon::parse($audit->audit_date)->format('d/m/Y')
                                        : '-' }}

                            </td>


                            <td class="whitespace-nowrap">

                                {{ $audit->audit_time
                                        ? \Carbon\Carbon::parse($audit->audit_time)->format('h:i A')
                                        : '-' }}

                            </td>


                            <td>

                                <p class="font-bold text-slate-800 whitespace-nowrap">
                                    {{ $audit->teacherName ?? '-' }}
                                </p>

                            </td>


                            <td>

                                @if($audit->role === 'Observer')

                                <span class="app-badge app-badge-info">
                                    Observer
                                </span>

                                @elseif($audit->role === 'External Observer')

                                <span class="app-badge border-violet-200 bg-violet-50 text-violet-600">
                                    External Observer
                                </span>

                                @else

                                <span class="app-badge border-slate-200 bg-slate-50 text-slate-600">
                                    {{ $audit->role ?? '-' }}
                                </span>

                                @endif

                            </td>


                            <td>

                                <p class="font-bold text-slate-800 whitespace-nowrap">
                                    {{ $audit->gn_name ?? '-' }}
                                </p>

                            </td>


                            <td>
                                {{ $audit->form_name ?? '-' }}
                            </td>


                            <td>

                                @if($audit->stage === 'PRE')

                                <span class="app-badge app-badge-info">
                                    PRE
                                </span>

                                @elseif($audit->stage === 'POST')

                                <span class="app-badge app-badge-success">
                                    POST
                                </span>

                                @elseif($audit->stage === 'EXTERNAL')

                                <span class="app-badge app-badge-warning">
                                    EXTERNAL
                                </span>

                                @else

                                <span class="app-badge border-slate-200 bg-slate-50 text-slate-600">
                                    {{ $audit->stage ?? '-' }}
                                </span>

                                @endif

                            </td>


                            <td class="text-center">

                                @if($audit->action === 'Submitted')

                                <span class="app-badge app-badge-success">

                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>

                                    Submitted

                                </span>

                                @else

                                <span class="app-badge app-badge-info">

                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>

                                    {{ $audit->action ?? '-' }}

                                </span>

                                @endif

                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td
                                colspan="9"
                                class="text-center !py-14 text-slate-400">

                                No observation audit records found

                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-app-layout>