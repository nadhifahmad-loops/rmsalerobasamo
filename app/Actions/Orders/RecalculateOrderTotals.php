<?php

namespace App\Actions\Orders;

use App\Models\Order;

/**
 * Satu-satunya aturan perhitungan nominal order (BUSINESS-RULES §4).
 *
 * Pemanggil harus memegang lock order bila action ini dipakai di dalam alur
 * pembayaran atau mutasi item yang rawan concurrency.
 */
final class RecalculateOrderTotals
{
    public function handle(Order $order): Order
    {
        $subtotal = (int) $order->items()->sum('subtotal');
        $discount = min(max(0, (int) $order->discount), $subtotal);
        $tax = max(0, (int) $order->tax);
        $serviceCharge = max(0, (int) $order->service_charge);
        $total = max(0, $subtotal - $discount + $tax + $serviceCharge);

        if ((int) $order->subtotal !== $subtotal || (int) $order->total !== $total) {
            $order->update([
                'subtotal' => $subtotal,
                'total' => $total,
            ]);
        }

        return $order->refresh();
    }
}
