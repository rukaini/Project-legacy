import AppLayout from '@/layouts/app-layout';
import { useRole } from '@/lib/auth';
import {
    type HistoryRow,
    type PageProps,
    type Training,
} from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Award, CheckCircle2, ClipboardList, Clock, ExternalLink, FileText, ScrollText, Tag, UserCircle2, XCircle } from 'lucide-react';

interface SessionAttendanceRow {
    session_id: string;
    session_date: string;
    start_time: string | null;
    end_time: string | null;
    attended: boolean;
    timestamp_status: number | null;
    hours_ladap: number;
    trainers: { trainer_id: string; trainer_name: string }[];
}

interface AssessmentItem {
    assessment_id: number;
    type: string;
    title: string;
    deadline: string | null;
    total_marks: number | null;
    status: string;
    my_submission: {
        submission_id: number;
        grade: number | null;
        status: string;
        submitted_at: string;
    } | null;
}

interface Props extends PageProps {
    training: Training;
    row: HistoryRow;
    session_attendance: SessionAttendanceRow[];
    assessments?: AssessmentItem[];
}

function formatTime(time: string | null | undefined): string {
    if (!time) return '—';
    const [h, m] = time.substring(0, 5).split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    return `${h % 12 || 12}:${m.toString().padStart(2, '0')} ${period}`;
}

