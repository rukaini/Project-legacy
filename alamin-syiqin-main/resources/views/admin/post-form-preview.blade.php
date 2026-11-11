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
                                This shows how the form appears to the evaluator
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Preview Content --}}
            <div class="space-y-6">

                @foreach($form->sections as $section)

                @php
                $displayFields = $section->fields
                ->where('field_type', 'display')
                ->values();

                $inputFields = $section->fields
                ->where('field_type', '!=', 'display')
                ->values();
                @endphp


                @if($displayFields->isNotEmpty())

                <div class="app-section-card">

                    <div class="px-5 md:px-6 py-4 border-b border-blue-100 bg-blue-50">

                        <h2 class="text-[15px] font-extrabold text-slate-900">
                            {{ $section->section_name }}
                        </h2>

                    </div>


                    <div class="app-table-wrap">

                        <table class="w-full table-fixed text-sm">

                            <tbody>

                                @foreach($displayFields->chunk(4) as $row)

                                <tr>

                                    @foreach($row as $field)

                                    <td class="border-r border-b border-slate-100
                                               px-5 py-4 text-slate-700 font-semibold
                                               align-middle bg-white
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


                @if($inputFields->isNotEmpty())

                <div class="app-section-card">

                    <div class="px-5 md:px-6 py-4 border-b border-blue-100 bg-blue-50">

                        <h2 class="text-[15px] font-extrabold text-slate-900">
                            {{ $section->section_name }}
                        </h2>

                    </div>


                    <div class="divide-y divide-slate-100">

                        @foreach($inputFields as $field)

                        @if($field->field_type === 'textarea')

                        <div class="px-5 md:px-6 py-5">

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start">

                                <div class="md:col-span-1">

                                    <label class="block text-[12.5px] text-slate-700 font-bold pt-2">

                                        {{ $field->field_label }}

                                        @if($field->is_required)
                                        <span class="text-red-500">*</span>
                                        @endif

                                    </label>

                                </div>


                                <div class="md:col-span-5">

                                    <textarea
                                        rows="3"
                                        disabled
                                        class="app-textarea !bg-slate-50">
                                    </textarea>

                                </div>

                            </div>

                        </div>


                        @elseif($field->field_type === 'checkbox')

                        <div class="px-5 md:px-6 py-5">

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start">

                                <div class="md:col-span-1">

                                    <label class="block text-[12.5px] text-slate-700 font-bold">

                                        {{ $field->field_label }}

                                        @if($field->is_required)
                                        <span class="text-red-500">*</span>
                                        @endif

                                    </label>

                                </div>


                                <div class="md:col-span-5">

                                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">

                                        @foreach($field->options as $option)

                                        <label class="inline-flex items-center gap-2">

                                            <input
                                                type="checkbox"
                                                disabled
                                                class="rounded border-slate-300 text-blue-600">

                                            <span class="text-[12.5px] text-slate-700">
                                                {{ $option->option_label }}
                                            </span>

                                        </label>

                                        @endforeach

                                    </div>

                                </div>

                            </div>

                        </div>


                        @elseif($field->field_type === 'radio')

                        <div class="px-5 md:px-6 py-5">

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start">

                                <div class="md:col-span-1">

                                    <label class="block text-[12.5px] text-slate-700 font-bold">

                                        {{ $field->field_label }}

                                        @if($field->is_required)
                                        <span class="text-red-500">*</span>
                                        @endif

                                    </label>

                                </div>


                                <div class="md:col-span-5">

                                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">

                                        @foreach($field->options as $option)

                                        <label class="inline-flex items-center gap-2">

                                            <input
                                                type="radio"
                                                disabled
                                                class="border-slate-300 text-blue-600">

                                            <span class="text-[12.5px] text-slate-700">
                                                {{ $option->option_label }}
                                            </span>

                                        </label>

                                        @endforeach

                                    </div>

                                </div>

                            </div>

                        </div>


                        @else

                        <div class="px-5 md:px-6 py-5">

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-center">

                                <label class="md:col-span-1 text-[12.5px] text-slate-700 font-bold">

                                    {{ $field->field_label }}

                                    @if($field->is_required)
                                    <span class="text-red-500">*</span>
                                    @endif

                                </label>


                                <div class="md:col-span-5">

                                    <input
                                        type="text"
                                        disabled
                                        class="app-input !bg-slate-50">

                                </div>

                            </div>

                        </div>

                        @endif

                        @endforeach

                    </div>

                </div>

                @endif

                @endforeach

            </div>


            <div class="mb-24"></div>

        </div>

    </div>


    {{-- Bottom actions --}}
    <div class="fixed bottom-4 left-0 right-0 z-40 px-6 pointer-events-none">

        <div class="max-w-7xl mx-auto">

            <div class="bg-white/95 backdrop-blur-sm border border-slate-200
                        shadow-xl rounded-2xl px-5 py-4 pointer-events-auto">

                <div class="flex items-center justify-between">

                    <a
                        href="{{ route('admin.post.form') }}"
                        class="app-button-outline">

                        Back

                    </a>


                    <a
                        href="{{ route('admin.post.form.edit', $form->formID) }}"
                        class="app-button-primary">

                        Edit

                    </a>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>