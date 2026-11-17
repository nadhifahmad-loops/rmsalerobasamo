<?php

namespace Database\Factories;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.now('Asia/Jakarta')->format('Ymd').'-'.Str::upper(Str::random(6)),
            'order_type' => Order::TYPE_DINE_IN,
            'dining_table_id' => DiningTable::factory(),
            'table_label_snapshot' => 'Meja '.fake()->numberBetween(1, 99),
            'cashier_id' => User::factory(),
            'shift_id' => null,
            'status' => Order::STATUS_OPEN,
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'service_charge' => 0,
            'total' => 0,
            'notes' => null,
        ];
    }

    /**
     * Indicate a takeaway order, which must never have a dining table.
     */
    public function takeaway(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_type' => Order::TYPE_TAKEAWAY,
            'dining_table_id' => null,
            'table_label_snapshot' => null,
        ]);
    }

    /**
     * Indicate that the order belongs to a shift.
     */
    public function inShift(?Shift $shift = null): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_id' => $shift?->id ?? Shift::factory(),
        ]);
    }

    /**
     * Indicate that the order has been paid and its items are locked.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Indicate that the order is awaiting payment.
     */
    public function awaitingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_AWAITING_PAYMENT,
        ]);
    }

    /**
     * Indicate that the order has been voided.
     */
    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_VOIDED,
            'voided_at' => now(),
            'void_reason' => 'Dibatalkan untuk keperluan pengujian.',
        ]);
    }
}
