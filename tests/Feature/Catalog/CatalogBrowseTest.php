<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

it('kasir dapat membuka katalog read-only', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('catalog/browse'));
});

it('hanya produk dan variasi yang dapat dipesan yang dikirim ke kasir', function () {
    $category = Category::factory()->create(['name' => 'Makanan']);

    $valid = Product::factory()->create(['category_id' => $category->id, 'name' => 'Rendang']);
    ProductVariant::factory()->create([
        'product_id' => $valid->id,
        'name' => '1 potong',
        'price' => 25000,
        'is_available' => true,
    ]);
    ProductVariant::factory()->unavailable()->create([
        'product_id' => $valid->id,
        'name' => 'Paket Habis',
        'price' => 75000,
        'is_default' => false,
    ]);

    $inactive = Product::factory()->inactive()->create(['category_id' => $category->id, 'name' => 'Produk Nonaktif']);
    ProductVariant::factory()->create(['product_id' => $inactive->id]);

    $soldOut = Product::factory()->unavailable()->create(['category_id' => $category->id, 'name' => 'Produk Habis']);
    ProductVariant::factory()->create(['product_id' => $soldOut->id]);

    $withoutVariant = Product::factory()->create(['category_id' => $category->id, 'name' => 'Tanpa Variasi']);

    $onlyInactiveVariant = Product::factory()->create(['category_id' => $category->id, 'name' => 'Variasi Nonaktif']);
    ProductVariant::factory()->inactive()->create(['product_id' => $onlyInactiveVariant->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('catalog/browse')
            ->where('products.total', 1)
            ->where('products.data.0.name', 'Rendang')
            ->where('products.data.0.variants.0.name', '1 potong')
            ->where('products.data.0.variants.0.price', 25000)
            ->missing('products.data.0.variants.1'));
});

it('pencarian katalog bekerja berdasarkan nama produk', function () {
    $category = Category::factory()->create();

    $rendang = Product::factory()->create(['category_id' => $category->id, 'name' => 'Rendang']);
    ProductVariant::factory()->create(['product_id' => $rendang->id]);
    $ayam = Product::factory()->create(['category_id' => $category->id, 'name' => 'Ayam Bakar']);
    ProductVariant::factory()->create(['product_id' => $ayam->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('catalog.index', ['search' => 'Ayam']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('products.total', 1)
            ->where('products.data.0.name', 'Ayam Bakar')
            ->where('filters.search', 'Ayam'));
});

it('filter kategori katalog bekerja', function () {
    $food = Category::factory()->create(['name' => 'Makanan']);
    $drink = Category::factory()->create(['name' => 'Minuman']);

    $rendang = Product::factory()->create(['category_id' => $food->id]);
    ProductVariant::factory()->create(['product_id' => $rendang->id]);
    $tea = Product::factory()->create(['category_id' => $drink->id]);
    ProductVariant::factory()->create(['product_id' => $tea->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('catalog.index', ['category_id' => $drink->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('products.total', 1)
            ->where('filters.category_id', $drink->id)
            ->where('products.data.0.category.name', 'Minuman'));
});

it('kategori nonaktif tidak ditampilkan sebagai filter', function () {
    Category::factory()->create(['name' => 'Aktif']);
    Category::factory()->inactive()->create(['name' => 'Nonaktif']);

    $this->actingAs(User::factory()->create())
        ->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('categories', fn ($categories) => $categories->count() === 1 && $categories->first()['name'] === 'Aktif'));
});

it('tamu dialihkan dari katalog', function () {
    $this->get(route('catalog.index'))->assertRedirect('/login');
});
