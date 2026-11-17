<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ReceiptController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $order->loadMissing('items', 'cashier', 'diningTable', 'payment');

        $payment = $order->payment;

        abort_unless(
            $order->status === Order::STATUS_PAID
                && $payment !== null
                && $payment->status === Payment::STATUS_PAID,
            404,
        );

        $paperWidth = $request->integer('paper') === 58 ? 58 : 80;

        return view('receipts.show', [
            'businessName' => (string) config('app.name', 'Rumah Makan'),
            'order' => $order,
            'payment' => $payment,
            'cashierName' => $payment->processor?->name ?? $order->cashier?->name ?? '-',
            'tableLabel' => $order->table_label_snapshot ?: ($order->diningTable?->displayLabel() ?? '-'),
            'orderTypeLabel' => $order->order_type === Order::TYPE_DINE_IN ? 'Makan di tempat' : 'Bawa pulang',
            'paymentMethodLabel' => Payment::METHOD_LABELS[$payment->payment_method] ?? $payment->payment_method,
            'paidAt' => $payment->created_at?->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '-',
            'paperWidth' => $paperWidth,
            'successMessage' => $request->session()->get('success'),
        ]);
    }
}
