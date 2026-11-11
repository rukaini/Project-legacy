import AppLayout from '@/layouts/app-layout';
import { type PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, Building2, CheckCircle, Clock, GraduationCap, School, Users } from 'lucide-react';

interface Stats {
    total_schools: number;
    total_teachers: number;
    total_outsiders: number;
    total_guru_baru: number;
    pending_total: number;
    approved_this_month: number;
}

interface StatusBreakdown { pending: number; approved: number; rejected: number }

interface School {
    id: string;
    name: string;
    teacher_count: number;
}

interface RecentApp {
    id: string;
    type: 'outsider' | 'guru_baru';
    outsider_id?: number;
    enrollment_id?: number;
    courseID: string;
    name: string;
    training: string;
    applied_at: string;
}

interface Props extends PageProps {
    stats: Stats;
    outsider_stats: StatusBreakdown;
    guru_baru_stats: StatusBreakdown;
    schools: School[];
    recent_pending: RecentApp[];
}

function StatCard({ label, value, sub, icon: Icon, color }: { label: string; value: number; sub?: string; icon: React.ElementType; color: string }) {
    return (
        <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div className={`mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg ${color}`}>
                <Icon className="h-5 w-5" />
            </div>
            <p className="text-2xl font-bold text-gray-900">{value}</p>
            <p className="mt-0.5 text-sm font-medium text-gray-600">{label}</p>
            {sub && <p className="mt-0.5 text-xs text-gray-400">{sub}</p>}
        </div>
    );
}

function StatusBar({ stats, label }: { stats: StatusBreakdown; label: string }) {
    const total = stats.pending + stats.approved + stats.rejected || 1;
    return (
        <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 className="mb-4 text-sm font-semibold text-gray-700">{label}</h3>
            <div className="space-y-3">
                {([
                    { key: 'pending',  label: 'Pending',  color: 'bg-amber-400',  text: 'text-amber-700'  },
                    { key: 'approved', label: 'Approved', color: 'bg-green-500',  text: 'text-green-700'  },
                    { key: 'rejected', label: 'Rejected', color: 'bg-red-400',    text: 'text-red-700'    },
                ] as const).map(({ key, label: l, color, text }) => (
                    <div key={key}>
                        <div className="mb-1 flex justify-between text-xs">
                            <span className={`font-medium ${text}`}>{l}</span>
                            <span className="font-semibold text-gray-700">{stats[key]}</span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div className={`h-full rounded-full ${color}`} style={{ width: `${(stats[key] / total) * 100}%` }} />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function approveOutsider(outsiderId: number, courseId: string) {
    router.post(route('hr.applications.outsider.approve', { outsiderId, courseId }));
}

function approveGuruBaru(enrollmentId: number) {
    router.post(route('hr.applications.guru-baru.approve', { enrollment: enrollmentId }));
}

export default function HrDashboard({ stats, outsider_stats, guru_baru_stats, schools, recent_pending }: Props) {
    return (
        <AppLayout>
            <Head title="HR Dashboard" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">HR Dashboard</h1>

                {/* Summary cards */}
                <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
                    <StatCard label="Total Schools"       value={stats.total_schools}        icon={School}        color="bg-indigo-100 text-indigo-600" />
                    <StatCard label="Total Teachers"      value={stats.total_teachers}       icon={GraduationCap} color="bg-blue-100 text-blue-600" />
                    <StatCard label="Total Outsiders"     value={stats.total_outsiders}      icon={Users}         color="bg-blue-100 text-blue-600" />
                    <StatCard label="Total Guru Baru"     value={stats.total_guru_baru}      icon={BookOpen}      color="bg-green-100 text-green-600" />
                    <StatCard label="Pending Approvals"   value={stats.pending_total}        icon={Clock}         color="bg-amber-100 text-amber-600"
                        sub={`${stats.approved_this_month} approved this month`} />
                </div>

                {/* Status breakdowns */}
                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <StatusBar stats={outsider_stats}  label="Outsider Applications" />
                    <StatusBar stats={guru_baru_stats} label="Guru Baru Applications" />
                </div>

                {/* Teachers by school */}
                <div className="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div className="flex items-center gap-2">
                            <Building2 className="h-4 w-4 text-gray-400" />
                            <h2 className="text-sm font-semibold text-gray-700">Teachers by School</h2>
                        </div>
                        <Link href={route('hr.teachers.index')} className="text-xs text-blue-600 hover:underline">View all →</Link>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {schools.slice(0, 8).map(school => (
                            <div key={school.id} className="flex items-center justify-between px-5 py-3">
                                <span className="text-sm text-gray-700">{school.name}</span>
                                <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                    {school.teacher_count} teachers
                                </span>
                            </div>
                        ))}
                        {schools.length === 0 && (
                            <p className="px-5 py-4 text-sm text-gray-400">No schools found.</p>
                        )}
                    </div>
                </div>

                {/* Recent pending */}
                <div className="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div className="flex items-center gap-2">
                            <Clock className="h-4 w-4 text-amber-500" />
                            <h2 className="text-sm font-semibold text-gray-700">Recent Pending Applications</h2>
                        </div>
                        <Link href={route('hr.applications.index')} className="text-xs text-blue-600 hover:underline">View all →</Link>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {recent_pending.length === 0 && (
                            <p className="px-5 py-4 text-sm text-gray-400">No pending applications.</p>
                        )}
                        {recent_pending.map(app => (
                            <div key={app.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium text-gray-800">{app.name}</p>
                                    <p className="truncate text-xs text-gray-400">{app.training}</p>
                                </div>
                                <div className="flex items-center gap-2 shrink-0">
                                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                        app.type === 'outsider' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'
                                    }`}>
                                        {app.type === 'outsider' ? 'Outsider' : 'Guru Baru'}
                                    </span>
                                    <button
                                        onClick={() => app.type === 'outsider'
                                            ? approveOutsider(app.outsider_id!, app.courseID)
                                            : approveGuruBaru(app.enrollment_id!)
                                        }
                                        className="flex items-center gap-1 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700"
                                    >
                                        <CheckCircle className="h-3.5 w-3.5" /> Approve
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
