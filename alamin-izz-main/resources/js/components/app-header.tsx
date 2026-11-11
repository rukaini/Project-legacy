import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { UserMenuContent } from '@/components/user-menu-content';
import { useRole } from '@/lib/auth';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { type PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Award, Bell, BookOpen, ClipboardList, GraduationCap, HeartHandshake, History, LayoutDashboard, Menu, TrendingUp, Users } from 'lucide-react';
import { type LucideIcon } from 'lucide-react';
import AppLogo from './app-logo';

interface NavLink {
    title: string;
    url: string;
    icon: LucideIcon;
}

function useNavItems(): NavLink[] {
    const { isGuruBesar, isGuruBaru, isTeacher, isHR, isStaffEdu } = useRole();

    if (isStaffEdu) {
        return [{ title: 'Trainings', url: route('trainings.index'), icon: BookOpen }];
    }

    if (isHR) {
        return [
            { title: 'Dashboard',    url: route('hr.dashboard'),          icon: LayoutDashboard },
            { title: 'Applications', url: route('hr.applications.index'), icon: ClipboardList },
            { title: 'Teachers',     url: route('hr.teachers.index'),     icon: GraduationCap },
            { title: 'Outsiders',    url: route('hr.outsiders.index'),    icon: Users },
            { title: 'Guru Baru',    url: route('hr.guru-baru.index'),    icon: BookOpen },
        ];
    }

    if (isGuruBesar) {
        return [
            { title: 'Dashboard',    url: route('dashboard.guru-besar'), icon: LayoutDashboard },
            { title: 'Trainings',    url: route('trainings.index'),       icon: BookOpen },
            { title: 'Penyelaras Tarbiah', url: route('tarbiah.index'),    icon: HeartHandshake },
            { title: 'Penyelaras Ladap', url: route('penyelaras-ladap.index'), icon: Users },
            { title: 'Certificate',  url: route('certificates.school'),   icon: Award },
            { title: 'Applications', url: route('applications.index'),    icon: ClipboardList },
            { title: 'Overview',     url: route('reports.development'),   icon: TrendingUp },
        ];
    }

    if (isGuruBaru) {
        return [
            { title: 'Dashboard',       url: route('dashboard.guru-baru'),             icon: LayoutDashboard },
            { title: 'Trainings',       url: route('trainings.index'),                 icon: BookOpen },
            { title: 'My Trainings',    url: route('guru_baru.courses.mine'),          icon: GraduationCap },
            { title: 'My Applications', url: route('guru_baru.applications.mine'),     icon: ClipboardList },
            { title: 'My History',      url: route('history.mine'),                    icon: History },
        ];
    }

    if (isTeacher) {
        return [
            { title: 'Dashboard',       url: route('dashboard'),           icon: LayoutDashboard },
            { title: 'Trainings',       url: route('trainings.index'),     icon: BookOpen },
            { title: 'Tarbiah',         url: route('tarbiah.index'),          icon: HeartHandshake },
            { title: 'LADAP',           url: route('penyelaras-ladap.index'), icon: Users },
            { title: 'Certificate',     url: route('certificates.index'),  icon: Award },
            { title: 'My Applications', url: route('applications.mine'),   icon: ClipboardList },
            { title: 'My History',      url: route('history.mine'),        icon: History },
        ];
    }

    return [
        { title: 'Dashboard',       url: route('dashboard'),           icon: LayoutDashboard },
        { title: 'Trainings',       url: route('trainings.index'),     icon: BookOpen },
        { title: 'My Applications', url: route('applications.mine'),   icon: ClipboardList },
        { title: 'My History',      url: route('history.mine'),        icon: History },
    ];
}

