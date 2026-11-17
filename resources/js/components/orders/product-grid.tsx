import { CatalogFilters } from '@/components/catalog/catalog-filters';
import { Pagination } from '@/components/catalog/pagination';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { formatRupiah } from '@/lib/format';
import type { Category, CatalogFilters as Filters, Paginated, Product } from '@/types/catalog';
import { useState } from 'react';
import { ProductCard } from './product-card';

interface ProductGridProps {
    url: string;
    products: Paginated<Product>;
    categories: Category[];
    filters: Filters;
    onAdd: (productId: number, variantId: number) => void;
    disabled?: boolean;
    addingVariant?: number | null;
}

/**
 * Katalog POS yang dipakai ulang oleh dine-in dan takeaway. Komponen ini hanya
 * menampilkan data dan memilih variasi; mutasi tetap dilakukan oleh pemanggil.
 */
export function ProductGrid({ url, products, categories, filters, onAdd, disabled = false, addingVariant = null }: ProductGridProps) {
    const [variantProduct, setVariantProduct] = useState<Product | null>(null);

    const chooseVariant = (productId: number, variantId: number) => {
        onAdd(productId, variantId);
        setVariantProduct(null);
    };

    return (
        <section className="flex min-w-0 flex-col gap-4" aria-label="Pilih menu">
            <CatalogFilters url={url} categories={categories} filters={filters} />

            {disabled && <p className="text-muted-foreground text-sm">Pesanan ini sudah terkunci. Item tidak dapat diubah lagi.</p>}

            {products.data.length === 0 ? (
                <div className="rounded-xl border border-dashed p-8 text-center">
                    <h3 className="font-medium">Menu tidak ditemukan</h3>
                    <p className="text-muted-foreground mt-1 text-sm">Ubah kata kunci atau filter kategori.</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {products.data.map((product) => {
                        const variants = (product.variants ?? []).filter((variant) => variant.is_active && variant.is_available);

                        return (
                            <ProductCard
                                key={product.id}
                                product={product}
                                variants={variants}
                                disabled={disabled}
                                addingVariant={addingVariant}
                                onAdd={onAdd}
                                onRequestVariants={setVariantProduct}
                            />
                        );
                    })}
                </div>
            )}

            <Pagination links={products.links} />

            <Dialog open={variantProduct !== null} onOpenChange={(open) => !open && setVariantProduct(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Pilih variasi</DialogTitle>
                        <DialogDescription>{variantProduct?.name ?? ''} — pilih satu variasi sebelum menambah ke pesanan.</DialogDescription>
                    </DialogHeader>

                    <div className="max-h-[60vh] space-y-2 overflow-y-auto pr-1">
                        {(variantProduct?.variants ?? [])
                            .filter((variant) => variant.is_active && variant.is_available)
                            .map((variant) => (
                                <Button
                                    key={variant.id}
                                    type="button"
                                    variant="outline"
                                    className="h-auto w-full justify-between gap-3 py-3 text-left"
                                    disabled={addingVariant === variant.id}
                                    onClick={() => variantProduct && chooseVariant(variantProduct.id, variant.id)}
                                >
                                    <span className="truncate text-sm font-medium">{variant.name}</span>
                                    <span className="text-brand shrink-0 text-sm font-semibold">{formatRupiah(variant.price)}</span>
                                </Button>
                            ))}
                    </div>

                    <p className="text-muted-foreground text-xs">Tidak ada variasi yang dipilih otomatis.</p>
                </DialogContent>
            </Dialog>
        </section>
    );
}
