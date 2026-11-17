<?php

use App\Models\AuditLog;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

it('kasir dapat membuka Open Order pada meja tersedia', function () {
    $table = DiningTable::factory()->create(['table_number' => '7', 'label' => 'Meja Tujuh']);
    $kasir = User::factory()->create();

    $response = $this->actingAs($kasir)
        ->post(route('orders.store'), [
            'dining_table_id' => $table->id,
            'notes' => 'Pelanggan menunggu es teh.',
        ]);

    $order = Order::firstOrFail();

    $response->assertRedirect(route('orders.show', $order));

    expect($order->order_type)->toBe(Order::TYPE_DINE_IN)
        ->and($order->status)->toBe(Order::STATUS_OPEN)
        ->and($order->dining_table_id)->toBe($table->id)
        ->and($order->table_label_snapshot)->toBe('Meja Tujuh')
        ->and($order->cashier_id)->toBe($kasir->id)
        ->and($order->notes)->toBe('Pelanggan menunggu es teh.')
        ->and($order->total)->toBe(0)
        ->and($order->order_number)->toStartWith('ORD-')
        ->and(AuditLog::where('action', 'orders.created')->exists())->toBeTrue();
});

it('meja yang sudah punya order aktif tidak dapat dipakai kasir lain', function () {
    $table = DiningTable::factory()->create();
    Order::factory()->create(['dining_table_id' => $table->id]);

    $this->actingAs(User::factory()->create())
        ->post(route('orders.store'), ['dining_table_id' => $table->id])
        ->assertSessionHasErrors('dining_table_id');

    expect(Order::where('dining_table_id', $table->id)->count())->toBe(1);
});

it('meja yang menunggu pembayaran juga dianggap aktif', function () {
    $table = DiningTable::factory()->create();
    Order::factory()->awaitingPayment()->create(['dining_table_id' => $table->id]);

    $this->actingAs(User::factory()->create())
        ->post(route('orders.store'), ['dining_table_id' => $table->id])
        ->assertSessionHasErrors('dining_table_id');
});

it('meja yang sudah dibayarkan atau dibatalkan dapat dipakai untuk order baru', function () {
    $table = DiningTable::factory()->create();
    Order::factory()->paid()->create(['dining_table_id' => $table->id]);
    Order::factory()->voided()->create(['dining_table_id' => $table->id]);

    $this->actingAs(User::factory()->create())
        ->post(route('orders.store'), ['dining_table_id' => $table->id])
        ->assertRedirect();

    expect(Order::where('dining_table_id', $table->id)->active()->count())->toBe(1);
});

it('meja nonaktif tidak dapat dipakai untuk order baru', function () {
    $table = DiningTable::factory()->inactive()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('orders.store'), ['dining_table_id' => $table->id])
        ->assertSessionHasErrors('dining_table_id');

    expect(Order::count())->toBe(0);
});

it('order aktif tetap ada setelah refresh dan dapat dibuka kasir lain', function () {
    $kasir = User::factory()->create();
    $order = Order::factory()->create(['cashier_id' => $kasir->id, 'notes' => 'Catatan awal']);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price_snapshot' => 25_000,
        'quantity' => 2,
        'subtotal' => 50_000,
    ]);
    $order->update(['subtotal' => 50_000, 'total' => 50_000]);

    $this->actingAs(User::factory()->create())
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('orders/show')
            ->where('order.id', $order->id)
            ->where('order.notes', 'Catatan awal')
            ->where('order.items.0.id', $item->id)
            ->where('order.total', 50_000)
            ->where('canEdit', true));
});

it('daftar order aktif tersedia dan tidak memuat order paid', function () {
    $active = Order::factory()->create();
    $paid = Order::factory()->paid()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('orders/index')
            ->where('orders.total', 1)
            ->where('orders.data.0.id', $active->id)
            ->missing('orders.data.1'));

    expect($paid->status)->toBe(Order::STATUS_PAID);
});

it('catatan order dapat diperbarui pada order terbuka', function () {
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('orders.notes.update', $order), ['notes' => 'Tambah sambal'])
        ->assertRedirect(route('orders.show', $order));

    expect($order->fresh()->notes)->toBe('Tambah sambal')
        ->and(AuditLog::where('action', 'orders.notes_updated')->exists())->toBeTrue();
});

it('catatan order paid tidak dapat diubah', function () {
    $order = Order::factory()->paid()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('orders.notes.update', $order), ['notes' => 'Diubah'])
        ->assertSessionHasErrors('order');

    expect($order->fresh()->notes)->toBeNull();
});

it('tamu tidak dapat mengakses halaman atau endpoint order', function () {
    $order = Order::factory()->create();
    $table = DiningTable::factory()->create();

    $this->get(route('orders.index'))->assertRedirect(route('login'));
    $this->get(route('orders.show', $order))->assertRedirect(route('login'));
    $this->post(route('orders.store'), ['dining_table_id' => $table->id])->assertRedirect(route('login'));
    $this->put(route('orders.notes.update', $order), ['notes' => 'x'])->assertRedirect(route('login'));
});
