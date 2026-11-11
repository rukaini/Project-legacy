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
                            d="M9 12h6m-6 4h6m2 5H7
                               a2 2 0 01-2-2V5
                               a2 2 0 012-2h5.586
                               a1 1 0 01.707.293
                               l5.414 5.414
                               a1 1 0 01.293.707V19
                               a2 2 0 01-2 2z" />

                    </svg>

                </div>

                <div>

                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Manage Form
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Manage observation form templates and downloadable evaluation documents.
                    </p>

                </div>

            </div>


            {{-- Observation Forms --}}
            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Observation Forms
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Select a form to manage its content.
                    </p>

                </div>


                <div class="app-table-wrap">

                    <table class="app-table">

                        <thead>

                            <tr>

                                <th class="w-24">
                                    No
                                </th>

                                <th>
                                    Form
                                </th>

                                <th class="text-center w-44">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <tr>

                                <td class="text-slate-500">
                                    1
                                </td>

                                <td>

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100
                                                    flex items-center justify-center shrink-0">

                                            <span class="text-[10px] font-extrabold text-blue-600">
                                                PRE
                                            </span>

                                        </div>

                                        <p class="font-bold text-slate-800">
                                            {{ $preForm?->form_name ?? 'Pre Observation Form' }}
                                        </p>

                                    </div>

                                </td>

                                <td class="text-center">

                                    <a
                                        href="{{ route('admin.pre.form') }}"
                                        class="app-button-outline">

                                        Manage

                                    </a>

                                </td>

                            </tr>


                            <tr>

                                <td class="text-slate-500">
                                    2
                                </td>

                                <td>

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100
                                                    flex items-center justify-center shrink-0">

                                            <span class="text-[9px] font-extrabold text-indigo-600">
                                                PDPC
                                            </span>

                                        </div>

                                        <p class="font-bold text-slate-800">
                                            {{ $pdpcForm?->form_name ?? 'PDPC Observation Form' }}
                                        </p>

                                    </div>

                                </td>

                                <td class="text-center">

                                    <a
                                        href="{{ route('admin.pdpc.form') }}"
                                        class="app-button-outline">

                                        Manage

                                    </a>

                                </td>

                            </tr>


                            {{-- Feedback observation form --}}
                            <tr>

                                <td class="text-slate-500">
                                    3
                                </td>

                                <td>

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-100
                                                    flex items-center justify-center shrink-0">

                                            <span class="text-[9px] font-extrabold text-emerald-600">
                                                POST
                                            </span>

                                        </div>

                                        <p class="font-bold text-slate-800">
                                            {{ $postForm?->form_name ?? 'Feedback Observation Form' }}
                                        </p>

                                    </div>

                                </td>

                                <td class="text-center">

                                    <a
                                        href="{{ route('admin.post.form') }}"
                                        class="app-button-outline">

                                        Manage

                                    </a>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Upload Evaluation Form --}}
            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Upload Evaluation Form
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Upload PDF or Excel forms for observer and external observer to download.
                    </p>

                </div>


                {{-- Upload Form --}}
                <div class="p-5 md:p-6 border-b border-slate-100 bg-slate-50/40">

                    <form
                        method="POST"
                        action="{{ route('admin.evaluation.doc.store') }}"
                        enctype="multipart/form-data">

                        @csrf


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            <div>

                                <label
                                    for="form_name"
                                    class="app-label">
                                    Form Name
                                </label>

                                <input
                                    type="text"
                                    id="form_name"
                                    name="form_name"
                                    value="{{ old('form_name') }}"
                                    placeholder="E.g. Borang Pencerapan"
                                    required
                                    class="app-input">

                                @error('form_name')

                                <p class="text-red-500 text-xs mt-1.5">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>


                            <div>

                                <label
                                    for="file"
                                    class="app-label">
                                    Upload File
                                </label>

                                <input
                                    type="file"
                                    id="file"
                                    name="file"
                                    accept=".pdf,.xls,.xlsx"
                                    required
                                    class="w-full min-h-[40px] rounded-[10px]
                                           border border-slate-200
                                           bg-white px-3 py-2
                                           text-[13px] font-semibold text-slate-600
                                           file:mr-3 file:border-0
                                           file:bg-blue-50 file:text-blue-600
                                           file:px-3 file:py-1.5
                                           file:rounded-lg
                                           file:text-[12px] file:font-bold">

                                <p class="text-[11px] text-slate-400 mt-2">
                                    PDF, XLS or XLSX only. Maximum 10MB
                                </p>

                                @error('file')

                                <p class="text-red-500 text-xs mt-1.5">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>


                            <div class="md:col-span-2">

                                <label
                                    for="description"
                                    class="app-label">
                                    Description
                                </label>

                                <input
                                    type="text"
                                    id="description"
                                    name="description"
                                    value="{{ old('description') }}"
                                    placeholder="Enter form description"
                                    class="app-input">

                                @error('description')

                                <p class="text-red-500 text-xs mt-1.5">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>

                        </div>


                        <div class="flex justify-end mt-5">

                            <button
                                type="submit"
                                class="app-button-primary">

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
                                        d="M12 16V4m0 0L8 8m4-4l4 4M5 20h14" />

                                </svg>

                                Upload

                            </button>

                        </div>

                    </form>

                </div>


                {{-- Uploaded documents --}}
                <div class="app-table-wrap">

                    <table class="app-table">

                        <thead>

                            <tr>

                                <th>
                                    No
                                </th>

                                <th>
                                    Form Name
                                </th>

                                <th>
                                    Description
                                </th>

                                <th class="text-center">
                                    Type
                                </th>

                                <th class="text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($documents as $document)

                            <tr>

                                <td class="text-slate-500">
                                    {{ $loop->iteration }}
                                </td>


                                <td>

                                    <p class="font-bold text-slate-800">
                                        {{ $document->form_name }}
                                    </p>

                                    <p class="text-[11px] text-slate-400 mt-1">
                                        {{ $document->file_name }}
                                    </p>

                                </td>


                                <td class="text-slate-600">
                                    {{ $document->description ?? '-' }}
                                </td>


                                <td class="text-center">

                                    @if($document->file_type === 'PDF')

                                    <span class="app-badge app-badge-danger">
                                        PDF
                                    </span>

                                    @else

                                    <span class="app-badge app-badge-success">
                                        EXCEL
                                    </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    <form
                                        method="POST"
                                        action="{{ route(
                                                'admin.evaluation.doc.delete',
                                                $document->doc_id
                                            ) }}">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Delete this file?')"
                                            title="Delete"
                                            class="inline-flex items-center justify-center
                                                       w-9 h-9 rounded-lg
                                                       border border-red-200
                                                       bg-red-50 text-red-600
                                                       hover:bg-red-100 transition">

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

                                </td>

                            </tr>

                            @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="!py-14 text-center">

                                    <p class="font-semibold text-slate-700">
                                        No evaluation documents uploaded
                                    </p>

                                </td>

                            </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>