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
                                d="M16.862 3.487a2.1 2.1 0 013 2.97L8.4 17.92
                                   4 19l1.08-4.4L16.862 3.487z" />

                        </svg>

                    </div>


                    <div>

                        <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                            {{ $form->form_name }}
                        </h1>

                        <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                            Manage form information, sections and criteria.
                        </p>

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


                    @if($formUsed)

                    <div class="border border-amber-200 bg-amber-50 rounded-xl px-4 py-3
                                    flex items-center gap-3">

                        <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 text-amber-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />

                            </svg>

                        </div>

                        <div>

                            <p class="text-[12px] font-extrabold text-amber-800">
                                Form in use
                            </p>

                            <p class="text-[11px] text-amber-600 mt-0.5">
                                Only criteria/section name can be change
                            </p>

                        </div>

                    </div>

                    @else

                    <div class="border border-blue-200 bg-blue-50 rounded-xl px-4 py-3
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
                                    d="M5 13l4 4L19 7" />

                            </svg>

                        </div>

                        <div>

                            <p class="text-[12px] font-extrabold text-blue-800">
                                Current Form
                            </p>

                            <p class="text-[11px] text-blue-600 mt-0.5">
                                Form content can be change
                            </p>

                        </div>

                    </div>

                    @endif

                </div>

            </div>


            @if(session('success'))

            <div class="mb-5 px-4 py-3 bg-emerald-50 border border-emerald-200
                            text-emerald-700 rounded-xl text-sm font-semibold">
                {{ session('success') }}
            </div>

            @endif


            @if(session('error'))

            <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200
                            text-red-700 rounded-xl text-sm font-semibold">
                {{ session('error') }}
            </div>

            @endif


            @if($errors->any())

            <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200
                            text-red-700 rounded-xl">

                <ul class="list-disc list-inside text-sm">

                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

            @endif


            {{-- Form Information --}}
            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100
                            flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div>

                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            Form Information
                        </h2>

                        <p class="text-[12.5px] text-slate-500 mt-1">
                            Basic form details and scoring.
                        </p>

                    </div>


                    <div class="flex flex-wrap items-center gap-3">

                        <div class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-200">

                            <span class="text-[11px] text-slate-400">
                                Criteria
                            </span>

                            <span class="font-extrabold text-slate-800 ml-1 text-[12px]">
                                {{ $criteriaCount }}
                            </span>

                        </div>

                        <div class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-200">

                            <span class="text-[11px] text-slate-400">
                                Score Range
                            </span>

                            <span class="font-extrabold text-slate-800 ml-1 text-[12px]">
                                {{ $form->min_score }} - {{ $form->max_score }}
                            </span>

                        </div>

                        <div class="px-3 py-2 rounded-lg bg-amber-50 border border-amber-100">

                            <span class="text-[11px] text-amber-600">
                                Maximum Total
                            </span>

                            <span class="font-extrabold text-amber-600 ml-1 text-[12px]">
                                {{ $maxTotal }}
                            </span>

                        </div>

                    </div>

                </div>


                <div class="p-5 md:p-6">

                    <form
                        id="formInformation"
                        method="POST"
                        action="{{ route('admin.pre.form.update', $form->formID) }}">

                        @csrf
                        @method('PUT')


                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                            <div class="lg:col-span-4">

                                <label class="app-label">
                                    Form Name
                                </label>

                                <input
                                    type="text"
                                    name="form_name"
                                    value="{{ old('form_name', $form->form_name) }}"
                                    required
                                    class="app-input">

                            </div>


                            <div class="lg:col-span-4">

                                <label class="app-label">
                                    Instruction
                                </label>

                                <input
                                    type="text"
                                    name="instruction"
                                    value="{{ old('instruction', $form->instruction) }}"
                                    class="app-input">

                            </div>


                            <div class="lg:col-span-2">

                                <label class="app-label">
                                    Minimum Score
                                </label>

                                @if($formUsed)

                                <div class="h-[42px] px-4 flex items-center
                                                bg-slate-50 border border-slate-200
                                                rounded-[10px] text-slate-500 text-sm">

                                    {{ $form->min_score }}

                                </div>

                                @else

                                <input
                                    type="number"
                                    name="min_score"
                                    value="{{ old('min_score', $form->min_score) }}"
                                    min="0"
                                    required
                                    class="app-input">

                                @endif

                            </div>


                            <div class="lg:col-span-2">

                                <label class="app-label">
                                    Maximum Score
                                </label>

                                @if($formUsed)

                                <div class="h-[42px] px-4 flex items-center
                                                bg-slate-50 border border-slate-200
                                                rounded-[10px] text-slate-500 text-sm">

                                    {{ $form->max_score }}

                                </div>

                                @else

                                <input
                                    type="number"
                                    name="max_score"
                                    value="{{ old('max_score', $form->max_score) }}"
                                    min="1"
                                    required
                                    class="app-input">

                                @endif

                            </div>

                        </div>

                    </form>

                </div>

            </div>


            {{-- Form Content --}}
            <div class="app-section-card mb-24">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100
                            flex items-center justify-between gap-4">

                    <div>

                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            Form Content
                        </h2>

                        <p class="text-[12.5px] text-slate-500 mt-1">
                            Manage sections and criteria for this version.
                        </p>

                    </div>


                    <p class="text-[12px] text-slate-500 whitespace-nowrap">
                        {{ $sectionCount }} Sections · {{ $criteriaCount }} Criteria
                    </p>

                </div>


                @forelse($form->sections as $section)

                <div class="border-b border-slate-100">

                    {{-- Section heading --}}
                    <div class="px-5 md:px-6 py-5 bg-blue-50 border-b border-blue-100">

                        <div class="flex items-center justify-between gap-4">

                            <div class="flex items-center gap-4">

                                <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100
                                                text-blue-600 flex items-center justify-center font-extrabold">

                                    {{ $loop->iteration }}

                                </div>


                                <div>

                                    <p class="font-extrabold text-slate-900 text-[14px]">
                                        {{ $section->section_name }}
                                    </p>

                                    <p class="text-[10px] uppercase tracking-[0.06em] text-slate-400 mt-1 font-bold">
                                        Section
                                    </p>

                                </div>

                            </div>


                            <div class="flex items-center gap-2">

                                <button
                                    type="button"
                                    data-section-id="{{ $section->sectionID }}"
                                    onclick="toggleSectionEdit(this)"
                                    class="app-button-outline">

                                    Edit

                                </button>


                                @if(!$formUsed)

                                <form
                                    method="POST"
                                    action="{{ route('admin.pre.section.delete', $section->sectionID) }}">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        onclick="return confirm('Delete this section and all its criteria?')"
                                        class="w-9 h-9 inline-flex items-center justify-center
                                                       border border-red-200 bg-red-50 hover:bg-red-100
                                                       text-red-600 rounded-lg transition">

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5" />

                                        </svg>

                                    </button>

                                </form>

                                @endif

                            </div>

                        </div>

                    </div>


                    {{-- Section edit --}}
                    <div
                        id="section-edit-{{ $section->sectionID }}"
                        class="hidden px-5 md:px-6 py-5 bg-blue-50/50 border-t border-blue-100">

                        <form
                            method="POST"
                            action="{{ route('admin.pre.section.update', $section->sectionID) }}">

                            @csrf
                            @method('PUT')


                            <div class="flex items-end gap-4">

                                <div class="flex-1">

                                    <label class="app-label">
                                        Section Name
                                    </label>

                                    <input
                                        type="text"
                                        name="section_name"
                                        value="{{ $section->section_name }}"
                                        required
                                        class="app-input">

                                </div>


                                <button
                                    type="submit"
                                    class="app-button-primary">

                                    Update

                                </button>

                            </div>

                        </form>

                    </div>


                    {{-- Criteria --}}
                    <div class="divide-y divide-slate-100">

                        @foreach($section->criteria as $criteria)

                        <div class="px-5 md:px-6 py-4">

                            <div class="flex items-center justify-between gap-5">

                                <div class="flex items-center gap-4 flex-1">

                                    <span class="w-8 text-center text-[12px] font-semibold text-slate-400">
                                        {{ $loop->iteration }}
                                    </span>

                                    <p class="text-[13px] font-semibold text-slate-700">
                                        {{ $criteria->criteria_label }}
                                    </p>

                                </div>


                                <div class="flex items-center gap-2">

                                    <button
                                        type="button"
                                        data-criteria-id="{{ $criteria->criteriaID }}"
                                        onclick="toggleCriteriaEdit(this)"
                                        class="app-button-outline">

                                        Edit

                                    </button>


                                    @if(!$formUsed)

                                    <form
                                        method="POST"
                                        action="{{ route('admin.pre.criteria.delete', $criteria->criteriaID) }}">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Delete this criteria?')"
                                            class="w-9 h-9 inline-flex items-center justify-center
                                                               border border-red-200 bg-red-50 hover:bg-red-100
                                                               text-red-600 rounded-lg transition">

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="w-4 h-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2">

                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5" />

                                            </svg>

                                        </button>

                                    </form>

                                    @endif

                                </div>

                            </div>


                            <div
                                id="criteria-edit-{{ $criteria->criteriaID }}"
                                class="hidden mt-4 ml-12 p-4 bg-slate-50 border border-slate-100 rounded-xl">

                                <form
                                    method="POST"
                                    action="{{ route('admin.pre.criteria.update', $criteria->criteriaID) }}">

                                    @csrf
                                    @method('PUT')


                                    <div class="flex items-end gap-4">

                                        <div class="flex-1">

                                            <label class="app-label">
                                                Criteria
                                            </label>

                                            <input
                                                type="text"
                                                name="criteria_label"
                                                value="{{ $criteria->criteria_label }}"
                                                required
                                                class="app-input">

                                        </div>


                                        <button
                                            type="submit"
                                            class="app-button-primary">

                                            Update

                                        </button>

                                    </div>

                                </form>

                            </div>

                        </div>

                        @endforeach

                    </div>


                    @if(!$formUsed)

                    <div class="px-5 md:px-6 py-4 bg-slate-50/50 border-t border-slate-100">

                        <details>

                            <summary class="cursor-pointer text-blue-600 hover:text-blue-700
                                                    font-bold text-[12.5px]">

                                + Add Criteria

                            </summary>


                            <form
                                method="POST"
                                action="{{ route('admin.pre.criteria.store') }}"
                                class="mt-4">

                                @csrf

                                <input
                                    type="hidden"
                                    name="sectionID"
                                    value="{{ $section->sectionID }}">


                                <div class="flex items-end gap-4">

                                    <div class="flex-1">

                                        <label class="app-label">
                                            Criteria
                                        </label>

                                        <input
                                            type="text"
                                            name="criteria_label"
                                            required
                                            class="app-input">

                                    </div>


                                    <button
                                        type="submit"
                                        class="app-button-primary">

                                        Add

                                    </button>

                                </div>

                            </form>

                        </details>

                    </div>

                    @endif

                </div>

                @empty

                <div class="py-12 text-center text-slate-400">
                    No sections added
                </div>

                @endforelse


                @if(!$formUsed)

                <div class="px-5 md:px-6 py-5 bg-blue-50 border-b border-blue-100">

                    <details>

                        <summary class="cursor-pointer text-blue-600 hover:text-blue-700
                                            font-bold text-[12.5px]">

                            + Add Section

                        </summary>


                        <form
                            method="POST"
                            action="{{ route('admin.pre.section.store') }}"
                            class="mt-4">

                            @csrf

                            <input
                                type="hidden"
                                name="formID"
                                value="{{ $form->formID }}">


                            <div class="flex items-end gap-4">

                                <div class="flex-1">

                                    <label class="app-label">
                                        Section Name
                                    </label>

                                    <input
                                        type="text"
                                        name="section_name"
                                        required
                                        class="app-input">

                                </div>


                                <button
                                    type="submit"
                                    class="app-button-primary">

                                    Add

                                </button>

                            </div>

                        </form>

                    </details>

                </div>

                @endif

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


                    <div class="flex items-center gap-3">

                        <a
                            href="{{ route('admin.pre.form.preview', $form->formID) }}"
                            class="app-button-outline">

                            Preview

                        </a>


                        <button
                            type="submit"
                            form="formInformation"
                            class="app-button-primary">

                            Save

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <script>
        function toggleSectionEdit(button) {
            const row = document.getElementById('section-edit-' + button.dataset.sectionId);
            if (row) row.classList.toggle('hidden');
        }

        function toggleCriteriaEdit(button) {
            const row = document.getElementById('criteria-edit-' + button.dataset.criteriaId);
            if (row) row.classList.toggle('hidden');
        }
    </script>

</x-app-layout>