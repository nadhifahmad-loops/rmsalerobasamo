import { ProductGrid } from '@/components/orders/product-grid';
import type { Category, CatalogFilters as Filters, Paginated, Product } from '@/types/catalog';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface ProductPickerProps {
    orderId: number;
    products: Paginated<Product>;
    categories: Category[];
    filters: Filters;
    canEdit: boolean;
}

/** Katalog POS: filter server-side dan tombol variasi yang langsung menambah item. */
export function ProductPicker({ orderId, products, categories, filters, canEdit }: ProductPickerProps) {
    const [addingVariant, setAddingVariant] = useState<number | null>(null);

    const addItem = (productId: number, variantId: number) => {
        setAddingVariant(variantId);

        router.post(
            `/orders/${orderId}/items`,
            { product_id: productId, product_variant_id: variantId, quantity: 1 },
            {
                preserveScroll: true,
                onFinish: () => setAddingVariant(null),
            },
        );
    };

    return (
        <ProductGrid
            url={`/orders/${orderId}`}
            products={products}
            categories={categories}
            filters={filters}
            onAdd={addItem}
            disabled={!canEdit}
            addingVariant={addingVariant}
        />
    );
}
