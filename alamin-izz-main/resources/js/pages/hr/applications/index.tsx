import AppLayout from '@/layouts/app-layout';
import { type PageProps } from '@/types';
import { Head, router } from '@inertiajs/react';
import { CheckCircle, Search, XCircle } from 'lucide-react';
import { useState } from 'react';

interface OutsiderApp {
    id: string;
    type: 'outsider';
    outsider_id: number;
    courseID: string;
    name: string;
    ic_number: string;
    phone: string;
    training: string;
    status: string;
    applied_at: string;
    rejection_reason: string | null;
}

interface GuruBaruApp {
    id: string;
    type: 'guru_baru';
    enrollment_id: number;
    courseID: string;
    name: string;
    email: string;
    phone: string;
    training: string;
    status: string;
    applied_at: string;
    rejection_reason: string | null;
}

interface Props extends PageProps {
    outsiders: OutsiderApp[];
    guru_baru: GuruBaruApp[];
    filters: { tab: string; status: string; search: string };
    counts: { outsider_pending: number; guru_baru_pending: number };
}

const STATUS_BADGE: Record<string, string> = {
    pending:  'bg-amber-100 text-amber-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
};

function RejectModal({ onConfirm, onCancel }: { onConfirm: (reason: string) => void; onCancel: () => void }) {
    const [reason, setReason] = useState('');
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 className="mb-3 text-base font-semibold text-gray-900">Reject Application</h3>
                <textarea
                    value={reason}
                    onChange={e => setReason(e.target.value)}
                    placeholder="Reason for rejection (optional)"
                    rows={3}
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                />
                <div className="mt-4 flex justify-end gap-2">
                    <button onClick={onCancel} className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button onClick={() => onConfirm(reason)} className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Confirm Reject</button>
                </div>
            </div>
        </div>
    );
}

export default function HrApplications({ outsiders, guru_baru, filters, counts }: Props) {
    const [tab, setTab]         = useState(filters.tab ?? 'all');
    const [status, setStatus]   = useState(filters.status ?? 'pending');
    const [search, setSearch]   = useState(filters.search ?? '');
    const [rejectTarget, setRejectTarget] = useState<OutsiderApp | GuruBaruApp | null>(null);

    function applyFilters(overrides: Partial<{ tab: string; status: string; search: string }>) {
        const params = { tab, status, search, ...overrides };
        router.get(route('hr.applications.index'), params, { preserveState: true });
    }

    function approve(app: OutsiderApp | GuruBaruApp) {
        if (app.type === 'outsider') {
            router.post(route('hr.applications.outsider.approve', { outsiderId: app.outsider_id, courseId: app.courseID }));
        } else {
            router.post(route('hr.applications.guru-baru.approve', { enrollment: app.enrollment_id }));
        }
    }

    function reject(app: OutsiderApp | GuruBaruApp, reason: string) {
        if (app.type === 'outsider') {
            router.post(route('hr.applications.outsider.reject', { outsiderId: app.outsider_id, courseId: app.courseID }), { reason });
        } else {
            router.post(route('hr.applications.guru-baru.reject', { enrollment: app.enrollment_id }), { reason });
        }
        setRejectTarget(null);
    }

    const allApps = [
        ...(tab !== 'guru_baru' ? outsiders : []),
        ...(tab !== 'outsider'  ? guru_baru  : []),
    ];

    return (
        <AppLayout>
            <Head title="HR — Applications" />
            {rejectTarget && <RejectModal onConfirm={r => reject(rejectTarget, r)} onCancel={() => setRejectTarget(null)} />}

            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">Applications</h1>

                {/* Tabs */}
                <div className="mb-4 flex gap-2">
                    {([
                        { key: 'all',       label: 'All',       count: counts.outsider_pending + counts.guru_baru_pending },
                        { key: 'outsider',  label: 'Outsider',  count: counts.outsider_pending },
                        { key: 'guru_baru', label: 'Guru Baru', count: counts.guru_baru_pending },
                    ] as const).map(t => (
                        <button key={t.key} onClick={() => { setTab(t.key); applyFilters({ tab: t.key }); }}
                            className={`flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${
                                tab === t.key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            }`}>
                            {t.label}
                            {t.count > 0 && <span className={`rounded-full px-1.5 text-xs font-bold ${tab === t.key ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700'}`}>{t.count}</span>}
                        </button>
                    ))}
                </div>

                {/* Filters */}
                <div className="mb-5 flex flex-wrap gap-3">
                    <div className="relative flex-1 min-w-48">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input type="text" value={search} placeholder="Search by name…"
                            onChange={e => setSearch(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && applyFilters({ search })}
                            className="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-blue-400 focus:outline-none" />
                    </div>
                    <div className="flex gap-1">
                        {(['pending','approved','rejected','all'] as const).map(s => (
                            <button key={s} onClick={() => { setStatus(s); applyFilters({ status: s }); }}
                                className={`rounded-full px-3 py-1.5 text-xs font-medium capitalize transition-colors ${
                                    status === s ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                }`}>{s}</button>
                        ))}
                    </div>
                </div>

                {/* Table */}
                <div className="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
                    {allApps.length === 0 ? (
                        <p className="p-10 text-center text-sm text-gray-400">No applications found.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                <tr>
                                    <th className="px-5 py-3 text-left">Name</th>
                                    <th className="px-5 py-3 text-left">Type</th>
                                    <th className="px-5 py-3 text-left">Training</th>
                                    <th className="px-5 py-3 text-left">Applied</th>
                                    <th className="px-5 py-3 text-left">Status</th>
                                    <th className="px-5 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {allApps.map(app => (
                                    <tr key={app.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3 font-medium text-gray-800">{app.name}</td>
                                        <td className="px-5 py-3">
                                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                app.type === 'outsider' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'
                                            }`}>
                                                {app.type === 'outsider' ? 'Outsider' : 'Guru Baru'}
                                            </span>
                                        </td>
                                        <td className="px-5 py-3 text-gray-500">{app.training}</td>
                                        <td className="px-5 py-3 text-gray-400">
                                            {app.applied_at ? new Date(app.applied_at).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'}
                                        </td>
                                        <td className="px-5 py-3">
                                            <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${STATUS_BADGE[app.status] ?? ''}`}>
                                                {app.status}
                                            </span>
                                        </td>
                                        <td className="px-5 py-3">
                                            {app.status === 'pending' && (
                                                <div className="flex gap-2">
                                                    <button onClick={() => approve(app)}
                                                        className="flex items-center gap-1 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">
                                                        <CheckCircle className="h-3.5 w-3.5" /> Approve
                                                    </button>
                                                    <button onClick={() => setRejectTarget(app)}
                                                        className="flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100">
                                                        <XCircle className="h-3.5 w-3.5" /> Reject
                                                    </button>
                                                </div>
                                            )}
                                            {app.status !== 'pending' && <span className="text-xs text-gray-300">—</span>}
                                        </td>
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
