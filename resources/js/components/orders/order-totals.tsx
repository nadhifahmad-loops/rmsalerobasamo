import { formatRupiah } from '@/lib/format';
import type { Order } from '@/types/orders';

/** Ringkasan nominal. Semua angka berasal dari perhitungan backend. */
export function OrderTotals({ order }: { order: Order }) {
    const note =
        order.status === 'PAID'
            ? 'Pesanan sudah dibayar.'
            : order.status === 'AWAITING_PAYMENT'
              ? 'Tagihan siap dibayar.'
              : order.status === 'VOIDED'
                ? 'Pesanan dibatalkan.'
                : 'Belum termasuk pembayaran. Status pesanan masih Open.';

    return (
        <dl className="rounded-xl border p-4 text-sm">
            <div className="flex items-center justify-between py-1">
                <dt className="text-muted-foreground">Subtotal</dt>
                <dd className="font-medium">{formatRupiah(order.subtotal)}</dd>
            </div>
            <div className="flex items-center justify-between py-1">
                <dt className="text-muted-foreground">Diskon</dt>
                <dd className="font-medium">{formatRupiah(order.discount)}</dd>
            </div>
            <div className="flex items-center justify-between py-1">
                <dt className="text-muted-foreground">Pajak</dt>
                <dd className="font-medium">{formatRupiah(order.tax)}</dd>
            </div>
            <div className="flex items-center justify-between py-1">
                <dt className="text-muted-foreground">Biaya layanan</dt>
                <dd className="font-medium">{formatRupiah(order.service_charge)}</dd>
            </div>
            <div className="mt-2 flex items-center justify-between border-t pt-3">
                <dt className="font-medium">Total</dt>
                <dd className="text-lg font-semibold">{formatRupiah(order.total)}</dd>
            </div>
            <p className="text-muted-foreground mt-3 text-xs">{note}</p>
        </dl>
    );
}
