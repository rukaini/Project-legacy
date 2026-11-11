<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-8xl mx-auto"
            x-data="{
                 activeTab: new URLSearchParams(window.location.search)
                     .get('tab') === 'completed' ? 'completed' : 'latest'
             }">

            {{-- PAGE HEADER --}}
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
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>

                    </div>

                    <div>
                        <h1 class="text-[26px] md:text-[30px]
                                   font-extrabold tracking-tight text-slate-900">
                            Evaluation Results
                        </h1>

                        <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                            Monitor the evaluation progress of new teachers.
                        </p>
                    </div>

                </div>

                <div class="flex flex-wrap gap-3">

                    <div class="bg-white border border-slate-200
                                rounded-2xl px-5 py-3.5 min-w-[145px] shadow-sm">

                        <p class="text-[10px] font-bold uppercase
                                  tracking-[0.07em] text-slate-400">
                            Active Teachers
                        </p>

                        <p class="text-[24px] font-extrabold
                                  text-slate-900 mt-0.5">
                            {{ $totalTeachers }}
                        </p>

                    </div>

                    <div class="bg-white border border-slate-200
                                rounded-2xl px-5 py-3.5 min-w-[145px] shadow-sm">

                        <p class="text-[10px] font-bold uppercase
                                  tracking-[0.07em] text-slate-400">
                            Completed Teachers
                        </p>

                        <p class="text-[24px] font-extrabold
                                  text-emerald-600 mt-0.5">
                            {{ $totalCompleted }}
                        </p>

                    </div>

                </div>

            </div>


            {{-- TAB BUTTONS --}}
            <div class="flex flex-wrap items-center gap-2 mb-6">

                <button type="button"
                    @click="activeTab = 'latest'"
                    :class="activeTab === 'latest'
                            ? 'bg-blue-50 border-blue-200 text-blue-600'
                            : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50'"
                    class="px-5 py-3 rounded-xl border
                               text-[13px] font-bold transition">

                    Latest Evaluation

                </button>

                <button type="button"
                    @click="activeTab = 'completed'"
                    :class="activeTab === 'completed'
                            ? 'bg-blue-50 border-blue-200 text-blue-600'
                            : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50'"
                    class="px-5 py-3 rounded-xl border
                               text-[13px] font-bold transition">

                    Completed Evaluation

                </button>

            </div>


            {{-- SEARCH AND FILTER --}}
            <div class="app-section-card mb-6">

                <div class="px-5 md:px-6 py-5">

                    <div class="flex flex-col xl:flex-row
                                xl:items-end xl:justify-between gap-5">

                        <div>

                        </div>

                        <form method="GET"
                            action="{{ route('principal.result') }}"
                            class="flex flex-col sm:flex-row
                                     sm:items-center gap-2">

                            <input type="hidden"
                                name="tab"
                                :value="activeTab">

                            <div class="relative w-full sm:w-[250px]">

                                <svg class="absolute left-3 top-1/2
                                            -translate-y-1/2 w-4 h-4
                                            text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">

                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                                </svg>

                                <input type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    placeholder="Search teacher..."
                                    class="app-input !pl-9">

                            </div>

                            <div x-show="activeTab === 'latest'"
                                x-cloak>

                                <select name="status"
                                    :disabled="activeTab !== 'latest'"
                                    class="app-select sm:!w-[170px]">

                                    <option value="all"
                                        {{ $status === 'all' ? 'selected' : '' }}>
                                        All Status
                                    </option>

                                    <option value="Pending"
                                        {{ $status === 'Pending' ? 'selected' : '' }}>
                                        Pending
                                    </option>

                                    <option value="In Progress"
                                        {{ $status === 'In Progress' ? 'selected' : '' }}>
                                        In Progress
                                    </option>

                                    <option value="Repeat Required"
                                        {{ $status === 'Repeat Required' ? 'selected' : '' }}>
                                        Repeat Required
                                    </option>

                                </select>

                            </div>

                            <button type="submit"
                                class="app-button-primary">
                                Search
                            </button>

                            <a href="{{ route('principal.result') }}"
                                class="app-button-outline">
                                Clear
                            </a>

                        </form>

                    </div>

                </div>

            </div>


            {{-- LATEST EVALUATION --}}
            <div x-show="activeTab === 'latest'"
                x-cloak>

                <div class="app-section-card">

                    <div class="px-5 md:px-6 py-5
                                border-b border-slate-100">

                        <div class="flex flex-wrap items-center
                                    justify-between gap-3">

                            <div>
                                <h2 class="text-[17px] font-extrabold text-slate-900">
                                    Latest Evaluation List
                                </h2>

                                <p class="text-[12.5px] text-slate-500 mt-1">
                                    Teachers with pending, ongoing or repeat-required evaluations.
                                </p>
                            </div>

                            <span class="app-badge app-badge-info">
                                {{ $totalOngoing }} Teachers
                            </span>

                        </div>

                    </div>

                    <div class="app-table-wrap">

                        <table class="app-table">

                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Teacher Name</th>
                                    <th>Pre-Observation</th>
                                    <th>External Observation</th>
                                    <th>Post-Observation</th>
                                    <th class="!text-center">Overall Status</th>
                                    <th class="!text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($ongoingTeachers as $teacher)

                                <tr>

                                    <td>
                                        {{ $ongoingTeachers->firstItem() + $loop->index }}
                                    </td>

                                    <td>

                                        <div class="flex items-center gap-3">

                                            <div class="w-9 h-9 rounded-xl
                                                            bg-blue-50 border border-blue-200
                                                            flex items-center justify-center
                                                            shrink-0">

                                                <span class="text-[12px]
                                                                 font-extrabold text-blue-600">
                                                    {{ strtoupper(substr($teacher->gn_name, 0, 1)) }}
                                                </span>

                                            </div>

                                            <p class="font-bold text-slate-800
                                                          whitespace-nowrap">
                                                {{ $teacher->gn_name }}
                                            </p>

                                        </div>

                                    </td>

                                    {{-- PRE --}}
                                    <td>

                                        <div class="w-28">

                                            <div class="flex items-center justify-between mb-2">

                                                <span class="text-[12px] font-bold text-slate-700">
                                                    {{ $teacher->pre_completed }}/{{ $teacher->pre_total }}
                                                </span>

                                                <span class="text-[10px] font-semibold text-slate-400">
                                                    {{ $teacher->pre_progress }}%
                                                </span>

                                            </div>

                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">

                                                <div class="h-full bg-blue-600 rounded-full"
                                                    :style="{ width: '{{ $teacher->pre_progress }}%' }">
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    {{-- EXTERNAL --}}
                                    <td>

                                        <div class="w-28">

                                            <div class="flex items-center justify-between mb-2">

                                                <span class="text-[12px] font-bold text-slate-700">
                                                    {{ $teacher->external_completed }}/{{ $teacher->external_total }}
                                                </span>

                                                <span class="text-[10px] font-semibold text-slate-400">
                                                    {{ $teacher->external_progress }}%
                                                </span>

                                            </div>

                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">

                                                <div class="h-full bg-blue-600 rounded-full"
                                                    :style="{ width: '{{ $teacher->external_progress }}%' }">
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    {{-- POST --}}
                                    <td>

                                        <div class="w-28">

                                            <div class="flex items-center justify-between mb-2">

                                                <span class="text-[12px] font-bold text-slate-700">
                                                    {{ $teacher->post_completed }}/{{ $teacher->post_total }}
                                                </span>

                                                <span class="text-[10px] font-semibold text-slate-400">
                                                    {{ $teacher->post_progress }}%
                                                </span>

                                            </div>

                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">

                                                <div class="h-full bg-blue-600 rounded-full"
                                                    :style="{ width: '{{ $teacher->post_progress }}%' }">
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    {{-- STATUS --}}
                                    <td class="!text-center">

                                        @if($teacher->evaluation_status === 'Repeat Required')

                                        <span class="app-badge app-badge-danger">
                                            Repeat Required
                                        </span>

                                        @elseif($teacher->evaluation_status === 'In Progress')

                                        <span class="app-badge app-badge-info">
                                            In Progress
                                        </span>

                                        @else

                                        <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                                            Pending
                                        </span>

                                        @endif

                                    </td>

                                    {{-- ACTION --}}
                                    <td class="!text-center">

                                        <a href="{{ route('principal.result.show', $teacher->gn_id) }}"
                                            class="app-button-outline">
                                            View
                                        </a>

                                    </td>

                                </tr>

                                @empty

                                <tr>
                                    <td colspan="7"
                                        class="!text-center !py-12 text-slate-400">
                                        No ongoing evaluation results found.
                                    </td>
                                </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    @if($ongoingTeachers->hasPages())

                    <div class="px-5 md:px-6 py-5 border-t border-slate-100">
                        {{ $ongoingTeachers->appends(['tab' => 'latest'])->links() }}
                    </div>

                    @endif

                </div>

            </div>


            {{-- COMPLETED EVALUATION --}}
            <div x-show="activeTab === 'completed'"
                x-cloak>

                <div class="app-section-card">

                    <div class="px-5 md:px-6 py-5
                                border-b border-slate-100">

                        <div class="flex flex-wrap items-center
                                    justify-between gap-3">

                            <div>
                                <h2 class="text-[17px] font-extrabold text-slate-900">
                                    Completed Evaluation List
                                </h2>

                                <p class="text-[12.5px] text-slate-500 mt-1">
                                    Teachers who have successfully completed their evaluation.
                                </p>
                            </div>

                            <span class="app-badge app-badge-success">
                                {{ $totalCompleted }} Completed
                            </span>

                        </div>

                    </div>

                    <div class="app-table-wrap">

                        <table class="app-table">

                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Teacher Name</th>
                                    <th>Pre-Observation</th>
                                    <th>External Observation</th>
                                    <th>Post-Observation</th>
                                    <th class="!text-center">Overall Status</th>
                                    <th class="!text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($completedTeachers as $teacher)

                                <tr>

                                    <td>
                                        {{ $completedTeachers->firstItem() + $loop->index }}
                                    </td>

                                    <td>

                                        <div class="flex items-center gap-3">

                                            <div class="w-9 h-9 rounded-xl
                                                            bg-emerald-50 border border-emerald-200
                                                            flex items-center justify-center
                                                            shrink-0">

                                                <span class="text-[12px]
                                                                 font-extrabold text-emerald-600">
                                                    {{ strtoupper(substr($teacher->gn_name, 0, 1)) }}
                                                </span>

                                            </div>

                                            <p class="font-bold text-slate-800
                                                          whitespace-nowrap">
                                                {{ $teacher->gn_name }}
                                            </p>

                                        </div>

                                    </td>

                                    {{-- PRE --}}
                                    <td>

                                        <div class="w-28">

                                            <div class="flex items-center justify-between mb-2">

                                                <span class="text-[12px] font-bold text-slate-700">
                                                    {{ $teacher->pre_completed }}/{{ $teacher->pre_total }}
                                                </span>

                                                <span class="text-[10px] font-semibold text-slate-400">
                                                    {{ $teacher->pre_progress }}%
                                                </span>

                                            </div>

                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">

                                                <div class="h-full bg-blue-600 rounded-full"
                                                    :style="{ width: '{{ $teacher->pre_progress }}%' }">
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    {{-- EXTERNAL --}}
                                    <td>

                                        <div class="w-28">

                                            <div class="flex items-center justify-between mb-2">

                                                <span class="text-[12px] font-bold text-slate-700">
                                                    {{ $teacher->external_completed }}/{{ $teacher->external_total }}
                                                </span>

                                                <span class="text-[10px] font-semibold text-slate-400">
                                                    {{ $teacher->external_progress }}%
                                                </span>

                                            </div>

                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">

                                                <div class="h-full bg-blue-600 rounded-full"
                                                    :style="{ width: '{{ $teacher->external_progress }}%' }">
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    {{-- POST --}}
                                    <td>

                                        <div class="w-28">

                                            <div class="flex items-center justify-between mb-2">

                                                <span class="text-[12px] font-bold text-slate-700">
                                                    {{ $teacher->post_completed }}/{{ $teacher->post_total }}
                                                </span>

                                                <span class="text-[10px] font-semibold text-slate-400">
                                                    {{ $teacher->post_progress }}%
                                                </span>

                                            </div>

                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">

                                                <div class="h-full bg-blue-600 rounded-full"
                                                    :style="{ width: '{{ $teacher->post_progress }}%' }">
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    {{-- STATUS --}}
                                    <td class="!text-center">

                                        <span class="app-badge app-badge-success">
                                            Completed
                                        </span>

                                    </td>

                                    {{-- ACTION --}}
                                    <td class="!text-center">

                                        <a href="{{ route('principal.result.show', $teacher->gn_id) }}"
                                            class="app-button-outline">
                                            View
                                        </a>

                                    </td>

                                </tr>

                                @empty

                                <tr>
                                    <td colspan="7"
                                        class="!text-center !py-12 text-slate-400">
                                        No completed teachers found.
                                    </td>
                                </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    @if($completedTeachers->hasPages())

                    <div class="px-5 md:px-6 py-5 border-t border-slate-100">
                        {{ $completedTeachers->appends(['tab' => 'completed'])->links() }}
                    </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-app-layout>