import AppLayout from '@/layouts/app-layout';
import { formatMode } from '@/lib/utils';
import { type PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, Bell, BookOpen, CheckCircle, ClipboardList, GraduationCap, History, User, XCircle } from 'lucide-react';

interface ApplicantProfile {
    id: string;
    full_name: string;
    email: string;
    phone_number: string;
}

interface RecentApplication {
    courseID: string;
    courseName: string;
    category: string | null;
    status: 'pending' | 'approved' | 'rejected';
    applied_at: string;
}

interface OpenCourse {
    id: string;
    name: string;
    category: string | null;
    mode: string | null;
    organiser: string;
    price: number;
}

interface Props extends PageProps {
    applicant: ApplicantProfile | null;
    series_count: number;
    pending_count: number;
    approved_count: number;
    open_courses: OpenCourse[];
    recent_applications: RecentApplication[];
    unread_notifications: number;
}

function StatCard({
    icon,
    value,
    label,
    subtitle,
    colorClass,
    iconColorClass,
    hasDot,
}: {
    icon: React.ReactNode;
    value: number | string;
    label: string;
    subtitle: string;
    colorClass: string;
    iconColorClass: string;
    hasDot?: boolean;
}) {
    return (
        <div className={`relative rounded-xl p-5 ${colorClass}`}>
            {hasDot && (
                <span className="absolute right-4 top-4 flex h-3 w-3">
                    <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75" />
                    <span className="relative inline-flex h-3 w-3 rounded-full bg-amber-500" />
                </span>
            )}
            <div className={`mb-3 w-fit rounded-full p-2 ${iconColorClass}`}>{icon}</div>
            <p className="text-3xl font-bold text-gray-900">{value}</p>
            <p className="mt-0.5 text-sm font-medium text-gray-700">{label}</p>
            <p className="mt-0.5 text-xs text-gray-500">{subtitle}</p>
        </div>
    );
}

const APP_STATUS: Record<string, { label: string; icon: typeof CheckCircle; className: string }> = {
    pending:       { label: 'Pending',  icon: AlertTriangle, className: 'text-amber-600 bg-amber-50 border-amber-200' },
    approved:      { label: 'Approved', icon: CheckCircle,   className: 'text-green-600 bg-green-50 border-green-200' },
    rejected:      { label: 'Rejected', icon: XCircle,       className: 'text-red-500 bg-red-50 border-red-200' },
    auto_enrolled: { label: 'Enrolled', icon: CheckCircle,   className: 'text-blue-600 bg-blue-50 border-blue-200' },
};

