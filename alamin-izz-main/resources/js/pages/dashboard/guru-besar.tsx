import AppLayout from '@/layouts/app-layout';
import { formatMode } from '@/lib/utils';
import { type PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { BookOpen, Calendar, ChevronRight, GraduationCap, TrendingUp, Users } from 'lucide-react';

interface RecentApplication {
    id: string;
    applicant: string;
    training: string;
    applied_at: string;
}

interface UpcomingTraining {
    id: string;
    title: string;
    mode: string | null;
    capacity: number;
}

interface TeacherAttendance {
    id: number;
    name: string;
    email: string;
    ic_number: string | null;
    total_trainings: number;
    total_sessions: number;
    attended: number;
    ladap_hours: number;
    latest_attendance_at: string | null;
    attendance_rate: number | null;
}

interface Props extends PageProps {
    display_name: string;
    school_name: string | null;
    active_trainings: number;
    pending_applications: number;
    completed_trainings_this_month: number;
    total_teachers: number;
    recent_applications: RecentApplication[];
    upcoming_trainings: UpcomingTraining[];
    teacher_attendance: TeacherAttendance[];
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

function attendanceColor(rate: number | null): string {
    if (rate === null) return 'text-gray-400';
    if (rate >= 80) return 'text-green-600';
    if (rate >= 50) return 'text-amber-600';
    return 'text-red-600';
}

function attendanceBadge(rate: number | null): string {
    if (rate === null) return 'bg-gray-100 text-gray-500';
    if (rate >= 80) return 'bg-green-100 text-green-700';
    if (rate >= 50) return 'bg-amber-100 text-amber-700';
    return 'bg-red-100 text-red-700';
}

export default function GuruBesarDashboard({
    display_name,
    school_name,
    active_trainings,
    pending_applications,
    completed_trainings_this_month,
    total_teachers,
    recent_applications,
    upcoming_trainings,
    teacher_attendance,
}: Props) {
    const { auth } = usePage<PageProps>().props;

    return (
        <AppLayout>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">

                {/* Header */}
                <div className="mb-8 flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            Welcome back, {display_name}!
                        </h1>
                        {school_name && (
                            <p className="mt-0.5 text-sm font-medium text-blue-700">{school_name}</p>
                        )}
                        <p className="mt-1 text-sm text-gray-500">
                            Management Overview
                            {pending_applications > 0 && (
                                <span className="ml-1 font-medium text-amber-600">
                                    • {pending_applications} applications need approval
                                </span>
                            )}
                        </p>
                    </div>
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                        <GraduationCap className="h-5 w-5 text-red-500" />
                    </div>
                </div>

                {/* Stat cards */}
                <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        icon={<GraduationCap className="h-5 w-5 text-blue-600" />}
                        value={active_trainings}
                        label="Active Trainings"
                        subtitle={`${completed_trainings_this_month} completed this month`}
                        colorClass="bg-blue-50"
                        iconColorClass="bg-blue-100"
                    />
                    <StatCard
                        icon={<BookOpen className="h-5 w-5 text-amber-600" />}
                        value={pending_applications}
                        label="Applications"
                        subtitle="Need your approval"
                        colorClass="bg-amber-50"
                        iconColorClass="bg-amber-100"
                        hasDot={pending_applications > 0}
                    />
                    <StatCard
                        icon={<Users className="h-5 w-5 text-green-600" />}
                        value={total_teachers}
                        label="Total Teachers"
                        subtitle="Registered in system"
                        colorClass="bg-green-50"
                        iconColorClass="bg-green-100"
                    />
                    <StatCard
                        icon={<TrendingUp className="h-5 w-5 text-blue-600" />}
                        value={completed_trainings_this_month}
                        label="Completed This Month"
                        subtitle="Training programs done"
                        colorClass="bg-blue-50"
                        iconColorClass="bg-blue-100"
                    />
                </div>

                {/* Two-column row: Pending Applications + Upcoming Trainings */}
                <div className="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

                    {/* Recent Pending Applications */}
                    <div className="rounded-xl border border-gray-200 bg-white">
                        <div className="border-b border-gray-100 px-6 py-4">
                            <h2 className="font-semibold text-gray-900">Recent Pending Applications</h2>
                        </div>
                        {recent_applications.length === 0 ? (
                            <p className="px-6 py-8 text-center text-sm text-gray-500">No pending applications.</p>
                        ) : (
                            <ul className="divide-y divide-gray-100">
                                {recent_applications.map((app) => (
                                    <li key={app.id} className="flex items-center justify-between px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                                Pending
                                            </span>
                                            <span className="text-sm font-medium text-gray-800">
                                                {app.applicant} — {app.training}
                                            </span>
                                        </div>
                                        <span className="text-xs text-gray-400">
                                            {new Date(app.applied_at).toLocaleDateString('en-MY', {
                                                day: '2-digit', month: 'short', year: 'numeric',
                                            })}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <div className="border-t border-gray-100 px-6 py-3">
                            <Link
                                href={route('trainings.index')}
                                className="text-sm font-medium text-blue-600 hover:text-blue-700"
                            >
                                View all trainings →
                            </Link>
                        </div>
                    </div>

                    {/* Upcoming Trainings */}
                    <div className="rounded-xl border border-gray-200 bg-white">
                        <div className="border-b border-gray-100 px-6 py-4">
                            <h2 className="font-semibold text-gray-900">Upcoming Trainings</h2>
                        </div>
                        {upcoming_trainings.length === 0 ? (
                            <p className="px-6 py-8 text-center text-sm text-gray-500">No upcoming trainings.</p>
                        ) : (
                            <ul className="divide-y divide-gray-100">
                                {upcoming_trainings.map((t) => (
                                    <li key={t.id} className="flex items-start gap-3 px-6 py-4">
                                        <Calendar className="mt-0.5 h-4 w-4 shrink-0 text-blue-400" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-800">{t.title}</p>
                                            <p className="text-xs text-gray-500">
                                                {formatMode(t.mode) ?? '—'} · {t.capacity} seats
                                            </p>
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
                                View all trainings →
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Teacher Attendance Statistics */}
                <div className="rounded-xl border border-gray-200 bg-white">
                    <div className="border-b border-gray-100 px-6 py-4">
                        <h2 className="font-semibold text-gray-900">Teacher Attendance Statistics</h2>
                        <p className="mt-0.5 text-xs text-gray-500">Based on approved training enrolments</p>
                    </div>
                    {teacher_attendance.length === 0 ? (
                        <p className="px-6 py-8 text-center text-sm text-gray-500">No teacher records found.</p>
                    ) : (
                        <>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-100 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        <th className="px-6 py-3">Teacher</th>
                                        <th className="px-6 py-3">IC Number</th>
                                        <th className="px-6 py-3 text-center">Trainings</th>
                                        <th className="px-6 py-3 text-center">Days Attended</th>
                                        <th className="px-6 py-3 text-right">LADAP Hrs</th>
                                        <th className="px-6 py-3 text-center">Attendance Rate</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {teacher_attendance.map((t) => (
                                        <tr key={t.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-3">
                                                <p className="font-medium text-gray-800">{t.name}</p>
                                                <p className="text-xs text-gray-400">{t.email}</p>
                                            </td>
                                            <td className="px-6 py-3 font-mono text-sm text-gray-700">
                                                {t.ic_number ?? <span className="text-gray-400">—</span>}
                                            </td>
                                            <td className="px-6 py-3 text-center text-gray-700">{t.total_trainings}</td>
                                            <td className="px-6 py-3 text-center text-gray-700">
                                                {t.attended} / {t.total_sessions}
                                            </td>
                                            <td className="px-6 py-3 text-right font-semibold text-blue-700">
                                                {t.ladap_hours.toFixed(2)}
                                            </td>
                                            <td className="px-6 py-3 text-center">
                                                {t.attendance_rate === null ? (
                                                    <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-400">
                                                        N/A
                                                    </span>
                                                ) : (
                                                    <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${attendanceBadge(t.attendance_rate)}`}>
                                                        {t.attendance_rate}%
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="border-t border-gray-100 px-6 py-3">
                            <Link
                                href={route('reports.development')}
                                className="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700"
                            >
                                Show more <ChevronRight className="h-4 w-4" />
                            </Link>
                        </div>
                        </>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
