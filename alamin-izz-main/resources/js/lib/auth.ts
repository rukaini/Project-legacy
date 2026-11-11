import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

export function useRole() {
    const { auth } = usePage<PageProps>().props;
    const role = auth.user?.role;

    return {
        role,
        isGuruBesar:   role === 'guru_besar',
        isTeacher:     role === 'teacher',
        isOutsider:    role === 'outsider',
        isGuruBaru:    role === 'guru_baru',
        isHR:          role === 'hr',
        isParticipant: role === 'teacher' || role === 'outsider' || role === 'guru_baru',
    };
}
