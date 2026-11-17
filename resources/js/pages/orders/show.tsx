import { FlashMessage } from '@/components/flash-message';
import { OrderCart } from '@/components/orders/order-cart';
import { OrderNotesForm } from '@/components/orders/order-notes-form';
import { OrderPaymentDetail } from '@/components/orders/order-payment-detail';
import { OrderStatusLabel } from '@/components/orders/order-status-label';
import { OrderTotals } from '@/components/orders/order-totals';
import { ProductPicker } from '@/components/orders/product-picker';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import type { OrderShowProps } from '@/types/orders';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ReceiptText } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pesanan', href: '/orders' },
    { title: 'Detail', href: '#' },
];

export default function OrderShow({ order, products, categories, filters, productImages, canEdit }: OrderShowProps) {
    const [markingWaiting, setMarkingWaiting] = useState(false);
    const hasItems = order.items.length > 0;

    const markAwaitingPayment = () => {
        setMarkingWaiting(true);
        router.post(
            `/orders/${order.id}/awaiting-payment`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setMarkingWaiting(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Pesanan ${order.order_number}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4 pb-28 md:p-6 md:pb-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{order.table_label_snapshot ?? order.order_number}</h1>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {order.order_number} · {order.order_type === 'TAKEAWAY' ? 'Takeaway' : 'Dine-in'} · {order.cashier?.name ?? '-'} ·{' '}
                            {new Intl.DateTimeFormat('id-ID', {
                                timeZone: 'Asia/Jakarta',
                                day: '2-digit',
                                month: 'short',
                                hour: '2-digit',
                                minute: '2-digit',
                            }).format(new Date(order.created_at))}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <OrderStatusLabel status={order.status} />
                        <Button asChild variant="outline" size="sm">
                            <Link href="/tables">
                                <ArrowLeft aria-hidden="true" />
                                Meja
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/orders">Daftar pesanan</Link>
                        </Button>
                        {order.status === 'PAID' && (
                            <Button asChild variant="outline" size="sm">
                                <Link href={`/orders/${order.id}/receipt`}>
                                    <ReceiptText aria-hidden="true" />
                                    Lihat struk
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <FlashMessage />

                <div className="grid min-w-0 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_400px]">
                    <ProductPicker orderId={order.id} products={products} categories={categories} filters={filters} canEdit={canEdit} />

                    <aside className="flex flex-col gap-4 lg:sticky lg:top-4">
                        <OrderCart order={order} canEdit={canEdit} productImages={productImages} />
                        <OrderNotesForm order={order} canEdit={canEdit} />
                        <OrderTotals order={order} />
                        {order.payment && <OrderPaymentDetail payment={order.payment} />}

                        {canEdit && hasItems && (
                            <div className="flex flex-col gap-2">
                                {order.status === 'OPEN' && (
                                    <Button type="button" variant="outline" onClick={markAwaitingPayment} disabled={markingWaiting}>
                                        {markingWaiting ? 'Menyimpan…' : 'Siapkan tagihan'}
                                    </Button>
                                )}
                                <Button asChild>
                                    <Link href={`/orders/${order.id}/checkout`}>Checkout</Link>
                                </Button>
                            </div>
                        )}
                    </aside>
                </div>

                <div className="bg-background fixed inset-x-0 bottom-16 z-30 flex items-center justify-between gap-3 border-t px-4 py-3 shadow-sm md:hidden">
                    <div>
                        <p className="text-muted-foreground text-xs">Total {order.items.length} item</p>
                        <p className="font-semibold">{formatRupiah(order.total)}</p>
                    </div>
                    {order.status === 'PAID' ? (
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/orders/${order.id}/receipt`}>Lihat struk</Link>
                        </Button>
                    ) : canEdit && hasItems ? (
                        <Button asChild size="sm">
                            <Link href={`/orders/${order.id}/checkout`}>Checkout</Link>
                        </Button>
                    ) : (
                        <Button asChild variant="outline" size="sm">
                            <a href="#cart">Lihat pesanan</a>
                        </Button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
