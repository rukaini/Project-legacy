<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-7xl mx-auto">

            {{-- Page heading --}}
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 mb-7">

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
                                d="M2.458 12C3.732 7.943 7.523 5 12 5
                                   c4.478 0 8.268 2.943 9.542 7
                                   -1.274 4.057-5.064 7-9.542 7
                                   -4.477 0-8.268-2.943-9.542-7z" />

                            <circle cx="12" cy="12" r="3" />

                        </svg>

                    </div>


                    <div>

                        <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                            {{ $form->form_name }}
                        </h1>


                        @if($form->instruction)

                        <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                            {{ $form->instruction }}
                        </p>

                        @endif

                    </div>

                </div>


                <div class="flex flex-wrap items-stretch gap-3">

                    <div class="min-w-[105px] bg-white border border-slate-200
                                rounded-xl px-4 py-3 shadow-sm">

                        <p class="text-[10px] uppercase tracking-[0.06em] text-slate-400 font-bold">
                            Version
                        </p>

                        <p class="text-[18px] font-extrabold text-slate-900 mt-1">
                            {{ $form->version }}
                        </p>

                    </div>


                    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3
                                flex items-center gap-3">

                        <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 text-blue-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5
                                       c4.478 0 8.268 2.943 9.542 7
                                       -1.274 4.057-5.064 7-9.542 7
                                       -4.477 0-8.268-2.943-9.542-7z" />

                                <circle cx="12" cy="12" r="3" />

                            </svg>

                        </div>


                        <div>

                            <p class="text-[12px] font-extrabold text-blue-800">
                                Preview Only
                            </p>

                            <p class="text-[11px] text-blue-600 mt-0.5">
                                This shows how the form appears to the observer
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Evaluation Form --}}
            <div class="app-section-card mb-7">

                <div class="app-table-wrap">

                    <table class="w-full text-sm">

                        <thead class="bg-blue-50 border-b border-blue-300">

                            <tr>

                                <th class="px-5 py-4 text-center w-16
                                           text-[10px] uppercase tracking-[0.06em]
                                           font-extrabold text-slate-500">

                                    No.

                                </th>

                                <th class="px-5 py-4 text-left
                                           text-[10px] uppercase tracking-[0.06em]
                                           font-extrabold text-slate-500">

                                    Section / Criteria

                                </th>

                                <th class="px-5 py-4 text-center w-72
                                           text-[10px] uppercase tracking-[0.06em]
                                           font-extrabold text-slate-500">

                                    Score

                                </th>

                                <th class="px-5 py-4 text-left w-80
                                           text-[10px] uppercase tracking-[0.06em]
                                           font-extrabold text-slate-500">

                                    Comment

                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @foreach($form->sections as $section)

                            @if($section->criteria->isNotEmpty())

                            <tr class="bg-blue-50/50">

                                <td class="px-5 py-4 text-center text-[12px]
                                                   font-extrabold text-blue-600">

                                    {{ $loop->iteration }}

                                </td>


                                <td
                                    colspan="3"
                                    class="px-5 py-4 text-[13px]
                                                   font-extrabold text-slate-900">

                                    {{ $section->section_name }}

                                </td>

                            </tr>


                            @foreach($section->criteria as $criteria)

                            @php $letter = chr(96 + $loop->iteration); @endphp

                            <tr>

                                <td class="px-5 py-5"></td>


                                <td class="px-5 py-5 text-[13px] text-slate-700 align-middle">

                                    <span class="font-bold text-slate-500">
                                        {{ $letter }}.
                                    </span>

                                    {{ $criteria->criteria_label }}

                                </td>


                                <td class="px-5 py-5">

                                    <div class="flex items-center justify-center gap-4 flex-wrap">

                                        @for($score = $form->min_score; $score <= $form->max_score; $score++)

                                            <label class="flex items-center gap-1.5">

                                                <input
                                                    type="radio"
                                                    disabled
                                                    class="text-blue-600 border-slate-300">

                                                <span class="text-[12px] font-bold text-slate-600">
                                                    {{ $score }}
                                                </span>

                                            </label>

                                            @endfor

                                    </div>

                                </td>


                                @if($loop->first)

                                <td
                                    rowspan="{{ $section->criteria->count() }}"
                                    class="px-5 py-5 align-middle border-l border-slate-100">

                                    <textarea
                                        rows="5"
                                        disabled
                                        placeholder="Enter comment..."
                                        class="app-textarea !bg-slate-50"></textarea>

                                </td>

                                @endif

                            </tr>

                            @endforeach

                            @endif

                            @endforeach

                        </tbody>


                        <tfoot>

                            <tr class="bg-slate-50 border-t border-slate-200">

                                <td
                                    colspan="2"
                                    class="px-5 py-4 text-right text-[11px]
                                           font-extrabold uppercase tracking-[0.05em]
                                           text-slate-600">

                                    Total

                                </td>


                                <td class="px-5 py-4 text-center text-sm">

                                    <span class="font-extrabold text-slate-900">
                                        0
                                    </span>

                                    <span class="text-slate-400">
                                        /
                                    </span>

                                    <span class="font-semibold text-slate-500">
                                        {{ $maximumScore }}
                                    </span>

                                </td>


                                <td class="px-5 py-4 text-center text-sm">

                                    <span class="font-extrabold uppercase text-slate-600">
                                        Percentage :
                                    </span>

                                    <span class="ml-2 font-extrabold text-blue-600">
                                        0%
                                    </span>

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>


            {{-- Other Comment --}}
            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Other Comment
                    </h2>

                </div>


                <div class="p-5 md:p-6">

                    <textarea
                        rows="4"
                        disabled
                        placeholder="Enter comment..."
                        class="app-textarea !bg-slate-50"></textarea>

                </div>

            </div>


            {{-- Achievement Level --}}
            <div class="app-section-card mb-24">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Achievement Level
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Evaluation percentage categories.
                    </p>

                </div>


                <div class="p-5 md:p-6">

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">

                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-center">

                            <p class="font-bold text-slate-800 text-[12px]">
                                Weak
                            </p>

                            <p class="text-[11px] text-slate-500 mt-1">
                                0 - 39%
                            </p>

                        </div>


                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-center">

                            <p class="font-bold text-slate-800 text-[12px]">
                                Satisfactory
                            </p>

                            <p class="text-[11px] text-slate-500 mt-1">
                                40 - 59%
                            </p>

                        </div>


                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-center">

                            <p class="font-bold text-slate-800 text-[12px]">
                                Good
                            </p>

                            <p class="text-[11px] text-slate-500 mt-1">
                                60 - 79%
                            </p>

                        </div>


                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-center">

                            <p class="font-bold text-slate-800 text-[12px]">
                                Very Good
                            </p>

                            <p class="text-[11px] text-slate-500 mt-1">
                                80 - 89%
                            </p>

                        </div>


                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-center">

                            <p class="font-bold text-slate-800 text-[12px]">
                                Excellent
                            </p>

                            <p class="text-[11px] text-slate-500 mt-1">
                                90 - 100%
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Bottom actions --}}
    <div class="fixed bottom-4 left-0 right-0 z-40 px-6 pointer-events-none">

        <div class="max-w-7xl mx-auto">

            <div class="bg-white/95 backdrop-blur-md border border-slate-200
                        shadow-xl rounded-2xl px-5 py-4 pointer-events-auto">

                <div class="flex items-center justify-between">

                    <a
                        href="{{ route('admin.pre.form') }}"
                        class="app-button-outline">

                        Back

                    </a>


                    <a
                        href="{{ route('admin.pre.form.edit', $form->formID) }}"
                        class="app-button-primary">

                        Edit

                    </a>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>