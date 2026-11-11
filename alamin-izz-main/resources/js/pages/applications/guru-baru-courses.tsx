import ViewTrainingButton from '@/components/view-training-button';
import AppLayout from '@/layouts/app-layout';
import { formatMode } from '@/lib/utils';
import { type PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import { GraduationCap, Monitor, Search, X } from 'lucide-react';
import { useState } from 'react';

interface Course {
    id: string;
    courseName: string;
    status: string;
    organiser: string;
    mode: string | null;
    can_view: boolean;
    access_until: string | null;
}

interface Props extends PageProps {
    courses: Course[];
}

const TRAINING_STATUS: Record<string, { label: string; className: string }> = {
    upcoming: { label: 'Upcoming', className: 'bg-blue-100 text-blue-700 border-blue-200' },
    ongoing:  { label: 'Ongoing',  className: 'bg-amber-100 text-amber-700 border-amber-200' },
    completed: { label: 'Completed', className: 'bg-green-100 text-green-700 border-green-200' },
};

const STATUS_FILTERS = [
    { value: '',         label: 'All Status' },
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'ongoing',  label: 'Ongoing' },
    { value: 'completed', label: 'Completed' },
];

export default function GuruBaruCourses({ courses }: Props) {
    const [search, setSearch]       = useState('');
    const [statusFilter, setStatus] = useState('');

    const filtered = courses.filter((c) => {
        const matchSearch = c.courseName.toLowerCase().includes(search.toLowerCase()) ||
                            c.organiser.toLowerCase().includes(search.toLowerCase());
        const matchStatus = statusFilter === '' || c.status === statusFilter;
        return matchSearch && matchStatus;
    });

    return (
        <AppLayout>
            <Head title="My Trainings" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                <div className="mb-6 flex items-center gap-2">
                    <GraduationCap className="h-6 w-6 text-blue-500" />
                    <h1 className="text-2xl font-bold text-gray-900">My Trainings</h1>
                    <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                        Auto-enrolled
                    </span>
                </div>

                {/* Search + filter bar */}
                <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Search by training name or organiser..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-9 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                        />
                        {search && (
                            <button onClick={() => setSearch('')} className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <X className="h-3.5 w-3.5" />
                            </button>
                        )}
                    </div>
                    <select
                        value={statusFilter}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                    >
                        {STATUS_FILTERS.map((f) => (
                            <option key={f.value} value={f.value}>{f.label}</option>
                        ))}
                    </select>
                </div>

                {courses.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center">
                        <GraduationCap className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">No series trainings available yet.</p>
                    </div>
                ) : filtered.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-200 p-10 flex-1 flex flex-col items-center justify-center">
                        <Search className="mx-auto mb-2 h-8 w-8 text-gray-300" />
                        <p className="text-sm text-gray-500">No trainings match your search.</p>
                        <button onClick={() => { setSearch(''); setStatus(''); }} className="mt-2 text-xs text-blue-600 hover:underline">
                            Clear filters
                        </button>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-6 py-3">Training</th>
                                    <th className="px-6 py-3">Mode</th>
                                    <th className="px-6 py-3">Status</th>
                                    <th className="px-6 py-3">Enrolment</th>
                                    <th className="px-6 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {filtered.map((course) => {
                                    const statusConf = TRAINING_STATUS[course.status] ?? null;
                                    return (
                                        <tr key={course.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <p className="font-semibold text-gray-900">{course.courseName}</p>
                                                <p className="mt-0.5 text-xs text-gray-400">{course.organiser}</p>
                                            </td>
                                            <td className="px-6 py-4">
                                                {course.mode ? (
                                                    <span className="inline-flex items-center gap-1.5 text-sm text-gray-600">
                                                        <Monitor className="h-3.5 w-3.5 text-gray-400" />
                                                        {formatMode(course.mode)}
                                                    </span>
                                                ) : <span className="text-gray-400">—</span>}
                                            </td>
                                            <td className="px-6 py-4">
                                                {statusConf ? (
                                                    <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${statusConf.className}`}>
                                                        {statusConf.label}
                                                    </span>
                                                ) : <span className="text-gray-400">—</span>}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                    <GraduationCap className="h-3.5 w-3.5" />
                                                    Auto-enrolled
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <ViewTrainingButton
                                                    href={route('trainings.show', course.id) + '?from=my_courses'}
                                                    canView={course.can_view}
                                                    accessUntil={course.access_until}
                                                />
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
