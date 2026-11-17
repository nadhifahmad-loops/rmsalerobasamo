<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function imageProductPayload(int $categoryId): array
{
    return [
        'category_id' => $categoryId,
        'name' => 'Rendang',
        'description' => 'Rendang daging sapi',
        'unit' => 'potong',
        'is_active' => true,
        'is_available' => true,
        'variants' => [
            [
                'name' => '1 potong',
                'price' => 25000,
                'is_default' => true,
                'is_active' => true,
                'is_available' => true,
            ],
        ],
    ];
}

/** PNG 1x1 dibuat dari konten, bukan dari ekstensi nama file. */
function productImageContent(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
}

function productImageUpload(string $name = 'rendang.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, productImageContent());
}

beforeEach(function (): void {
    Storage::fake('public');
});

it('admin dapat membuat produk dengan foto dan URL gambar tampil di katalog', function () {
    $category = Category::factory()->create(['is_active' => true]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), [
            ...imageProductPayload($category->id),
            'image' => productImageUpload(),
        ])
        ->assertRedirect(route('products.index'));

    $product = Product::where('name', 'Rendang')->firstOrFail();

    expect($product->image_path)->toStartWith('products/')
        ->and($product->image_url)->toBe(Storage::disk('public')->url($product->image_path));

    Storage::disk('public')->assertExists($product->image_path);

    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('catalog/browse')
            ->where('products.data.0.image_url', $product->image_url));
});

it('produk tanpa foto memiliki image_url null dan placeholder lokal tersedia', function () {
    $category = Category::factory()->create(['is_active' => true]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), imageProductPayload($category->id))
        ->assertRedirect(route('products.index'));

    $product = Product::where('name', 'Rendang')->firstOrFail();

    expect($product->image_path)->toBeNull()
        ->and($product->image_url)->toBeNull();

    $this->assertFileExists(public_path('images/products/placeholder.svg'));

    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('products.data.0.image_url', null));
});

it('menolak foto dengan format yang tidak didukung', function () {
    $category = Category::factory()->create(['is_active' => true]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), [
            ...imageProductPayload($category->id),
            'image' => UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
        ])
        ->assertSessionHasErrors('image');

    expect(Product::where('name', 'Rendang')->exists())->toBeFalse()
        ->and(Storage::disk('public')->allFiles('products'))->toBe([]);
});

it('menolak foto yang melebihi batas 5 MB', function () {
    $category = Category::factory()->create(['is_active' => true]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.store'), [
            ...imageProductPayload($category->id),
            'image' => UploadedFile::fake()->create('rendang-besar.png', 6000, 'image/png'),
        ])
        ->assertSessionHasErrors('image');

    expect(Product::where('name', 'Rendang')->exists())->toBeFalse()
        ->and(Storage::disk('public')->allFiles('products'))->toBe([]);
});

it('mengganti foto menghapus file lama setelah penyimpanan berhasil', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $oldPath = Storage::disk('public')->putFile('products', productImageUpload('lama.png'), 'public');
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'image_path' => $oldPath,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.update', $product), [
            ...imageProductPayload($category->id),
            '_method' => 'PUT',
            'image' => productImageUpload('baru.png'),
        ])
        ->assertRedirect(route('products.edit', $product));

    $product->refresh();

    expect($product->image_path)->not->toBe($oldPath)
        ->and($product->image_path)->toStartWith('products/');

    Storage::disk('public')->assertExists($product->image_path);
    Storage::disk('public')->assertMissing($oldPath);
});

it('menghapus foto mengosongkan path dan menghapus file lama', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $oldPath = Storage::disk('public')->putFile('products', productImageUpload('lama.png'), 'public');
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'image_path' => $oldPath,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('products.update', $product), [
            ...imageProductPayload($category->id),
            '_method' => 'PUT',
            'remove_image' => true,
        ])
        ->assertRedirect(route('products.edit', $product));

    $product->refresh();

    expect($product->image_path)->toBeNull()
        ->and($product->image_url)->toBeNull();

    Storage::disk('public')->assertMissing($oldPath);
});

it('gagal upload tidak menghapus foto lama', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $realDisk = Storage::disk('public');
    $oldPath = $realDisk->putFile('products', productImageUpload('lama.png'), 'public');
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'image_path' => $oldPath,
    ]);

    $failingDisk = Mockery::mock(Filesystem::class);
    $failingDisk->shouldReceive('putFile')->once()->andThrow(new RuntimeException('Gagal menyimpan foto produk.'));
    Storage::shouldReceive('disk')->with('public')->andReturn($failingDisk);

    $this->withoutExceptionHandling();

    try {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('products.update', $product), [
                ...imageProductPayload($category->id),
                '_method' => 'PUT',
                'image' => productImageUpload('baru.png'),
            ]);
    } catch (RuntimeException) {
        // Upload gagal; transaksi tidak boleh menyentuh data lama.
    }

    $product->refresh();

    expect($product->image_path)->toBe($oldPath);
    $realDisk->assertExists($oldPath);
});

it('kasir tidak dapat mengunggah foto produk', function () {
    $category = Category::factory()->create(['is_active' => true]);

    $this->actingAs(User::factory()->withRole('kasir')->create())
        ->post(route('products.store'), [
            ...imageProductPayload($category->id),
            'image' => productImageUpload(),
        ])
        ->assertForbidden();

    expect(Product::where('name', 'Rendang')->exists())->toBeFalse();
});
