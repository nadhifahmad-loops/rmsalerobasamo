import { cn } from '@/lib/utils';

type Tone = 'success' | 'warning' | 'muted' | 'info';

const textTone: Record<Tone, string> = {
    success: 'text-success',
    warning: 'text-warning',
    muted: 'text-muted-foreground',
    info: 'text-info',
};

const dotTone: Record<Tone, string> = {
    success: 'bg-success',
    warning: 'bg-warning',
    muted: 'bg-muted-foreground',
    info: 'bg-info',
};

interface StatusLabelProps {
    label: string;
    tone?: Tone;
}

/** Status selalu memiliki label teks; warna hanya penguat, bukan satu-satunya informasi. */
export function StatusLabel({ label, tone = 'muted' }: StatusLabelProps) {
    return (
        <span className={cn('inline-flex items-center gap-1.5 text-xs font-medium whitespace-nowrap', textTone[tone])}>
            <span aria-hidden="true" className={cn('size-1.5 rounded-full', dotTone[tone])} />
            {label}
        </span>
    );
}

export function AvailabilityLabel({ isActive, isAvailable }: { isActive: boolean; isAvailable: boolean }) {
    if (!isActive) {
        return <StatusLabel label="Nonaktif" tone="muted" />;
    }

    if (!isAvailable) {
        return <StatusLabel label="Habis" tone="warning" />;
    }

    return <StatusLabel label="Tersedia" tone="success" />;
}

export function VariantStatusLabel({ isActive, isAvailable }: { isActive: boolean; isAvailable: boolean }) {
    if (!isActive) {
        return <StatusLabel label="Variasi nonaktif" tone="muted" />;
    }

    if (!isAvailable) {
        return <StatusLabel label="Habis" tone="warning" />;
    }

    return <StatusLabel label="Dapat dipesan" tone="success" />;
}
