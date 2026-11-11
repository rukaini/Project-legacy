import AppLayout from '@/layouts/app-layout';
import { type DevelopmentOverviewRow, type PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BarChart3, Download, Eye } from 'lucide-react';

interface Props extends PageProps {
    overview: DevelopmentOverviewRow[];
    departments: string[];
    filters: { department?: string };
    ladap_target_per_teacher: number;
}

export default function DevelopmentOverview({ overview, ladap_target_per_teacher }: Props) {
    const totalHours = overview.reduce((sum, r) => sum + r.ladap_hours, 0);
    const avgHours = overview.length > 0 ? totalHours / overview.length : 0;
    const targetHours = overview.length * ladap_target_per_teacher;

    return (
        <AppLayout>
            <Head title="Development Overview" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                <div className="mb-6 flex items-center gap-3">
                    <BarChart3 className="h-6 w-6 text-gray-500" />
                    <h1 className="text-2xl font-bold text-gray-900">Development Overview</h1>
                    <a
                        href={route('reports.development.pdf')}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="ml-auto inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3.5 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100"
                    >
                        <Download className="h-4 w-4" />
                        Export PDF
                    </a>
                </div>

                {/* KPI tiles */}
                <div className="mb-6 grid gap-3 sm:grid-cols-3">
                    <Tile label="Teachers Tracked" value={overview.length.toString()} />
                    <Tile label="Total LADAP Hours" value={`${totalHours.toFixed(2)} / ${targetHours.toFixed(2)}`} accent />
                    <Tile label="Avg per Teacher" value={`${avgHours.toFixed(2)} / ${ladap_target_per_teacher.toFixed(2)}`} />
                </div>

                {overview.length === 0 ? (
                    <div className="flex-1 flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 p-12">
                        <BarChart3 className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">No teacher data to show.</p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Teacher</th>
                                    <th className="px-4 py-3">IC Number</th>
                                    <th className="px-4 py-3 text-right">Completed</th>
                                    <th className="px-4 py-3 text-right">Total</th>
                                    <th className="px-4 py-3 text-right">Attendance Rate</th>
                                    <th className="px-4 py-3 text-right">LADAP Hours</th>
                                    <th className="px-4 py-3 text-right">Detail</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {overview.map((row) => (
                                    <tr key={row.user.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-gray-900">{row.user.name}</div>
                                            <div className="text-xs text-gray-500">{row.user.email}</div>
                                        </td>
                                        <td className="px-4 py-3 font-mono text-gray-700">
                                            {row.ic_number ?? <span className="text-gray-400">-</span>}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-700">{row.completed_trainings}</td>
                                        <td className="px-4 py-3 text-right text-gray-700">{row.total_trainings}</td>
                                        <td className="px-4 py-3 text-right text-gray-700">
                                            {row.attendance_rate !== null ? `${row.attendance_rate}%` : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-right font-semibold text-blue-700">
                                            {row.ladap_hours.toFixed(2)}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={route('history.teacher', row.user.id)}
                                                className="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100"
                                            >
                                                <Eye className="h-3.5 w-3.5" /> View Detail
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function Tile({ label, value, accent }: { label: string; value: string; accent?: boolean }) {
    return (
        <div className="rounded-xl border border-gray-200 bg-white p-4">
            <div className="text-xs font-medium text-gray-500">{label}</div>
            <div className={`mt-1 text-2xl font-bold ${accent ? 'text-blue-700' : 'text-gray-900'}`}>
                {value}
            </div>
        </div>
    );
}
