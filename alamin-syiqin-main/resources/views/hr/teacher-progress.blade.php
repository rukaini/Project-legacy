<x-app-layout>

    <div class="app-page-shell">
        <div class="max-w-8xl mx-auto">

            {{-- PAGE HEADER --}}
            <div class="mb-7 flex items-start gap-4">

                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200
                            flex items-center justify-center shrink-0">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.7"
                        stroke="currentColor"
                        class="w-6 h-6 text-blue-600">

                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75l2.25 2.25L15 9.75
                               M12 3.75a8.25 8.25 0 110 16.5
                               8.25 8.25 0 010-16.5z" />
                    </svg>

                </div>

                <div>
                    <h1 class="text-[26px] md:text-[30px] font-extrabold text-slate-900">
                        List Complete Teacher
                    </h1>

                    <p class="text-sm text-slate-500 mt-1">
                        View new teachers who have completed all observation,
                        training and feedback requirements.
                    </p>
                </div>

            </div>

            {{-- SEARCH --}}
            <div class="app-section-card mb-7">
                <div class="px-5 md:px-6 py-5">

                    <form method="GET"
                        action="{{ route('hr.teacher.progress') }}"
                        class="flex flex-wrap items-end gap-3">

                        <div class="flex-1 min-w-[220px]">

                            <label class="block text-xs font-semibold text-slate-600 mb-2">
                                Search Teacher or School
                            </label>

                            <div class="relative">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="absolute left-3 top-1/2 -translate-y-1/2
                                           w-4 h-4 text-slate-400 pointer-events-none">

                                    <circle cx="11" cy="11" r="7" />
                                    <path stroke-linecap="round" d="m20 20-4-4" />

                                </svg>

                                <input
                                    type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    placeholder="Search name, ID, email or school"
                                    class="w-full rounded-lg border-slate-200 text-sm pl-10">

                            </div>
                        </div>

                        <button type="submit" class="app-button-primary">
                            Search
                        </button>

                        <a href="{{ route('hr.teacher.progress') }}"
                            class="app-button-outline">
                            Reset
                        </a>

                    </form>

                </div>
            </div>

            {{-- COMPLETED OBSERVATION AND TRAINING LIST --}}
            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Completed Observation & Training List
                    </h2>

                    <p class="text-xs text-slate-500 mt-1">
                        New teachers who have successfully completed all evaluation
                        components, LMS courses and course feedback.
                    </p>

                </div>

                <div class="app-table-wrap">

                    <table class="app-table">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Teacher</th>
                                <th>School</th>
                                <th class="text-center">Observation Completion</th>
                                <th class="text-center">Training Completion</th>
                                <th class="text-center">Feedback Completion</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($teachers as $teacher)

                            <tr>

                                {{-- NO --}}
                                <td>
                                    {{ $teachers->firstItem() + $loop->index }}
                                </td>

                                {{-- TEACHER --}}
                                <td>

                                    <p class="font-bold text-slate-800">
                                        {{ $teacher->gn_name }}
                                    </p>

                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ $teacher->gn_id }}
                                    </p>

                                </td>

                                {{-- SCHOOL --}}
                                <td>
                                    {{ $teacher->school?->schoolName ?? '-' }}
                                </td>

                                {{-- OBSERVATION COMPLETION --}}
                                <td class="text-center align-middle">

                                    <span class="inline-flex items-center gap-1.5
                                                     text-xs font-bold text-emerald-700">

                                        {{ $teacher->observation_count }}/{{ $teacher->observation_total }}
                                        Completed

                                    </span>

                                </td>

                                {{-- TRAINING COMPLETION --}}
                                <td class="text-center align-middle">

                                    <span class="inline-flex items-center gap-1.5
                                                     text-xs font-bold text-emerald-700">

                                        {{ $teacher->completed_training }}/{{ $teacher->total_training }}
                                        Completed

                                    </span>

                                </td>

                                {{-- FEEDBACK COMPLETION --}}
                                <td class="text-center align-middle">

                                    <span class="inline-flex items-center gap-1.5
                                                     text-xs font-bold text-emerald-700">

                                        {{ $teacher->completed_feedback }}/{{ $teacher->total_feedback }}
                                        Completed

                                    </span>

                                </td>

                            </tr>

                            @empty

                            <tr>
                                <td colspan="6"
                                    class="text-center py-10 text-slate-500">

                                    No teachers have completed all observation,
                                    training and feedback requirements yet.

                                </td>
                            </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                {{-- PAGINATION --}}
                @if($teachers->hasPages())

                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $teachers->links() }}
                </div>

                @endif

            </div>

        </div>
    </div>

</x-app-layout>