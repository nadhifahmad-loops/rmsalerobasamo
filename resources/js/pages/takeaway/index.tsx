import { FlashMessage } from '@/components/flash-message';
import { ProductGrid } from '@/components/orders/product-grid';
import { TakeawayCart } from '@/components/takeaway/takeaway-cart';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { TakeawayCartItem, TakeawayFormValue, TakeawayPageProps } from '@/types/takeaway';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pesanan', href: '/orders' },
    { title: 'Takeaway', href: '/takeaway' },
];

const MAX_QUANTITY = 100;

export default function TakeawayIndex({ products, categories, filters, activeShift }: TakeawayPageProps) {
    const form = useForm<TakeawayFormValue>({ notes: '', items: [] });
    const serverErrors = form.errors as Partial<Record<string, string>>;
    const itemError = Object.entries(serverErrors).find(([key]) => key.startsWith('items'))?.[1];
    const cartErrors: Partial<Record<string, string>> = { ...serverErrors, items: itemError };

    const addItem = (productId: number, variantId: number) => {
        const product = products.data.find((entry) => entry.id === productId);
        const variant = product?.variants?.find((entry) => entry.id === variantId);

        if (!product || !variant) {
            return;
        }

        const items = [...form.data.items];
        const index = items.findIndex((item) => item.product_variant_id === variantId);

        if (index >= 0) {
            items[index] = { ...items[index], quantity: Math.min(MAX_QUANTITY, items[index].quantity + 1) };
        } else {
            items.push({
                product_id: product.id,
                product_variant_id: variant.id,
                product_name: product.name,
                variant_name: variant.name,
                unit: product.unit,
                price: variant.price,
                quantity: 1,
                notes: '',
                image_url: product.image_url,
            });
        }

        form.setData('items', items);
    };

    const changeQuantity = (index: number, delta: number) => {
        const items = [...form.data.items];
        items[index] = { ...items[index], quantity: Math.min(MAX_QUANTITY, Math.max(1, items[index].quantity + delta)) };
        form.setData('items', items);
    };

    const removeItem = (index: number) => {
        form.setData(
            'items',
            form.data.items.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const changeItemNotes = (index: number, value: string) => {
        const items = [...form.data.items];
        items[index] = { ...items[index], notes: value };
        form.setData('items', items);
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/takeaway', { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Takeaway" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4 pb-28 md:p-6 md:pb-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Takeaway</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Pilih menu, lalu checkout untuk membuat pesanan tanpa meja.</p>
                </div>

                <FlashMessage />

                {!activeShift && (
                    <div className="border-warning/50 bg-warning/5 rounded-xl border p-3 text-sm">
                        Belum ada shift aktif. Pesanan tetap dapat dibuat dan akan dicatat tanpa shift.
                    </div>
                )}

                <div className="grid min-w-0 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_400px]">
                    <ProductGrid url="/takeaway" products={products} categories={categories} filters={filters} onAdd={addItem} />

                    <aside className="lg:sticky lg:top-4">
                        <TakeawayCart
                            items={form.data.items as TakeawayCartItem[]}
                            notes={form.data.notes}
                            submitting={form.processing}
                            errors={cartErrors}
                            onQuantityChange={changeQuantity}
                            onRemove={removeItem}
                            onItemNotesChange={changeItemNotes}
                            onNotesChange={(value) => form.setData('notes', value)}
                            onSubmit={submit}
                        />
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}
