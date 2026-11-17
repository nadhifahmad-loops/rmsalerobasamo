<?php

use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\ProductVariantController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Orders\OrderItemController;
use App\Http\Controllers\Payments\CheckoutController;
use App\Http\Controllers\Payments\ReceiptController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Shifts\ShiftController;
use App\Http\Controllers\Tables\TableController;
use App\Http\Controllers\Takeaway\TakeawayController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', fn () => inertia('dashboard'))->name('dashboard');

    // Katalog read-only: dapat dilihat kasir, admin, dan owner.
    Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');

    // Mutasi katalog hanya untuk role dengan permission manage-catalog.
    // Authorization diperiksa ulang oleh FormRequest pada setiap mutasi.
    Route::middleware('can:manage-catalog')->group(function () {
        Route::resource('categories', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names('categories');

        Route::resource('products', ProductController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names('products');

        Route::resource('products.variants', ProductVariantController::class)
            ->only(['store', 'update', 'destroy'])
            ->names('products.variants');
    });

    // Daftar meja dapat dibaca semua pengguna; status dihitung dari order aktif.
    Route::get('tables', [TableController::class, 'index'])->name('tables.index');

    Route::middleware('can:manage-tables')->group(function () {
        Route::post('tables', [TableController::class, 'store'])->name('tables.store');
        Route::put('tables/{table}', [TableController::class, 'update'])->name('tables.update');
        Route::delete('tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');
    });

    // POS dan Open Order dapat dipakai kasir, admin, dan owner.
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('orders/{order}/notes', [OrderController::class, 'updateNotes'])->name('orders.notes.update');
    Route::post('orders/{order}/awaiting-payment', [OrderController::class, 'markAwaitingPayment'])->name('orders.awaiting-payment');

    Route::post('orders/{order}/items', [OrderItemController::class, 'store'])->name('orders.items.store');
    Route::put('orders/{order}/items/{item}', [OrderItemController::class, 'update'])->name('orders.items.update');
    Route::delete('orders/{order}/items/{item}', [OrderItemController::class, 'destroy'])->name('orders.items.destroy');

    // Checkout, pembayaran, dan struk. Payment hanya dibuat oleh endpoint POST
    // orders.pay; GET struk hanya membaca data tersimpan.
    Route::get('orders/{order}/checkout', [CheckoutController::class, 'show'])->name('orders.checkout');
    Route::post('orders/{order}/pay', [CheckoutController::class, 'store'])->name('orders.pay');
    Route::get('orders/{order}/receipt', [ReceiptController::class, 'show'])->name('orders.receipt');

    // Takeaway tidak memakai meja dan langsung menuju checkout.
    Route::get('takeaway', [TakeawayController::class, 'index'])->name('takeaway.index');
    Route::post('takeaway', [TakeawayController::class, 'store'])->name('takeaway.store');

    // Shift dapat dibuka kasir; menutup shift orang lain butuh manage-shifts.
    Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::put('shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');

    // Laporan hanya untuk admin/owner; authorization juga dicek ReportRequest.
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index')
        ->middleware('can:view-reports');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export')
        ->middleware('can:view-reports');
});
