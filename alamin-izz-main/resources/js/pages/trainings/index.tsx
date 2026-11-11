import AppLayout from '@/layouts/app-layout';
import { useRole } from '@/lib/auth';
import { formatMode } from '@/lib/utils';
import { type PageProps, type Training } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, ChevronDown, Clock, GraduationCap, Monitor, Search, Tag, Users } from 'lucide-react';
import { useState } from 'react';

const LADAP_TARGET = 30;

interface Props extends PageProps {
    trainings: Training[];
    user_applications: Record<string, 'pending' | 'approved' | 'rejected' | 'enrolled'>;
    filters: { search?: string; status?: string };
    total_ladap_hours?: number;
    estimated_ladap_hours?: number;
}

function LadapStats({ total, estimatedHours }: { total: number; estimatedHours: number }) {
    const [open, setOpen] = useState(false);

    const pct       = Math.min(100, Math.round((total / LADAP_TARGET) * 100));
    const remaining = Math.max(0, LADAP_TARGET - total);
    const fmt       = (n: number) => n.toFixed(2);

    const barColor =
        pct >= 100 ? 'bg-green-500' :
        pct >= 50  ? 'bg-amber-400' :
                     'bg-blue-500';

    const valueColor =
        pct >= 100 ? 'text-green-600' :
        pct >= 50  ? 'text-amber-500' :
                     'text-blue-600';

    return (
        <div className="mb-6 rounded-xl border border-gray-200 bg-white overflow-hidden">
            {/* Collapsed header — always visible */}
            <button
                onClick={() => setOpen(o => !o)}
                className="w-full flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition-colors"
            >
                <div className="flex items-center gap-2 shrink-0">
                    <Clock className="h-4 w-4 text-gray-400" />
                    <span className="text-sm font-semibold text-gray-700">LADAP Hours</span>
                </div>

                <div className="flex flex-1 items-center gap-3 min-w-0">
                    <div className="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div
                            className={`h-full rounded-full transition-all ${barColor}`}
                            style={{ width: `${pct}%` }}
                        />
                    </div>
                    <span className={`text-xs font-bold shrink-0 ${valueColor}`}>
                        {fmt(total)} / {LADAP_TARGET} hrs
                    </span>
                    {pct >= 100 && (
                        <span className="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                            Target Achieved ✓
                        </span>
                    )}
                </div>

                <ChevronDown className={`h-4 w-4 text-gray-400 shrink-0 transition-transform ${open ? 'rotate-180' : ''}`} />
            </button>

            {/* Expanded detail */}
            {open && (
                <div className="border-t border-gray-100 px-5 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <p className="text-xs text-gray-400 mb-0.5">Earned</p>
                        <p className={`text-xl font-bold ${valueColor}`}>{fmt(total)} <span className="text-xs font-normal text-gray-400">hrs</span></p>
                    </div>
                    <div>
                        <p className="text-xs text-gray-400 mb-0.5">Target</p>
                        <p className="text-xl font-bold text-gray-700">{LADAP_TARGET} <span className="text-xs font-normal text-gray-400">hrs</span></p>
                    </div>
                    <div>
                        <p className="text-xs text-gray-400 mb-0.5">Remaining</p>
                        <p className="text-xl font-bold text-gray-500">{fmt(remaining)} <span className="text-xs font-normal text-gray-400">hrs</span></p>
                    </div>
                    {estimatedHours > 0 && (
                        <div>
                            <p className="text-xs text-gray-400 mb-0.5">Est. if Apply</p>
                            <p className="text-xl font-bold text-blue-500">+{fmt(estimatedHours)} <span className="text-xs font-normal text-gray-400">hrs</span></p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

const STATUS_BADGE: Record<string, { label: string; className: string }> = {
    upcoming: { label: 'Upcoming', className: 'bg-blue-100 text-blue-700' },
    ongoing:  { label: 'Ongoing',  className: 'bg-amber-100 text-amber-700' },
    completed: { label: 'Completed', className: 'bg-green-100 text-green-700' },
};

function CapacityBar({ approved, capacity }: { approved: number; capacity: number }) {
    const pct = Math.min(100, Math.round((approved / capacity) * 100));
    return (
        <div className="mt-3">
            <div className="mb-1 flex justify-between text-xs text-gray-500">
                <span className="flex items-center gap-1">
                    <Users className="h-3 w-3" />
                    {approved}/{capacity} enrolled
                </span>
                <span>{pct}%</span>
            </div>
            <div className="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                <div
                    className="h-full rounded-full bg-blue-500 transition-all"
                    style={{ width: `${pct}%` }}
                />
            </div>
        </div>
    );
}

type EnrollTab = 'all' | 'enrolled' | 'not_enrolled';

export default function TrainingsIndex({ trainings, user_applications, filters, total_ladap_hours = 0, estimated_ladap_hours = 0 }: Props) {
    const { isGuruBesar, isGuruBaru, isTeacher, isOutsider } = useRole();
    const [search, setSearch]       = useState(filters.search ?? '');
    const [enrollTab, setEnrollTab] = useState<EnrollTab>('all');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get(route('trainings.index'), { search, status: filters.status }, { preserveState: true });
    }

    function filterStatus(status: string | undefined) {
        router.get(route('trainings.index'), { search, status }, { preserveState: true });
    }

    const statusFilters = [
        { label: 'All',      value: undefined },
        { label: 'Upcoming', value: 'upcoming' },
        { label: 'Ongoing',  value: 'ongoing' },
        { label: 'Completed', value: 'completed' },
    ];

    const enrolledCount    = trainings.filter(t => {
        const s = user_applications[t.id];
        return s === 'approved' || s === 'enrolled';
    }).length;
    const notEnrolledCount = trainings.length - enrolledCount;

    function enrollPriority(courseId: string): number {
        const s = user_applications[courseId];
        if (!s)                                    return 0;
        if (s === 'pending')                       return 1;
        if (s === 'approved' || s === 'enrolled')  return 2;
        return 3;
    }

    const displayedTrainings = (isGuruBesar ? trainings : trainings.filter(t => {
        if (enrollTab === 'all') return true;
        const isEnrolled = user_applications[t.id] === 'approved' || user_applications[t.id] === 'enrolled';
        return enrollTab === 'enrolled' ? isEnrolled : !isEnrolled;
    })).slice().sort((a, b) => enrollPriority(a.id) - enrollPriority(b.id));

    return (
        <AppLayout>
            <Head title="Trainings" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">

                {/* Page header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100">
                            <BookOpen className="h-5 w-5 text-gray-500" />
                        </div>
                        <h1 className="text-2xl font-bold text-gray-900">Trainings</h1>
                    </div>
                </div>

                {/* LADAP hours bar — teacher only */}
                {isTeacher && (
                    <LadapStats
                        total={total_ladap_hours}
                        estimatedHours={estimated_ladap_hours}
                    />
                )}

                {/* Search + filters */}
                <div className="mb-6 rounded-xl border border-gray-200 bg-white p-4">
                    <form onSubmit={handleSearch} className="flex gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search trainings..."
                                className="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                            />
                        </div>
                    </form>
                    {!isGuruBesar && (
                        <div className="mt-3 flex items-center gap-3">
                            <span className="text-xs font-medium text-gray-400">View</span>
                            <div className="flex rounded-lg border border-gray-200 bg-gray-100 p-0.5">
                                {([
                                    { key: 'all'          as const, label: 'All',          count: trainings.length },
                                    { key: 'enrolled'     as const, label: 'Enrolled',     count: enrolledCount },
                                    { key: 'not_enrolled' as const, label: 'Not Enrolled', count: notEnrolledCount },
                                ]).map(tab => (
                                    <button
                                        key={tab.key}
                                        onClick={() => setEnrollTab(tab.key)}
                                        className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition-all ${
                                            enrollTab === tab.key
                                                ? 'bg-white text-blue-700 shadow-sm'
                                                : 'text-gray-500 hover:text-gray-700'
                                        }`}
                                    >
                                        {tab.label}
                                        <span className={`rounded-full px-1.5 py-0.5 text-xs font-bold ${
                                            enrollTab === tab.key
                                                ? 'bg-blue-100 text-blue-600'
                                                : 'bg-gray-200 text-gray-500'
                                        }`}>
                                            {tab.count}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="mt-3 flex gap-2">
                        {statusFilters.map((f) => (
                            <button
                                key={f.label}
                                onClick={() => filterStatus(f.value)}
                                className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${
                                    filters.status === f.value
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                }`}
                            >
                                {f.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Training cards grid */}
                {displayedTrainings.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center">
                        <GraduationCap className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">
                            {enrollTab === 'enrolled'     ? 'No enrolled courses yet.'
                            : enrollTab === 'not_enrolled' ? 'All courses are enrolled.'
                            : 'No courses found.'}
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {displayedTrainings.map((training) => {
                            const badge = STATUS_BADGE[training.status] ?? STATUS_BADGE.upcoming;
                            const appStatus = user_applications[training.id];
                            const approved = training.applications_count ?? 0;

                            return (
                                <div
                                    key={training.id}
                                    className="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div className="mb-3 flex items-start justify-between gap-2">
                                        <h3 className="font-semibold text-gray-900 leading-snug">
                                            {training.courseName}
                                        </h3>
                                        <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs font-medium ${badge.className}`}>
                                            {badge.label}
                                        </span>
                                    </div>

                                    <p className="mb-4 line-clamp-2 text-sm text-gray-500">
                                        {training.description}
                                    </p>

                                    <div className="mt-auto space-y-1.5 text-sm text-gray-600">
                                        {training.mode && (
                                            <div className="flex items-center gap-2">
                                                <Monitor className="h-3.5 w-3.5 text-gray-400" />
                                                {formatMode(training.mode)}
                                            </div>
                                        )}
                                        {training.course_category && (
                                            <div className="flex items-center gap-2">
                                                <Tag className="h-3.5 w-3.5 text-gray-400" />
                                                {training.course_category.split('_').map((w: string) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}
                                            </div>
                                        )}
                                        <div className="flex items-center gap-2">
                                            <Users className="h-3.5 w-3.5 text-gray-400" />
                                            {training.organiserName}
                                        </div>
                                    </div>

                                    <CapacityBar approved={approved} capacity={training.capacity} />

                                    {/* Action */}
                                    <div className="mt-4">
                                        {isGuruBesar ? (
                                            <Link
                                                href={route('trainings.show', training.id)}
                                                className="block w-full rounded-lg bg-blue-600 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700"
                                            >
                                                View Training
                                            </Link>
                                        ) : appStatus === 'approved' || appStatus === 'enrolled' ? (
                                            <Link
                                                href={route('trainings.show', training.id)}
                                                className="block w-full rounded-lg bg-green-100 py-2 text-center text-sm font-semibold text-green-700 hover:bg-green-200"
                                            >
                                                {appStatus === 'enrolled' ? 'Series — View Course' : 'Enrolled — View Course'}
                                            </Link>
                                        ) : appStatus === 'pending' ? (
                                            <span className="block w-full rounded-lg bg-amber-100 py-2 text-center text-sm font-semibold text-amber-700">
                                                Pending Approval
                                            </span>
                                        ) : appStatus === 'rejected' ? (
                                            <span className="block w-full rounded-lg bg-red-100 py-2 text-center text-sm font-semibold text-red-600">
                                                Rejected
                                            </span>
                                        ) : isGuruBaru && training.course_category === 'series' ? (
                                            <Link
                                                href={route('trainings.show', training.id)}
                                                className="block w-full rounded-lg bg-green-100 py-2 text-center text-sm font-semibold text-green-700 hover:bg-green-200"
                                            >
                                                Series — View Course
                                            </Link>
                                        ) : (
                                            <Link
                                                href={route('trainings.show', training.id)}
                                                className="block w-full rounded-lg bg-blue-600 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700"
                                            >
                                                View & Enroll
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
