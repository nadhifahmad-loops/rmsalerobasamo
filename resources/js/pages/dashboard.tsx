import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { BarChart3, ClipboardList, History, ShoppingBag, Wallet } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Beranda',
        href: '/dashboard',
    },
];

export default function Dashboard() {
    const { auth, name } = usePage<SharedData>().props;
    const user = auth.user;

    const roleLabel = user?.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : '-';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Beranda" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Selamat datang, {user?.name ?? ''}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">{name} — aplikasi kasir rumah makan.</p>
                </div>

                <div className="rounded-xl border">
                    <div className="border-b px-4 py-3">
                        <h2 className="text-sm font-medium">Informasi akun</h2>
                    </div>
                    <dl className="divide-y">
                        <div className="flex items-center justify-between gap-4 px-4 py-3">
                            <dt className="text-muted-foreground text-sm">Nama</dt>
                            <dd className="text-sm font-medium">{user?.name}</dd>
                        </div>
                        <div className="flex items-center justify-between gap-4 px-4 py-3">
                            <dt className="text-muted-foreground text-sm">Username</dt>
                            <dd className="text-sm font-medium">{user?.username}</dd>
                        </div>
                        <div className="flex items-center justify-between gap-4 px-4 py-3">
                            <dt className="text-muted-foreground text-sm">Email</dt>
                            <dd className="text-sm font-medium">{user?.email ?? '—'}</dd>
                        </div>
                        <div className="flex items-center justify-between gap-4 px-4 py-3">
                            <dt className="text-muted-foreground text-sm">Role</dt>
                            <dd className="text-sm font-medium">{roleLabel}</dd>
                        </div>
                        <div className="flex items-center justify-between gap-4 px-4 py-3">
                            <dt className="text-muted-foreground text-sm">Status akun</dt>
                            <dd className="text-sm font-medium">
                                <span className={user?.is_active ? 'text-success' : 'text-warning'}>{user?.is_active ? 'Aktif' : 'Nonaktif'}</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button asChild size="sm">
                        <Link href="/tables">Lihat meja</Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href="/orders">
                            <ClipboardList aria-hidden="true" />
                            Pesanan aktif
                        </Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href="/takeaway">
                            <ShoppingBag aria-hidden="true" />
                            Takeaway
                        </Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href="/orders?view=history">
                            <History aria-hidden="true" />
                            Riwayat
                        </Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href="/shifts">
                            <Wallet aria-hidden="true" />
                            Shift
                        </Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href="/catalog">Buka katalog menu</Link>
                    </Button>
                    {user?.permissions['view-reports'] && (
                        <Button asChild variant="outline" size="sm">
                            <Link href="/reports">
                                <BarChart3 aria-hidden="true" />
                                Laporan
                            </Link>
                        </Button>
                    )}
                    {user?.permissions['manage-catalog'] && (
                        <Button asChild variant="outline" size="sm">
                            <Link href="/products">Kelola menu & kategori</Link>
                        </Button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