interface AppHeaderProps {
    breadcrumbs?: unknown[];
}

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
    const { auth, notifications } = usePage<PageProps>().props;
    const getInitials = useInitials();
    const navItems = useNavItems();
    const { isGuruBesar, isGuruBaru, isHR, isStaffEdu } = useRole();
    const page = usePage();
    const fromParam = page.url.includes('from=my_courses')      ? 'my_courses'
                    : page.url.includes('from=my_applications') ? 'my_applications'
                    : page.url.includes('from=applications')    ? 'applications'
                    : null;

    const isNavActive = (item: NavLink): boolean => {
        if (fromParam === 'my_courses')      return item.url === route('guru_baru.courses.mine');
        if (fromParam === 'my_applications') return item.url === route('guru_baru.applications.mine');
        if (fromParam === 'applications')    return item.url === route('applications.index') || item.url === route('applications.mine');
        const itemPath    = item.url.replace(window.location.origin, '');
        const currentPath = page.url.replace(window.location.origin, '').split('?')[0];
        return currentPath.startsWith(itemPath) && itemPath !== '/';
    };

    if (!auth.user) return null;

    return (
        <div className="sticky top-0 z-50 h-[58px] border-b border-border bg-white/96 shadow-[0_1px_10px_rgba(15,23,42,0.04)] backdrop-blur-[10px]">
            <div className="mx-auto flex h-full max-w-[1880px] items-center gap-4 px-4 sm:px-[clamp(16px,2.25vw,42px)]">

                {/* Mobile menu */}
                <div className="lg:hidden">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-9 w-9">
                                <Menu className="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-64">
                            <SheetTitle className="sr-only">Navigation</SheetTitle>
                            <SheetHeader className="mb-6">
                                <AppLogo />
                            </SheetHeader>
                            <nav className="flex flex-col gap-1">
                                {navItems.map((item) => {
                                    const active = isNavActive(item);
                                    return (
                                        <Link
                                            key={item.url}
                                            href={item.url}
                                            className={cn(
                                                'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium',
                                                active ? 'bg-accent text-primary' : 'text-gray-700 hover:bg-gray-100',
                                            )}
                                        >
                                            <item.icon className={cn('h-4 w-4 shrink-0', active ? 'text-primary' : 'text-gray-400')} />
                                            {item.title}
                                        </Link>
                                    );
                                })}
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>

                {/* Logo */}
                <Link href={isGuruBesar ? route('dashboard.guru-besar') : isGuruBaru ? route('dashboard.guru-baru') : isHR ? route('hr.dashboard') : route('dashboard')}>
                    <AppLogo />
                </Link>

                {/* Desktop nav */}
                <nav className="ml-6 hidden h-full items-center gap-1 lg:flex">
                    {navItems.map((item) => {
                        const active = isNavActive(item);
                        return (
                            <Link
                                key={item.url}
                                href={item.url}
                                className={cn(
                                    'relative flex h-full items-center gap-2 border-b-2 border-transparent px-3 text-sm font-semibold transition-colors',
                                    active ? 'border-primary bg-accent text-primary' : 'text-gray-600 hover:bg-muted hover:text-gray-900',
                                )}
                            >
                                <item.icon className={cn('h-4 w-4 shrink-0', active ? 'text-primary' : 'text-gray-400')} />
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>

                {/* Right side */}
                <div className="ml-auto flex items-center gap-2">
                    {/* Notification bell */}
                    {!isStaffEdu && <Link href={route('notifications.index')} className="relative">
                        <Button variant="ghost" size="icon" className="h-9 w-9">
                            <Bell className="h-5 w-5 text-gray-600" />
                        </Button>
                        {notifications.unread_count > 0 && (
                            <span className="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-white">
                                {notifications.unread_count > 9 ? '9+' : notifications.unread_count}
                            </span>
                        )}
                    </Link>}

                    {/* User avatar dropdown */}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="flex items-center gap-2 rounded-full px-2">
                                <Avatar className="size-8">
                                    <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                                    <AvatarFallback className="bg-accent text-primary text-xs font-semibold">
                                        {getInitials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <span className="hidden text-sm font-medium text-gray-700 lg:block">
                                    {auth.user.name}
                                </span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="w-56" align="end">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>
    );
}
