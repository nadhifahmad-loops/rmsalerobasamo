<?php

namespace App\Http\Controllers\Tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\StoreDiningTableRequest;
use App\Http\Requests\Tables\UpdateDiningTableRequest;
use App\Models\AuditLog;
use App\Models\DiningTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TableController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $tables = DiningTable::query()
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('table_number', 'like', $term)
                        ->orWhere('label', 'like', $term);
                });
            })
            ->with('activeOrder:id,dining_table_id,status,total,order_number')
            ->orderBy('table_number')
            ->paginate((int) config('pos.tables.page_size', 60))
            ->withQueryString()
            ->through(fn (DiningTable $table): array => [
                'id' => $table->id,
                'table_number' => $table->table_number,
                'label' => $table->label,
                'display_label' => $table->displayLabel(),
                'is_active' => $table->is_active,
                'status' => $table->derivedStatus(),
                'active_order_id' => $table->activeOrder?->id,
                'active_order_number' => $table->activeOrder?->order_number,
                'running_total' => $table->activeOrder?->total ?? 0,
            ]);

        return Inertia::render('tables/index', [
            'tables' => $tables,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(StoreDiningTableRequest $request): RedirectResponse
    {
        $table = DiningTable::create($request->validated());

        AuditLog::record('tables.created', 'dining_table', $table->id, [
            'table_number' => $table->table_number,
            'is_active' => $table->is_active,
        ]);

        return redirect()->route('tables.index')->with('success', 'Meja berhasil ditambahkan.');
    }

    public function update(UpdateDiningTableRequest $request, DiningTable $table): RedirectResponse
    {
        $before = [
            'table_number' => $table->table_number,
            'label' => $table->label,
            'is_active' => $table->is_active,
        ];

        $table->update($request->validated());

        AuditLog::record('tables.updated', 'dining_table', $table->id, [
            'before' => $before,
            'after' => [
                'table_number' => $table->table_number,
                'label' => $table->label,
                'is_active' => $table->is_active,
            ],
        ]);

        return redirect()->route('tables.index')->with('success', 'Meja berhasil diperbarui.');
    }

    public function destroy(DiningTable $table): RedirectResponse
    {
        if ($table->orders()->exists()) {
            return redirect()->route('tables.index')->withErrors([
                'table' => 'Meja memiliki riwayat pesanan dan tidak dapat dihapus.',
            ]);
        }

        $tableNumber = $table->table_number;
        $table->delete();

        AuditLog::record('tables.deleted', 'dining_table', $table->id, [
            'table_number' => $tableNumber,
        ]);

        return redirect()->route('tables.index')->with('success', 'Meja berhasil dihapus.');
    }
}
