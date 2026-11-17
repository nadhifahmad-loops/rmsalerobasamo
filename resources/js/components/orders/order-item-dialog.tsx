import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatRupiah } from '@/lib/format';
import type { OrderItem, OrderItemFormValue } from '@/types/orders';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface OrderItemDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    orderId: number;
    item: OrderItem;
}

/** Ubah jumlah dan catatan item. Harga snapshot tidak pernah dikirim dari UI. */
export function OrderItemDialog({ open, onOpenChange, orderId, item }: OrderItemDialogProps) {
    const form = useForm<OrderItemFormValue>({
        quantity: item.quantity,
        notes: item.notes ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.put(`/orders/${orderId}/items/${item.id}`, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-md overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Ubah item</DialogTitle>
                    <DialogDescription>
                        {item.product_name_snapshot} · {item.variant_name_snapshot} · {formatRupiah(item.price_snapshot)} / {item.unit_snapshot}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="item-quantity">Jumlah</Label>
                        <Input
                            id="item-quantity"
                            type="number"
                            min={1}
                            max={100}
                            inputMode="numeric"
                            value={form.data.quantity}
                            onChange={(event) => form.setData('quantity', Number(event.target.value))}
                        />
                        <InputError message={form.errors.quantity} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="item-notes">Catatan item (opsional)</Label>
                        <Textarea
                            id="item-notes"
                            rows={2}
                            maxLength={500}
                            value={form.data.notes}
                            onChange={(event) => form.setData('notes', event.target.value)}
                            placeholder="Mis. tanpa cabai"
                        />
                        <InputError message={form.errors.notes} />
                        <InputError message={form.errors.order} />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan perubahan'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
