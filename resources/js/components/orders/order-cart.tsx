import { ConfirmDialog } from '@/components/catalog/confirm-dialog';
import { ProductImage } from '@/components/catalog/product-image';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/format';
import type { Order, OrderItem } from '@/types/orders';
import { router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { OrderItemDialog } from './order-item-dialog';

interface OrderCartProps {
    order: Order;
    canEdit: boolean;
    productImages: Record<number, string | null>;
}

/** Daftar item pesanan. Perubahan dan penghapusan selalu melalui backend. */
export function OrderCart({ order, canEdit, productImages }: OrderCartProps) {
    const [editing, setEditing] = useState<OrderItem | null>(null);
    const [deleting, setDeleting] = useState<OrderItem | null>(null);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        setProcessing(true);
        router.delete(`/orders/${order.id}/items/${deleting.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <section id="cart" aria-label="Item pesanan" className="flex flex-col gap-3">
            <div className="flex items-center justify-between gap-3">
                <h2 className="font-medium">Item pesanan</h2>
                <span className="text-muted-foreground text-xs">{order.items.length} item</span>
            </div>

            {order.items.length === 0 ? (
                <div className="rounded-lg border border-dashed p-6 text-center">
                    <p className="font-medium">Belum ada item</p>
                    <p className="text-muted-foreground mt-1 text-xs">Pilih menu dan variasi untuk menambah pesanan.</p>
                </div>
            ) : (
                <div className="divide-y rounded-xl border">
                    {order.items.map((item) => (
                        <div key={item.id} className="flex gap-3 p-3">
                            <ProductImage
                                src={item.product_id ? (productImages[item.product_id] ?? null) : null}
                                alt={item.product_name_snapshot}
                                className="size-12 shrink-0 rounded-md border"
                            />

                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium">{item.product_name_snapshot}</p>
                                <p className="text-muted-foreground mt-0.5 text-xs">
                                    {item.variant_name_snapshot} · {formatRupiah(item.price_snapshot)} × {item.quantity} {item.unit_snapshot}
                                </p>
                                {item.notes && <p className="text-muted-foreground mt-1 text-xs">Catatan: {item.notes}</p>}
                            </div>

                            <div className="flex shrink-0 flex-col items-end gap-2">
                                <span className="text-sm font-semibold whitespace-nowrap">{formatRupiah(item.subtotal)}</span>
                                <div className="flex gap-1">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-9"
                                        disabled={!canEdit}
                                        aria-label={`Ubah ${item.product_name_snapshot}`}
                                        onClick={() => setEditing(item)}
                                    >
                                        <Pencil className="size-4" aria-hidden="true" />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="text-destructive size-9"
                                        disabled={!canEdit}
                                        aria-label={`Hapus ${item.product_name_snapshot}`}
                                        onClick={() => setDeleting(item)}
                                    >
                                        <Trash2 className="size-4" aria-hidden="true" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {editing && (
                <OrderItemDialog key={editing.id} open onOpenChange={(open) => !open && setEditing(null)} orderId={order.id} item={editing} />
            )}

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus item?"
                description={`${deleting?.product_name_snapshot ?? ''} akan dihapus dari pesanan ini.`}
                confirmLabel="Hapus item"
                destructive
                processing={processing}
                onConfirm={confirmDelete}
            />
        </section>
    );
}
