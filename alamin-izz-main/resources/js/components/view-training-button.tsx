import { Link } from '@inertiajs/react';
import { Lock } from 'lucide-react';

interface Props {
    href: string;
    /** From participant_material_access, computed on the server. */
    canView: boolean;
    /** ISO date the access ends, when an end date is set. */
    accessUntil?: string | null;
}

function fmtDate(iso: string) {
    return new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

/** "View Training" link for participants; disabled when the course's access is off or has expired. */
export default function ViewTrainingButton({ href, canView, accessUntil }: Props) {
    if (!canView) {
        return (
            <div className="inline-flex flex-col items-center gap-1">
                <span
                    aria-disabled="true"
                    title={accessUntil ? `Access ended on ${fmtDate(accessUntil)}` : 'Access to this training is disabled'}
                    className="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg bg-gray-200 px-4 py-2 text-xs font-semibold text-gray-400"
                >
                    <Lock className="h-3.5 w-3.5" />
                    View Training
                </span>
                <span className="text-[11px] text-gray-400">
                    {accessUntil ? `Ended ${fmtDate(accessUntil)}` : 'Access disabled'}
                </span>
            </div>
        );
    }

    return (
        <div className="inline-flex flex-col items-center gap-1">
            <Link
                href={href}
                className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                View Training
            </Link>
            {accessUntil && <span className="text-[11px] text-gray-400">Until {fmtDate(accessUntil)}</span>}
        </div>
    );
}
