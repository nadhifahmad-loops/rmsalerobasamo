import { ConfirmDialog } from '@/components/catalog/confirm-dialog';
import { Pagination } from '@/components/catalog/pagination';
import { FlashMessage } from '@/components/flash-message';
import { DiningTableFormDialog } from '@/components/orders/dining-table-form-dialog';
import { TableStatusLabel } from '@/components/orders/table-status-label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import type { BreadcrumbItem, SharedData } from '@/types';
import type { DiningTable, TablesPageProps } from '@/types/orders';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Meja', href: '/tables' }];

export default function Tables({ tables, filters }: TablesPageProps) {
    const canManage = usePage<SharedData>().props.auth.user?.permissions['manage-tables'] ?? false;
    const [search, setSearch] = useState(filters.search);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<DiningTable | null>(null);
    const [deleting, setDeleting] = useState<DiningTable | null>(null);
    const [processing, setProcessing] = useState(false);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get('/tables', { search: search || undefined }, { preserveState: true, preserveScroll: true });
    };

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        setProcessing(true);
        router.delete(`/tables/${deleting.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Meja" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Meja</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Status dihitung otomatis dari pesanan aktif pada meja.</p>
                    </div>
                    {canManage && (
                        <Button
                            onClick={() => {
                                setEditing(null);
                                setDialogOpen(true);
                            }}
                        >
                            <Plus aria-hidden="true" />
                            Tambah meja
                        </Button>
                    )}
                </div>

                <FlashMessage />

                <form onSubmit={submitSearch} role="search" className="rounded-xl border p-3">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="space-y-1.5 sm:max-w-xs sm:flex-1">
                            <Label htmlFor="table-search">Cari meja</Label>
                            <div className="relative">
                                <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" aria-hidden="true" />
                                <Input
                                    id="table-search"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Nomor atau label meja"
                                    className="pl-9"
                                    autoComplete="off"
                                />
                            </div>
                        </div>
                        <Button type="submit" variant="outline" className="min-h-10">
                            Cari
                        </Button>
                    </div>
                </form>

                {tables.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <h2 className="font-medium">Belum ada meja</h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {filters.search ? 'Coba kata kunci lain.' : 'Tambahkan meja agar kasir dapat membuka pesanan.'}
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
                        {tables.data.map((table) => {
                            const canOpen = table.is_active && table.status === 'AVAILABLE';
                            const canContinue = table.active_order_id !== null;

                            return (
                                <article key={table.id} className="flex min-w-0 flex-col justify-between gap-4 rounded-xl border p-4">
                                    <div className="min-w-0">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="text-muted-foreground text-xs">Meja</p>
                                                <h2 className="truncate text-lg font-semibold">{table.display_label}</h2>
                                            </div>
                                            <TableStatusLabel status={table.status} isActive={table.is_active} />
                                        </div>
                                        {table.label && <p className="text-muted-foreground mt-1 truncate text-xs">Nomor {table.table_number}</p>}
                                        {canContinue && (
                                            <p className="text-muted-foreground mt-3 text-xs">
                                                {table.active_order_number} · {formatRupiah(table.running_total)}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex flex-col gap-2">
                                        {canOpen ? (
                                            <Link
                                                href="/orders"
                                                method="post"
                                                data={{ dining_table_id: table.id }}
                                                preserveScroll
                                                className="bg-primary text-primary-foreground focus-visible:ring-ring inline-flex min-h-10 items-center justify-center gap-2 rounded-md px-3 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                                            >
                                                Buka pesanan
                                                <ArrowRight className="size-4" aria-hidden="true" />
                                            </Link>
                                        ) : canContinue ? (
                                            <Link
                                                href={`/orders/${table.active_order_id}`}
                                                className="border-input focus-visible:ring-ring inline-flex min-h-10 items-center justify-center gap-2 rounded-md border px-3 text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                                            >
                                                Buka pesanan
                                                <ArrowRight className="size-4" aria-hidden="true" />
                                            </Link>
                                        ) : (
                                            <p className="text-muted-foreground text-xs">Tidak tersedia untuk pesanan baru.</p>
                                        )}

                                        {canManage && (
                                            <div className="flex gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="min-h-9 flex-1"
                                                    onClick={() => {
                                                        setEditing(table);
                                                        setDialogOpen(true);
                                                    }}
                                                >
                                                    <Pencil aria-hidden="true" />
                                                    Edit
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="text-destructive min-h-9 flex-1"
                                                    onClick={() => setDeleting(table)}
                                                >
                                                    <Trash2 aria-hidden="true" />
                                                    Hapus
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}

                <Pagination links={tables.links} />

                {dialogOpen && (
                    <DiningTableFormDialog key={editing?.id ?? 'new'} open onOpenChange={(open) => !open && setDialogOpen(false)} table={editing} />
                )}

                <ConfirmDialog
                    open={deleting !== null}
                    onOpenChange={(open) => !open && setDeleting(null)}
                    title="Hapus meja?"
                    description={`Meja ${deleting?.display_label ?? ''} akan dihapus. Meja dengan riwayat pesanan tidak dapat dihapus.`}
                    confirmLabel="Hapus meja"
                    destructive
                    processing={processing}
                    onConfirm={confirmDelete}
                />
            </div>
        </AppLayout>
    );
}
