<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonImmutable;

function createReportPaidOrder(
    CarbonImmutable $paidAt,
    int $amount,
    string $method,
    string $productName,
    string $variantName,
    int $quantity,
    int $unitPrice,
    array $overrides = [],
): Order {
    $order = Order::factory()->paid()->create(array_merge([
        'subtotal' => $quantity * $unitPrice,
        'total' => $amount,
    ], $overrides));
    $order->update(['paid_at' => $paidAt]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name_snapshot' => $productName,
        'variant_name_snapshot' => $variantName,
        'unit_snapshot' => 'porsi',
        'price_snapshot' => $unitPrice,
        'quantity' => $quantity,
        'subtotal' => $quantity * $unitPrice,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method' => $method,
        'amount' => $amount,
        'received_amount' => $method === Payment::METHOD_CASH ? $amount : null,
        'change_amount' => 0,
    ]);

    return $order->fresh();
}

it('laporan hanya dapat diakses admin/owner dan menolak kasir serta tamu', function () {
    $kasir = User::factory()->create();

    $this->actingAs($kasir)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($kasir)->get(route('reports.export'))->assertForbidden();

    $this->app['auth']->forgetGuards();

    $this->get(route('reports.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reports/index'));
});

it('omzet, transaksi, item, rata-rata, metode, dan menu terlaris hanya memakai order Paid dalam periode', function () {
    $admin = User::factory()->admin()->create();
    $inside = CarbonImmutable::create(2026, 11, 17, 12, 0, 0, 'Asia/Jakarta');

    createReportPaidOrder($inside, 40_000, Payment::METHOD_CASH, 'Ayam Bakar', 'Reguler', 2, 20_000);
    createReportPaidOrder($inside, 30_000, Payment::METHOD_QRIS, 'Sate Ayam', 'Reguler', 1, 30_000);
    createReportPaidOrder(CarbonImmutable::create(2026, 11, 16, 12, 0, 0, 'Asia/Jakarta'), 999_999, Payment::METHOD_CASH, 'Luar Periode', 'Reguler', 9, 10_000);

    $voided = createReportPaidOrder($inside, 88_888, Payment::METHOD_TRANSFER, 'Dibatalkan', 'Reguler', 5, 10_000);
    $voided->update(['status' => Order::STATUS_VOIDED, 'paid_at' => null]);

    $open = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $open->id,
        'product_name_snapshot' => 'Open Order',
        'quantity' => 50,
        'subtotal' => 500_000,
    ]);
    Order::factory()->awaitingPayment()->create();

    $this->actingAs($admin)
        ->get(route('reports.index', ['from' => '2026-11-17', 'to' => '2026-11-17']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/index')
            ->where('report.period.from', '2026-11-17')
            ->where('report.summary.revenue', 70_000)
            ->where('report.summary.transactions', 2)
            ->where('report.summary.items_sold', 3)
            ->where('report.summary.average_transaction', 35_000)
            ->where('report.methods', fn ($methods) => count($methods) === 2)
            ->where('report.methods.0.label', 'Tunai')
            ->where('report.methods.0.revenue', 40_000)
            ->where('report.methods.1.label', 'QRIS')
            ->where('report.methods.1.revenue', 30_000)
            ->where('report.top_items.0.product_name', 'Ayam Bakar')
            ->where('report.top_items.0.quantity', 2)
            ->where('report.top_items.1.product_name', 'Sate Ayam'));
});

it('laporan memakai batas hari Asia/Jakarta yang inklusif', function () {
    $admin = User::factory()->admin()->create();

    createReportPaidOrder(CarbonImmutable::create(2026, 11, 17, 0, 0, 0, 'Asia/Jakarta'), 10_000, Payment::METHOD_CASH, 'Awal', 'Reguler', 1, 10_000);
    createReportPaidOrder(CarbonImmutable::create(2026, 11, 17, 23, 59, 59, 'Asia/Jakarta'), 20_000, Payment::METHOD_CASH, 'Akhir', 'Reguler', 1, 20_000);
    createReportPaidOrder(CarbonImmutable::create(2026, 11, 16, 23, 59, 59, 'Asia/Jakarta'), 30_000, Payment::METHOD_CASH, 'Sebelum', 'Reguler', 1, 30_000);
    createReportPaidOrder(CarbonImmutable::create(2026, 11, 18, 0, 0, 0, 'Asia/Jakarta'), 40_000, Payment::METHOD_CASH, 'Sesudah', 'Reguler', 1, 40_000);

    $this->actingAs($admin)
        ->get(route('reports.index', ['from' => '2026-11-17', 'to' => '2026-11-17']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.summary.revenue', 30_000)
            ->where('report.summary.transactions', 2));
});

it('laporan tanpa transaksi mengembalikan nol tanpa pembagian nol', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.index', ['from' => '2026-01-01', 'to' => '2026-01-01']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.summary.revenue', 0)
            ->where('report.summary.transactions', 0)
            ->where('report.summary.items_sold', 0)
            ->where('report.summary.average_transaction', 0)
            ->where('report.methods', [])
            ->where('report.top_items', []));
});

