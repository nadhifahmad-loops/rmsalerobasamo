import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem } from '@/types';

/**
 * Header khusus layar sentuh (< 768px): logo, judul halaman, dan tombol menu.
 * Desktop memakai AppSidebarHeader dengan breadcrumbs.
 */
export function MobileHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItem[] }) {
    const title = breadcrumbs.length > 0 ? breadcrumbs[breadcrumbs.length - 1].title : 'Beranda';

    return (
        <header className="bg-background sticky top-0 z-30 flex h-14 shrink-0 items-center gap-3 border-b px-4 md:hidden">
            <SidebarTrigger className="-ml-1" />
            <img
                src="/images/brand/logo-salero-basamo-jati.png"
                alt="Salero Basamo Jati"
                className="h-6 w-auto max-w-[5rem] shrink-0 object-contain"
                draggable={false}
            />
            <span className="text-sm font-semibold" data-testid="mobile-page-title">
                {title}
            </span>
        </header>
    );
}
