<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;

function createHistoryPaidOrder(
    CarbonImmutable $paidAt,
    int $total = 25_000,
    string $method = Payment::METHOD_CASH,
    array $overrides = [],
): Order {
    $factory = ($overrides['order_type'] ?? null) === Order::TYPE_TAKEAWAY
        ? Order::factory()->takeaway()->paid()
        : Order::factory()->paid();

    $order = $factory->create(array_merge([
        'subtotal' => $total,
        'total' => $total,
    ], $overrides));
    $order->update(['paid_at' => $paidAt]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method' => $method,
        'amount' => $total,
        'received_amount' => $method === Payment::METHOD_CASH ? $total : null,
        'change_amount' => 0,
    ]);

    return $order->fresh();
}

it('riwayat menampilkan Paid dan Voided tetapi tidak order aktif', function () {
    $kasir = User::factory()->create();
    $active = Order::factory()->create(['cashier_id' => $kasir->id]);
    $paid = createHistoryPaidOrder(CarbonImmutable::now('Asia/Jakarta'), 25_000, Payment::METHOD_CASH, ['cashier_id' => $kasir->id]);
    $voided = Order::factory()->voided()->create(['cashier_id' => $kasir->id]);

    $this->actingAs($kasir)
        ->get(route('orders.index', ['view' => 'history']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('orders/index')
            ->where('view', 'history')
            ->where('orders.total', 2)
            ->where('orders.data', fn ($rows) => count($rows) === 2)
            ->where('orders.data', fn ($rows) => collect($rows)->pluck('id')->doesntContain($active->id))
            ->where('orders.data', fn ($rows) => collect($rows)->pluck('id')->contains($paid->id))
            ->where('orders.data', fn ($rows) => collect($rows)->pluck('id')->contains($voided->id)));
});

it('riwayat dapat dicari berdasarkan nomor order dan nomor pembayaran', function () {
    $kasir = User::factory()->create();
    $paid = createHistoryPaidOrder(CarbonImmutable::now('Asia/Jakarta'), 30_000, Payment::METHOD_CASH, ['cashier_id' => $kasir->id]);
    createHistoryPaidOrder(CarbonImmutable::now('Asia/Jakarta'), 20_000, Payment::METHOD_QRIS, ['cashier_id' => $kasir->id]);

    $this->actingAs($kasir)
        ->get(route('orders.index', ['view' => 'history', 'search' => $paid->order_number]))
        ->assertInertia(fn ($page) => $page->where('orders.total', 1)->where('orders.data.0.id', $paid->id));

    $this->actingAs($kasir)
        ->get(route('orders.index', ['view' => 'history', 'search' => $paid->payment->payment_number]))
        ->assertInertia(fn ($page) => $page->where('orders.total', 1)->where('orders.data.0.id', $paid->id));
});

it('filter tanggal memakai batas tengah malam Asia/Jakarta secara inklusif', function () {
    $kasir = User::factory()->create();
    $start = createHistoryPaidOrder(CarbonImmutable::create(2026, 11, 17, 0, 0, 0, 'Asia/Jakarta'), 10_000, Payment::METHOD_CASH, [
        'cashier_id' => $kasir->id,
    ]);
    $end = createHistoryPaidOrder(CarbonImmutable::create(2026, 11, 17, 23, 59, 59, 'Asia/Jakarta'), 20_000, Payment::METHOD_CASH, [
        'cashier_id' => $kasir->id,
    ]);
    $before = createHistoryPaidOrder(CarbonImmutable::create(2026, 11, 16, 23, 59, 59, 'Asia/Jakarta'), 30_000, Payment::METHOD_CASH, [
        'cashier_id' => $kasir->id,
    ]);
    $after = createHistoryPaidOrder(CarbonImmutable::create(2026, 11, 18, 0, 0, 0, 'Asia/Jakarta'), 40_000, Payment::METHOD_CASH, [
        'cashier_id' => $kasir->id,
    ]);

    $this->actingAs($kasir)
        ->get(route('orders.index', ['view' => 'history', 'from' => '2026-11-17', 'to' => '2026-11-17']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('orders.total', 2)
            ->where('orders.data', fn ($rows) => collect($rows)->pluck('id')->contains($start->id))
            ->where('orders.data', fn ($rows) => collect($rows)->pluck('id')->contains($end->id))
            ->where('orders.data', fn ($rows) => collect($rows)->pluck('id')->doesntContain($before->id))
            ->where('orders.data', fn ($rows) => collect($rows)->pluck('id')->doesntContain($after->id)));
});

it('filter kasir, tipe order, metode bayar, dan status bekerja bersama', function () {
    $kasirA = User::factory()->create();
    $kasirB = User::factory()->create();
    $target = createHistoryPaidOrder(CarbonImmutable::now('Asia/Jakarta'), 50_000, Payment::METHOD_QRIS, [
        'cashier_id' => $kasirA->id,
        'order_type' => Order::TYPE_TAKEAWAY,
    ]);
    createHistoryPaidOrder(CarbonImmutable::now('Asia/Jakarta'), 40_000, Payment::METHOD_CASH, [
        'cashier_id' => $kasirA->id,
        'order_type' => Order::TYPE_DINE_IN,
    ]);
    createHistoryPaidOrder(CarbonImmutable::now('Asia/Jakarta'), 30_000, Payment::METHOD_QRIS, [
        'cashier_id' => $kasirB->id,
        'order_type' => Order::TYPE_TAKEAWAY,
    ]);

    $this->actingAs($kasirA)
        ->get(route('orders.index', [
            'view' => 'history',
            'cashier_id' => $kasirA->id,
            'order_type' => Order::TYPE_TAKEAWAY,
            'payment_method' => Payment::METHOD_QRIS,
            'status' => Order::STATUS_PAID,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('orders.total', 1)
            ->where('orders.data.0.id', $target->id));
});

it('riwayat memiliki pagination', function () {
    $kasir = User::factory()->create();

    for ($index = 0; $index < 6; $index++) {
        createHistoryPaidOrder(CarbonImmutable::now('Asia/Jakarta'), 10_000 + $index, Payment::METHOD_CASH, ['cashier_id' => $kasir->id]);
    }

    $this->actingAs($kasir)
        ->get(route('orders.index', ['view' => 'history', 'per_page' => 5]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('orders.total', 6)
            ->where('orders.per_page', 5)
            ->where('orders.last_page', 2)
            ->where('orders.data', fn ($rows) => count($rows) === 5));
});

it('rentang tanggal terbalik ditolak dan tamu tidak dapat melihat riwayat', function () {
    $kasir = User::factory()->create();

    $this->actingAs($kasir)
        ->get(route('orders.index', ['view' => 'history', 'from' => '2026-11-18', 'to' => '2026-11-17']))
        ->assertSessionHasErrors('to');

    $this->app['auth']->forgetGuards();

    $this->get(route('orders.index', ['view' => 'history']))->assertRedirect(route('login'));
});
