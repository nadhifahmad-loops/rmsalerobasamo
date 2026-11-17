<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductVariantRequest;
use App\Http\Requests\Catalog\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;

final class ProductVariantController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $this->catalog->saveVariant($product, $request->validated());

        return redirect()->route('products.edit', $product)->with('success', 'Variasi berhasil ditambahkan.');
    }

    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $this->catalog->saveVariant($product, $request->validated(), $variant);

        return redirect()->route('products.edit', $product)->with('success', 'Variasi berhasil diperbarui.');
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $this->catalog->deleteVariant($variant);

        return redirect()->route('products.edit', $product)->with('success', 'Variasi berhasil dihapus.');
    }
}
