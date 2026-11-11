import AppLayout from '@/layouts/app-layout';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useRole } from '@/lib/auth';
import { type PageProps } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarClock, CheckCircle2, ChevronDown, Clock, HeartHandshake, MapPin, Pencil, Plus, Trash2, Users } from 'lucide-react';
import { useState } from 'react';

interface TarbiahRow {
    tarbiah_id: number;
    title: string;
    description: string | null;
    session_date: string;
    end_date: string;
    start_time: string;
    end_time: string;
    location: string | null;
    credit_hours: number;
    ladap_hours: number;
    attendance_count: number;
    attendees: { teacher_id: string; name: string }[];
    has_attended: boolean;
    is_active: boolean;
    status: 'upcoming' | 'active' | 'completed';
}

interface Props extends PageProps {
    tarbiahs: TarbiahRow[];
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

function TarbiahLadapStats({ total, target }: { total: number; target: number }) {
    const pct = Math.min(100, Math.round((total / target) * 100));
    const remaining = Math.max(0, target - total);

    const barColor =
        pct >= 100 ? 'bg-green-500' :
        pct >= 50  ? 'bg-amber-400' :
                     'bg-rose-500';

    const valueColor =
        pct >= 100 ? 'text-green-600' :
        pct >= 50  ? 'text-amber-500' :
                     'text-rose-600';

    return (
        <div className="mb-6 rounded-xl border border-gray-200 bg-white px-5 py-4">
            <div className="flex items-center gap-4">
                <div className="flex items-center gap-2 shrink-0">
                    <Clock className="h-4 w-4 text-gray-400" />
                    <span className="text-sm font-semibold text-gray-700">Tarbiah LADAP Hours</span>
                </div>
                <div className="flex flex-1 items-center gap-3 min-w-0">
                    <div className="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div className={`h-full rounded-full transition-all ${barColor}`} style={{ width: `${pct}%` }} />
                    </div>
                    <span className={`text-xs font-bold shrink-0 ${valueColor}`}>
                        {total.toFixed(2)} / {target} hrs
                    </span>
                    {pct >= 100 && (
                        <span className="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                            Target Achieved ✓
                        </span>
                    )}
                </div>
            </div>
            {pct < 100 && (
                <p className="mt-2 text-xs text-gray-400">{remaining.toFixed(2)} hrs remaining to reach target</p>
            )}
        </div>
    );
}

/** Register a Tarbiah, or edit `tarbiah` when given. */
function RegisterForm({ tarbiah, onDone }: { tarbiah?: TarbiahRow; onDone: () => void }) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        title:        tarbiah?.title ?? '',
        description:  tarbiah?.description ?? '',
        session_date: tarbiah?.session_date ?? '',
        end_date:     tarbiah?.end_date ?? '',
        start_time:   tarbiah?.start_time.slice(0, 5) ?? '',
        end_time:     tarbiah?.end_time.slice(0, 5) ?? '',
        credit_hours: tarbiah ? String(tarbiah.credit_hours) : '',
        location:     tarbiah?.location ?? '',
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
        if (tarbiah) {
            put(route('tarbiah.update', tarbiah.tarbiah_id), options);
        } else {
            post(route('tarbiah.store'), options);
        }
    }

    return (
        <form onSubmit={submit} className="border-t border-gray-100 p-5 space-y-4">
            <Field label="Title" error={errors.title}>
                <input
                    type="text"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    className={inputCls(errors.title)}
                    placeholder="Tarbiah program name"
                />
            </Field>

            <Field label="Description" error={errors.description}>
                <textarea
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={3}
                    className={inputCls(errors.description)}
                    placeholder="Describe the Tarbiah session"
                />
            </Field>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field label="Start Date" error={errors.session_date}>
                    <input
                        type="date"
                        value={data.session_date}
                        onChange={(e) => setData('session_date', e.target.value)}
                        className={inputCls(errors.session_date)}
                    />
                </Field>
                <Field label="End Date" error={errors.end_date}>
                    <input
                        type="date"
                        value={data.end_date}
                        onChange={(e) => setData('end_date', e.target.value)}
                        className={inputCls(errors.end_date)}
                        placeholder="Same as start date if left blank"
                    />
                </Field>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                <Field label="Credit Hours" error={errors.credit_hours}>
                    <input
                        type="number"
                        step="0.5"
                        min="0.5"
                        max="99.99"
                        value={data.credit_hours}
                        onChange={(e) => setData('credit_hours', e.target.value)}
                        className={inputCls(errors.credit_hours)}
                        placeholder="e.g. 2.0"
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
                    {processing ? 'Saving...' : tarbiah ? 'Save Changes' : 'Register Tarbiah'}
                </button>
            </div>
        </form>
    );
}

