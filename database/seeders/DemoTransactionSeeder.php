<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoTransactionSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoTransactionSeeder tidak boleh dijalankan di production.');
        }

        $cashier = User::whereHas('role', fn ($q) => $q->where('name', 'kasir'))->firstOrFail();
        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->firstOrFail();
        $owner = User::whereHas('role', fn ($q) => $q->where('name', 'owner'))->firstOrFail();

        $products = Product::query()->get()->keyBy('name');
        $variants = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->select('product_variants.*', 'products.name as product_name')
            ->get()
            ->keyBy(fn (ProductVariant $v): string => $v->product_name.'|'.$v->name);
        $tables = DiningTable::query()->get()->keyBy('table_number');

        $d1 = CarbonImmutable::create(2026, 11, 17, 0, 0, 0, 'Asia/Jakarta');
        $d2 = CarbonImmutable::create(2026, 11, 18, 0, 0, 0, 'Asia/Jakarta');

        $shifts = [
            'A' => Shift::firstOrCreate(
                ['cashier_id' => $cashier->id, 'opened_at' => $d1->setTime(8, 0)],
                ['opening_cash' => 150000, 'status' => Shift::STATUS_CLOSED, 'closed_at' => $d1->setTime(16, 0)],
            ),
            'B' => Shift::firstOrCreate(
                ['cashier_id' => $cashier->id, 'opened_at' => $d2->setTime(0, 5)],
                ['opening_cash' => 150000, 'status' => Shift::STATUS_CLOSED, 'closed_at' => $d2->setTime(1, 0)],
            ),
            'C' => Shift::firstOrCreate(
                ['cashier_id' => $cashier->id, 'status' => Shift::STATUS_OPEN],
                ['opening_cash' => 150000, 'opened_at' => $d2->setTime(1, 5)],
            ),
        ];

        $rows = <<<'DATA'
