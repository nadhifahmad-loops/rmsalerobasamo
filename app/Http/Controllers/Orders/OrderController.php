<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Catalog\BrowsePosCatalog;
use App\Actions\Reports\ResolveDateRange;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\OrderHistoryRequest;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderNotesRequest;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
        private readonly BrowsePosCatalog $catalog,
        private readonly ResolveDateRange $dates,
        private readonly ShiftService $shifts,
    ) {}

    public function index(OrderHistoryRequest $request): Response
    {
        $input = $request->validated();
        $view = $input['view'] ?? 'active';
        $search = $input['search'] ?? '';
        $from = $input['from'] ?? null;
        $to = $input['to'] ?? null;
        $range = ($from !== null || $to !== null) ? $this->dates->handle($from, $to) : null;

        $query = Order::query()
            ->with([
                'diningTable:id,table_number,label',
                'cashier:id,name',
                'payment:id,order_id,payment_number,payment_method,amount,received_amount,change_amount,created_at',
            ])
            ->withCount('items');

        if ($view === 'active') {
            $query->active();
        } else {
            $query->whereIn('status', [Order::STATUS_PAID, Order::STATUS_VOIDED]);
        }

        if ($search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($term): void {
                $query->where('order_number', 'like', $term)
                    ->orWhereHas('payment', fn ($paymentQuery) => $paymentQuery->where('payment_number', 'like', $term));
            });
        }

        if ($range !== null) {
            if ($view === 'active') {
                $query->whereBetween('created_at', [$range['from'], $range['to']]);
            } else {
                // Paid memakai paid_at; Voided tidak punya paid_at sehingga
                // memakai created_at agar tetap dapat ditemukan dalam riwayat.
                $query->where(function ($query) use ($range): void {
                    $query->whereBetween('paid_at', [$range['from'], $range['to']])
                        ->orWhere(function ($query) use ($range): void {
                            $query->whereNull('paid_at')
                                ->whereBetween('created_at', [$range['from'], $range['to']]);
                        });
                });
            }
        }

        foreach ([
            'cashier_id' => 'cashier_id',
            'payment_method' => null,
            'order_type' => 'order_type',
            'status' => 'status',
        ] as $inputKey => $column) {
            $value = $input[$inputKey] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if ($column === null) {
                $query->whereHas('payment', fn ($paymentQuery) => $paymentQuery->where('payment_method', $value));
            } else {
                $query->where($column, $value);
            }
        }

        $orders = $query
            ->latest('created_at')
            ->paginate((int) ($input['per_page'] ?? config('pos.orders.page_size', 15)))
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'table_label_snapshot' => $order->table_label_snapshot,
                'status' => $order->status,
                'total' => $order->total,
                'notes' => $order->notes,
                'created_at' => $order->created_at?->toJSON(),
                'paid_at' => $order->paid_at?->toJSON(),
                'items_count' => $order->items_count,
                'cashier' => $order->cashier ? ['id' => $order->cashier->id, 'name' => $order->cashier->name] : null,
                'dining_table' => $order->diningTable
                    ? ['id' => $order->diningTable->id, 'table_number' => $order->diningTable->table_number, 'label' => $order->diningTable->label]
                    : null,
                'payment_number' => $order->payment?->payment_number,
                'payment_method' => $order->payment?->payment_method,
            ]);

        return Inertia::render('orders/index', [
            'orders' => $orders,
            'view' => $view,
            'filters' => [
                'view' => $view,
                'search' => $search,
                'from' => $range['from_label'] ?? '',
                'to' => $range['to_label'] ?? '',
                'cashier_id' => $input['cashier_id'] ?? null,
                'payment_method' => $input['payment_method'] ?? '',
                'order_type' => $input['order_type'] ?? '',
                'status' => $input['status'] ?? '',
            ],
            'options' => [
                'cashiers' => User::query()->whereHas('orders')->orderBy('name')->get(['id', 'name']),
                'statuses' => [
                    ['value' => Order::STATUS_OPEN, 'label' => 'Open'],
                    ['value' => Order::STATUS_AWAITING_PAYMENT, 'label' => 'Menunggu bayar'],
                    ['value' => Order::STATUS_PAID, 'label' => 'Paid'],
                    ['value' => Order::STATUS_VOIDED, 'label' => 'Void'],
                ],
                'types' => [
                    ['value' => Order::TYPE_DINE_IN, 'label' => 'Dine-in'],
                    ['value' => Order::TYPE_TAKEAWAY, 'label' => 'Takeaway'],
                ],
                'methods' => collect(Payment::METHODS)->map(fn (string $method): array => [
                    'value' => $method,
                    'label' => Payment::METHOD_LABELS[$method],
                ])->all(),
            ],
            'activeShift' => $this->shifts->activeFor((int) $request->user()->id),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $table = DiningTable::query()->findOrFail($data['dining_table_id']);

        $order = $this->orders->createDineInOrder(
            $table,
            (int) $request->user()->id,
            $data['notes'] ?? null,
        );

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan dibuka pada meja '.$order->table_label_snapshot.'.');
    }

    public function show(Request $request, Order $order): Response
    {
        $browse = $this->catalog->handle(
            $request->string('search')->trim()->toString(),
            $request->integer('category_id') ?: null,
            (int) config('pos.orders.catalog_page_size', 24),
        );

        $order->load('diningTable', 'cashier', 'items', 'payment.processor', 'shift');

        $productIds = $order->items->pluck('product_id')->filter()->unique()->all();
        $productImages = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'image_path'])
            ->mapWithKeys(fn (Product $product): array => [$product->id => $product->image_url])
            ->all();

        return Inertia::render('orders/show', [
            'order' => $order,
            ...$browse,
            'productImages' => $productImages,
            'canEdit' => in_array($order->status, Order::EDITABLE_STATUSES, true),
        ]);
    }

    public function updateNotes(UpdateOrderNotesRequest $request, Order $order): RedirectResponse
    {
        $this->orders->updateNotes($order, $request->validated()['notes'] ?? null);

        return redirect()->route('orders.show', $order)->with('success', 'Catatan pesanan disimpan.');
    }

    public function markAwaitingPayment(Order $order): RedirectResponse
    {
        $this->payments->markAwaitingPayment($order);

        return redirect()->route('orders.show', $order)->with('success', 'Tagihan siap dibayar.');
    }
}
