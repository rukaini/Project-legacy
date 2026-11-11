import AppLayout from '@/layouts/app-layout';
import { type PageProps } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Search, Users } from 'lucide-react';
import { useState } from 'react';

interface Outsider {
    id: number;
    name: string;
    ic: string;
    phone: string | null;
    gender: string | null;
}

interface Props extends PageProps {
    outsiders: Outsider[];
    filters: { search: string };
}

export default function HrOutsiders({ outsiders, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function applySearch() {
        router.get(route('hr.outsiders.index'), { search }, { preserveState: true });
    }

    return (
        <AppLayout>
            <Head title="HR — Outsiders" />
            <div className="flex flex-1 flex-col px-6 py-8 sm:px-8">
                <div className="mb-6 flex items-center gap-3">
                    <Users className="h-6 w-6 text-blue-600" />
                    <h1 className="text-2xl font-bold text-gray-900">Outsiders</h1>
                    <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{outsiders.length}</span>
                </div>

                <div className="mb-5 relative max-w-sm">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input type="text" value={search} placeholder="Search by name or IC…"
                        onChange={e => setSearch(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && applySearch()}
                        className="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-blue-400 focus:outline-none" />
                </div>

                <div className="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
                    {outsiders.length === 0 ? (
                        <p className="p-10 text-center text-sm text-gray-400">No outsiders found.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                <tr>
                                    <th className="px-5 py-3 text-left">Name</th>
                                    <th className="px-5 py-3 text-left">IC Number</th>
                                    <th className="px-5 py-3 text-left">Phone</th>
                                    <th className="px-5 py-3 text-left">Gender</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {outsiders.map(o => (
                                    <tr key={o.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3 font-medium text-gray-800">{o.name}</td>
                                        <td className="px-5 py-3 text-gray-500">{o.ic}</td>
                                        <td className="px-5 py-3 text-gray-500">{o.phone ?? '—'}</td>
                                        <td className="px-5 py-3 text-gray-500">{o.gender ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
