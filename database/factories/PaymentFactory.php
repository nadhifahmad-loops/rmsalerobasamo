<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_number' => 'PAY-'.now('Asia/Jakarta')->format('Ymd').'-'.Str::upper(Str::random(6)),
            'idempotency_key' => Str::random(32),
            'payment_method' => Payment::METHOD_CASH,
            'amount' => 25_000,
            'received_amount' => 25_000,
            'change_amount' => 0,
            'status' => Payment::STATUS_PAID,
            'processed_by' => User::factory(),
        ];
    }
}
