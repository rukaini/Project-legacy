<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-8xl mx-auto">

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
                            d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14" />

                    </svg>

                </div>


                <div>


                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Download Observation Forms
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        You can download the observation forms below.
                    </p>

                </div>

            </div>


            {{-- Observation forms --}}
            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Observation Forms
                    </h2>
                </div>


                <div class="app-table-wrap">

                    <table class="app-table">

                        <thead>

                            <tr>

                                <th class="w-20">
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

                                <th class="text-center w-40">
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

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100
                                                        flex items-center justify-center shrink-0">

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.7"
                                                stroke="currentColor"
                                                class="w-4.5 h-4.5 text-blue-600">

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

                                        <p class="font-bold text-slate-800">
                                            {{ $document->form_name }}
                                        </p>

                                    </div>

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

                                    @if($role === 'observer')

                                    <a
                                        href="{{ route(
                                            'evaluation.doc.download',
                                            $document->doc_id
                                        ) }}"
                                        class="app-button-outline inline-flex items-center justify-center gap-2 whitespace-nowrap">

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4 shrink-0"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 4v12m0 0l-4-4m4 4l4-4M5 20h14" />

                                        </svg>

                                        Download

                                    </a>

                                    @else

                                    <a
                                        href="{{ route(
                                            'evaluation.doc.download',
                                            $document->doc_id
                                        ) }}"
                                        class="app-button-outline inline-flex items-center justify-center gap-2 whitespace-nowrap">

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4 shrink-0"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 4v12m0 0l-4-4m4 4l4-4M5 20h14" />

                                        </svg>

                                        Download

                                    </a>

                                    @endif

                                </td>

                            </tr>

                            @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="!py-14 text-center">

                                    <div class="flex flex-col items-center">

                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100
                                                        flex items-center justify-center mb-3">

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.7"
                                                stroke="currentColor"
                                                class="w-6 h-6 text-slate-400">

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

                                        <p class="font-semibold text-slate-700">
                                            No evaluation documents available
                                        </p>

                                    </div>

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