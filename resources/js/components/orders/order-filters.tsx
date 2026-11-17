import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { OrderFilterOptions, OrderFiltersValue } from '@/types/orders';
import { router } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';

interface OrderFiltersProps {
    filters: OrderFiltersValue;
    options: OrderFilterOptions;
}

const selectClassName =
    'border-input bg-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none';

/** Filter server-side untuk daftar order aktif dan riwayat. */
export function OrderFilters({ filters, options }: OrderFiltersProps) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    const apply = (overrides: Partial<OrderFiltersValue> = {}) => {
        const next = { ...filters, ...overrides, search };

        router.get(
            '/orders',
            {
                view: next.view,
                search: next.search || undefined,
                from: next.from || undefined,
                to: next.to || undefined,
                cashier_id: next.cashier_id ?? undefined,
                payment_method: next.payment_method || undefined,
                order_type: next.order_type || undefined,
                status: next.status || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        apply();
    };

    const reset = () => {
        setSearch('');
        router.get('/orders', { view: filters.view }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const isHistory = filters.view === 'history';

    return (
        <section className="flex flex-col gap-3" aria-label="Filter pesanan">
            <div className="flex flex-wrap gap-2" role="tablist" aria-label="Jenis daftar pesanan">
                <Button
                    type="button"
                    size="sm"
                    variant={isHistory ? 'outline' : 'default'}
                    aria-selected={!isHistory}
                    onClick={() => apply({ view: 'active', status: '', payment_method: '' })}
                >
                    Aktif
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant={isHistory ? 'default' : 'outline'}
                    aria-selected={isHistory}
                    onClick={() => apply({ view: 'history', status: '', payment_method: '' })}
                >
                    Riwayat
                </Button>
            </div>

            <form onSubmit={submit} className="rounded-xl border p-3 md:p-4">
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="order-search">Cari nomor</Label>
                        <Input
                            id="order-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Nomor order/transaksi"
                            autoComplete="off"
                        />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="order-from">Dari tanggal</Label>
                        <Input id="order-from" type="date" value={filters.from} onChange={(event) => apply({ from: event.target.value })} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="order-to">Sampai tanggal</Label>
                        <Input id="order-to" type="date" value={filters.to} onChange={(event) => apply({ to: event.target.value })} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="order-cashier">Kasir</Label>
                        <select
                            id="order-cashier"
                            className={selectClassName}
                            value={filters.cashier_id ?? ''}
                            onChange={(event) => apply({ cashier_id: event.target.value ? Number(event.target.value) : null })}
                        >
                            <option value="">Semua kasir</option>
                            {options.cashiers.map((cashier) => (
                                <option key={cashier.id} value={cashier.id}>
                                    {cashier.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="order-type">Tipe order</Label>
                        <select
                            id="order-type"
                            className={selectClassName}
                            value={filters.order_type}
                            onChange={(event) => apply({ order_type: event.target.value as OrderFiltersValue['order_type'] })}
                        >
                            <option value="">Semua tipe</option>
                            {options.types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="order-status">Status</Label>
                        <select
                            id="order-status"
                            className={selectClassName}
                            value={filters.status}
                            onChange={(event) => apply({ status: event.target.value as OrderFiltersValue['status'] })}
                        >
                            <option value="">Semua status</option>
                            {(isHistory
                                ? options.statuses.filter((status) => status.value === 'PAID' || status.value === 'VOIDED')
                                : options.statuses
                            ).map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    {isHistory && (
                        <div className="space-y-1.5">
                            <Label htmlFor="order-method">Metode bayar</Label>
                            <select
                                id="order-method"
                                className={selectClassName}
                                value={filters.payment_method}
                                onChange={(event) => apply({ payment_method: event.target.value as OrderFiltersValue['payment_method'] })}
                            >
                                <option value="">Semua metode</option>
                                {options.methods.map((method) => (
                                    <option key={method.value} value={method.value}>
                                        {method.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}
                </div>

                <div className="mt-3 flex flex-wrap gap-2">
                    <Button type="submit" size="sm">
                        Terapkan
                    </Button>
                    <Button type="button" size="sm" variant="outline" onClick={reset}>
                        Reset
                    </Button>
                </div>
            </form>
        </section>
    );
}
