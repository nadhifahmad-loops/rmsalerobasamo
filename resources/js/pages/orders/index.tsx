import { Pagination } from '@/components/catalog/pagination';
import { FlashMessage } from '@/components/flash-message';
import { OrderFilters } from '@/components/orders/order-filters';
import { OrderStatusLabel } from '@/components/orders/order-status-label';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatShortDateTime } from '@/lib/datetime';
import { formatRupiah } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import type { OrderRow, OrdersPageProps } from '@/types/orders';
import { Head, Link } from '@inertiajs/react';
import { Armchair, ClipboardList, ReceiptText, ShoppingBag, Wallet } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pesanan', href: '/orders' }];

const paymentLabels: Record<string, string> = {
    CASH: 'Tunai',
    QRIS: 'QRIS',
    TRANSFER: 'Transfer',
    DEBIT: 'Debit',
};

function OrderCard({ order, showPayment }: { order: OrderRow; showPayment: boolean }) {
    return (
        <article className="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0 space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                    <h2 className="font-semibold">{order.table_label_snapshot ?? order.order_number}</h2>
                    <OrderStatusLabel status={order.status} />
                </div>
                <p className="text-muted-foreground text-xs">
                    {order.order_number} · {order.order_type === 'TAKEAWAY' ? 'Takeaway' : 'Dine-in'} · {order.items_count} item ·{' '}
                    {order.cashier?.name ?? '-'}
                </p>
                <p className="text-muted-foreground text-xs">
                    {showPayment
                        ? `Dibayar ${formatShortDateTime(order.paid_at ?? order.created_at)}`
                        : `Dibuat ${formatShortDateTime(order.created_at)}`}
                    {showPayment && order.payment_method ? ` · ${paymentLabels[order.payment_method] ?? order.payment_method}` : ''}
                </p>
                {showPayment && order.payment_number && <p className="text-muted-foreground text-xs">{order.payment_number}</p>}
            </div>

            <div className="flex items-center justify-between gap-4 sm:justify-end">
                <span className="font-semibold whitespace-nowrap">{formatRupiah(order.total)}</span>
                <div className="flex flex-wrap items-center gap-2">
                    <Button asChild variant="outline" size="sm">
                        <Link href={`/orders/${order.id}`}>Detail</Link>
                    </Button>
                    {order.status === 'PAID' && (
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/orders/${order.id}/receipt`} aria-label={`Lihat struk ${order.order_number}`}>
                                <ReceiptText aria-hidden="true" />
                                <span className="hidden sm:inline">Struk</span>
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </article>
    );
}

export default function OrdersIndex({ orders, view, filters, options, activeShift }: OrdersPageProps) {
    const isHistory = view === 'history';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isHistory ? 'Riwayat pesanan' : 'Pesanan aktif'} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4 pb-28 md:p-6 md:pb-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{isHistory ? 'Riwayat pesanan' : 'Pesanan aktif'}</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {isHistory ? 'Transaksi Paid dan order yang dibatalkan.' : 'Order Open dan menunggu pembayaran yang masih berjalan.'}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href="/tables">
                                <Armchair aria-hidden="true" />
                                Meja
                            </Link>
                        </Button>
                        <Button asChild size="sm">
                            <Link href="/takeaway">
                                <ShoppingBag aria-hidden="true" />
                                Takeaway
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/shifts">
                                <Wallet aria-hidden="true" />
                                Shift
                            </Link>
                        </Button>
                    </div>
                </div>

                <FlashMessage />

                {!activeShift && (
                    <div className="border-warning/50 bg-warning/5 rounded-xl border p-3 text-sm">
                        Belum ada shift aktif untuk akun ini.{' '}
                        <Link href="/shifts" className="font-medium underline">
                            Buka shift
                        </Link>
                        {'.'}
                    </div>
                )}

                <OrderFilters filters={filters} options={options} />

                {orders.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-8 text-center">
                        <ClipboardList className="text-muted-foreground mx-auto size-8" aria-hidden="true" />
                        <h2 className="mt-3 font-medium">Belum ada pesanan</h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {isHistory
                                ? 'Riwayat akan muncul setelah pembayaran berhasil atau order dibatalkan.'
                                : 'Buka pesanan dari meja atau buat pesanan takeaway.'}
                        </p>
                        {!isHistory && (
                            <Button asChild size="sm" className="mt-4">
                                <Link href="/takeaway">Buat pesanan takeaway</Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="flex flex-col gap-3">
                        {orders.data.map((order) => (
                            <OrderCard key={order.id} order={order} showPayment={isHistory} />
                        ))}
                        <Pagination links={orders.links} />
                        <p className="text-muted-foreground text-center text-xs">
                            Menampilkan {orders.from ?? 0}–{orders.to ?? 0} dari {orders.total} pesanan
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
