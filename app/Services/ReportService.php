<?php

namespace App\Services;

use App\Actions\Reports\ResolveDateRange;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shift;

/**
 * Laporan Phase 5. Semua angka berasal dari order PAID berdasarkan paid_at
 * dan dapat ditelusuri ke payment/order sumbernya (BUSINESS-RULES §9).
 */
final class ReportService
{
    public function __construct(
        private readonly ResolveDateRange $resolveDateRange,
        private readonly ShiftService $shifts,
    ) {}

    /**
     * @return array{
     *     period: array{from: string, to: string},
     *     summary: array{revenue: int, transactions: int, items_sold: int, average_transaction: int},
     *     methods: list<array{method: string, label: string, transactions: int, revenue: int}>,
     *     top_items: list<array{product_name: string, variant_name: string, unit: string, quantity: int, revenue: int}>,
     *     shifts: list<array{id: int, opened_at: string, cashier: string, status: string, opening_cash: int, expected_cash: int|null, actual_cash: int|null, difference: int|null}>,
     *     shift_count: int
     * }
     */
    public function generate(?string $from, ?string $to): array
    {
        $range = $this->resolveDateRange->handle($from, $to);

        $paidOrders = Order::query()
            ->where('orders.status', Order::STATUS_PAID)
            ->whereNotNull('orders.paid_at')
            ->whereBetween('orders.paid_at', [$range['from'], $range['to']]);

        $transactions = (clone $paidOrders)->count();
        $revenue = (int) (clone $paidOrders)
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->where('payments.status', Payment::STATUS_PAID)
            ->sum('payments.amount');
        $itemsSold = (int) OrderItem::query()
            ->whereIn('order_id', (clone $paidOrders)->select('orders.id'))
            ->sum('quantity');

        $methods = Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.status', Payment::STATUS_PAID)
            ->where('orders.status', Order::STATUS_PAID)
            ->whereBetween('orders.paid_at', [$range['from'], $range['to']])
            ->selectRaw('payments.payment_method as method, COUNT(DISTINCT orders.id) as transactions, SUM(payments.amount) as revenue')
            ->groupBy('payments.payment_method')
            ->orderByDesc('revenue')
            ->orderBy('method')
            ->get()
            ->map(fn ($row): array => [
                'method' => $row->method,
                'label' => Payment::METHOD_LABELS[$row->method] ?? $row->method,
                'transactions' => (int) $row->transactions,
                'revenue' => (int) $row->revenue,
            ])
            ->all();

        $topItems = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', Order::STATUS_PAID)
            ->whereBetween('orders.paid_at', [$range['from'], $range['to']])
            ->selectRaw(
                'order_items.product_name_snapshot as product_name, '
                .'order_items.variant_name_snapshot as variant_name, '
                .'order_items.unit_snapshot as unit, '
                .'SUM(order_items.quantity) as quantity, '
                .'SUM(order_items.subtotal) as revenue',
            )
            ->groupBy('order_items.product_name_snapshot', 'order_items.variant_name_snapshot', 'order_items.unit_snapshot')
            ->orderByDesc('quantity')
            ->orderBy('product_name')
            ->limit((int) config('pos.reports.top_items_limit', 10))
            ->get()
            ->map(fn ($row): array => [
                'product_name' => $row->product_name,
                'variant_name' => $row->variant_name,
                'unit' => $row->unit,
                'quantity' => (int) $row->quantity,
                'revenue' => (int) $row->revenue,
            ])
            ->all();

        $shiftQuery = Shift::query()
            ->with('cashier:id,name')
            ->whereBetween('opened_at', [$range['from'], $range['to']])
            ->orderByDesc('opened_at');
        $shiftCount = (clone $shiftQuery)->count();

        $shifts = (clone $shiftQuery)
            ->limit(20)
            ->get();

        $cashByShift = Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->whereIn('orders.shift_id', $shifts->pluck('id'))
            ->where('orders.status', Order::STATUS_PAID)
            ->where('payments.status', Payment::STATUS_PAID)
            ->where('payments.payment_method', Payment::METHOD_CASH)
            ->selectRaw('orders.shift_id, SUM(payments.amount) as cash_total')
            ->groupBy('orders.shift_id')
            ->pluck('cash_total', 'orders.shift_id');

        $shiftRows = $shifts
            ->map(function (Shift $shift): array {
                return [
                    'id' => $shift->id,
                    'opened_at' => $shift->opened_at?->toJSON(),
                    'cashier' => $shift->cashier?->name ?? '-',
                    'status' => $shift->status,
                    'opening_cash' => (int) $shift->opening_cash,
                    'expected_cash' => $shift->status === Shift::STATUS_CLOSED
                        ? ($shift->expected_cash === null ? null : (int) $shift->expected_cash)
                        : $this->shifts->expectedCash($shift),
                    'actual_cash' => $shift->actual_cash === null ? null : (int) $shift->actual_cash,
                    'difference' => $shift->difference === null ? null : (int) $shift->difference,
                ];
            })
            ->all();

        return [
            'period' => [
                'from' => $range['from_label'],
                'to' => $range['to_label'],
            ],
            'summary' => [
                'revenue' => $revenue,
                'transactions' => $transactions,
                'items_sold' => $itemsSold,
                'average_transaction' => $transactions > 0 ? (int) round($revenue / $transactions) : 0,
            ],
            'methods' => $methods,
            'top_items' => $topItems,
            'shifts' => $shiftRows,
            'shift_count' => $shiftCount,
        ];
    }

    /**
     * Export sederhana memakai filter dan angka yang sama dengan halaman laporan.
     * Angka ditulis mentah agar dapat diolah spreadsheet.
     *
     * @param  array<string, mixed>  $report
     */
    public function toCsv(array $report): string
    {
        $handle = fopen('php://temp', 'w+');

        fputcsv($handle, ['Laporan Penjualan']);
        fputcsv($handle, ['Periode', $report['period']['from'].' s/d '.$report['period']['to']]);
        fputcsv($handle, ['Omzet', $report['summary']['revenue']]);
        fputcsv($handle, ['Transaksi Paid', $report['summary']['transactions']]);
        fputcsv($handle, ['Item Terjual', $report['summary']['items_sold']]);
        fputcsv($handle, ['Rata-rata Transaksi', $report['summary']['average_transaction']]);
        fputcsv($handle, []);

        fputcsv($handle, ['Metode Pembayaran', 'Transaksi', 'Omzet']);
        foreach ($report['methods'] as $method) {
            fputcsv($handle, [$method['label'], $method['transactions'], $method['revenue']]);
        }
        fputcsv($handle, []);

        fputcsv($handle, ['Menu', 'Variasi', 'Satuan', 'Quantity', 'Subtotal']);
        foreach ($report['top_items'] as $item) {
            fputcsv($handle, [$item['product_name'], $item['variant_name'], $item['unit'], $item['quantity'], $item['revenue']]);
        }
        fputcsv($handle, []);

        fputcsv($handle, ['Shift', 'Kasir', 'Status', 'Modal', 'Diharapkan', 'Aktual', 'Selisih']);
        foreach ($report['shifts'] as $shift) {
            fputcsv($handle, [
                $shift['opened_at'],
                $shift['cashier'],
                $shift['status'],
                $shift['opening_cash'],
                $shift['expected_cash'],
                $shift['actual_cash'],
                $shift['difference'],
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF".($content === false ? '' : $content);
    }
}
