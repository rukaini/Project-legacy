import AppLayout from '@/layouts/app-layout';
import { type PageProps } from '@/types';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Head, router, useForm } from '@inertiajs/react';
import { Award, Check, ChevronRight, FileText, Mail, Search, UserRound, X } from 'lucide-react';
import { useState } from 'react';

interface TeacherCard {
    id: string;
    name: string;
    email: string | null;
    certificate_count: number;
}

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

interface Props extends PageProps {
    school_name: string | null;
    teachers: TeacherCard[];
    selected_teacher: TeacherCard | null;
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

type Decision = 'approved' | 'rejected';

function ApprovalBadge({ cert }: { cert: CertificateRow }) {
    if (cert.approval === 'approved') {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700" title={cert.remarks ?? undefined}>
                <Check className="h-3.5 w-3.5" />
                Approved · {(cert.ladap_hours ?? 0).toFixed(2)} LADAP hrs
            </span>
        );
    }
    return (
        <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700" title={cert.remarks ?? undefined}>
            <X className="h-3.5 w-3.5" />
            Rejected
        </span>
    );
}

function ReviewDialog({ target, decision, onClose }: { target: CertificateRow | null; decision: Decision; onClose: () => void }) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        approval:     decision as string,
        credit_hours: '',
        remarks:      '',
    });
    const isApprove = decision === 'approved';
    const credit = parseFloat(data.credit_hours);

    function close() {
        if (processing) return;
        reset();
        clearErrors();
        onClose();
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!target) return;
        post(route('certificates.review', target.id), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    }

    const fieldCls = (error?: string) =>
        `w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-1 ${
            error ? 'border-red-400 focus:border-red-500 focus:ring-red-300' : 'border-gray-200 focus:border-blue-400 focus:ring-blue-300'
        }`;

    return (
        <Dialog open={!!target} onOpenChange={close}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{isApprove ? 'Approve Certificate' : 'Reject Certificate'}</DialogTitle>
                </DialogHeader>

                {target && (
                    <form id="review-form" onSubmit={submit} className="space-y-4 py-2">
                        <div className="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                            <p className="text-sm font-semibold text-gray-800">{target.title}</p>
                            <p className="text-xs text-gray-500">
                                {fmtDate(target.session_date)} · {fmtTime(target.start_time)} – {fmtTime(target.end_time)}
                                {' '}({target.session_hours.toFixed(2)} hrs)
                            </p>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700">Credit Hours</label>
                            <input
                                type="number"
                                step="0.25"
                                min="0"
                                value={data.credit_hours}
                                onChange={(e) => setData('credit_hours', e.target.value)}
                                className={fieldCls(errors.credit_hours)}
                                placeholder="e.g. 1.5"
                                autoFocus
                            />
                            {errors.credit_hours && <p className="mt-1 text-xs text-red-600">{errors.credit_hours}</p>}
                            {!isNaN(credit) && (
                                <p className="mt-1 text-xs text-gray-500">
                                    LADAP hours: {target.session_hours.toFixed(2)} hrs × {credit} ={' '}
                                    <span className="font-semibold text-gray-700">{(target.session_hours * credit).toFixed(2)}</span>
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700">
                                Remarks <span className="font-normal text-gray-400">(optional)</span>
                            </label>
                            <textarea
                                rows={3}
                                value={data.remarks}
                                onChange={(e) => setData('remarks', e.target.value)}
                                className={fieldCls(errors.remarks)}
                                placeholder="Any notes for the teacher"
                            />
                            {errors.remarks && <p className="mt-1 text-xs text-red-600">{errors.remarks}</p>}
                        </div>
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
                        form="review-form"
                        disabled={processing}
                        className={`rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 ${
                            isApprove ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'
                        }`}
                    >
                        {processing ? 'Saving...' : isApprove ? 'Approve' : 'Reject'}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

const searchInputCls =
    'w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400';

export default function SchoolCertificates({ school_name, teachers, selected_teacher, certificates }: Props) {
    const [search, setSearch] = useState('');
    const [reviewTarget, setReviewTarget] = useState<CertificateRow | null>(null);
    const [decision, setDecision] = useState<Decision>('approved');
    const q = search.toLowerCase();

    function openReview(cert: CertificateRow, d: Decision) {
        setDecision(d);
        setReviewTarget(cert);
    }

    function selectTeacher(teacherId: string) {
        router.get(route('certificates.school'), { teacher_id: teacherId });
    }

    function backToTeachers() {
        router.get(route('certificates.school'));
    }

    /* ── Teacher cards view ────────────────────────────────────────────── */
    if (!selected_teacher) {
        const filtered = teachers.filter(
            (t) => t.name.toLowerCase().includes(q) || (t.email ?? '').toLowerCase().includes(q),
        );

        return (
            <AppLayout>
                <Head title="Certificate" />
                <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                    <div className="mb-6 flex items-center gap-3">
                        <Award className="h-6 w-6 text-blue-600" />
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Certificate</h1>
                            {school_name && <p className="text-sm text-gray-500">{school_name}</p>}
                        </div>
                        <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                            {teachers.length} teachers
                        </span>
                    </div>

                    <div className="relative mb-6 max-w-sm">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            value={search}
                            placeholder="Search teacher by name or email…"
                            onChange={(e) => setSearch(e.target.value)}
                            className={searchInputCls}
                        />
                    </div>

                    {filtered.length === 0 ? (
                        <div className="flex h-48 items-center justify-center rounded-xl border border-dashed border-gray-300">
                            <p className="text-sm text-gray-400">No teachers found.</p>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {filtered.map((t) => (
                                <button
                                    key={t.id}
                                    onClick={() => selectTeacher(t.id)}
                                    className="group flex flex-col rounded-xl border border-gray-200 bg-white p-5 text-left shadow-sm transition-all hover:border-blue-300 hover:shadow-md"
                                >
                                    <div className="mb-4 flex items-start justify-between">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                            <UserRound className="h-5 w-5 text-blue-600" />
                                        </div>
                                        <ChevronRight className="h-4 w-4 text-gray-300 transition-colors group-hover:text-blue-500" />
                                    </div>

                                    <p className="mb-1 font-semibold leading-snug text-gray-900">{t.name}</p>

                                    <div className="mt-auto space-y-1.5 border-t border-gray-100 pt-3">
                                        <div className="flex items-center gap-1.5 text-xs text-gray-500">
                                            <Mail className="h-3.5 w-3.5 text-gray-400" />
                                            <span className="truncate">{t.email ?? '—'}</span>
                                        </div>
                                        <div className="flex items-center gap-1.5 text-xs text-gray-500">
                                            <Award className="h-3.5 w-3.5 text-gray-400" />
                                            {t.certificate_count} certificate{t.certificate_count !== 1 ? 's' : ''}
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

    /* ── Certificate list view ─────────────────────────────────────────── */
    const filteredCerts = certificates.filter(
        (c) => c.title.toLowerCase().includes(q) || (c.location ?? '').toLowerCase().includes(q),
    );

    return (
        <AppLayout>
            <Head title={`Certificate — ${selected_teacher.name}`} />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">

                <button onClick={backToTeachers} className="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                    ← Back to Teachers
                </button>

                {/* Teacher header */}
                <div className="mb-5 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                <UserRound className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <h2 className="text-base font-bold text-gray-900">{selected_teacher.name}</h2>
                                <div className="flex items-center gap-1.5 text-sm text-gray-500">
                                    <Mail className="h-3.5 w-3.5 text-gray-400" />
                                    <span>{selected_teacher.email ?? '—'}</span>
                                </div>
                            </div>
                        </div>
                        <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            {selected_teacher.certificate_count} certificate{selected_teacher.certificate_count !== 1 ? 's' : ''}
                        </span>
                    </div>
                </div>

                <div className="relative mb-4 max-w-sm">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        type="text"
                        value={search}
                        placeholder="Search by title or location…"
                        onChange={(e) => setSearch(e.target.value)}
                        className={searchInputCls}
                    />
                </div>

                {/* Certificate table */}
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    {filteredCerts.length === 0 ? (
                        <p className="p-10 text-center text-sm text-gray-400">
                            {certificates.length === 0 ? 'This teacher has not uploaded any certificates yet.' : 'No certificates match your search.'}
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    <tr>
                                        <th className="px-5 py-3 text-left">Title</th>
                                        <th className="px-5 py-3 text-left">Date</th>
                                        <th className="px-5 py-3 text-left">Time</th>
                                        <th className="px-5 py-3 text-left">Location</th>
                                        <th className="px-5 py-3 text-left">Certificate</th>
                                        <th className="px-5 py-3 text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {filteredCerts.map((c) => (
                                        <tr key={c.id} className="hover:bg-gray-50">
                                            <td className="px-5 py-3">
                                                <p className="font-medium text-gray-800">{c.title}</p>
                                                {c.description && <p className="line-clamp-1 text-xs text-gray-400">{c.description}</p>}
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-3 text-gray-500">{fmtDate(c.session_date)}</td>
                                            <td className="whitespace-nowrap px-5 py-3 text-gray-500">
                                                {fmtTime(c.start_time)} – {fmtTime(c.end_time)}
                                            </td>
                                            <td className="px-5 py-3 text-gray-500">{c.location ?? '—'}</td>
                                            <td className="px-5 py-3">
                                                {c.has_file ? (
                                                    <a
                                                        href={route('certificates.show', c.id)}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50"
                                                    >
                                                        <FileText className="h-3.5 w-3.5 text-red-500" />
                                                        View PDF
                                                    </a>
                                                ) : (
                                                    <span className="text-gray-400">—</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3">
                                                {c.approval ? (
                                                    <ApprovalBadge cert={c} />
                                                ) : (
                                                    <div className="flex items-center gap-2">
                                                        <button
                                                            onClick={() => openReview(c, 'approved')}
                                                            title="Approve"
                                                            className="flex h-8 w-8 items-center justify-center rounded-lg border border-green-200 bg-green-50 text-green-600 hover:bg-green-100"
                                                        >
                                                            <Check className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => openReview(c, 'rejected')}
                                                            title="Reject"
                                                            className="flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100"
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            <ReviewDialog
                key={`${reviewTarget?.id ?? 'none'}-${decision}`}
                target={reviewTarget}
                decision={decision}
                onClose={() => setReviewTarget(null)}
            />
        </AppLayout>
    );
}
