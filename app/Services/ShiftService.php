<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aturan shift Phase 5 (BUSINESS-RULES §8): satu shift aktif per kasir,
 * rekonsiliasi kas tunai, dan audit penutupan shift.
 */
final class ShiftService
{
    public function activeFor(int $cashierId): ?Shift
    {
        return Shift::query()
            ->where('cashier_id', $cashierId)
            ->open()
            ->latest('opened_at')
            ->first();
    }

    public function open(int $cashierId, int $openingCash): Shift
    {
        if ($openingCash < 0) {
            throw ValidationException::withMessages([
                'opening_cash' => 'Modal awal tidak boleh negatif.',
            ]);
        }

        $shift = DB::transaction(function () use ($cashierId, $openingCash): Shift {
            // Serialisasi dengan pembukaan shift lain dan pembuatan order.
            User::query()->whereKey($cashierId)->lockForUpdate()->firstOrFail();

            if (Shift::query()->where('cashier_id', $cashierId)->open()->exists()) {
                throw ValidationException::withMessages([
                    'shift' => 'Kasir masih memiliki shift aktif. Tutup shift tersebut terlebih dahulu.',
                ]);
            }

            $shift = Shift::create([
                'cashier_id' => $cashierId,
                'opening_cash' => $openingCash,
                'status' => Shift::STATUS_OPEN,
                'opened_at' => now(),
            ]);

            AuditLog::record('shifts.opened', 'shift', $shift->id, [
                'cashier_id' => $cashierId,
                'opening_cash' => $openingCash,
            ]);

            return $shift;
        });

        return $shift->refresh()->load('cashier');
    }

    public function close(Shift $shift, int $actualCash, int $actorId): Shift
    {
        if ($actualCash < 0) {
            throw ValidationException::withMessages([
                'actual_cash' => 'Kas aktual tidak boleh negatif.',
            ]);
        }

        $closed = DB::transaction(function () use ($shift, $actualCash, $actorId): Shift {
            // Kunci user agar order baru tidak sempat memakai shift yang sedang ditutup.
            User::query()->whereKey($shift->cashier_id)->lockForUpdate()->firstOrFail();
            $locked = Shift::query()->whereKey($shift->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Shift::STATUS_CLOSED) {
                throw ValidationException::withMessages([
                    'shift' => 'Shift sudah ditutup dan tidak dapat dibuka ulang.',
                ]);
            }

            $expectedCash = $this->expectedCash($locked);
            $difference = $actualCash - $expectedCash;

            $locked->update([
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'status' => Shift::STATUS_CLOSED,
                'closed_at' => now(),
            ]);

            AuditLog::record('shifts.closed', 'shift', $locked->id, [
                'cashier_id' => $locked->cashier_id,
                'closed_by' => $actorId,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
            ]);

            return $locked;
        });

        return $closed->refresh()->load('cashier');
    }

    /**
     * Kas diharapkan = modal awal + pembayaran tunai bersih order pada shift ini.
     *
     * PaymentService menyimpan `amount` sebagai tagihan akhir dan
     * `received_amount - change_amount = amount`, sehingga `amount` sudah
     * merupakan uang tunai yang benar-benar masuk laci. Non-tunai tidak dihitung.
     */
    public function expectedCash(Shift $shift): int
    {
        $cashPayments = Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('orders.shift_id', $shift->id)
            ->where('orders.status', Order::STATUS_PAID)
            ->where('payments.status', Payment::STATUS_PAID)
            ->where('payments.payment_method', Payment::METHOD_CASH)
            ->sum('payments.amount');

        return (int) $shift->opening_cash + (int) $cashPayments;
    }
}
