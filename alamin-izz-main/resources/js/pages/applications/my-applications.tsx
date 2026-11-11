import ViewTrainingButton from '@/components/view-training-button';
import AppLayout from '@/layouts/app-layout';
import { type Application, type PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, ClipboardList, Search, XCircle } from 'lucide-react';
import { useState } from 'react';

interface Props extends PageProps {
    applications: Application[];
}

const APPROVAL_CONFIG: Record<string, { label: string; className: string; Icon: typeof CheckCircle }> = {
    pending:  { label: 'Pending',  className: 'bg-amber-100 text-amber-700 border-amber-200', Icon: AlertTriangle },
    approved: { label: 'Approved', className: 'bg-green-100 text-green-700 border-green-200', Icon: CheckCircle },
    rejected: { label: 'Rejected', className: 'bg-red-100 text-red-600 border-red-200',       Icon: XCircle },
};

const TRAINING_STATUS_CONFIG: Record<string, { label: string; className: string }> = {
    upcoming: { label: 'Upcoming', className: 'bg-blue-100 text-blue-700 border-blue-200' },
    ongoing:  { label: 'Ongoing',  className: 'bg-amber-100 text-amber-700 border-amber-200' },
    completed: { label: 'Completed', className: 'bg-green-100 text-green-700 border-green-200' },
};

const APPROVAL_FILTERS = [
    { label: 'All',      value: 'all' },
    { label: 'Pending',  value: 'pending' },
    { label: 'Approved', value: 'approved' },
    { label: 'Rejected', value: 'rejected' },
] as const;

const APPROVAL_CHIP_COLORS: Record<string, { active: string; inactive: string }> = {
    All:      { active: 'bg-gray-800 text-white border-gray-800',       inactive: 'border-gray-200 text-gray-600 hover:border-gray-400' },
    Pending:  { active: 'bg-amber-500 text-white border-amber-500',     inactive: 'border-gray-200 text-amber-700 hover:border-amber-300' },
    Approved: { active: 'bg-green-600 text-white border-green-600',     inactive: 'border-gray-200 text-green-700 hover:border-green-300' },
    Rejected: { active: 'bg-red-500 text-white border-red-500',         inactive: 'border-gray-200 text-red-600 hover:border-red-300' },
};

const TRAINING_FILTERS = [
    { label: 'All',      value: 'all' },
    { label: 'Upcoming', value: 'upcoming' },
    { label: 'Ongoing',  value: 'ongoing' },
    { label: 'Completed', value: 'completed' },
] as const;

const TRAINING_CHIP_COLORS: Record<string, { active: string; inactive: string }> = {
    All:      { active: 'bg-gray-800 text-white border-gray-800',       inactive: 'border-gray-200 text-gray-600 hover:border-gray-400' },
    Upcoming: { active: 'bg-blue-600 text-white border-blue-600',       inactive: 'border-gray-200 text-blue-700 hover:border-blue-300' },
    Ongoing:  { active: 'bg-amber-500 text-white border-amber-500',     inactive: 'border-gray-200 text-amber-700 hover:border-amber-300' },
    Completed: { active: 'bg-green-600 text-white border-green-600',     inactive: 'border-gray-200 text-green-700 hover:border-green-300' },
};