export default function HistoryShow({ training, row, session_attendance, assessments }: Props) {
    const { isOutsider, isGuruBaru, isGuruBesar } = useRole();
    const hideladap  = isOutsider || isGuruBaru;
    const isSeries   = row.course_category === 'series';

    const backHref  = isGuruBesar ? route('reports.development') : route('history.mine');
    const backLabel = isGuruBesar ? 'Back to Development Overview' : 'Back to My History';

    return (
        <AppLayout>
            <Head title={`History — ${training.courseName}`} />
            <div className="mx-auto w-full max-w-4xl flex flex-1 flex-col px-6 py-8 sm:px-8">
                <Link
                    href={backHref}
                    className="mb-4 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-700"
                >
                    <ArrowLeft className="h-4 w-4" /> {backLabel}
                </Link>

                <div className="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h1 className="text-2xl font-bold text-gray-900">{training.courseName}</h1>
                    <div className="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        {training.course_category && (
                            <span className="flex items-center gap-1">
                                <Tag className="h-3.5 w-3.5" />
                                {training.course_category.split('_').map((w: string) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}
                            </span>
                        )}
                        <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                            {training.organiserName}
                        </span>
                    </div>
                </div>

                {/* Summary tiles */}
                {(() => {
                    const tileCount = 1
                        + (!hideladap ? 2 : 0)
                        + (isGuruBaru ? 2 : 0)
                        + (isGuruBaru ? 1 : 0);
                    const cols = tileCount <= 3 ? `sm:grid-cols-${tileCount}` : 'sm:grid-cols-4';
                    return (
                        <div className={`mb-6 grid gap-3 ${cols}`}>
                            <SummaryTile
                                icon={ScrollText}
                                label="Attendance"
                                value={row.total_sessions > 0 ? `${row.present_count}/${row.total_sessions}` : '—'}
                                sub={row.attendance_rate !== null ? `${row.attendance_rate}%` : ''}
                            />
                            {!hideladap && (
                                <SummaryTile
                                    icon={Award}
                                    label="LADAP Hours"
                                    value={row.ladap_hours.toFixed(2)}
                                    sub={row.status === 'completed' ? 'Credited' : 'Pending'}
                                    accent
                                />
                            )}
                            {isGuruBaru && (
                                <SummaryTile
                                    icon={ClipboardList}
                                    label="Task Submitted"
                                    value={isSeries ? `${row.submitted_count ?? 0}/${row.total_tasks ?? 0}` : '—'}
                                    sub={isSeries ? 'tasks' : ''}
                                />
                            )}
                            {isGuruBaru && (
                                <SummaryTile
                                    icon={Award}
                                    label="Quiz Avg"
                                    value={isSeries
                                        ? (row.average_quiz_score != null ? `${row.average_quiz_score}%` : '—')
                                        : '—'}
                                    sub={isSeries && row.average_quiz_score != null ? 'average score' : ''}
                                    accent
                                />
                            )}
                            <SummaryTile
                                icon={Clock}
                                label="Total Hours"
                                value={row.total_session_hours.toFixed(1)}
                                sub="attended"
                                accent={isGuruBaru}
                                indigo={isGuruBaru}
                            />
                        </div>
                    );
                })()}

                {/* Attendance sessions */}
                <Section title="Attendance Sessions">
                    {session_attendance.length === 0 ? (
                        <EmptyHint text="No sessions for this training yet." />
                    ) : (() => {
                        const now = new Date();
                        const todayStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;

                        // Dates where the user already attended at least one session
                        const attendedDates = new Set(
                            session_attendance
                                .filter(s => s.attended)
                                .map(s => s.session_date.substring(0, 10))
                        );

                        return (
                            <ul className="divide-y divide-gray-100">
                                {session_attendance.map((s) => {
                                    const dateStr = s.session_date.substring(0, 10);
                                    const isPast    = dateStr < todayStr;
                                    // Same-day slot already filled by another attended session
                                    const slotTaken = !s.attended && attendedDates.has(dateStr);

                                    return (
                                        <li key={s.session_id} className="flex items-center justify-between py-3 text-sm">
                                            <div>
                                                <div className="font-medium text-gray-900">
                                                    {new Date(s.session_date).toLocaleDateString('en-MY', {
                                                        day: '2-digit', month: 'short', year: 'numeric',
                                                    })}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {formatTime(s.start_time)} – {formatTime(s.end_time)}
                                                </div>
                                                {s.trainers.length > 0 && (
                                                    <div className="mt-0.5 flex items-center gap-1 text-xs text-gray-400">
                                                        <UserCircle2 className="h-3 w-3" />
                                                        {s.trainers.map(t => t.trainer_name).join(', ')}
                                                    </div>
                                                )}
                                                {s.attended && (
                                                    <div className="text-xs text-gray-400">
                                                        {s.timestamp_status != null && `${Number(s.timestamp_status).toFixed(1)} hrs`}
                                                        {!hideladap && s.hours_ladap > 0 && ` · ${Number(s.hours_ladap).toFixed(2)} LADAP hrs`}
                                                    </div>
                                                )}
                                            </div>
                                            {s.attended ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                                    <CheckCircle2 className="h-3 w-3" /> Present
                                                </span>
                                            ) : isPast && !s.attended ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                                                    <XCircle className="h-3 w-3" /> Absent
                                                </span>
                                            ) : slotTaken ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                                    <XCircle className="h-3 w-3" /> Not Attended
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                                    No record yet
                                                </span>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        );
                    })()}
                </Section>

                {/* Quiz & Task sections — guru_baru only */}
                {isGuruBaru && assessments && (() => {
                    const quizzes = assessments.filter(a => a.type === 'quiz');
                    const tasks   = assessments.filter(a => a.type !== 'quiz');
                    return (
                        <>
                            {quizzes.length > 0 && (
                                <Section title="Quizzes">
                                    <ul className="divide-y divide-gray-100">
                                        {quizzes.map(a => (
                                            <li key={a.assessment_id} className="flex items-center justify-between py-3 text-sm">
                                                <div>
                                                    <p className="font-medium text-gray-900">{a.title}</p>
                                                    {a.deadline && (
                                                        <p className="text-xs text-gray-400">
                                                            Deadline: {new Date(a.deadline).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                        </p>
                                                    )}
                                                    {a.my_submission && (
                                                        <p className="text-xs text-gray-400">
                                                            Submitted: {new Date(a.my_submission.submitted_at).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="flex flex-col items-end gap-1">
                                                    {a.my_submission ? (
                                                        <>
                                                            <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                                                <CheckCircle2 className="h-3 w-3" /> Submitted
                                                            </span>
                                                            {a.my_submission.grade != null && (
                                                                <span className="text-xs font-semibold text-indigo-700">
                                                                    {a.my_submission.grade}/{a.total_marks ?? '—'}
                                                                </span>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                                            Not attempted
                                                        </span>
                                                    )}
                                                    <Link
                                                        href={route('assessments.show', { training: training.courseID ?? training.id, assessment: a.assessment_id })}
                                                        className="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"
                                                    >
                                                        <ExternalLink className="h-3 w-3" /> Open
                                                    </Link>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </Section>
                            )}

                            {tasks.length > 0 && (
                                <Section title="Tasks">
                                    <ul className="divide-y divide-gray-100">
                                        {tasks.map(a => (
                                            <li key={a.assessment_id} className="flex items-center justify-between py-3 text-sm">
                                                <div>
                                                    <p className="font-medium text-gray-900">{a.title}</p>
                                                    {a.deadline && (
                                                        <p className="text-xs text-gray-400">
                                                            Deadline: {new Date(a.deadline).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                        </p>
                                                    )}
                                                    {a.my_submission && (
                                                        <p className="text-xs text-gray-400">
                                                            Submitted: {new Date(a.my_submission.submitted_at).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="flex flex-col items-end gap-1">
                                                    {a.my_submission ? (
                                                        <>
                                                            <span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                                                <FileText className="h-3 w-3" /> Submitted
                                                            </span>
                                                            {a.my_submission.grade != null && (
                                                                <span className="text-xs font-semibold text-indigo-700">
                                                                    Grade: {a.my_submission.grade}/{a.total_marks ?? '—'}
                                                                </span>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                                            Not submitted
                                                        </span>
                                                    )}
                                                    <Link
                                                        href={route('assessments.show', { training: training.courseID ?? training.id, assessment: a.assessment_id })}
                                                        className="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"
                                                    >
                                                        <ExternalLink className="h-3 w-3" /> Open
                                                    </Link>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </Section>
                            )}
                        </>
                    );
                })()}
            </div>
        </AppLayout>
    );
}

function SummaryTile({
    icon: Icon, label, value, sub, accent, indigo,
}: {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    value: string;
    sub?: string;
    accent?: boolean;
    indigo?: boolean;
}) {
    const valueClass = indigo ? 'text-indigo-700' : accent ? 'text-blue-700' : 'text-gray-900';
    const cardClass  = indigo ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-white';
    return (
        <div className={`rounded-xl border p-4 ${cardClass}`}>
            <div className="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Icon className="h-3.5 w-3.5" />
                {label}
            </div>
            <div className={`mt-1 text-xl font-bold ${valueClass}`}>
                {value}
            </div>
            {sub && <div className="text-xs text-gray-400">{sub}</div>}
        </div>
    );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div className="mb-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">{title}</h2>
            {children}
        </div>
    );
}

function EmptyHint({ text }: { text: string }) {
    return <p className="py-3 text-sm text-gray-400">{text}</p>;
}
