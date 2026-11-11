<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-7xl mx-auto">

            {{-- Page Header --}}
            <div class="flex items-start gap-4 mb-7">

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
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0
                               9 9 0 0 1 18 0Z" />
                    </svg>

                </div>

                <div>

                    <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-blue-600 mb-1">
                        {{ $stage }} Observation
                    </p>

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


            {{-- Errors --}}
            @if($errors->any())

            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4">

                <ul class="list-disc list-inside text-sm">

                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

            @endif


            <form
                method="POST"
                id="pdpcForm"
                action="{{ $role === 'observer'
                    ? route('observer.pdpc.store', $gn_id)
                    : route('external.pdpc.store', $gn_id) }}">

                @csrf


                {{-- Observation Information --}}
                <div class="app-section-card mb-7">

                    <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            Observation Information
                        </h2>

                        <p class="text-[12.5px] text-slate-500 mt-1">
                            Enter the classroom observation details.
                        </p>

                    </div>


                    <div class="p-5 md:p-6">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pb-5 mb-5 border-b border-slate-100">

                            <div>

                                <p class="app-label">
                                    Teacher
                                </p>

                                <p class="text-[13.5px] font-extrabold text-slate-900 uppercase">
                                    {{ $guru->gn_name }}
                                </p>

                            </div>


                            <div>

                                <p class="app-label">
                                    School
                                </p>

                                <p class="text-[13.5px] font-extrabold text-slate-900">
                                    {{ $guru->school?->schoolName ?? '-' }}
                                </p>

                            </div>


                            <div>

                                <p class="app-label">
                                    {{ $role === 'observer' ? 'Observer' : 'External Observer' }}
                                </p>

                                <p class="text-[13.5px] font-extrabold text-slate-900 uppercase">
                                    {{ Auth::guard('teacher')->user()->teacherName }}
                                </p>

                            </div>

                        </div>


                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">

                            <div>

                                <label class="app-label">
                                    Class
                                </label>

                                <input
                                    type="text"
                                    name="class_name"
                                    value="{{ old('class_name') }}"
                                    required
                                    placeholder="Enter class"
                                    class="app-input">

                            </div>


                            <div>

                                <label class="app-label">
                                    Subject
                                </label>

                                <input
                                    type="text"
                                    name="subject_name"
                                    value="{{ old('subject_name') }}"
                                    required
                                    placeholder="Enter subject"
                                    class="app-input">

                            </div>


                            <div>

                                <label class="app-label">
                                    Date
                                </label>

                                <input
                                    type="date"
                                    name="observation_date"
                                    value="{{ old('observation_date') }}"
                                    required
                                    class="app-input">

                            </div>


                            <div>

                                <label class="app-label">
                                    Time
                                </label>

                                <input
                                    type="time"
                                    name="observation_time"
                                    value="{{ old('observation_time') }}"
                                    required
                                    class="app-input">

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Aspects --}}
                @foreach($form->aspects as $aspect)

                <div class="app-section-card mb-7">

                    {{-- Aspect Header --}}
                    <div class="px-5 md:px-6 py-5 border-b border-blue-100 bg-blue-50">

                        <p class="text-[10.5px] uppercase tracking-[0.08em] font-extrabold text-blue-600">
                            Aspect {{ $aspect->aspect_code }}
                        </p>

                        <h2 class="text-[17px] font-extrabold text-slate-900 mt-1">
                            {{ $aspect->aspect_name }}
                        </h2>

                    </div>


                    @foreach($aspect->tums as $tums)

                    @php
                    $allPoints = $tums->tt->flatMap(fn($tt) => $tt->points);
                    $totalPoints = $allPoints->count();
                    @endphp


                    <div
                        id="tums-container-{{ $tums->tumsID }}"
                        data-weight="{{ $tums->wajaran }}"
                        class="border-b border-slate-200 last:border-b-0">


                        {{-- TUMS Header --}}
                        <div class="px-5 md:px-6 py-4 bg-slate-50 border-b border-slate-200">

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                                <div>

                                    <p class="text-[10.5px] font-extrabold uppercase tracking-[0.07em] text-blue-600">
                                        TUMS {{ $tums->tums_code }}
                                    </p>

                                    <p class="text-[13.5px] font-extrabold text-slate-900 mt-1">
                                        {{ $tums->tums_name }}
                                    </p>

                                </div>


                                <span class="app-badge app-badge-info">
                                    Wajaran: {{ number_format($tums->wajaran, 2) }}
                                </span>

                            </div>

                        </div>


                        {{-- Evaluation Table --}}
                        <div class="overflow-x-auto">

                            <table class="w-full table-fixed text-sm">

                                <thead>

                                    <tr class="bg-blue-50 text-blue-700 uppercase text-[10.5px]">

                                        <th class="w-14 px-4 py-3 text-center font-extrabold border-b border-slate-200">
                                            No
                                        </th>

                                        <th class="w-[42%] px-5 py-3 text-left font-extrabold border-b border-slate-200">
                                            Tahap Tindakan (TT)
                                        </th>

                                        <th class="w-28 px-5 py-3 text-center font-extrabold border-b border-slate-200">
                                            Skor
                                        </th>

                                        <th class="px-5 py-3 text-left font-extrabold border-b border-slate-200">
                                            Rubrik Tahap Kualiti (RTK)
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach($allPoints as $point)

                                    <tr class="align-top border-b border-slate-100 last:border-b-0 hover:bg-slate-50/60">

                                        <td class="px-4 py-4 text-center text-slate-500">
                                            {{ $loop->iteration }}.
                                        </td>

                                        <td class="px-5 py-4 border-l border-slate-100 text-slate-700 leading-6">
                                            {{ $point->point_text }}
                                        </td>

                                        <td class="px-5 py-4 border-l border-slate-100 text-center">

                                            <input
                                                type="text"
                                                name="scores[{{ $point->pointID }}]"
                                                value="{{ old('scores.' . $point->pointID) }}"
                                                maxlength="1"
                                                inputmode="numeric"
                                                placeholder="-"
                                                data-tums="{{ $tums->tumsID }}"
                                                oninput="this.value=this.value.replace(/[^0-4]/g,'').slice(0,1); calculateTums('{{ $tums->tumsID }}');"
                                                class="w-16 h-10 rounded-lg border-slate-300
                                                       text-center text-[13px] font-bold text-slate-900
                                                       focus:border-blue-500 focus:ring-blue-500">

                                        </td>


                                        @if($loop->first)

                                        <td
                                            rowspan="{{ $totalPoints }}"
                                            class="px-5 py-4 border-l border-slate-100 align-top">

                                            <div class="space-y-2.5">

                                                @foreach([4, 3, 2, 1, 0] as $score)

                                                @php($rubric = $tums->rubrics->firstWhere('score', $score))

                                                <div class="flex items-start gap-3">

                                                    <span class="inline-flex items-center justify-center
                                                                 min-w-[58px] px-2 py-2
                                                                 rounded-lg bg-blue-50 border border-blue-200
                                                                 text-blue-700 text-[10.5px] font-extrabold whitespace-nowrap">

                                                        RTK {{ $score }}

                                                    </span>

                                                    <div class="flex-1 px-3 py-2 rounded-lg
                                                                border border-slate-200 bg-white
                                                                text-[11.5px] text-slate-600 leading-5">

                                                        {{ $rubric?->description ?? '-' }}

                                                    </div>

                                                </div>

                                                @endforeach

                                            </div>

                                        </td>

                                        @endif

                                    </tr>

                                    @endforeach

                                </tbody>


                                {{-- TUMS Calculation --}}
                                <tfoot class="bg-blue-50/50 text-[12px]">

                                    <tr class="border-t border-blue-100">

                                        <td></td>

                                        <td class="px-5 py-2.5 text-slate-700 border-l border-blue-100">
                                            Bilangan Tindakan / Jumlah Skor Kualiti
                                        </td>

                                        <td
                                            id="action-count-{{ $tums->tumsID }}"
                                            class="px-3 py-2.5 text-center font-bold text-slate-900 border-l border-blue-100">
                                            0
                                        </td>

                                        <td class="p-0 border-l border-blue-100">

                                            <div class="grid grid-cols-2">

                                                <div
                                                    id="quality-total-{{ $tums->tumsID }}"
                                                    class="px-3 py-2.5 text-center font-bold text-slate-900 border-r border-blue-100">
                                                    0
                                                </div>

                                                <div></div>

                                            </div>

                                        </td>

                                    </tr>


                                    <tr class="border-t border-blue-100">

                                        <td></td>

                                        <td class="px-5 py-2.5 text-slate-700 border-l border-blue-100">
                                            Skor Tahap Tindakan / Min Skor Tahap Kualiti
                                        </td>

                                        <td
                                            id="action-score-{{ $tums->tumsID }}"
                                            class="px-3 py-2.5 text-center font-bold text-slate-900 border-l border-blue-100">
                                            0
                                        </td>

                                        <td class="p-0 border-l border-blue-100">

                                            <div class="grid grid-cols-2">

                                                <div
                                                    id="quality-mean-{{ $tums->tumsID }}"
                                                    class="px-3 py-2.5 text-center font-bold text-slate-900 border-r border-blue-100">
                                                    0.00
                                                </div>

                                                <div></div>

                                            </div>

                                        </td>

                                    </tr>


                                    <tr class="border-t border-blue-100">

                                        <td></td>

                                        <td class="px-5 py-2.5 text-slate-700 border-l border-blue-100">
                                            Peratus Skor Tahap Tindakan / Peratus Skor Tahap Kualiti
                                        </td>

                                        <td
                                            id="action-percentage-{{ $tums->tumsID }}"
                                            class="px-3 py-2.5 text-center font-bold text-slate-900 border-l border-blue-100">
                                            0.00
                                        </td>

                                        <td class="p-0 border-l border-blue-100">

                                            <div class="grid grid-cols-2">

                                                <div
                                                    id="quality-percentage-{{ $tums->tumsID }}"
                                                    class="px-3 py-2.5 text-center font-bold text-slate-900 border-r border-blue-100">
                                                    0.00
                                                </div>

                                                <div></div>

                                            </div>

                                        </td>

                                    </tr>


                                    <tr class="border-t border-blue-100">

                                        <td></td>

                                        <td colspan="2"
                                            class="px-5 py-2.5 text-left font-extrabold text-slate-900 border-l border-blue-100">
                                            Peratus TUMS
                                        </td>

                                        <td class="p-0 border-l border-blue-100">

                                            <div class="grid grid-cols-2">

                                                <div
                                                    id="tums-percentage-{{ $tums->tumsID }}"
                                                    class="px-3 py-2.5 text-center bg-blue-600 text-white font-extrabold">
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

                @endforeach


                {{-- Overall Summary --}}
                <div class="app-section-card mb-7">

                    <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            Observation Summary
                        </h2>

                    </div>


                    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,2.1fr)_minmax(330px,1fr)] gap-5 p-5 md:p-6">

                        {{-- Aspect Summary --}}
                        <div class="overflow-x-auto rounded-xl border border-slate-200 self-start">

                            <table class="w-full table-fixed text-sm">

                                <thead>

                                    <tr class="bg-blue-50 text-slate-900 uppercase text-[10px]">

                                        <th class="w-[10%] px-3 py-3 text-center border-r border-slate-200">
                                            Aspect
                                        </th>

                                        <th class="w-[32%] px-3 py-3 text-left border-r border-slate-200">
                                            Aspect Name
                                        </th>

                                        <th class="w-[13%] px-3 py-3 text-center border-r border-slate-200">
                                            TUMS
                                        </th>

                                        <th class="w-[14%] px-3 py-3 text-center border-r border-slate-200">
                                            Wajaran
                                        </th>

                                        <th class="w-[14%] px-3 py-3 text-center border-r border-slate-200">
                                            %
                                        </th>

                                        <th class="w-[17%] px-3 py-3 text-center">
                                            Skor
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="divide-y divide-slate-100">

                                    @foreach($form->aspects as $aspect)

                                    @foreach($aspect->tums as $tums)

                                    <tr class="hover:bg-slate-50">

                                        <td class="px-3 py-2.5 text-center text-slate-600 border-r border-slate-100">
                                            {{ $aspect->aspect_code }}
                                        </td>

                                        <td class="px-3 py-2.5 text-slate-600 border-r border-slate-100">
                                            {{ $aspect->aspect_name }}
                                        </td>

                                        <td class="px-3 py-2.5 text-center text-slate-600 border-r border-slate-100">
                                            {{ $tums->tums_code }}
                                        </td>

                                        <td class="px-3 py-2.5 text-center text-slate-600 border-r border-slate-100">
                                            {{ number_format($tums->wajaran, 2) }}
                                        </td>

                                        <td
                                            id="summary-percentage-{{ $tums->tumsID }}"
                                            class="px-3 py-2.5 text-center text-slate-600 border-r border-slate-100">
                                            0.00
                                        </td>

                                        <td
                                            id="summary-score-{{ $tums->tumsID }}"
                                            class="px-3 py-2.5 text-center font-bold text-slate-900">
                                            0.00
                                        </td>

                                    </tr>

                                    @endforeach

                                    @endforeach

                                </tbody>


                                <tfoot>

                                    <tr class="bg-blue-50 border-t border-blue-200">

                                        <td colspan="5"
                                            class="px-3 py-3 text-right font-extrabold text-slate-900 border-r border-blue-100">
                                            Total
                                        </td>

                                        <td
                                            id="overall-weighted-score"
                                            class="px-3 py-3 text-center bg-blue-600 text-white font-extrabold">
                                            0.00
                                        </td>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>


                        {{-- Achievement Level --}}
                        <div class="overflow-hidden rounded-xl border border-slate-200 self-start">

                            <table class="w-full table-fixed text-sm">

                                <thead>

                                    <tr class="bg-blue-50 text-slate-900 uppercase text-[10px]">

                                        <th class="w-[45%] px-3 py-3 text-left border-r border-slate-200">
                                            Taraf
                                        </th>

                                        <th class="w-[38%] px-3 py-3 text-center border-r border-slate-200">
                                            Skor
                                        </th>

                                        <th class="w-[17%] px-3 py-3 text-center">
                                            ✓
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="divide-y divide-slate-100">

                                    <tr>
                                        <td class="px-3 py-3 text-slate-600 border-r border-slate-100">Cemerlang</td>
                                        <td class="px-3 py-3 text-center text-slate-600 border-r border-slate-100">90 - 100</td>
                                        <td id="achievement-cemerlang" class="px-3 py-3 text-center text-emerald-600 text-lg font-bold"></td>
                                    </tr>

                                    <tr>
                                        <td class="px-3 py-3 text-slate-600 border-r border-slate-100">Baik</td>
                                        <td class="px-3 py-3 text-center text-slate-600 border-r border-slate-100">80 - 89.99</td>
                                        <td id="achievement-baik" class="px-3 py-3 text-center text-blue-600 text-lg font-bold"></td>
                                    </tr>

                                    <tr>
                                        <td class="px-3 py-3 text-slate-600 border-r border-slate-100">Sederhana</td>
                                        <td class="px-3 py-3 text-center text-slate-600 border-r border-slate-100">50 - 79.99</td>
                                        <td id="achievement-sederhana" class="px-3 py-3 text-center text-amber-500 text-lg font-bold"></td>
                                    </tr>

                                    <tr>
                                        <td class="px-3 py-3 text-slate-600 border-r border-slate-100">Lemah</td>
                                        <td class="px-3 py-3 text-center text-slate-600 border-r border-slate-100">20 - 49.99</td>
                                        <td id="achievement-lemah" class="px-3 py-3 text-center text-orange-500 text-lg font-bold"></td>
                                    </tr>

                                    <tr>
                                        <td class="px-3 py-3 text-slate-600 border-r border-slate-100">Sangat Lemah</td>
                                        <td class="px-3 py-3 text-center text-slate-600 border-r border-slate-100">0 - 19.99</td>
                                        <td id="achievement-sangat-lemah" class="px-3 py-3 text-center text-red-600 text-lg font-bold"></td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                {{-- Actions --}}
                <div class="sticky bottom-4 z-40 mt-8">

                    <div class="bg-white/95 backdrop-blur-md border border-slate-200
                                shadow-lg rounded-2xl px-5 md:px-6 py-4">

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                            <a
                                href="{{ route(
                                    $role === 'observer'
                                        ? 'observer.manage'
                                        : 'external.manage',
                                    $gn_id
                                ) }}"
                                class="inline-flex items-center justify-center px-5 py-2.5
                                bg-slate-100 hover:bg-slate-200
                                border border-slate-300
                                text-slate-700 text-[13px] font-bold
                                rounded-[10px] transition">
                                Back
                            </a>


                            <div class="flex items-center gap-3">

                                <button
                                    type="submit"
                                    name="submit_action"
                                    value="Draft"
                                    class="app-button-outline">
                                    Save
                                </button>


                                <button
                                    type="submit"
                                    id="submitButton"
                                    name="submit_action"
                                    value="Submitted"
                                    disabled
                                    class="px-5 py-2.5 bg-slate-100 border border-slate-200
                                           text-slate-400 text-[13px] font-bold rounded-[10px]
                                           cursor-not-allowed transition">
                                    Submit
                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- ORIGINAL CALCULATION / FUNCTIONALITY --}}
    <script>
        function calculateTums(tumsID) {
            const inputs = document.querySelectorAll(`[data-tums="${tumsID}"]`);
            const totalPoints = inputs.length;

            let actionCount = 0;
            let qualityTotal = 0;

            inputs.forEach(input => {
                if (input.value === '') return;

                const score = Number(input.value);

                if (Number.isNaN(score) || score < 0 || score > 4) return;

                qualityTotal += score;

                if (score > 0) actionCount++;
            });

            const actionScore = totalPoints > 0 ?
                Math.round((actionCount / totalPoints) * 4) :
                0;

            const qualityMean = totalPoints > 0 ?
                qualityTotal / totalPoints :
                0;

            const roundedQualityMean =
                Math.round(qualityMean * 100) / 100;

            const actionPercentage =
                (actionScore / 4) * 100;

            const qualityPercentage =
                (roundedQualityMean / 4) * 100;

            const tumsPercentage =
                (actionPercentage * 0.25) +
                (qualityPercentage * 0.75);

            document.getElementById(`action-count-${tumsID}`).textContent =
                actionCount;

            document.getElementById(`quality-total-${tumsID}`).textContent =
                qualityTotal;

            document.getElementById(`action-score-${tumsID}`).textContent =
                actionScore;

            document.getElementById(`quality-mean-${tumsID}`).textContent =
                roundedQualityMean.toFixed(2);

            document.getElementById(`action-percentage-${tumsID}`).textContent =
                actionPercentage.toFixed(2);

            document.getElementById(`quality-percentage-${tumsID}`).textContent =
                qualityPercentage.toFixed(2);

            document.getElementById(`tums-percentage-${tumsID}`).textContent =
                tumsPercentage.toFixed(2);

            const container =
                document.getElementById(`tums-container-${tumsID}`);

            const weight =
                container ? Number(container.dataset.weight) : 0;

            const weightedScore =
                (tumsPercentage * weight) / 100;

            document.getElementById(`summary-percentage-${tumsID}`).textContent =
                tumsPercentage.toFixed(2);

            const summaryScore =
                document.getElementById(`summary-score-${tumsID}`);

            summaryScore.dataset.rawScore = weightedScore;
            summaryScore.textContent = weightedScore.toFixed(2);

            calculateOverallSummary();
        }


        function calculateOverallSummary() {
            let total = 0;

            document.querySelectorAll('[id^="summary-score-"]').forEach(element => {
                total += Number(element.dataset.rawScore) || 0;
            });

            total = Math.round(total * 100) / 100;

            document.getElementById('overall-weighted-score').textContent =
                total.toFixed(2);

            document.getElementById('achievement-cemerlang').textContent = '';
            document.getElementById('achievement-baik').textContent = '';
            document.getElementById('achievement-sederhana').textContent = '';
            document.getElementById('achievement-lemah').textContent = '';
            document.getElementById('achievement-sangat-lemah').textContent = '';

            if (total >= 90) {
                document.getElementById('achievement-cemerlang').textContent = '✓';
            } else if (total >= 80) {
                document.getElementById('achievement-baik').textContent = '✓';
            } else if (total >= 50) {
                document.getElementById('achievement-sederhana').textContent = '✓';
            } else if (total >= 20) {
                document.getElementById('achievement-lemah').textContent = '✓';
            } else {
                document.getElementById('achievement-sangat-lemah').textContent = '✓';
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            const form =
                document.getElementById('pdpcForm');

            const submitButton =
                document.getElementById('submitButton');

            const classInput =
                form.querySelector('[name="class_name"]');

            const subjectInput =
                form.querySelector('[name="subject_name"]');

            const dateInput =
                form.querySelector('[name="observation_date"]');

            const timeInput =
                form.querySelector('[name="observation_time"]');

            const scoreInputs =
                form.querySelectorAll('[data-tums]');


            function updateSubmitButton() {
                const informationComplete =
                    classInput.value.trim() !== '' &&
                    subjectInput.value.trim() !== '' &&
                    dateInput.value !== '' &&
                    timeInput.value !== '';

                const scoresComplete =
                    scoreInputs.length > 0 && [...scoreInputs].every(input => input.value !== '');

                const formComplete =
                    informationComplete &&
                    scoresComplete;

                if (formComplete) {
                    submitButton.disabled = false;

                    submitButton.className =
                        'px-5 py-2.5 bg-blue-600 hover:bg-blue-700 ' +
                        'text-white text-[13px] font-bold rounded-[10px] ' +
                        'cursor-pointer transition';
                } else {
                    submitButton.disabled = true;

                    submitButton.className =
                        'px-5 py-2.5 bg-slate-100 border border-slate-200 ' +
                        'text-slate-400 text-[13px] font-bold rounded-[10px] ' +
                        'cursor-not-allowed transition';
                }
            }


            const tumsIDs = new Set();

            scoreInputs.forEach(input => {
                tumsIDs.add(input.dataset.tums);
            });

            tumsIDs.forEach(tumsID => {
                calculateTums(tumsID);
            });


            form.addEventListener(
                'input',
                updateSubmitButton
            );

            form.addEventListener(
                'change',
                updateSubmitButton
            );

            updateSubmitButton();
        });
    </script>

</x-app-layout>