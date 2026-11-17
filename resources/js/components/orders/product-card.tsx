import { ProductImage } from '@/components/catalog/product-image';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Product, ProductVariant } from '@/types/catalog';
import { Plus } from 'lucide-react';

interface ProductCardProps {
    product: Product;
    variants: ProductVariant[];
    disabled: boolean;
    addingVariant: number | null;
    onAdd: (productId: number, variantId: number) => void;
    onRequestVariants: (product: Product) => void;
}

/** Kartu katalog POS. Foto menjadi fokus, harga dan nama tetap terbaca. */
export function ProductCard({ product, variants, disabled, addingVariant, onAdd, onRequestVariants }: ProductCardProps) {
    const soldOut = !product.is_available || variants.length === 0;
    const locked = disabled || soldOut;
    const lowestPrice = variants.length > 0 ? Math.min(...variants.map((variant) => variant.price)) : null;

    const activate = () => {
        if (locked) {
            return;
        }

        if (variants.length > 1) {
            onRequestVariants(product);

            return;
        }

        if (variants.length === 1) {
            onAdd(product.id, variants[0].id);
        }
    };

    return (
        <article className="bg-surface flex min-w-0 flex-col overflow-hidden rounded-xl border">
            <button type="button" className="block w-full text-left disabled:cursor-not-allowed" disabled={locked} onClick={activate}>
                <ProductImage src={product.image_url} alt={product.name} className="aspect-[4/3] w-full" />

                <div className="space-y-1 p-3">
                    <div className="flex items-start justify-between gap-2">
                        <p className="text-muted-foreground truncate text-[11px] font-medium tracking-wide uppercase">
                            {product.category?.name ?? 'Tanpa kategori'}
                        </p>
                        <span className="text-muted-foreground inline-flex shrink-0 items-center gap-1 text-[11px]">
                            <span className={cn('size-1.5 rounded-full', soldOut ? 'bg-destructive' : 'bg-success')} aria-hidden="true" />
                            {soldOut ? 'Habis' : 'Tersedia'}
                        </span>
                    </div>

                    <h3 className="truncate text-sm font-semibold">{product.name}</h3>
                    <p className="text-muted-foreground text-xs">{product.unit}</p>

                    <p className="text-brand mt-2 text-sm font-semibold">
                        {lowestPrice === null ? '-' : variants.length > 1 ? `Mulai ${formatRupiah(lowestPrice)}` : formatRupiah(lowestPrice)}
                    </p>
                    {variants.length > 1 && <p className="text-muted-foreground text-[11px]">{variants.length} variasi harga</p>}
                </div>
            </button>

            <div className="mt-auto border-t p-3">
                <Button
                    type="button"
                    size="sm"
                    className="w-full"
                    disabled={locked}
                    onClick={activate}
                    aria-label={
                        variants.length > 1
                            ? `Pilih variasi ${product.name}`
                            : variants.length === 1
                              ? `Tambah ${product.name}`
                              : `${product.name} habis`
                    }
                >
                    {variants.length > 1 ? (
                        'Pilih variasi'
                    ) : variants.length === 1 && addingVariant === variants[0].id ? (
                        'Menambah…'
                    ) : (
                        <>
                            <Plus aria-hidden="true" />
                            {soldOut ? 'Habis' : 'Tambah'}
                        </>
                    )}
                </Button>
            </div>
        </article>
    );
}
