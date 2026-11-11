import AppLayout from '@/layouts/app-layout';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type PageProps } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarClock, CheckCircle2, ChevronDown, Clock, ListChecks, MapPin, Pencil, Plus, Trash2, UserRound, Users } from 'lucide-react';
import { useRole } from '@/lib/auth';
import { useState } from 'react';

interface Attendee {
    attendance_id: number;
    teacher_id: string;
    name: string;
    is_assigned: boolean;
    hours_ladap: number;
    remarks: string | null;
    status: 'pending' | 'approved' | 'rejected';
}

interface SessionRow {
    id: number;
    title: string;
    description: string | null;
    session_date: string;
    start_time: string;
    end_time: string;
    location: string | null;
    assigned_teacher_id: string;
    assigned_teacher: string | null;
    status: 'upcoming' | 'active' | 'completed';
    session_hours: number;
    has_attended: boolean;
    earned_hours: number | null;
    attendance_status: 'pending' | 'approved' | 'rejected' | null;
    attendance_remarks: string | null;
    is_assigned_to_me: boolean;
    attendees: Attendee[];
}

interface TeacherOption {
    id: string;
    label: string;
}

interface Props extends PageProps {
    sessions: SessionRow[];
    teachers: TeacherOption[];
    can_manage: boolean;
    current_teacher_id: string | null;
    total_ladap_hours: number;
    ladap_target_hours: number;
}

function fmtTime(t: string) {
    const [h, m] = t.split(':');
    const hour = parseInt(h, 10);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const hour12 = ((hour + 11) % 12) + 1;
    return `${hour12}:${m} ${suffix}`;
}

function fmtDate(d: string) {
    return new Date(d + 'T00:00:00').toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

function inputCls(error?: string) {
    return `w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-1 ${
        error
            ? 'border-red-400 focus:border-red-500 focus:ring-red-300'
            : 'border-gray-200 focus:border-blue-400 focus:ring-blue-300'
    }`;
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>
            {children}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}

/** Create a session, or edit `session` when given. */
function RegisterForm({ teachers, session, onDone }: { teachers: TeacherOption[]; session?: SessionRow; onDone: () => void }) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        title:               session?.title ?? '',
        description:         session?.description ?? '',
        session_date:        session?.session_date ?? '',
        start_time:          session?.start_time.slice(0, 5) ?? '',
        end_time:            session?.end_time.slice(0, 5) ?? '',
        location:            session?.location ?? '',
        assigned_teacher_id: session?.assigned_teacher_id ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onDone();
            },
        };
        if (session) {
            put(route('penyelaras-ladap.update', session.id), options);
        } else {
            post(route('penyelaras-ladap.store'), options);
        }
    }

    return (
        <form onSubmit={submit} className="space-y-4 border-t border-gray-100 p-5">
            <Field label="Title" error={errors.title}>
                <input
                    type="text"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    className={inputCls(errors.title)}
                    placeholder="Penyelaras Ladap session name"
                />
            </Field>

            <Field label="Description" error={errors.description}>
                <textarea
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={3}
                    className={inputCls(errors.description)}
                />
            </Field>

            <Field label="Assigned Teacher" error={errors.assigned_teacher_id}>
                <select
                    value={data.assigned_teacher_id}
                    onChange={(e) => setData('assigned_teacher_id', e.target.value)}
                    className={inputCls(errors.assigned_teacher_id)}
                >
                    <option value="">— Select teacher —</option>
                    {teachers.map((t) => (
                        <option key={t.id} value={t.id}>{t.label}</option>
                    ))}
                </select>
            </Field>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Field label="Date" error={errors.session_date}>
                    <input
                        type="date"
                        value={data.session_date}
                        onChange={(e) => setData('session_date', e.target.value)}
                        className={inputCls(errors.session_date)}
                    />
                </Field>
                <Field label="Start Time" error={errors.start_time}>
                    <input
                        type="time"
                        value={data.start_time}
                        onChange={(e) => setData('start_time', e.target.value)}
                        className={inputCls(errors.start_time)}
                    />
                </Field>
                <Field label="End Time" error={errors.end_time}>
                    <input
                        type="time"
                        value={data.end_time}
                        onChange={(e) => setData('end_time', e.target.value)}
                        className={inputCls(errors.end_time)}
                    />
                </Field>
            </div>

            <Field label="Location" error={errors.location}>
                <input
                    type="text"
                    value={data.location}
                    onChange={(e) => setData('location', e.target.value)}
                    className={inputCls(errors.location)}
                    placeholder="Venue"
                />
            </Field>

            <div className="flex justify-end">
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                >
                    {processing ? 'Saving...' : session ? 'Save Changes' : 'Save Session'}
                </button>
            </div>
        </form>
    );
}

