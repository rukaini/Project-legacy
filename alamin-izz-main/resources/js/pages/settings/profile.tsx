import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Profile settings', href: '/settings/profile' },
];

interface TeacherProfile {
    teacherName?: string | null;
    phoneNumber?: string | null;
    ICNumber?: string | null;
    gender?: string | null;
    address?: string | null;
    race?: string | null;
    martialStatus?: string | null;
    appointedDate?: string | null;
    serviceDate?: string | null;
    pensionDate?: string | null;
    latestAge?: number | null;
    schoolName?: string | null;
}

interface PrincipalProfile {
    principalName?: string | null;
    phoneNumber?: string | null;
    ICNumber?: string | null;
    gender?: string | null;
    address?: string | null;
    race?: string | null;
    martialStatus?: string | null;
    appointedDate?: string | null;
    schoolName?: string | null;
}

interface OutsiderProfile {
    organization?: string | null;
    phoneNumber?: string | null;
    ICNumber?: string | null;
    gender?: string | null;
}

type ProfileData = TeacherProfile & PrincipalProfile & OutsiderProfile;

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    profile: ProfileData;
}

function ReadOnlyField({ label, value }: { label: string; value?: string | number | null }) {
    return (
        <div className="grid gap-1">
            <Label className="text-xs text-gray-500">{label}</Label>
            <p className="rounded-md border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                {value ?? '—'}
            </p>
        </div>
    );
}

// Digits only, one dash after the first three: 019-7646689
function formatPhone(v: string): string {
    const d = v.replace(/\D/g, '').slice(0, 11);
    if (d.length <= 3) return d;
    return `${d.slice(0, 3)}-${d.slice(3)}`;
}

// Digits only, then auto-dashes: 011023-19-10000
function formatIc(v: string): string {
    const d = v.replace(/\D/g, '').slice(0, 13);
    if (d.length <= 6) return d;
    if (d.length <= 8) return `${d.slice(0, 6)}-${d.slice(6)}`;
    return `${d.slice(0, 6)}-${d.slice(6, 8)}-${d.slice(8)}`;
}

export default function Profile({ mustVerifyEmail, status, profile }: Props) {
    const { auth } = usePage<SharedData>().props;
    const role = auth.user!.role;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name:         auth.user!.name,
        email:        auth.user!.email,
        phone_number: (profile as TeacherProfile).phoneNumber ?? '',
        address:      (profile as TeacherProfile).address      ?? '',
        organization: (profile as OutsiderProfile).organization ?? '',
        phone_number_outsider: (profile as OutsiderProfile).phoneNumber ?? '',
        ic_number:    (profile as OutsiderProfile).ICNumber    ?? '',
        gender:       (profile as OutsiderProfile).gender      ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('profile.update'));
    };

    const isStaff = role === 'teacher' || role === 'guru_besar';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Profile information"
                        description={isStaff ? 'View your staff details and update your contact information' : 'Update your name, email, and account details'}
                    />

                    {/* ── Teacher / Principal — read-only info ───────────────────── */}
                    {isStaff && (
                        <div className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Staff Record
                            </p>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <ReadOnlyField label="Full Name" value={
                                    role === 'teacher'
                                        ? (profile as TeacherProfile).teacherName
                                        : (profile as PrincipalProfile).principalName
                                } />
                                <ReadOnlyField label="Email" value={auth.user!.email} />
                                <ReadOnlyField label="School" value={(profile as TeacherProfile).schoolName} />
                                <ReadOnlyField label="IC Number" value={(profile as TeacherProfile).ICNumber} />
                                <ReadOnlyField label="Gender" value={(profile as TeacherProfile).gender} />
                                <ReadOnlyField label="Race" value={(profile as TeacherProfile).race} />
                                <ReadOnlyField label="Marital Status" value={(profile as TeacherProfile).martialStatus} />
                                {(profile as TeacherProfile).appointedDate && (
                                    <ReadOnlyField label="Appointed Date" value={(profile as TeacherProfile).appointedDate} />
                                )}
                                {role === 'teacher' && (profile as TeacherProfile).serviceDate && (
                                    <ReadOnlyField label="Service Date" value={(profile as TeacherProfile).serviceDate} />
                                )}
                            </div>
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6">
                        {/* ── Outsider — name + email ──────────────────────────────── */}
                        {!isStaff && (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </>
                        )}

                        {/* ── Teacher / Principal — editable contact fields ──────── */}
                        {isStaff && (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone_number">Phone Number</Label>
                                    <Input
                                        id="phone_number"
                                        value={data.phone_number}
                                        onChange={(e) => setData('phone_number', e.target.value)}
                                        placeholder="+601X-XXXXXXX"
                                    />
                                    <InputError message={errors.phone_number} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="address">Address</Label>
                                    <textarea
                                        id="address"
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        rows={3}
                                        placeholder="Home address"
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    />
                                    <InputError message={errors.address} />
                                </div>
                            </>
                        )}

                        {/* ── Outsider — extra fields ──────────────────────────────── */}
                        {role === 'outsider' && (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="organization">Organization</Label>
                                    <Input
                                        id="organization"
                                        value={data.organization}
                                        onChange={(e) => setData('organization', e.target.value)}
                                        placeholder="e.g. Kementerian Pendidikan Malaysia"
                                    />
                                    <InputError message={errors.organization} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone_outsider">Phone Number</Label>
                                    <Input
                                        id="phone_outsider"
                                        value={data.phone_number_outsider}
                                        onChange={(e) => setData('phone_number_outsider', formatPhone(e.target.value))}
                                        placeholder="000-0000000"
                                        inputMode="numeric"
                                    />
                                    <InputError message={errors.phone_number_outsider} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="ic_number">IC Number</Label>
                                    <Input
                                        id="ic_number"
                                        value={data.ic_number}
                                        onChange={(e) => setData('ic_number', formatIc(e.target.value))}
                                        placeholder="000000-00-00000"
                                        inputMode="numeric"
                                    />
                                    <InputError message={errors.ic_number} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="gender">Gender</Label>
                                    <select
                                        id="gender"
                                        value={data.gender}
                                        onChange={(e) => setData('gender', e.target.value)}
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                    <InputError message={errors.gender} />
                                </div>
                            </>
                        )}

                        {mustVerifyEmail && auth.user!.email_verified_at === null && !isStaff && (
                            <div>
                                <p className="mt-2 text-sm text-neutral-800">
                                    Your email address is unverified.{' '}
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2 focus:outline-hidden"
                                    >
                                        Click here to re-send the verification email.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        A new verification link has been sent to your email address.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Save</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Saved</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                {!isStaff && <DeleteUser />}
            </SettingsLayout>
        </AppLayout>
    );
}
