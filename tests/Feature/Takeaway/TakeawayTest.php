<?php

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Str;

function takeawayMenu(int $price = 25_000): array
{
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => 'Reguler',
        'price' => $price,
    ]);

    return [$product, $variant];
}

function takeawayPayload(array $items, array $overrides = []): array
{
    return array_merge([
        'notes' => 'Dimasak saat pelanggan tiba',
        'items' => $items,
    ], $overrides);
}

it('halaman takeaway dapat dibuka kasir dan menolak tamu', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('takeaway.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('takeaway/index')
            ->where('products.total', 0)
            ->where('activeShift', null));

    $this->app['auth']->forgetGuards();

    $this->get(route('takeaway.index'))->assertRedirect(route('login'));
});

it('kasir membuat order takeaway tanpa meja lalu diarahkan ke checkout', function () {
    [$product, $variant] = takeawayMenu(20_000);
    $kasir = User::factory()->create();

    $this->actingAs($kasir)
        ->post(route('takeaway.store'), takeawayPayload([
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'notes' => 'Tanpa bawang',
            ],
        ]))
        ->assertRedirect(route('orders.checkout', Order::firstOrFail()));

    $order = Order::firstOrFail();
    $item = OrderItem::firstOrFail();

    expect($order->order_type)->toBe(Order::TYPE_TAKEAWAY)
        ->and($order->dining_table_id)->toBeNull()
        ->and($order->table_label_snapshot)->toBeNull()
        ->and($order->cashier_id)->toBe($kasir->id)
        ->and($order->status)->toBe(Order::STATUS_OPEN)
        ->and($order->shift_id)->toBeNull()
        ->and($order->notes)->toBe('Dimasak saat pelanggan tiba')
        ->and($order->subtotal)->toBe(40_000)
        ->and($order->total)->toBe(40_000)
        ->and($item->product_name_snapshot)->toBe($product->name)
        ->and($item->price_snapshot)->toBe(20_000)
        ->and($item->quantity)->toBe(2)
        ->and($item->notes)->toBe('Tanpa bawang')
        ->and(AuditLog::where('action', 'orders.created')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'orders.item_added')->exists())->toBeTrue();
});

it('order takeaway dihubungkan ke shift aktif kasir', function () {
    [$product, $variant] = takeawayMenu();
    $kasir = User::factory()->create();
    $shift = Shift::factory()->create(['cashier_id' => $kasir->id]);

    $this->actingAs($kasir)
        ->post(route('takeaway.store'), takeawayPayload([
            ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 1],
        ]))
        ->assertRedirect();

    expect(Order::firstOrFail()->shift_id)->toBe($shift->id);
});

it('item invalid menyebabkan seluruh order takeaway di-rollback', function () {
    [$product, $variant] = takeawayMenu();

    $this->actingAs(User::factory()->create())
        ->post(route('takeaway.store'), takeawayPayload([
            ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 1],
            ['product_id' => 999_999, 'product_variant_id' => $variant->id, 'quantity' => 1],
        ]))
        ->assertSessionHasErrors('items.1.product_id');

    expect(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0)
        ->and(AuditLog::where('action', 'orders.created')->exists())->toBeFalse();
});

it('keranjang kosong dan kuantitas di luar batas ditolak', function () {
    $kasir = User::factory()->create();

    $this->actingAs($kasir)
        ->post(route('takeaway.store'), takeawayPayload([]))
        ->assertSessionHasErrors('items');

    [$product, $variant] = takeawayMenu();

    $this->actingAs($kasir)
        ->post(route('takeaway.store'), takeawayPayload([
            ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 0],
        ]))
        ->assertSessionHasErrors('items.0.quantity');

    expect(Order::count())->toBe(0);
});

it('order takeaway dapat dibayar dan struknya tetap dapat dicetak ulang', function () {
    [$product, $variant] = takeawayMenu(30_000);
    $kasir = User::factory()->create();

    $this->actingAs($kasir)
        ->post(route('takeaway.store'), takeawayPayload([
            ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 1],
        ]))
        ->assertRedirect();

    $order = Order::firstOrFail();

    $this->actingAs($kasir)
        ->post(route('orders.pay', $order), [
            'payment_method' => Payment::METHOD_CASH,
            'received_amount' => 30_000,
            'idempotency_key' => Str::uuid()->toString(),
        ])
        ->assertRedirect(route('orders.receipt', $order));

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID);

    $this->actingAs($kasir)
        ->get(route('orders.receipt', $order))
        ->assertOk()
        ->assertSee('Bawa pulang');
});