export default function GuruBaruDashboard({
    applicant,
    series_count,
    pending_count,
    approved_count,
    open_courses,
    recent_applications,
    unread_notifications,
}: Props) {
    const { auth } = usePage<PageProps>().props;

    return (
        <AppLayout>
            <Head title="Dashboard — Guru Baru" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">

                {/* Header */}
                <div className="mb-8 flex items-start justify-between">
                    <div>
                        <div className="mb-1 inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            <GraduationCap className="h-3.5 w-3.5" />
                            Guru Baru
                        </div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            Welcome, {applicant?.full_name ?? auth.user?.name}!
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Manage your training journey and track your progress
                        </p>
                    </div>
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                        <GraduationCap className="h-5 w-5 text-blue-600" />
                    </div>
                </div>

                {/* Stat cards */}
                <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <StatCard
                        icon={<BookOpen className="h-5 w-5 text-blue-600" />}
                        value={series_count}
                        label="Series Trainings"
                        subtitle="Auto-enrolled"
                        colorClass="bg-blue-50"
                        iconColorClass="bg-blue-100"
                    />
                    <StatCard
                        icon={<ClipboardList className="h-5 w-5 text-amber-600" />}
                        value={pending_count}
                        label="Pending"
                        subtitle="Applications awaiting"
                        colorClass="bg-amber-50"
                        iconColorClass="bg-amber-100"
                        hasDot={pending_count > 0}
                    />
                    <StatCard
                        icon={<CheckCircle className="h-5 w-5 text-green-600" />}
                        value={approved_count}
                        label="Approved"
                        subtitle="Single / multi-day"
                        colorClass="bg-green-50"
                        iconColorClass="bg-green-100"
                    />
                    <StatCard
                        icon={<Bell className="h-5 w-5 text-blue-600" />}
                        value={unread_notifications}
                        label="Notifications"
                        subtitle="Unread messages"
                        colorClass="bg-blue-50"
                        iconColorClass="bg-blue-100"
                        hasDot={unread_notifications > 0}
                    />
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    {/* Left column */}
                    <div className="space-y-6 lg:col-span-2">

                        {/* Recent Applications */}
                        <div className="rounded-xl border border-gray-200 bg-white">
                            <div className="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                <h2 className="font-semibold text-gray-900">My Applications</h2>
                                <Link
                                    href={route('guru_baru.applications.mine')}
                                    className="text-xs font-medium text-blue-600 hover:text-blue-700"
                                >
                                    View all →
                                </Link>
                            </div>
                            {recent_applications.length === 0 ? (
                                <div className="px-6 py-8 text-center">
                                    <ClipboardList className="mx-auto mb-2 h-8 w-8 text-gray-200" />
                                    <p className="text-sm text-gray-400">No applications yet.</p>
                                    <Link
                                        href={route('trainings.index')}
                                        className="mt-3 inline-block text-xs font-medium text-blue-600 hover:underline"
                                    >
                                        Browse trainings →
                                    </Link>
                                </div>
                            ) : (
                                <ul className="divide-y divide-gray-100">
                                    {recent_applications.map((app) => {
                                        const conf = APP_STATUS[app.status] ?? APP_STATUS.pending;
                                        const Icon = conf.icon;
                                        return (
                                            <li key={app.courseID} className="flex items-center justify-between px-6 py-3">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium text-gray-900">{app.courseName}</p>
                                                    <p className="text-xs text-gray-400">
                                                        {new Date(app.applied_at).toLocaleDateString('en-MY', {
                                                            day: '2-digit', month: 'short', year: 'numeric',
                                                        })}
                                                    </p>
                                                </div>
                                                <span className={`ml-4 inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-semibold ${conf.className}`}>
                                                    <Icon className="h-3 w-3" />
                                                    {conf.label}
                                                </span>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </div>

                        {/* Available Courses */}
                        <div className="rounded-xl border border-gray-200 bg-white">
                            <div className="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                <h2 className="font-semibold text-gray-900">Open Trainings</h2>
                                <span className="text-xs text-gray-400">Single & Multi-day</span>
                            </div>
                            {open_courses.length === 0 ? (
                                <p className="px-6 py-8 text-center text-sm text-gray-400">
                                    No open trainings at the moment.
                                </p>
                            ) : (
                                <ul className="divide-y divide-gray-100">
                                    {open_courses.map((course) => (
                                        <li key={course.id} className="px-6 py-4">
                                            <div className="flex items-center justify-between gap-4">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-semibold text-gray-900">{course.name}</p>
                                                    <p className="mt-0.5 text-xs text-gray-400">
                                                        {course.category?.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}
                                                        {course.mode ? ` · ${formatMode(course.mode)}` : ''}
                                                    </p>
                                                </div>
                                                <Link
                                                    href={route('trainings.show', course.id)}
                                                    className="shrink-0 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                                >
                                                    View
                                                </Link>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <div className="border-t border-gray-100 px-6 py-3">
                                <Link
                                    href={route('trainings.index')}
                                    className="text-sm font-medium text-blue-600 hover:text-blue-700"
                                >
                                    Browse all trainings →
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Right column: Profile + Quick Links */}
                    <div className="space-y-6">

                        {/* Profile */}
                        <div className="rounded-xl border border-gray-200 bg-white">
                            <div className="border-b border-gray-100 px-6 py-4">
                                <h2 className="font-semibold text-gray-900">My Profile</h2>
                            </div>
                            <div className="px-6 py-5">
                                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                    <User className="h-6 w-6 text-blue-600" />
                                </div>
                                {applicant ? (
                                    <dl className="space-y-3">
                                        <div>
                                            <dt className="text-xs font-medium text-gray-500">Full Name</dt>
                                            <dd className="mt-0.5 text-sm font-medium text-gray-900">{applicant.full_name}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs font-medium text-gray-500">Email</dt>
                                            <dd className="mt-0.5 text-sm text-gray-700">{applicant.email}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs font-medium text-gray-500">Phone</dt>
                                            <dd className="mt-0.5 text-sm text-gray-700">{applicant.phone_number ?? '—'}</dd>
                                        </div>
                                    </dl>
                                ) : (
                                    <p className="text-sm text-gray-400">No profile found.</p>
                                )}
                            </div>
                            <div className="border-t border-gray-100 px-6 py-3">
                                <Link
                                    href={route('profile.edit')}
                                    className="text-sm font-medium text-blue-600 hover:text-blue-700"
                                >
                                    Edit profile →
                                </Link>
                            </div>
                        </div>

                        {/* Quick Links */}
                        <div className="rounded-xl border border-gray-200 bg-white">
                            <div className="border-b border-gray-100 px-6 py-4">
                                <h2 className="font-semibold text-gray-900">Quick Links</h2>
                            </div>
                            <div className="divide-y divide-gray-100">
                                <Link href={route('trainings.index')} className="flex items-center gap-3 px-6 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <BookOpen className="h-4 w-4 text-blue-500" /> Browse All Trainings
                                </Link>
                                <Link href={route('guru_baru.courses.mine')} className="flex items-center gap-3 px-6 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <GraduationCap className="h-4 w-4 text-blue-500" /> My Trainings
                                </Link>
                                <Link href={route('guru_baru.applications.mine')} className="flex items-center gap-3 px-6 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <ClipboardList className="h-4 w-4 text-amber-500" /> My Applications
                                </Link>
                                <Link href={route('history.mine')} className="flex items-center gap-3 px-6 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <History className="h-4 w-4 text-blue-500" /> My Training History
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