DEMO-20261117-001|DINE_IN|01|A|PAID|CASH|2026-11-17 09:15|Nasi Rendang:Reguler:1,Ayam Pop:Reguler:1,Teh Manis:Reguler:1|Dummy dine in|
DEMO-20261117-002|TAKEAWAY||A|PAID|QRIS|2026-11-17 11:40|Sate Ayam:Reguler:2,Nasi Goreng Padang:Reguler:1|Dummy takeaway|
DEMO-20261117-003|DINE_IN|02|A|VOIDED||2026-11-17 12:10|Nasi Putih:Reguler:1,Balado Telur:Reguler:1|Dummy dibatalkan|Pelanggan membatalkan pesanan
DEMO-20261118-001|DINE_IN|03|B|PAID|TRANSFER|2026-11-18 00:20|Rendang Daging:Reguler:1,Gulai Kambing:Reguler:1,Air Mineral:Reguler:1||
DEMO-20261118-002|TAKEAWAY||B|PAID|CASH|2026-11-18 00:40|Ayam Bakar:Reguler:1,Kopi Hitam:Hangat:1,Pisang Goreng:Reguler:1|Dummy takeaway|
DEMO-20261118-003|DINE_IN|05|B|PAID|DEBIT|2026-11-18 00:55|Ikan Bakar:Reguler:1,Balado Telur:Pedas:1,Es Teh Manis:Reguler:1||
DEMO-20261118-004|DINE_IN|07|C|PAID|CASH|2026-11-18 01:15|Nasi Ayam Pop:Reguler:1,Sate Kambing:Reguler:1,Teh Tawar:Hangat:1||
DEMO-20261118-005|TAKEAWAY||C|PAID|QRIS|2026-11-18 01:30|Mie Goreng Padang:Pedas:1,Air Mineral:Reguler:1|Dummy takeaway|
DEMO-20261118-006|DINE_IN|09|C|OPEN||2026-11-18 01:45|Nasi Rendang:Extra Daging:1,Sambal Hijau:Reguler:1|Dummy order aktif di Meja 09|
DEMO-20261118-007|TAKEAWAY||C|AWAITING_PAYMENT||2026-11-18 02:00|Nasi Goreng Padang:Pedas:1,Es Teh Manis:Large:1|Dummy menunggu pembayaran|
DATA;

        DB::transaction(function () use ($cashier, $admin, $owner, $products, $variants, $tables, $shifts, $rows): void {
            foreach (explode("\n", trim($rows)) as $line) {
                $line = rtrim($line, "\r");

                [$number, $type, $tableNo, $shiftKey, $statusText, $method, $atText, $itemText, $notes, $voidReason] = array_pad(explode('|', $line, 10), 10, '');

                $shift = $shifts[$shiftKey];
                $table = $tableNo !== '' ? $tables->get($tableNo) : null;
                $at = CarbonImmutable::parse($atText, 'Asia/Jakarta');
                $status = match ($statusText) {
                    'PAID' => Order::STATUS_PAID,
                    'VOIDED' => Order::STATUS_VOIDED,
                    'AWAITING_PAYMENT' => Order::STATUS_AWAITING_PAYMENT,
                    default => Order::STATUS_OPEN,
                };

                $subtotal = 0;
                $itemRows = [];

                foreach (explode(',', $itemText) as $item) {
                    [$productName, $variantName, $quantity] = explode(':', $item);
                    $variant = $variants->get($productName.'|'.$variantName);
                    $product = $products->get($productName);

                    if ($variant === null || $product === null) {
                        throw new RuntimeException("Varian katalog {$productName} - {$variantName} tidak ditemukan.");
                    }

                    $lineTotal = (int) $variant->price * (int) $quantity;
                    $subtotal += $lineTotal;

                    $itemRows[] = [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'product_name_snapshot' => $product->name,
                        'variant_name_snapshot' => $variant->name,
                        'unit_snapshot' => $product->unit,
                        'price_snapshot' => (int) $variant->price,
                        'quantity' => (int) $quantity,
                        'subtotal' => $lineTotal,
                    ];
                }

                $isPaid = $status === Order::STATUS_PAID;

                $order = Order::firstOrCreate(
                    ['order_number' => $number],
                    [
                        'order_type' => $type,
                        'dining_table_id' => $table?->id,
                        'table_label_snapshot' => $table?->label,
                        'cashier_id' => $cashier->id,
                        'shift_id' => $shift->id,
                        'status' => $status,
                        'subtotal' => $subtotal,
                        'discount' => 0,
                        'tax' => 0,
                        'service_charge' => 0,
                        'total' => $subtotal,
                        'notes' => $notes !== '' ? $notes : null,
                        'paid_at' => $isPaid ? $at : null,
                        'voided_at' => $status === Order::STATUS_VOIDED ? $at : null,
                        'voided_by' => $status === Order::STATUS_VOIDED ? $admin->id : null,
                        'void_reason' => $voidReason !== '' ? $voidReason : null,
                    ],
                );

                if (! $order->wasRecentlyCreated) {
                    continue;
                }

                foreach ($itemRows as $itemRow) {
                    OrderItem::create(['order_id' => $order->id] + $itemRow);
                }

                self::logOnce('order.created', 'order', $order->id, $cashier->id, [
                    'order_number' => $order->order_number,
                    'source' => 'demo-seeder',
                ]);

                if ($isPaid) {
                    $isCash = $method === Payment::METHOD_CASH;
                    $received = $isCash ? (int) (ceil($subtotal / 5000) * 5000) : null;

                    $payment = Payment::create([
                        'order_id' => $order->id,
                        'payment_number' => 'PAY-'.$order->order_number,
                        'idempotency_key' => 'demo-'.$order->order_number,
                        'payment_method' => $method,
                        'amount' => $subtotal,
                        'received_amount' => $received,
                        'change_amount' => $received === null ? 0 : $received - $subtotal,
                        'status' => Payment::STATUS_PAID,
                        'processed_by' => $cashier->id,
                    ]);

                    self::logOnce('payment.recorded', 'payment', $payment->id, $cashier->id, [
                        'order_number' => $order->order_number,
                        'payment_number' => $payment->payment_number,
                        'method' => $payment->payment_method,
                    ]);
                }

                if ($status === Order::STATUS_VOIDED) {
                    self::logOnce('order.voided', 'order', $order->id, $admin->id, [
                        'order_number' => $order->order_number,
                        'reason' => $voidReason,
                    ]);
                }
            }

            $cashTotals = Payment::query()
                ->join('orders', 'orders.id', '=', 'payments.order_id')
                ->whereIn('orders.shift_id', [$shifts['A']->id, $shifts['B']->id])
                ->where('orders.status', Order::STATUS_PAID)
                ->where('payments.status', Payment::STATUS_PAID)
                ->where('payments.payment_method', Payment::METHOD_CASH)
                ->selectRaw('orders.shift_id, SUM(payments.amount) as cash_total')
                ->groupBy('orders.shift_id')
                ->pluck('cash_total', 'orders.shift_id');

            foreach ([[$shifts['A'], -2000], [$shifts['B'], 3000]] as [$shift, $adjustment]) {
                $expected = (int) $shift->opening_cash + (int) ($cashTotals[$shift->id] ?? 0);
                $actual = $expected + $adjustment;

                $shift->update([
                    'expected_cash' => $expected,
                    'actual_cash' => $actual,
                    'difference' => $actual - $expected,
                    'status' => Shift::STATUS_CLOSED,
                ]);

                self::logOnce('shift.closed', 'shift', $shift->id, $cashier->id, [
                    'expected_cash' => $expected,
                    'actual_cash' => $actual,
                    'difference' => $actual - $expected,
                ]);
            }

            self::logOnce('shift.opened', 'shift', $shifts['C']->id, $cashier->id, [
                'opening_cash' => (int) $shifts['C']->opening_cash,
                'source' => 'demo-seeder',
            ]);

            self::logOnce('demo.seeded', 'catalog', null, $owner->id, [
                'source' => 'DemoDataSeeder',
                'environment' => app()->environment(),
            ]);
        });

        $this->command?->info('Dummy siap: 3 shift, 10 order, 7 pembayaran.');
    }

    /** @param array<string, mixed> $metadata */
    private static function logOnce(
        string $action,
        string $entityType,
        int|string|null $entityId,
        int $userId,
        array $metadata,
    ): void {
        $exists = AuditLog::query()
            ->where('action', $action)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->exists();

        if ($exists) {
            return;
        }

        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'ip_address' => '127.0.0.1',
        ]);
    }
}
