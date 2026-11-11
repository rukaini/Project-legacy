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
                                d="M2.25 12s3.75-7.5 9.75-7.5
                                   9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5
                                   S2.25 12 2.25 12z" />

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
                            {{ $form->version_no }}
                        </p>

                    </div>


                    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3
                                flex items-center gap-3">

                        <div class="w-9 h-9 rounded-lg bg-blue-100
                                    flex items-center justify-center shrink-0">

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
                                    d="M2.25 12s3.75-7.5 9.75-7.5
                                       9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5
                                       S2.25 12 2.25 12z" />

                                <circle cx="12" cy="12" r="3" />

                            </svg>

                        </div>


                        <div>

                            <p class="text-[12px] font-extrabold text-blue-800">
                                Preview Only
                            </p>

                            <p class="text-[11px] text-blue-600 mt-0.5">
                                This shows how the form appears to the evaluator
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            @forelse($form->aspects as $aspect)

            <div class="app-section-card mb-7">

                {{-- Aspect --}}
                <div class="px-5 md:px-6 py-5 bg-slate-50 border-b border-slate-200">

                    <p class="text-[10px] uppercase tracking-[0.07em]
                                  text-blue-600 font-bold">

                        Aspect {{ $aspect->aspect_code }}

                    </p>

                    <h2 class="text-[16px] font-extrabold text-slate-900 mt-1">
                        {{ $aspect->aspect_name }}
                    </h2>

                </div>


                @foreach($aspect->tums as $tums)

                @php
                $allPoints = $tums->tt->flatMap(fn($tt) => $tt->points)->values();
                $totalPoints = $allPoints->count();
                @endphp


                <div class="border-b border-slate-100 last:border-b-0">

                    {{-- TUMS --}}
                    <div class="px-5 md:px-6 py-4 bg-blue-50/40 border-b border-blue-100">

                        <div class="flex items-center justify-between gap-4">

                            <div>

                                <p class="text-[10px] font-bold uppercase tracking-[0.06em]
                                                  text-blue-600">

                                    TUMS {{ $tums->tums_code }}

                                </p>

                                <p class="font-extrabold text-slate-900 text-[13px] mt-1">
                                    {{ $tums->tums_name }}
                                </p>

                            </div>


                            <span class="app-badge app-badge-info">
                                Wajaran: {{ number_format($tums->wajaran, 2) }}
                            </span>

                        </div>

                    </div>


                    <div class="app-table-wrap">

                        <table class="w-full table-fixed text-sm">

                            <thead class="bg-blue-50 border-b border-blue-100">

                                <tr>

                                    <th class="w-14 px-4 py-3 text-center
                                                       text-[10px] uppercase tracking-[0.06em]
                                                       font-extrabold text-slate-500">
                                        No
                                    </th>

                                    <th class="w-[42%] px-5 py-3 text-left
                                                       text-[10px] uppercase tracking-[0.06em]
                                                       font-extrabold text-slate-500">
                                        Tahap Tindakan (TT)
                                    </th>

                                    <th class="w-28 px-5 py-3 text-center
                                                       text-[10px] uppercase tracking-[0.06em]
                                                       font-extrabold text-slate-500">
                                        Skor
                                    </th>

                                    <th class="px-5 py-3 text-left
                                                       text-[10px] uppercase tracking-[0.06em]
                                                       font-extrabold text-slate-500">
                                        Rubrik Tahap Kualiti (RTK)
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @forelse($allPoints as $point)

                                <tr class="align-top">

                                    <td class="px-4 py-4 text-center text-slate-500">
                                        {{ $loop->iteration }}.
                                    </td>


                                    <td class="px-5 py-4 border-l border-slate-100
                                                           text-slate-700 leading-6">

                                        {{ $point->point_text }}

                                    </td>


                                    <td class="px-5 py-4 border-l border-slate-100 text-center">

                                        <input
                                            type="text"
                                            disabled
                                            maxlength="1"
                                            placeholder="-"
                                            class="app-input !w-20 !mx-auto !text-center !bg-slate-50">

                                    </td>


                                    {{-- ONE RTK SET PER TUMS --}}
                                    @if($loop->first)

                                    <td
                                        rowspan="{{ $totalPoints }}"
                                        class="px-5 py-4 border-l border-slate-100 align-top">

                                        <div class="space-y-3">

                                            @foreach([4, 3, 2, 1, 0] as $score)

                                            @php
                                            $rubric = $tums->rubrics->firstWhere('score', $score);
                                            @endphp


                                            <div class="flex items-start gap-3">

                                                <span class="inline-flex items-center justify-center
                                                                                 min-w-[60px] px-2 py-2 rounded-lg
                                                                                 bg-blue-50 border border-blue-100
                                                                                 text-blue-700 text-[10px] font-extrabold
                                                                                 whitespace-nowrap">

                                                    RTK {{ $score }}

                                                </span>


                                                <div class="flex-1 px-3 py-2 rounded-xl
                                                                                border border-slate-200 bg-slate-50
                                                                                text-[11px] text-slate-700 leading-5">

                                                    {{ $rubric?->description ?? '-' }}

                                                </div>

                                            </div>

                                            @endforeach

                                        </div>

                                    </td>

                                    @endif

                                </tr>

                                @empty

                                <tr>

                                    <td
                                        colspan="4"
                                        class="px-5 py-10 text-center text-slate-400">

                                        No Tahap Tindakan added

                                    </td>

                                </tr>

                                @endforelse

                            </tbody>


                            <tfoot class="bg-slate-50 text-[12px]">

                                <tr class="border-t border-slate-200">

                                    <td></td>

                                    <td class="px-5 py-2.5 text-left text-slate-700
                                                       border-l border-slate-200">

                                        Bilangan Tindakan / Jumlah Skor Kualiti

                                    </td>

                                    <td class="px-3 py-2.5 text-center text-slate-700
                                                       border-l border-slate-200">
                                        0
                                    </td>

                                    <td class="p-0 border-l border-slate-200">

                                        <div class="grid grid-cols-2 h-full">

                                            <div class="px-3 py-2.5 text-center text-slate-700
                                                                border-r border-slate-200">
                                                0
                                            </div>

                                            <div></div>

                                        </div>

                                    </td>

                                </tr>


                                <tr class="border-t border-slate-200">

                                    <td></td>

                                    <td class="px-5 py-2.5 text-left text-slate-700
                                                       border-l border-slate-200">

                                        Skor Tahap Tindakan / Min Skor Tahap Kualiti

                                    </td>

                                    <td class="px-3 py-2.5 text-center text-slate-700
                                                       border-l border-slate-200">
                                        0
                                    </td>

                                    <td class="p-0 border-l border-slate-200">

                                        <div class="grid grid-cols-2 h-full">

                                            <div class="px-3 py-2.5 text-center text-slate-700
                                                                border-r border-slate-200">
                                                0.00
                                            </div>

                                            <div></div>

                                        </div>

                                    </td>

                                </tr>


                                <tr class="border-t border-slate-200">

                                    <td></td>

                                    <td class="px-5 py-2.5 text-left text-slate-700
                                                       border-l border-slate-200">

                                        Peratus Skor Tahap Tindakan / Peratus Skor Tahap Kualiti

                                    </td>

                                    <td class="px-3 py-2.5 text-center text-slate-700
                                                       border-l border-slate-200">
                                        0.00
                                    </td>

                                    <td class="p-0 border-l border-slate-200">

                                        <div class="grid grid-cols-2 h-full">

                                            <div class="px-3 py-2.5 text-center text-slate-700
                                                                border-r border-slate-200">
                                                0.00
                                            </div>

                                            <div></div>

                                        </div>

                                    </td>

                                </tr>


                                <tr class="border-t border-blue-200 bg-blue-50/60">

                                    <td></td>

                                    <td
                                        colspan="2"
                                        class="px-5 py-2.5 text-left font-extrabold
                                                       text-slate-800 border-l border-blue-200">

                                        Peratus TUMS

                                    </td>

                                    <td class="p-0 border-l border-blue-200">

                                        <div class="grid grid-cols-2 h-full">

                                            <div class="px-3 py-2.5 text-center
                                                                bg-blue-600 text-white font-extrabold">
                                                0.00
                                            </div>

                                            <div></div>

                                        </div>

                                    </td>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

                @endforeach

            </div>

            @empty

            <div class="app-section-card py-12 text-center text-slate-400 mb-7">
                No form content added
            </div>

            @endforelse


            {{-- Observation Summary --}}
            <div class="app-section-card mb-24">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Observation Summary
                    </h2>

                </div>


                <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,2.1fr)_minmax(330px,1fr)]
                            gap-6 p-5 md:p-6">

                    <div class="overflow-hidden rounded-xl border border-slate-200 self-start">

                        <table class="w-full table-fixed text-sm">

                            <thead class="bg-blue-50 border-b border-blue-100">

                                <tr>

                                    <th class="w-[10%] px-3 py-3 text-center
                                               border-r border-slate-200 text-[10px]
                                               uppercase tracking-[0.06em] font-extrabold text-slate-900">
                                        Aspect
                                    </th>

                                    <th class="w-[32%] px-3 py-3 text-left
                                               border-r border-slate-200 text-[10px]
                                               uppercase tracking-[0.06em] font-extrabold text-slate-900">
                                        Aspect Name
                                    </th>

                                    <th class="w-[13%] px-3 py-3 text-center
                                               border-r border-slate-200 text-[10px]
                                               uppercase tracking-[0.06em] font-extrabold text-slate-900">
                                        TUMS
                                    </th>

                                    <th class="w-[14%] px-3 py-3 text-center
                                               border-r border-slate-200 text-[10px]
                                               uppercase tracking-[0.06em] font-extrabold text-slate-900">
                                        Wajaran
                                    </th>

                                    <th class="w-[14%] px-3 py-3 text-center
                                               border-r border-slate-200 text-[10px]
                                               uppercase tracking-[0.06em] font-extrabold text-slate-900">
                                        %
                                    </th>

                                    <th class="w-[17%] px-3 py-3 text-center
                                               text-[10px] uppercase tracking-[0.06em]
                                               font-extrabold text-slate-900">
                                        Skor
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @forelse($form->aspects as $aspect)

                                @foreach($aspect->tums as $tums)

                                <tr>

                                    <td class="px-3 py-2.5 text-center text-slate-700
                                                       border-r border-slate-100">
                                        {{ $aspect->aspect_code }}
                                    </td>

                                    <td class="px-3 py-2.5 text-slate-700
                                                       border-r border-slate-100">
                                        {{ $aspect->aspect_name }}
                                    </td>

                                    <td class="px-3 py-2.5 text-center text-slate-700
                                                       border-r border-slate-100">
                                        {{ $tums->tums_code }}
                                    </td>

                                    <td class="px-3 py-2.5 text-center text-slate-700
                                                       border-r border-slate-100">
                                        {{ number_format($tums->wajaran, 2) }}
                                    </td>

                                    <td class="px-3 py-2.5 text-center text-slate-400
                                                       border-r border-slate-100">
                                        0.00
                                    </td>

                                    <td class="px-3 py-2.5 text-center text-slate-400">
                                        0.00
                                    </td>

                                </tr>

                                @endforeach

                                @empty

                                <tr>

                                    <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                        No TUMS available
                                    </td>

                                </tr>

                                @endforelse

                            </tbody>


                            <tfoot>

                                <tr class="bg-blue-50 border-t border-blue-200">

                                    <td
                                        colspan="5"
                                        class="px-3 py-3 text-right font-extrabold
                                               text-slate-900 border-r border-blue-200">

                                        Total

                                    </td>

                                    <td class="px-3 py-3 text-center bg-blue-600
                                               text-white font-extrabold">
                                        0.00
                                    </td>

                                </tr>

                            </tfoot>

                        </table>

                    </div>


                    <div class="overflow-hidden rounded-xl border border-slate-200 self-start">

                        <table class="w-full table-fixed text-sm">

                            <thead class="bg-blue-50 border-b border-blue-100">

                                <tr>

                                    <th class="w-[45%] px-3 py-3 text-left
                                               border-r border-slate-200
                                               text-[10px] uppercase tracking-[0.06em]
                                               font-extrabold text-slate-900">
                                        Taraf
                                    </th>

                                    <th class="w-[38%] px-3 py-3 text-center
                                               border-r border-slate-200
                                               text-[10px] uppercase tracking-[0.06em]
                                               font-extrabold text-slate-900">
                                        Skor
                                    </th>

                                    <th class="w-[17%] px-3 py-3 text-center
                                               text-[10px] uppercase tracking-[0.06em]
                                               font-extrabold text-slate-900">
                                        ✓
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                <tr>
                                    <td class="px-3 py-3 text-slate-700 border-r border-slate-100">Cemerlang</td>
                                    <td class="px-3 py-3 text-center text-slate-700 border-r border-slate-100">90 - 100</td>
                                    <td class="px-3 py-3 text-center"></td>
                                </tr>

                                <tr>
                                    <td class="px-3 py-3 text-slate-700 border-r border-slate-100">Baik</td>
                                    <td class="px-3 py-3 text-center text-slate-700 border-r border-slate-100">80 - 89.99</td>
                                    <td class="px-3 py-3 text-center"></td>
                                </tr>

                                <tr>
                                    <td class="px-3 py-3 text-slate-700 border-r border-slate-100">Sederhana</td>
                                    <td class="px-3 py-3 text-center text-slate-700 border-r border-slate-100">50 - 79.99</td>
                                    <td class="px-3 py-3 text-center"></td>
                                </tr>

                                <tr>
                                    <td class="px-3 py-3 text-slate-700 border-r border-slate-100">Lemah</td>
                                    <td class="px-3 py-3 text-center text-slate-700 border-r border-slate-100">20 - 49.99</td>
                                    <td class="px-3 py-3 text-center"></td>
                                </tr>

                                <tr>
                                    <td class="px-3 py-3 text-slate-700 border-r border-slate-100">Sangat Lemah</td>
                                    <td class="px-3 py-3 text-center text-slate-700 border-r border-slate-100">0 - 19.99</td>
                                    <td class="px-3 py-3 text-center"></td>
                                </tr>

                            </tbody>

                        </table>

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
                        href="{{ route('admin.pdpc.form') }}"
                        class="app-button-outline">

                        Back

                    </a>

                    <a
                        href="{{ route('admin.pdpc.form.edit', $form->formID) }}"
                        class="app-button-primary">

                        Edit

                    </a>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>