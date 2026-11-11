<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-7xl mx-auto">

            {{-- Page heading --}}
            <div class="flex items-start gap-4 mb-7">

                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">

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
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />

                    </svg>

                </div>

                <div>

                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Pre-Observation Form Versions
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Manage form content and saved versions.
                    </p>

                </div>

            </div>


            @if(session('success'))

            <div class="mb-5 px-4 py-3 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm font-semibold">
                {{ session('success') }}
            </div>

            @endif


            @if(session('error'))

            <div class="mb-5 px-4 py-3 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm font-semibold">
                {{ session('error') }}
            </div>

            @endif


            @if($errors->any())

            <div class="mb-5 px-4 py-3 rounded-xl border border-red-200 bg-red-50 text-red-700">

                <ul class="list-disc list-inside text-sm">

                    @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                    @endforeach

                </ul>

            </div>

            @endif


            @if($forms->isEmpty())

            <div class="app-section-card mb-24">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Create Pre-Observation Form
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Create the first version of the form.
                    </p>

                </div>


                <div class="p-5 md:p-6">

                    <form
                        method="POST"
                        action="{{ route('admin.pre.form.store') }}">

                        @csrf


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            <div>

                                <label class="app-label">
                                    Form Name
                                </label>

                                <input
                                    type="text"
                                    name="form_name"
                                    value="{{ old('form_name') }}"
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
                                    value="{{ old('instruction') }}"
                                    class="app-input">

                            </div>


                            <div>

                                <label class="app-label">
                                    Minimum Score
                                </label>

                                <input
                                    type="number"
                                    name="min_score"
                                    value="{{ old('min_score', 1) }}"
                                    min="0"
                                    required
                                    class="app-input">

                            </div>


                            <div>

                                <label class="app-label">
                                    Maximum Score
                                </label>

                                <input
                                    type="number"
                                    name="max_score"
                                    value="{{ old('max_score', 5) }}"
                                    min="1"
                                    required
                                    class="app-input">

                            </div>

                        </div>


                        <div class="flex justify-end mt-6">

                            <button
                                type="submit"
                                class="app-button-primary">

                                Create Form

                            </button>

                        </div>

                    </form>

                </div>

            </div>

            @else

            @php
            $activeForm = $forms->firstWhere('status', 'Active');
            @endphp


            <div class="app-section-card mb-24">

                <div class="app-table-wrap">

                    <table class="app-table">

                        <thead>

                            <tr>

                                <th class="w-32">
                                    Version
                                </th>

                                <th>
                                    Form Content
                                </th>

                                <th class="text-center w-32">
                                    Status
                                </th>

                                <th class="text-center w-32">
                                    Usage
                                </th>

                                <th class="text-center w-24">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($forms as $form)

                            <tr class="{{ $form->status === 'Active' ? 'bg-blue-50/30' : '' }}">

                                <td>

                                    <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center text-[14px] font-extrabold">
                                        V{{ $form->version }}
                                    </div>

                                </td>


                                <td>

                                    <p class="font-extrabold text-slate-900 text-[14px] mb-2">
                                        {{ $form->form_name }}
                                    </p>

                                    <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-[12px] text-slate-500">

                                        <span>
                                            <strong class="text-slate-800">
                                                {{ $form->section_count }}
                                            </strong>
                                            Sections
                                        </span>

                                        <span>
                                            <strong class="text-slate-800">
                                                {{ $form->criteria_count }}
                                            </strong>
                                            Criteria
                                        </span>

                                        <span>
                                            <strong class="text-slate-800">
                                                {{ $form->min_score }} - {{ $form->max_score }}
                                            </strong>
                                            Score Range
                                        </span>

                                        <span>
                                            <strong class="text-amber-600">
                                                {{ $form->max_mark }}
                                            </strong>
                                            Maximum Total
                                        </span>

                                    </div>


                                    @if($form->instruction)

                                    <p class="text-[12px] text-slate-400 mt-2">
                                        {{ $form->instruction }}
                                    </p>

                                    @else

                                    <p class="text-[12px] text-slate-300 mt-2">
                                        No instruction
                                    </p>

                                    @endif

                                </td>


                                <td class="text-center">

                                    @if($form->status === 'Active')

                                    <span class="app-badge app-badge-success">
                                        Active
                                    </span>

                                    @else

                                    <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                                        Inactive
                                    </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    @if($form->is_used)

                                    <span class="app-badge app-badge-warning">
                                        Used
                                    </span>

                                    @else

                                    <span class="app-badge app-badge-info">
                                        Not Used
                                    </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    <button
                                        type="button"
                                        data-preview="{{ route('admin.pre.form.preview', $form->formID) }}"
                                        data-edit="{{ route('admin.pre.form.edit', $form->formID) }}"
                                        data-delete="{{ !$form->is_used ? route('admin.pre.form.delete', $form->formID) : '' }}"
                                        data-version="{{ $form->version }}"
                                        onclick="openActionMenu(this)"
                                        class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition">

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4"
                                            fill="currentColor"
                                            viewBox="0 0 24 24">

                                            <circle cx="12" cy="5" r="1.7"></circle>
                                            <circle cx="12" cy="12" r="1.7"></circle>
                                            <circle cx="12" cy="19" r="1.7"></circle>

                                        </svg>

                                    </button>

                                </td>

                            </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

            @endif

        </div>

    </div>


    <div
        id="floatingActionMenu"
        class="hidden fixed w-44 bg-white border border-slate-200 rounded-xl shadow-xl z-[9999] overflow-hidden">

        <a
            id="actionPreview"
            href="#"
            class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">

            <svg
                class="w-4 h-4 text-slate-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                </path>

                <circle cx="12" cy="12" r="3"></circle>

            </svg>

            <span>
                Preview
            </span>

        </a>


        <a
            id="actionEdit"
            href="#"
            class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 border-t border-slate-100">

            <svg
                class="w-4 h-4 text-slate-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.232 5.232l3.536 3.536M16.732 3.732a2.5 2.5 0 013.536 3.536L7.5 20.036 3 21l.964-4.5L16.732 3.732z">
                </path>

            </svg>

            <span>
                Edit
            </span>

        </a>


        <form
            id="actionDeleteForm"
            method="POST"
            action="">

            @csrf
            @method('DELETE')

            <button
                id="actionDeleteButton"
                type="submit"
                class="w-full flex items-center gap-3 px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50 border-t border-slate-100">

                <svg
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2">

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5">
                    </path>

                </svg>

                <span>
                    Delete
                </span>

            </button>

        </form>

    </div>


    <div class="fixed bottom-4 left-0 right-0 z-40 px-6 pointer-events-none">

        <div class="max-w-7xl mx-auto">

            <div class="bg-white/95 backdrop-blur-md border border-slate-200 shadow-xl rounded-2xl px-5 py-4 pointer-events-auto">

                <div class="flex items-center justify-between">

                    <a
                        href="{{ route('admin.manage.form') }}"
                        class="app-button-outline">

                        Back

                    </a>


                    @if($forms->isNotEmpty() && $activeForm)

                    <form
                        method="POST"
                        action="{{ route('admin.pre.form.new-version', $activeForm->formID) }}">

                        @csrf

                        <button
                            type="submit"
                            onclick="return confirm('Create a new version based on Version {{ $activeForm->version }}?')"
                            class="app-button-primary">

                            + New Version

                        </button>

                    </form>

                    @endif

                </div>

            </div>

        </div>

    </div>


    <script>
        function openActionMenu(button) {
            const menu = document.getElementById('floatingActionMenu');
            const preview = document.getElementById('actionPreview');
            const edit = document.getElementById('actionEdit');
            const deleteForm = document.getElementById('actionDeleteForm');
            const deleteButton = document.getElementById('actionDeleteButton');

            preview.href = button.dataset.preview;
            edit.href = button.dataset.edit;

            if (button.dataset.delete) {
                deleteForm.action = button.dataset.delete;
                deleteForm.classList.remove('hidden');

                deleteButton.onclick = function() {
                    return confirm('Delete Version ' + button.dataset.version + '?');
                };
            } else {
                deleteForm.classList.add('hidden');
            }

            const rect = button.getBoundingClientRect();
            const menuWidth = 176;
            const menuHeight = button.dataset.delete ? 145 : 98;

            let left = rect.right - menuWidth;
            let top = rect.bottom + 6;

            if (left < 10) left = 10;

            if (top + menuHeight > window.innerHeight - 10) {
                top = rect.top - menuHeight - 6;
            }

            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
            menu.classList.remove('hidden');
        }


        document.addEventListener('click', function(event) {
            const menu = document.getElementById('floatingActionMenu');

            if (!event.target.closest('[onclick="openActionMenu(this)"]') &&
                !event.target.closest('#floatingActionMenu')) {
                menu.classList.add('hidden');
            }
        });


        window.addEventListener('scroll', function() {
            document.getElementById('floatingActionMenu').classList.add('hidden');
        }, true);
    </script>

</x-app-layout>