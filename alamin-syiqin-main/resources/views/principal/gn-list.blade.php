<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-8xl mx-auto">

            {{-- Page Heading --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 mb-7">

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
                                d="M18 18.72a9.094 9.094 0 003.741-.479
                                   3 3 0 00-4.682-2.72m.94 3.198
                                   .001.031c0 .225-.012.447-.037.666
                                   A11.944 11.944 0 0112 21
                                   c-2.17 0-4.207-.576-5.963-1.584
                                   A6.062 6.062 0 016 18.719
                                   m12 0a5.971 5.971 0 00-.941-3.197
                                   m0 0A5.995 5.995 0 0012 12.75
                                   a5.995 5.995 0 00-5.058 2.772
                                   m0 0a3 3 0 00-4.681 2.72
                                   8.986 8.986 0 003.74.477
                                   m.94-3.197a5.971 5.971 0 00-.94 3.197
                                   M15 6.75a3 3 0 11-6 0
                                   3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0
                                   2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0
                                   2.25 2.25 0 014.5 0z" />

                        </svg>

                    </div>


                    <div>

                        <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                            List New Teacher
                        </h1>

                        <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                            List of new teachers at your school.
                        </p>

                    </div>

                </div>


                <div class="bg-white border border-slate-200 rounded-2xl px-5 py-3.5 min-w-[150px] shadow-sm">

                    <p class="text-[10px] font-bold uppercase tracking-[0.07em] text-slate-400">
                        Total Teacher
                    </p>

                    <p class="text-[24px] font-extrabold text-slate-900 mt-0.5">
                        {{ $totalTeachers }}
                    </p>

                </div>

            </div>


            {{-- Teacher List --}}
            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                        <div>

                            <h2 class="text-[17px] font-extrabold text-slate-900">
                                New Teachers
                            </h2>

                            <p class="text-[12.5px] text-slate-500 mt-1">
                                View teachers registered under your school.
                            </p>

                        </div>


                        <form
                            method="GET"
                            action="{{ route('principal.gn.list') }}"
                            class="flex flex-col sm:flex-row sm:items-center gap-2">

                            <div class="relative w-full sm:w-[300px]">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400">

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0
                                           8 8 0 0 1 16 0Z" />

                                </svg>

                                <input
                                    type="text"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Search teacher..."
                                    class="app-input !pl-9">

                            </div>


                            <button
                                type="submit"
                                class="app-button-primary">

                                Search

                            </button>


                            @if(request('search'))

                            <a
                                href="{{ route('principal.gn.list') }}"
                                class="app-button-outline">

                                Clear

                            </a>

                            @endif

                        </form>

                    </div>

                </div>


                <div class="app-table-wrap">

                    <table class="app-table">

                        <thead>

                            <tr>

                                <th>No</th>

                                <th>Teacher Name</th>

                                <th>Email</th>

                                <th>Phone Number</th>

                                <th>Appointed Date</th>

                                <th class="!text-center">Status</th>

                                <th class="!text-center">Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($guruNew as $guru)

                            <tr>

                                <td>
                                    {{ $guruNew->firstItem() + $loop->index }}
                                </td>


                                <td>

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-200
                                                        flex items-center justify-center shrink-0">

                                            <span class="text-[12px] font-extrabold text-blue-600">
                                                {{ strtoupper(substr($guru->gn_name, 0, 1)) }}
                                            </span>

                                        </div>

                                        <p class="font-bold text-slate-800">
                                            {{ $guru->gn_name }}
                                        </p>

                                    </div>

                                </td>


                                <td class="text-slate-500">
                                    {{ $guru->email ?? '-' }}
                                </td>


                                <td class="text-slate-500">
                                    {{ $guru->phone_number ?? '-' }}
                                </td>


                                <td class="text-slate-500">

                                    {{ $guru->appointed_date
                                            ? \Carbon\Carbon::parse($guru->appointed_date)->format('d M Y')
                                            : '-' }}

                                </td>


                                <td class="!text-center">

                                    @if($guru->current_status === 'Active')

                                    <span class="app-badge app-badge-success">
                                        Active
                                    </span>

                                    @elseif($guru->current_status === 'Complete')

                                    <span class="app-badge app-badge-info">
                                        Complete
                                    </span>

                                    @else

                                    <span class="app-badge app-badge-danger">
                                        Inactive
                                    </span>

                                    @endif

                                </td>


                                <td class="!text-center">

                                    <button
                                        type="button"
                                        onclick="openTeacherModal(this)"
                                        data-teacher="{{ json_encode([
                                                'ic_number' => $guru->ic_number,
                                                'gn_name' => $guru->gn_name,
                                                'phone_number' => $guru->phone_number,
                                                'email' => $guru->email,
                                                'marital_status' => $guru->marital_status,
                                                'gender' => $guru->gender,
                                                'address' => $guru->address,
                                                'race' => $guru->race,
                                                'appointed_date' => $guru->appointed_date
                                                    ? \Carbon\Carbon::parse($guru->appointed_date)->format('d M Y')
                                                    : '-',
                                                'current_status' => $guru->current_status,
                                                'schoolName' => $guru->schoolName,
                                            ]) }}"
                                        class="app-button-outline">

                                        View

                                    </button>

                                </td>

                            </tr>

                            @empty

                            <tr>

                                <td
                                    colspan="7"
                                    class="!text-center !py-12 text-slate-400">

                                    No teachers found..

                                </td>

                            </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if($guruNew->hasPages())

                <div class="px-5 md:px-6 py-5 border-t border-slate-100">
                    {{ $guruNew->links() }}
                </div>

                @endif

            </div>

        </div>

    </div>


    {{-- Teacher Detail Modal --}}
    <div
        id="teacherModal"
        class="hidden fixed inset-0 z-[9999]">

        <div
            class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
            onclick="closeTeacherModal()">
        </div>


        <div class="relative min-h-screen flex items-center justify-center p-4 md:p-6">

            <div class="relative bg-white w-full max-w-4xl rounded-2xl
                        border border-slate-200 shadow-2xl overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-100">

                    <div class="flex items-start justify-between gap-5">

                        <div>

                            <p class="text-[10px] font-bold uppercase tracking-[0.08em] text-blue-600 mb-1">
                                New Teacher
                            </p>

                            <h2 class="text-[20px] font-extrabold text-slate-900">
                                Teacher Details
                            </h2>

                        </div>


                        <button
                            type="button"
                            onclick="closeTeacherModal()"
                            class="w-9 h-9 flex items-center justify-center rounded-xl
                                   border border-slate-200 text-slate-400
                                   hover:bg-slate-50 hover:text-slate-700 transition">

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
                                    d="M6 18L18 6M6 6l12 12" />

                            </svg>

                        </button>

                    </div>

                </div>


                <div class="p-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">School</p>

                            <p id="modalSchoolDetail" class="text-[13.5px] font-bold text-slate-800"></p>

                        </div>


                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">Appointed Date</p>

                            <p id="modalAppointedDate" class="text-[13.5px] font-bold text-slate-800"></p>

                        </div>


                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">IC Number</p>

                            <p id="modalIC" class="text-[13.5px] font-bold text-slate-800"></p>

                        </div>


                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">Current Status</p>

                            <span
                                id="modalStatus"
                                class="inline-flex px-3 py-1 rounded-full text-xs font-semibold">
                            </span>

                        </div>


                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">Full Name</p>

                            <p id="modalTeacherName" class="text-[13.5px] font-bold text-slate-800"></p>

                        </div>


                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">Marital Status</p>

                            <p id="modalMarital" class="text-[13.5px] font-bold text-slate-800"></p>

                        </div>


                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">Email</p>

                            <p id="modalEmail" class="text-[13.5px] font-bold text-slate-800 break-all"></p>

                        </div>


                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">Race</p>

                            <p id="modalRace" class="text-[13.5px] font-bold text-slate-800"></p>

                        </div>


                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">Phone Number</p>

                            <p id="modalPhone" class="text-[13.5px] font-bold text-slate-800"></p>

                        </div>


                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">Gender</p>

                            <p id="modalGender" class="text-[13.5px] font-bold text-slate-800"></p>

                        </div>


                        <div class="md:col-span-2 p-4 rounded-xl bg-slate-50 border border-slate-100">

                            <p class="app-label">Address</p>

                            <p id="modalAddress" class="text-[13.5px] font-bold text-slate-800 leading-6"></p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <script>
        // Open teacher modal
        function openTeacherModal(button) {

            const teacher = JSON.parse(
                button.dataset.teacher
            );

            document.getElementById('modalIC').textContent =
                teacher.ic_number || '-';

            document.getElementById('modalTeacherName').textContent =
                teacher.gn_name || '-';

            document.getElementById('modalPhone').textContent =
                teacher.phone_number || '-';

            document.getElementById('modalEmail').textContent =
                teacher.email || '-';

            document.getElementById('modalMarital').textContent =
                teacher.marital_status || '-';

            document.getElementById('modalGender').textContent =
                teacher.gender || '-';

            document.getElementById('modalRace').textContent =
                teacher.race || '-';

            document.getElementById('modalSchoolDetail').textContent =
                teacher.schoolName || '-';

            document.getElementById('modalAppointedDate').textContent =
                teacher.appointed_date || '-';

            document.getElementById('modalAddress').textContent =
                teacher.address || '-';


            const status =
                document.getElementById('modalStatus');

            status.textContent =
                teacher.current_status || '-';

            status.className =
                'inline-flex px-3 py-1 rounded-full text-xs font-semibold';


            if (teacher.current_status === 'Active') {

                status.classList.add(
                    'bg-green-100',
                    'text-green-700'
                );

            } else if (teacher.current_status === 'Complete') {

                status.classList.add(
                    'bg-blue-100',
                    'text-blue-700'
                );

            } else {

                status.classList.add(
                    'bg-red-100',
                    'text-red-700'
                );
            }


            document.getElementById(
                'teacherModal'
            ).classList.remove('hidden');

            document.body.classList.add(
                'overflow-hidden'
            );
        }


        // Close teacher modal
        function closeTeacherModal() {

            document.getElementById(
                'teacherModal'
            ).classList.add('hidden');

            document.body.classList.remove(
                'overflow-hidden'
            );
        }


        // Close modal using Escape
        document.addEventListener(
            'keydown',
            function(event) {

                if (event.key === 'Escape') {
                    closeTeacherModal();
                }
            }
        );
    </script>

</x-app-layout>