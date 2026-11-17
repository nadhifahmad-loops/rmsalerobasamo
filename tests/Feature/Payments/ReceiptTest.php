<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;

function receiptOrder(): Order
{
    $order = Order::factory()->create(['table_label_snapshot' => 'Meja 12']);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name_snapshot' => 'Ayam Bakar',
        'variant_name_snapshot' => '1 porsi',
        'unit_snapshot' => 'porsi',
        'price_snapshot' => 30_000,
        'quantity' => 2,
        'subtotal' => 60_000,
    ]);

    $order->update(['subtotal' => 60_000, 'total' => 60_000]);

    return $order->fresh();
}

it('struk menampilkan data tersimpan dari order dan payment', function () {
    $order = receiptOrder();
    $kasir = User::factory()->create(['name' => 'Kasir Satu']);

    $this->actingAs($kasir)
        ->post(route('orders.pay', $order), [
            'payment_method' => Payment::METHOD_CASH,
            'received_amount' => 75_000,
            'idempotency_key' => Str::uuid()->toString(),
        ])
        ->assertRedirect(route('orders.receipt', $order));

    $payment = Payment::firstOrFail();

    $this->actingAs($kasir)
        ->get(route('orders.receipt', $order))
        ->assertOk()
        ->assertSee($payment->payment_number)
        ->assertSee($order->order_number)
        ->assertSee('Kasir Satu')
        ->assertSee('Makan di tempat')
        ->assertSee('Meja 12')
        ->assertSee('Ayam Bakar')
        ->assertSee('1 porsi')
        ->assertSee('Rp 60.000')
        ->assertSee('Tunai')
        ->assertSee('Rp 75.000')
        ->assertSee('Rp 15.000')
        ->assertSee('Cetak struk');
});

it('cetak ulang struk tidak membuat payment baru', function () {
    $order = receiptOrder();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), [
            'payment_method' => Payment::METHOD_QRIS,
            'idempotency_key' => Str::uuid()->toString(),
        ])
        ->assertRedirect(route('orders.receipt', $order));

    $this->actingAs(User::factory()->create())->get(route('orders.receipt', $order))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('orders.receipt', $order))->assertOk();

    expect(Payment::count())->toBe(1);
});

it('struk order belum paid tidak tersedia', function () {
    $order = receiptOrder();

    $this->actingAs(User::factory()->create())
        ->get(route('orders.receipt', $order))
        ->assertNotFound();
});

it('struk menyediakan ukuran kertas 58 mm dan 80 mm', function () {
    $order = receiptOrder();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.pay', $order), [
            'payment_method' => Payment::METHOD_CASH,
            'received_amount' => 60_000,
            'idempotency_key' => Str::uuid()->toString(),
        ]);

    $this->actingAs(User::factory()->create())
        ->get(route('orders.receipt', ['order' => $order, 'paper' => 58]))
        ->assertOk()
        ->assertSee('data-paper="58"', false)
        ->assertSee('size: 58mm auto', false);

    $this->actingAs(User::factory()->create())
        ->get(route('orders.receipt', $order))
        ->assertOk()
        ->assertSee('data-paper="80"', false)
        ->assertSee('size: 80mm auto', false);
});
