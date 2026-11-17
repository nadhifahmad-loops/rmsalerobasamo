import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { MobileBottomNav } from '@/components/mobile-bottom-nav';
import { MobileHeader } from '@/components/mobile-header';
import { type BreadcrumbItem } from '@/types';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="pb-16 md:pb-0">
                <AppSidebarHeader breadcrumbs={breadcrumbs} className="hidden md:flex" />
                <MobileHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <MobileBottomNav />
        </AppShell>
    );
}
