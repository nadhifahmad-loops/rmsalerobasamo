import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Armchair, BarChart3, BookOpen, ClipboardList, History, LayoutGrid, Settings, ShoppingBag, UtensilsCrossed, Wallet } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const user = usePage<SharedData>().props.auth.user;

    const mainNavItems: NavItem[] = [
        {
            title: 'Beranda',
            url: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Meja',
            url: '/tables',
            icon: Armchair,
        },
        {
            title: 'Pesanan',
            url: '/orders',
            icon: ClipboardList,
        },
        {
            title: 'Takeaway',
            url: '/takeaway',
            icon: ShoppingBag,
        },
        {
            title: 'Riwayat',
            url: '/orders?view=history',
            icon: History,
        },
        {
            title: 'Shift',
            url: '/shifts',
            icon: Wallet,
        },
        {
            title: 'Katalog',
            url: '/catalog',
            icon: BookOpen,
        },
        ...(user?.permissions['manage-catalog']
            ? [
                  {
                      title: 'Menu & Kategori',
                      url: '/products',
                      icon: UtensilsCrossed,
                  },
              ]
            : []),
        ...(user?.permissions['view-reports']
            ? [
                  {
                      title: 'Laporan',
                      url: '/reports',
                      icon: BarChart3,
                  },
              ]
            : []),
        {
            title: 'Pengaturan',
            url: '/settings/profile',
            icon: Settings,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
