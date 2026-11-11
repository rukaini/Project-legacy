import AppLayout from '@/layouts/app-layout';
import { type PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle, ClipboardList, Search, XCircle } from 'lucide-react';
import { useState } from 'react';

interface AppItem {
    id: string;
    type: 'teacher';
    route_key: string;
    user_name: string;
    user_email: string;
    training_id: string | null;
    training_title: string;
    status: string;
    applied_at: string;
    rejection_reason: string | null;
}

interface Props extends PageProps {
    applications: AppItem[];
    filters: { status?: string };
}

const STATUS_BADGE: Record<string, { label: string; className: string }> = {
    pending:  { label: 'Pending',  className: 'bg-amber-100 text-amber-700' },
    approved: { label: 'Approved', className: 'bg-green-100 text-green-700' },
    rejected: { label: 'Rejected', className: 'bg-red-100 text-red-600' },
};

export default function ApplicationsIndex({ applications, filters }: Props) {
    const [rejectingItem, setRejectingItem] = useState<AppItem | null>(null);
    const [reason, setReason]               = useState('');
    const [search, setSearch]               = useState('');

    function filterStatus(status: string | undefined) {
        router.get(route('applications.index'), { status }, { preserveState: true });
    }

    const q = search.toLowerCase().trim();
    const visible = applications.filter(app => {
        if (!q) return true;
        return (
            app.user_name.toLowerCase().includes(q) ||
            app.user_email.toLowerCase().includes(q) ||
            app.training_title.toLowerCase().includes(q)
        );
    });

    function handleApprove(app: AppItem) {
        router.post(route('applications.approve', app.route_key), {}, { preserveScroll: true });
    }

    function handleConfirmReject() {
        if (!rejectingItem) return;
        router.post(route('applications.reject', rejectingItem.route_key), { reason }, {
            preserveScroll: true,
            onSuccess: () => {
                setRejectingItem(null);
                setReason('');
            },
        });
    }

    const statusFilters = [
        { label: 'All',      value: undefined },
        { label: 'Pending',  value: 'pending' },
        { label: 'Approved', value: 'approved' },
        { label: 'Rejected', value: 'rejected' },
    ];

    return (
        <AppLayout>
            <Head title="Applications" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                <div className="mb-6 flex items-center gap-2">
                    <ClipboardList className="h-6 w-6 text-gray-500" />
                    <h1 className="text-2xl font-bold text-gray-900">Applications</h1>
                </div>

                {/* Search + Filters */}
                <div className="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    {/* Search bar */}
                    <div className="relative mb-4">
                        <Search className="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            placeholder="Search by name, email or training…"
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

                    {/* Status filter */}
                    <div className="flex flex-col gap-1.5">
                        <p className="text-xs font-semibold uppercase tracking-wider text-gray-400">Status</p>
                        <div className="flex flex-wrap gap-1.5">
                            {statusFilters.map((f) => {
                                const active = (filters.status ?? undefined) === f.value;
                                const colors: Record<string, string> = {
                                    All:      active ? 'bg-primary text-primary-foreground border-primary' : 'border-gray-200 text-gray-600 hover:border-gray-400',
                                    Pending:  active ? 'bg-warning text-white border-warning' : 'border-gray-200 text-warning hover:border-amber-300',
                                    Approved: active ? 'bg-success text-white border-success' : 'border-gray-200 text-success hover:border-green-300',
                                    Rejected: active ? 'bg-danger text-white border-danger' : 'border-gray-200 text-danger hover:border-red-300',
                                };
                                return (
                                    <button
                                        key={f.label}
                                        onClick={() => filterStatus(f.value)}
                                        className={`rounded-full border px-3.5 py-1 text-xs font-semibold transition-all ${colors[f.label]}`}
                                    >
                                        {f.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Active filter summary */}
                    {(search || filters.status) && (
                        <div className="mt-3 flex items-center gap-2 border-t border-gray-100 pt-3">
                            <span className="text-xs text-gray-400">Showing {visible.length} of {applications.length} applications</span>
                            <button
                                onClick={() => { setSearch(''); filterStatus(undefined); }}
                                className="ml-auto text-xs font-medium text-blue-600 hover:text-blue-800"
                            >
                                Clear all filters
                            </button>
                        </div>
                    )}
                </div>

                {visible.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center">
                        <ClipboardList className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">No applications match your search or filter.</p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table className="w-full text-sm">
                            <thead className="border-b border-gray-100 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Name</th>
                                    <th className="px-4 py-3 font-medium">Training</th>
                                    <th className="px-4 py-3 font-medium">Applied At</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {visible.map((app) => {
                                    const badge = STATUS_BADGE[app.status] ?? STATUS_BADGE.pending;
                                    return (
                                        <tr key={app.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-gray-900">{app.user_name}</div>
                                                <div className="text-xs text-gray-500">{app.user_email}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                {app.training_id ? (
                                                    <Link
                                                        href={route('trainings.show', app.training_id) + '?from=applications'}
                                                        className="text-blue-600 hover:underline"
                                                    >
                                                        {app.training_title}
                                                    </Link>
                                                ) : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">
                                                {new Date(app.applied_at).toLocaleDateString('en-MY', {
                                                    day: '2-digit', month: 'short', year: 'numeric',
                                                })}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${badge.className}`}>
                                                    {badge.label}
                                                </span>
                                                {app.rejection_reason && (
                                                    <p className="mt-0.5 text-xs text-red-500">{app.rejection_reason}</p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {app.status === 'pending' && (
                                                    <div className="inline-flex gap-2">
                                                        <button
                                                            onClick={() => handleApprove(app)}
                                                            className="inline-flex items-center gap-1 rounded-md bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 hover:bg-green-100"
                                                        >
                                                            <CheckCircle className="h-3.5 w-3.5" /> Approve
                                                        </button>
                                                        <button
                                                            onClick={() => setRejectingItem(app)}
                                                            className="inline-flex items-center gap-1 rounded-md bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-100"
                                                        >
                                                            <XCircle className="h-3.5 w-3.5" /> Reject
                                                        </button>
                                                    </div>
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

            {/* Reject modal */}
            {rejectingItem !== null && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                        <h2 className="mb-2 text-lg font-semibold text-gray-900">Reject application</h2>
                        <p className="mb-4 text-sm text-gray-500">
                            Optionally provide a reason. The applicant will see this in their list.
                        </p>
                        <textarea
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            rows={3}
                            placeholder="e.g. Maximum capacity reached"
                            className="w-full rounded-lg border border-gray-200 p-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                        />
                        <div className="mt-4 flex justify-end gap-2">
                            <button
                                onClick={() => { setRejectingItem(null); setReason(''); }}
                                className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleConfirmReject}
                                className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                            >
                                Confirm Reject
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
