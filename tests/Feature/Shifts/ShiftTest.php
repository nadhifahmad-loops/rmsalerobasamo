<?php

use App\Models\AuditLog;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\User;

function createPaidShiftPayment(Shift $shift, int $amount, string $method, ?int $receivedAmount = null, int $changeAmount = 0): Order
{
    $order = Order::factory()->paid()->create([
        'cashier_id' => $shift->cashier_id,
        'shift_id' => $shift->id,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method' => $method,
        'amount' => $amount,
        'received_amount' => $receivedAmount,
        'change_amount' => $changeAmount,
        'processed_by' => $shift->cashier_id,
    ]);

    return $order;
}

it('kasir dapat membuka shift dengan modal awal yang valid', function () {
    $kasir = User::factory()->create();

    $this->actingAs($kasir)
        ->post(route('shifts.store'), ['opening_cash' => 100_000])
        ->assertRedirect(route('shifts.index'));

    $shift = Shift::firstOrFail();

    expect($shift->cashier_id)->toBe($kasir->id)
        ->and($shift->opening_cash)->toBe(100_000)
        ->and($shift->status)->toBe(Shift::STATUS_OPEN)
        ->and($shift->opened_at)->not->toBeNull()
        ->and($shift->closed_at)->toBeNull()
        ->and(AuditLog::where('action', 'shifts.opened')->exists())->toBeTrue();
});

it('kasir tidak dapat membuka dua shift aktif dan modal negatif ditolak', function () {
    $kasir = User::factory()->create();
    Shift::factory()->create(['cashier_id' => $kasir->id]);

    $this->actingAs($kasir)
        ->post(route('shifts.store'), ['opening_cash' => 50_000])
        ->assertSessionHasErrors('shift');

    $this->actingAs($kasir)
        ->post(route('shifts.store'), ['opening_cash' => -1])
        ->assertSessionHasErrors('opening_cash');

    expect(Shift::count())->toBe(1);
});

it('shift aktif kasir dihubungkan ke order baru dan tidak dipakai setelah ditutup', function () {
    $kasir = User::factory()->create();
    $shift = Shift::factory()->create(['cashier_id' => $kasir->id]);
    $firstTable = DiningTable::factory()->create();
    $secondTable = DiningTable::factory()->create();

    $this->actingAs($kasir)
        ->post(route('orders.store'), ['dining_table_id' => $firstTable->id])
        ->assertRedirect();

    expect(Order::firstOrFail()->shift_id)->toBe($shift->id);

    $shift->update(['status' => Shift::STATUS_CLOSED, 'closed_at' => now()]);

    $this->actingAs($kasir)
        ->post(route('orders.store'), ['dining_table_id' => $secondTable->id])
        ->assertRedirect();

    expect(Order::latest('id')->firstOrFail()->shift_id)->toBeNull();
});

it('expected cash hanya menambah pembayaran tunai bersih dan penutupan mencatat selisih', function () {
    $kasir = User::factory()->create();
    $shift = Shift::factory()->create(['cashier_id' => $kasir->id, 'opening_cash' => 100_000]);

    createPaidShiftPayment($shift, 40_000, Payment::METHOD_CASH, 50_000, 10_000);
    createPaidShiftPayment($shift, 25_000, Payment::METHOD_QRIS);

    $this->actingAs($kasir)
        ->get(route('shifts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('shifts/index')
            ->where('activeShift.expected_cash', 140_000));

    $this->actingAs($kasir)
        ->put(route('shifts.close', $shift), ['actual_cash' => 137_000])
        ->assertRedirect(route('shifts.index'));

    $fresh = $shift->fresh();

    expect($fresh->status)->toBe(Shift::STATUS_CLOSED)
        ->and($fresh->expected_cash)->toBe(140_000)
        ->and($fresh->actual_cash)->toBe(137_000)
        ->and($fresh->difference)->toBe(-3_000)
        ->and($fresh->closed_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'shifts.closed')->exists())->toBeTrue();
});

it('shift yang sudah ditutup tidak dapat ditutup ulang', function () {
    $kasir = User::factory()->create();
    $shift = Shift::factory()->closed()->create(['cashier_id' => $kasir->id]);

    $this->actingAs($kasir)
        ->put(route('shifts.close', $shift), ['actual_cash' => 150_000])
        ->assertSessionHasErrors('shift');

    expect($shift->fresh()->status)->toBe(Shift::STATUS_CLOSED);
});

it('kasir tidak dapat menutup shift milik kasir lain tanpa permission', function () {
    $shift = Shift::factory()->create(['cashier_id' => User::factory()->create()->id]);
    $otherCashier = User::factory()->create();

    $this->actingAs($otherCashier)
        ->put(route('shifts.close', $shift), ['actual_cash' => 100_000])
        ->assertForbidden();

    expect($shift->fresh()->status)->toBe(Shift::STATUS_OPEN);
});

it('admin dengan permission manage-shifts dapat menutup shift kasir lain', function () {
    $kasir = User::factory()->create();
    $shift = Shift::factory()->create(['cashier_id' => $kasir->id]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('shifts.close', $shift), ['actual_cash' => 100_000])
        ->assertRedirect(route('shifts.index'));

    $audit = AuditLog::where('action', 'shifts.closed')->firstOrFail();

    expect($shift->fresh()->status)->toBe(Shift::STATUS_CLOSED)
        ->and($audit->user_id)->toBe($admin->id)
        ->and($audit->metadata['closed_by'])->toBe($admin->id);
});

it('tamu tidak dapat mengakses halaman atau endpoint shift', function () {
    $shift = Shift::factory()->create();

    $this->get(route('shifts.index'))->assertRedirect(route('login'));
    $this->post(route('shifts.store'), ['opening_cash' => 100_000])->assertRedirect(route('login'));
    $this->put(route('shifts.close', $shift), ['actual_cash' => 100_000])->assertRedirect(route('login'));
});
