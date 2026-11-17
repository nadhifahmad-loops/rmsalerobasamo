<?php

use App\Models\AuditLog;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\User;

it('admin melihat daftar meja dengan status yang diturunkan dari order', function () {
    $available = DiningTable::factory()->create(['table_number' => '1', 'label' => 'Meja Jendela']);
    $occupied = DiningTable::factory()->create(['table_number' => '2']);
    $awaiting = DiningTable::factory()->create(['table_number' => '3']);
    $released = DiningTable::factory()->create(['table_number' => '4']);

    Order::factory()->create(['dining_table_id' => $occupied->id, 'total' => 50_000]);
    Order::factory()->awaitingPayment()->create(['dining_table_id' => $awaiting->id]);
    Order::factory()->paid()->create(['dining_table_id' => $released->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('tables.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tables/index')
            ->where('tables.data.0.display_label', 'Meja Jendela')
            ->where('tables.data.0.status', DiningTable::STATUS_AVAILABLE)
            ->where('tables.data.1.status', DiningTable::STATUS_OCCUPIED)
            ->where('tables.data.1.running_total', 50_000)
            ->where('tables.data.2.status', DiningTable::STATUS_AWAITING_PAYMENT)
            ->where('tables.data.3.status', DiningTable::STATUS_AVAILABLE));
});

it('kasir dapat melihat meja tetapi tidak mengelolanya (authorization negatif)', function () {
    $kasir = User::factory()->create();
    $table = DiningTable::factory()->create();

    $this->actingAs($kasir)->get(route('tables.index'))->assertOk();
    $this->actingAs($kasir)->post(route('tables.store'), ['table_number' => '99'])->assertForbidden();
    $this->actingAs($kasir)->put(route('tables.update', $table), ['table_number' => '99'])->assertForbidden();
    $this->actingAs($kasir)->delete(route('tables.destroy', $table))->assertForbidden();
});

it('tamu tidak dapat melihat daftar meja', function () {
    $this->get(route('tables.index'))->assertRedirect(route('login'));
});

it('admin dapat membuat meja dan audit tercatat', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('tables.store'), [
            'table_number' => 'A1',
            'label' => 'Meja depan',
            'is_active' => true,
        ])
        ->assertRedirect(route('tables.index'));

    expect(DiningTable::where('table_number', 'A1')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'tables.created')->exists())->toBeTrue();
});

it('nomor meja duplikat ditolak', function () {
    DiningTable::factory()->create(['table_number' => 'A1']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('tables.store'), ['table_number' => 'A1'])
        ->assertSessionHasErrors('table_number');

    expect(DiningTable::where('table_number', 'A1')->count())->toBe(1);
});

it('admin dapat mengubah meja dengan nomor sendiri', function () {
    $table = DiningTable::factory()->create(['table_number' => 'A1', 'is_active' => true]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('tables.update', $table), [
            'table_number' => 'A1',
            'label' => 'Meja belakang',
            'is_active' => false,
        ])
        ->assertRedirect(route('tables.index'));

    expect($table->fresh()->label)->toBe('Meja belakang')
        ->and($table->fresh()->is_active)->toBeFalse();
});

it('meja dengan riwayat order tidak dapat dihapus', function () {
    $table = DiningTable::factory()->create();
    Order::factory()->paid()->create(['dining_table_id' => $table->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('tables.destroy', $table))
        ->assertSessionHasErrors('table');

    expect(DiningTable::find($table->id))->not->toBeNull();
});

it('meja tanpa riwayat dapat dihapus dan audit tercatat', function () {
    $table = DiningTable::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('tables.destroy', $table))
        ->assertRedirect(route('tables.index'));

    expect(DiningTable::find($table->id))->toBeNull()
        ->and(AuditLog::where('action', 'tables.deleted')->exists())->toBeTrue();
});

it('pencarian meja berdasarkan nomor atau label', function () {
    DiningTable::factory()->create(['table_number' => '1', 'label' => 'Depan']);
    DiningTable::factory()->create(['table_number' => '2', 'label' => 'Belakang']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('tables.index', ['search' => 'Belakang']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tables.total', 1)
            ->where('tables.data.0.table_number', '2'));
});
