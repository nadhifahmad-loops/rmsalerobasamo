<?php

namespace App\Http\Controllers\Shifts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shifts\CloseShiftRequest;
use App\Http\Requests\Shifts\OpenShiftRequest;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ShiftController extends Controller
{
    public function __construct(private readonly ShiftService $shifts) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $canManage = Gate::forUser($user)->allows('manage-shifts');

        $shifts = Shift::query()
            ->with('cashier:id,name')
            ->withCount('orders')
            ->latest('opened_at')
            ->paginate((int) config('pos.shifts.page_size', 15));

        $activeShift = $this->shifts->activeFor((int) $user->id);
        $managedShifts = $canManage
            ? Shift::query()->open()->with('cashier:id,name')->latest('opened_at')->get()->map(fn (Shift $shift): array => [
                'id' => $shift->id,
                'cashier' => $shift->cashier?->name ?? '-',
                'opened_at' => $shift->opened_at?->toJSON(),
                'opening_cash' => (int) $shift->opening_cash,
                'expected_cash' => $this->shifts->expectedCash($shift),
            ])->all()
            : [];

        return Inertia::render('shifts/index', [
            'shifts' => $shifts->through(fn (Shift $shift): array => [
                'id' => $shift->id,
                'cashier' => $shift->cashier?->name ?? '-',
                'status' => $shift->status,
                'opened_at' => $shift->opened_at?->toJSON(),
                'closed_at' => $shift->closed_at?->toJSON(),
                'opening_cash' => (int) $shift->opening_cash,
                'expected_cash' => $shift->expected_cash === null ? null : (int) $shift->expected_cash,
                'actual_cash' => $shift->actual_cash === null ? null : (int) $shift->actual_cash,
                'difference' => $shift->difference === null ? null : (int) $shift->difference,
                'orders_count' => $shift->orders_count,
            ]),
            'activeShift' => $activeShift
                ? [
                    'id' => $activeShift->id,
                    'cashier_id' => $activeShift->cashier_id,
                    'opening_cash' => (int) $activeShift->opening_cash,
                    'opened_at' => $activeShift->opened_at?->toJSON(),
                    'expected_cash' => $this->shifts->expectedCash($activeShift),
                ]
                : null,
            'managedShifts' => $managedShifts,
            'canManage' => $canManage,
        ]);
    }

    public function store(OpenShiftRequest $request): RedirectResponse
    {
        $this->shifts->open((int) $request->user()->id, (int) $request->validated()['opening_cash']);

        return redirect()->route('shifts.index')->with('success', 'Shift dibuka. Modal awal tercatat.');
    }

    public function close(CloseShiftRequest $request, Shift $shift): RedirectResponse
    {
        $this->shifts->close($shift, (int) $request->validated()['actual_cash'], (int) $request->user()->id);

        return redirect()->route('shifts.index')->with('success', 'Shift ditutup dan selisih kas tercatat.');
    }
}
