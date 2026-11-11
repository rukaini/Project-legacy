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
                            Manage form information, sections and fields.
                        </p>

                    </div>

                </div>


                <div class="flex flex-wrap items-stretch gap-3">

                    <div class="min-w-[105px] bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm">

                        <p class="text-[10px] font-bold uppercase tracking-[0.06em] text-slate-400">
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
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0
                                           9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />

                            </svg>

                        </div>

                        <div>

                            <p class="text-[12px] font-extrabold text-amber-800">
                                Form in used
                            </p>

                            <p class="text-[11px] text-amber-600 mt-0.5">
                                Only section/field name can be change
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


            @php
            $sectionCount = $form->sections->count();

            $fieldCount = $form->sections->sum(function ($section) {
            return $section->fields->count();
            });

            $inputCount = $form->sections->sum(function ($section) {
            return $section->fields->where('field_type', '!=', 'display')->count();
            });
            @endphp


            {{-- Form Information --}}
            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100
                            flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                    <div>

                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            Form Information
                        </h2>

                        <p class="text-[12.5px] text-slate-500 mt-1">
                            Update form name and instruction.
                        </p>

                    </div>


                    <div class="flex items-center gap-3">

                        <div class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-200">

                            <span class="text-[11px] text-slate-400">
                                Sections
                            </span>

                            <span class="text-[12px] font-extrabold text-slate-800 ml-1">
                                {{ $sectionCount }}
                            </span>

                        </div>

                        <div class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-200">

                            <span class="text-[11px] text-slate-400">
                                Fields
                            </span>

                            <span class="text-[12px] font-extrabold text-slate-800 ml-1">
                                {{ $fieldCount }}
                            </span>

                        </div>

                    </div>

                </div>


                <div class="p-5 md:p-6">

                    <form
                        id="formInformation"
                        method="POST"
                        action="{{ route('admin.post.form.update', $form->formID) }}">

                        @csrf
                        @method('PUT')


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            <div>

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


                            <div>

                                <label class="app-label">
                                    Instruction
                                </label>

                                <input
                                    type="text"
                                    name="instruction"
                                    value="{{ old('instruction', $form->instruction) }}"
                                    class="app-input">

                            </div>

                        </div>

                    </form>

                </div>

            </div>


            {{-- Sections --}}
            <div class="space-y-5 mb-8">

                @forelse($form->sections as $section)

                <div class="app-section-card">

                    {{-- Section header --}}
                    <div class="px-5 md:px-6 py-4 bg-slate-50 border-b border-slate-200">

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                            <div>

                                <p class="text-[10px] uppercase tracking-[0.07em] text-blue-600 font-bold">
                                    Section {{ $loop->iteration }}
                                </p>

                                <h2 class="text-[16px] font-extrabold text-slate-900 mt-1">
                                    {{ $section->section_name }}
                                </h2>

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
                                    action="{{ route('admin.post.section.delete', $section->sectionID) }}">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        onclick="return confirm('Delete this section and all its fields?')"
                                        title="Delete Section"
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
                                                d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" />

                                        </svg>

                                    </button>

                                </form>

                                @endif

                            </div>

                        </div>

                    </div>


                    {{-- Edit section --}}
                    <div
                        id="section-edit-{{ $section->sectionID }}"
                        class="hidden p-5 bg-blue-50/50 border-b border-blue-100">

                        <form
                            method="POST"
                            action="{{ route('admin.post.section.update', $section->sectionID) }}">

                            @csrf
                            @method('PUT')


                            <div class="flex flex-col md:flex-row gap-4 items-end">

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

                                    @if($formUsed)

                                    <p class="text-[11px] text-amber-600 mt-2">
                                        Wording correction only.
                                    </p>

                                    @endif

                                </div>


                                <button
                                    type="submit"
                                    class="app-button-primary">

                                    Update

                                </button>

                            </div>

                        </form>

                    </div>


                    {{-- Fields --}}
                    <div class="app-table-wrap">

                        <table class="app-table">

                            <thead class="bg-blue-50">

                                <tr class="[&>th]:!bg-blue-50 [&>th]:!text-slate-700">

                                    <th class="w-20">
                                        No
                                    </th>

                                    <th>
                                        Field
                                    </th>

                                    <th class="text-center w-32">
                                        Type
                                    </th>

                                    <th class="text-center w-28">
                                        Required
                                    </th>

                                    <th class="text-center w-44">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse($section->fields as $field)

                                <tr>

                                    <td class="text-slate-500">
                                        {{ $loop->iteration }}
                                    </td>


                                    <td>

                                        <p class="font-semibold text-slate-800">
                                            {{ $field->field_label }}
                                        </p>

                                        @if($field->options->isNotEmpty())

                                        <div class="flex flex-wrap gap-2 mt-2">

                                            @foreach($field->options as $option)

                                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px]">
                                                {{ $option->option_label }}
                                            </span>

                                            @endforeach

                                        </div>

                                        @endif

                                    </td>


                                    <td class="text-center">

                                        <span class="app-badge app-badge-info">
                                            {{ ucfirst($field->field_type) }}
                                        </span>

                                    </td>


                                    <td class="text-center">

                                        @if($field->field_type === 'display')

                                        <span class="text-slate-400">
                                            —
                                        </span>

                                        @elseif($field->is_required)

                                        <span class="font-bold text-emerald-600 text-[12px]">
                                            Yes
                                        </span>

                                        @else

                                        <span class="text-slate-500 text-[12px]">
                                            No
                                        </span>

                                        @endif

                                    </td>


                                    <td class="text-center">

                                        <div class="inline-flex items-center gap-2">

                                            <button
                                                type="button"
                                                data-field-id="{{ $field->fieldID }}"
                                                onclick="toggleFieldEdit(this)"
                                                class="app-button-outline">

                                                Edit

                                            </button>


                                            @if(!$formUsed)

                                            <form
                                                method="POST"
                                                action="{{ route('admin.post.field.delete', $field->fieldID) }}">

                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Delete this field?')"
                                                    title="Delete Field"
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

                                    </td>

                                </tr>


                                {{-- Edit Field --}}
                                <tr
                                    id="field-edit-{{ $field->fieldID }}"
                                    class="hidden bg-slate-50">

                                    <td colspan="5" class="!p-5">

                                        <form
                                            method="POST"
                                            action="{{ route('admin.post.field.update', $field->fieldID) }}">

                                            @csrf
                                            @method('PUT')


                                            @if($formUsed)

                                            <div class="flex flex-col md:flex-row gap-4 items-end">

                                                <div class="flex-1">

                                                    <label class="app-label">
                                                        Field
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="field_label"
                                                        value="{{ $field->field_label }}"
                                                        required
                                                        class="app-input">

                                                    <p class="text-[11px] text-amber-600 mt-2">
                                                        Wording correction only
                                                    </p>

                                                </div>


                                                <button
                                                    type="submit"
                                                    class="app-button-primary">

                                                    Update

                                                </button>

                                            </div>

                                            @else

                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                                <div class="md:col-span-2">

                                                    <label class="app-label">
                                                        Field
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="field_label"
                                                        value="{{ $field->field_label }}"
                                                        required
                                                        class="app-input">

                                                </div>


                                                <div>

                                                    <label class="app-label">
                                                        Type
                                                    </label>

                                                    <select
                                                        name="field_type"
                                                        required
                                                        data-options-target="edit-options-{{ $field->fieldID }}"
                                                        onchange="toggleOptions(this)"
                                                        class="app-select">

                                                        <option value="display" {{ $field->field_type === 'display' ? 'selected' : '' }}>
                                                            Display
                                                        </option>

                                                        <option value="text" {{ $field->field_type === 'text' ? 'selected' : '' }}>
                                                            Text
                                                        </option>

                                                        <option value="textarea" {{ $field->field_type === 'textarea' ? 'selected' : '' }}>
                                                            Textarea
                                                        </option>

                                                        <option value="checkbox" {{ $field->field_type === 'checkbox' ? 'selected' : '' }}>
                                                            Checkbox
                                                        </option>

                                                        <option value="radio" {{ $field->field_type === 'radio' ? 'selected' : '' }}>
                                                            Radio
                                                        </option>

                                                    </select>

                                                </div>


                                                <div>

                                                    <label class="app-label">
                                                        Required
                                                    </label>

                                                    <select
                                                        name="is_required"
                                                        class="app-select">

                                                        <option value="1" {{ $field->is_required ? 'selected' : '' }}>
                                                            Yes
                                                        </option>

                                                        <option value="0" {{ !$field->is_required ? 'selected' : '' }}>
                                                            No
                                                        </option>

                                                    </select>

                                                </div>


                                                <div
                                                    id="edit-options-{{ $field->fieldID }}"
                                                    class="md:col-span-2 {{ in_array($field->field_type, ['checkbox', 'radio']) ? '' : 'hidden' }}">

                                                    <label class="app-label">
                                                        Options
                                                    </label>


                                                    <div
                                                        id="edit-option-list-{{ $field->fieldID }}"
                                                        class="space-y-2">

                                                        @forelse($field->options as $option)

                                                        <div class="flex items-center gap-2">

                                                            <input
                                                                type="text"
                                                                name="options[]"
                                                                value="{{ $option->option_label }}"
                                                                class="app-input flex-1">

                                                            <button
                                                                type="button"
                                                                onclick="removeOption(this)"
                                                                class="w-10 h-10 shrink-0
                                                                                       border border-red-200 bg-red-50
                                                                                       hover:bg-red-100 text-red-600 rounded-lg">

                                                                ×

                                                            </button>

                                                        </div>

                                                        @empty

                                                        <div class="flex items-center gap-2">

                                                            <input
                                                                type="text"
                                                                name="options[]"
                                                                placeholder="Option"
                                                                class="app-input flex-1">

                                                            <button
                                                                type="button"
                                                                onclick="removeOption(this)"
                                                                class="w-10 h-10 shrink-0
                                                                                       border border-red-200 bg-red-50
                                                                                       hover:bg-red-100 text-red-600 rounded-lg">

                                                                ×

                                                            </button>

                                                        </div>

                                                        @endforelse

                                                    </div>


                                                    <button
                                                        type="button"
                                                        data-option-list="edit-option-list-{{ $field->fieldID }}"
                                                        onclick="addOption(this)"
                                                        class="mt-3 text-[12px] text-blue-600 hover:text-blue-700 font-bold">

                                                        + Add Option

                                                    </button>

                                                </div>

                                            </div>


                                            <div class="flex justify-end mt-5">

                                                <button
                                                    type="submit"
                                                    class="app-button-primary">

                                                    Update

                                                </button>

                                            </div>

                                            @endif

                                        </form>

                                    </td>

                                </tr>

                                @empty

                                <tr>

                                    <td colspan="5" class="!py-12 text-center text-slate-400">
                                        No fields added
                                    </td>

                                </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    @if(!$formUsed)

                    <div class="px-5 md:px-6 py-5 border-t border-slate-100">

                        <details>

                            <summary class="cursor-pointer inline-flex items-center gap-2
                                                    text-blue-600 hover:text-blue-700
                                                    font-bold text-[12.5px]">

                                <span class="w-6 h-6 rounded-lg bg-blue-50 border border-blue-100
                                                     flex items-center justify-center">
                                    +
                                </span>

                                Add Field

                            </summary>


                            <form
                                method="POST"
                                action="{{ route('admin.post.field.store') }}"
                                class="mt-5">

                                @csrf

                                <input
                                    type="hidden"
                                    name="sectionID"
                                    value="{{ $section->sectionID }}">


                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                    <div class="md:col-span-2">

                                        <label class="app-label">
                                            Field
                                        </label>

                                        <input
                                            type="text"
                                            name="field_label"
                                            required
                                            placeholder="Enter field label"
                                            class="app-input">

                                    </div>


                                    <div>

                                        <label class="app-label">
                                            Type
                                        </label>

                                        <select
                                            name="field_type"
                                            required
                                            data-options-target="add-options-{{ $section->sectionID }}"
                                            onchange="toggleOptions(this)"
                                            class="app-select">

                                            <option value="display">Display</option>
                                            <option value="text">Text</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="checkbox">Checkbox</option>
                                            <option value="radio">Radio</option>

                                        </select>

                                    </div>


                                    <div>

                                        <label class="app-label">
                                            Required
                                        </label>

                                        <select
                                            name="is_required"
                                            class="app-select">

                                            <option value="0">No</option>
                                            <option value="1">Yes</option>

                                        </select>

                                    </div>


                                    <div
                                        id="add-options-{{ $section->sectionID }}"
                                        class="hidden md:col-span-2">

                                        <label class="app-label">
                                            Options
                                        </label>


                                        <div
                                            id="add-option-list-{{ $section->sectionID }}"
                                            class="space-y-2">

                                            <div class="flex items-center gap-2">

                                                <input
                                                    type="text"
                                                    name="options[]"
                                                    placeholder="Option"
                                                    class="app-input flex-1">

                                                <button
                                                    type="button"
                                                    onclick="removeOption(this)"
                                                    class="w-10 h-10 shrink-0
                                                                   border border-red-200 bg-red-50
                                                                   hover:bg-red-100 text-red-600 rounded-lg">

                                                    ×

                                                </button>

                                            </div>

                                        </div>


                                        <button
                                            type="button"
                                            data-option-list="add-option-list-{{ $section->sectionID }}"
                                            onclick="addOption(this)"
                                            class="mt-3 text-[12px] text-blue-600 hover:text-blue-700 font-bold">

                                            + Add Option

                                        </button>

                                    </div>

                                </div>


                                <div class="flex justify-end mt-5">

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

                <div class="app-section-card py-12 text-center text-slate-400">
                    No sections added
                </div>

                @endforelse

            </div>


            @if(!$formUsed)

            <div class="app-section-card mb-28">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Add New Section
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Add another section to this form version.
                    </p>

                </div>


                <div class="p-5 md:p-6">

                    <form
                        method="POST"
                        action="{{ route('admin.post.section.store') }}">

                        @csrf

                        <input
                            type="hidden"
                            name="formID"
                            value="{{ $form->formID }}">


                        <div class="flex flex-col md:flex-row gap-4 items-end">

                            <div class="flex-1">

                                <label class="app-label">
                                    Section Name
                                </label>

                                <input
                                    type="text"
                                    name="section_name"
                                    required
                                    placeholder="Enter section name"
                                    class="app-input">

                            </div>


                            <button
                                type="submit"
                                class="app-button-primary">

                                Add

                            </button>

                        </div>

                    </form>

                </div>

            </div>

            @else

            <div class="mb-24"></div>

            @endif

        </div>

    </div>


    {{-- Bottom actions --}}
    <div class="fixed bottom-4 left-0 right-0 z-40 px-6 pointer-events-none">

        <div class="max-w-7xl mx-auto">

            <div class="bg-white/95 backdrop-blur-md border border-slate-200
                        shadow-xl rounded-2xl px-5 py-4 pointer-events-auto">

                <div class="flex items-center justify-between gap-3">

                    <a
                        href="{{ route('admin.post.form') }}"
                        class="app-button-outline">

                        Back

                    </a>


                    <div class="flex items-center gap-3">

                        <a
                            href="{{ route('admin.post.form.show', $form->formID) }}"
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

        function toggleFieldEdit(button) {
            const row = document.getElementById('field-edit-' + button.dataset.fieldId);
            if (row) row.classList.toggle('hidden');
        }

        function toggleOptions(select) {
            const element = document.getElementById(select.dataset.optionsTarget);

            if (!element) return;

            if (select.value === 'checkbox' || select.value === 'radio') {
                element.classList.remove('hidden');
            } else {
                element.classList.add('hidden');
            }
        }

        function addOption(button) {
            const list = document.getElementById(button.dataset.optionList);

            if (!list) return;

            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';

            row.innerHTML = `
                <input
                    type="text"
                    name="options[]"
                    placeholder="Option"
                    class="app-input flex-1">

                <button
                    type="button"
                    onclick="removeOption(this)"
                    class="w-10 h-10 shrink-0 border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg">
                    ×
                </button>
            `;

            list.appendChild(row);
        }

        function removeOption(button) {
            const row = button.parentElement;
            if (row) row.remove();
        }
    </script>

</x-app-layout>