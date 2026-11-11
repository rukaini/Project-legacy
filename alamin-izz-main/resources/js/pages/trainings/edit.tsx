import AppLayout from '@/layouts/app-layout';
import { type PageProps, type Training } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

const MODES = [
    { value: 'online',   label: 'Online' },
    { value: 'physical', label: 'Physical' },
    { value: 'hybrid',   label: 'Hybrid' },
];
const CATEGORIES = [
    { value: 'LMS',    label: 'LMS (auto-enrolled)' },
    { value: 'Simple', label: 'Simple (apply to enroll)' },
];

interface Props extends PageProps {
    training: Training;
}

export default function TrainingEdit({ training }: Props) {
    const trainingId = training.courseID ?? training.id;

    const { data, setData, patch, processing, errors } = useForm({
        courseName:      training.courseName,
        courseType: training.courseType ?? '',
        description:     training.description ?? '',
        capacity:        training.capacity,
        price:           training.price,
        mode:            training.mode ?? 'physical',
        course_pic:      training.course_pic ?? '',
        whatsappGroup:   training.whatsappGroup ?? '',
        organiserName:   training.organiserName,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        patch(route('trainings.update', trainingId));
    }

    return (
        <AppLayout>
            <Head title={`Edit Training — ${training.courseName}`} />
            <div className="mx-auto w-full max-w-4xl flex flex-1 flex-col px-6 py-8 sm:px-8">

                <div className="mb-6">
                    <Link
                        href={route('trainings.show', trainingId)}
                        className="text-sm text-gray-500 hover:text-gray-700"
                    >
                        ← Back to Training
                    </Link>
                    <h1 className="mt-2 text-2xl font-bold text-gray-900">Edit Training</h1>
                    <p className="mt-0.5 text-sm text-gray-500">{training.courseName}</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded-xl border border-gray-200 bg-white p-6">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">
                            Basic Information
                        </h2>
                        <div className="space-y-4">
                            <Field label="Training Name" error={errors.courseName}>
                                <input
                                    type="text"
                                    value={data.courseName}
                                    onChange={(e) => setData('courseName', e.target.value)}
                                    className={inputCls(errors.courseName)}
                                />
                            </Field>

                            <Field label="Course Type" error={errors.courseType}>
                                <select
                                    value={data.courseType}
                                    onChange={(e) => setData('courseType', e.target.value)}
                                    className={inputCls(errors.courseType)}
                                >
                                    <option value="">— Select course type —</option>
                                    {CATEGORIES.map((c) => (
                                        <option key={c.value} value={c.value}>{c.label}</option>
                                    ))}
                                </select>
                            </Field>

                            <Field label="Description" error={errors.description}>
                                <textarea
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={4}
                                    className={inputCls(errors.description)}
                                />
                            </Field>

                            <Field label="Organiser Name" error={errors.organiserName}>
                                <input
                                    type="text"
                                    value={data.organiserName}
                                    onChange={(e) => setData('organiserName', e.target.value)}
                                    className={inputCls(errors.organiserName)}
                                />
                            </Field>
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-6">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">
                            Logistics
                        </h2>
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <Field label="Capacity" error={errors.capacity}>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.capacity}
                                        onChange={(e) => setData('capacity', parseInt(e.target.value))}
                                        className={inputCls(errors.capacity)}
                                    />
                                </Field>
                                <Field label="Price (RM)" error={errors.price}>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.price}
                                        onChange={(e) => setData('price', parseFloat(e.target.value))}
                                        className={inputCls(errors.price)}
                                    />
                                </Field>
                            </div>

                            <Field label="Mode" error={errors.mode}>
                                <select
                                    value={data.mode}
                                    onChange={(e) => setData('mode', e.target.value)}
                                    className={inputCls(errors.mode)}
                                >
                                    {MODES.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                                </select>
                            </Field>

                            <Field label="Contact Person" error={errors.course_pic}>
                                <input
                                    type="text"
                                    value={data.course_pic}
                                    onChange={(e) => setData('course_pic', e.target.value)}
                                    className={inputCls(errors.course_pic)}
                                />
                            </Field>

                            <Field label="WhatsApp Group URL" error={errors.whatsappGroup}>
                                <input
                                    type="url"
                                    value={data.whatsappGroup}
                                    onChange={(e) => setData('whatsappGroup', e.target.value)}
                                    className={inputCls(errors.whatsappGroup)}
                                />
                            </Field>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3">
                        <Link
                            href={route('trainings.show', trainingId)}
                            className="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            {processing ? 'Saving...' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

function inputCls(error?: string) {
    return `w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-1 ${
        error
            ? 'border-red-400 focus:border-red-500 focus:ring-red-300'
            : 'border-gray-200 focus:border-blue-400 focus:ring-blue-300'
    }`;
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>
            {children}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}
