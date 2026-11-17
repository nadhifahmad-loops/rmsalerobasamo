<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Http\Requests\Catalog\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $categoryId = $request->integer('category_id') ?: null;
        $status = $request->string('status')->toString();

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($categoryId !== null, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status === 'active', fn ($query) => $query->active()->available())
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($status === 'unavailable', fn ($query) => $query->where('is_available', false))
            ->with([
                'category:id,name',
                'variants' => fn ($query) => $query->orderByDesc('is_default')->orderBy('id'),
            ])
            ->withCount('variants')
            ->orderBy('name')
            ->paginate((int) config('pos.catalog.admin_page_size', 15))
            ->withQueryString();

        return Inertia::render('catalog/products', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('catalog/products/create', [
            'categories' => Category::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $variants = $data['variants'];
        $image = $data['image'] ?? null;
        unset($data['variants'], $data['image']);

        $this->catalog->createProduct($data, $variants, $image);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('catalog/products/edit', [
            'product' => $product->load('category', 'variants'),
            'categories' => Category::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $image = $data['image'] ?? null;
        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image']);

        $this->catalog->updateProduct($product, $data, $image, $removeImage);

        return redirect()->route('products.edit', $product)->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->catalog->deleteProduct($product);

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
