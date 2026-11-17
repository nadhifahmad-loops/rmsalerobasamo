import { StatusLabel } from '@/components/catalog/status-label';
import type { TableStatus } from '@/types/orders';

const statusMap = {
    AVAILABLE: { label: 'Tersedia', tone: 'success' },
    OCCUPIED: { label: 'Terisi', tone: 'warning' },
    AWAITING_PAYMENT: { label: 'Menunggu pembayaran', tone: 'info' },
} as const;

/** Status meja selalu disertai teks; warna hanya penguat visual. */
export function TableStatusLabel({ status, isActive = true }: { status: TableStatus; isActive?: boolean }) {
    if (!isActive) {
        return <StatusLabel label="Nonaktif" tone="muted" />;
    }

    const current = statusMap[status];

    return <StatusLabel label={current.label} tone={current.tone} />;
}
