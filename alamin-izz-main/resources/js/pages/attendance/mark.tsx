import AppLayout from '@/layouts/app-layout';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type PageProps, type Training } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, CheckCircle, Clock, XCircle } from 'lucide-react';
import { useState } from 'react';

interface Session {
    session_id: string;
    date: string;
    start_time: string | null;
    end_time: string | null;
    status: 'active' | 'attended' | 'upcoming' | 'missed';
}

interface Props extends PageProps {
    training: Training;
    sessions: Session[];
}

const STATUS_CONFIG = {
    attended: { label: 'Attended',  className: 'bg-green-100 text-green-700' },
    active:   { label: 'Active Now', className: 'bg-blue-100 text-blue-700' },
    upcoming: { label: 'Upcoming',  className: 'bg-gray-100 text-gray-500' },
    missed:   { label: 'Missed',    className: 'bg-red-100 text-red-500' },
};

function formatTime(time: string | null) {
    if (!time) return '—';
    const [h, m] = time.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    return `${hour % 12 || 12}:${m} ${ampm}`;
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-MY', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

export default function MarkAttendance({ training, sessions }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [selected, setSelected] = useState<Session | null>(null);
    const [remarks, setRemarks] = useState('');
    const [processing, setProcessing] = useState(false);

    function openModal(session: Session) {
        setSelected(session);
    }

    function closeModal() {
        if (!processing) {
            setSelected(null);
            setRemarks('');
        }
    }

    function confirmAttendance() {
        if (!selected) return;
        setProcessing(true);
        router.post(
            route('attendance.mark', training.id),
            { session_id: selected.session_id, remarks: remarks.trim() || null },
            {
                preserveScroll: true,
                onSuccess: () => { setSelected(null); setRemarks(''); setProcessing(false); },
                onError: ()   => { setProcessing(false); },
            },
        );
    }

    return (
        <AppLayout>
            <Head title={`Mark Attendance — ${training.courseName}`} />
            <div className="mx-auto w-full max-w-2xl flex flex-1 flex-col px-6 py-8 sm:px-8">
                <Link
                    href={route('trainings.show', training.id)}
                    className="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
                >
                    ← Back to {training.courseName}
                </Link>

                <div className="mb-2 flex items-center gap-2">
                    <CalendarDays className="h-5 w-5 text-blue-600" />
                    <h1 className="text-xl font-bold text-gray-900">Mark Attendance</h1>
                </div>
                <p className="mb-6 text-sm text-gray-500">
                    Click <strong>Mark Present</strong> on the active session to record your attendance.
                </p>

                {flash?.success && (
                    <div className="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                        <CheckCircle className="h-4 w-4 shrink-0" />
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-4 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        <XCircle className="h-4 w-4 shrink-0" />
                        {flash.error}
                    </div>
                )}

                <div className="space-y-3">
                    {sessions.length === 0 && (
                        <div className="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-400">
                            No sessions available for this training.
                        </div>
                    )}
                    {sessions.map((session) => {
                        const cfg = STATUS_CONFIG[session.status];
                        return (
                            <div
                                key={session.session_id}
                                className={`flex items-center justify-between rounded-xl border bg-white px-5 py-4 ${
                                    session.status === 'active' ? 'border-blue-300 shadow-sm' : 'border-gray-200'
                                }`}
                            >
                                <div className="space-y-1">
                                    <p className="text-sm font-semibold text-gray-800">{formatDate(session.date)}</p>
                                    <div className="flex items-center gap-1.5 text-xs text-gray-500">
                                        <Clock className="h-3.5 w-3.5" />
                                        {formatTime(session.start_time)} – {formatTime(session.end_time)}
                                    </div>
                                </div>

                                <div className="flex items-center gap-3">
                                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${cfg.className}`}>
                                        {cfg.label}
                                    </span>
                                    {session.status === 'active' && (
                                        <button
                                            onClick={() => openModal(session)}
                                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                        >
                                            Mark Present
                                        </button>
                                    )}
                                    {session.status === 'attended' && (
                                        <CheckCircle className="h-5 w-5 text-green-500" />
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Confirmation modal */}
            <Dialog open={!!selected} onOpenChange={closeModal}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Confirm Attendance</DialogTitle>
                    </DialogHeader>

                    {selected && (
                        <div className="space-y-3 py-2">
                            <p className="text-sm text-gray-600">
                                You are about to mark yourself as <strong>present</strong> for:
                            </p>
                            <div className="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 space-y-1">
                                <p className="text-sm font-semibold text-gray-800">{training.courseName}</p>
                                <p className="text-xs text-gray-500">{formatDate(selected.date)}</p>
                                <p className="text-xs text-gray-500">
                                    {formatTime(selected.start_time)} – {formatTime(selected.end_time)}
                                </p>
                            </div>
                            <div>
                                <label htmlFor="attendance-remarks" className="mb-1 block text-xs font-medium text-gray-600">
                                    Remarks <span className="text-gray-400">(optional)</span>
                                </label>
                                <textarea
                                    id="attendance-remarks"
                                    value={remarks}
                                    onChange={(e) => setRemarks(e.target.value)}
                                    maxLength={500}
                                    rows={3}
                                    placeholder="Add a note about this attendance, e.g. arrived late, left early..."
                                    className="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                                />
                            </div>
                            <p className="text-xs text-gray-400">This action cannot be undone.</p>
                        </div>
                    )}

                    <DialogFooter>
                        <button
                            onClick={closeModal}
                            disabled={processing}
                            className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={confirmAttendance}
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {processing ? 'Submitting...' : 'Confirm Attendance'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
