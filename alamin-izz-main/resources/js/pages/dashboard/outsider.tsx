import AppLayout from '@/layouts/app-layout';
import { formatMode } from '@/lib/utils';
import { type PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Bell, CheckCircle, Clock, GraduationCap } from 'lucide-react';

interface UpcomingTraining {
    id: string;
    title: string;
    mode: string | null;
    organiser: string;
    status: string;
}

interface Props extends PageProps {
    registered_trainings: UpcomingTraining[];
    pending_applications: number;
    training_history: number;
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

export default function OutsiderDashboard({
    registered_trainings,
    pending_applications,
    training_history,
    unread_notifications,
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
                            Welcome back, {auth.user?.name}!
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Your Training Overview
                            {registered_trainings.length > 0 && (
                                <span className="ml-1 font-medium text-blue-600">
                                    • {registered_trainings.length} upcoming
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
                        value={registered_trainings.length}
                        label="My Trainings"
                        subtitle={`${training_history} completed`}
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
                        icon={<CheckCircle className="h-5 w-5 text-green-600" />}
                        value={training_history}
                        label="Completed"
                        subtitle="Trainings attended"
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

                {/* Upcoming Trainings */}
                <div className="rounded-xl border border-gray-200 bg-white">
                    <div className="border-b border-gray-100 px-6 py-4">
                        <h2 className="font-semibold text-gray-900">Upcoming Training</h2>
                    </div>
                    {registered_trainings.length === 0 ? (
                        <p className="px-6 py-8 text-center text-sm text-gray-500">
                            No upcoming trainings.{' '}
                            <Link href={route('trainings.index')} className="text-blue-600 hover:underline">
                                Browse trainings
                            </Link>
                        </p>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {registered_trainings.map((training) => (
                                <li key={training.id} className="flex items-center justify-between px-6 py-4">
                                    <div className="flex items-center gap-3">
                                        <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                                            Training
                                        </span>
                                        <div>
                                            <p className="text-sm font-medium text-gray-800">{training.title}</p>
                                            <p className="text-xs text-gray-500">
                                                {[formatMode(training.mode), training.organiser].filter(Boolean).join(' · ')}
                                            </p>
                                        </div>
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
        </AppLayout>
    );
}
