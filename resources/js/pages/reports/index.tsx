import { FlashMessage } from '@/components/flash-message';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/datetime';
import { formatRupiah } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import type { ReportsPageProps } from '@/types/reports';
import { Head, Link, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Laporan', href: '/reports' }];

function historyUrl(from: string, to: string, method?: string): string {
    const params = new URLSearchParams({ view: 'history' });
    if (from) {
        params.set('from', from);
    }
    if (to) {
        params.set('to', to);
    }
    if (method) {
        params.set('payment_method', method);
    }

    return `/orders?${params.toString()}`;
}

function EmptyRow({ message, colSpan = 4 }: { message: string; colSpan?: number }) {
    return (
        <tr>
            <td colSpan={colSpan} className="text-muted-foreground px-4 py-6 text-center text-sm">
                {message}
            </td>
        </tr>
    );
}

export default function ReportsIndex({ report }: ReportsPageProps) {
    const [from, setFrom] = useState(report.period.from);
    const [to, setTo] = useState(report.period.to);

    useEffect(() => {
        setFrom(report.period.from);
        setTo(report.period.to);
    }, [report.period.from, report.period.to]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get('/reports', { from: from || undefined, to: to || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const exportUrl = `/reports/export?${new URLSearchParams({ from: report.period.from, to: report.period.to }).toString()}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan penjualan" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4 pb-28 md:p-6 md:pb-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Laporan penjualan</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Periode {report.period.from} s/d {report.period.to} (Asia/Jakarta). Omzet hanya order Paid berdasarkan waktu bayar.
                        </p>
                    </div>
                    <Button asChild variant="outline" size="sm">
                        <a href={exportUrl} download>
                            <Download aria-hidden="true" />
                            Unduh CSV
                        </a>
                    </Button>
                </div>

                <FlashMessage />

                <form onSubmit={submit} className="rounded-xl border p-3 md:p-4" aria-label="Filter periode laporan">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="report-from">Dari tanggal</Label>
                            <Input id="report-from" type="date" value={from} onChange={(event) => setFrom(event.target.value)} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="report-to">Sampai tanggal</Label>
                            <Input id="report-to" type="date" value={to} onChange={(event) => setTo(event.target.value)} />
                        </div>
                    </div>
                    <div className="mt-3 flex flex-wrap gap-2">
                        <Button type="submit" size="sm">
                            Terapkan
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => {
                                setFrom('');
                                setTo('');
                                router.get('/reports', {}, { preserveState: true, preserveScroll: true, replace: true });
                            }}
                        >
                            Hari ini
                        </Button>
                    </div>
                </form>

                <section className="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Ringkasan penjualan">
                    {[
                        { label: 'Omzet', value: formatRupiah(report.summary.revenue) },
                        { label: 'Transaksi Paid', value: String(report.summary.transactions) },
                        { label: 'Item terjual', value: String(report.summary.items_sold) },
                        { label: 'Rata-rata transaksi', value: formatRupiah(report.summary.average_transaction) },
                    ].map((metric) => (
                        <div key={metric.label} className="rounded-xl border p-4">
                            <p className="text-muted-foreground text-xs">{metric.label}</p>
                            <p className="mt-1 text-lg font-semibold whitespace-nowrap">{metric.value}</p>
                        </div>
                    ))}
                </section>

                <section className="flex flex-col gap-3">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="font-semibold">Penjualan per metode pembayaran</h2>
                        <Link href={historyUrl(report.period.from, report.period.to)} className="text-primary text-xs underline">
                            Lihat transaksi
                        </Link>
                    </div>
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[520px] text-sm">
                            <thead className="bg-muted/30 border-b text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Metode</th>
                                    <th className="px-4 py-3 text-right font-medium">Transaksi</th>
                                    <th className="px-4 py-3 text-right font-medium">Omzet</th>
                                    <th className="px-4 py-3 text-right font-medium">Detail</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {report.methods.length === 0 ? (
                                    <EmptyRow message="Belum ada pembayaran pada periode ini." />
                                ) : (
                                    report.methods.map((method) => (
                                        <tr key={method.method}>
                                            <td className="px-4 py-3 font-medium">{method.label}</td>
                                            <td className="px-4 py-3 text-right">{method.transactions}</td>
                                            <td className="px-4 py-3 text-right font-medium">{formatRupiah(method.revenue)}</td>
                                            <td className="px-4 py-3 text-right">
                                                <Link
                                                    href={historyUrl(report.period.from, report.period.to, method.method)}
                                                    className="text-primary text-xs underline"
                                                >
                                                    Lihat
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="flex flex-col gap-3">
                    <h2 className="font-semibold">Menu terlaris</h2>
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[560px] text-sm">
                            <thead className="bg-muted/30 border-b text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Menu</th>
                                    <th className="px-4 py-3 font-medium">Variasi</th>
                                    <th className="px-4 py-3 text-right font-medium">Terjual</th>
                                    <th className="px-4 py-3 text-right font-medium">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {report.top_items.length === 0 ? (
                                    <EmptyRow message="Belum ada item dari order Paid pada periode ini." />
                                ) : (
                                    report.top_items.map((item, index) => (
                                        <tr key={`${item.product_name}-${item.variant_name}-${index}`}>
                                            <td className="px-4 py-3 font-medium">{item.product_name}</td>
                                            <td className="text-muted-foreground px-4 py-3">{item.variant_name}</td>
                                            <td className="px-4 py-3 text-right">
                                                {item.quantity} {item.unit}
                                            </td>
                                            <td className="px-4 py-3 text-right font-medium">{formatRupiah(item.revenue)}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="flex flex-col gap-3">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="font-semibold">Rekap shift ({report.shift_count})</h2>
                        <Link href="/shifts" className="text-primary text-xs underline">
                            Buka shift
                        </Link>
                    </div>
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[720px] text-sm">
                            <thead className="bg-muted/30 border-b text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Dibuka</th>
                                    <th className="px-4 py-3 font-medium">Kasir</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 text-right font-medium">Diharapkan</th>
                                    <th className="px-4 py-3 text-right font-medium">Aktual</th>
                                    <th className="px-4 py-3 text-right font-medium">Selisih</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {report.shifts.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="text-muted-foreground px-4 py-6 text-center text-sm">
                                            Belum ada shift pada periode ini.
                                        </td>
                                    </tr>
                                ) : (
                                    report.shifts.map((shift) => (
                                        <tr key={shift.id}>
                                            <td className="text-muted-foreground px-4 py-3 text-xs">{formatDateTime(shift.opened_at)}</td>
                                            <td className="px-4 py-3">{shift.cashier}</td>
                                            <td className="px-4 py-3">{shift.status === 'OPEN' ? 'Aktif' : 'Ditutup'}</td>
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
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {report.shift_count > report.shifts.length && (
                        <p className="text-muted-foreground text-xs">
                            Menampilkan {report.shifts.length} shift terakhir. Buka halaman shift untuk riwayat lengkap.
                        </p>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
