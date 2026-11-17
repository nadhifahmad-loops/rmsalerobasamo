<?php

namespace App\Actions\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Katalog POS dipakai bersama oleh order dine-in dan takeaway. Filter tetap
 * server-side agar daftar menu tidak pernah memuat seluruh katalog.
 */
final class BrowsePosCatalog
{
    /**
     * @return array{
     *     products: LengthAwarePaginator<Product>,
     *     categories: Collection<int, Category>,
     *     filters: array{search: string, category_id: int|null}
     * }
     */
    public function handle(string $search, ?int $categoryId, int $pageSize): array
    {
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
            ->with([
                'category:id,name',
                'variants' => fn ($query) => $query
                    ->active()
                    ->available()
                    ->orderByDesc('is_default')
                    ->orderBy('id'),
            ])
            ->orderBy('name')
            ->paginate($pageSize)
            ->withQueryString();

        return [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
            ],
        ];
    }
}
