<?php

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

function activeVariant(array $attributes = []): array
{
    $product = Product::factory()->create($attributes['product_attributes'] ?? []);
    $variant = ProductVariant::factory()->create(array_merge([
        'product_id' => $product->id,
        'price' => 25_000,
    ], $attributes['variant_attributes'] ?? []));

    return [$product, $variant];
}

it('harga dan identitas item diambil dari database, bukan dari browser', function () {
    $order = Order::factory()->create();
    [$product, $variant] = activeVariant([
        'product_attributes' => ['name' => 'Rendang Sapi', 'unit' => 'potong'],
        'variant_attributes' => ['name' => '1 potong', 'price' => 25_000],
    ]);

    $this->actingAs(User::factory()->create())
        ->post(route('orders.items.store', $order), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'notes' => 'Tanpa cabai',
            'price_snapshot' => 1,
            'subtotal' => 1,
            'total' => 1,
        ])
        ->assertRedirect(route('orders.show', $order));

    $item = $order->items()->firstOrFail();

    expect($item->product_name_snapshot)->toBe('Rendang Sapi')
        ->and($item->variant_name_snapshot)->toBe('1 potong')
        ->and($item->unit_snapshot)->toBe('potong')
        ->and($item->price_snapshot)->toBe(25_000)
        ->and($item->quantity)->toBe(2)
        ->and($item->subtotal)->toBe(50_000)
        ->and($item->notes)->toBe('Tanpa cabai')
        ->and($order->fresh()->subtotal)->toBe(50_000)
        ->and($order->fresh()->total)->toBe(50_000)
        ->and(AuditLog::where('action', 'orders.item_added')->exists())->toBeTrue();
});

it('item kedua menambah subtotal order', function () {
    $order = Order::factory()->create();
    [$productA, $variantA] = activeVariant(['variant_attributes' => ['price' => 20_000]]);
    [$productB, $variantB] = activeVariant(['variant_attributes' => ['price' => 30_000]]);
    $kasir = User::factory()->create();

    $this->actingAs($kasir)->post(route('orders.items.store', $order), [
        'product_id' => $productA->id, 'product_variant_id' => $variantA->id, 'quantity' => 1,
    ]);
    $this->actingAs($kasir)->post(route('orders.items.store', $order), [
        'product_id' => $productB->id, 'product_variant_id' => $variantB->id, 'quantity' => 2,
    ]);

    expect($order->fresh()->items()->count())->toBe(2)
        ->and($order->fresh()->subtotal)->toBe(80_000)
        ->and($order->fresh()->total)->toBe(80_000);
});

it('variasi dari produk lain ditolak', function () {
    $order = Order::factory()->create();
    [$product] = activeVariant();
    [, $otherVariant] = activeVariant();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.items.store', $order), [
            'product_id' => $product->id,
            'product_variant_id' => $otherVariant->id,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('product_variant_id');

    expect($order->items()->count())->toBe(0);
});

it('produk nonaktif atau habis tidak dapat ditambahkan', function () {
    $order = Order::factory()->create();
    [, $inactiveVariant] = activeVariant([
        'product_attributes' => ['is_active' => false],
    ]);
    [, $soldOutVariant] = activeVariant([
        'variant_attributes' => ['is_available' => false],
    ]);

    $this->actingAs(User::factory()->create())
        ->post(route('orders.items.store', $order), [
            'product_id' => $inactiveVariant->product_id,
            'product_variant_id' => $inactiveVariant->id,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('product_id');

    $this->actingAs(User::factory()->create())
        ->post(route('orders.items.store', $order), [
            'product_id' => $soldOutVariant->product_id,
            'product_variant_id' => $soldOutVariant->id,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('product_variant_id');
});

it('quantity harus positif dan tidak melebihi batas', function () {
    $order = Order::factory()->create();
    [$product, $variant] = activeVariant();
    $kasir = User::factory()->create();

    foreach ([0, -1, 101, 1.5] as $quantity) {
        $this->actingAs($kasir)
            ->post(route('orders.items.store', $order), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
            ])
            ->assertSessionHasErrors('quantity');
    }

    expect($order->items()->count())->toBe(0);
});

it('update quantity dan catatan tidak mengubah snapshot harga meski master berubah', function () {
    $order = Order::factory()->create();
    [$product, $variant] = activeVariant(['variant_attributes' => ['price' => 25_000]]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'price_snapshot' => 25_000,
        'quantity' => 1,
        'subtotal' => 25_000,
    ]);
    $order->update(['subtotal' => 25_000, 'total' => 25_000]);

    $variant->update(['price' => 99_000]);

    $this->actingAs(User::factory()->create())
        ->put(route('orders.items.update', [$order, $item]), [
            'quantity' => 3,
            'notes' => 'Ekstra hangat',
        ])
        ->assertRedirect(route('orders.show', $order));

    expect($item->fresh()->price_snapshot)->toBe(25_000)
        ->and($item->fresh()->quantity)->toBe(3)
        ->and($item->fresh()->subtotal)->toBe(75_000)
        ->and($item->fresh()->notes)->toBe('Ekstra hangat')
        ->and($order->fresh()->total)->toBe(75_000)
        ->and(AuditLog::where('action', 'orders.item_updated')->exists())->toBeTrue();
});

it('hapus item menghitung ulang total dan audit tercatat', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price_snapshot' => 40_000,
        'quantity' => 2,
        'subtotal' => 80_000,
    ]);
    $order->update(['subtotal' => 80_000, 'total' => 80_000]);

    $this->actingAs(User::factory()->create())
        ->delete(route('orders.items.destroy', [$order, $item]))
        ->assertRedirect(route('orders.show', $order));

    expect(OrderItem::find($item->id))->toBeNull()
        ->and($order->fresh()->subtotal)->toBe(0)
        ->and($order->fresh()->total)->toBe(0)
        ->and(AuditLog::where('action', 'orders.item_removed')->exists())->toBeTrue();
});

it('item pada order paid tidak dapat diubah atau dihapus', function () {
    $order = Order::factory()->paid()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id]);

    $this->actingAs(User::factory()->create())
        ->put(route('orders.items.update', [$order, $item]), ['quantity' => 2])
        ->assertSessionHasErrors('order');

    $this->actingAs(User::factory()->create())
        ->delete(route('orders.items.destroy', [$order, $item]))
        ->assertSessionHasErrors('order');

    expect($item->fresh()->quantity)->toBe(1)
        ->and(OrderItem::find($item->id))->not->toBeNull();
});

it('item tidak dapat diakses melalui order lain', function () {
    $order = Order::factory()->create();
    $otherOrder = Order::factory()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id]);

    $this->actingAs(User::factory()->create())
        ->put(route('orders.items.update', [$otherOrder, $item]), ['quantity' => 2])
        ->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->delete(route('orders.items.destroy', [$otherOrder, $item]))
        ->assertNotFound();
});

it('tamu tidak dapat menambah, mengubah, atau menghapus item', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id]);
    [$product, $variant] = activeVariant();

    $this->post(route('orders.items.store', $order), [
        'product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 1,
    ])->assertRedirect(route('login'));
    $this->put(route('orders.items.update', [$order, $item]), ['quantity' => 2])->assertRedirect(route('login'));
    $this->delete(route('orders.items.destroy', [$order, $item]))->assertRedirect(route('login'));
});
