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
                                d="M9 12h6m-6 4h6m2.25 5H6.75A2.25 2.25 0 014.5 18.75V5.25A2.25 2.25 0 016.75 3h6.879c.597 0 1.17.237 1.591.659l3.621 3.621c.422.422.659.994.659 1.591v9.879A2.25 2.25 0 0117.25 21z" />

                        </svg>

                    </div>


                    <div>

                        <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-blue-600 mb-1">
                            Pre Observation
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


                <div class="bg-white border border-slate-200 rounded-xl px-4 py-3
                            min-w-[105px] shadow-sm">

                    <p class="text-[10px] uppercase tracking-[0.06em] text-slate-400 font-bold">
                        Version
                    </p>

                    <p class="text-[18px] font-extrabold text-slate-900 mt-1">
                        {{ $form->version }}
                    </p>

                </div>

            </div>


            {{-- Validation errors --}}
            @if($errors->any())

            <div class="mb-6 px-5 py-4 bg-red-50 border border-red-200
                            text-red-700 rounded-xl">

                <ul class="list-disc list-inside text-sm space-y-1">

                    @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

            @endif


            <form
                method="POST"
                action="{{ route('observer.pre.store', $gn_id) }}">

                @csrf

                {{-- Exact form version --}}
                <input
                    type="hidden"
                    name="formID"
                    value="{{ $form->formID }}">


                {{-- Observer Information --}}
                <div class="app-section-card mb-7">

                    <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                        <div class="flex items-center gap-3">

                            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100
                                        flex items-center justify-center shrink-0">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="w-[18px] h-[18px] text-blue-600">

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />

                                </svg>

                            </div>


                            <div>

                                <h2 class="text-[17px] font-extrabold text-slate-900">
                                    Observer Information
                                </h2>

                                <p class="text-[12.5px] text-slate-500 mt-0.5">
                                    Teacher and observation details.
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="p-5 md:p-6">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5
                                    pb-5 mb-5 border-b border-slate-100">

                            <div>

                                <p class="app-label">
                                    Teacher Name
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
                                    Observer Name
                                </p>

                                <p class="text-[13.5px] font-extrabold text-slate-900 uppercase">
                                    {{ Auth::guard('teacher')->user()->teacherName }}
                                </p>

                            </div>

                        </div>


                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                            <div>

                                <label
                                    for="class_name"
                                    class="app-label">
                                    Class
                                </label>

                                <input
                                    type="text"
                                    id="class_name"
                                    name="class_name"
                                    value="{{ old('class_name') }}"
                                    required
                                    class="app-input">

                                @error('class_name')

                                <p class="text-red-500 text-xs mt-1">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>


                            <div>

                                <label
                                    for="subject_name"
                                    class="app-label">
                                    Subject
                                </label>

                                <input
                                    type="text"
                                    id="subject_name"
                                    name="subject_name"
                                    value="{{ old('subject_name') }}"
                                    required
                                    class="app-input">

                                @error('subject_name')

                                <p class="text-red-500 text-xs mt-1">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>


                            <div>

                                <label
                                    for="observation_date"
                                    class="app-label">
                                    Date
                                </label>

                                <input
                                    type="date"
                                    id="observation_date"
                                    name="observation_date"
                                    value="{{ old('observation_date') }}"
                                    required
                                    class="app-input">

                                @error('observation_date')

                                <p class="text-red-500 text-xs mt-1">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Evaluation --}}
                <div class="app-section-card mb-7">

                    <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            Evaluation
                        </h2>

                        <p class="text-[12.5px] text-slate-500 mt-1">
                            Select a score for each criterion and provide a comment for every section.
                        </p>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="w-full text-sm">

                            <thead class="bg-blue-50 border-b border-blue-100">

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

                                {{-- Section --}}
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


                                <tr class="hover:bg-slate-50/60 transition">

                                    <td class="px-5 py-5"></td>


                                    <td class="px-5 py-5 text-[13px] text-slate-700 align-middle">

                                        <span class="font-bold text-slate-500">
                                            {{ $letter }}.
                                        </span>

                                        {{ $criteria->criteria_label }}

                                    </td>


                                    {{-- Dynamic score --}}
                                    <td class="px-5 py-5 align-middle">

                                        <div class="flex items-center justify-center gap-4 flex-wrap">

                                            @for($score = $form->min_score; $score <= $form->max_score; $score++)

                                                <label class="flex items-center gap-1.5 cursor-pointer">

                                                    <input
                                                        type="radio"
                                                        name="scores[{{ $criteria->criteriaID }}]"
                                                        value="{{ $score }}"
                                                        {{ old('scores.' . $criteria->criteriaID) == $score ? 'checked' : '' }}
                                                        class="pre-score text-blue-600 border-slate-300 focus:ring-blue-500">

                                                    <span class="text-[12px] font-bold text-slate-600">
                                                        {{ $score }}
                                                    </span>

                                                </label>

                                                @endfor

                                        </div>


                                        @error('scores.' . $criteria->criteriaID)

                                        <p class="text-red-500 text-xs mt-2 text-center">
                                            {{ $message }}
                                        </p>

                                        @enderror

                                    </td>


                                    {{-- One comment per section --}}
                                    @if($loop->first)

                                    <td
                                        rowspan="{{ $section->criteria->count() }}"
                                        class="px-5 py-5 align-middle border-l border-slate-100">

                                        <textarea
                                            name="section_comments[{{ $section->sectionID }}]"
                                            rows="5"
                                            placeholder="Enter comment..."
                                            class="section-comment app-textarea">{{ old('section_comments.' . $section->sectionID) }}</textarea>

                                        @error('section_comments.' . $section->sectionID)

                                        <p class="text-red-500 text-xs mt-1">
                                            {{ $message }}
                                        </p>

                                        @enderror

                                    </td>

                                    @endif

                                </tr>

                                @endforeach

                                @endif

                                @endforeach

                            </tbody>


                            {{-- Total --}}
                            <tfoot>

                                <tr class="bg-blue-50 border-t border-blue-200">

                                    <td
                                        colspan="2"
                                        class="px-5 py-4 text-right text-[11px]
                                               font-extrabold uppercase tracking-[0.05em]
                                               text-slate-700">

                                        Total

                                    </td>


                                    <td class="px-5 py-4 text-center text-sm">

                                        <span
                                            id="totalScore"
                                            class="font-extrabold text-slate-900">
                                            0
                                        </span>

                                        <span class="text-slate-400">
                                            /
                                        </span>

                                        <span
                                            id="maximumScore"
                                            data-max-score="{{ $form->max_score }}"
                                            class="font-semibold text-slate-500">

                                            0

                                        </span>

                                    </td>


                                    <td class="px-5 py-4 text-center text-sm">

                                        <span class="font-extrabold uppercase text-slate-700">
                                            Percentage :
                                        </span>

                                        <span
                                            id="percentage"
                                            class="ml-2 font-extrabold text-blue-600">

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
                            name="other_comment"
                            rows="4"
                            placeholder="Enter comment..."
                            class="app-textarea">{{ old('other_comment') }}</textarea>

                    </div>

                </div>


                {{-- Achievement Level --}}
                <div class="app-section-card mb-7">

                    <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            Achievement Level
                        </h2>

                        <p class="text-[12.5px] text-slate-500 mt-1">
                            The achievement level will be highlighted after all scores are completed.
                        </p>

                    </div>


                    <div class="p-5 md:p-6">

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">

                            <div
                                id="level-weak"
                                class="achievement-level bg-slate-50 border border-slate-200
                                       rounded-xl px-5 py-3 text-center transition">

                                <p class="font-bold text-slate-800 text-[12px]">
                                    Weak
                                </p>

                                <p class="text-[11px] text-slate-500 mt-1">
                                    0 - 39%
                                </p>

                            </div>


                            <div
                                id="level-satisfactory"
                                class="achievement-level bg-slate-50 border border-slate-200
                                       rounded-xl px-5 py-3 text-center transition">

                                <p class="font-bold text-slate-800 text-[12px]">
                                    Satisfactory
                                </p>

                                <p class="text-[11px] text-slate-500 mt-1">
                                    40 - 59%
                                </p>

                            </div>


                            <div
                                id="level-good"
                                class="achievement-level bg-slate-50 border border-slate-200
                                       rounded-xl px-5 py-3 text-center transition">

                                <p class="font-bold text-slate-800 text-[12px]">
                                    Good
                                </p>

                                <p class="text-[11px] text-slate-500 mt-1">
                                    60 - 79%
                                </p>

                            </div>


                            <div
                                id="level-very-good"
                                class="achievement-level bg-slate-50 border border-slate-200
                                       rounded-xl px-5 py-3 text-center transition">

                                <p class="font-bold text-slate-800 text-[12px]">
                                    Very Good
                                </p>

                                <p class="text-[11px] text-slate-500 mt-1">
                                    80 - 89%
                                </p>

                            </div>


                            <div
                                id="level-excellent"
                                class="achievement-level bg-slate-50 border border-slate-200
                                       rounded-xl px-5 py-3 text-center transition">

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


                {{-- Action Bar --}}
                <div class="sticky bottom-4 z-40 mt-8">

                    <div class="bg-white/95 backdrop-blur-md border border-slate-200
                shadow-lg rounded-2xl px-5 md:px-6 py-4">

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                            <a
                                href="{{ route('observer.manage', $gn_id) }}"
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
                           text-slate-400 font-bold text-[13px]
                           rounded-[10px] cursor-not-allowed transition">

                                    Submit

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- Live Calculation --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const classInput = document.getElementById('class_name');
            const subjectInput = document.getElementById('subject_name');
            const dateInput = document.getElementById('observation_date');

            const scoreInputs = document.querySelectorAll('.pre-score');
            const sectionComments = document.querySelectorAll('.section-comment');

            const submitButton = document.getElementById('submitButton');
            const totalScoreElement = document.getElementById('totalScore');
            const maximumScoreElement = document.getElementById('maximumScore');
            const percentageElement = document.getElementById('percentage');

            const maxScore = Number(maximumScoreElement.dataset.maxScore);

            function resetAchievementLevels() {

                document.querySelectorAll('.achievement-level').forEach(function(level) {

                    level.classList.remove(
                        'bg-red-100',
                        'border-red-400',
                        'bg-orange-100',
                        'border-orange-400',
                        'bg-yellow-100',
                        'border-yellow-400',
                        'bg-blue-100',
                        'border-blue-400',
                        'bg-emerald-100',
                        'border-emerald-400',
                        'border-2'
                    );

                    level.classList.add(
                        'bg-slate-50',
                        'border',
                        'border-slate-200'
                    );
                });
            }

            function highlightAchievementLevel(percentage) {

                let selectedLevel = null;
                let backgroundClass = '';
                let borderClass = '';

                if (percentage < 40) {
                    selectedLevel = document.getElementById('level-weak');
                    backgroundClass = 'bg-red-100';
                    borderClass = 'border-red-400';

                } else if (percentage < 60) {
                    selectedLevel = document.getElementById('level-satisfactory');
                    backgroundClass = 'bg-orange-100';
                    borderClass = 'border-orange-400';

                } else if (percentage < 80) {
                    selectedLevel = document.getElementById('level-good');
                    backgroundClass = 'bg-yellow-100';
                    borderClass = 'border-yellow-400';

                } else if (percentage < 90) {
                    selectedLevel = document.getElementById('level-very-good');
                    backgroundClass = 'bg-blue-100';
                    borderClass = 'border-blue-400';

                } else {
                    selectedLevel = document.getElementById('level-excellent');
                    backgroundClass = 'bg-emerald-100';
                    borderClass = 'border-emerald-400';
                }

                if (selectedLevel) {

                    selectedLevel.classList.remove(
                        'bg-slate-50',
                        'border-slate-200'
                    );

                    selectedLevel.classList.add(
                        backgroundClass,
                        borderClass,
                        'border-2'
                    );
                }
            }

            function updateForm() {

                let totalScore = 0;

                const selectedScores =
                    document.querySelectorAll('.pre-score:checked');

                selectedScores.forEach(function(input) {
                    totalScore += Number(input.value);
                });

                const criteriaNames = new Set();

                scoreInputs.forEach(function(input) {
                    criteriaNames.add(input.name);
                });

                const totalCriteria = criteriaNames.size;
                const maximumScore = totalCriteria * maxScore;

                let percentage = 0;

                if (maximumScore > 0) {
                    percentage = (totalScore / maximumScore) * 100;
                }

                totalScoreElement.textContent = totalScore;
                maximumScoreElement.textContent = maximumScore;
                percentageElement.textContent = percentage.toFixed(1) + '%';

                const scoresComplete =
                    totalCriteria > 0 &&
                    selectedScores.length === totalCriteria;

                resetAchievementLevels();

                if (scoresComplete) {
                    highlightAchievementLevel(percentage);
                }

                const informationComplete =
                    classInput.value.trim() !== '' &&
                    subjectInput.value.trim() !== '' &&
                    dateInput.value !== '';

                let commentsComplete = true;

                sectionComments.forEach(function(comment) {
                    if (comment.value.trim() === '') {
                        commentsComplete = false;
                    }
                });

                const formComplete =
                    informationComplete &&
                    scoresComplete &&
                    commentsComplete;

                if (formComplete) {

                    submitButton.disabled = false;

                    submitButton.className =
                        'px-5 py-2.5 ' +
                        'bg-blue-600 hover:bg-blue-700 ' +
                        'text-white font-bold text-[13px] ' +
                        'rounded-[10px] shadow-sm cursor-pointer transition';

                } else {

                    submitButton.disabled = true;

                    submitButton.className =
                        'px-5 py-2.5 bg-slate-100 border border-slate-200 ' +
                        'text-slate-400 font-bold text-[13px] ' +
                        'rounded-[10px] cursor-not-allowed transition';
                }
            }

            classInput.addEventListener('input', updateForm);
            subjectInput.addEventListener('input', updateForm);
            dateInput.addEventListener('change', updateForm);

            scoreInputs.forEach(function(input) {
                input.addEventListener('change', updateForm);
            });

            sectionComments.forEach(function(comment) {
                comment.addEventListener('input', updateForm);
            });

            updateForm();

        });
    </script>

</x-app-layout>