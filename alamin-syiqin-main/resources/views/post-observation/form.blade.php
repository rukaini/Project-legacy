<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-7xl mx-auto">

            {{-- Page heading --}}
            <div class="flex items-start gap-4 mb-7">

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
                        Post Observation
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


            {{-- Messages --}}
            @if(session('success'))

            <div class="mb-6 px-5 py-4 bg-emerald-50 border border-emerald-200
                        text-emerald-700 rounded-xl text-sm font-semibold">

                {{ session('success') }}

            </div>

            @endif


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
                action="{{ $role === 'observer'
                    ? route('observer.post.store', $gn_id)
                    : route('external.post.store', $gn_id) }}">

                @csrf

                {{-- Exact form version --}}
                <input type="hidden" name="formID" value="{{ $form->formID }}">


                {{-- Observation Information --}}
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
                                    Maklumat Penyeliaan
                                </h2>

                                <p class="text-[12.5px] text-slate-500 mt-0.5">
                                    Maklumat guru dan sesi pemerhatian.
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="p-5 md:p-6">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5
                                    pb-5 mb-5 border-b border-slate-100">

                            <div>

                                <p class="app-label">
                                    Nama Guru
                                </p>

                                <p class="text-[13.5px] font-extrabold text-slate-900 uppercase">
                                    {{ $guru->gn_name }}
                                </p>

                            </div>


                            <div>

                                <p class="app-label">
                                    Sekolah
                                </p>

                                <p class="text-[13.5px] font-extrabold text-slate-900">
                                    {{ $guru->school?->schoolName ?? '-' }}
                                </p>

                            </div>


                            <div>

                                <p class="app-label">
                                    Penyelia
                                </p>

                                <p class="text-[13.5px] font-extrabold text-slate-900 uppercase">
                                    {{ Auth::guard('teacher')->user()->teacherName }}
                                </p>

                            </div>

                        </div>


                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">

                            <div>

                                <label for="class_name" class="app-label">
                                    Kelas
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

                                <label for="subject_name" class="app-label">
                                    Subjek
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

                                <label for="observation_date" class="app-label">
                                    Tarikh
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


                            <div>

                                <label for="observation_time" class="app-label">
                                    Masa
                                </label>

                                <input
                                    type="time"
                                    id="observation_time"
                                    name="observation_time"
                                    value="{{ old('observation_time') }}"
                                    required
                                    class="app-input">

                                @error('observation_time')

                                <p class="text-red-500 text-xs mt-1">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Dynamic Sections --}}
                @foreach($form->sections as $section)

                @php
                $displayFields = $section->fields->where('field_type', 'display')->values();
                $inputFields = $section->fields->where('field_type', '!=', 'display')->values();
                @endphp


                {{-- Display Fields --}}
                @if($displayFields->isNotEmpty())

                <div class="app-section-card mb-6">

                    <div class="px-5 md:px-6 py-4 bg-blue-50 border-b border-blue-100">

                        <h2 class="text-[13px] font-extrabold text-blue-700 uppercase tracking-[0.05em]">
                            {{ $section->section_name }}
                        </h2>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="w-full table-fixed text-sm">

                            <tbody>

                                @foreach($displayFields->chunk(4) as $row)

                                <tr>

                                    @foreach($row as $field)

                                    <td class="border-r border-b border-slate-100
                                               px-5 py-4 text-[13px] text-slate-700 font-semibold align-middle
                                               {{ $loop->last && $row->count() === 4 ? 'border-r-0' : '' }}">

                                        {{ $field->field_label }}

                                    </td>

                                    @endforeach


                                    @if($row->count() < 4)

                                        @for($i=$row->count(); $i < 4; $i++)

                                            <td class="border-r border-b border-slate-100 px-5 py-4
                                                   {{ $i === 3 ? 'border-r-0' : '' }}">
                                            </td>

                                            @endfor

                                            @endif

                                </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

                @endif


                {{-- Input Fields --}}
                @if($inputFields->isNotEmpty())

                <div class="app-section-card mb-7">

                    <div class="px-5 md:px-6 py-4 bg-blue-50 border-b border-blue-100">

                        <h2 class="text-[13px] font-extrabold text-blue-700 uppercase tracking-[0.05em]">
                            {{ $section->section_name }}
                        </h2>

                    </div>


                    <div class="divide-y divide-slate-100">

                        @foreach($inputFields as $field)


                        {{-- Textarea --}}
                        @if($field->field_type === 'textarea')

                        <div class="px-5 md:px-6 py-5">

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start">

                                <div class="md:col-span-1">

                                    <label class="block text-[13px] text-slate-700 font-bold pt-2">

                                        {{ $field->field_label }}

                                        @if($field->is_required)

                                        <span class="text-red-500">*</span>

                                        @endif

                                    </label>

                                </div>


                                <div class="md:col-span-5">

                                    <textarea
                                        name="answers[{{ $field->fieldID }}]"
                                        rows="3"
                                        class="{{ $field->is_required ? 'post-required-field' : '' }} app-textarea">{{ old('answers.' . $field->fieldID) }}</textarea>

                                    @error('answers.' . $field->fieldID)

                                    <p class="text-red-500 text-xs mt-1">
                                        {{ $message }}
                                    </p>

                                    @enderror

                                </div>

                            </div>

                        </div>


                        {{-- Checkbox --}}
                        @elseif($field->field_type === 'checkbox')

                        <div class="px-5 md:px-6 py-5">

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start">

                                <div class="md:col-span-1">

                                    <label class="block text-[13px] text-slate-700 font-bold">

                                        {{ $field->field_label }}

                                        @if($field->is_required)

                                        <span class="text-red-500">*</span>

                                        @endif

                                    </label>

                                </div>


                                <div class="md:col-span-5">

                                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">

                                        @foreach($field->options as $option)

                                        <label class="inline-flex items-center gap-2.5 cursor-pointer">

                                            <input
                                                type="checkbox"
                                                name="answers[{{ $field->fieldID }}][]"
                                                value="{{ $option->option_label }}"
                                                {{ in_array($option->option_label, old('answers.' . $field->fieldID, [])) ? 'checked' : '' }}
                                                class="{{ $field->is_required ? 'post-required-field' : '' }}
                                                       rounded border-slate-300 text-blue-600 focus:ring-blue-500">

                                            <span class="text-[13px] font-medium text-slate-700">
                                                {{ $option->option_label }}
                                            </span>

                                        </label>

                                        @endforeach

                                    </div>

                                    @error('answers.' . $field->fieldID)

                                    <p class="text-red-500 text-xs mt-2">
                                        {{ $message }}
                                    </p>

                                    @enderror

                                </div>

                            </div>

                        </div>


                        {{-- Radio --}}
                        @elseif($field->field_type === 'radio')

                        <div class="px-5 md:px-6 py-5">

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start">

                                <div class="md:col-span-1">

                                    <label class="block text-[13px] text-slate-700 font-bold">

                                        {{ $field->field_label }}

                                        @if($field->is_required)

                                        <span class="text-red-500">*</span>

                                        @endif

                                    </label>

                                </div>


                                <div class="md:col-span-5">

                                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">

                                        @foreach($field->options as $option)

                                        <label class="inline-flex items-center gap-2.5 cursor-pointer">

                                            <input
                                                type="radio"
                                                name="answers[{{ $field->fieldID }}]"
                                                value="{{ $option->option_label }}"
                                                {{ old('answers.' . $field->fieldID) === $option->option_label ? 'checked' : '' }}
                                                class="{{ $field->is_required ? 'post-required-field' : '' }}
                                                       border-slate-300 text-blue-600 focus:ring-blue-500">

                                            <span class="text-[13px] font-medium text-slate-700">
                                                {{ $option->option_label }}
                                            </span>

                                        </label>

                                        @endforeach

                                    </div>

                                    @error('answers.' . $field->fieldID)

                                    <p class="text-red-500 text-xs mt-2">
                                        {{ $message }}
                                    </p>

                                    @enderror

                                </div>

                            </div>

                        </div>


                        {{-- Text --}}
                        @else

                        <div class="px-5 md:px-6 py-5">

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-center">

                                <label class="md:col-span-1 text-[13px] text-slate-700 font-bold">

                                    {{ $field->field_label }}

                                    @if($field->is_required)

                                    <span class="text-red-500">*</span>

                                    @endif

                                </label>


                                <div class="md:col-span-5">

                                    <input
                                        type="text"
                                        name="answers[{{ $field->fieldID }}]"
                                        value="{{ old('answers.' . $field->fieldID) }}"
                                        class="{{ $field->is_required ? 'post-required-field' : '' }} app-input">

                                    @error('answers.' . $field->fieldID)

                                    <p class="text-red-500 text-xs mt-1">
                                        {{ $message }}
                                    </p>

                                    @enderror

                                </div>

                            </div>

                        </div>

                        @endif

                        @endforeach

                    </div>

                </div>

                @endif

                @endforeach


                {{-- Action Bar --}}
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


    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const classInput = document.getElementById('class_name');
            const subjectInput = document.getElementById('subject_name');
            const dateInput = document.getElementById('observation_date');
            const timeInput = document.getElementById('observation_time');
            const submitButton = document.getElementById('submitButton');
            const requiredFields = document.querySelectorAll('.post-required-field');

            // Check whether all required fields are completed
            function updateSubmitButton() {

                const informationComplete =
                    classInput.value.trim() !== '' &&
                    subjectInput.value.trim() !== '' &&
                    dateInput.value !== '' &&
                    timeInput.value !== '';

                let dynamicFieldsComplete = true;
                const checkedGroups = new Set();

                requiredFields.forEach(function(field) {

                    // Check radio and checkbox groups
                    if (field.type === 'radio' || field.type === 'checkbox') {

                        if (checkedGroups.has(field.name)) return;

                        checkedGroups.add(field.name);

                        let groupComplete = false;

                        requiredFields.forEach(function(groupField) {
                            if (
                                groupField.name === field.name &&
                                groupField.checked
                            ) {
                                groupComplete = true;
                            }
                        });

                        if (!groupComplete) {
                            dynamicFieldsComplete = false;
                        }

                        return;
                    }

                    // Check text and textarea fields
                    if (field.value.trim() === '') {
                        dynamicFieldsComplete = false;
                    }
                });

                const formComplete =
                    informationComplete &&
                    dynamicFieldsComplete;

                // Enable or disable Submit
                if (formComplete) {

                    submitButton.disabled = false;

                    submitButton.className =
                        'px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[13px] rounded-[10px] shadow-sm cursor-pointer transition';

                } else {

                    submitButton.disabled = true;

                    submitButton.className =
                        'px-5 py-2.5 bg-slate-100 border border-slate-200 text-slate-400 font-bold text-[13px] rounded-[10px] cursor-not-allowed transition';
                }
            }

            // Listen to observation information
            classInput.addEventListener('input', updateSubmitButton);
            subjectInput.addEventListener('input', updateSubmitButton);
            dateInput.addEventListener('change', updateSubmitButton);
            timeInput.addEventListener('change', updateSubmitButton);

            // Listen to required dynamic fields
            requiredFields.forEach(function(field) {

                field.addEventListener(
                    field.type === 'radio' || field.type === 'checkbox' ?
                    'change' :
                    'input',
                    updateSubmitButton
                );
            });

            // Check form on initial load
            updateSubmitButton();

        });
    </script>

</x-app-layout>