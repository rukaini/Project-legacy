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
                            d="M9 12.75L11.25 15 15 9.75M8.25 4.5h7.5
                               A2.25 2.25 0 0118 6.75v12
                               A2.25 2.25 0 0115.75 21h-7.5
                               A2.25 2.25 0 016 18.75v-12
                               A2.25 2.25 0 018.25 4.5z" />

                    </svg>

                </div>

                <div>


                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Observation Audit
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        View submitted observation records.
                    </p>

                </div>

            </div>


            {{-- Search & Filter --}}
            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Search & Filter
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Filter audit records by observer, role, stage or date.
                    </p>

                </div>


                <div class="p-5 md:p-6 bg-slate-50/40">

                    <form
                        id="auditFilterForm"
                        method="GET"
                        action="{{ route('admin.audit.observation') }}"
                        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-4 items-end">

                        {{-- Search --}}
                        <div class="xl:col-span-5">

                            <label for="search" class="app-label">
                                Search
                            </label>

                            <input
                                type="text"
                                id="search"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search observer or teacher..."
                                class="app-input">

                        </div>


                        {{-- Role --}}
                        <div class="xl:col-span-2">

                            <label for="role" class="app-label">
                                Role
                            </label>

                            <select
                                id="role"
                                name="role"
                                class="app-select">

                                <option value="">
                                    All Roles
                                </option>

                                <option
                                    value="Observer"
                                    {{ request('role') === 'Observer' ? 'selected' : '' }}>
                                    Observer
                                </option>

                                <option
                                    value="External Observer"
                                    {{ request('role') === 'External Observer' ? 'selected' : '' }}>
                                    External Observer
                                </option>

                            </select>

                        </div>


                        {{-- Stage --}}
                        <div class="xl:col-span-2">

                            <label for="stage" class="app-label">
                                Stage
                            </label>

                            <select
                                id="stage"
                                name="stage"
                                class="app-select">

                                <option value="">
                                    All Stages
                                </option>

                                <option
                                    value="PRE"
                                    {{ request('stage') === 'PRE' ? 'selected' : '' }}>
                                    PRE
                                </option>

                                <option
                                    value="EXTERNAL"
                                    {{ request('stage') === 'EXTERNAL' ? 'selected' : '' }}>
                                    EXTERNAL
                                </option>

                                <option
                                    value="POST"
                                    {{ request('stage') === 'POST' ? 'selected' : '' }}>
                                    POST
                                </option>

                            </select>

                        </div>


                        {{-- Date --}}
                        <div class="xl:col-span-2">

                            <label for="date" class="app-label">
                                Date
                            </label>

                            <input
                                type="date"
                                id="date"
                                name="date"
                                value="{{ request('date') }}"
                                class="app-input">

                        </div>


                        <button
                            type="button"
                            onclick="applyAuditFilter()"
                            class="app-button-primary w-full">

                            Filter

                        </button>


                        @if(request()->filled('search') ||
                        request()->filled('role') ||
                        request()->filled('stage') ||
                        request()->filled('date'))

                        <div class="xl:col-span-12 flex justify-end">

                            <a
                                href="{{ route('admin.audit.observation') }}"
                                class="text-[12px] font-bold text-red-500 hover:text-red-600 transition">
                                Clear Filters
                            </a>

                        </div>

                        @endif

                    </form>

                </div>

            </div>


            {{-- Audit History --}}
            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Observation Audit History
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Submitted observation activity recorded in the system.
                    </p>

                </div>


                <div class="app-table-wrap">

                    <table class="app-table">

                        <thead>

                            <tr>

                                <th>
                                    No.
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Time
                                </th>

                                <th class="whitespace-nowrap">
                                    Observed By
                                </th>

                                <th>
                                    Role
                                </th>

                                <th class="whitespace-nowrap">
                                    Teacher Observed
                                </th>

                                <th>
                                    Form
                                </th>

                                <th>
                                    Stage
                                </th>

                                <th class="text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($audits as $audit)

                            <tr>

                                {{-- Number --}}
                                <td class="text-slate-500">

                                    {{
                                            ($audits->currentPage() - 1)
                                            * $audits->perPage()
                                            + $loop->iteration
                                        }}

                                </td>


                                <td class="text-slate-600">

                                    {{ $audit->audit_date
                                            ? \Carbon\Carbon::parse($audit->audit_date)->format('d/m/Y')
                                            : '-' }}

                                </td>


                                {{-- Time --}}
                                <td class="text-slate-600 whitespace-nowrap">

                                    {{ $audit->audit_time
                                            ? \Carbon\Carbon::parse($audit->audit_time)->format('h:i A')
                                            : '-' }}

                                </td>


                                <td>

                                    <div class="flex items-center gap-3">

                                        <div class="w-8 h-8 rounded-full bg-blue-50 border border-blue-200
                                                        flex items-center justify-center shrink-0">

                                            <span class="text-[10px] font-extrabold text-blue-600">
                                                {{ strtoupper(substr($audit->teacherName ?? '-', 0, 1)) }}
                                            </span>

                                        </div>

                                        <p class="font-semibold text-slate-800 uppercase">
                                            {{ $audit->teacherName ?? '-' }}
                                        </p>

                                    </div>

                                </td>


                                <td>

                                    @if($audit->role === 'Observer')

                                    <span class="app-badge app-badge-info">
                                        Observer
                                    </span>

                                    @elseif($audit->role === 'External Observer')

                                    <span class="app-badge border-violet-200 bg-violet-50 text-violet-700">
                                        External Observer
                                    </span>

                                    @else

                                    <span class="app-badge border-slate-200 bg-slate-50 text-slate-600">
                                        {{ $audit->role ?? '-' }}
                                    </span>

                                    @endif

                                </td>


                                <td>

                                    <p class="font-semibold text-slate-800 uppercase">
                                        {{ $audit->gn_name ?? '-' }}
                                    </p>

                                </td>


                                <td class="text-slate-600">
                                    {{ $audit->form_name ?? '-' }}
                                </td>


                                <td>

                                    @if($audit->stage === 'PRE')

                                    <span class="app-badge border-sky-200 bg-sky-50 text-sky-700">
                                        PRE
                                    </span>

                                    @elseif($audit->stage === 'POST')

                                    <span class="app-badge app-badge-success">
                                        POST
                                    </span>

                                    @elseif($audit->stage === 'EXTERNAL')

                                    <span class="app-badge app-badge-warning">
                                        EXTERNAL
                                    </span>

                                    @else

                                    <span class="app-badge border-slate-200 bg-slate-50 text-slate-600">
                                        {{ $audit->stage ?? '-' }}
                                    </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    @if($audit->action === 'Submitted')

                                    <span class="app-badge app-badge-success">

                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>

                                        Submitted

                                    </span>

                                    @else

                                    <span class="app-badge app-badge-info">

                                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>

                                        {{ $audit->action ?? '-' }}

                                    </span>

                                    @endif

                                </td>

                            </tr>

                            @empty

                            <tr>

                                <td
                                    colspan="9"
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
                                            No observation audit records found.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if($audits->hasPages())

                <div class="px-5 md:px-6 py-4 border-t border-slate-100">

                    {{ $audits->links() }}

                </div>

                @endif

            </div>

        </div>

    </div>

    <script>
        function applyAuditFilter() {
            const search = document.querySelector('[name="search"]').value.trim();
            const role = document.querySelector('[name="role"]').value;
            const stage = document.querySelector('[name="stage"]').value;
            const date = document.querySelector('[name="date"]').value;

            const params = new URLSearchParams();

            if (search !== '') {
                params.set('search', search);
            }

            if (role !== '') {
                params.set('role', role);
            }

            if (stage !== '') {
                params.set('stage', stage);
            }

            if (date !== '') {
                params.set('date', date);
            }

            const baseUrl = "{{ route('admin.audit.observation') }}";

            window.location.href =
                params.toString() !== '' ?
                `${baseUrl}?${params.toString()}` :
                baseUrl;
        }
    </script>
</x-app-layout>