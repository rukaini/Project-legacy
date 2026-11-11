import ViewTrainingButton from '@/components/view-training-button';
import AppLayout from '@/layouts/app-layout';
import { type PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, ClipboardList, Search, X, XCircle } from 'lucide-react';
import { useState } from 'react';

interface GuruBaruApp {
    id: string;
    status: 'pending' | 'approved' | 'rejected';
    course_category: string | null;
    applied_at: string | null;
    rejection_reason: string | null;
    training: {
        id: string;
        courseName: string;
        title: string;
        status: string;
    } | null;
    can_view: boolean;
    access_until: string | null;
}

interface Props extends PageProps {
    applications: GuruBaruApp[];
}

const STATUS_CONFIG: Record<string, { label: string; className: string; icon: typeof CheckCircle }> = {
    approved: { label: 'Approved', className: 'bg-green-100 text-green-700 border-green-200',   icon: CheckCircle },
    pending:  { label: 'Pending',  className: 'bg-amber-100 text-amber-700 border-amber-200',   icon: AlertTriangle },
    rejected: { label: 'Rejected', className: 'bg-red-100 text-red-600 border-red-200',         icon: XCircle },
};

const TRAINING_STATUS: Record<string, { label: string; className: string }> = {
    upcoming: { label: 'Upcoming', className: 'bg-blue-100 text-blue-700 border-blue-200' },
    ongoing:  { label: 'Ongoing',  className: 'bg-amber-100 text-amber-700 border-amber-200' },
    completed: { label: 'Completed', className: 'bg-green-100 text-green-700 border-green-200' },
};

const APPROVAL_FILTERS = [
    { value: '',         label: 'All Status' },
    { value: 'pending',  label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
];

const TRAINING_FILTERS = [
    { value: '',         label: 'All Training' },
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'ongoing',  label: 'Ongoing' },
    { value: 'completed', label: 'Completed' },
];

export default function GuruBaruApplications({ applications }: Props) {
    const [search, setSearch]           = useState('');
    const [approvalFilter, setApproval] = useState('');
    const [trainingFilter, setTraining] = useState('');

    const filtered = applications.filter((app) => {
        const matchSearch   = (app.training?.title ?? '').toLowerCase().includes(search.toLowerCase());
        const matchApproval = approvalFilter === '' || app.status === approvalFilter;
        const matchTraining = trainingFilter === '' || app.training?.status === trainingFilter;
        return matchSearch && matchApproval && matchTraining;
    });

    const clearAll = () => { setSearch(''); setApproval(''); setTraining(''); };

    return (
        <AppLayout>
            <Head title="My Applications" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                <div className="mb-6 flex items-center gap-2">
                    <ClipboardList className="h-6 w-6 text-gray-500" />
                    <h1 className="text-2xl font-bold text-gray-900">My Applications</h1>
                </div>

                {/* Search + filter bar */}
                <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Search by training name..."
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
                        value={approvalFilter}
                        onChange={(e) => setApproval(e.target.value)}
                        className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                    >
                        {APPROVAL_FILTERS.map((f) => (
                            <option key={f.value} value={f.value}>{f.label}</option>
                        ))}
                    </select>
                    <select
                        value={trainingFilter}
                        onChange={(e) => setTraining(e.target.value)}
                        className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                    >
                        {TRAINING_FILTERS.map((f) => (
                            <option key={f.value} value={f.value}>{f.label}</option>
                        ))}
                    </select>
                </div>

                {applications.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center">
                        <ClipboardList className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">No applications yet.</p>
                        <Link
                            href={route('trainings.index')}
                            className="mt-4 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Browse Trainings to Apply
                        </Link>
                    </div>
                ) : filtered.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-200 p-10 flex-1 flex flex-col items-center justify-center">
                        <Search className="mx-auto mb-2 h-8 w-8 text-gray-300" />
                        <p className="text-sm text-gray-500">No applications match your search.</p>
                        <button onClick={clearAll} className="mt-2 text-xs text-blue-600 hover:underline">
                            Clear filters
                        </button>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-6 py-3">Training</th>
                                    <th className="px-6 py-3">Approval</th>
                                    <th className="px-6 py-3">Training Status</th>
                                    <th className="px-6 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {filtered.map((app) => {
                                    const conf       = STATUS_CONFIG[app.status] ?? STATUS_CONFIG.pending;
                                    const StatusIcon = conf.icon;
                                    const trainingStatus = app.training?.status
                                        ? (TRAINING_STATUS[app.training.status] ?? null)
                                        : null;
                                    return (
                                        <tr key={app.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <p className="font-semibold text-gray-900">
                                                    {app.training?.title ?? '—'}
                                                </p>
                                                <p className="mt-0.5 text-xs text-gray-400">
                                                    {app.course_category?.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') ?? '—'}
                                                    {app.applied_at ? ` · Applied ${new Date(app.applied_at).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' })}` : ''}
                                                </p>
                                                {app.status === 'rejected' && app.rejection_reason && (
                                                    <p className="mt-1 text-xs text-red-500">Reason: {app.rejection_reason}</p>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold ${conf.className}`}>
                                                    <StatusIcon className="h-3.5 w-3.5" />
                                                    {conf.label}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                {trainingStatus ? (
                                                    <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${trainingStatus.className}`}>
                                                        {trainingStatus.label}
                                                    </span>
                                                ) : <span className="text-gray-400">—</span>}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                {app.training && (
                                                    <ViewTrainingButton
                                                        href={route('trainings.show', app.training.id) + '?from=my_applications'}
                                                        canView={app.can_view}
                                                        accessUntil={app.access_until}
                                                    />
                                                )}
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
