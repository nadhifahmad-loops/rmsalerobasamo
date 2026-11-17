import { StatusLabel } from '@/components/catalog/status-label';
import type { OrderStatus } from '@/types/orders';

const statusLabels: Record<OrderStatus, { label: string; tone: 'success' | 'warning' | 'muted' | 'info' }> = {
    OPEN: { label: 'Open', tone: 'warning' },
    AWAITING_PAYMENT: { label: 'Menunggu pembayaran', tone: 'info' },
    PAID: { label: 'Paid', tone: 'success' },
    VOIDED: { label: 'Dibatalkan', tone: 'muted' },
};

/** Label status order dipakai bersama di daftar, detail, dan checkout. */
export function OrderStatusLabel({ status }: { status: OrderStatus }) {
    const value = statusLabels[status];

    return <StatusLabel label={value.label} tone={value.tone} />;
}
