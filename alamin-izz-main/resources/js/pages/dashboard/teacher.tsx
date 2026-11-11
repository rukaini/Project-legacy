import AppLayout from '@/layouts/app-layout';
import { formatMode } from '@/lib/utils';
import { type PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Bell, GraduationCap, TrendingUp, Clock, HeartHandshake } from 'lucide-react';

interface UpcomingItem {
    type: 'training' | 'penyelaras_ladap' | 'tarbiah';
    id: string | number;
    title: string;
    mode: string | null;
    organiser: string | null;
    location: string | null;
    date: string | null;
}

const UPCOMING_TYPE_LABEL: Record<UpcomingItem['type'], string> = {
    training: 'Training',
    penyelaras_ladap: 'LADAP',
    tarbiah: 'Tarbiah',
};

const UPCOMING_TYPE_PILL: Record<UpcomingItem['type'], string> = {
    training: 'bg-blue-100 text-blue-700',
    penyelaras_ladap: 'bg-purple-100 text-purple-700',
    tarbiah: 'bg-rose-100 text-rose-700',
};

function fmtDate(d: string) {
    return new Date(d + 'T00:00:00').toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

interface Props extends PageProps {
    display_name: string;
    upcoming: UpcomingItem[];
    pending_applications: number;
    total_ladap_hours: number;
    total_tarbiah_hours: number;
    completed_count: number;
    unread_notifications: number;
    school_name: string | null;
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

export default function TeacherDashboard({
    display_name,
    upcoming,
    pending_applications,
    total_ladap_hours,
    total_tarbiah_hours,
    completed_count,
    unread_notifications,
    school_name,
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
                            Your Training Overview
                            {upcoming.length > 0 && (
                                <span className="ml-1 font-medium text-blue-600">
                                    • {upcoming.length} upcoming
                                </span>
                            )}
                        </p>
                    </div>
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                        <GraduationCap className="h-5 w-5 text-red-500" />
                    </div>
                </div>

                {/* Stat cards */}
                <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <StatCard
                        icon={<GraduationCap className="h-5 w-5 text-blue-600" />}
                        value={upcoming.filter((u) => u.type === 'training').length}
                        label="My Trainings"
                        subtitle={`${completed_count} completed`}
                        colorClass="bg-blue-50"
                        iconColorClass="bg-blue-100"
                    />
                    <StatCard
                        icon={<Clock className="h-5 w-5 text-amber-600" />}
                        value={pending_applications}
                        label="Pending Applications"
                        subtitle="Waiting for approval"
                        colorClass="bg-amber-50"
                        iconColorClass="bg-amber-100"
                        hasDot={pending_applications > 0}
                    />
                    <StatCard
                        icon={<TrendingUp className="h-5 w-5 text-green-600" />}
                        value={Number(total_ladap_hours).toFixed(2)}
                        label="Training, LADAP & Certificate Hours"
                        subtitle={`${completed_count} completed trainings`}
                        colorClass="bg-green-50"
                        iconColorClass="bg-green-100"
                    />
                    <StatCard
                        icon={<HeartHandshake className="h-5 w-5 text-rose-600" />}
                        value={Number(total_tarbiah_hours).toFixed(2)}
                        label="Tarbiah Hours"
                        subtitle="10 hrs per Tarbiah attended"
                        colorClass="bg-rose-50"
                        iconColorClass="bg-rose-100"
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

                {/* Upcoming */}
                <div className="rounded-xl border border-gray-200 bg-white">
                    <div className="border-b border-gray-100 px-6 py-4">
                        <h2 className="font-semibold text-gray-900">Upcoming</h2>
                    </div>
                    {upcoming.length === 0 ? (
                        <p className="px-6 py-8 text-center text-sm text-gray-500">
                            Nothing upcoming.{' '}
                            <Link href={route('trainings.index')} className="text-blue-600 hover:underline">
                                Browse trainings
                            </Link>
                        </p>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {upcoming.map((item) => (
                                <li key={`${item.type}-${item.id}`} className="flex items-center justify-between px-6 py-4">
                                    <div className="flex items-center gap-3">
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${UPCOMING_TYPE_PILL[item.type]}`}>
                                            {UPCOMING_TYPE_LABEL[item.type]}
                                        </span>
                                        <div>
                                            <p className="text-sm font-medium text-gray-800">{item.title}</p>
                                            <p className="text-xs text-gray-500">
                                                {item.type === 'training'
                                                    ? [formatMode(item.mode), item.organiser].filter(Boolean).join(' · ')
                                                    : [item.location, item.date ? fmtDate(item.date) : null].filter(Boolean).join(' · ')}
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                    <div className="flex items-center gap-4 border-t border-gray-100 px-6 py-3">
                        <Link
                            href={route('trainings.index')}
                            className="text-sm font-medium text-blue-600 hover:text-blue-700"
                        >
                            Browse all trainings →
                        </Link>
                        <Link
                            href={route('penyelaras-ladap.index')}
                            className="text-sm font-medium text-purple-600 hover:text-purple-700"
                        >
                            LADAP →
                        </Link>
                        <Link
                            href={route('tarbiah.index')}
                            className="text-sm font-medium text-rose-600 hover:text-rose-700"
                        >
                            Tarbiah →
                        </Link>
                        <Link
                            href={route('certificates.index')}
                            className="text-sm font-medium text-emerald-600 hover:text-emerald-700"
                        >
                            Certificate →
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
