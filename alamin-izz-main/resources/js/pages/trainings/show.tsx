import AppLayout from '@/layouts/app-layout';
import { useRole } from '@/lib/auth';
import { formatMode } from '@/lib/utils';
import { type Application, type PageProps, type Training } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    Users, Monitor, Tag,
    CheckCircle, XCircle, AlertTriangle, UserPlus,
    MapPin, Clock, UserCircle2, MessageCircle,
    QrCode, BookOpen, ClipboardList,
    Info, X, ChevronRight, ChevronDown,
    BookMarked, Layers, CalendarCheck, FileText, User,
    Download, FileSpreadsheet, Presentation,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

function useNow(intervalMs = 1000): Date {
    const [now, setNow] = useState(() => new Date());
    useEffect(() => {
        const t = setInterval(() => setNow(new Date()), intervalMs);
        return () => clearInterval(t);
    }, [intervalMs]);
    return now;
}

interface LearningMaterialItem {
    material_id: number;
    original_file_name: string | null;
    file_path: string | null;
    material_type: string | null;
    trainer: string | null;
    session_date: string | null;
    session_start_time: string | null;
    session_end_time: string | null;
    uploaded_at?: string | null;
}

interface ContentBlock {
    block_id: number;
    block_type: string;
    block_title: string | null;
    block_content: string | null;
    external_url: string | null;
    assessment_id: number | null;
    material_id: number | null;
    material: LearningMaterialItem | null;
    block_order: number;
}

interface FeedbackQuestionItem {
    questionID: string;
    category: string | null;
    questionText: string;
    questionType: 'likert' | 'rating' | 'short_text' | 'paragraph' | 'yes_no' | 'multiple_choice' | 'image';
    questionImage: string | null;
    options_json: string | null;
    isRequired: boolean;
    questionOrder: number;
}

interface FeedbackFormData {
    formID: string;
    title: string;
    questions: FeedbackQuestionItem[];
}

interface CourseModuleDef {
    module_id: number;
    module_title: string;
    module_order: number;
    blocks?: ContentBlock[];
}

interface AssessmentItem {
    assessment_id: number;
    module_id: number | null;
    type: string;
    title: string;
    instruction: string | null;
    deadline: string | null;
    total_marks: number | null;
    accepted_format: string | null;
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
    has_applied: boolean;
    can_apply: boolean;
    is_full: boolean;
    ladap_cap_reached?: boolean;
    application: Application | null;
    attended_session_ids: string[];
    course_modules?: CourseModuleDef[];
    assessments?: AssessmentItem[] | null;
    learning_materials?: LearningMaterialItem[] | null;
    enrollment_id?: number | null;
    feedback_form?: FeedbackFormData | null;
    has_submitted_feedback?: boolean;
}

// Fallback — used when controller doesn't pass course_modules
const DEFAULT_MODULES: CourseModuleDef[] = [
    { module_id: 1, module_title: 'General Info',    module_order: 1 },
    { module_id: 2, module_title: 'Who Am I',        module_order: 2 },
    { module_id: 3, module_title: 'Teaching Plan',   module_order: 3 },
    { module_id: 4, module_title: 'Book & Resource', module_order: 4 },
    { module_id: 5, module_title: 'Assessment',      module_order: 5 },
    { module_id: 6, module_title: 'Chapter',         module_order: 6 },
];

// Static attendance tile — never stored in training_module
const ATTENDANCE_TILE: CourseModuleDef = { module_id: 0,  module_title: 'Attendance', module_order: 0 };
// Static feedback tile — injected at the end when a feedback_form exists
const FEEDBACK_TILE:   CourseModuleDef = { module_id: -1, module_title: 'Feedback',   module_order: 999 };

function getModuleIcon(title: string): React.ReactNode {
    const t   = title.toLowerCase();
    const cls = 'h-14 w-14 text-white/80';
    if (t.includes('general'))                         return <Info className={cls} />;
    if (t.includes('who'))                             return <User className={cls} />;
    if (t.includes('attendance'))                      return <CalendarCheck className={cls} />;
    if (t.includes('teaching') || t.includes('plan'))  return <FileText className={cls} />;
    if (t.includes('book') || t.includes('resource'))  return <BookMarked className={cls} />;
    if (t.includes('assessment'))                      return <ClipboardList className={cls} />;
    if (t.includes('chapter'))                         return <Layers className={cls} />;
    if (t.includes('final') || t.includes('project'))  return <FileText className={cls} />;
    if (t.includes('feedback'))                          return <MessageCircle className={cls} />;
    if (t.includes('wrap'))                              return <CheckCircle className={cls} />;
    return <BookOpen className={cls} />;
}

const STATUS_CONFIG = {
    upcoming: { label: 'Upcoming', className: 'bg-blue-100 text-blue-700 border-blue-200' },
    ongoing:  { label: 'Ongoing',  className: 'bg-amber-100 text-amber-700 border-amber-200' },
    completed: { label: 'Completed', className: 'bg-green-100 text-green-700 border-green-200' },
    cancelled: { label: 'Cancelled', className: 'bg-red-100 text-red-700 border-red-200' },
};

function formatTime(time: string | null | undefined): string {
    if (!time) return '—';
    const [h, m] = time.substring(0, 5).split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    const hour   = h % 12 || 12;
    return `${hour}:${m.toString().padStart(2, '0')} ${period}`;
}

type QrState = 'active' | 'not_yet' | 'ended';

function getQrState(
    session: { session_date: string; start_time: string | null; end_time: string | null },
    now: Date,
): QrState {
    if (!session.start_time || !session.end_time) return 'not_yet';
    const d = new Date(session.session_date);
    const dateStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const start = new Date(`${dateStr}T${session.start_time.substring(0, 8)}`);
    const end   = new Date(`${dateStr}T${session.end_time.substring(0, 8)}`);
    if (now < start) return 'not_yet';
    if (now > end)   return 'ended';
    return 'active';
}

const APP_STATUS_CONFIG = {
    pending:  { label: 'Application Pending',  className: 'bg-amber-50 border-amber-200 text-amber-700' },
    approved: { label: 'You are Enrolled',     className: 'bg-green-50 border-green-200 text-green-700' },
    rejected: { label: 'Application Rejected', className: 'bg-red-50 border-red-200 text-red-600' },
};

