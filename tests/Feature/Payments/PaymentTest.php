<?php

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Str;

function makePayableOrder(int $unitPrice = 25_000, int $quantity = 2): Order
{
    $order = Order::factory()->create();
    $subtotal = $unitPrice * $quantity;

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'price_snapshot' => $unitPrice,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
    ]);

    $order->update([
        'subtotal' => $subtotal,
        'total' => $subtotal,
    ]);

    return $order->fresh();
}

function cashPayload(array $overrides = []): array
{
    return array_merge([
        'payment_method' => Payment::METHOD_CASH,
        'received_amount' => 50_000,
        'idempotency_key' => Str::uuid()->toString(),
    ], $overrides);
}

it('kasir dapat membayar tunai tepat dan meja langsung dilepas', function () {
    $order = makePayableOrder();
    $kasir = User::factory()->create();

    $this->actingAs($kasir)
        ->post(route('orders.pay', $order), cashPayload())
        ->assertRedirect(route('orders.receipt', $order));

    $payment = Payment::firstOrFail();
    $freshOrder = $order->fresh();

    expect($payment->amount)->toBe(50_000)
        ->and($payment->received_amount)->toBe(50_000)
        ->and($payment->change_amount)->toBe(0)
        ->and($payment->payment_method)->toBe(Payment::METHOD_CASH)
        ->and($payment->processed_by)->toBe($kasir->id)
        ->and($payment->payment_number)->toStartWith('PAY-')
        ->and($freshOrder->status)->toBe(Order::STATUS_PAID)
        ->and($freshOrder->paid_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'payments.created')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'orders.paid')->exists())->toBeTrue();

    $this->actingAs($kasir)
        ->post(route('orders.store'), ['dining_table_id' => $order->dining_table_id])
        ->assertRedirect();

    expect(Order::where('dining_table_id', $order->dining_table_id)->active()->count())->toBe(1);
});

it('tunai lebih besar menghitung kembalian', function () {
    $order = makePayableOrder(20_000, 2);

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), cashPayload(['received_amount' => 75_000]))
        ->assertRedirect(route('orders.receipt', $order));

    $payment = Payment::firstOrFail();

    expect($payment->amount)->toBe(40_000)
        ->and($payment->received_amount)->toBe(75_000)
        ->and($payment->change_amount)->toBe(35_000);
});

it('tunai kurang atau negatif ditolak tanpa mengubah status order', function (int $received) {
    $order = makePayableOrder();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), cashPayload(['received_amount' => $received]))
        ->assertSessionHasErrors('received_amount');

    expect(Payment::count())->toBe(0)
        ->and($order->fresh()->status)->toBe(Order::STATUS_OPEN);
})->with([-1, 49_999]);

it('nilai total dan status dari browser diabaikan backend', function () {
    $order = makePayableOrder();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), cashPayload([
            'amount' => 1,
            'subtotal' => 1,
            'total' => 1,
            'status' => Order::STATUS_PAID,
            'cashier_id' => 9999,
            'paid_at' => now()->toIso8601String(),
        ]))
        ->assertRedirect(route('orders.receipt', $order));

    $payment = Payment::firstOrFail();

    expect($payment->amount)->toBe(50_000)
        ->and($payment->processed_by)->not->toBe(9999)
        ->and($order->fresh()->total)->toBe(50_000);
});

it('metode non-tunai dicatat dengan nominal sama dengan total order', function (string $method) {
    $order = makePayableOrder(25_000, 3);

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), [
            'payment_method' => $method,
            'idempotency_key' => Str::uuid()->toString(),
            'received_amount' => 999_999,
            'amount' => 1,
        ])
        ->assertRedirect(route('orders.receipt', $order));

    $payment = Payment::firstOrFail();

    expect($payment->payment_method)->toBe($method)
        ->and($payment->amount)->toBe(75_000)
        ->and($payment->received_amount)->toBeNull()
        ->and($payment->change_amount)->toBe(0);
})->with([Payment::METHOD_QRIS, Payment::METHOD_TRANSFER, Payment::METHOD_DEBIT]);

it('metode pembayaran dan kunci idempotency divalidasi', function (array $payload, string $field) {
    $order = makePayableOrder();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), $payload)
        ->assertSessionHasErrors($field);

    expect(Payment::count())->toBe(0);
})->with([
    'metode tidak dikenal' => [cashPayload(['payment_method' => 'CASH_OP']), 'payment_method'],
    'kunci terlalu pendek' => [cashPayload(['idempotency_key' => 'abc']), 'idempotency_key'],
    'kunci mengandung karakter tidak valid' => [cashPayload(['idempotency_key' => str_repeat('a', 16).'!']), 'idempotency_key'],
    'tunai tanpa nominal diterima' => [['payment_method' => Payment::METHOD_CASH, 'idempotency_key' => Str::uuid()->toString()], 'received_amount'],
]);

it('order yang sudah paid tidak dapat dibayar ulang', function () {
    $order = makePayableOrder();
    $kasir = User::factory()->create();

    $this->actingAs($kasir)->post(route('orders.pay', $order), cashPayload())->assertRedirect();

    $this->actingAs($kasir)
        ->post(route('orders.pay', $order), cashPayload())
        ->assertSessionHasErrors('order');

    expect(Payment::count())->toBe(1);
});

