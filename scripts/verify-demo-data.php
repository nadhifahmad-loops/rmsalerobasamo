<?php

use App\Models\AuditLog;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shift;
use Database\Seeders\DemoProductImageSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$demoNames = DemoProductImageSeeder::productNames();

$counts = [
    'categories' => DB::table('categories')->count(),
    'products' => Product::count(),
    'demo_product_images' => Product::whereIn('name', $demoNames)->where('image_path', 'like', 'products/demo/%')->count(),
    'variants' => ProductVariant::count(),
    'tables' => DiningTable::count(),
    'shifts' => Shift::count(),
    'orders' => Order::count(),
    'order_items' => OrderItem::count(),
    'payments' => Payment::count(),
    'audit_logs' => AuditLog::count(),
];
foreach ($counts as $name => $value) {
    echo $name.'='.$value.PHP_EOL;
}

$failures = [];
$paid = Order::where('status', Order::STATUS_PAID)->get();
if ($paid->count() !== Payment::count()) {
    $failures[] = 'Jumlah payment != order PAID.';
}
foreach ($paid as $order) {
    $payment = $order->payment;
    if ($payment === null || (int) $payment->amount !== (int) $order->total) {
        $failures[] = 'Nominal payment salah: '.$order->order_number;
    }
}
foreach (Order::all() as $order) {
    if ((int) $order->items()->sum('subtotal') !== (int) $order->total) {
        $failures[] = 'Total item salah: '.$order->order_number;
    }
}
$keys = Payment::whereNotNull('idempotency_key')->pluck('idempotency_key');
if ($keys->count() !== $keys->unique()->count()) {
    $failures[] = 'Idempotency key duplikat.';
}
$numbers = Order::pluck('order_number');
if ($numbers->count() !== $numbers->unique()->count()) {
    $failures[] = 'Nomor order duplikat.';
}
$openPerCashier = Shift::where('status', Shift::STATUS_OPEN)
    ->selectRaw('cashier_id, COUNT(*) as total')
    ->groupBy('cashier_id')
    ->pluck('total', 'cashier_id');
foreach ($openPerCashier as $cashierId => $total) {
    if ((int) $total > 1) {
        $failures[] = 'Lebih dari satu shift OPEN untuk kasir '.$cashierId;
    }
}
foreach (Shift::where('status', Shift::STATUS_CLOSED)->get() as $shift) {
    $cash = Payment::query()
        ->join('orders', 'orders.id', '=', 'payments.order_id')
        ->where('orders.shift_id', $shift->id)
        ->where('orders.status', Order::STATUS_PAID)
        ->where('payments.status', Payment::STATUS_PAID)
        ->where('payments.payment_method', Payment::METHOD_CASH)
        ->sum('payments.amount');
    $expected = (int) $shift->opening_cash + (int) $cash;
    if ((int) $shift->expected_cash !== $expected) {
        $failures[] = 'Expected cash salah: shift '.$shift->id;
    }
    if ((int) $shift->difference !== (int) $shift->actual_cash - $expected) {
        $failures[] = 'Selisih kas salah: shift '.$shift->id;
    }
}
foreach ($demoNames as $name) {
    $product = Product::where('name', $name)->first();

    if ($product === null) {
        $failures[] = 'Produk dummy tidak ditemukan: '.$name;

        continue;
    }

    if ($product->image_path === null) {
        $failures[] = 'Produk dummy belum punya foto: '.$name;

        continue;
    }

    if (! str_starts_with($product->image_path, 'products/demo/')) {
        // Foto custom milik user tidak boleh dianggap gagal oleh verifier dummy.
        continue;
    }

    if (! Storage::disk('public')->exists($product->image_path)) {
        $failures[] = 'File foto dummy tidak ada di storage: '.$product->image_path;
    }

    $sourcePath = public_path('images/products/demo/'.basename($product->image_path));
    if (! File::exists($sourcePath)) {
        $failures[] = 'Aset sumber foto dummy tidak ada: '.$sourcePath;
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        echo 'FAIL: '.$failure.PHP_EOL;
    }
    exit(1);
}
echo 'RESULT=OK'.PHP_EOL;
