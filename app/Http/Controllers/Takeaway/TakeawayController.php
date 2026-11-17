<?php

namespace App\Http\Controllers\Takeaway;

use App\Actions\Catalog\BrowsePosCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Takeaway\StoreTakeawayOrderRequest;
use App\Services\OrderService;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TakeawayController extends Controller
{
    public function __construct(
        private readonly BrowsePosCatalog $catalog,
        private readonly OrderService $orders,
        private readonly ShiftService $shifts,
    ) {}

    public function index(Request $request): Response
    {
        $browse = $this->catalog->handle(
            $request->string('search')->trim()->toString(),
            $request->integer('category_id') ?: null,
            (int) config('pos.takeaway.catalog_page_size', 24),
        );

        return Inertia::render('takeaway/index', [
            ...$browse,
            'activeShift' => $this->shifts->activeFor((int) $request->user()->id),
        ]);
    }

    public function store(StoreTakeawayOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $order = $this->orders->createTakeawayOrder(
            $data['items'],
            (int) $request->user()->id,
            $data['notes'] ?? null,
        );

        return redirect()->route('orders.checkout', $order)->with('success', 'Pesanan takeaway dibuat. Pilih metode pembayaran.');
    }
}
