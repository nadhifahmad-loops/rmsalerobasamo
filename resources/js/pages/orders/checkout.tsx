import { FlashMessage } from '@/components/flash-message';
import { CheckoutSummary } from '@/components/orders/checkout-summary';
import { OrderStatusLabel } from '@/components/orders/order-status-label';
import { PaymentForm } from '@/components/orders/payment-form';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { CheckoutPageProps } from '@/types/orders';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pesanan', href: '/orders' },
    { title: 'Checkout', href: '#' },
];

export default function Checkout({ order, paymentMethods, maxReceivedAmount }: CheckoutPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Checkout ${order.order_number}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4 pb-28 md:p-6 md:pb-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Checkout</h1>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {order.order_number} · {order.table_label_snapshot ?? 'Tanpa meja'} · {order.cashier?.name ?? '-'}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <OrderStatusLabel status={order.status} />
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/orders/${order.id}`}>
                                <ArrowLeft aria-hidden="true" />
                                Kembali ke pesanan
                            </Link>
                        </Button>
                    </div>
                </div>

                <FlashMessage />

                <div className="grid min-w-0 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_400px]">
                    <CheckoutSummary order={order} />

                    <aside className="lg:sticky lg:top-4">
                        {order.items.length === 0 ? (
                            <Alert>
                                <AlertDescription>
                                    Pesanan belum memiliki item.{' '}
                                    <Link href={`/orders/${order.id}`} className="underline underline-offset-2">
                                        Tambahkan item terlebih dahulu.
                                    </Link>
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <PaymentForm order={order} methods={paymentMethods} maxReceivedAmount={maxReceivedAmount} />
                        )}
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}