it('order yang dibatalkan tidak dapat dibayar', function () {
    $order = makePayableOrder();
    $order->update([
        'status' => Order::STATUS_VOIDED,
        'voided_at' => now(),
        'void_reason' => 'Dibatalkan untuk pengujian.',
    ]);

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), cashPayload())
        ->assertSessionHasErrors('order');

    expect(Payment::count())->toBe(0);
});

it('retry dengan idempotency key yang sama tidak membuat payment baru', function () {
    $order = makePayableOrder();
    $payload = cashPayload();
    $kasir = User::factory()->create();

    $this->actingAs($kasir)->post(route('orders.pay', $order), $payload)->assertRedirect(route('orders.receipt', $order));
    $firstPaymentId = Payment::firstOrFail()->id;

    $this->actingAs($kasir)->post(route('orders.pay', $order), $payload)->assertRedirect(route('orders.receipt', $order));

    expect(Payment::count())->toBe(1)
        ->and(Payment::firstOrFail()->id)->toBe($firstPaymentId);
});

it('idempotency key yang dipakai order lain ditolak', function () {
    $first = makePayableOrder();
    $second = makePayableOrder();
    $key = Str::uuid()->toString();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $first), cashPayload(['idempotency_key' => $key]))
        ->assertRedirect();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $second), cashPayload(['idempotency_key' => $key]))
        ->assertSessionHasErrors('idempotency_key');

    expect(Payment::count())->toBe(1)
        ->and($second->fresh()->status)->toBe(Order::STATUS_OPEN);
});

it('unique constraint mencegah dua payment untuk satu order dan rollback tetap bersih', function () {
    $order = makePayableOrder();
    $kasir = User::factory()->create();

    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 50_000,
        'processed_by' => $kasir->id,
    ]);

    $this->actingAs($kasir)
        ->post(route('orders.pay', $order), cashPayload())
        ->assertSessionHasErrors('order');

    expect(Payment::count())->toBe(1)
        ->and($order->fresh()->status)->toBe(Order::STATUS_OPEN)
        ->and($order->fresh()->paid_at)->toBeNull();
});

it('pembayaran menolak order tanpa item', function () {
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), cashPayload())
        ->assertSessionHasErrors('order');

    expect(Payment::count())->toBe(0)
        ->and($order->fresh()->status)->toBe(Order::STATUS_OPEN);
});

it('kasir dapat menandai tagihan awaiting payment dan transisi invalid ditolak', function () {
    $order = makePayableOrder();
    $kasir = User::factory()->create();

    $this->actingAs($kasir)
        ->post(route('orders.awaiting-payment', $order))
        ->assertRedirect(route('orders.show', $order));

    expect($order->fresh()->status)->toBe(Order::STATUS_AWAITING_PAYMENT)
        ->and(AuditLog::where('action', 'orders.awaiting_payment')->count())->toBe(1);

    $this->actingAs($kasir)->post(route('orders.awaiting-payment', $order))->assertRedirect();
    expect(AuditLog::where('action', 'orders.awaiting_payment')->count())->toBe(1);

    $this->actingAs($kasir)
        ->post(route('orders.pay', $order), cashPayload())
        ->assertRedirect(route('orders.receipt', $order));

    $this->actingAs($kasir)
        ->post(route('orders.awaiting-payment', $order))
        ->assertSessionHasErrors('order');
});

it('item pada order awaiting payment masih dapat ditambah sebelum dibayar', function () {
    $order = makePayableOrder();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 25_000]);
    $kasir = User::factory()->create();

    $this->actingAs($kasir)->post(route('orders.awaiting-payment', $order))->assertRedirect();

    $this->actingAs($kasir)
        ->post(route('orders.items.store', $order), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])
        ->assertRedirect(route('orders.show', $order));

    expect($order->fresh()->status)->toBe(Order::STATUS_AWAITING_PAYMENT)
        ->and($order->fresh()->total)->toBe(75_000);

    $this->actingAs($kasir)
        ->post(route('orders.pay', $order), cashPayload(['received_amount' => 75_000]))
        ->assertRedirect(route('orders.receipt', $order));

    expect(Payment::firstOrFail()->amount)->toBe(75_000);
});

it('halaman checkout memakai ringkasan nominal dari backend', function () {
    $order = makePayableOrder(30_000, 2);

    $this->actingAs(User::factory()->create())
        ->get(route('orders.checkout', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('orders/checkout')
            ->where('order.id', $order->id)
            ->where('order.total', 60_000)
            ->has('paymentMethods', 4));
});

it('halaman checkout paid order dialihkan ke struk', function () {
    $order = makePayableOrder();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), cashPayload())
        ->assertRedirect(route('orders.receipt', $order));

    $this->actingAs(User::factory()->create())
        ->get(route('orders.checkout', $order))
        ->assertRedirect(route('orders.receipt', $order));
});

it('tamu tidak dapat mengakses checkout atau pembayaran', function (string $method, string $url) {
    $this->call($method, $url)->assertRedirect(route('login'));
})->with([
    'checkout' => ['GET', '/orders/1/checkout'],
    'pay' => ['POST', '/orders/1/pay'],
    'awaiting payment' => ['POST', '/orders/1/awaiting-payment'],
    'receipt' => ['GET', '/orders/1/receipt'],
]);