it('rekap shift menampilkan kas diharapkan dari pembayaran tunai pada shift', function () {
    $admin = User::factory()->admin()->create();
    $kasir = User::factory()->create();
    $shift = Shift::factory()->create([
        'cashier_id' => $kasir->id,
        'opening_cash' => 100_000,
        'opened_at' => CarbonImmutable::create(2026, 11, 17, 8, 0, 0, 'Asia/Jakarta'),
    ]);

    createReportPaidOrder(
        CarbonImmutable::create(2026, 11, 17, 10, 0, 0, 'Asia/Jakarta'),
        50_000,
        Payment::METHOD_CASH,
        'Ayam Bakar',
        'Reguler',
        1,
        50_000,
        ['cashier_id' => $kasir->id, 'shift_id' => $shift->id],
    );

    $this->actingAs($admin)
        ->get(route('reports.index', ['from' => '2026-11-17', 'to' => '2026-11-17']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.shift_count', 1)
            ->where('report.shifts.0.cashier', $kasir->name)
            ->where('report.shifts.0.expected_cash', 150_000)
            ->where('report.shifts.0.actual_cash', null));
});

it('rekap beberapa shift memakai satu agregat kas tunai yang benar', function () {
    $admin = User::factory()->admin()->create();
    $kasirSatu = User::factory()->create();
    $kasirDua = User::factory()->create();

    $shiftSatu = Shift::factory()->create([
        'cashier_id' => $kasirSatu->id,
        'opening_cash' => 100_000,
        'opened_at' => CarbonImmutable::create(2026, 11, 17, 8, 0, 0, 'Asia/Jakarta'),
    ]);
    $shiftDua = Shift::factory()->create([
        'cashier_id' => $kasirDua->id,
        'opening_cash' => 200_000,
        'opened_at' => CarbonImmutable::create(2026, 11, 17, 9, 0, 0, 'Asia/Jakarta'),
    ]);

    createReportPaidOrder(
        CarbonImmutable::create(2026, 11, 17, 10, 0, 0, 'Asia/Jakarta'),
        50_000,
        Payment::METHOD_CASH,
        'Ayam Bakar',
        'Reguler',
        1,
        50_000,
        ['cashier_id' => $kasirSatu->id, 'shift_id' => $shiftSatu->id],
    );
    createReportPaidOrder(
        CarbonImmutable::create(2026, 11, 17, 11, 0, 0, 'Asia/Jakarta'),
        75_000,
        Payment::METHOD_CASH,
        'Sate Ayam',
        'Reguler',
        1,
        75_000,
        ['cashier_id' => $kasirDua->id, 'shift_id' => $shiftDua->id],
    );
    createReportPaidOrder(
        CarbonImmutable::create(2026, 11, 17, 12, 0, 0, 'Asia/Jakarta'),
        999_999,
        Payment::METHOD_QRIS,
        'Nasi Rendang',
        'Reguler',
        1,
        999_999,
        ['cashier_id' => $kasirDua->id, 'shift_id' => $shiftDua->id],
    );

    $this->actingAs($admin)
        ->get(route('reports.index', ['from' => '2026-11-17', 'to' => '2026-11-17']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.shift_count', 2)
            ->where('report.shifts.0.expected_cash', 275_000)
            ->where('report.shifts.1.expected_cash', 150_000));
});

it('export CSV memakai filter dan angka laporan yang sama', function () {
    $admin = User::factory()->admin()->create();
    $inside = CarbonImmutable::create(2026, 11, 17, 12, 0, 0, 'Asia/Jakarta');

    createReportPaidOrder($inside, 40_000, Payment::METHOD_CASH, 'Ayam Bakar', 'Reguler', 2, 20_000);
    createReportPaidOrder($inside, 30_000, Payment::METHOD_QRIS, 'Sate Ayam', 'Reguler', 1, 30_000);

    $response = $this->actingAs($admin)
        ->get(route('reports.export', ['from' => '2026-11-17', 'to' => '2026-11-17']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="laporan-penjualan-2026-11-17-2026-11-17.csv"');

    $content = $response->getContent();

    expect($content)->toContain('Laporan Penjualan')
        ->toContain('2026-11-17 s/d 2026-11-17')
        ->toContain('Tunai')
        ->toContain('70000')
        ->toContain('Ayam Bakar');
});

it('rentang tanggal laporan terbalik ditolak', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.index', ['from' => '2026-11-18', 'to' => '2026-11-17']))
        ->assertSessionHasErrors('to');
});
