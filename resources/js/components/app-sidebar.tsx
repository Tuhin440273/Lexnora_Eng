import { Link, usePage } from '@inertiajs/react';
import {
    AudioLines,
    BookOpen,
    FolderGit2,
    GraduationCap,
    LayoutGrid,
    ShieldCheck,
    Tags,
} from 'lucide-react';
import AudioLibraryController from '@/actions/App/Modules/Admin/Http/Controllers/AudioLibraryController';
import CategoryController from '@/actions/App/Modules/Admin/Http/Controllers/CategoryController';
import CourseController from '@/actions/App/Modules/Admin/Http/Controllers/CourseController';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Overview',
        href: DashboardController.index(),
        icon: ShieldCheck,
    },
    {
        title: 'Courses',
        href: CourseController.index(),
        icon: GraduationCap,
        matchChildren: true,
    },
    {
        title: 'Categories',
        href: CategoryController.index(),
        icon: Tags,
    },
    {
        title: 'Audio',
        href: AudioLibraryController.index(),
        icon: AudioLines,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/Tuhin440273/Lexnora_Eng',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {auth.can_access_admin && (
                    <NavMain items={adminNavItems} label="Content admin" />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
