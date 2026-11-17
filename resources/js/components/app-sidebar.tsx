import { Link, usePage } from '@inertiajs/react';
import {
    AudioLines,
    BookMarked,
    BookOpen,
    ChartLine,
    CircleAlert,
    FolderGit2,
    ChartColumn,
    GraduationCap,
    KeyRound,
    Languages,
    LayoutGrid,
    NotebookPen,
    RotateCw,
    ShieldCheck,
    SlidersHorizontal,
    Tags,
    Users,
    Trophy,
} from 'lucide-react';
import CatalogController from '@/actions/App/Modules/Courses/Http/Controllers/CatalogController';
import MistakeController from '@/actions/App/Modules/Dictation/Http/Controllers/MistakeController';
import NoteController from '@/actions/App/Modules/Learning/Http/Controllers/NoteController';
import ReviewController from '@/actions/App/Modules/Review/Http/Controllers/ReviewController';
import SavedVocabularyController from '@/actions/App/Modules/Vocabulary/Http/Controllers/SavedVocabularyController';
import AnalyticsController from '@/actions/App/Modules/Admin/Http/Controllers/AnalyticsController';
import RoleController from '@/actions/App/Modules/Admin/Http/Controllers/RoleController';
import SettingsController from '@/actions/App/Modules/Admin/Http/Controllers/SettingsController';
import VocabularyController from '@/actions/App/Modules/Admin/Http/Controllers/VocabularyController';
import UserController from '@/actions/App/Modules/Admin/Http/Controllers/UserController';
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
import { analytics, dashboard, leaderboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    {
        title: 'Courses',
        href: CatalogController.index(),
        icon: BookOpen,
        matchChildren: true,
    },
    { title: 'Review', href: ReviewController.index(), icon: RotateCw },
    {
        title: 'Vocabulary',
        href: SavedVocabularyController.index(),
        icon: BookMarked,
    },
    { title: 'Mistakes', href: MistakeController.index(), icon: CircleAlert },
    { title: 'Notes', href: NoteController.index(), icon: NotebookPen },
    { title: 'Progress', href: analytics(), icon: ChartLine },
    { title: 'Leaderboard', href: leaderboard(), icon: Trophy },
];

type AdminNavItem = NavItem & { permission?: string };

const adminNavItems: AdminNavItem[] = [
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
        permission: 'audio.manage',
    },
    {
        title: 'Vocabulary',
        href: VocabularyController.index(),
        icon: Languages,
        permission: 'vocabulary.manage',
    },
    {
        title: 'Users',
        href: UserController.index(),
        icon: Users,
        permission: 'users.manage',
    },
    {
        title: 'Roles',
        href: RoleController.index(),
        icon: KeyRound,
        permission: 'roles.manage',
    },
    {
        title: 'Settings',
        href: SettingsController.edit(),
        icon: SlidersHorizontal,
        permission: 'settings.manage',
    },
    {
        title: 'Analytics',
        href: AnalyticsController(),
        icon: ChartColumn,
        permission: 'analytics.view',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/Tuhin440273/Lexnora_Eng',
        icon: FolderGit2,
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
                    <NavMain
                        items={adminNavItems.filter(
                            (item) =>
                                !item.permission ||
                                auth.permissions.includes(item.permission),
                        )}
                        label="Content admin"
                    />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
