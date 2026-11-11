import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { type BreadcrumbItem, type PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { CheckCircle, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

interface AppHeaderLayoutProps {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

function FlashBanner() {
    const { flash } = usePage<PageProps>().props;
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (flash?.success || flash?.error) {
            setVisible(true);
            const t = setTimeout(() => setVisible(false), 4000);
            return () => clearTimeout(t);
        }
    }, [flash?.success, flash?.error]);

    if (!visible || (!flash?.success && !flash?.error)) return null;

    const isError = !!flash?.error;
    return (
        <div className={`fixed right-4 top-4 z-50 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium shadow-lg transition-all ${
            isError
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-green-200 bg-green-50 text-green-700'
        }`}>
            {isError
                ? <XCircle className="h-4 w-4 shrink-0" />
                : <CheckCircle className="h-4 w-4 shrink-0" />}
            {flash?.error ?? flash?.success}
        </div>
    );
}

export default function AppHeaderLayout({ children, breadcrumbs }: AppHeaderLayoutProps) {
    return (
        <AppShell>
            <AppHeader breadcrumbs={breadcrumbs} />
            <FlashBanner />
            <AppContent>{children}</AppContent>
        </AppShell>
    );
}
