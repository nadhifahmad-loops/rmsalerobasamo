<?php

use App\Actions\Catalog\ResolveOrderItemSnapshot;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

it('snapshot diambil dari data master di database', function () {
    $product = Product::factory()->create(['name' => 'Rendang', 'unit' => 'potong']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => '2 potong',
        'price' => 48000,
    ]);

    $snapshot = app(ResolveOrderItemSnapshot::class)->handle($product->id, $variant->id);

    expect($snapshot)->toBe([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name_snapshot' => 'Rendang',
        'variant_name_snapshot' => '2 potong',
        'unit_snapshot' => 'potong',
        'price_snapshot' => 48000,
    ]);
});

it('snapshot lama tidak berubah saat harga master diperbarui', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 25000]);
    $resolver = app(ResolveOrderItemSnapshot::class);

    $snapshotLama = $resolver->handle($product->id, $variant->id);

    $product->update(['name' => 'Nama Baru']);
    $variant->update(['price' => 40000, 'name' => 'Nama Variasi Baru']);

    $snapshotBaru = $resolver->handle($product->id, $variant->id);

    expect($snapshotLama['price_snapshot'])->toBe(25000)
        ->and($snapshotLama['product_name_snapshot'])->not->toBe('Nama Baru')
        ->and($snapshotLama['variant_name_snapshot'])->not->toBe('Nama Variasi Baru')
        ->and($snapshotBaru['price_snapshot'])->toBe(40000);
});

it('menolak produk yang tidak aktif atau habis', function () {
    $product = Product::factory()->inactive()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    expect(fn () => app(ResolveOrderItemSnapshot::class)->handle($product->id, $variant->id))
        ->toThrow(ValidationException::class);

    $product->update(['is_active' => true, 'is_available' => false]);

    expect(fn () => app(ResolveOrderItemSnapshot::class)->handle($product->id, $variant->id))
        ->toThrow(ValidationException::class);
});

it('menolak variasi yang tidak aktif, habis, atau berasal dari produk lain', function () {
    $product = Product::factory()->create();
    $inactive = ProductVariant::factory()->inactive()->create(['product_id' => $product->id]);
    $unavailable = ProductVariant::factory()->unavailable()->create(['product_id' => $product->id, 'is_default' => false]);
    $otherProduct = Product::factory()->create();
    $otherVariant = ProductVariant::factory()->create(['product_id' => $otherProduct->id]);
    $resolver = app(ResolveOrderItemSnapshot::class);

    expect(fn () => $resolver->handle($product->id, $inactive->id))
        ->toThrow(ValidationException::class);

    expect(fn () => $resolver->handle($product->id, $unavailable->id))
        ->toThrow(ValidationException::class);

    expect(fn () => $resolver->handle($product->id, $otherVariant->id))
        ->toThrow(ValidationException::class);
});
