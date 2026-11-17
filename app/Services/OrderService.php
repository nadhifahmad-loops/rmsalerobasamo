<?php

namespace App\Services;

use App\Actions\Catalog\ResolveOrderItemSnapshot;
use App\Actions\Orders\RecalculateOrderTotals;
use App\Models\AuditLog;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pusat aturan bisnis order Phase 3: satu order aktif per meja, snapshot item
 * dari database, dan perhitungan ulang yang dilakukan backend.
 */
final class OrderService
{
    public function __construct(
        private readonly ResolveOrderItemSnapshot $resolveSnapshot,
        private readonly RecalculateOrderTotals $recalculateTotals,
    ) {}

    /**
     * BUSINESS-RULES §2: cek ketersediaan dan pembuatan order berada dalam satu
     * transaction dengan row lock pada meja.
     */
    public function createDineInOrder(DiningTable $table, int $cashierId, ?string $notes): Order
    {
        $order = DB::transaction(function () use ($table, $cashierId, $notes): Order {
            $lockedTable = DiningTable::query()
                ->whereKey($table->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedTable->is_active) {
                throw ValidationException::withMessages([
                    'dining_table_id' => 'Meja tidak aktif dan tidak dapat dipakai untuk pesanan baru.',
                ]);
            }

            if (Order::query()
                ->where('dining_table_id', $lockedTable->id)
                ->active()
                ->exists()) {
                throw ValidationException::withMessages([
                    'dining_table_id' => 'Meja sudah memiliki pesanan aktif. Buka pesanan itu atau pilih meja lain.',
                ]);
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'order_type' => Order::TYPE_DINE_IN,
                'dining_table_id' => $lockedTable->id,
                'table_label_snapshot' => $lockedTable->displayLabel(),
                'cashier_id' => $cashierId,
                'shift_id' => $this->activeShiftIdFor($cashierId),
                'status' => Order::STATUS_OPEN,
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'service_charge' => 0,
                'total' => 0,
                'notes' => $notes ?: null,
            ]);

            AuditLog::record('orders.created', 'order', $order->id, [
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'dining_table_id' => $lockedTable->id,
                'table_label_snapshot' => $order->table_label_snapshot,
                'shift_id' => $order->shift_id,
            ]);

            return $order;
        });

