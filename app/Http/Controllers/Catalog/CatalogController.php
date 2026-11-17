<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CatalogController extends Controller
{
    /**
     * Katalog read-only untuk kasir. Hanya produk dan variasi yang benar-benar
     * dapat dipesan yang dikirim ke browser; harga selalu berasal dari database.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $categoryId = $request->integer('category_id') ?: null;

        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->active()
            ->available()
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($categoryId !== null, fn ($query) => $query->where('category_id', $categoryId))
            ->whereHas('variants', fn ($query) => $query->active()->available())
            ->with([
                'category:id,name',
                'variants' => fn ($query) => $query->active()
                    ->available()
                    ->orderByDesc('is_default')
                    ->orderBy('name')
                    ->orderBy('id'),
            ])
            ->orderBy('name')
            ->paginate((int) config('pos.catalog.browse_page_size', 24))
            ->withQueryString();

        return Inertia::render('catalog/browse', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
            ],
        ]);
    }
}
