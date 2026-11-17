import { formatRupiah } from '@/lib/format';
import type { Order } from '@/types/orders';

/** Ringkasan checkout hanya membaca nominal yang sudah dihitung backend. */
export function CheckoutSummary({ order }: { order: Order }) {
    return (
        <section className="flex flex-col gap-4" aria-label="Ringkasan pesanan">
            <div className="rounded-xl border">
                <div className="border-b px-4 py-3">
                    <h2 className="font-medium">Item pesanan</h2>
                </div>

                {order.items.length === 0 ? (
                    <p className="text-muted-foreground p-6 text-center text-sm">Pesanan belum memiliki item.</p>
                ) : (
                    <ul className="divide-y">
                        {order.items.map((item) => (
                            <li key={item.id} className="flex gap-4 p-4">
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">{item.product_name_snapshot}</p>
                                    <p className="text-muted-foreground mt-0.5 text-xs">
                                        {item.variant_name_snapshot} · {formatRupiah(item.price_snapshot)} × {item.quantity} {item.unit_snapshot}
                                    </p>
                                    {item.notes && <p className="text-muted-foreground mt-1 text-xs">Catatan: {item.notes}</p>}
                                </div>
                                <span className="shrink-0 text-sm font-semibold whitespace-nowrap">{formatRupiah(item.subtotal)}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <dl className="rounded-xl border p-4 text-sm">
                <div className="flex items-center justify-between py-1">
                    <dt className="text-muted-foreground">Subtotal</dt>
                    <dd>{formatRupiah(order.subtotal)}</dd>
                </div>
                <div className="flex items-center justify-between py-1">
                    <dt className="text-muted-foreground">Diskon</dt>
                    <dd>{formatRupiah(order.discount)}</dd>
                </div>
                <div className="flex items-center justify-between py-1">
                    <dt className="text-muted-foreground">Pajak</dt>
                    <dd>{formatRupiah(order.tax)}</dd>
                </div>
                <div className="flex items-center justify-between py-1">
                    <dt className="text-muted-foreground">Biaya layanan</dt>
                    <dd>{formatRupiah(order.service_charge)}</dd>
                </div>
                <div className="mt-2 flex items-center justify-between border-t pt-3">
                    <dt className="font-medium">Total tagihan</dt>
                    <dd className="text-xl font-semibold">{formatRupiah(order.total)}</dd>
                </div>
            </dl>
        </section>
    );
}