export default function TarbiahIndex({ tarbiahs, total_ladap_hours, ladap_target_hours }: Props) {
    const { isGuruBesar, isTeacher } = useRole();
    const [showForm, setShowForm] = useState(false);
    const [selected, setSelected] = useState<TarbiahRow | null>(null);
    const [attendeesOf, setAttendeesOf] = useState<TarbiahRow | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<TarbiahRow | null>(null);
    const [editTarget, setEditTarget] = useState<TarbiahRow | null>(null);
    const [processing, setProcessing] = useState(false);

    function confirmDelete() {
        if (!deleteTarget) return;
        setProcessing(true);
        router.delete(route('tarbiah.destroy', deleteTarget.tarbiah_id), {
            preserveScroll: true,
            onSuccess: () => { setDeleteTarget(null); setProcessing(false); },
            onError:   () => { setProcessing(false); },
        });
    }

    function closeModal() {
        if (!processing) setSelected(null);
    }

    function confirmAttendance() {
        if (!selected) return;
        setProcessing(true);
        router.post(route('tarbiah.mark', selected.tarbiah_id), {}, {
            preserveScroll: true,
            onSuccess: () => { setSelected(null); setProcessing(false); },
            onError:   () => { setProcessing(false); },
        });
    }

    return (
        <AppLayout>
            <Head title="Tarbiah" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">

                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100">
                            <HeartHandshake className="h-5 w-5 text-gray-500" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Tarbiah</h1>
                            <p className="text-sm text-gray-500">
                                {isTeacher
                                    ? 'Auto-enrolled for your school — hours are earned per session duration'
                                    : 'Registered Tarbiah sessions for your school'}
                            </p>
                        </div>
                    </div>

                    {isGuruBesar && (
                        <button
                            onClick={() => setShowForm((s) => !s)}
                            className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            <Plus className="h-4 w-4" />
                            Register Tarbiah
                            <ChevronDown className={`h-4 w-4 transition-transform ${showForm ? 'rotate-180' : ''}`} />
                        </button>
                    )}
                </div>

                {isTeacher && (
                    <TarbiahLadapStats total={total_ladap_hours} target={ladap_target_hours} />
                )}

                {isGuruBesar && showForm && (
                    <div className="mb-6 rounded-xl border border-gray-200 bg-white overflow-hidden">
                        <RegisterForm onDone={() => setShowForm(false)} />
                    </div>
                )}

                {tarbiahs.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center">
                        <HeartHandshake className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">No Tarbiah sessions registered yet.</p>
                    </div>
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {tarbiahs.map((t) => (
                            <div
                                key={t.tarbiah_id}
                                onClick={isGuruBesar ? () => setAttendeesOf(t) : undefined}
                                className={`flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md ${
                                    isGuruBesar ? 'cursor-pointer' : ''
                                }`}
                            >
                                <div className="mb-3 flex items-start justify-between gap-2">
                                    <h3 className="font-semibold text-gray-900 leading-snug">{t.title}</h3>
                                    <div className="flex shrink-0 items-center gap-2">
                                        {t.status === 'active' && (
                                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                                Active
                                            </span>
                                        )}
                                        {t.status === 'completed' && (
                                            <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">
                                                Completed
                                            </span>
                                        )}
                                        {isGuruBesar && (
                                            <button
                                                onClick={(e) => { e.stopPropagation(); setEditTarget(t); }}
                                                title="Edit Tarbiah"
                                                className="rounded-md p-1 text-gray-300 hover:bg-blue-50 hover:text-blue-600"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>
                                        )}
                                        {isGuruBesar && (
                                            <button
                                                onClick={(e) => { e.stopPropagation(); setDeleteTarget(t); }}
                                                title="Delete Tarbiah"
                                                className="rounded-md p-1 text-gray-300 hover:bg-red-50 hover:text-red-600"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                </div>

                                {t.description && (
                                    <p className="mb-4 line-clamp-2 text-sm text-gray-500">{t.description}</p>
                                )}

                                <div className="mt-auto space-y-1.5 text-sm text-gray-600">
                                    <div className="flex items-center gap-2">
                                        <CalendarClock className="h-3.5 w-3.5 text-gray-400" />
                                        {t.end_date !== t.session_date
                                            ? `${fmtDate(t.session_date)} – ${fmtDate(t.end_date)}`
                                            : fmtDate(t.session_date)}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Clock className="h-3.5 w-3.5 text-gray-400" />
                                        {fmtTime(t.start_time)} – {fmtTime(t.end_time)}
                                        <span className="text-xs text-gray-400">({t.ladap_hours.toFixed(2)} hrs)</span>
                                    </div>
                                    {t.location && (
                                        <div className="flex items-center gap-2">
                                            <MapPin className="h-3.5 w-3.5 text-gray-400" />
                                            {t.location}
                                        </div>
                                    )}
                                    {isGuruBesar && (
                                        <div className="flex items-center gap-2 text-blue-600 hover:underline">
                                            <Users className="h-3.5 w-3.5" />
                                            {t.attendance_count} teacher{t.attendance_count === 1 ? '' : 's'} attended
                                        </div>
                                    )}
                                </div>

                                {isTeacher && (
                                    <div className="mt-4">
                                        {t.has_attended ? (
                                            <span className="flex items-center justify-center gap-2 rounded-lg bg-green-100 py-2 text-sm font-semibold text-green-700">
                                                <CheckCircle2 className="h-4 w-4" />
                                                Attended — +{t.ladap_hours.toFixed(2)} hrs
                                            </span>
                                        ) : t.is_active ? (
                                            <button
                                                onClick={() => setSelected(t)}
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
                                <p className="text-sm font-semibold text-gray-800">{selected.title}</p>
                                <p className="text-xs text-gray-500">{fmtDate(selected.session_date)}</p>
                                <p className="text-xs text-gray-500">
                                    {fmtTime(selected.start_time)} – {fmtTime(selected.end_time)}
                                </p>
                                <p className="text-xs font-semibold text-blue-600">
                                    +{selected.ladap_hours.toFixed(2)} LADAP hrs
                                </p>
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

            {/* Attendees list modal */}
            <Dialog open={!!attendeesOf} onOpenChange={() => setAttendeesOf(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{attendeesOf?.title}</DialogTitle>
                    </DialogHeader>

                    {attendeesOf && (
                        attendeesOf.attendees.length === 0 ? (
                            <p className="py-4 text-center text-sm text-gray-500">
                                No teachers have attended this Tarbiah yet.
                            </p>
                        ) : (
                            <ul className="max-h-80 space-y-1 overflow-y-auto py-1">
                                {attendeesOf.attendees.map((a, i) => (
                                    <li
                                        key={a.teacher_id}
                                        className="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-50"
                                    >
                                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                            {i + 1}
                                        </span>
                                        <span className="text-sm text-gray-800">{a.name}</span>
                                    </li>
                                ))}
                            </ul>
                        )
                    )}
                </DialogContent>
            </Dialog>

            {/* Edit modal */}
            <Dialog open={!!editTarget} onOpenChange={(open) => !open && setEditTarget(null)}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Edit Tarbiah</DialogTitle>
                    </DialogHeader>
                    {editTarget && (
                        <>
                            {editTarget.attendance_count > 0 && (
                                <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                    {editTarget.attendance_count} teacher{editTarget.attendance_count === 1 ? ' has' : 's have'} attended.
                                    Changing credit hours updates the hours they earned.
                                </p>
                            )}
                            <RegisterForm key={editTarget.tarbiah_id} tarbiah={editTarget} onDone={() => setEditTarget(null)} />
                        </>
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete confirmation modal */}
            <Dialog open={!!deleteTarget} onOpenChange={() => !processing && setDeleteTarget(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Delete Tarbiah</DialogTitle>
                    </DialogHeader>

                    {deleteTarget && (
                        <div className="space-y-3 py-2">
                            <p className="text-sm text-gray-600">
                                Are you sure you want to delete this Tarbiah? This cannot be undone
                                {deleteTarget.attendance_count > 0 && (
                                    <> — {deleteTarget.attendance_count} recorded attendance{deleteTarget.attendance_count === 1 ? '' : 's'} will be removed too</>
                                )}.
                            </p>
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
                            {processing ? 'Deleting...' : 'Delete Tarbiah'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
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
