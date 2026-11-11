import AppLayout from '@/layouts/app-layout';
import { type HistoryRow, type PageProps, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Award, BookOpen, Clock, Download, HeartHandshake, History, Search, UserCircle2, X } from 'lucide-react';
import { useState } from 'react';

interface TarbiahHistoryRow {
    tarbiah_id: number;
    title: string;
    session_date: string;
    start_time: string;
    end_time: string;
    location: string | null;
    ladap_hours: number;
}

interface Props extends PageProps {
    rows: HistoryRow[];
    tarbiah_rows: TarbiahHistoryRow[];
    filters: { status?: string };
    viewing_self: boolean;
    viewing_user: Pick<User, 'id' | 'name' | 'email'> | null;
    selected_year: number;
    available_years: number[];
    total_ladap: number;
    total_ladap_training: number;
    total_ladap_training_only: number;
    total_ladap_penyelaras: number;
    total_ladap_certificate: number;
    total_ladap_tarbiah: number;
    ladap_training_target: number;
    ladap_tarbiah_target: number;
    is_outsider: boolean;
    is_guru_baru: boolean;
}

function fmtTime(t: string) {
    const [h, m] = t.split(':');
    const hour = parseInt(h, 10);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const hour12 = ((hour + 11) % 12) + 1;
    return `${hour12}:${m} ${suffix}`;
}

