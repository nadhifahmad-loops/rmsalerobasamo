import { CategoryFormDialog } from '@/components/catalog/category-form-dialog';
import { ConfirmDialog } from '@/components/catalog/confirm-dialog';
import { Pagination } from '@/components/catalog/pagination';
import { AvailabilityLabel } from '@/components/catalog/status-label';
import { FlashMessage } from '@/components/flash-message';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { Category, Paginated } from '@/types/catalog';
import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Power, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Kategori', href: '/categories' }];

type CategoryRow = Category & { products_count: number };

interface CategoriesProps {
    categories: Paginated<CategoryRow>;
}

export default function Categories({ categories }: CategoriesProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<Category | null>(null);
    const [deleting, setDeleting] = useState<Category | null>(null);
    const [processing, setProcessing] = useState(false);

    const toggleStatus = (category: Category) => {
        router.put(
            `/categories/${category.id}`,
            { name: category.name, sort_order: category.sort_order, is_active: !category.is_active },
            { preserveScroll: true },
        );
    };

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        setProcessing(true);
        router.delete(`/categories/${deleting.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kategori" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Kategori</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Kelola pengelompokan menu yang ditampilkan di POS.</p>
                    </div>
                    <Button
                        onClick={() => {
                            setEditing(null);
                            setDialogOpen(true);
                        }}
                    >
                        <Plus aria-hidden="true" />
                        Tambah kategori
                    </Button>
                </div>

                <FlashMessage />

                {categories.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <h2 className="font-medium">Belum ada kategori</h2>
                        <p className="text-muted-foreground mt-1 text-sm">Tambah kategori sebelum membuat produk.</p>
                    </div>
                ) : (
                    <div className="divide-y rounded-xl border">
                        {categories.data.map((category) => (
                            <div key={category.id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="min-w-0">
                                    <p className="font-medium">{category.name}</p>
                                    <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                                        <AvailabilityLabel isActive={category.is_active} isAvailable />
                                        <span className="text-muted-foreground text-xs">Urutan {category.sort_order}</span>
                                        <span className="text-muted-foreground text-xs">{category.products_count} produk</span>
                                    </div>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            setEditing(category);
                                            setDialogOpen(true);
                                        }}
                                    >
                                        <Pencil aria-hidden="true" />
                                        Edit
                                    </Button>
                                    <Button type="button" variant="outline" size="sm" onClick={() => toggleStatus(category)}>
                                        <Power aria-hidden="true" />
                                        {category.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setDeleting(category)}
                                        className="text-destructive"
                                    >
                                        <Trash2 aria-hidden="true" />
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination links={categories.links} />
            </div>

            <CategoryFormDialog key={editing?.id ?? 'new'} open={dialogOpen} onOpenChange={setDialogOpen} category={editing} />

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus kategori?"
                description={`Kategori "${deleting?.name ?? ''}" akan dihapus. Kategori yang masih dipakai produk tidak dapat dihapus.`}
                confirmLabel="Hapus kategori"
                destructive
                processing={processing}
                onConfirm={confirmDelete}
            />
        </AppLayout>
    );
}
