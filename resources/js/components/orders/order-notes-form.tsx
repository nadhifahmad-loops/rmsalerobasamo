import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Order, OrderNotesFormValue } from '@/types/orders';
import { useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';

interface OrderNotesFormProps {
    order: Order;
    canEdit: boolean;
}

/** Catatan pesanan disimpan eksplisit melalui backend, bukan state lokal saja. */
export function OrderNotesForm({ order, canEdit }: OrderNotesFormProps) {
    const form = useForm<OrderNotesFormValue>({ notes: order.notes ?? '' });

    useEffect(() => {
        form.setData('notes', order.notes ?? '');
        // Nilai order berubah setelah redirect Inertia; form harus mengikuti.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [order.notes]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.put(`/orders/${order.id}/notes`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-2">
            <Label htmlFor="order-notes">Catatan pesanan</Label>
            <Textarea
                id="order-notes"
                rows={2}
                maxLength={500}
                value={form.data.notes}
                onChange={(event) => form.setData('notes', event.target.value)}
                disabled={!canEdit}
                placeholder="Mis. Pelanggan menunggu di meja"
            />
            <InputError message={form.errors.notes} />
            <InputError message={form.errors.order} />
            <Button type="submit" variant="outline" size="sm" disabled={!canEdit || form.processing} className="min-h-9">
                {form.processing ? 'Menyimpan…' : 'Simpan catatan'}
            </Button>
        </form>
    );
}
