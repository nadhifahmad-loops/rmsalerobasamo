import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/datetime';
import { formatRupiah } from '@/lib/format';
import type { CloseShiftFormValue } from '@/types/shifts';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface CloseShiftFormProps {
    shiftId: number;
    cashier: string;
    openedAt: string;
    openingCash: number;
    expectedCash: number;
}

/** Form tutup shift dipakai kasir sendiri dan admin/owner yang memiliki permission. */
export function CloseShiftForm({ shiftId, cashier, openedAt, openingCash, expectedCash }: CloseShiftFormProps) {
    const form = useForm<CloseShiftFormValue>({ actual_cash: '' });
    const actualCash = Number(form.data.actual_cash);
    const difference = form.data.actual_cash === '' || !Number.isFinite(actualCash) ? null : actualCash - expectedCash;

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.put(`/shifts/${shiftId}/close`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="rounded-xl border p-4" aria-label={`Tutup shift ${shiftId}`}>
            <div className="flex flex-wrap items-start justify-between gap-2 border-b pb-3">
                <div>
                    <h2 className="font-semibold">Tutup shift</h2>
                    <p className="text-muted-foreground mt-1 text-xs">
                        {cashier} · dibuka {formatDateTime(openedAt)}
                    </p>
                </div>
            </div>

            <dl className="divide-y text-sm">
                <div className="flex items-center justify-between gap-4 py-2.5">
                    <dt className="text-muted-foreground">Modal awal</dt>
                    <dd className="font-medium">{formatRupiah(openingCash)}</dd>
                </div>
                <div className="flex items-center justify-between gap-4 py-2.5">
                    <dt className="text-muted-foreground">Kas diharapkan</dt>
                    <dd className="font-semibold">{formatRupiah(expectedCash)}</dd>
                </div>
                {difference !== null && (
                    <div className="flex items-center justify-between gap-4 py-2.5">
                        <dt className="text-muted-foreground">Selisih</dt>
                        <dd className={difference < 0 ? 'text-destructive font-semibold' : 'font-semibold'}>{formatRupiah(difference)}</dd>
                    </div>
                )}
            </dl>

            <div className="mt-4 space-y-1.5">
                <Label htmlFor={`shift-actual-${shiftId}`}>Kas aktual</Label>
                <Input
                    id={`shift-actual-${shiftId}`}
                    type="number"
                    min={0}
                    inputMode="numeric"
                    value={form.data.actual_cash}
                    onChange={(event) => form.setData('actual_cash', event.target.value)}
                    placeholder="Hitung uang di laci"
                    disabled={form.processing}
                    required
                />
                <InputError message={form.errors.actual_cash} />
                <InputError message={form.errors.shift} />
            </div>

            <Button type="submit" className="mt-4 w-full" disabled={form.processing}>
                {form.processing ? 'Menyimpan…' : 'Simpan penutupan shift'}
            </Button>
            <p className="text-muted-foreground mt-2 text-xs">Non-tunai tidak menambah kas fisik. Selisih disimpan untuk audit.</p>
        </form>
    );
}
