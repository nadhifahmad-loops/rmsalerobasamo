import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Armchair, BarChart3, ClipboardList, History, Home, Settings } from 'lucide-react';

interface MobileNavItem {
    title: string;
    url: string;
    icon: typeof Home;
}

/**
 * Bottom navigation untuk layar sentuh (< 768px).
 * Fokus pada alur kasir: meja, pesanan, dan akun. Menu kelola hanya muncul
 * untuk role dengan permission manage-catalog.
 * Menggunakan safe-area inset untuk perangkat dengan notch.
 */
export function MobileBottomNav({ className }: { className?: string }) {
    const { url, props } = usePage<SharedData>();
    const user = props.auth.user;

    // Maksimal lima tujuan agar area sentuh tetap lega. Riwayat/Takeaway/Shift
    // tetap dapat diakses dari halaman Pesanan dan sidebar.
    const navItems: MobileNavItem[] = [
        { title: 'Beranda', url: '/dashboard', icon: Home },
        { title: 'Meja', url: '/tables', icon: Armchair },
        { title: 'Pesanan', url: '/orders', icon: ClipboardList },
        ...(user?.permissions['view-reports']
            ? [{ title: 'Laporan', url: '/reports', icon: BarChart3 }]
            : [{ title: 'Riwayat', url: '/orders?view=history', icon: History }]),
        { title: 'Akun', url: '/settings/profile', icon: Settings },
    ];

    return (
        <nav
            aria-label="Navigasi utama"
            className={cn('bg-background fixed inset-x-0 bottom-0 z-40 border-t md:hidden', 'pb-[env(safe-area-inset-bottom)]', className)}
        >
            <div className="grid" style={{ gridTemplateColumns: `repeat(${navItems.length}, minmax(0, 1fr))` }}>
                {navItems.map((item) => {
                    const isActive = url === item.url || (item.url !== '/dashboard' && url.startsWith(item.url));

                    return (
                        <Link
                            key={item.url}
                            href={item.url}
                            prefetch
                            aria-current={isActive ? 'page' : undefined}
                            className={cn(
                                'flex min-h-16 flex-col items-center justify-center gap-1 px-2 text-center',
                                'focus-visible:ring-ring rounded-t-md focus-visible:ring-2 focus-visible:outline-none',
                                isActive ? 'text-foreground font-medium' : 'text-muted-foreground',
                            )}
                        >
                            <item.icon className="h-5 w-5" aria-hidden />
                            <span className="text-xs">{item.title}</span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
