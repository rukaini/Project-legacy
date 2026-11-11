import AppLayout from '@/layouts/app-layout';
import { type PageProps, type Training } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BarChart3 } from 'lucide-react';

interface SessionLite {
    id: string;
    date: string;
}

interface MatrixRow {
    user_id: string;
    name: string;
    email: string;
    cells: Record<string, 'present' | 'absent' | 'late' | null>;
}

interface Props extends PageProps {
    training: Training;
    sessions: SessionLite[];
    matrix: MatrixRow[];
}

const STATUS_BADGE: Record<string, string> = {
    present: 'bg-green-100 text-green-700',
    absent:  'bg-red-100 text-red-600',
    late:    'bg-amber-100 text-amber-700',
};

export default function AttendanceReport({ training, sessions, matrix }: Props) {
    return (
        <AppLayout>
            <Head title={`Attendance Report â€” ${training.title}`} />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                <Link
                    href={route('attendance.manage', training.id)}
                    className="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
                >
                    â† Back to Attendance
                </Link>

                <div className="mb-6 flex items-center gap-2">
                    <BarChart3 className="h-6 w-6 text-gray-500" />
                    <h1 className="text-2xl font-bold text-gray-900">Attendance Report</h1>
                </div>

                <p className="mb-4 text-sm text-gray-500">{training.title}</p>

                {matrix.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center text-gray-500">
                        No approved participants yet.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table className="w-full text-sm">
                            <thead className="border-b border-gray-100 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="sticky left-0 z-10 bg-gray-50 px-4 py-3 font-medium">
                                        Participant
                                    </th>
                                    {sessions.map((s) => (
                                        <th key={s.id} className="px-3 py-3 text-center font-medium">
                                            {new Date(s.date).toLocaleDateString('en-MY', {
                                                day: '2-digit', month: 'short',
                                            })}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {matrix.map((row) => (
                                    <tr key={row.user_id} className="hover:bg-gray-50">
                                        <td className="sticky left-0 z-10 bg-white px-4 py-3">
                                            <div className="font-medium text-gray-900">{row.name}</div>
                                            <div className="text-xs text-gray-500">{row.email}</div>
                                        </td>
                                        {sessions.map((s) => {
                                            const status = row.cells[s.id];
                                            return (
                                                <td key={s.id} className="px-3 py-3 text-center">
                                                    {status ? (
                                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_BADGE[status]}`}>
                                                            {status}
                                                        </span>
                                                    ) : (
                                                        <span className="text-xs text-gray-300">â€”</span>
                                                    )}
                                                </td>
                                            );
                                        })}
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
