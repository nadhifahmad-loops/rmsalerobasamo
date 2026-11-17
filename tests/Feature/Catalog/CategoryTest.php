<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

it('admin dapat melihat halaman kelola kategori', function () {
    $category = Category::factory()->create(['name' => 'Minuman']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('catalog/categories')
            ->where('categories.data.0.name', 'Minuman'));
});

it('admin dapat membuat kategori dan audit tercatat', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('categories.store'), [
            'name' => 'Makanan Berat',
            'sort_order' => 2,
            'is_active' => true,
        ])
        ->assertRedirect(route('categories.index'));

    expect(Category::where('name', 'Makanan Berat')->exists())->toBeTrue();

    $log = AuditLog::where('action', 'catalog.category_created')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->entity_type)->toBe('category')
        ->and($log->metadata['name'])->toBe('Makanan Berat');
});

it('menolak nama kategori duplikat', function () {
    Category::factory()->create(['name' => 'Minuman']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('categories.store'), ['name' => 'Minuman'])
        ->assertSessionHasErrors('name');

    expect(Category::where('name', 'Minuman')->count())->toBe(1);
});

it('admin dapat memperbarui kategori dengan nama sendiri', function () {
    $category = Category::factory()->create(['name' => 'Lama']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('categories.update', $category), [
            'name' => 'Lama',
            'sort_order' => 9,
            'is_active' => false,
        ])
        ->assertRedirect(route('categories.index'));

    expect($category->fresh()->sort_order)->toBe(9)
        ->and($category->fresh()->is_active)->toBeFalse();
});

it('menolak sort order negatif', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('categories.store'), [
            'name' => 'Kategori Valid',
            'sort_order' => -1,
        ])
        ->assertSessionHasErrors('sort_order');
});

it('kategori yang masih dipakai produk tidak dapat dihapus', function () {
    $product = Product::factory()->create();
    $category = $product->category;

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('categories.destroy', $category))
        ->assertSessionHasErrors('category');

    expect(Category::find($category->id))->not->toBeNull();
});

it('kategori kosong dapat dihapus dan audit tercatat', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    expect(Category::find($category->id))->toBeNull()
        ->and(AuditLog::where('action', 'catalog.category_deleted')->exists())->toBeTrue();
});

it('kasir tidak dapat mengelola kategori (authorization negatif)', function () {
    $kasir = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($kasir)->get(route('categories.index'))->assertForbidden();
    $this->actingAs($kasir)->post(route('categories.store'), ['name' => 'Ilegal'])->assertForbidden();
    $this->actingAs($kasir)->put(route('categories.update', $category), ['name' => 'Ilegal'])->assertForbidden();
    $this->actingAs($kasir)->delete(route('categories.destroy', $category))->assertForbidden();
});

it('tamu tidak dapat mengakses kategori', function () {
    $this->get(route('categories.index'))->assertRedirect('/login');
    $this->post(route('categories.store'), ['name' => 'Ilegal'])->assertRedirect('/login');
});