export default function MyApplications({ applications }: Props) {
    const [search,          setSearch]          = useState('');
    const [approvalFilter,  setApprovalFilter]  = useState<'all' | 'pending' | 'approved' | 'rejected'>('all');
    const [trainingFilter,  setTrainingFilter]  = useState<'all' | 'upcoming' | 'ongoing' | 'completed'>('all');

    const q = search.toLowerCase().trim();
    const visible = applications.filter((app) => {
        const matchApproval  = approvalFilter  === 'all' || app.status === approvalFilter;
        const matchTraining  = trainingFilter  === 'all' || app.training?.status === trainingFilter;
        const matchSearch    = !q || (app.training?.title ?? app.training?.courseName ?? '').toLowerCase().includes(q);
        return matchApproval && matchTraining && matchSearch;
    });

    const hasActiveFilter = search || approvalFilter !== 'all' || trainingFilter !== 'all';

    return (
        <AppLayout>
            <Head title="My Applications" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                <div className="mb-6 flex items-center gap-2">
                    <ClipboardList className="h-6 w-6 text-gray-500" />
                    <h1 className="text-2xl font-bold text-gray-900">My Applications</h1>
                </div>

                {applications.length === 0 ? (
                    <div className="flex-1 flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 p-12">
                        <ClipboardList className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">You haven't applied for any trainings yet.</p>
                        <Link
                            href={route('trainings.index')}
                            className="mt-4 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Browse Trainings
                        </Link>
                    </div>
                ) : (
                    <>
                        {/* Search + Filter */}
                        <div className="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            {/* Search bar */}
                            <div className="relative mb-4">
                                <Search className="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Search by training name…"
                                    className="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-800 placeholder-gray-400 transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
                                />
                                {search && (
                                    <button
                                        onClick={() => setSearch('')}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    >
                                        ✕
                                    </button>
                                )}
                            </div>

                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:divide-x sm:divide-gray-100">
                                {/* Approval status chips */}
                                <div className="flex flex-col gap-2">
                                    <p className="text-xs font-semibold uppercase tracking-wider text-gray-400">Application</p>
                                    <div className="flex flex-wrap gap-1.5">
                                        {APPROVAL_FILTERS.map((f) => {
                                            const active = approvalFilter === f.value;
                                            const colors = APPROVAL_CHIP_COLORS[f.label];
                                            return (
                                                <button
                                                    key={f.value}
                                                    onClick={() => setApprovalFilter(f.value)}
                                                    className={`rounded-full border px-3.5 py-1 text-xs font-semibold transition-all ${active ? colors.active : colors.inactive}`}
                                                >
                                                    {f.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                {/* Training status chips */}
                                <div className="flex flex-col gap-2 sm:pl-3">
                                    <p className="text-xs font-semibold uppercase tracking-wider text-gray-400">Training</p>
                                    <div className="flex flex-wrap gap-1.5">
                                        {TRAINING_FILTERS.map((f) => {
                                            const active = trainingFilter === f.value;
                                            const colors = TRAINING_CHIP_COLORS[f.label];
                                            return (
                                                <button
                                                    key={f.value}
                                                    onClick={() => setTrainingFilter(f.value)}
                                                    className={`rounded-full border px-3.5 py-1 text-xs font-semibold transition-all ${active ? colors.active : colors.inactive}`}
                                                >
                                                    {f.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>

                            {/* Active filter summary */}
                            {hasActiveFilter && (
                                <div className="mt-3 flex items-center gap-2 border-t border-gray-100 pt-3">
                                    <span className="text-xs text-gray-400">
                                        Showing {visible.length} of {applications.length} applications
                                    </span>
                                    <button
                                        onClick={() => { setSearch(''); setApprovalFilter('all'); setTrainingFilter('all'); }}
                                        className="ml-auto text-xs font-medium text-blue-600 hover:text-blue-800"
                                    >
                                        Clear all filters
                                    </button>
                                </div>
                            )}
                        </div>

                        {visible.length === 0 ? (
                            <div className="flex-1 flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 p-12">
                                <Search className="mx-auto mb-3 h-8 w-8 text-gray-300" />
                                <p className="text-sm text-gray-500">No applications match your search or filter.</p>
                                <button
                                    onClick={() => { setSearch(''); setApprovalFilter('all'); setTrainingFilter('all'); }}
                                    className="mt-2 text-xs text-blue-600 hover:underline"
                                >
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
                                        {visible.map((app) => {
                                            const approval = APPROVAL_CONFIG[app.status] ?? APPROVAL_CONFIG.pending;
                                            const ApprovalIcon = approval.Icon;
                                            const trainingStatus = app.training?.status
                                                ? (TRAINING_STATUS_CONFIG[app.training.status] ?? null)
                                                : null;

                                            return (
                                                <tr key={app.id} className="hover:bg-gray-50">
                                                    {/* Course */}
                                                    <td className="px-6 py-4">
                                                        <p className="font-semibold text-gray-900">
                                                            {app.training?.title ?? app.training?.courseName ?? '—'}
                                                        </p>
                                                        <p className="mt-0.5 text-xs text-gray-400">
                                                            Applied{' '}
                                                            {new Date(app.applied_at).toLocaleDateString('en-MY', {
                                                                day: '2-digit', month: 'short', year: 'numeric',
                                                            })}
                                                        </p>
                                                        {app.status === 'rejected' && app.rejection_reason && (
                                                            <p className="mt-1 text-xs text-red-500">
                                                                Reason: {app.rejection_reason}
                                                            </p>
                                                        )}
                                                    </td>

                                                    {/* Approval */}
                                                    <td className="px-6 py-4">
                                                        <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold ${approval.className}`}>
                                                            <ApprovalIcon className="h-3.5 w-3.5" />
                                                            {approval.label}
                                                        </span>
                                                    </td>

                                                    {/* Training Status */}
                                                    <td className="px-6 py-4">
                                                        {trainingStatus ? (
                                                            <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${trainingStatus.className}`}>
                                                                {trainingStatus.label}
                                                            </span>
                                                        ) : (
                                                            <span className="text-gray-400">—</span>
                                                        )}
                                                    </td>

                                                    {/* Action */}
                                                    <td className="px-6 py-4 text-center">
                                                        {app.training && (
                                                            <ViewTrainingButton
                                                                href={route('trainings.show', app.training.id) + '?from=applications'}
                                                                canView={app.can_view ?? true}
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
                    </>
                )}
            </div>
        </AppLayout>
    );
}
