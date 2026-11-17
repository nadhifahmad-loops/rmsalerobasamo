import { Pagination } from '@/components/catalog/pagination';
import { StatusLabel } from '@/components/catalog/status-label';
import { FlashMessage } from '@/components/flash-message';
import InputError from '@/components/input-error';
import { CloseShiftForm } from '@/components/shifts/close-shift-form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/datetime';
import { formatRupiah } from '@/lib/format';
import type { BreadcrumbItem, SharedData } from '@/types';
import type { OpenShiftFormValue, ShiftRow, ShiftsPageProps } from '@/types/shifts';
import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Shift', href: '/shifts' }];

function ShiftRowView({ shift }: { shift: ShiftRow }) {
    return (
        <article className="flex flex-col gap-2 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0 space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                    <h2 className="font-semibold">{shift.cashier}</h2>
                    <StatusLabel label={shift.status === 'OPEN' ? 'Aktif' : 'Ditutup'} tone={shift.status === 'OPEN' ? 'info' : 'muted'} />
                </div>
                <p className="text-muted-foreground text-xs">
                    {formatDateTime(shift.opened_at)} · {shift.orders_count} order
                </p>
                {shift.closed_at && <p className="text-muted-foreground text-xs">Ditutup {formatDateTime(shift.closed_at)}</p>}
            </div>
            <div className="grid grid-cols-2 gap-x-6 gap-y-1 text-xs sm:grid-cols-4 sm:text-right">
                <div>
                    <p className="text-muted-foreground">Modal</p>
                    <p className="font-medium">{formatRupiah(shift.opening_cash)}</p>
                </div>
                <div>
                    <p className="text-muted-foreground">Diharapkan</p>
                    <p className="font-medium">{shift.expected_cash === null ? '—' : formatRupiah(shift.expected_cash)}</p>
                </div>
                <div>
                    <p className="text-muted-foreground">Aktual</p>
                    <p className="font-medium">{shift.actual_cash === null ? '—' : formatRupiah(shift.actual_cash)}</p>
                </div>
                <div>
                    <p className="text-muted-foreground">Selisih</p>
                    <p className={shift.difference !== null && shift.difference < 0 ? 'text-destructive font-medium' : 'font-medium'}>
                        {shift.difference === null ? '—' : formatRupiah(shift.difference)}
                    </p>
                </div>
            </div>
        </article>
    );
}