export default function TrainingShow({ training, has_applied, can_apply, is_full, ladap_cap_reached, application, attended_session_ids, course_modules: propModules, assessments, learning_materials, enrollment_id, feedback_form, has_submitted_feedback }: Props) {
    const { isGuruBesar, isOutsider, isGuruBaru } = useRole();
    const now        = useNow();
    const statusConf = STATUS_CONFIG[training.status] ?? STATUS_CONFIG.upcoming;
    const trainingId = training.courseID ?? training.id;

    const fromParam          = new URLSearchParams(window.location.search).get('from');
    const fromApplications   = fromParam === 'applications';
    const fromMyCourses      = fromParam === 'my_courses';
    const fromMyApplications = fromParam === 'my_applications';

    const backHref  = fromApplications && isGuruBesar ? route('applications.index')
                    : fromApplications   ? route('applications.mine')
                    : fromMyCourses      ? route('guru_baru.courses.mine')
                    : fromMyApplications ? route('guru_baru.applications.mine')
                    : route('trainings.index');
    const backLabel = fromApplications && isGuruBesar ? '<- Back to Applications'
                    : fromApplications   ? '<- Back to My Applications'
                    : fromMyCourses      ? '<- Back to My Trainings'
                    : fromMyApplications ? '<- Back to My Applications'
                    : '<- Back to Trainings';

    const [activeBox,    setActiveBox]    = useState<'attendance' | 'material' | 'feedback'>('attendance');
    const [activeModule, setActiveModule] = useState<number | null>(null);
    const [navExpanded,  setNavExpanded]  = useState(true);

    const isEnrolled           = application?.status === 'approved';
    const showMoodleLayout     = isGuruBaru && fromMyCourses && isEnrolled;
    const showEnrolledSimplified = !isGuruBesar && isEnrolled && (fromApplications || fromMyApplications);

    function handleApply() {
        if (isGuruBaru) {
            router.post(route('guru_baru.applications.store', trainingId));
        } else if (isOutsider) {
            router.post(route('outsider.applications.store', trainingId));
        } else {
            router.post(route('applications.store', trainingId));
        }
    }

    // DB modules (or fallback), with static Attendance tile injected after position 2
    // and Feedback tile appended at the end when a feedback form exists
    const dbModules     = propModules ?? DEFAULT_MODULES;
    const courseModules = [
        ...dbModules.slice(0, 2),
        ATTENDANCE_TILE,
        ...dbModules.slice(2),
        ...(feedback_form ? [FEEDBACK_TILE] : []),
    ];

    // ── Full Moodle layout — GuruBaru viewing a series course from My Courses ──
    if (showMoodleLayout) {
        const activeModuleData = courseModules.find(m => m.module_id === activeModule);
        const timeStr = now.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', hour12: true }).toLowerCase();

        return (
            <AppLayout>
                <Head title={training.courseName} />

                <div className="flex" style={{ minHeight: 'calc(100vh - 56px)' }}>

                    {/* ── Sidebar ── */}
                    <aside className="flex w-60 flex-shrink-0 flex-col border-r border-gray-200 bg-white overflow-y-auto">

                        <div className="border-b border-gray-100 px-4 py-3">
                            <Link href={route('guru_baru.courses.mine')} className="text-xs text-blue-600 hover:underline">
                                &larr; Back to My Trainings
                            </Link>
                        </div>

                        {/* Clock */}
                        <div className="border-b border-gray-200 p-4">
                            <div className="mb-2 flex items-center gap-2">
                                <span className="flex h-5 w-5 items-center justify-center rounded bg-indigo-700">
                                    <Clock className="h-3 w-3 text-white" />
                                </span>
                                <span className="text-sm font-semibold text-gray-700">Clock</span>
                            </div>
                            <div className="space-y-1 text-xs text-gray-500">
                                <div className="flex justify-between">
                                    <span>Server:</span>
                                    <span className="font-medium text-gray-700">{timeStr}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>You:</span>
                                    <span className="font-medium text-gray-700">{timeStr}</span>
                                </div>
                            </div>
                        </div>

                        {/* Navigation */}
                        <div className="p-4">
                            <div className="mb-2 flex items-center gap-2">
                                <span className="flex h-5 w-5 items-center justify-center rounded bg-indigo-700">
                                    <Users className="h-3 w-3 text-white" />
                                </span>
                                <span className="text-sm font-semibold text-gray-700">Navigation</span>
                            </div>
                            <nav className="text-xs">
                                <button
                                    onClick={() => setNavExpanded(v => !v)}
                                    className="flex w-full items-center gap-1 py-1 text-left text-gray-700 hover:text-blue-700"
                                >
                                    {navExpanded
                                        ? <ChevronDown className="h-3 w-3 flex-shrink-0" />
                                        : <ChevronRight className="h-3 w-3 flex-shrink-0" />}
                                    <span className="font-medium">Dashboard</span>
                                </button>

                                {navExpanded && (
                                    <div className="ml-3 border-l border-gray-200 pl-2">
                                        <div className="flex items-center gap-1 py-0.5 text-gray-600">
                                            <ChevronDown className="h-3 w-3 flex-shrink-0" />
                                            <span>Trainings</span>
                                        </div>
                                        <div className="ml-3 border-l border-gray-200 pl-2">
                                            <div className="flex items-center gap-1 py-0.5 font-semibold text-gray-800">
                                                <ChevronDown className="h-3 w-3 flex-shrink-0" />
                                                <span className="truncate">{training.courseName}</span>
                                            </div>
                                            <div className="ml-3 mt-0.5 space-y-0.5 border-l border-gray-200 pl-2">
                                                {courseModules.map(mod => (
                                                    <button
                                                        key={mod.module_id}
                                                        onClick={() => setActiveModule(mod.module_id)}
                                                        className={`flex w-full items-center gap-1 rounded py-0.5 text-left hover:text-blue-700 ${
                                                            activeModule === mod.module_id
                                                                ? 'font-semibold text-blue-700'
                                                                : 'text-gray-600'
                                                        }`}
                                                    >
                                                        <ChevronRight className="h-3 w-3 flex-shrink-0" />
                                                        <span className="truncate">{mod.module_title}</span>
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </nav>
                        </div>
                    </aside>

                    {/* ── Main ── */}
                    <main className="flex-1 overflow-auto bg-gray-100 p-6">
                        <div className="mb-5">
                            <p className="mb-0.5 text-xs font-semibold uppercase tracking-widest text-indigo-500">Training</p>
                            <h1 className="text-2xl font-bold text-gray-900">{training.courseName}</h1>
                            {training.organiserName && (
                                <p className="mt-1 text-sm text-gray-500">Organised by <span className="font-medium text-gray-700">{training.organiserName}</span></p>
                            )}
                        </div>

                        <div className="grid grid-cols-4 gap-3">
                            {courseModules.map(mod => {
                                const isActive = activeModule === mod.module_id;
                                return (
                                    <button
                                        key={mod.module_id}
                                        onClick={() => setActiveModule(mod.module_id)}
                                        className={`overflow-hidden rounded shadow-md transition-all hover:brightness-110 focus:outline-none ${
                                            isActive ? 'ring-2 ring-white ring-offset-1' : ''
                                        }`}
                                    >
                                        <div className={`h-1.5 ${isActive ? 'bg-white/50' : 'bg-indigo-400'}`} />
                                        <div className={`flex aspect-[4/3] flex-col ${isActive ? 'bg-indigo-800' : 'bg-indigo-700'}`}>
                                            <p className="px-2.5 pt-2 text-left text-lg font-bold uppercase tracking-wide text-white">
                                                {mod.module_title}
                                            </p>
                                            <div className="flex flex-1 items-center justify-center">
                                                {getModuleIcon(mod.module_title)}
                                            </div>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </main>
                </div>

                {/* ── Module Modal ── */}
                {activeModule !== null && activeModuleData && (
                    <div
                        className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-6"
                        onClick={e => { if (e.target === e.currentTarget) setActiveModule(null); }}
                    >
                        <div className="relative max-h-[80vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-2xl">
                            <div className="p-8">
                                <button
                                    onClick={() => setActiveModule(null)}
                                    className="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 text-gray-500 hover:bg-gray-100"
                                >
                                    <X className="h-4 w-4" />
                                </button>

                                <div className="mb-8">
                                    <h2 className="text-2xl font-bold uppercase tracking-wide text-gray-800">
                                        {activeModuleData.module_title}
                                    </h2>
                                    <div className="mt-1.5 h-0.5 w-20 bg-blue-600" />
                                </div>

                                {activeModuleData.module_id === FEEDBACK_TILE.module_id ? (
                                    feedback_form
                                        ? <FeedbackPanel form={feedback_form} trainingId={trainingId} hasSubmitted={has_submitted_feedback ?? false} />
                                        : <p className="text-sm text-gray-400">No feedback form available for this course.</p>
                                ) : activeModuleData.module_title.toLowerCase() === 'attendance' ? (
                                    <AttendancePanel
                                        sessions={training.course_sessions ?? []}
                                        attendedIds={attended_session_ids}
                                        trainingId={trainingId}
                                        now={now}
                                    />
                                ) : activeModuleData.module_title.toLowerCase().includes('assessment') ? (
                                    <AssessmentPanel
                                        assessments={assessments ?? []}
                                        trainingId={trainingId}
                                    />
                                ) : (
                                    <ContentBlocksPanel
                                        blocks={activeModuleData.blocks ?? []}
                                        assessments={assessments ?? []}
                                        trainingId={trainingId}
                                        moduleId={activeModuleData.module_id}
                                        enrollmentId={enrollment_id ?? null}
                                    />
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={training.courseName} />
            <div className="mx-auto w-full max-w-4xl flex flex-1 flex-col px-6 py-8 sm:px-8">

                {/* ── Enrolled view from My Applications — simplified tabs ── */}
                {showEnrolledSimplified ? (
                    <div className="space-y-4">
                        <Link href={backHref} className="mb-2 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                            {backLabel}
                        </Link>

                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <span className={`mb-2 inline-block rounded-full border px-3 py-0.5 text-xs font-semibold ${statusConf.className}`}>
                                        {statusConf.label}
                                    </span>
                                    <h1 className="text-2xl font-bold text-gray-900">{training.courseName}</h1>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Organiser: <span className="font-medium text-gray-700">{training.organiserName}</span>
                                    </p>
                                    {training.course_category && (
                                        <p className="mt-1 text-sm text-gray-500">
                                            Category: <span className="font-medium text-gray-700">
                                                {training.course_category.split('_').map((w: string) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}
                                            </span>
                                        </p>
                                    )}
                                </div>
                                <span className="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                    <CheckCircle className="h-3.5 w-3.5" /> Enrolled
                                </span>
                            </div>
                            {training.whatsappGroup && (
                                <a href={training.whatsappGroup} target="_blank" rel="noreferrer"
                                    className="mt-4 inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline">
                                    <MessageCircle className="h-4 w-4" /> Join WhatsApp Group
                                </a>
                            )}
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            {([
                                { key: 'attendance' as const, label: 'Attendance',        icon: <QrCode className="h-8 w-8" />,          bg: 'bg-blue-100', iconColor: 'text-blue-600', activeBorder: 'border-blue-500 bg-blue-50' },
                                { key: 'material'   as const, label: 'Learning Material', icon: <BookOpen className="h-8 w-8" />,        bg: 'bg-blue-100',   iconColor: 'text-blue-600',   activeBorder: 'border-blue-500 bg-blue-50' },
                                { key: 'feedback'   as const, label: 'Feedback',          icon: <MessageCircle className="h-8 w-8" />,    bg: 'bg-green-100',  iconColor: 'text-green-600',  activeBorder: 'border-green-500 bg-green-50' },
                            ]).map(({ key, label, icon, bg, iconColor, activeBorder }) => {
                                const isActive = activeBox === key;
                                return (
                                    <button key={key} onClick={() => setActiveBox(key)}
                                        className={`flex aspect-square flex-col items-center justify-center gap-3 rounded-xl border-2 p-6 transition-all hover:shadow-md ${
                                            isActive ? `${activeBorder} shadow-sm` : 'border-gray-200 bg-white hover:border-gray-300'
                                        }`}
                                    >
                                        <span className={`flex h-14 w-14 items-center justify-center rounded-xl ${bg} ${iconColor}`}>{icon}</span>
                                        <span className={`text-sm font-semibold ${isActive ? 'text-gray-900' : 'text-gray-600'}`}>{label}</span>
                                    </button>
                                );
                            })}
                        </div>

                        <div className="rounded-xl border border-gray-200 bg-white shadow-sm">
                            {activeBox === 'attendance' && (
                                <div className="p-6">
                                    <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Attendance</h3>
                                    <AttendancePanel
                                        sessions={training.course_sessions ?? []}
                                        attendedIds={attended_session_ids}
                                        trainingId={trainingId}
                                        now={now}
                                    />
                                </div>
                            )}
                            {activeBox === 'material' && (
                                <div className="p-6">
                                    <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Learning Material</h3>
                                    {learning_materials && learning_materials.length > 0
                                        ? <LearningMaterialList materials={learning_materials} />
                                        : <EmptyPanel message="No learning material available yet." />
                                    }
                                </div>
                            )}
                            {activeBox === 'feedback' && (
                                <div className="p-6">
                                    <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Feedback</h3>
                                    {feedback_form
                                        ? <FeedbackPanel form={feedback_form} trainingId={trainingId} hasSubmitted={has_submitted_feedback ?? false} />
                                        : <EmptyPanel message="No form available yet." />
                                    }
                                </div>
                            )}
                        </div>
                    </div>
                ) : (

                /* ── Full training detail view (courses list, GuruBesar, non-enrolled) ── */
                <div className="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="px-6 pt-6">
                        <Link href={backHref} className="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                            {backLabel}
                        </Link>
                    </div>

                    <div className="border-b border-gray-100 p-6">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <span className={`rounded-full border px-3 py-0.5 text-xs font-semibold ${statusConf.className}`}>
                                        {statusConf.label}
                                    </span>
                                </div>
                                <h1 className="text-2xl font-bold text-gray-900">{training.courseName}</h1>
                                <p className="mt-1 text-sm text-gray-500">
                                    Organiser: <span className="font-medium text-gray-700">{training.organiserName}</span>
                                </p>
                            </div>
                            {isGuruBesar && training.status === 'upcoming' && (
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    <Link href={route('trainings.assign.form', trainingId)}
                                        className="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                        <UserPlus className="h-3.5 w-3.5" /> Assign Teachers
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>

                    {training.poster && (
                        <div className="border-b border-gray-100 px-6 pt-4 pb-0">
                            <img src={`/storage/${training.poster}`} alt="Course poster" className="max-h-64 w-full rounded-lg object-cover" />
                        </div>
                    )}

                    <div className="grid gap-6 p-6 sm:grid-cols-2">
                        <div className="space-y-4">
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-400">Details</h2>
                            <InfoRow icon={<Monitor className="h-4 w-4" />} label="Mode">{formatMode(training.mode) ?? '—'}</InfoRow>
                            <InfoRow icon={<Users className="h-4 w-4" />} label="Capacity">
                                {training.applications_count ?? 0} / {training.capacity} enrolled
                            </InfoRow>
                            {training.course_category && (
                                <InfoRow icon={<Tag className="h-4 w-4" />} label="Category">
                                    {training.course_category.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}
                                </InfoRow>
                            )}
                            {training.whatsappGroup && (
                                <InfoRow icon={<MessageCircle className="h-4 w-4" />} label="WhatsApp Group">
                                    <a href={training.whatsappGroup} target="_blank" rel="noreferrer" className="text-blue-600 underline">Join Group</a>
                                </InfoRow>
                            )}
                        </div>
                        <div className="space-y-4">
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-400">Organiser</h2>
                            <InfoRow icon={<UserCircle2 className="h-4 w-4" />} label="Organiser Name">{training.organiserName}</InfoRow>
                        </div>
                    </div>

                    {training.description && (
                        <div className="border-t border-gray-100 p-6">
                            <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-400">Description</h2>
                            <p className="whitespace-pre-line text-sm text-gray-700">{training.description}</p>
                        </div>
                    )}

                    {training.course_sessions && training.course_sessions.length > 0 && (
                        <div className="border-t border-gray-100 p-6">
                            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Training Sessions</h2>
                            <div className="space-y-3">
                                {training.course_sessions.map((session, idx) => (
                                    <div key={session.session_id} className="rounded-xl border border-gray-100 bg-gray-50 px-5 py-4">
                                        <div className="mb-2 flex items-center gap-2">
                                            <span className="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">{idx + 1}</span>
                                            <span className="font-semibold text-gray-900">
                                                {new Date(session.session_date).toLocaleDateString('en-MY', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })}
                                            </span>
                                        </div>
                                        <div className="flex flex-wrap gap-x-6 gap-y-1.5 text-sm text-gray-600">
                                            {(session.start_time || session.end_time) && (
                                                <span className="flex items-center gap-1.5">
                                                    <Clock className="h-3.5 w-3.5 text-gray-400" />
                                                    {formatTime(session.start_time)} – {formatTime(session.end_time)}
                                                </span>
                                            )}
                                            {session.location && (
                                                <span className="flex items-center gap-1.5">
                                                    <MapPin className="h-3.5 w-3.5 text-gray-400" />
                                                    {session.location}
                                                </span>
                                            )}
                                            {(session.trainers ?? []).length > 0 && (
                                                <span className="flex items-center gap-1.5">
                                                    <UserCircle2 className="h-3.5 w-3.5 text-gray-400" />
                                                    {(session.trainers ?? []).map(t => t.trainer_name).join(', ')}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {isEnrolled && learning_materials && learning_materials.length > 0 && (
                        <div className="border-t border-gray-100 p-6">
                            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Learning Material</h2>
                            <LearningMaterialList materials={learning_materials} />
                        </div>
                    )}

                    {isEnrolled && feedback_form && (
                        <div className="border-t border-gray-100 p-6">
                            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Feedback</h2>
                            <FeedbackPanel form={feedback_form} trainingId={trainingId} hasSubmitted={has_submitted_feedback ?? false} />
                        </div>
                    )}

                    {!isGuruBesar && (
                        <div className="border-t border-gray-100 p-6">
                            {has_applied && application ? (
                                <div className={`flex items-center gap-3 rounded-lg border p-4 ${APP_STATUS_CONFIG[application.status].className}`}>
                                    {application.status === 'approved' && <CheckCircle className="h-5 w-5" />}
                                    {application.status === 'pending'  && <AlertTriangle className="h-5 w-5" />}
                                    {application.status === 'rejected' && <XCircle className="h-5 w-5" />}
                                    <div>
                                        <p className="font-semibold">{APP_STATUS_CONFIG[application.status].label}</p>
                                        {application.rejection_reason && (
                                            <p className="mt-0.5 text-sm opacity-80">Reason: {application.rejection_reason}</p>
                                        )}
                                    </div>
                                </div>
                            ) : can_apply ? (
                                <div className="flex items-center justify-between">
                                    <p className="text-sm text-gray-600">Registration is open. Apply now to secure your spot.</p>
                                    <button onClick={handleApply}
                                        className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                                        Apply Now
                                    </button>
                                </div>
                            ) : is_full ? (
                                <p className="text-sm font-semibold text-red-600">This training is full. No more applications can be accepted.</p>
                            ) : ladap_cap_reached ? (
                                <p className="text-sm font-semibold text-red-600">You have reached the 30-hour LADAP target and cannot enroll in more training.</p>
                            ) : (
                                <p className="text-sm text-gray-500">Registration is not available for this training.</p>
                            )}
                        </div>
                    )}
                </div>
                )}
            </div>
        </AppLayout>
    );
}

type SessionRow = {
    session_id: string;
    session_date: string;
    start_time: string | null;
    end_time: string | null;
    location?: string | null;
    trainers?: { trainer_name: string }[];
};

function AttendancePanel({ sessions, attendedIds, trainingId, now }: {
    sessions: SessionRow[];
    attendedIds: string[];
    trainingId: string;
    now: Date;
}) {
    if (sessions.length === 0) return <p className="text-sm text-gray-400">No sessions available.</p>;

    const grouped = sessions.reduce<Record<string, SessionRow[]>>((acc, s) => {
        if (!acc[s.session_date]) acc[s.session_date] = [];
        acc[s.session_date]!.push(s);
        return acc;
    }, {});
    const days = Object.entries(grouped).sort(([a], [b]) => a.localeCompare(b));

    return (
        <div className="space-y-5">
            {days.map(([date, daySessions], dayIdx) => {
                const dayAttended = daySessions.some(s => attendedIds.includes(s.session_id));
                return (
                    <div key={date}>
                        <div className="mb-2 flex items-center gap-2">
                            <span className="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">{dayIdx + 1}</span>
                            <span className="font-semibold text-gray-900">
                                {new Date(date).toLocaleDateString('en-MY', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })}
                            </span>
                            {dayAttended && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                    <CheckCircle className="h-3 w-3" /> Attended
                                </span>
                            )}
                        </div>
                        <div className="space-y-3 pl-7">
                            {daySessions.map(session => {
                                const alreadyAttended = attendedIds.includes(session.session_id);
                                const dayDoneByOther  = dayAttended && !alreadyAttended;
                                return (
                                    <div key={session.session_id} className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                        <div className="mb-3 flex flex-wrap gap-x-6 gap-y-1.5 text-sm text-gray-600">
                                            {(session.start_time || session.end_time) && (
                                                <span className="flex items-center gap-1.5">
                                                    <Clock className="h-3.5 w-3.5 text-gray-400" />
                                                    {formatTime(session.start_time)} – {formatTime(session.end_time)}
                                                </span>
                                            )}
                                            {session.location && (
                                                <span className="flex items-center gap-1.5">
                                                    <MapPin className="h-3.5 w-3.5 text-gray-400" />
                                                    {session.location}
                                                </span>
                                            )}
                                            {(session.trainers ?? []).length > 0 && (
                                                <span className="flex items-center gap-1.5">
                                                    <UserCircle2 className="h-3.5 w-3.5 text-gray-400" />
                                                    {(session.trainers ?? []).map(t => t.trainer_name).join(', ')}
                                                </span>
                                            )}
                                        </div>
                                        {alreadyAttended ? (
                                            <div className="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                                                <CheckCircle className="h-4 w-4" /> You attended this session.
                                            </div>
                                        ) : dayDoneByOther ? (
                                            <div className="flex items-center gap-2 rounded-lg border border-dashed border-gray-200 bg-white px-4 py-3 text-sm text-gray-400">
                                                <CheckCircle className="h-4 w-4" /> Already attended today — no action needed.
                                            </div>
                                        ) : (() => {
                                            const state = getQrState(session, now);
                                            if (state === 'active') return (
                                                <div className="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                                                    <div>
                                                        <p className="text-sm font-semibold text-blue-700">Session is active now</p>
                                                        <p className="text-xs text-blue-500">Open until {formatTime(session.end_time)}</p>
                                                    </div>
                                                    <Link
                                                        href={route('attendance.mark.form', trainingId)}
                                                        className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                                    >
                                                        Mark Present
                                                    </Link>
                                                </div>
                                            );
                                            if (state === 'ended') return (
                                                <div className="flex items-center gap-2 rounded-lg border border-dashed border-gray-200 bg-white px-4 py-3 text-sm text-gray-400">
                                                    <Clock className="h-4 w-4" /> Session has ended.
                                                </div>
                                            );
                                            return (
                                                <div className="flex items-center gap-2 rounded-lg border border-dashed border-gray-200 bg-white px-4 py-3 text-sm text-gray-400">
                                                    <Clock className="h-4 w-4" /> Attendance opens on{' '}
                                                    {new Date(session.session_date).toLocaleDateString('en-MY', { day: '2-digit', month: 'short' })}
                                                    {session.start_time && ` at ${formatTime(session.start_time)}`}.
                                                </div>
                                            );
                                        })()}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function AssessmentPanel({ assessments, trainingId }: { assessments: AssessmentItem[]; trainingId: string }) {
    const quizzes = assessments.filter(a => a.type === 'quiz');
    const tasks   = assessments.filter(a => a.type !== 'quiz');

    function statusBadge(sub: AssessmentItem['my_submission']) {
        if (!sub) return <span className="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Not submitted</span>;
        if (sub.status === 'graded') return <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Graded {sub.grade !== null ? `— ${sub.grade}` : ''}</span>;
        return <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Submitted</span>;
    }

    function AssessmentRow({ a }: { a: AssessmentItem }) {
        const isQuiz = a.type === 'quiz';
        return (
            <div className="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <span className={`flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ${isQuiz ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'}`}>
                            {isQuiz ? 'Quiz' : 'Task'}
                        </span>
                        <p className="truncate text-sm font-medium text-gray-900">{a.title}</p>
                    </div>
                    {a.deadline && (
                        <p className="mt-0.5 text-xs text-gray-400">
                            Due {new Date(a.deadline).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' })}
                        </p>
                    )}
                </div>
                <div className="flex flex-shrink-0 items-center gap-2">
                    {statusBadge(a.my_submission)}
                    <Link
                        href={route('assessments.show', { training: trainingId, assessment: a.assessment_id }) + '?from=my_courses'}
                        className="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                    >
                        Open <ChevronRight className="h-3 w-3" />
                    </Link>
                </div>
            </div>
        );
    }

    if (assessments.length === 0) {
        return <p className="py-6 text-center text-sm text-gray-400">No assessments available yet.</p>;
    }

    return (
        <div className="space-y-6">
            {quizzes.length > 0 && (
                <div>
                    <div className="mb-3 flex items-center gap-3">
                        <div className="h-6 w-1 rounded-full bg-blue-600" />
                        <h3 className="text-sm font-bold uppercase tracking-widest text-gray-700">Quizzes</h3>
                    </div>
                    <div className="space-y-2">{quizzes.map(a => <AssessmentRow key={a.assessment_id} a={a} />)}</div>
                </div>
            )}
            {tasks.length > 0 && (
                <div>
                    <div className="mb-3 flex items-center gap-3">
                        <div className="h-6 w-1 rounded-full bg-blue-600" />
                        <h3 className="text-sm font-bold uppercase tracking-widest text-gray-700">Tasks</h3>
                    </div>
                    <div className="space-y-2">{tasks.map(a => <AssessmentRow key={a.assessment_id} a={a} />)}</div>
                </div>
            )}
        </div>
    );
}

function ModuleSections({ sections }: { sections: string[] }) {
    return (
        <div className="space-y-6">
            {sections.map(section => (
                <div key={section}>
                    <div className="mb-3 flex items-center gap-3">
                        <div className="h-6 w-1 rounded-full bg-blue-600" />
                        <h3 className="text-sm font-bold uppercase tracking-widest text-gray-700">{section}</h3>
                    </div>
                    <div className="border-t border-gray-100 pt-3">
                        <p className="pl-4 text-sm text-gray-400">No content available yet.</p>
                    </div>
                </div>
            ))}
        </div>
    );
}

function EmptyPanel({ message }: { message: string }) {
    return (
        <div className="flex flex-col items-center gap-2 py-10 text-gray-400">
            <BookOpen className="h-10 w-10 text-gray-200" />
            <p className="text-sm">{message}</p>
        </div>
    );
}

const QUESTION_TYPE_LABEL: Record<string, string> = {
    likert:          'Likert Scale',
    rating:          'Rating (1–10)',
    short_text:      'Short Answer',
    paragraph:       'Paragraph',
    yes_no:          'Yes / No',
    multiple_choice: 'Multiple Choice',
    image:           'Image',
};

const LIKERT_LABELS = ['Strongly Disagree', 'Disagree', 'Neutral', 'Agree', 'Strongly Agree'];

function FeedbackPanel({
    form,
    trainingId,
    hasSubmitted,
}: {
    form: FeedbackFormData;
    trainingId: string;
    hasSubmitted: boolean;
}) {
    const [answers, setAnswers]       = useState<Record<string, string>>({});
    const [submitted, setSubmitted]   = useState(hasSubmitted);
    const [submitting, setSubmitting] = useState(false);
    const [fieldError, setFieldError] = useState<string | null>(null);
    const [showModal, setShowModal]   = useState(false);
    const [rating, setRating]         = useState<number | null>(null);
    const [comment, setComment]       = useState('');
    const [hoverRating, setHoverRating] = useState<number | null>(null);

    const requiredIds = form.questions.filter(q => q.isRequired).map(q => q.questionID);
    const isComplete  = requiredIds.every(id => (answers[id] ?? '').trim() !== '');

    function setAnswer(qId: string, val: string) {
        setAnswers(prev => ({ ...prev, [qId]: val }));
        setFieldError(null);
    }

    function handleSubmit() {
        if (!isComplete) {
            setFieldError('Please answer all required questions (marked with *).');
            return;
        }
        setFieldError(null);
        setShowModal(true);
    }

    function handleConfirm() {
        setSubmitting(true);
        router.post(
            route('feedback.submit', { training: trainingId, form: form.formID }),
            { answers, rating, comment },
            {
                preserveScroll: true,
                onSuccess: () => { setSubmitted(true); setShowModal(false); },
                onError: (errors) => {
                    setShowModal(false);
                    setFieldError(errors.answers ?? 'Submission failed. Please try again.');
                },
                onFinish: () => setSubmitting(false),
            },
        );
    }

    // Group questions by category, preserving order of first appearance
    const groups = form.questions.reduce<{ category: string | null; questions: FeedbackQuestionItem[] }[]>(
        (acc, q) => {
            const existing = acc.find(g => g.category === q.category);
            if (existing) existing.questions.push(q);
            else acc.push({ category: q.category, questions: [q] });
            return acc;
        },
        [],
    );

    if (submitted) {
        return (
            <div className="flex flex-col items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-6 py-10 text-center">
                <CheckCircle className="h-12 w-12 text-green-500" />
                <p className="text-base font-semibold text-green-800">Thank you for your feedback!</p>
                <p className="text-sm text-green-600">Your response has been recorded.</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-base font-semibold text-gray-900">{form.title}</h3>
                <p className="mt-1 text-xs text-gray-400">Fields marked <span className="text-red-500">*</span> are required.</p>
            </div>

            {groups.map((group, gi) => (
                <div key={gi} className="space-y-3">
                    {group.category && (
                        <div className="flex items-center gap-2">
                            <div className="h-5 w-1 rounded-full bg-green-500" />
                            <p className="text-xs font-bold uppercase tracking-widest text-gray-600">{group.category}</p>
                        </div>
                    )}
                    {group.questions.map(q => {
                        let options: string[] = [];
                        if (q.questionType === 'multiple_choice' && q.options_json) {
                            try { options = JSON.parse(q.options_json); } catch { options = []; }
                        }
                        const current = answers[q.questionID] ?? '';

                        return (
                            <div key={q.questionID} className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                <div className="mb-3 flex items-start justify-between gap-3">
                                    <p className="text-sm font-medium leading-snug text-gray-800">
                                        <span className="mr-1.5 text-gray-400">{q.questionOrder}.</span>
                                        {q.questionText}
                                        {q.isRequired && <span className="ml-1 text-red-500">*</span>}
                                    </p>
                                    <span className="flex-shrink-0 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                        {QUESTION_TYPE_LABEL[q.questionType] ?? q.questionType}
                                    </span>
                                </div>

                                {q.questionType === 'likert' && (
                                    <div className="flex gap-1">
                                        {LIKERT_LABELS.map((label, i) => {
                                            const val = String(i + 1);
                                            const active = current === val;
                                            return (
                                                <button
                                                    key={i}
                                                    type="button"
                                                    onClick={() => setAnswer(q.questionID, val)}
                                                    className={`flex flex-1 flex-col items-center gap-1.5 rounded-lg border-2 py-2 text-center transition-colors ${
                                                        active
                                                            ? 'border-green-500 bg-green-50'
                                                            : 'border-gray-200 bg-white hover:border-green-300'
                                                    }`}
                                                >
                                                    <span className={`text-sm font-bold ${active ? 'text-green-600' : 'text-gray-400'}`}>{val}</span>
                                                    <span className="text-xs leading-tight text-gray-500">{label}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}

                                {q.questionType === 'rating' && (
                                    <div className="flex flex-wrap gap-1.5">
                                        {Array.from({ length: 10 }, (_, i) => String(i + 1)).map(val => {
                                            const active = current === val;
                                            return (
                                                <button
                                                    key={val}
                                                    type="button"
                                                    onClick={() => setAnswer(q.questionID, val)}
                                                    className={`flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-semibold transition-colors ${
                                                        active
                                                            ? 'border-green-500 bg-green-500 text-white'
                                                            : 'border-gray-300 bg-white text-gray-500 hover:border-green-400'
                                                    }`}
                                                >
                                                    {val}
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}

                                {q.questionType === 'yes_no' && (
                                    <div className="flex gap-3">
                                        {['Yes', 'No'].map(opt => {
                                            const active = current === opt;
                                            return (
                                                <button
                                                    key={opt}
                                                    type="button"
                                                    onClick={() => setAnswer(q.questionID, opt)}
                                                    className={`flex items-center gap-2 rounded-lg border-2 px-5 py-2 text-sm font-medium transition-colors ${
                                                        active
                                                            ? 'border-green-500 bg-green-50 text-green-700'
                                                            : 'border-gray-200 bg-white text-gray-600 hover:border-green-300'
                                                    }`}
                                                >
                                                    <span className={`flex h-4 w-4 items-center justify-center rounded-full border-2 ${active ? 'border-green-500 bg-green-500' : 'border-gray-300'}`}>
                                                        {active && <span className="h-1.5 w-1.5 rounded-full bg-white" />}
                                                    </span>
                                                    {opt}
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}

                                {q.questionType === 'multiple_choice' && options.length > 0 && (
                                    <div className="space-y-2">
                                        {options.map((opt, i) => {
                                            const active = current === opt;
                                            return (
                                                <button
                                                    key={i}
                                                    type="button"
                                                    onClick={() => setAnswer(q.questionID, opt)}
                                                    className={`flex w-full items-center gap-2.5 rounded-lg border-2 px-4 py-2.5 text-left text-sm transition-colors ${
                                                        active
                                                            ? 'border-green-500 bg-green-50 text-green-800'
                                                            : 'border-gray-200 bg-white text-gray-600 hover:border-green-300'
                                                    }`}
                                                >
                                                    <span className={`flex h-4 w-4 flex-shrink-0 items-center justify-center rounded border-2 ${active ? 'border-green-500 bg-green-500' : 'border-gray-300 bg-white'}`}>
                                                        {active && <span className="text-white" style={{ fontSize: 9, lineHeight: 1 }}>✓</span>}
                                                    </span>
                                                    {opt}
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}

                                {q.questionType === 'short_text' && (
                                    <input
                                        type="text"
                                        value={current}
                                        onChange={e => setAnswer(q.questionID, e.target.value)}
                                        placeholder="Your answer…"
                                        className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-green-400 focus:outline-none focus:ring-1 focus:ring-green-400"
                                    />
                                )}

                                {q.questionType === 'paragraph' && (
                                    <textarea
                                        value={current}
                                        onChange={e => setAnswer(q.questionID, e.target.value)}
                                        placeholder="Your answer…"
                                        rows={4}
                                        className="mt-1 w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-green-400 focus:outline-none focus:ring-1 focus:ring-green-400"
                                    />
                                )}

                                {q.questionType === 'image' && (
                                    <div className="space-y-3">
                                        {q.questionImage && (
                                            <img
                                                src={`/storage/${q.questionImage}`}
                                                alt={q.questionText}
                                                className="max-h-64 w-full rounded-lg border border-gray-200 object-contain"
                                            />
                                        )}
                                        <textarea
                                            value={current}
                                            onChange={e => setAnswer(q.questionID, e.target.value)}
                                            placeholder="Your answer…"
                                            rows={3}
                                            className="w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-green-400 focus:outline-none focus:ring-1 focus:ring-green-400"
                                        />
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            ))}

            {fieldError && (
                <p className="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-600">{fieldError}</p>
            )}

            <div className="flex justify-end">
                <button
                    type="button"
                    onClick={handleSubmit}
                    disabled={submitting}
                    className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Submit Feedback
                </button>
            </div>

            {/* Confirmation modal */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                        <h2 className="mb-1 text-lg font-bold text-gray-900">Confirm Submission</h2>
                        <p className="mb-5 text-sm text-gray-500">
                            Add an overall rating and comment before confirming. You cannot change your answers after submitting.
                        </p>

                        {/* Overall rating */}
                        <div className="mb-4">
                            <label className="mb-2 block text-sm font-semibold text-gray-700">
                                Overall Rating <span className="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <div className="flex gap-1">
                                {[1, 2, 3, 4, 5].map(star => (
                                    <button
                                        key={star}
                                        type="button"
                                        onClick={() => setRating(star === rating ? null : star)}
                                        onMouseEnter={() => setHoverRating(star)}
                                        onMouseLeave={() => setHoverRating(null)}
                                        className="text-2xl transition-transform hover:scale-110 focus:outline-none"
                                    >
                                        <span className={(hoverRating ?? rating ?? 0) >= star ? 'text-yellow-400' : 'text-gray-300'}>
                                            ★
                                        </span>
                                    </button>
                                ))}
                                {rating && (
                                    <span className="ml-2 self-center text-sm font-medium text-gray-600">{rating}/5</span>
                                )}
                            </div>
                        </div>

                        {/* Comment */}
                        <div className="mb-6">
                            <label className="mb-2 block text-sm font-semibold text-gray-700">
                                Comment <span className="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea
                                value={comment}
                                onChange={e => setComment(e.target.value)}
                                rows={3}
                                placeholder="Any additional thoughts or suggestions…"
                                className="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-green-400 focus:outline-none focus:ring-1 focus:ring-green-400"
                            />
                        </div>

                        <div className="flex justify-end gap-3">
                            <button
                                type="button"
                                onClick={() => setShowModal(false)}
                                className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={handleConfirm}
                                disabled={submitting}
                                className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60"
                            >
                                {submitting ? 'Submitting…' : 'Confirm & Submit'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

function logInteraction(
    trainingId: string,
    blockId: number,
    enrollmentId: number | null,
    eventType: string,
    secondsSpent = 0,
) {
    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    fetch(`/trainings/${trainingId}/blocks/${blockId}/interact`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ event_type: eventType, seconds_spent: secondsSpent }),
    }).catch(() => {});
}

function ContentBlocksPanel({
    blocks,
    assessments = [],
    trainingId,
    moduleId,
    enrollmentId = null,
}: {
    blocks: ContentBlock[];
    assessments?: AssessmentItem[];
    trainingId: string;
    moduleId: number;
    enrollmentId?: number | null;
}) {
    const [viewingMaterial, setViewingMaterial] = useState<{ material: LearningMaterialItem; blockId: number } | null>(null);
    const [doneGroups, setDoneGroups] = useState<Set<string>>(new Set());

    function toggleDone(type: string) {
        setDoneGroups(prev => {
            const next = new Set(prev);
            next.has(type) ? next.delete(type) : next.add(type);
            return next;
        });
    }

    const regularBlocks = blocks.filter(b => b.block_type !== 'quiz' && b.block_type !== 'submission');

    // Lookup: assessment_id → block_id (from quiz/submission blocks filtered out of display)
    const assessmentBlockMap = new Map<number, number>(
        blocks
            .filter(b => (b.block_type === 'quiz' || b.block_type === 'submission') && b.assessment_id != null)
            .map(b => [b.assessment_id!, b.block_id])
    );

    // Quizzes and tasks come from the assessments array filtered by this module
    const moduleAssessments = assessments.filter(a => a.module_id === moduleId);
    const quizzes = moduleAssessments.filter(a => a.type === 'quiz');
    const tasks   = moduleAssessments.filter(a => a.type !== 'quiz');

    function statusBadge(sub: AssessmentItem['my_submission']) {
        if (!sub) return <span className="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Not submitted</span>;
        if (sub.status === 'graded') return <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Graded{sub.grade !== null ? ` — ${sub.grade}` : ''}</span>;
        return <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Submitted</span>;
    }

    function AssessmentRow({ a }: { a: AssessmentItem }) {
        const isQuiz  = a.type === 'quiz';
        const blockId = assessmentBlockMap.get(a.assessment_id);
        return (
            <div className="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <span className={`flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ${isQuiz ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'}`}>
                            {isQuiz ? 'Quiz' : 'Task'}
                        </span>
                        <p className="truncate text-sm font-medium text-gray-900">{a.title}</p>
                    </div>
                    {a.deadline && (
                        <p className="mt-0.5 text-xs text-gray-400">
                            Due {new Date(a.deadline).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' })}
                        </p>
                    )}
                </div>
                <div className="flex flex-shrink-0 items-center gap-2">
                    {statusBadge(a.my_submission)}
                    <Link
                        href={route('assessments.show', { training: trainingId, assessment: a.assessment_id }) + '?from=my_courses'}
                        onClick={() => { if (blockId) logInteraction(trainingId, blockId, enrollmentId, 'open'); }}
                        className="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                    >
                        Open <ChevronRight className="h-3 w-3" />
                    </Link>
                </div>
            </div>
        );
    }

    const hasContent = regularBlocks.length > 0 || quizzes.length > 0 || tasks.length > 0;

    if (!hasContent) {
        return (
            <div className="flex flex-col items-center gap-2 py-10 text-gray-400">
                <BookOpen className="h-12 w-12 text-gray-200" />
                <p className="text-sm">No content available yet.</p>
            </div>
        );
    }

    // Group blocks with the same block_type under one header, then sort by priority
    const BLOCK_TYPE_ORDER: Record<string, number> = { material: 0, youtube: 1 };
    const groupedBlocks = regularBlocks
        .reduce<{ type: string; blocks: ContentBlock[] }[]>((acc, block) => {
            const existing = acc.find(g => g.type === block.block_type);
            if (existing) { existing.blocks.push(block); }
            else { acc.push({ type: block.block_type, blocks: [block] }); }
            return acc;
        }, [])
        .sort((a, b) => (BLOCK_TYPE_ORDER[a.type] ?? 99) - (BLOCK_TYPE_ORDER[b.type] ?? 99));

    return (
        <div className="space-y-6">
            {groupedBlocks.length > 0 && (
                <div className="space-y-5">
                    {groupedBlocks.map(group => {
                        const done = doneGroups.has(group.type);
                        return (
                            <div key={group.type}>
                                <div className="mb-3 flex items-center gap-3">
                                    <div className="h-6 w-1 rounded-full bg-blue-600" />
                                    <h3 className="text-sm font-bold uppercase tracking-widest text-gray-700">{group.type}</h3>
                                    <button
                                        onClick={() => toggleDone(group.type)}
                                        className={`ml-auto inline-flex shrink-0 items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium ${
                                            done
                                                ? 'border-green-200 bg-green-50 text-green-700'
                                                : 'border-gray-200 bg-white text-gray-400 hover:border-green-300 hover:bg-green-50 hover:text-green-700'
                                        }`}
                                    >
                                        <CheckCircle className="h-3.5 w-3.5" />
                                        {done ? 'Done' : 'Mark as Done'}
                                    </button>
                                </div>
                                <div className="space-y-1.5">
                                    {group.blocks.map(block => (
                                        <div key={block.block_id}>
                                            {block.block_type === 'material' && block.material ? (
                                                <button
                                                    onClick={() => setViewingMaterial({ material: block.material!, blockId: block.block_id })}
                                                    className="flex w-full items-center gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-left text-sm text-blue-700 hover:bg-blue-100"
                                                >
                                                    <FileText className="h-5 w-5 flex-shrink-0" />
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate font-medium">{block.material.original_file_name ?? block.block_title ?? 'Download Material'}</p>
                                                        {(block.material.session_start_time || block.material.session_end_time) && (
                                                            <p className="text-xs text-blue-500">
                                                                Class session for {formatTime(block.material.session_start_time)} – {formatTime(block.material.session_end_time)}
                                                            </p>
                                                        )}
                                                        {block.material.trainer && (
                                                            <p className="text-xs text-blue-400">Trainer: {block.material.trainer}</p>
                                                        )}
                                                    </div>
                                                    <Download className="h-4 w-4 flex-shrink-0 text-blue-400" />
                                                </button>
                                            ) : block.block_type === 'youtube' && block.external_url ? (
                                                <>
                                                    {block.block_title && <p className="text-sm font-semibold text-gray-800">{block.block_title}</p>}
                                                    <div className="aspect-video w-full overflow-hidden rounded-lg bg-black">
                                                        <iframe
                                                            src={block.external_url}
                                                            className="h-full w-full"
                                                            allowFullScreen
                                                            title={block.block_title ?? 'Video'}
                                                            onLoad={() => logInteraction(trainingId, block.block_id, enrollmentId, 'open')}
                                                        />
                                                    </div>
                                                </>
                                            ) : (
                                                <>
                                                    {block.block_title && (
                                                        <p className={`font-semibold text-gray-800 ${block.block_type === 'title' ? 'text-base' : 'text-sm'}`}>
                                                            {block.block_title}
                                                        </p>
                                                    )}
                                                    {block.block_content && <p className="whitespace-pre-line text-sm text-gray-600">{block.block_content}</p>}
                                                    {block.external_url && (
                                                        <a href={block.external_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline">
                                                            {block.external_url}
                                                        </a>
                                                    )}
                                                </>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {quizzes.length > 0 && (
                <div>
                    <div className="mb-3 flex items-center gap-3">
                        <div className="h-6 w-1 rounded-full bg-blue-600" />
                        <h3 className="text-sm font-bold uppercase tracking-widest text-gray-700">Quizzes</h3>
                        <button
                            onClick={() => toggleDone('quizzes')}
                            className={`ml-auto inline-flex shrink-0 items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium ${
                                doneGroups.has('quizzes')
                                    ? 'border-green-200 bg-green-50 text-green-700'
                                    : 'border-gray-200 bg-white text-gray-400 hover:border-green-300 hover:bg-green-50 hover:text-green-700'
                            }`}
                        >
                            <CheckCircle className="h-3.5 w-3.5" />
                            {doneGroups.has('quizzes') ? 'Done' : 'Mark as Done'}
                        </button>
                    </div>
                    <div className="space-y-2">
                        {quizzes.map(a => <AssessmentRow key={a.assessment_id} a={a} />)}
                    </div>
                </div>
            )}

            {tasks.length > 0 && (
                <div>
                    <div className="mb-3 flex items-center gap-3">
                        <div className="h-6 w-1 rounded-full bg-blue-600" />
                        <h3 className="text-sm font-bold uppercase tracking-widest text-gray-700">Tasks</h3>
                        <button
                            onClick={() => toggleDone('tasks')}
                            className={`ml-auto inline-flex shrink-0 items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium ${
                                doneGroups.has('tasks')
                                    ? 'border-green-200 bg-green-50 text-green-700'
                                    : 'border-gray-200 bg-white text-gray-400 hover:border-green-300 hover:bg-green-50 hover:text-green-700'
                            }`}
                        >
                            <CheckCircle className="h-3.5 w-3.5" />
                            {doneGroups.has('tasks') ? 'Done' : 'Mark as Done'}
                        </button>
                    </div>
                    <div className="space-y-2">
                        {tasks.map(a => <AssessmentRow key={a.assessment_id} a={a} />)}
                    </div>
                </div>
            )}

            {viewingMaterial && (
                <DocumentViewerModal
                    material={viewingMaterial.material}
                    blockId={viewingMaterial.blockId}
                    trainingId={trainingId}
                    enrollmentId={enrollmentId}
                    onClose={() => setViewingMaterial(null)}
                />
            )}
        </div>
    );
}

function getFileTypeMeta(fileName: string | null) {
    const ext = (fileName ?? '').split('.').pop()?.toLowerCase() ?? '';
    switch (ext) {
        case 'pdf':
            return { Icon: FileText,       color: '#DC2626', bg: '#FEF2F2', border: '#FECACA', label: 'PDF',         btnClass: 'bg-red-600 hover:bg-red-700' };
        case 'pptx':
        case 'ppt':
            return { Icon: Presentation,   color: '#C43E1C', bg: '#FFF7F5', border: '#FED7CC', label: 'PowerPoint',  btnClass: 'bg-orange-600 hover:bg-orange-700' };
        case 'docx':
        case 'doc':
            return { Icon: FileText,       color: '#2B579A', bg: '#EFF6FF', border: '#BFDBFE', label: 'Word',        btnClass: 'bg-blue-700 hover:bg-blue-800' };
        case 'xlsx':
        case 'xls':
            return { Icon: FileSpreadsheet, color: '#217346', bg: '#F0FDF4', border: '#BBF7D0', label: 'Excel',       btnClass: 'bg-green-700 hover:bg-green-800' };
        default:
            return { Icon: FileText,       color: '#6B7280', bg: '#F9FAFB', border: '#E5E7EB', label: 'File',        btnClass: 'bg-gray-600 hover:bg-gray-700' };
    }
}

function DocxViewer({ url }: { url: string }) {
    const [html, setHtml] = useState<string | null>(null);
    const [error, setError] = useState(false);

    useEffect(() => {
        import('mammoth').then(async (mammoth) => {
            try {
                const res = await fetch(url);
                const buf = await res.arrayBuffer();
                const result = await mammoth.convertToHtml({ arrayBuffer: buf });
                setHtml(result.value || '<p style="color:#9ca3af">No content found.</p>');
            } catch {
                setError(true);
            }
        });
    }, [url]);

    if (error) return <p className="p-8 text-sm text-red-500">Failed to render document.</p>;
    if (!html)  return <p className="p-8 text-sm text-gray-400">Loading document…</p>;

    return (
        <div
            className="prose prose-sm max-w-none p-8 text-gray-800"
            style={{ fontFamily: 'Calibri, Georgia, serif', lineHeight: 1.7 }}
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}

function XlsxViewer({ url }: { url: string }) {
    const [tableHtml, setTableHtml] = useState<string | null>(null);
    const [error, setError] = useState(false);

    useEffect(() => {
        import('xlsx').then(async (XLSX) => {
            try {
                const res  = await fetch(url);
                const buf  = await res.arrayBuffer();
                const wb   = XLSX.read(buf, { type: 'array' });
                const ws   = wb.Sheets[wb.SheetNames[0]];
                const html = XLSX.utils.sheet_to_html(ws, { header: '', footer: '' });
                setTableHtml(html);
            } catch {
                setError(true);
            }
        });
    }, [url]);

    if (error)     return <p className="p-8 text-sm text-red-500">Failed to render spreadsheet.</p>;
    if (!tableHtml) return <p className="p-8 text-sm text-gray-400">Loading spreadsheet…</p>;

    return (
        <div className="overflow-auto p-4">
            <style>{`
                .xlsx-table table { border-collapse: collapse; width: 100%; font-size: 13px; font-family: Calibri, Arial, sans-serif; }
                .xlsx-table td, .xlsx-table th { border: 1px solid #d1d5db; padding: 6px 10px; white-space: nowrap; }
                .xlsx-table tr:first-child td { background: #f0fdf4; font-weight: 600; color: #166534; }
                .xlsx-table tr:nth-child(even) td { background: #f9fafb; }
            `}</style>
            <div className="xlsx-table" dangerouslySetInnerHTML={{ __html: tableHtml }} />
        </div>
    );
}

function PptxViewer({ url }: { url: string }) {
    const [slides, setSlides] = useState<{ title: string; bullets: string[] }[]>([]);
    const [error, setError]   = useState(false);

    useEffect(() => {
        import('jszip').then(async ({ default: JSZip }) => {
            try {
                const res  = await fetch(url);
                const buf  = await res.arrayBuffer();
                const zip  = await JSZip.loadAsync(buf);

                const slideKeys = Object.keys(zip.files)
                    .filter(k => /^ppt\/slides\/slide\d+\.xml$/.test(k))
                    .sort((a, b) => {
                        const na = parseInt(a.match(/(\d+)/)?.[1] ?? '0');
                        const nb = parseInt(b.match(/(\d+)/)?.[1] ?? '0');
                        return na - nb;
                    });

                const parsed = await Promise.all(slideKeys.map(async (key) => {
                    const xml  = await zip.files[key].async('text');
                    const texts = [...xml.matchAll(/<a:t[^>]*>([^<]+)<\/a:t>/g)]
                        .map(m => m[1].trim()).filter(Boolean);

                    // First non-empty text is treated as title, rest as bullets
                    const [title = '', ...bullets] = texts;
                    return { title, bullets };
                }));

                setSlides(parsed.filter(s => s.title || s.bullets.length));
            } catch {
                setError(true);
            }
        });
    }, [url]);

    if (error)         return <p className="p-8 text-sm text-red-500">Failed to render presentation.</p>;
    if (!slides.length) return <p className="p-8 text-sm text-gray-400">Loading presentation…</p>;

    return (
        <div className="space-y-4 bg-gray-100 p-6 min-h-full">
            {slides.map((slide, i) => (
                <div
                    key={i}
                    className="relative rounded-xl border border-orange-100 bg-white shadow-sm overflow-hidden"
                    style={{ minHeight: '180px' }}
                >
                    {/* Slide accent bar */}
                    <div className="h-1.5 w-full" style={{ backgroundColor: '#C43E1C' }} />
                    <div className="p-6">
                        <p className="text-xs font-semibold uppercase tracking-widest mb-3" style={{ color: '#C43E1C' }}>
                            Slide {i + 1}
                        </p>
                        {slide.title && (
                            <h3 className="text-lg font-bold text-gray-900 mb-3 leading-snug">
                                {slide.title}
                            </h3>
                        )}
                        {slide.bullets.length > 0 && (
                            <ul className="space-y-1.5">
                                {slide.bullets.map((b, j) => (
                                    <li key={j} className="flex items-start gap-2 text-sm text-gray-600">
                                        <span className="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full" style={{ backgroundColor: '#C43E1C' }} />
                                        {b}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}

function DocumentViewerModal({
    material,
    blockId,
    trainingId,
    enrollmentId,
    onClose,
}: {
    material: LearningMaterialItem;
    blockId?: number;
    trainingId?: string;
    enrollmentId?: number | null;
    onClose: () => void;
}) {
    const viewUrl     = route('learning-materials.show', material.material_id);
    const downloadUrl = route('learning-materials.show', material.material_id) + '?dl=1';
    const openedAt    = useRef<number>(Date.now());
    const { Icon, color, bg, border, label, btnClass } = getFileTypeMeta(material.original_file_name);
    const ext = (material.original_file_name ?? '').split('.').pop()?.toLowerCase() ?? '';

    useEffect(() => {
        if (blockId && trainingId) logInteraction(trainingId, blockId, enrollmentId ?? null, 'open');
        return () => {
            if (blockId && trainingId) {
                const seconds = Math.round((Date.now() - openedAt.current) / 1000);
                logInteraction(trainingId, blockId, enrollmentId ?? null, 'close', seconds);
            }
        };
    }, []);

    function handleDownload() {
        if (blockId && trainingId) logInteraction(trainingId, blockId, enrollmentId ?? null, 'download');
    }

    function renderBody() {
        if (ext === 'pdf')              return <iframe src={viewUrl} className="h-[80vh] w-full" title={material.original_file_name ?? 'PDF'} />;
        if (ext === 'docx' || ext === 'doc')  return <div className="h-[80vh] overflow-auto bg-white"><DocxViewer url={downloadUrl} /></div>;
        if (ext === 'xlsx' || ext === 'xls')  return <div className="h-[80vh] overflow-auto bg-gray-50"><XlsxViewer url={downloadUrl} /></div>;
        if (ext === 'pptx' || ext === 'ppt')  return <div className="h-[80vh] overflow-auto"><PptxViewer url={downloadUrl} /></div>;
        return <iframe src={viewUrl} className="h-[80vh] w-full" title={material.original_file_name ?? 'File'} />;
    }

    return (
        <div
            className="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
            onClick={e => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div className="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                {/* Coloured top bar */}
                <div className="h-1 w-full flex-shrink-0" style={{ backgroundColor: color }} />

                {/* Header */}
                <div className="flex items-center justify-between border-b px-5 py-3" style={{ backgroundColor: bg, borderColor: border }}>
                    <div className="flex min-w-0 items-center gap-2.5">
                        <span className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg" style={{ backgroundColor: color + '20' }}>
                            <Icon className="h-4 w-4" style={{ color }} />
                        </span>
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-gray-900">
                                {material.original_file_name ?? 'Document'}
                            </p>
                            <p className="text-xs" style={{ color }}>{label} Document</p>
                        </div>
                    </div>
                    <div className="ml-4 flex flex-shrink-0 items-center gap-2">
                        <a
                            href={downloadUrl}
                            onClick={handleDownload}
                            className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white ${btnClass}`}
                        >
                            <Download className="h-3.5 w-3.5" /> Download
                        </a>
                        <button
                            onClick={onClose}
                            className="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 hover:bg-gray-100"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                </div>

                {/* Body */}
                <div className="flex-1 overflow-auto">
                    {renderBody()}
                </div>
            </div>
        </div>
    );
}

function MaterialRow({ m, onView }: { m: LearningMaterialItem; onView: (m: LearningMaterialItem) => void }) {
    const sessionInfo = (m.session_start_time || m.session_end_time)
        ? `Class session · ${formatTime(m.session_start_time)} – ${formatTime(m.session_end_time)}`
        : null;
    const { Icon, color, bg, border, label } = getFileTypeMeta(m.original_file_name);

    return (
        <button
            onClick={() => onView(m)}
            className="flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left transition-colors hover:brightness-95"
            style={{ backgroundColor: bg, borderColor: border }}
        >
            <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style={{ backgroundColor: color + '20' }}>
                <Icon className="h-4.5 w-4.5" style={{ color }} />
            </span>
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-gray-900">{m.original_file_name ?? 'Material'}</p>
                <p className="mt-0.5 text-xs" style={{ color }}>
                    {label}
                    {sessionInfo && <span className="text-gray-400"> · {sessionInfo}</span>}
                </p>
                {m.trainer && (
                    <p className="text-xs text-gray-400">Trainer: {m.trainer}</p>
                )}
            </div>
            <Download className="h-4 w-4 flex-shrink-0 text-gray-400" />
        </button>
    );
}

function LearningMaterialList({ materials }: { materials: LearningMaterialItem[] }) {
    const [viewing, setViewing] = useState<LearningMaterialItem | null>(null);

    if (!materials || materials.length === 0) {
        return (
            <div className="flex flex-col items-center gap-2 py-10 text-gray-400">
                <BookOpen className="h-12 w-12 text-gray-200" />
                <p className="text-sm">No learning materials available yet.</p>
            </div>
        );
    }

    // Group by session_date; null session_date → "General"
    const groups = materials.reduce<{ date: string | null; label: string; items: LearningMaterialItem[] }[]>(
        (acc, m) => {
            const key = m.session_date ?? null;
            const existing = acc.find(g => g.date === key);
            if (existing) {
                existing.items.push(m);
            } else {
                const label = key
                    ? new Date(key).toLocaleDateString('en-MY', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })
                    : 'General';
                acc.push({ date: key, label, items: [m] });
            }
            return acc;
        },
        [],
    );

    return (
        <>
            <div className="space-y-5">
                {groups.map(group => (
                    <div key={group.date ?? '__general'}>
                        <div className="mb-2 flex items-center gap-3">
                            <div className="h-5 w-1 rounded-full bg-blue-500" />
                            <h4 className="text-xs font-bold uppercase tracking-widest text-gray-500">{group.label}</h4>
                        </div>
                        <div className="space-y-1.5">
                            {group.items.map(m => (
                                <MaterialRow key={m.material_id} m={m} onView={setViewing} />
                            ))}
                        </div>
                    </div>
                ))}
            </div>

            {viewing && <DocumentViewerModal material={viewing} onClose={() => setViewing(null)} />}
        </>
    );
}

function InfoRow({ icon, label, children }: { icon: React.ReactNode; label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-start gap-2">
            {icon && <span className="mt-0.5 shrink-0 text-gray-400">{icon}</span>}
            <div>
                <span className="text-xs font-medium text-gray-400">{label}</span>
                <p className="text-sm text-gray-800">{children}</p>
            </div>
        </div>
    );
}