        return $order->load('diningTable', 'cashier', 'items');
    }

    /**
     * Takeaway tidak memakai meja dan langsung menuju checkout. Order, item,
     * snapshot, dan total dibuat dalam satu transaction agar tidak ada order
     * kosong atau setengah terisi (USER-FLOWS §2, BUSINESS-RULES §1).
     *
     * @param  list<array{product_id: int, product_variant_id: int, quantity: int, notes?: string|null}>  $items
     */
    public function createTakeawayOrder(array $items, int $cashierId, ?string $notes): Order
    {
        $order = DB::transaction(function () use ($items, $cashierId, $notes): Order {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'order_type' => Order::TYPE_TAKEAWAY,
                'dining_table_id' => null,
                'table_label_snapshot' => null,
                'cashier_id' => $cashierId,
                'shift_id' => $this->activeShiftIdFor($cashierId),
                'status' => Order::STATUS_OPEN,
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'service_charge' => 0,
                'total' => 0,
                'notes' => $notes ?: null,
            ]);

            foreach ($items as $item) {
                $snapshot = $this->resolveSnapshot->handle(
                    (int) $item['product_id'],
                    (int) $item['product_variant_id'],
                );

                $savedItem = $order->items()->create([
                    ...$snapshot,
                    'quantity' => (int) $item['quantity'],
                    'subtotal' => $snapshot['price_snapshot'] * (int) $item['quantity'],
                    'notes' => isset($item['notes']) && $item['notes'] !== '' ? $item['notes'] : null,
                ]);

                AuditLog::record('orders.item_added', 'order_item', $savedItem->id, [
                    'order_id' => $order->id,
                    'product_id' => $savedItem->product_id,
                    'product_variant_id' => $savedItem->product_variant_id,
                    'quantity' => $savedItem->quantity,
                    'price_snapshot' => $savedItem->price_snapshot,
                ]);
            }

            $this->recalculateTotals->handle($order);

            AuditLog::record('orders.created', 'order', $order->id, [
                'order_number' => $order->order_number,
                'order_type' => Order::TYPE_TAKEAWAY,
                'shift_id' => $order->shift_id,
                'item_count' => count($items),
            ]);

            return $order;
        });

        return $order->load('cashier', 'items');
    }

    public function addItem(Order $order, int $productId, int $variantId, int $quantity, ?string $notes): OrderItem
    {
        $item = DB::transaction(function () use ($order, $productId, $variantId, $quantity, $notes): OrderItem {
            $lockedOrder = $this->lockEditableOrder($order);
            $snapshot = $this->resolveSnapshot->handle($productId, $variantId);

            $item = $lockedOrder->items()->create([
                ...$snapshot,
                'quantity' => $quantity,
                'subtotal' => $snapshot['price_snapshot'] * $quantity,
                'notes' => $notes ?: null,
            ]);

            $this->recalculateTotals->handle($lockedOrder);

            AuditLog::record('orders.item_added', 'order_item', $item->id, [
                'order_id' => $lockedOrder->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'price_snapshot' => $item->price_snapshot,
            ]);

            return $item;
        });

        return $item->load('order');
    }

    public function updateItem(Order $order, OrderItem $item, int $quantity, ?string $notes): OrderItem
    {
        abort_unless($item->order_id === $order->id, 404);

        $saved = DB::transaction(function () use ($order, $item, $quantity, $notes): OrderItem {
            $lockedOrder = $this->lockEditableOrder($order);
            $lockedItem = OrderItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            // Harga snapshot sengaja tidak disentuh meski master harga berubah.
            $lockedItem->update([
                'quantity' => $quantity,
                'subtotal' => $lockedItem->price_snapshot * $quantity,
                'notes' => $notes ?: null,
            ]);

            $this->recalculateTotals->handle($lockedOrder);

            AuditLog::record('orders.item_updated', 'order_item', $lockedItem->id, [
                'order_id' => $lockedOrder->id,
                'quantity' => $lockedItem->quantity,
                'price_snapshot' => $lockedItem->price_snapshot,
            ]);

            return $lockedItem;
        });

        return $saved->refresh();
    }

    public function removeItem(Order $order, OrderItem $item): void
    {
        abort_unless($item->order_id === $order->id, 404);

        DB::transaction(function () use ($order, $item): void {
            $lockedOrder = $this->lockEditableOrder($order);
            $lockedItem = OrderItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            $removed = [
                'product_name_snapshot' => $lockedItem->product_name_snapshot,
                'variant_name_snapshot' => $lockedItem->variant_name_snapshot,
                'price_snapshot' => $lockedItem->price_snapshot,
                'quantity' => $lockedItem->quantity,
            ];

            $lockedItem->delete();
            $this->recalculateTotals->handle($lockedOrder);

            AuditLog::record('orders.item_removed', 'order_item', $lockedItem->id, [
                'order_id' => $lockedOrder->id,
                'removed' => $removed,
            ]);
        });
    }

    public function updateNotes(Order $order, ?string $notes): Order
    {
        $updated = DB::transaction(function () use ($order, $notes): Order {
            $lockedOrder = $this->lockEditableOrder($order);
            $lockedOrder->update(['notes' => $notes ?: null]);

            AuditLog::record('orders.notes_updated', 'order', $lockedOrder->id, [
                'order_number' => $lockedOrder->order_number,
            ]);

            return $lockedOrder;
        });

        return $updated->refresh();
    }

    private function lockEditableOrder(Order $order): Order
    {
        $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

        if (! in_array($locked->status, Order::EDITABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan sudah tidak dapat diubah.',
            ]);
        }

        return $locked;
    }

    /**
     * Kaitkan order ke shift aktif kasir bila ada. Kunci baris user membuat
     * pembuatan order menunggu penutupan shift yang sedang berlangsung.
     * Kebijakan wajib shift belum dikonfirmasi, sehingga null diizinkan.
     */
    private function activeShiftIdFor(int $cashierId): ?int
    {
        User::query()->whereKey($cashierId)->lockForUpdate()->firstOrFail();

        return Shift::query()
            ->where('cashier_id', $cashierId)
            ->open()
            ->latest('opened_at')
            ->value('id');
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-'.now('Asia/Jakarta')->format('Ymd').'-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $number = $prefix.Str::upper(Str::random(6));

            if (! Order::query()->where('order_number', $number)->exists()) {
                return $number;
            }
        }

        throw new \RuntimeException('Gagal membuat nomor order yang unik.');
    }
}
