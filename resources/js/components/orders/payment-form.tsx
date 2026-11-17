import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Order, PaymentMethod, PaymentMethodOption } from '@/types/orders';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

interface PaymentFormProps {
    order: Order;
    methods: PaymentMethodOption[];
    maxReceivedAmount: number;
}

function newIdempotencyKey(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `idem-${Date.now()}-${Math.random().toString(36).slice(2)}-${Math.random().toString(36).slice(2)}`;
}

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="text-destructive mt-1 text-xs">{message}</p>;
}

/** Form pembayaran. Total dan kembalian final selalu dihitung ulang backend. */
export function PaymentForm({ order, methods, maxReceivedAmount }: PaymentFormProps) {
    const [idempotencyKey] = useState(newIdempotencyKey);
    const form = useForm({
        payment_method: 'CASH' as PaymentMethod,
        received_amount: '',
        idempotency_key: idempotencyKey,
    });

    const isCash = form.data.payment_method === 'CASH';
    const receivedAmount = Number(form.data.received_amount);
    const isShort = isCash && (!Number.isFinite(receivedAmount) || receivedAmount < order.total);
    const changePreview = isCash && !isShort ? receivedAmount - order.total : 0;
    const serverErrors = form.errors as Partial<Record<string, string>>;
    const generalError = serverErrors.order ?? form.errors.idempotency_key;

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(`/orders/${order.id}/pay`, {
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit} className="rounded-xl border p-4" aria-label="Form pembayaran">
            <div className="flex flex-col gap-1">
                <h2 className="font-medium">Pembayaran</h2>
                <p className="text-muted-foreground text-xs">Total tagihan {formatRupiah(order.total)}.</p>
            </div>

            {generalError && (
                <Alert variant="destructive" className="mt-4">
                    <AlertDescription>{generalError}</AlertDescription>
                </Alert>
            )}

            <fieldset className="mt-4">
                <legend className="text-sm font-medium">Metode pembayaran</legend>
                <div className="mt-2 grid grid-cols-2 gap-2">
                    {methods.map((method) => (
                        <label
                            key={method.value}
                            htmlFor={`payment-${method.value}`}
                            className={cn(
                                'flex min-h-11 cursor-pointer items-center rounded-md border px-3 text-sm',
                                form.data.payment_method === method.value ? 'border-primary bg-accent font-medium' : 'border-input',
                            )}
                        >
                            <input
                                id={`payment-${method.value}`}
                                type="radio"
                                name="payment_method"
                                value={method.value}
                                checked={form.data.payment_method === method.value}
                                onChange={() => form.setData('payment_method', method.value)}
                                className="accent-primary me-2 size-4"
                            />
                            {method.label}
                        </label>
                    ))}
                </div>
                <FieldError message={form.errors.payment_method} />
            </fieldset>

            {isCash ? (
                <div className="mt-4">
                    <Label htmlFor="received-amount">Uang diterima</Label>
                    <div className="relative mt-1.5">
                        <span className="text-muted-foreground absolute inset-y-0 start-3 flex items-center text-sm">Rp</span>
                        <Input
                            id="received-amount"
                            type="number"
                            inputMode="numeric"
                            min={0}
                            max={maxReceivedAmount}
                            step={1}
                            value={form.data.received_amount}
                            onChange={(event) => form.setData('received_amount', event.target.value)}
                            className="ps-10"
                            placeholder="0"
                            aria-invalid={Boolean(form.errors.received_amount)}
                            autoComplete="off"
                        />
                    </div>
                    <FieldError message={form.errors.received_amount} />
                    {!form.errors.received_amount && isShort && <p className="text-destructive mt-1 text-xs">Nominal kurang dari total tagihan.</p>}

                    <div className="bg-muted mt-3 flex items-center justify-between rounded-md px-3 py-2 text-sm">
                        <span className="text-muted-foreground">Kembalian (pratinjau)</span>
                        <span className="font-semibold">{formatRupiah(changePreview)}</span>
                    </div>
                </div>
            ) : (
                <p className="text-muted-foreground bg-muted mt-4 rounded-md px-3 py-2 text-xs">
                    Konfirmasi pembayaran {methods.find((method) => method.value === form.data.payment_method)?.label} sesuai SOP sebelum mencatat.
                    Nominal dicatat sama dengan total tagihan.
                </p>
            )}

            <Button type="submit" className="mt-5 min-h-11 w-full" disabled={form.processing || isShort}>
                {form.processing ? 'Memproses…' : `Bayar ${formatRupiah(order.total)}`}
            </Button>
            <p className="text-muted-foreground mt-2 text-center text-xs">Kembalian dan total final dihitung backend saat pembayaran disimpan.</p>
        </form>
    );
}
