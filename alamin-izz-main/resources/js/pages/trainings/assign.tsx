import AppLayout from '@/layouts/app-layout';
import { type PageProps, type Training, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { useState } from 'react';

interface Props extends PageProps {
    training: Training;
    available_teachers: Pick<User, 'id' | 'name' | 'email'>[];
}

export default function AssignTeachers({ training, available_teachers }: Props) {
    const [selected, setSelected] = useState<number[]>([]);
    const [submitting, setSubmitting] = useState(false);

    function toggle(id: number) {
        setSelected((prev) =>
            prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
        );
    }

    function toggleAll() {
        setSelected(selected.length === available_teachers.length
            ? []
            : available_teachers.map((t) => t.id),
        );
    }

    function handleSubmit() {
        if (selected.length === 0) return;
        setSubmitting(true);
        router.post(
            route('trainings.assign', training.id),
            { user_ids: selected },
            { onFinish: () => setSubmitting(false) },
        );
    }

    return (
        <AppLayout>
            <Head title={`Assign Teachers — ${training.title}`} />
            <div className="mx-auto w-full max-w-4xl flex flex-1 flex-col px-6 py-8 sm:px-8">
                <Link
                    href={route('trainings.show', training.id)}
                    className="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
                >
                    ← Back to {training.title}
                </Link>

                <div className="mb-6 flex items-center gap-2">
                    <UserPlus className="h-6 w-6 text-gray-500" />
                    <h1 className="text-2xl font-bold text-gray-900">Assign Teachers</h1>
                </div>

                <p className="mb-4 text-sm text-gray-500">
                    Select teachers to enroll directly. They will not need to apply — their applications will be created as approved.
                </p>

                <div className="rounded-xl border border-gray-200 bg-white shadow-sm">
                    {available_teachers.length === 0 ? (
                        <div className="p-8 text-center text-gray-500">
                            All teachers have already applied or been assigned to this training.
                        </div>
                    ) : (
                        <>
                            <div className="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                                <label className="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={selected.length === available_teachers.length && available_teachers.length > 0}
                                        onChange={toggleAll}
                                        className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                                    />
                                    Select All
                                </label>
                                <span className="text-xs text-gray-500">
                                    {selected.length} of {available_teachers.length} selected
                                </span>
                            </div>
                            <ul className="divide-y divide-gray-100">
                                {available_teachers.map((teacher) => (
                                    <li key={teacher.id} className="px-4 py-3">
                                        <label className="flex items-center gap-3 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={selected.includes(teacher.id)}
                                                onChange={() => toggle(teacher.id)}
                                                className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                                            />
                                            <div>
                                                <div className="text-sm font-medium text-gray-900">{teacher.name}</div>
                                                <div className="text-xs text-gray-500">{teacher.email}</div>
                                            </div>
                                        </label>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </div>

                <div className="mt-6 flex justify-end gap-2">
                    <Link
                        href={route('trainings.show', training.id)}
                        className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>
                    <button
                        onClick={handleSubmit}
                        disabled={selected.length === 0 || submitting}
                        className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <UserPlus className="h-4 w-4" />
                        Assign {selected.length > 0 && `(${selected.length})`}
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
