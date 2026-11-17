import { CatalogFilters } from '@/components/catalog/catalog-filters';
import { ConfirmDialog } from '@/components/catalog/confirm-dialog';
import { Pagination } from '@/components/catalog/pagination';
import { AvailabilityLabel } from '@/components/catalog/status-label';
import { FlashMessage } from '@/components/flash-message';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import type { Category, CatalogFilters as Filters, Paginated, Product } from '@/types/catalog';
import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Produk', href: '/products' }];

const statusOptions = [
    { value: 'active', label: 'Tersedia' },
    { value: 'inactive', label: 'Nonaktif' },
    { value: 'unavailable', label: 'Habis' },
];

interface ProductsProps {
    products: Paginated<Product>;
    categories: Category[];
    filters: Filters;
}

function priceSummary(product: Product): string {
    const prices = (product.variants ?? []).map((variant) => variant.price);

    if (prices.length === 0) {
        return 'Belum ada variasi';
    }

    if (prices.length === 1) {
        return formatRupiah(prices[0]);
    }

    return `${formatRupiah(Math.min(...prices))} – ${formatRupiah(Math.max(...prices))}`;
}

export default function Products({ products, categories, filters }: ProductsProps) {
    const [deleting, setDeleting] = useState<Product | null>(null);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        setProcessing(true);
        router.delete(`/products/${deleting.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Produk" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Produk</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Kelola menu, variasi, harga, dan ketersediaan.</p>
                    </div>
                    <Button asChild>
                        <Link href="/products/create">
                            <Plus aria-hidden="true" />
                            Tambah produk
                        </Link>
                    </Button>
                </div>

                <FlashMessage />

                <CatalogFilters url="/products" categories={categories} filters={filters} statuses={statusOptions} />

                {products.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <h2 className="font-medium">Produk tidak ditemukan</h2>
                        <p className="text-muted-foreground mt-1 text-sm">Tambah produk baru atau ubah kata kunci dan filter yang digunakan.</p>
                    </div>
                ) : (
                    <div className="divide-y rounded-xl border">
                        {products.data.map((product) => (
                            <div key={product.id} className="flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
                                <div className="min-w-0">
                                    <p className="font-medium">{product.name}</p>
                                    <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                                        <span className="text-muted-foreground text-xs">
                                            {product.category?.name ?? 'Tanpa kategori'} · {product.unit} · {product.variants_count ?? 0} variasi
                                        </span>
                                        <AvailabilityLabel isActive={product.is_active} isAvailable={product.is_available} />
                                    </div>
                                    <p className="mt-1 text-sm font-medium">{priceSummary(product)}</p>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={`/products/${product.id}/edit`}>
                                            <Pencil aria-hidden="true" />
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button type="button" variant="ghost" size="sm" className="text-destructive" onClick={() => setDeleting(product)}>
                                        <Trash2 aria-hidden="true" />
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination links={products.links} />
            </div>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus produk?"
                description={`Produk "${deleting?.name ?? ''}" akan disembunyikan dari katalog. Data histori tetap disimpan.`}
                confirmLabel="Hapus produk"
                destructive
                processing={processing}
                onConfirm={confirmDelete}
            />
        </AppLayout>
    );
}
