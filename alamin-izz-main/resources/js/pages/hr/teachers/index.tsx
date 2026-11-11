import AppLayout from '@/layouts/app-layout';
import { type PageProps } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Building2, ChevronRight, GraduationCap, Search, User, Users } from 'lucide-react';
import { useState } from 'react';

interface School {
    id: string;
    name: string;
    teacher_count: number;
    guru_besar: string;
}

interface Teacher {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    gender: string | null;
}

interface Props extends PageProps {
    schools: School[];
    teachers: Teacher[];
    selected_school: School | null;
    filters: { search: string; schoolId: string | null };
}

export default function HrTeachers({ schools, teachers, selected_school, filters }: Props) {
    const [search, setSearch]           = useState(filters.search ?? '');
    const [schoolSearch, setSchoolSearch] = useState('');

    function selectSchool(schoolId: string) {
        router.get(route('hr.teachers.index'), { school_id: schoolId, search: '' }, { preserveState: false });
        setSearch('');
    }

    function backToSchools() {
        router.get(route('hr.teachers.index'), {}, { preserveState: false });
    }

    function applySearch() {
        router.get(route('hr.teachers.index'), { school_id: filters.schoolId ?? '', search }, { preserveState: true });
    }

    /* ── School cards view ─────────────────────────────────────────────── */
    if (!selected_school) {
        return (
            <AppLayout>
                <Head title="HR — Teachers" />
                <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                    <div className="mb-6 flex items-center gap-3">
                        <GraduationCap className="h-6 w-6 text-blue-600" />
                        <h1 className="text-2xl font-bold text-gray-900">Teachers</h1>
                        <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                            {schools.length} schools
                        </span>
                    </div>

                    {/* School search */}
                    <div className="mb-6 relative max-w-sm">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            value={schoolSearch}
                            placeholder="Search school…"
                            onChange={e => setSchoolSearch(e.target.value)}
                            className="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                        />
                    </div>

                    {schools.filter(s => s.name.toLowerCase().includes(schoolSearch.toLowerCase())).length === 0 ? (
                        <div className="flex h-48 items-center justify-center rounded-xl border border-dashed border-gray-300">
                            <p className="text-sm text-gray-400">No schools found.</p>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {schools.filter(s => s.name.toLowerCase().includes(schoolSearch.toLowerCase())).map(school => (
                                <button
                                    key={school.id}
                                    onClick={() => selectSchool(school.id)}
                                    className="group flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm text-left transition-all hover:border-blue-300 hover:shadow-md"
                                >
                                    <div className="mb-4 flex items-start justify-between">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                            <Building2 className="h-5 w-5 text-blue-600" />
                                        </div>
                                        <ChevronRight className="h-4 w-4 text-gray-300 group-hover:text-blue-500 transition-colors" />
                                    </div>

                                    <p className="mb-1 font-semibold text-gray-900 leading-snug">{school.name}</p>

                                    <div className="mt-auto pt-3 space-y-1.5 border-t border-gray-100">
                                        <div className="flex items-center gap-1.5 text-xs text-gray-500">
                                            <User className="h-3.5 w-3.5 text-gray-400" />
                                            <span className="font-medium text-gray-700">GB:</span> {school.guru_besar}
                                        </div>
                                        <div className="flex items-center gap-1.5 text-xs text-gray-500">
                                            <Users className="h-3.5 w-3.5 text-gray-400" />
                                            {school.teacher_count} teacher{school.teacher_count !== 1 ? 's' : ''}
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </AppLayout>
        );
    }

    /* ── Teacher list view ─────────────────────────────────────────────── */
    return (
        <AppLayout>
            <Head title={`HR — ${selected_school.name}`} />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">

                {/* Back link */}
                <button onClick={backToSchools} className="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                    ← Back to Schools
                </button>

                {/* School header */}
                <div className="mb-5 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                <Building2 className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <h2 className="text-base font-bold text-gray-900">{selected_school.name}</h2>
                                <div className="flex items-center gap-1.5 text-sm text-gray-500">
                                    <User className="h-3.5 w-3.5 text-gray-400" />
                                    <span>Guru Besar:</span>
                                    <span className="font-medium text-gray-700">{selected_school.guru_besar}</span>
                                </div>
                            </div>
                        </div>
                        <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            {selected_school.teacher_count} teachers
                        </span>
                    </div>
                </div>

                {/* Search */}
                <div className="mb-4 relative max-w-sm">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        type="text"
                        value={search}
                        placeholder="Search by name or email…"
                        onChange={e => setSearch(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && applySearch()}
                        className="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                    />
                </div>

                {/* Teacher table */}
                <div className="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
                    {teachers.length === 0 ? (
                        <p className="p-10 text-center text-sm text-gray-400">No teachers found.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                <tr>
                                    <th className="px-5 py-3 text-left">Name</th>
                                    <th className="px-5 py-3 text-left">Email</th>
                                    <th className="px-5 py-3 text-left">Phone</th>
                                    <th className="px-5 py-3 text-left">Gender</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {teachers.map(t => (
                                    <tr key={t.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3 font-medium text-gray-800">{t.name}</td>
                                        <td className="px-5 py-3 text-gray-500">{t.email}</td>
                                        <td className="px-5 py-3 text-gray-500">{t.phone ?? '—'}</td>
                                        <td className="px-5 py-3 text-gray-500">{t.gender ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
