import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { ProductVariant, VariantFormValue } from '@/types/catalog';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { VariantFields } from './variant-fields';

interface VariantFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    productId: number;
    variant?: ProductVariant | null;
}

/** Dipakai untuk tambah dan edit satu variasi; parent memberi key agar form segar. */
export function VariantFormDialog({ open, onOpenChange, productId, variant }: VariantFormDialogProps) {
    const isEditing = variant != null;
    const form = useForm<VariantFormValue>({
        name: variant?.name ?? '',
        price: variant?.price ?? '',
        is_default: variant?.is_default ?? false,
        is_active: variant?.is_active ?? true,
        is_available: variant?.is_available ?? true,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.transform((data) => ({ ...data, price: Number(data.price) }));

        if (isEditing && variant) {
            form.put(`/products/${productId}/variants/${variant.id}`, {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
            });

            return;
        }

        form.post(`/products/${productId}/variants`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-lg overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{isEditing ? 'Edit variasi' : 'Tambah variasi'}</DialogTitle>
                    <DialogDescription>Harga yang disimpan di sini dipakai untuk pesanan baru.</DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <VariantFields
                        value={form.data}
                        onChange={(next) => form.setData(next)}
                        errors={{ name: form.errors.name, price: form.errors.price }}
                        idPrefix="variant-editor"
                        defaultRadioName="variant-editor-default"
                    />

                    <InputError message={form.errors.variants} />

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : isEditing ? 'Simpan perubahan' : 'Tambah variasi'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
