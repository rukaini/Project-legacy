import AppLayout from '@/layouts/app-layout';
import { type PageProps, type Training } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, Clock, Users } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface AttendanceSession {
    id: string;
    training_id: string;
    date: string;
    start_time: string | null;
    end_time: string | null;
    present_count: number;
}

interface Props extends PageProps {
    training: Training;
    sessions: AttendanceSession[];
}

function useNow(intervalMs = 10000): Date {
    const [now, setNow] = useState(() => new Date());
    useEffect(() => {
        const t = setInterval(() => setNow(new Date()), intervalMs);
        return () => clearInterval(t);
    }, [intervalMs]);
    return now;
}

type SessionPhase = 'active' | 'upcoming' | 'ended';

function getPhase(session: AttendanceSession, now: Date): SessionPhase {
    if (!session.start_time || !session.end_time) return 'upcoming';
    const start = new Date(session.start_time).getTime();
    const end   = new Date(session.end_time).getTime();
    const t     = now.getTime();
    if (t < start)  return 'upcoming';
    if (t <= end)   return 'active';
    return 'ended';
}

const PHASE_BADGE = {
    active:   'bg-green-100 text-green-700',
    upcoming: 'bg-amber-100 text-amber-700',
    ended:    'bg-gray-100 text-gray-500',
};

const PHASE_LABEL = {
    active:   'Active',
    upcoming: 'Upcoming',
    ended:    'Closed',
};

function formatTime(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' });
}

export default function ManageAttendance({ training, sessions }: Props) {
    const now = useNow();
    const prevPhases = useRef<Record<string, SessionPhase>>({});

    useEffect(() => {
        for (const session of sessions) {
            const phase     = getPhase(session, now);
            const prevPhase = prevPhases.current[session.id];
            if (prevPhase === 'active' && phase === 'ended') {
                router.reload({ only: ['sessions'] });
            }
            prevPhases.current[session.id] = phase;
        }
    }, [now, sessions]);

    return (
        <AppLayout>
            <Head title={`Attendance — ${training.courseName}`} />
            <div className="mx-auto w-full max-w-4xl flex flex-1 flex-col px-6 py-8 sm:px-8">
                <Link
                    href={route('trainings.show', training.id)}
                    className="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
                >
                    ← Back to {training.courseName}
                </Link>

                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <CalendarDays className="h-6 w-6 text-gray-500" />
                        <h1 className="text-2xl font-bold text-gray-900">Attendance</h1>
                    </div>
                    <Link
                        href={route('attendance.report', training.id)}
                        className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        View Report
                    </Link>
                </div>

                {sessions.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center">
                        <Clock className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">No sessions found for this training.</p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {sessions.map((session) => {
                            const phase = getPhase(session, now);
                            return (
                                <div
                                    key={session.id}
                                    className={`rounded-xl border bg-white p-5 shadow-sm ${
                                        phase === 'active' ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-200'
                                    }`}
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div className="flex items-center gap-3">
                                            <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${PHASE_BADGE[phase]}`}>
                                                {PHASE_LABEL[phase]}
                                            </span>
                                            <span className="text-sm font-semibold text-gray-800">
                                                {new Date(session.date).toLocaleDateString('en-MY', {
                                                    weekday: 'short', day: '2-digit', month: 'short', year: 'numeric',
                                                })}
                                            </span>
                                            {session.start_time && session.end_time && (
                                                <span className="flex items-center gap-1 text-xs text-gray-400">
                                                    <Clock className="h-3.5 w-3.5" />
                                                    {formatTime(session.start_time)} – {formatTime(session.end_time)}
                                                </span>
                                            )}
                                        </div>

                                        <div className="flex items-center gap-2 text-sm text-gray-600">
                                            <Users className="h-4 w-4 text-gray-400" />
                                            <span><strong>{session.present_count}</strong> marked present</span>
                                        </div>
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
