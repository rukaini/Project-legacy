import AppLayout from '@/layouts/app-layout';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type PageProps } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Award, CalendarClock, ChevronDown, Clock, FileText, MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';

interface CertificateRow {
    id: number;
    title: string;
    description: string | null;
    session_date: string;
    start_time: string;
    end_time: string;
    location: string | null;
    file_name: string | null;
    has_file: boolean;
    approval: 'approved' | 'rejected' | null;
    credit_hours: number | null;
    session_hours: number;
    ladap_hours: number | null;
    remarks: string | null;
}

function StatusBadge({ approval }: { approval: CertificateRow['approval'] }) {
    if (approval === 'approved') {
        return <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Approved</span>;
    }
    if (approval === 'rejected') {
        return <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Rejected</span>;
    }
    return <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Pending</span>;
}

interface Props extends PageProps {
    certificates: CertificateRow[];
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

/** Upload a certificate, or edit `editing` when given (new PDF optional). */
function UploadForm({ editing, onDone }: { editing?: CertificateRow; onDone: () => void }) {
    const fileInput = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm<{
        title: string;
        description: string;
        session_date: string;
        start_time: string;
        end_time: string;
        location: string;
        certificate: File | null;
        _method?: 'put';
    }>({
        title:        editing?.title ?? '',
        description:  editing?.description ?? '',
        session_date: editing?.session_date ?? '',
        start_time:   editing?.start_time.slice(0, 5) ?? '',
        end_time:     editing?.end_time.slice(0, 5) ?? '',
        location:     editing?.location ?? '',
        certificate:  null,
        // PHP can't read multipart bodies on a real PUT, so spoof the method.
        ...(editing ? { _method: 'put' as const } : {}),
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(editing ? route('certificates.update', editing.id) : route('certificates.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                if (fileInput.current) fileInput.current.value = '';
                onDone();
            },
        });
    }

    return (
        <form onSubmit={submit} className="space-y-4 border-t border-gray-100 p-5">
            <Field label="Title" error={errors.title}>
                <input
                    type="text"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    className={inputCls(errors.title)}
                    placeholder="Course or programme name"
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

            <Field
                label={editing ? 'Replace PDF (optional, max 10 MB)' : 'Certificate (PDF, max 10 MB)'}
                error={errors.certificate}
            >
                {editing?.file_name && (
                    <p className="mb-1.5 flex items-center gap-1.5 text-xs text-gray-500">
                        <FileText className="h-3.5 w-3.5 text-red-500" />
                        Current: {editing.file_name}
                    </p>
                )}
                <input
                    ref={fileInput}
                    type="file"
                    accept="application/pdf,.pdf"
                    onChange={(e) => setData('certificate', e.target.files?.[0] ?? null)}
                    className={`${inputCls(errors.certificate)} file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100`}
                />
            </Field>

            <div className="flex justify-end">
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                >
                    {processing ? 'Saving...' : editing ? 'Save Changes' : 'Save Certificate'}
                </button>
            </div>
        </form>
    );
}

export default function CertificateIndex({ certificates }: Props) {
    const [showForm, setShowForm] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<CertificateRow | null>(null);
    const [editTarget, setEditTarget] = useState<CertificateRow | null>(null);
    const [processing, setProcessing] = useState(false);

    function confirmDelete() {
        if (!deleteTarget) return;
        setProcessing(true);
        router.delete(route('certificates.destroy', deleteTarget.id), {
            preserveScroll: true,
            onSuccess: () => { setDeleteTarget(null); setProcessing(false); },
            onError:   () => { setProcessing(false); },
        });
    }

    return (
        <AppLayout>
            <Head title="Certificate" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">

                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100">
                            <Award className="h-5 w-5 text-gray-500" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Certificate</h1>
                            <p className="text-sm text-gray-500">Upload and keep your course certificates in one place</p>
                        </div>
                    </div>

                    <button
                        onClick={() => setShowForm((s) => !s)}
                        className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        <Plus className="h-4 w-4" />
                        New Certificate
                        <ChevronDown className={`h-4 w-4 transition-transform ${showForm ? 'rotate-180' : ''}`} />
                    </button>
                </div>

                {showForm && (
                    <div className="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <UploadForm onDone={() => setShowForm(false)} />
                    </div>
                )}

                {certificates.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 p-12">
                        <Award className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">No certificates uploaded yet.</p>
                    </div>
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {certificates.map((c) => (
                            <div
                                key={c.id}
                                className="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div className="mb-3 flex items-start justify-between gap-2">
                                    <h3 className="font-semibold leading-snug text-gray-900">{c.title}</h3>
                                    <div className="flex shrink-0 items-center gap-2">
                                        <StatusBadge approval={c.approval} />
                                        {!c.approval && (
                                            <button
                                                onClick={() => setEditTarget(c)}
                                                title="Edit certificate"
                                                className="rounded-md p-1 text-gray-300 hover:bg-blue-50 hover:text-blue-600"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>
                                        )}
                                        {!c.approval && (
                                            <button
                                                onClick={() => setDeleteTarget(c)}
                                                title="Delete certificate"
                                                className="rounded-md p-1 text-gray-300 hover:bg-red-50 hover:text-red-600"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                </div>

                                {c.description && (
                                    <p className="mb-4 line-clamp-2 text-sm text-gray-500">{c.description}</p>
                                )}

                                <div className="mt-auto space-y-1.5 text-sm text-gray-600">
                                    <div className="flex items-center gap-2">
                                        <CalendarClock className="h-3.5 w-3.5 text-gray-400" />
                                        {fmtDate(c.session_date)}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Clock className="h-3.5 w-3.5 text-gray-400" />
                                        {fmtTime(c.start_time)} – {fmtTime(c.end_time)}
                                    </div>
                                    {c.location && (
                                        <div className="flex items-center gap-2">
                                            <MapPin className="h-3.5 w-3.5 text-gray-400" />
                                            {c.location}
                                        </div>
                                    )}
                                </div>

                                {c.approval && (
                                    <div className={`mt-4 rounded-lg px-3 py-2 text-sm ${c.approval === 'approved' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
                                        <p className="font-semibold">
                                            {c.approval === 'approved'
                                                ? `LADAP hours: ${(c.ladap_hours ?? 0).toFixed(2)}`
                                                : 'Not counted toward LADAP'}
                                        </p>
                                        <p className="text-xs">
                                            {c.session_hours.toFixed(2)} hrs × {c.credit_hours ?? 0} credit hours
                                        </p>
                                        {c.remarks && <p className="text-xs">Remarks: {c.remarks}</p>}
                                    </div>
                                )}

                                {c.has_file ? (
                                    <a
                                        href={route('certificates.show', c.id)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="mt-4 flex items-center justify-center gap-2 rounded-lg border border-gray-200 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                                    >
                                        <FileText className="h-4 w-4 text-red-500" />
                                        <span className="truncate">{c.file_name ?? 'View Certificate'}</span>
                                    </a>
                                ) : (
                                    <span className="mt-4 block w-full rounded-lg bg-gray-100 py-2 text-center text-sm font-semibold text-gray-400">
                                        No file
                                    </span>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Edit certificate */}
            <Dialog open={!!editTarget} onOpenChange={(open) => !open && setEditTarget(null)}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Edit Certificate</DialogTitle>
                    </DialogHeader>
                    {editTarget && (
                        <UploadForm key={editTarget.id} editing={editTarget} onDone={() => setEditTarget(null)} />
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete confirmation */}
            <Dialog open={!!deleteTarget} onOpenChange={() => !processing && setDeleteTarget(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Delete Certificate</DialogTitle>
                    </DialogHeader>

                    {deleteTarget && (
                        <div className="space-y-3 py-2">
                            <p className="text-sm text-gray-600">Are you sure you want to delete this certificate? The uploaded PDF will be removed too. This cannot be undone.</p>
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
                            {processing ? 'Deleting...' : 'Delete Certificate'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
