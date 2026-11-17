<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\PayOrderRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class CheckoutController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function show(Order $order): Response|RedirectResponse
    {
        if ($order->status === Order::STATUS_PAID) {
            return redirect()->route('orders.receipt', $order);
        }

        if ($order->status === Order::STATUS_VOIDED) {
            return redirect()->route('orders.show', $order)->with('error', 'Pesanan dibatalkan dan tidak dapat dibayar.');
        }

        return Inertia::render('orders/checkout', [
            'order' => $order->load('diningTable', 'cashier', 'items'),
            'paymentMethods' => collect(Payment::METHODS)->map(fn (string $method): array => [
                'value' => $method,
                'label' => Payment::METHOD_LABELS[$method],
            ])->all(),
            'maxReceivedAmount' => (int) config('pos.payments.max_received_amount', 1_000_000_000_000),
        ]);
    }

    public function store(PayOrderRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();

        $payment = $this->payments->pay(
            $order,
            (string) $data['payment_method'],
            isset($data['received_amount']) ? (int) $data['received_amount'] : null,
            (string) $data['idempotency_key'],
            (int) $request->user()->id,
        );

        return redirect()->route('orders.receipt', $order)
            ->with('success', 'Pembayaran '.$payment->payment_number.' berhasil dicatat.');
    }
}
