<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\AddOrderItemRequest;
use App\Http\Requests\Orders\UpdateOrderItemRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;

final class OrderItemController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function store(AddOrderItemRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();

        $this->orders->addItem(
            $order,
            (int) $data['product_id'],
            (int) $data['product_variant_id'],
            (int) $data['quantity'],
            $data['notes'] ?? null,
        );

        return redirect()->route('orders.show', $order);
    }

    public function update(UpdateOrderItemRequest $request, Order $order, OrderItem $item): RedirectResponse
    {
        $data = $request->validated();

        $this->orders->updateItem(
            $order,
            $item,
            (int) $data['quantity'],
            $data['notes'] ?? null,
        );

        return redirect()->route('orders.show', $order);
    }

    public function destroy(Order $order, OrderItem $item): RedirectResponse
    {
        $this->orders->removeItem($order, $item);

        return redirect()->route('orders.show', $order);
    }
}
