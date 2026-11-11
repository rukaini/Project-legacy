<x-app-layout>
    <div class="app-page-shell">
        <div class="max-w-8xl mx-auto">

            {{-- PAGE HEADING --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 mb-7">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 text-blue-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m18 0v-2a4 4 0 00-3-3.87M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a4 4 0 00-3-3.87" />
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                            New Teachers
                        </h1>
                        <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                            Manage new teacher accounts and employment status.
                        </p>
                    </div>
                </div>
            </div>

            {{-- VALIDATION ERRORS --}}
            @if($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4">
                    <p class="text-sm font-bold text-red-700 mb-2">
                        Please check the following:
                    </p>
                    <ul class="list-disc pl-5 text-sm text-red-600 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- SUCCESS MESSAGE --}}
            @if(session('success'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- TEACHER LIST --}}
            <div class="app-section-card">

                {{-- FILTER --}}
                <div class="px-5 md:px-6 py-5 border-b border-slate-100 bg-slate-50/40">
                    <form method="GET"
                        action="{{ route('hr.gurunew.index') }}"
                        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-4 items-end">

                        <div class="xl:col-span-4">
                            <label for="search" class="app-label">
                                Search
                            </label>
                            <input type="text"
                                id="search"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search teacher..."
                                class="app-input">
                        </div>

                        <div class="xl:col-span-3">
                            <label for="schoolID" class="app-label">
                                School
                            </label>
                            <select id="schoolID"
                                name="schoolID"
                                class="app-select">
                                <option value="">All Schools</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->schoolID }}"
                                        {{ request('schoolID') == $school->schoolID ? 'selected' : '' }}>
                                        {{ $school->schoolName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="xl:col-span-2">
                            <label for="status" class="app-label">
                                Status
                            </label>
                            <select id="status"
                                name="status"
                                class="app-select">
                                <option value="">All Status</option>
                                <option value="Inactive"
                                    {{ request('status') === 'Inactive' ? 'selected' : '' }}>
                                    Inactive
                                </option>
                                <option value="Active"
                                    {{ request('status') === 'Active' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="Complete"
                                    {{ request('status') === 'Complete' ? 'selected' : '' }}>
                                    Complete
                                </option>
                            </select>
                        </div>

                        <div class="xl:col-span-2">
                            <label for="appointed_date" class="app-label">
                                Appointed Date
                            </label>
                            <input type="date"
                                id="appointed_date"
                                name="appointed_date"
                                value="{{ request('appointed_date') }}"
                                class="app-input">
                        </div>

                        <div class="xl:col-span-1">
                            <button type="submit" class="app-button-primary w-full">
                                Filter
                            </button>
                        </div>

                        @if(request('search') || request('schoolID') || request('status') || request('appointed_date'))
                            <div class="xl:col-span-12">
                                <a href="{{ route('hr.gurunew.index') }}"
                                    class="inline-flex items-center text-[12px] font-bold text-red-500 hover:text-red-600 transition">
                                    Clear Filter
                                </a>
                            </div>
                        @endif
                    </form>
                </div>

                {{-- TABLE --}}
                <div class="app-table-wrap">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Teacher</th>
                                <th>Email</th>
                                <th>School</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($guruNews as $guru)
                                <tr>
                                    <td class="text-slate-500">
                                        {{ $guruNews->firstItem() + $loop->index }}
                                    </td>

                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                                                <span class="text-[11px] font-extrabold text-blue-600">
                                                    {{ strtoupper(substr($guru->gn_name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800">
                                                    {{ $guru->gn_name }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-slate-600">
                                        {{ $guru->email }}
                                    </td>

                                    <td class="text-slate-600 max-w-xs">
                                        {{ $guru->school?->schoolName ?? '-' }}
                                    </td>

                                    <td>
                                        @if($guru->current_status === 'Active')
                                            <span class="app-badge app-badge-success">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>
                                        @elseif($guru->current_status === 'Complete')
                                            <span class="app-badge app-badge-info">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                Complete
                                            </span>
                                        @else
                                            <span class="app-badge app-badge-danger">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <button type="button"
                                            onclick="openModal('{{ $guru->gn_id }}')"
                                            class="app-button-outline">
                                            Manage
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="!py-14 text-center">
                                        <p class="font-semibold text-slate-700">
                                            No new teachers found
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1">
                                            No teacher records match the selected filter.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                @if($guruNews->hasPages())
                    <div class="px-5 md:px-6 py-4 border-t border-slate-100">
                        {{ $guruNews->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MANAGE TEACHER MODALS --}}
    @foreach($guruNews as $guru)
        <div id="modal-{{ $guru->gn_id }}"
            class="fixed inset-0 bg-slate-950/50 hidden items-center justify-center z-50 px-4 md:px-6">

            <div class="bg-white rounded-[18px] shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[90vh] overflow-y-auto">

                {{-- MODAL HEADER --}}
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.07em] text-blue-600">
                            New Teacher
                        </p>
                        <h2 class="text-[20px] font-extrabold text-slate-900 mt-1">
                            Manage Teacher
                        </h2>
                        <p class="text-[12.5px] text-slate-500 mt-1">
                            View and manage new teacher details.
                        </p>
                    </div>

                    <button type="button"
                        onclick="closeModal('{{ $guru->gn_id }}')"
                        class="w-9 h-9 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-slate-50 hover:text-red-500 transition">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="w-5 h-5">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- MANAGE FORM --}}
                <form method="POST"
                    action="{{ route('hr.gurunew.update', $guru->gn_id) }}">
                    @csrf
                    @method('PUT')

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-7 gap-y-5">

                            {{-- LEFT COLUMN --}}
                            <div class="space-y-5">
                                <div>
                                    <label class="app-label">IC Number</label>
                                    <input type="text"
                                        value="{{ $guru->ic_number }}"
                                        readonly
                                        class="app-input !bg-slate-50 !text-slate-500">
                                </div>

                                <div>
                                    <label for="phone_number_{{ $guru->gn_id }}"
                                        class="app-label">
                                        Phone Number
                                    </label>
                                    <input type="text"
                                        id="phone_number_{{ $guru->gn_id }}"
                                        name="phone_number"
                                        value="{{ $guru->phone_number }}"
                                        required
                                        class="app-input">
                                </div>

                                <div>
                                    <label class="app-label">Gender</label>
                                    <input type="text"
                                        value="{{ $guru->gender }}"
                                        readonly
                                        class="app-input !bg-slate-50 !text-slate-500">
                                </div>

                                <div>
                                    <label for="appointed_date_{{ $guru->gn_id }}"
                                        class="app-label">
                                        Appointed Date
                                    </label>
                                    <input type="date"
                                        id="appointed_date_{{ $guru->gn_id }}"
                                        name="appointed_date"
                                        value="{{ $guru->appointed_date }}"
                                        required
                                        class="app-input">
                                </div>
                            </div>

                            {{-- RIGHT COLUMN --}}
                            <div class="space-y-5">
                                <div>
                                    <label class="app-label">Teacher Name</label>
                                    <input type="text"
                                        value="{{ $guru->gn_name }}"
                                        readonly
                                        class="app-input !bg-slate-50 !text-slate-500">
                                </div>

                                <div>
                                    <label for="email_{{ $guru->gn_id }}"
                                        class="app-label">
                                        Email
                                    </label>
                                    <input type="email"
                                        id="email_{{ $guru->gn_id }}"
                                        name="email"
                                        value="{{ $guru->email }}"
                                        required
                                        class="app-input">
                                </div>

                                <div>
                                    <label for="schoolID_{{ $guru->gn_id }}"
                                        class="app-label">
                                        School
                                    </label>
                                    <select id="schoolID_{{ $guru->gn_id }}"
                                        name="schoolID"
                                        required
                                        class="app-select">
                                        @foreach($schools as $school)
                                            <option value="{{ $school->schoolID }}"
                                                {{ $guru->schoolID == $school->schoolID ? 'selected' : '' }}>
                                                {{ $school->schoolName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- CURRENT STATUS --}}
                                <div>
                                    <label for="current_status_{{ $guru->gn_id }}"
                                        class="app-label">
                                        Current Status
                                    </label>

                                    <select id="current_status_{{ $guru->gn_id }}"
                                        name="current_status"
                                        required
                                        class="app-select">

                                        @if($guru->current_status === 'Complete')
                                            <option value="Complete" selected>
                                                Complete (Automatically Assigned)
                                            </option>

                                            <option value="Inactive">
                                                Inactive
                                            </option>
                                        @else
                                            <option value="Inactive"
                                                {{ $guru->current_status === 'Inactive' ? 'selected' : '' }}>
                                                Inactive
                                            </option>

                                            <option value="Active"
                                                {{ $guru->current_status === 'Active' ? 'selected' : '' }}>
                                                Active
                                            </option>
                                        @endif
                                    </select>

                                    @if($guru->current_status === 'Complete')
                                        <p class="text-[11px] text-slate-500 mt-2">
                                            Complete is automatically assigned.
                                            You may update teacher details or change
                                            the status to Inactive if the teacher
                                            is no longer employed.
                                        </p>
                                    @else
                                        <p class="text-[11px] text-slate-500 mt-2">
                                            Complete is assigned automatically when all observation,
                                            training and feedback requirements are fulfilled.
                                        </p>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- MODAL FOOTER --}}
                    <div class="px-6 py-5 border-t border-slate-100 flex justify-end gap-3">
                        <button type="button"
                            onclick="closeModal('{{ $guru->gn_id }}')"
                            class="app-button-outline">
                            Cancel
                        </button>

                        <button type="submit"
                            class="app-button-primary">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    {{-- JAVASCRIPT --}}
    <script>
        function openModal(id) {
            const modal = document.getElementById('modal-' + id);

            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById('modal-' + id);

            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        }
    </script>
</x-app-layout>
