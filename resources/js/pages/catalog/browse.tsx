import { CatalogFilters } from '@/components/catalog/catalog-filters';
import { Pagination } from '@/components/catalog/pagination';
import { ProductImage } from '@/components/catalog/product-image';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import type { Category, CatalogFilters as Filters, Paginated, Product } from '@/types/catalog';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Katalog', href: '/catalog' }];

interface BrowseProps {
    products: Paginated<Product>;
    categories: Category[];
    filters: Filters;
}

export default function Browse({ products, categories, filters }: BrowseProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Katalog" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Katalog menu</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Menu yang tersedia untuk dipesan. Harga mengikuti data master terbaru.</p>
                </div>

                <CatalogFilters url="/catalog" categories={categories} filters={filters} />

                {products.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <h2 className="font-medium">Menu tidak ditemukan</h2>
                        <p className="text-muted-foreground mt-1 text-sm">Ubah kata kunci atau filter kategori untuk melihat menu lain.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        {products.data.map((product) => (
                            <article key={product.id} className="flex flex-col overflow-hidden rounded-xl border">
                                <ProductImage src={product.image_url} alt={product.name} className="aspect-[4/3] w-full border-b" />
                                <div className="p-4">
                                    <p className="text-muted-foreground text-xs">{product.category?.name ?? 'Tanpa kategori'}</p>
                                    <h2 className="mt-1 font-semibold">{product.name}</h2>
                                    <p className="text-muted-foreground mt-1 text-xs">Satuan: {product.unit}</p>
                                    {product.description && <p className="text-muted-foreground mt-2 line-clamp-2 text-sm">{product.description}</p>}
                                </div>

                                <div className="flex-1 divide-y p-4">
                                    {(product.variants ?? []).map((variant) => (
                                        <div key={variant.id} className="flex items-center justify-between gap-3 py-2 first:pt-0 last:pb-0">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {variant.name}
                                                    {variant.is_default && (
                                                        <span className="text-muted-foreground ml-1 text-xs font-normal">(default)</span>
                                                    )}
                                                </p>
                                            </div>
                                            <span className="text-sm font-semibold whitespace-nowrap">{formatRupiah(variant.price)}</span>
                                        </div>
                                    ))}
                                </div>
                            </article>
                        ))}
                    </div>
                )}

                <Pagination links={products.links} />

                <p className="text-muted-foreground text-xs">Harga pada pesanan yang sudah dibuat tidak berubah bila harga master diperbarui.</p>
            </div>
        </AppLayout>
    );
}
