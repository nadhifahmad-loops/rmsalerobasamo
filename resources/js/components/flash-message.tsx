import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, X } from 'lucide-react';
import { useState } from 'react';

/** Feedback sukses atau error singkat dari session flash. */
export function FlashMessage() {
    const { flash } = usePage<SharedData>().props;
    const [dismissed, setDismissed] = useState(false);
    const message = flash?.success ?? flash?.error;
    const isError = !flash?.success && Boolean(flash?.error);

    if (!message || dismissed) {
        return null;
    }

    return (
        <div
            role={isError ? 'alert' : 'status'}
            className={cn(
                'flex items-start justify-between gap-3 rounded-md border px-4 py-3 text-sm',
                isError ? 'border-destructive/30 bg-destructive/5 text-destructive' : 'border-success/30 bg-success/5 text-success',
            )}
        >
            <span className="flex items-center gap-2">
                {isError ? (
                    <AlertCircle className="size-4 shrink-0" aria-hidden="true" />
                ) : (
                    <CheckCircle2 className="size-4 shrink-0" aria-hidden="true" />
                )}
                {message}
            </span>
            <button
                type="button"
                onClick={() => setDismissed(true)}
                aria-label="Tutup pemberitahuan"
                className="focus-visible:ring-ring rounded-sm p-0.5 focus-visible:ring-2 focus-visible:outline-none"
            >
                <X className="size-4" aria-hidden="true" />
            </button>
        </div>
    );
}
