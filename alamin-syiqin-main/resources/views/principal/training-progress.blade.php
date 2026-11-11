<x-app-layout>

    <div class="app-page-shell">
        <div class="max-w-8xl mx-auto">

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
                            Training Progress
                        </h1>

                        <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                            Monitor the LMS training and feedback progress of active new teachers.
                        </p>
                    </div>

                </div>

                {{-- SUMMARY CARDS --}}
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
                            Completed Training
                        </p>

                        <p class="text-[24px] font-extrabold
                                  text-emerald-600 mt-0.5">
                            {{ $totalCompleted }}
                        </p>
                    </div>

                </div>
            </div>

            {{-- SEARCH --}}
            <div class="app-section-card mb-6">
                <div class="px-5 md:px-6 py-5">

                    <form method="GET"
                        action="{{ route('principal.training.progress') }}"
                        class="flex flex-col sm:flex-row
                                 sm:items-center sm:justify-end gap-2">

                        <div class="relative w-full sm:w-[280px]">

                            <svg class="absolute left-3 top-1/2
                                        -translate-y-1/2 w-4 h-4 text-slate-400"
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

                        <button type="submit" class="app-button-primary">
                            Search
                        </button>

                        <a href="{{ route('principal.training.progress') }}"
                            class="app-button-outline">
                            Clear
                        </a>

                    </form>

                </div>
            </div>

            {{-- TRAINING LIST --}}
            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <div class="flex flex-wrap items-center justify-between gap-3">

                        <div>
                            <h2 class="text-[17px] font-extrabold text-slate-900">
                                Training Progress List
                            </h2>

                            <p class="text-[12.5px] text-slate-500 mt-1">
                                LMS training and feedback completion status for active new teachers.
                            </p>
                        </div>

                        <span class="app-badge app-badge-info">
                            {{ $totalTeachers }} Teachers
                        </span>

                    </div>
                </div>

                {{-- TABLE --}}
                <div class="app-table-wrap">
                    <table class="app-table">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Teacher Name</th>
                                <th>Training Completion</th>
                                <th>Feedback Completion</th>
                                <th class="!text-center">Overall Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($teachers as $teacher)

                            <tr>
                                <td>
                                    {{ $teachers->firstItem() + $loop->index }}
                                </td>

                                {{-- TEACHER --}}
                                <td>
                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-xl
                                                        bg-blue-50 border border-blue-200
                                                        flex items-center justify-center shrink-0">

                                            <span class="text-[12px]
                                                             font-extrabold text-blue-600">
                                                {{ strtoupper(substr($teacher->gn_name, 0, 1)) }}
                                            </span>
                                        </div>

                                        <div>
                                            <p class="font-bold text-slate-800 whitespace-nowrap">
                                                {{ $teacher->gn_name }}
                                            </p>

                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                {{ $teacher->gn_id }}
                                            </p>
                                        </div>

                                    </div>
                                </td>

                                {{-- TRAINING COMPLETION --}}
                                <td>

                                    <div class="w-40 max-w-full">

                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-[12px] font-bold text-slate-700">
                                                {{ $teacher->completed_training }}/{{ $teacher->total_training }}
                                            </span>

                                            <span class="text-[10px] font-semibold text-slate-400">
                                                {{ $teacher->training_percentage }}%
                                            </span>
                                        </div>

                                        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                            <div
                                                class="h-full rounded-full {{ $teacher->training_percentage == 100 ? 'bg-blue-600' : 'bg-slate-400' }}"
                                                @style(['width: ' . $teacher->training_percentage . ' %'])>
                                            </div>
                                        </div>

                                    </div>

                                </td>

                                {{-- FEEDBACK COMPLETION --}}
                                <td>

                                    <div class="w-40 max-w-full">

                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-[12px] font-bold text-slate-700">
                                                {{ $teacher->completed_feedback }}/{{ $teacher->total_feedback }}
                                            </span>

                                            <span class="text-[10px] font-semibold text-slate-400">
                                                {{ $teacher->feedback_percentage }}%
                                            </span>
                                        </div>

                                        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                            <div
                                                class="h-full rounded-full {{ $teacher->feedback_percentage == 100 ? 'bg-blue-600' : 'bg-slate-400' }}"
                                                @style(['width: ' . $teacher->feedback_percentage . ' %'])>
                                            </div>
                                        </div>

                                    </div>

                                </td>

                                {{-- OVERALL STATUS --}}
                                <td class="!text-center">

                                    @if($teacher->training_status === 'Completed')

                                    <span class="app-badge app-badge-success">
                                        Completed
                                    </span>

                                    @elseif($teacher->training_status === 'In Progress')

                                    <span class="app-badge app-badge-info">
                                        In Progress
                                    </span>

                                    @else

                                    <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                                        Not Started
                                    </span>

                                    @endif

                                </td>
                            </tr>

                            @empty

                            <tr>
                                <td colspan="5"
                                    class="!text-center !py-12 text-slate-400">
                                    No active new teachers found.
                                </td>
                            </tr>

                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                @if($teachers->hasPages())
                <div class="px-5 md:px-6 py-5 border-t border-slate-100">
                    {{ $teachers->links() }}
                </div>
                @endif

            </div>

        </div>
    </div>

</x-app-layout>