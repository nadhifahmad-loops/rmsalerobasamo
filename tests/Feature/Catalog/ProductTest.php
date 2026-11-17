<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

function productPayload(int $categoryId, ?array $variants = null): array
{
    return [
        'category_id' => $categoryId,
        'name' => 'Rendang',
        'description' => 'Rendang daging sapi',
        'unit' => 'potong',
        'is_active' => true,
        'is_available' => true,
        'variants' => $variants ?? [['name' => '1 potong', 'price' => 25000, 'is_default' => true, 'is_active' => true, 'is_available' => true]],
    ];
}

it('admin dapat melihat daftar produk beserta filter', function () {
    $product = Product::factory()->create(['name' => 'Ayam Bakar']);
    ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('products.index', ['search' => 'Ayam']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('catalog/products')
            ->where('products.total', 1)
            ->where('products.data.0.name', 'Ayam Bakar')
            ->where('filters.search', 'Ayam'));
});

it('admin dapat membuat produk dengan satu variasi default', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), productPayload($category->id))
        ->assertRedirect(route('products.index'));

    $product = Product::where('name', 'Rendang')->firstOrFail();

    expect($product->variants()->count())->toBe(1)
        ->and($product->variants()->first()->price)->toBe(25000)
        ->and($product->variants()->first()->is_default)->toBeTrue()
        ->and(AuditLog::where('action', 'catalog.product_created')->exists())->toBeTrue();
});

it('admin dapat membuat beberapa variasi dengan harga berbeda dan tepat satu default', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), productPayload($category->id, [
            ['name' => '1 potong', 'price' => 25000, 'is_default' => false, 'is_active' => true, 'is_available' => true],
            ['name' => '2 potong', 'price' => 48000, 'is_default' => false, 'is_active' => true, 'is_available' => true],
            ['name' => 'Paket', 'price' => 75000, 'is_default' => false, 'is_active' => true, 'is_available' => true],
        ]))
        ->assertRedirect(route('products.index'));

    $product = Product::where('name', 'Rendang')->firstOrFail();

    expect($product->variants()->count())->toBe(3)
        ->and($product->variants()->where('is_default', true)->count())->toBe(1)
        ->and($product->variants()->where('is_default', true)->first()->name)->toBe('1 potong');
});

it('menolak harga negatif dan tidak menyimpan produk', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), productPayload($category->id, [
            ['name' => 'Reguler', 'price' => -1, 'is_active' => true, 'is_available' => true],
        ]))
        ->assertSessionHasErrors('variants.0.price');

    expect(Product::where('name', 'Rendang')->exists())->toBeFalse();
});

it('menolak harga di atas batas nominal', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), productPayload($category->id, [
            ['name' => 'Reguler', 'price' => 2_000_000_000, 'is_active' => true, 'is_available' => true],
        ]))
        ->assertSessionHasErrors('variants.0.price');
});

it('produk wajib memiliki minimal satu variasi', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), productPayload($category->id, []))
        ->assertSessionHasErrors('variants');
});

it('produk wajib memiliki kategori aktif', function () {
    $category = Category::factory()->inactive()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), productPayload($category->id))
        ->assertSessionHasErrors('category_id');
});

it('admin dapat memperbarui produk dan perubahan tercatat', function () {
    $product = Product::factory()->create(['name' => 'Nama Lama']);
    ProductVariant::factory()->create(['product_id' => $product->id]);

    $payload = productPayload($product->category_id);
    unset($payload['variants']);
    $payload['name'] = 'Nama Baru';

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('products.update', $product), $payload)
        ->assertRedirect(route('products.edit', $product));

    expect($product->fresh()->name)->toBe('Nama Baru');

    $log = AuditLog::where('action', 'catalog.product_updated')->latest('id')->first();
    expect($log->metadata['changed_fields'])->toContain('name');
});

it('produk dihapus lunak sehingga histori tidak hilang', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    expect(Product::find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id)->trashed())->toBeTrue()
        ->and(AuditLog::where('action', 'catalog.product_deleted')->exists())->toBeTrue();
});

it('kasir tidak dapat mengelola produk maupun harga (authorization negatif)', function () {
    $kasir = User::factory()->create();
    $product = Product::factory()->create();
    $category = $product->category_id;

    $this->actingAs($kasir)->get(route('products.index'))->assertForbidden();
    $this->actingAs($kasir)->get(route('products.create'))->assertForbidden();
    $this->actingAs($kasir)->post(route('products.store'), productPayload($category))->assertForbidden();
    $this->actingAs($kasir)->put(route('products.update', $product), productPayload($category))->assertForbidden();
    $this->actingAs($kasir)->delete(route('products.destroy', $product))->assertForbidden();
});

it('tamu tidak dapat mengakses produk', function () {
    $this->get(route('products.index'))->assertRedirect('/login');
    $this->post(route('products.store'), [])->assertRedirect('/login');
});
