import AppLayout from '@/layouts/app-layout';
import { type AppNotification, type PageProps } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Bell, CheckCheck, Trash2 } from 'lucide-react';

// Named so it doesn't overwrite the shared `notifications` prop (the bell's unread count).
interface Props extends PageProps {
    notification_list: AppNotification[];
}

interface NotificationGroup {
    type: string;
    url: string | null;
    items: AppNotification[];
    unreadCount: number;
    latestAt: string;
}

const GROUP_LABEL: Record<string, (n: number) => string> = {
    'application.submitted': (n) => `${n} new application${n > 1 ? 's' : ''} pending review`,
    'application.approved':  (n) => `${n} application${n > 1 ? 's' : ''} approved`,
    'application.rejected':  (n) => `${n} application${n > 1 ? 's' : ''} rejected`,
    'training.created':      (n) => `${n} new training${n > 1 ? 's' : ''} available`,
    'training.updated':      (n) => `${n} training update${n > 1 ? 's' : ''}`,
    'training.cancelled':    (n) => `${n} training${n > 1 ? 's' : ''} cancelled`,
    'teacher.assigned':      (n) => `Assigned to ${n} training${n > 1 ? 's' : ''}`,
    'attendance.reminder':   (n) => `${n} attendance session${n > 1 ? 's' : ''} open — mark your presence`,
    'tarbiah.created':                      (n) => `${n} new Tarbiah session${n > 1 ? 's' : ''}`,
    'penyelaras_ladap.created':             (n) => `${n} new LADAP session${n > 1 ? 's' : ''}`,
    'penyelaras_ladap.assigned':            (n) => `Assigned to lead ${n} LADAP session${n > 1 ? 's' : ''}`,
    'penyelaras_ladap.attendance_marked':   (n) => `${n} LADAP attendance${n > 1 ? 's' : ''} waiting for approval`,
    'penyelaras_ladap.attendance_approved': (n) => `${n} LADAP attendance${n > 1 ? 's' : ''} approved`,
    'certificate.uploaded':                 (n) => `${n} certificate${n > 1 ? 's' : ''} waiting for review`,
    'certificate.approved':                 (n) => `${n} certificate${n > 1 ? 's' : ''} approved`,
    'certificate.rejected':                 (n) => `${n} certificate${n > 1 ? 's' : ''} rejected`,
};

function groupNotifications(list: AppNotification[]): NotificationGroup[] {
    const map = new Map<string, NotificationGroup>();
    for (const n of list) {
        const key = `${n.type}::${n.url ?? ''}`;
        if (!map.has(key)) {
            map.set(key, { type: n.type, url: n.url, items: [], unreadCount: 0, latestAt: n.created_at });
        }
        const g = map.get(key)!;
        g.items.push(n);
        if (!n.is_read) g.unreadCount++;
    }
    return [...map.values()].sort(
        (a, b) => new Date(b.latestAt).getTime() - new Date(a.latestAt).getTime(),
    );
}

function groupMessage(group: NotificationGroup): string {
    if (group.items.length === 1) return group.items[0].message;
    const fn = GROUP_LABEL[group.type];
    return fn ? fn(group.items.length) : `${group.items.length} notifications`;
}

function relativeTime(iso: string): string {
    const ms = Date.now() - new Date(iso).getTime();
    const secs = Math.floor(ms / 1000);
    if (secs < 60) return `${secs}s ago`;
    const mins = Math.floor(secs / 60);
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;
    return new Date(iso).toLocaleDateString('en-MY', { day: '2-digit', month: 'short' });
}

export default function NotificationsIndex({ notification_list: notifications }: Props) {
    const groups = groupNotifications(notifications);
    const unreadCount = notifications.filter((n) => !n.is_read).length;

    function handleGroupClick(group: NotificationGroup) {
        const ids = group.items.map((n) => n.Notification_id);
        if (group.unreadCount > 0) {
            router.post(
                route('notifications.mark-read'),
                { ids },
                {
                    preserveScroll: true,
                    onSuccess: () => { if (group.url) router.visit(group.url); },
                },
            );
        } else if (group.url) {
            router.visit(group.url);
        }
    }

    function markAllRead() {
        router.post(route('notifications.mark-read'), {}, { preserveScroll: true });
    }

    function clearAll() {
        if (!confirm('Delete all notifications?')) return;
        router.delete(route('notifications.clear'), { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title="Notifications" />
            <div className="mx-auto w-full max-w-4xl flex flex-1 flex-col px-6 py-8 sm:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Bell className="h-6 w-6 text-gray-500" />
                        <h1 className="text-2xl font-bold text-gray-900">Notifications</h1>
                        {unreadCount > 0 && (
                            <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                                {unreadCount} new
                            </span>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={markAllRead}
                            disabled={unreadCount === 0}
                            className="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40"
                        >
                            <CheckCheck className="h-3.5 w-3.5" /> Mark all read
                        </button>
                        <button
                            onClick={clearAll}
                            disabled={notifications.length === 0}
                            className="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-40"
                        >
                            <Trash2 className="h-3.5 w-3.5" /> Clear all
                        </button>
                    </div>
                </div>

                {groups.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-12 flex-1 flex flex-col items-center justify-center">
                        <Bell className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <p className="text-gray-500">You're all caught up.</p>
                    </div>
                ) : (
                    <ul className="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        {groups.map((group, i) => {
                            const isUnread = group.unreadCount > 0;
                            const isClickable = group.url || isUnread;
                            return (
                                <li
                                    key={i}
                                    onClick={() => handleGroupClick(group)}
                                    className={`flex items-start gap-3 px-4 py-3 transition-colors ${
                                        isClickable ? 'cursor-pointer hover:bg-gray-50' : ''
                                    } ${isUnread ? 'bg-blue-50/40' : ''}`}
                                >
                                    <span className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${
                                        isUnread ? 'bg-blue-500' : 'bg-gray-300'
                                    }`} />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <p className={`text-sm ${isUnread ? 'font-medium text-gray-900' : 'text-gray-600'}`}>
                                                {groupMessage(group)}
                                            </p>
                                            {group.items.length > 1 && (
                                                <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                                    {group.items.length}
                                                </span>
                                            )}
                                        </div>
                                        <p className="mt-0.5 text-xs text-gray-400">
                                            {relativeTime(group.latestAt)}
                                        </p>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </AppLayout>
    );
}
