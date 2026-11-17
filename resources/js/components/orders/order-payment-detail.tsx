import { formatDateTime } from '@/lib/datetime';
import { formatRupiah } from '@/lib/format';
import type { Payment } from '@/types/orders';

const methodLabels: Record<Payment['payment_method'], string> = {
    CASH: 'Tunai',
    QRIS: 'QRIS',
    TRANSFER: 'Transfer',
    DEBIT: 'Debit',
};

/** Detail pembayaran hanya untuk order Paid; data berasal dari backend. */
export function OrderPaymentDetail({ payment }: { payment: Payment }) {
    return (
        <section className="rounded-xl border p-4" aria-label="Detail pembayaran">
            <div className="border-b pb-3">
                <h2 className="font-semibold">Pembayaran</h2>
                <p className="text-muted-foreground mt-1 text-xs">{payment.payment_number}</p>
            </div>

            <dl className="divide-y text-sm">
                <div className="flex items-center justify-between gap-4 py-2.5">
                    <dt className="text-muted-foreground">Metode</dt>
                    <dd className="font-medium">{methodLabels[payment.payment_method]}</dd>
                </div>
                <div className="flex items-center justify-between gap-4 py-2.5">
                    <dt className="text-muted-foreground">Waktu bayar</dt>
                    <dd className="text-right font-medium">{formatDateTime(payment.created_at)}</dd>
                </div>
                <div className="flex items-center justify-between gap-4 py-2.5">
                    <dt className="text-muted-foreground">Diproses oleh</dt>
                    <dd className="font-medium">{payment.processor?.name ?? '-'}</dd>
                </div>
                <div className="flex items-center justify-between gap-4 py-2.5">
                    <dt className="text-muted-foreground">Total dibayar</dt>
                    <dd className="font-semibold">{formatRupiah(payment.amount)}</dd>
                </div>
                {payment.payment_method === 'CASH' && (
                    <>
                        <div className="flex items-center justify-between gap-4 py-2.5">
                            <dt className="text-muted-foreground">Uang diterima</dt>
                            <dd className="font-medium">{formatRupiah(payment.received_amount ?? 0)}</dd>
                        </div>
                        <div className="flex items-center justify-between gap-4 py-2.5">
                            <dt className="text-muted-foreground">Kembalian</dt>
                            <dd className="font-medium">{formatRupiah(payment.change_amount)}</dd>
                        </div>
                    </>
                )}
            </dl>
        </section>
    );
}
