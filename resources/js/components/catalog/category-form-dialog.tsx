import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Category } from '@/types/catalog';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface CategoryFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    category?: Category | null;
}

/** Dipakai untuk tambah dan edit kategori; parent memberi key agar state form segar. */
export function CategoryFormDialog({ open, onOpenChange, category }: CategoryFormDialogProps) {
    const isEditing = category != null;
    const form = useForm({
        name: category?.name ?? '',
        sort_order: category?.sort_order ?? 0,
        is_active: category?.is_active ?? true,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (isEditing && category) {
            form.put(`/categories/${category.id}`, {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
            });

            return;
        }

        form.post('/categories', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-md overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{isEditing ? 'Edit kategori' : 'Tambah kategori'}</DialogTitle>
                    <DialogDescription>Kategori membantu kasir menemukan menu lebih cepat.</DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="category-name">Nama kategori</Label>
                        <Input
                            id="category-name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            placeholder="Contoh: Makanan Berat"
                            autoComplete="off"
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="category-sort">Urutan tampil</Label>
                        <Input
                            id="category-sort"
                            type="number"
                            min={0}
                            max={9999}
                            value={form.data.sort_order}
                            onChange={(event) => form.setData('sort_order', event.target.value === '' ? 0 : Number(event.target.value))}
                        />
                        <p className="text-muted-foreground text-xs">Angka lebih kecil ditampilkan lebih dulu.</p>
                        <InputError message={form.errors.sort_order} />
                    </div>

                    <label htmlFor="category-active" className="flex min-h-11 cursor-pointer items-center gap-3 text-sm">
                        <input
                            id="category-active"
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(event) => form.setData('is_active', event.target.checked)}
                            className="border-input size-4 rounded"
                        />
                        Kategori aktif
                    </label>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : isEditing ? 'Simpan perubahan' : 'Tambah kategori'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