function LadapStats({ total, target }: { total: number; target: number }) {
    const [open, setOpen] = useState(false);
    const pct = Math.min(100, Math.round((total / target) * 100));
    const remaining = Math.max(0, target - total);
    const barColor = 'bg-blue-600';
    const valueColor = 'text-blue-600';

    return (
        <div className="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                className="flex w-full items-center gap-4 px-5 py-4 text-left"
            >
                <div className="flex shrink-0 items-center gap-2">
                    <Clock className="h-4 w-4 text-gray-400" />
                    <span className="text-sm font-semibold text-gray-700">LADAP Hours</span>
                </div>
                <div className="flex min-w-0 flex-1 items-center gap-3">
                    <div className="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                        <div className={`h-full rounded-full transition-all ${barColor}`} style={{ width: `${pct}%` }} />
                    </div>
                    <span className={`shrink-0 text-xs font-bold ${valueColor}`}>
                        {total.toFixed(2)} / {target} hrs
                    </span>
                </div>
                <ChevronDown className={`h-4 w-4 shrink-0 text-gray-400 transition-transform ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <div className="grid grid-cols-3 gap-4 border-t border-gray-100 px-5 py-4">
                    <div>
                        <p className="text-xs text-gray-400">Earned</p>
                        <p className="text-lg font-bold text-blue-600">{total.toFixed(2)} <span className="text-xs font-normal text-gray-400">hrs</span></p>
                    </div>
                    <div>
                        <p className="text-xs text-gray-400">Target</p>
                        <p className="text-lg font-bold text-gray-900">{target} <span className="text-xs font-normal text-gray-400">hrs</span></p>
                    </div>
                    <div>
                        <p className="text-xs text-gray-400">Remaining</p>
                        <p className="text-lg font-bold text-gray-500">{remaining.toFixed(2)} <span className="text-xs font-normal text-gray-400">hrs</span></p>
                    </div>
                </div>
            )}
        </div>
    );
}

export default function PenyelarasLadapIndex({ sessions, teachers, can_manage, current_teacher_id, total_ladap_hours, ladap_target_hours }: Props) {
    const { isGuruBesar, isTeacher } = useRole();
    const canManage = can_manage && isGuruBesar;
    const [showForm, setShowForm] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<SessionRow | null>(null);
    const [editTarget, setEditTarget] = useState<SessionRow | null>(null);
    const [markTarget, setMarkTarget] = useState<SessionRow | null>(null);
    const [attendeesSessionId, setAttendeesSessionId] = useState<number | null>(null);
    const attendeesTarget = sessions.find((s) => s.id === attendeesSessionId) ?? null;
    const [processing, setProcessing] = useState(false);
    const [approveTarget, setApproveTarget] = useState<Attendee | null>(null);

    function confirmDelete() {
        if (!deleteTarget) return;
        setProcessing(true);
        router.delete(route('penyelaras-ladap.destroy', deleteTarget.id), {
            preserveScroll: true,
            onSuccess: () => { setDeleteTarget(null); setProcessing(false); },
            onError:   () => { setProcessing(false); },
        });
    }

    function confirmMark() {
        if (!markTarget) return;
        setProcessing(true);
        router.post(route('penyelaras-ladap.mark', markTarget.id), {}, {
            preserveScroll: true,
            onSuccess: () => { setMarkTarget(null); setProcessing(false); },
            onError:   () => { setProcessing(false); },
        });
    }

    return (
        <AppLayout>
            <Head title={isTeacher ? 'LADAP' : 'Penyelaras Ladap'} />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">

                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100">
                            <Users className="h-5 w-5 text-gray-500" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{isTeacher ? 'LADAP' : 'Penyelaras Ladap'}</h1>
                            <p className="text-sm text-gray-500">
                                {isTeacher
                                    ? 'Mark attendance to earn LADAP hours — the assigned teacher earns more'
                                    : 'LADAP sessions for your school, with an assigned teacher'}
                            </p>
                        </div>
                    </div>

                    {canManage && (
                    <button
                        onClick={() => setShowForm((s) => !s)}
                        className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        <Plus className="h-4 w-4" />
                        New Session
                        <ChevronDown className={`h-4 w-4 transition-transform ${showForm ? 'rotate-180' : ''}`} />
                    </button>
                    )}
                </div>

                {!canManage && (
                    <LadapStats total={total_ladap_hours} target={ladap_target_hours} />
                )}

                {canManage && showForm && (
                    <div className="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <RegisterForm teachers={teachers} onDone={() => setShowForm(false)} />
                    </div>
                )}

                {sessions.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 p-12">
                        <Users className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">No {isTeacher ? 'LADAP' : 'Penyelaras Ladap'} sessions yet.</p>
                    </div>
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {sessions.map((s) => (
                            <div
                                key={s.id}
                                className="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div className="mb-3 flex items-start justify-between gap-2">
                                    <h3 className="font-semibold leading-snug text-gray-900">{s.title}</h3>
                                    <div className="flex shrink-0 items-center gap-2">
                                        {s.status === 'active' && (
                                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Active</span>
                                        )}
                                        {s.status === 'completed' && (
                                            <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Completed</span>
                                        )}
                                        {canManage && (
                                        <button
                                            onClick={() => setEditTarget(s)}
                                            title="Edit session"
                                            className="rounded-md p-1 text-gray-300 hover:bg-blue-50 hover:text-blue-600"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </button>
                                        )}
                                        {canManage && (
                                        <button
                                            onClick={() => setDeleteTarget(s)}
                                            title="Delete session"
                                            className="rounded-md p-1 text-gray-300 hover:bg-red-50 hover:text-red-600"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                        )}
                                    </div>
                                </div>

                                {s.description && (
                                    <p className="mb-4 line-clamp-2 text-sm text-gray-500">{s.description}</p>
                                )}

                                <div className="mt-auto space-y-1.5 text-sm text-gray-600">
                                    <div className="flex items-center gap-2">
                                        <CalendarClock className="h-3.5 w-3.5 text-gray-400" />
                                        {fmtDate(s.session_date)}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Clock className="h-3.5 w-3.5 text-gray-400" />
                                        {fmtTime(s.start_time)} – {fmtTime(s.end_time)}
                                    </div>
                                    {s.location && (
                                        <div className="flex items-center gap-2">
                                            <MapPin className="h-3.5 w-3.5 text-gray-400" />
                                            {s.location}
                                        </div>
                                    )}
                                    <div className="flex items-center gap-2">
                                        <UserRound className="h-3.5 w-3.5 text-gray-400" />
                                        {s.assigned_teacher ?? 'No teacher assigned'}
                                        {current_teacher_id && s.assigned_teacher?.startsWith(current_teacher_id + ' ') && (
                                            <span className="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Assigned to you</span>
                                        )}
                                    </div>
                                </div>

                                {canManage && (
                                    <button
                                        onClick={() => setAttendeesSessionId(s.id)}
                                        className="mt-4 flex items-center justify-center gap-2 rounded-lg border border-gray-200 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                                    >
                                        <ListChecks className="h-4 w-4" />
                                        View Attendees ({s.attendees.length})
                                    </button>
                                )}

                                {isTeacher && (
                                    <div className="mt-4">
                                        {s.has_attended ? (
                                            <>
                                                <span className={`flex items-center justify-center gap-2 rounded-lg py-2 text-sm font-semibold ${
                                                    s.attendance_status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'
                                                }`}>
                                                    <CheckCircle2 className="h-4 w-4" />
                                                    {s.attendance_status === 'approved'
                                                        ? `Approved — +${(s.earned_hours ?? 0).toFixed(2)} hrs`
                                                        : 'Pending approval'}
                                                </span>
                                                {s.attendance_status === 'approved' && s.attendance_remarks && (
                                                    <p className="mt-2 text-xs text-gray-500">Remarks: {s.attendance_remarks}</p>
                                                )}
                                            </>
                                        ) : s.status === 'active' ? (
                                            <button
                                                onClick={() => setMarkTarget(s)}
                                                className="block w-full rounded-lg bg-blue-600 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700"
                                            >
                                                Mark Attendance
                                            </button>
                                        ) : (
                                            <span className="block w-full rounded-lg bg-gray-100 py-2 text-center text-sm font-semibold text-gray-400">
                                                Not Active
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Edit session */}
            <Dialog open={!!editTarget} onOpenChange={(open) => !open && setEditTarget(null)}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Edit Session</DialogTitle>
                    </DialogHeader>
                    {editTarget && (
                        <>
                            {editTarget.attendees.length > 0 && (
                                <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                    {editTarget.attendees.length} teacher{editTarget.attendees.length === 1 ? ' has' : 's have'} marked attendance.
                                    Their credit hours stay as set when you approved them.
                                </p>
                            )}
                            <RegisterForm
                                key={editTarget.id}
                                teachers={teachers}
                                session={editTarget}
                                onDone={() => setEditTarget(null)}
                            />
                        </>
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete confirmation */}
            <Dialog open={!!deleteTarget} onOpenChange={() => !processing && setDeleteTarget(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Delete Session</DialogTitle>
                    </DialogHeader>

                    {deleteTarget && (
                        <div className="space-y-3 py-2">
                            <p className="text-sm text-gray-600">Are you sure you want to delete this session? This cannot be undone.</p>
                            <div className="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                <p className="text-sm font-semibold text-gray-800">{deleteTarget.title}</p>
                                <p className="text-xs text-gray-500">{fmtDate(deleteTarget.session_date)}</p>
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <button
                            onClick={() => setDeleteTarget(null)}
                            disabled={processing}
                            className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={confirmDelete}
                            disabled={processing}
                            className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                        >
                            {processing ? 'Deleting...' : 'Delete Session'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Mark attendance confirmation */}
            <Dialog open={!!markTarget} onOpenChange={() => !processing && setMarkTarget(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Confirm Attendance</DialogTitle>
                    </DialogHeader>

                    {markTarget && (
                        <div className="space-y-3 py-2">
                            <p className="text-sm text-gray-600">
                                You are about to mark yourself as <strong>present</strong> for:
                            </p>
                            <div className="space-y-1 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                <p className="text-sm font-semibold text-gray-800">{markTarget.title}</p>
                                <p className="text-xs text-gray-500">
                                    {fmtTime(markTarget.start_time)} – {fmtTime(markTarget.end_time)}
                                </p>
                            </div>
                            <p className="text-xs text-gray-400">
                                Guru Besar will set your LADAP credit hours when approving. This action cannot be undone.
                            </p>
                        </div>
                    )}

                    <DialogFooter>
                        <button
                            onClick={() => setMarkTarget(null)}
                            disabled={processing}
                            className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={confirmMark}
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {processing ? 'Submitting...' : 'Confirm Attendance'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Attendees list — Guru Besar approval */}
            <Dialog open={!!attendeesTarget && !approveTarget} onOpenChange={() => setAttendeesSessionId(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Attendees</DialogTitle>
                    </DialogHeader>

                    {attendeesTarget && (
                        <div className="space-y-3 py-2">
                            <p className="text-sm font-medium text-gray-700">{attendeesTarget.title}</p>

                            {attendeesTarget.attendees.length === 0 ? (
                                <p className="py-4 text-center text-sm text-gray-400">No one has marked attendance yet.</p>
                            ) : (
                                <div className="max-h-80 space-y-2 overflow-y-auto">
                                    {attendeesTarget.attendees.map((a) => (
                                        <div
                                            key={a.attendance_id}
                                            className="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2"
                                        >
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold text-gray-800">
                                                    {a.name}
                                                    {a.is_assigned && (
                                                        <span className="ml-1.5 rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-700">Assigned</span>
                                                    )}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    {a.teacher_id}
                                                    {a.status === 'approved' && ` · +${a.hours_ladap.toFixed(2)} hrs`}
                                                </p>
                                                {a.status === 'approved' && a.remarks && (
                                                    <p className="truncate text-xs text-gray-400" title={a.remarks}>{a.remarks}</p>
                                                )}
                                            </div>
                                            {a.status === 'approved' ? (
                                                <span className="flex shrink-0 items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                                    Approved
                                                </span>
                                            ) : (
                                                <button
                                                    onClick={() => setApproveTarget(a)}
                                                    className="shrink-0 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                                >
                                                    Approve
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    <DialogFooter>
                        <button
                            onClick={() => setAttendeesSessionId(null)}
                            className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                        >
                            Close
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <ApproveDialog
                key={approveTarget?.attendance_id ?? 'none'}
                attendee={approveTarget}
                session={attendeesTarget}
                onClose={() => setApproveTarget(null)}
            />
        </AppLayout>
    );
}

/** Guru Besar sets credit hours (+ optional remarks) when approving a teacher's attendance. */
function ApproveDialog({ attendee, session, onClose }: { attendee: Attendee | null; session: SessionRow | null; onClose: () => void }) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        credit_hours: '',
        remarks:      '',
    });
    const credit = parseFloat(data.credit_hours);

    function close() {
        if (processing) return;
        reset();
        clearErrors();
        onClose();
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!attendee) return;
        post(route('penyelaras-ladap.attendance.approve', attendee.attendance_id), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    }

    return (
        <Dialog open={!!attendee} onOpenChange={close}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Approve Attendance</DialogTitle>
                </DialogHeader>

                {attendee && (
                    <form id="approve-attendance-form" onSubmit={submit} className="space-y-4 py-2">
                        <div className="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                            <p className="text-sm font-semibold text-gray-800">
                                {attendee.name}
                                {attendee.is_assigned && (
                                    <span className="ml-1.5 rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-700">Assigned</span>
                                )}
                            </p>
                            {session && (
                                <p className="text-xs text-gray-500">
                                    {session.title} · {fmtDate(session.session_date)} · {fmtTime(session.start_time)} – {fmtTime(session.end_time)}
                                    {' '}({session.session_hours.toFixed(2)} hrs)
                                </p>
                            )}
                        </div>

                        <Field label="Credit Hours" error={errors.credit_hours}>
                            <input
                                type="number"
                                step="0.25"
                                min="0"
                                value={data.credit_hours}
                                onChange={(e) => setData('credit_hours', e.target.value)}
                                className={inputCls(errors.credit_hours)}
                                placeholder="e.g. 1.5"
                                autoFocus
                            />
                            {session && !isNaN(credit) && (
                                <p className="mt-1 text-xs text-gray-500">
                                    LADAP hours: {session.session_hours.toFixed(2)} hrs × {credit} ={' '}
                                    <span className="font-semibold text-gray-700">{(session.session_hours * credit).toFixed(2)}</span>
                                </p>
                            )}
                        </Field>

                        <Field label="Remarks (optional)" error={errors.remarks}>
                            <textarea
                                rows={3}
                                value={data.remarks}
                                onChange={(e) => setData('remarks', e.target.value)}
                                className={inputCls(errors.remarks)}
                                placeholder="Any notes for the teacher"
                            />
                        </Field>
                    </form>
                )}

                <DialogFooter>
                    <button
                        type="button"
                        onClick={close}
                        disabled={processing}
                        className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        form="approve-attendance-form"
                        disabled={processing}
                        className="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        {processing ? 'Approving...' : 'Approve'}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