function fmtDate(d: string) {
    return new Date(d + 'T00:00:00').toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

const STATUS_PILLS: Record<string, string> = {
    completed: 'bg-green-100 text-green-700 border-green-200',
    upcoming: 'bg-blue-100 text-blue-700 border-blue-200',
    ongoing:  'bg-amber-100 text-amber-700 border-amber-200',
};

export default function HistoryIndex({
    rows,
    tarbiah_rows,
    filters,
    viewing_self,
    viewing_user,
    selected_year,
    available_years,
    total_ladap,
    total_ladap_training,
    total_ladap_training_only,
    total_ladap_penyelaras,
    total_ladap_certificate,
    total_ladap_tarbiah,
    ladap_training_target,
    ladap_tarbiah_target,
    is_outsider,
    is_guru_baru,
}: Props) {
    const hideladap = is_outsider || is_guru_baru;
    const historyRoute = viewing_self ? route('history.mine') : route('history.teacher', viewing_user!.id);

    const setFilter = (status: string | null) => {
        const params: Record<string, string> = { year: String(selected_year) };
        if (status) params.status = status;
        router.get(historyRoute, params, { preserveScroll: true, preserveState: true });
    };

    const setYear = (year: number) => {
        const params: Record<string, string> = { year: String(year) };
        if (filters.status) params.status = filters.status;
        router.get(historyRoute, params, { preserveScroll: true, preserveState: true });
    };

    const [search,         setSearch]         = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [typeFilter,     setTypeFilter]     = useState<'' | 'training' | 'penyelaras_ladap' | 'certificate'>('');
    const [activeTable,    setActiveTable]    = useState<'training' | 'tarbiah'>('training');

    const heading = viewing_self ? 'My Training History' : `${viewing_user?.name}'s Training History`;
    const showRow = (row: HistoryRow) => viewing_self
        ? route('history.show', row.training_id)
        : route('trainings.show', row.training_id);

    const filtered = rows.filter((r) => {
        const matchSearch = !search.trim() ||
            r.title.toLowerCase().includes(search.toLowerCase()) ||
            (r.organiserName ?? '').toLowerCase().includes(search.toLowerCase());
        const matchCategory = !categoryFilter || (is_guru_baru || is_outsider
            ? r.course_category === categoryFilter
            : r.course_type === categoryFilter);
        const matchType = !typeFilter || r.type === typeFilter;
        return matchSearch && matchCategory && matchType;
    });

    return (
        <AppLayout>
            <Head title={heading} />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                {!viewing_self && (
                    <Link
                        href={route('reports.development')}
                        className="mb-4 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-700"
                    >
                        <ArrowLeft className="h-4 w-4" /> Back to Development Overview
                    </Link>
                )}
                {!viewing_self && viewing_user && (
                    <div className="mb-4 flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        <UserCircle2 className="h-4 w-4" />
                        Viewing the training history of <strong>{viewing_user.name}</strong> ({viewing_user.email}).
                    </div>
                )}

                <div className="mb-6 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                    <div className="flex items-center gap-2">
                        <History className="h-6 w-6 text-gray-500" />
                        <h1 className="text-2xl font-bold text-gray-900">{heading}</h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <select
                            value={selected_year}
                            onChange={(e) => setYear(Number(e.target.value))}
                            className="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-8 text-sm font-medium text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                        >
                            {available_years.map((y) => (
                                <option key={y} value={y}>{y}</option>
                            ))}
                        </select>
                        {viewing_self && (
                            <a
                                href={
                                    is_outsider
                                        ? route('history.export.outsider', { year: selected_year })
                                        : is_guru_baru
                                        ? route('history.export.guru_baru', { year: selected_year })
                                        : route('history.export', { year: selected_year })
                                }
                                className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                <Download className="h-4 w-4" />
                                Export PDF
                            </a>
                        )}
                    </div>
                </div>

                {/* Summary cards */}
                <div className={`mb-6 grid gap-3 ${hideladap ? 'sm:grid-cols-3' : 'sm:grid-cols-4'}`}>
                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <div className="flex items-center gap-2 text-xs font-medium text-gray-500">
                            <BookOpen className="h-3.5 w-3.5" /> Total Trainings
                        </div>
                        <div className="mt-1 text-2xl font-bold text-gray-900">{rows.length}</div>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <div className="flex items-center gap-2 text-xs font-medium text-gray-500">
                            <BookOpen className="h-3.5 w-3.5" /> Completed
                        </div>
                        <div className="mt-1 text-2xl font-bold text-gray-900">
                            {rows.filter((r) => r.status === 'completed').length}
                        </div>
                    </div>
                    <div className={`rounded-xl border p-4 ${is_guru_baru ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-white'}`}>
                        <div className="flex items-center gap-2 text-xs font-medium text-gray-500">
                            <Clock className="h-3.5 w-3.5" /> Total Hours
                        </div>
                        <div className={`mt-1 text-2xl font-bold ${is_guru_baru ? 'text-indigo-700' : 'text-gray-900'}`}>
                            {rows.reduce((sum, r) => sum + (r.total_session_hours ?? 0), 0).toFixed(1)}
                        </div>
                    </div>
                    {!hideladap && (
                        <div className="rounded-xl border border-gray-200 bg-white p-4">
                            <div className="flex items-center gap-2 text-xs font-medium text-gray-500">
                                <Award className="h-3.5 w-3.5" /> Total LADAP Hours
                            </div>
                            <div className="mt-1 text-2xl font-bold text-blue-700">
                                {total_ladap.toFixed(2)}
                                <span className="ml-1 text-sm font-normal text-gray-400">
                                    / {(ladap_training_target + ladap_tarbiah_target).toFixed(0)} hrs
                                </span>
                            </div>
                            <div className="mt-1 text-xs text-gray-400">
                                {total_ladap_training_only.toFixed(2)} training + {total_ladap_penyelaras.toFixed(2)} ladap + {total_ladap_certificate.toFixed(2)} certificate + {total_ladap_tarbiah.toFixed(2)} tarbiah
                            </div>
                        </div>
                    )}
                </div>

                {!hideladap && (
                    <div className="mb-6 grid gap-3 sm:grid-cols-2">
                        <LadapProgressBar
                            label={`Training, LADAP & Certificate (${selected_year})`}
                            total={total_ladap_training}
                            target={ladap_training_target}
                            barColorClass="bg-blue-500"
                            active={activeTable === 'training'}
                            onClick={() => setActiveTable('training')}
                        />
                        <LadapProgressBar
                            label={`Tarbiah (${selected_year})`}
                            total={total_ladap_tarbiah}
                            target={ladap_tarbiah_target}
                            barColorClass="bg-rose-500"
                            active={activeTable === 'tarbiah'}
                            onClick={() => setActiveTable('tarbiah')}
                        />
                    </div>
                )}

                {activeTable === 'training' && viewing_self && (
                    <div className="mb-4 flex gap-2">
                        <button
                            onClick={() => setFilter(null)}
                            className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                !filters.status
                                    ? 'border-blue-500 bg-blue-50 text-blue-700'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                            }`}
                        >
                            All
                        </button>
                        <button
                            onClick={() => setFilter('in_progress')}
                            className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                filters.status === 'in_progress'
                                    ? 'border-blue-500 bg-blue-50 text-blue-700'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                            }`}
                        >
                            In Progress
                        </button>
                        <button
                            onClick={() => setFilter('completed')}
                            className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                filters.status === 'completed'
                                    ? 'border-green-500 bg-green-50 text-green-700'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                            }`}
                        >
                            Completed
                        </button>
                    </div>
                )}

                {/* Search + category filter */}
                {activeTable === 'training' && (
                <div className="mb-4 flex gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by training name or organiser..."
                            className="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-9 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                        />
                        {search && (
                            <button onClick={() => setSearch('')} className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <X className="h-3.5 w-3.5" />
                            </button>
                        )}
                    </div>
                    {!is_guru_baru && !is_outsider && (
                        <select
                            value={typeFilter}
                            onChange={(e) => setTypeFilter(e.target.value as '' | 'training' | 'penyelaras_ladap' | 'certificate')}
                            className="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-8 text-sm text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                        >
                            <option value="">All Types</option>
                            <option value="training">Training</option>
                            <option value="penyelaras_ladap">LADAP</option>
                            <option value="certificate">Certificate</option>
                        </select>
                    )}
                    <select
                        value={categoryFilter}
                        onChange={(e) => setCategoryFilter(e.target.value)}
                        className="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-8 text-sm text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400"
                    >
                        <option value="">All Categories</option>
                        {is_guru_baru && <option value="series">Series</option>}
                        {is_guru_baru && <option value="single">Single</option>}
                        {is_guru_baru && <option value="multi_day">Multi Day</option>}
                        {!is_guru_baru && !is_outsider && <option value="lms">LMS</option>}
                        {!is_guru_baru && !is_outsider && <option value="simple">Simple</option>}
                    </select>
                </div>
                )}

                {activeTable === 'training' && (rows.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center">
                        <History className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">No training history yet.</p>
                    </div>
                ) : filtered.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-200 p-12 flex-1 flex flex-col items-center justify-center">
                        <Search className="mx-auto mb-3 h-8 w-8 text-gray-300" />
                        <p className="text-sm text-gray-500">No trainings match your search.</p>
                        <button onClick={() => { setSearch(''); setCategoryFilter(''); setTypeFilter(''); }} className="mt-2 text-xs text-blue-600 hover:underline">
                            Clear filters
                        </button>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Training</th>
                                    {!is_guru_baru && !is_outsider && <th className="px-4 py-3">Type</th>}
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Attendance</th>
                                    {is_guru_baru && <th className="px-4 py-3 text-right">Task</th>}
                                    {is_guru_baru && <th className="px-4 py-3 text-right">Quiz Avg</th>}
                                    <th className="px-4 py-3 text-right">Total Hrs</th>
                                    {!hideladap && <th className="px-4 py-3 text-right">LADAP Hrs</th>}
                                    {viewing_self && <th className="px-4 py-3"></th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {filtered.map((row) => (
                                    <tr key={row.application_id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3">
                                            <p className="font-medium text-gray-900">{row.title}</p>
                                            <div className="text-xs text-gray-500">
                                                {row.type !== 'training'
                                                    ? [row.mode].filter(Boolean).join(' · ')
                                                    : [
                                                        row.course_type
                                                            ? row.course_type.toUpperCase()
                                                            : (row.course_category
                                                                ? row.course_category.split('_').map((w: string) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
                                                                : null),
                                                        row.organiserName,
                                                    ].filter(Boolean).join(' · ')}
                                            </div>
                                        </td>
                                        {!is_guru_baru && !is_outsider && (
                                            <td className="px-4 py-3">
                                                <span className={`inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold ${
                                                    row.type === 'penyelaras_ladap'
                                                        ? 'border-purple-200 bg-purple-100 text-purple-700'
                                                        : row.type === 'certificate'
                                                        ? 'border-emerald-200 bg-emerald-100 text-emerald-700'
                                                        : 'border-blue-200 bg-blue-100 text-blue-700'
                                                }`}>
                                                    {row.type === 'penyelaras_ladap' ? 'LADAP' : row.type === 'certificate' ? 'Certificate' : 'Training'}
                                                </span>
                                            </td>
                                        )}
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold ${STATUS_PILLS[row.status] ?? ''}`}>
                                                {row.status.charAt(0).toUpperCase() + row.status.slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            {(() => {
                                                if (row.present_count > 0 && row.total_sessions > 0) {
                                                    return <span className="text-gray-700">{row.present_count}/{row.total_sessions} ({row.attendance_rate}%)</span>;
                                                }
                                                if (row.total_sessions > 0) {
                                                    return <span className="text-gray-400">0/{row.total_sessions}</span>;
                                                }
                                                return <span className="text-gray-400">{'—'}</span>;
                                            })()}
                                        </td>
                                        {is_guru_baru && (
                                            <td className="px-4 py-3 text-right text-gray-700">
                                                {row.course_category === 'series'
                                                    ? `${row.submitted_count ?? 0}/${row.total_tasks ?? 0}`
                                                    : <span className="text-gray-400">—</span>}
                                            </td>
                                        )}
                                        {is_guru_baru && (
                                            <td className="px-4 py-3 text-right text-gray-700">
                                                {row.course_category === 'series'
                                                    ? (row.average_quiz_score != null ? `${row.average_quiz_score}%` : '—')
                                                    : <span className="text-gray-400">—</span>}
                                            </td>
                                        )}
                                        <td className={`px-4 py-3 text-right font-semibold ${is_guru_baru ? 'text-indigo-700' : 'text-gray-700'}`}>
                                            {row.total_session_hours.toFixed(1)}
                                        </td>
                                        {!hideladap && (
                                            <td className="px-4 py-3 text-right font-semibold text-blue-700">
                                                {row.ladap_hours.toFixed(2)}
                                            </td>
                                        )}
                                        {viewing_self && (
                                            <td className="px-4 py-3 text-right">
                                                {row.type !== 'training' ? null : (
                                                    <Link
                                                        href={showRow(row)}
                                                        className="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                                    >
                                                        View
                                                    </Link>
                                                )}
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ))}

                {activeTable === 'tarbiah' && !hideladap && (
                    <div>
                        <div className="mb-3 flex items-center gap-2">
                            <HeartHandshake className="h-5 w-5 text-gray-500" />
                            <h2 className="text-lg font-bold text-gray-900">Tarbiah History</h2>
                        </div>
                        {tarbiah_rows.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center">
                                <HeartHandshake className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                                <p className="text-gray-500">No Tarbiah attendance yet.</p>
                            </div>
                        ) : (
                            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <table className="w-full text-sm">
                                    <thead className="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th className="px-4 py-3">Tarbiah</th>
                                            <th className="px-4 py-3">Date</th>
                                            <th className="px-4 py-3">Time</th>
                                            <th className="px-4 py-3 text-right">LADAP Hrs</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {tarbiah_rows.map((t) => (
                                            <tr key={t.tarbiah_id} className="hover:bg-gray-50">
                                                <td className="px-4 py-3">
                                                    <p className="font-medium text-gray-900">{t.title}</p>
                                                    {t.location && <div className="text-xs text-gray-500">{t.location}</div>}
                                                </td>
                                                <td className="px-4 py-3 text-gray-700">{fmtDate(t.session_date)}</td>
                                                <td className="px-4 py-3 text-gray-700">
                                                    {fmtTime(t.start_time)} – {fmtTime(t.end_time)}
                                                </td>
                                                <td className="px-4 py-3 text-right font-semibold text-rose-700">
                                                    {t.ladap_hours.toFixed(2)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function LadapProgressBar({
    label,
    total,
    target,
    barColorClass,
    active,
    onClick,
}: {
    label: string;
    total: number;
    target: number;
    barColorClass: string;
    active: boolean;
    onClick: () => void;
}) {
    const pct = Math.min(100, Math.round((total / target) * 100));
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-xl border bg-white p-4 text-left transition-colors ${
                active ? 'border-blue-400 ring-1 ring-blue-300' : 'border-gray-200 hover:border-gray-300'
            }`}
        >
            <div className="mb-1.5 flex items-center justify-between text-xs font-medium text-gray-500">
                <span>{label}</span>
                <span className="font-semibold text-gray-700">{total.toFixed(2)} / {target} hrs</span>
            </div>
            <div className="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <div className={`h-full rounded-full transition-all ${barColorClass}`} style={{ width: `${pct}%` }} />
            </div>
        </button>
    );
}