export default function ShiftsIndex({ shifts, activeShift, managedShifts, canManage }: ShiftsPageProps) {
    const user = usePage<SharedData>().props.auth.user;
    const openForm = useForm<OpenShiftFormValue>({ opening_cash: '' });
    const isOwnShift = activeShift !== null && activeShift.cashier_id === user?.id;
    const canCloseOwn = isOwnShift || (canManage && activeShift !== null);

    const submitOpen = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        openForm.post('/shifts', { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Shift" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4 pb-28 md:p-6 md:pb-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Shift kasir</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Buka shift dengan modal awal dan rekonsiliasi kas saat menutup.</p>
                </div>

                <FlashMessage />

                <div className="grid min-w-0 items-start gap-4 lg:grid-cols-2">
                    {activeShift ? (
                        <section className="rounded-xl border p-4" aria-label="Shift aktif">
                            <div className="border-b pb-3">
                                <h2 className="font-semibold">Shift aktif</h2>
                                <p className="text-muted-foreground mt-1 text-xs">Dibuka {formatDateTime(activeShift.opened_at)}</p>
                            </div>
                            <dl className="divide-y text-sm">
                                <div className="flex items-center justify-between gap-4 py-2.5">
                                    <dt className="text-muted-foreground">Modal awal</dt>
                                    <dd className="font-medium">{formatRupiah(activeShift.opening_cash)}</dd>
                                </div>
                                <div className="flex items-center justify-between gap-4 py-2.5">
                                    <dt className="text-muted-foreground">Kas diharapkan</dt>
                                    <dd className="font-semibold">{formatRupiah(activeShift.expected_cash)}</dd>
                                </div>
                            </dl>
                            {!canCloseOwn && <p className="text-muted-foreground mt-3 text-xs">Hanya pemilik shift yang dapat menutupnya.</p>}
                        </section>
                    ) : (
                        <form onSubmit={submitOpen} className="rounded-xl border p-4" aria-label="Buka shift">
                            <div className="border-b pb-3">
                                <h2 className="font-semibold">Buka shift baru</h2>
                                <p className="text-muted-foreground mt-1 text-xs">Satu kasir hanya boleh memiliki satu shift aktif.</p>
                            </div>
                            <div className="mt-4 space-y-1.5">
                                <Label htmlFor="opening-cash">Modal awal</Label>
                                <Input
                                    id="opening-cash"
                                    type="number"
                                    min={0}
                                    inputMode="numeric"
                                    value={openForm.data.opening_cash}
                                    onChange={(event) => openForm.setData('opening_cash', event.target.value)}
                                    placeholder="Contoh: 100000"
                                    disabled={openForm.processing}
                                    required
                                />
                                <InputError message={openForm.errors.opening_cash} />
                                <InputError message={openForm.errors.shift} />
                            </div>
                            <Button type="submit" className="mt-4 w-full" disabled={openForm.processing}>
                                {openForm.processing ? 'Menyimpan…' : 'Buka shift'}
                            </Button>
                        </form>
                    )}

                    {canCloseOwn && activeShift && (
                        <CloseShiftForm
                            shiftId={activeShift.id}
                            cashier={user?.name ?? 'Kasir'}
                            openedAt={activeShift.opened_at}
                            openingCash={activeShift.opening_cash}
                            expectedCash={activeShift.expected_cash}
                        />
                    )}
                </div>

                {canManage && managedShifts.length > 0 && (
                    <section className="flex flex-col gap-3">
                        <h2 className="font-semibold">Shift aktif kasir lain</h2>
                        <div className="grid gap-3 lg:grid-cols-2">
                            {managedShifts
                                .filter((shift) => shift.id !== activeShift?.id)
                                .map((shift) => (
                                    <CloseShiftForm
                                        key={shift.id}
                                        shiftId={shift.id}
                                        cashier={shift.cashier}
                                        openedAt={shift.opened_at}
                                        openingCash={shift.opening_cash}
                                        expectedCash={shift.expected_cash}
                                    />
                                ))}
                        </div>
                    </section>
                )}

                <section className="flex flex-col gap-3">
                    <h2 className="font-semibold">Riwayat shift</h2>
                    {shifts.data.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-8 text-center">
                            <p className="font-medium">Belum ada shift</p>
                            <p className="text-muted-foreground mt-1 text-sm">Shift akan muncul setelah dibuka kasir.</p>
                        </div>
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto rounded-xl border md:block">
                                <table className="w-full min-w-[720px] text-sm">
                                    <thead className="bg-muted/30 border-b text-left">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">Kasir</th>
                                            <th className="px-4 py-3 font-medium">Dibuka</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 text-right font-medium">Modal</th>
                                            <th className="px-4 py-3 text-right font-medium">Diharapkan</th>
                                            <th className="px-4 py-3 text-right font-medium">Aktual</th>
                                            <th className="px-4 py-3 text-right font-medium">Selisih</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {shifts.data.map((shift) => (
                                            <tr key={shift.id}>
                                                <td className="px-4 py-3">{shift.cashier}</td>
                                                <td className="text-muted-foreground px-4 py-3 text-xs">{formatDateTime(shift.opened_at)}</td>
                                                <td className="px-4 py-3">
                                                    <StatusLabel
                                                        label={shift.status === 'OPEN' ? 'Aktif' : 'Ditutup'}
                                                        tone={shift.status === 'OPEN' ? 'info' : 'muted'}
                                                    />
                                                </td>
                                                <td className="px-4 py-3 text-right">{formatRupiah(shift.opening_cash)}</td>
                                                <td className="px-4 py-3 text-right">
                                                    {shift.expected_cash === null ? '—' : formatRupiah(shift.expected_cash)}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {shift.actual_cash === null ? '—' : formatRupiah(shift.actual_cash)}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {shift.difference === null ? '—' : formatRupiah(shift.difference)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <div className="flex flex-col gap-3 md:hidden">
                                {shifts.data.map((shift) => (
                                    <ShiftRowView key={shift.id} shift={shift} />
                                ))}
                            </div>
                            <Pagination links={shifts.links} />
                        </>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
