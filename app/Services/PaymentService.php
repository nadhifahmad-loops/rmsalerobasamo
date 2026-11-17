<?php

namespace App\Services;

use App\Actions\Orders\RecalculateOrderTotals;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Alur pembayaran Phase 4: lock order, hitung ulang nominal, catat payment,
 * dan tandai Paid dalam satu transaction (BUSINESS-RULES §6).
 */
final class PaymentService
{
    public function __construct(private readonly RecalculateOrderTotals $recalculateTotals) {}

    public function markAwaitingPayment(Order $order): Order
    {
        $updated = DB::transaction(function () use ($order): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Order::STATUS_AWAITING_PAYMENT) {
                return $locked->refresh();
            }

            if ($locked->status !== Order::STATUS_OPEN) {
                throw ValidationException::withMessages([
                    'order' => 'Status pesanan tidak memungkinkan perubahan ini.',
                ]);
            }

            $locked->update(['status' => Order::STATUS_AWAITING_PAYMENT]);

            AuditLog::record('orders.awaiting_payment', 'order', $locked->id, [
                'order_number' => $locked->order_number,
            ]);

            return $locked->refresh();
        });

        return $updated->load('diningTable', 'cashier', 'items');
    }

    public function pay(
        Order $order,
        string $method,
        ?int $receivedAmount,
        string $idempotencyKey,
        int $processorId,
    ): Payment {
        $payment = $this->runInTransaction(function () use ($order, $method, $receivedAmount, $idempotencyKey, $processorId): Payment {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Idempotency diperiksa setelah lock order agar retry tidak melihat
            // status lama dan tetap mengembalikan payment yang sama.
            $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                if ($existing->order_id !== $lockedOrder->id) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => 'Kunci permintaan sudah dipakai untuk pesanan lain.',
                    ]);
                }

                return $existing->load('order', 'processor');
            }

            if (! in_array($method, Payment::METHODS, true)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Metode pembayaran tidak dikenal.',
                ]);
            }

            if (! in_array($lockedOrder->status, Order::EDITABLE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan ini sudah dibayar atau dibatalkan dan tidak dapat dibayar lagi.',
                ]);
            }

            $lockedOrder = $this->recalculateTotals->handle($lockedOrder);

            if ($lockedOrder->items()->count() === 0) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan belum memiliki item untuk dibayar.',
                ]);
            }

            $amount = (int) $lockedOrder->total;
            $received = null;
            $change = 0;

            if ($method === Payment::METHOD_CASH) {
                if ($receivedAmount === null) {
                    throw ValidationException::withMessages([
                        'received_amount' => 'Nominal uang diterima wajib diisi untuk pembayaran tunai.',
                    ]);
                }

                $maxReceived = (int) config('pos.payments.max_received_amount', 1_000_000_000_000);

                if ($receivedAmount > $maxReceived) {
                    throw ValidationException::withMessages([
                        'received_amount' => 'Nominal uang diterima melebihi batas yang diizinkan.',
                    ]);
                }

                if ($receivedAmount < $amount) {
                    throw ValidationException::withMessages([
                        'received_amount' => 'Uang diterima kurang dari total tagihan.',
                    ]);
                }

                $received = $receivedAmount;
                $change = $receivedAmount - $amount;
            }

            $payment = Payment::create([
                'order_id' => $lockedOrder->id,
                'payment_number' => $this->generatePaymentNumber(),
                'idempotency_key' => $idempotencyKey,
                'payment_method' => $method,
                'amount' => $amount,
                'received_amount' => $received,
                'change_amount' => $change,
                'status' => Payment::STATUS_PAID,
                'processed_by' => $processorId,
            ]);

            $lockedOrder->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now('Asia/Jakarta'),
            ]);

            AuditLog::record('payments.created', 'payment', $payment->id, [
                'payment_number' => $payment->payment_number,
                'order_id' => $lockedOrder->id,
                'order_number' => $lockedOrder->order_number,
                'payment_method' => $payment->payment_method,
                'amount' => $payment->amount,
            ]);

            AuditLog::record('orders.paid', 'order', $lockedOrder->id, [
                'order_number' => $lockedOrder->order_number,
                'payment_number' => $payment->payment_number,
                'paid_at' => $lockedOrder->paid_at?->toIso8601String(),
            ]);

            return $payment->load('order', 'processor');
        });

        return $payment;
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $operation
     * @return TReturn
     */
    private function runInTransaction(Closure $operation): mixed
    {
        try {
            return DB::transaction($operation);
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception, 'idempotency_key')) {
                throw ValidationException::withMessages([
                    'idempotency_key' => 'Kunci permintaan sudah dipakai. Ulangi dari halaman checkout.',
                ]);
            }

            if ($this->isUniqueViolation($exception, 'order_id')) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan ini sudah memiliki pembayaran.',
                ]);
            }

            throw $exception;
        }
    }

    private function isUniqueViolation(QueryException $exception, string $column): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'payments.'.$column)
            || str_contains($message, 'payments_'.$column.'_unique')
            || str_contains($message, $column.'_unique');
    }

    private function generatePaymentNumber(): string
    {
        $prefix = 'PAY-'.now('Asia/Jakarta')->format('Ymd').'-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $number = $prefix.Str::upper(Str::random(6));

            if (! Payment::query()->where('payment_number', $number)->exists()) {
                return $number;
            }
        }

        throw new \RuntimeException('Gagal membuat nomor pembayaran yang unik.');
    }
}
