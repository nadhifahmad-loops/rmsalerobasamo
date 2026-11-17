<?php

namespace App\Actions\Catalog;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

/**
 * Dipanggil saat item akan masuk ke order (Phase 3). Mengambil harga dan
 * identitas dari database sehingga nilai snapshot tidak pernah berasal dari
 * browser dan tidak berubah saat master harga diperbarui kemudian.
 */
final class ResolveOrderItemSnapshot
{
    /**
     * @return array{
     *     product_id: int,
     *     product_variant_id: int,
     *     product_name_snapshot: string,
     *     variant_name_snapshot: string,
     *     unit_snapshot: string,
     *     price_snapshot: int
     * }
     */
    public function handle(int $productId, int $variantId): array
    {
        $product = Product::query()
            ->active()
            ->available()
            ->find($productId);

        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk tidak tersedia untuk dipesan.',
            ]);
        }

        $variant = $product->variants()
            ->active()
            ->available()
            ->find($variantId);

        if (! $variant) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Variasi tidak tersedia untuk dipesan.',
            ]);
        }

        return [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'unit_snapshot' => $product->unit,
            'price_snapshot' => $variant->price,
        ];
    }
}
