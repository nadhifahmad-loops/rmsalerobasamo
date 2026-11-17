<?php

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

function variantPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Reguler',
        'price' => 25000,
        'is_default' => true,
        'is_active' => true,
        'is_available' => true,
    ], $overrides);
}

it('admin dapat menambah variasi pada produk', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.variants.store', $product), variantPayload([
            'name' => 'Jumbo',
            'price' => 45000,
            'is_default' => false,
        ]))
        ->assertRedirect(route('products.edit', $product));

    expect($product->variants()->where('name', 'Jumbo')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'catalog.variant_created')->exists())->toBeTrue();
});

it('harga variasi negatif dan di atas batas ditolak', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.variants.store', $product), variantPayload(['price' => -5000]))
        ->assertSessionHasErrors('price');

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.variants.store', $product), variantPayload(['price' => 5_000_000_000]))
        ->assertSessionHasErrors('price');

    expect($product->variants()->count())->toBe(1);
});

it('perubahan harga variasi diaudit dengan nilai lama dan baru', function () {
    $variant = ProductVariant::factory()->create(['price' => 20000]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('products.variants.update', [$variant->product_id, $variant->id]), variantPayload(['price' => 30000]))
        ->assertRedirect(route('products.edit', $variant->product_id));

    expect($variant->fresh()->price)->toBe(30000);

    $log = AuditLog::where('action', 'catalog.variant_updated')->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->metadata['before']['price'])->toBe(20000)
        ->and($log->metadata['after']['price'])->toBe(30000);
});

it('variasi aktif terakhir tidak boleh dinonaktifkan', function () {
    $variant = ProductVariant::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('products.variants.update', [$variant->product_id, $variant->id]), variantPayload(['is_active' => false]))
        ->assertSessionHasErrors('variants');

    expect($variant->fresh()->is_active)->toBeTrue();
});

it('variasi aktif terakhir tidak boleh dihapus', function () {
    $variant = ProductVariant::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('products.variants.destroy', [$variant->product_id, $variant->id]))
        ->assertSessionHasErrors('variants');

    expect(ProductVariant::withTrashed()->find($variant->id)->trashed())->toBeFalse();
});

it('default otomatis berpindah saat variasi default dihapus', function () {
    $product = Product::factory()->create();
    $default = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => 'Kecil',
        'is_default' => true,
    ]);
    $other = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => 'Besar',
        'is_default' => false,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('products.variants.destroy', [$product->id, $default->id]))
        ->assertRedirect(route('products.edit', $product));

    expect($other->fresh()->is_default)->toBeTrue()
        ->and(ProductVariant::find($default->id))->toBeNull();
});

it('menghapus variasi non-default tidak mengubah default', function () {
    $product = Product::factory()->create();
    $default = ProductVariant::factory()->create(['product_id' => $product->id, 'is_default' => true]);
    $other = ProductVariant::factory()->create(['product_id' => $product->id, 'is_default' => false]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('products.variants.destroy', [$product->id, $other->id]))
        ->assertRedirect(route('products.edit', $product));

    expect($default->fresh()->is_default)->toBeTrue();
});

it('variasi dari produk lain ditolak', function () {
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();
    $variantB = ProductVariant::factory()->create(['product_id' => $productB->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('products.variants.update', [$productA->id, $variantB->id]), variantPayload())
        ->assertNotFound();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('products.variants.destroy', [$productA->id, $variantB->id]))
        ->assertNotFound();
});

it('kasir tidak dapat mengelola variasi (authorization negatif)', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->actingAs(User::factory()->create())
        ->post(route('products.variants.store', $product), variantPayload())
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->put(route('products.variants.update', [$product->id, $variant->id]), variantPayload())
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->delete(route('products.variants.destroy', [$product->id, $variant->id]))
        ->assertForbidden();
});
