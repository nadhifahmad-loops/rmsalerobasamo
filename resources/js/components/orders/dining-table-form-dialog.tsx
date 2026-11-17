import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DiningTable, DiningTableFormValue } from '@/types/orders';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface DiningTableFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    table?: DiningTable | null;
}

/** Dipakai untuk tambah dan edit meja; parent memberi key agar form segar. */
export function DiningTableFormDialog({ open, onOpenChange, table }: DiningTableFormDialogProps) {
    const isEditing = table != null;
    const form = useForm<DiningTableFormValue>({
        table_number: table?.table_number ?? '',
        label: table?.label ?? '',
        is_active: table?.is_active ?? true,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: () => onOpenChange(false) };

        if (isEditing && table) {
            form.put(`/tables/${table.id}`, options);
            return;
        }

        form.post('/tables', options);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-md overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{isEditing ? 'Edit meja' : 'Tambah meja'}</DialogTitle>
                    <DialogDescription>Nomor meja harus unik. Status meja dihitung dari pesanan aktif.</DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="table-number">Nomor meja</Label>
                        <Input
                            id="table-number"
                            value={form.data.table_number}
                            onChange={(event) => form.setData('table_number', event.target.value)}
                            placeholder="Mis. 12 atau A1"
                            autoComplete="off"
                        />
                        <InputError message={form.errors.table_number} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="table-label">Label (opsional)</Label>
                        <Input
                            id="table-label"
                            value={form.data.label}
                            onChange={(event) => form.setData('label', event.target.value)}
                            placeholder="Mis. Meja jendela"
                            autoComplete="off"
                        />
                        <InputError message={form.errors.label} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="table-active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                        />
                        <Label htmlFor="table-active" className="cursor-pointer font-normal">
                            Meja dapat dipakai untuk pesanan baru
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : isEditing ? 'Simpan perubahan' : 'Tambah meja'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
