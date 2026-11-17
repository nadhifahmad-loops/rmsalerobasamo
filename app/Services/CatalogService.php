<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Pusat aturan bisnis katalog. Semua mutasi berjalan dalam transaction agar
 * normalisasi variasi dan audit log tidak pernah setengah tersimpan.
 */
final class CatalogService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $variants
     */
    public function createProduct(array $data, array $variants, ?UploadedFile $image = null): Product
    {
        unset($data['image'], $data['remove_image']);

        $imagePath = $image !== null ? $this->storeImage($image) : null;

        try {
            $product = DB::transaction(function () use ($data, $variants, $imagePath): Product {
                $product = Product::create([...$data, 'image_path' => $imagePath]);

                foreach ($variants as $variant) {
                    $product->variants()->create($variant);
                }

                $this->normalizeVariants($product);

                AuditLog::record('catalog.product_created', 'product', $product->id, [
                    'name' => $product->name,
                    'variant_count' => count($variants),
                    'has_image' => $imagePath !== null,
                ]);

                return $product;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredImage($imagePath);

            throw $exception;
        }

        return $product->load('category', 'variants');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProduct(Product $product, array $data, ?UploadedFile $image = null, bool $removeImage = false): Product
    {
        unset($data['image'], $data['remove_image']);

        $oldImagePath = $product->image_path;
        $newImagePath = $image !== null ? $this->storeImage($image) : null;
        $nextImagePath = $removeImage ? null : ($newImagePath ?? $oldImagePath);

        try {
            DB::transaction(function () use ($product, $data, $nextImagePath, $oldImagePath): void {
                $changedFields = [];

                foreach (['category_id', 'name', 'unit', 'description', 'is_active', 'is_available'] as $field) {
                    if (array_key_exists($field, $data) && $product->{$field} != $data[$field]) {
                        $changedFields[] = $field;
                    }
                }

                if ($nextImagePath !== $oldImagePath) {
                    $changedFields[] = 'image_path';
                }

                $product->update([...$data, 'image_path' => $nextImagePath]);

                AuditLog::record('catalog.product_updated', 'product', $product->id, [
                    'changed_fields' => $changedFields,
                ]);
            });
        } catch (Throwable $exception) {
            // Upload baru dibatalkan; gambar lama masih utuh di database dan storage.
            $this->deleteStoredImage($newImagePath);

            throw $exception;
        }

        // File lama hanya dihapus setelah transaksi basis data berhasil.
        if ($newImagePath !== null || $removeImage) {
            $this->deleteStoredImage($oldImagePath);
        }

        return $product->refresh()->load('category', 'variants');
    }

    private function storeImage(UploadedFile $image): string
    {
        $path = Storage::disk('public')->putFile('products', $image, 'public');

        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan foto produk.');
        }

        return $path;
    }

    private function deleteStoredImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (! Storage::disk('public')->delete($path)) {
            Log::error('Gagal menghapus foto produk lama.', ['path' => $path]);
        }
    }

    public function deleteProduct(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->delete();

            AuditLog::record('catalog.product_deleted', 'product', $product->id, [
                'name' => $product->name,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveVariant(Product $product, array $data, ?ProductVariant $variant = null): ProductVariant
    {
        abort_unless($variant === null || $variant->product_id === $product->id, 404);

        $saved = DB::transaction(function () use ($product, $data, $variant): ProductVariant {
            if ($variant === null) {
                $variant = $product->variants()->create($data);

                AuditLog::record('catalog.variant_created', 'product_variant', $variant->id, [
                    'product_id' => $product->id,
                    'name' => $variant->name,
                    'price' => $variant->price,
                ]);
            } else {
                $before = [
                    'name' => $variant->name,
                    'price' => $variant->price,
                    'is_active' => $variant->is_active,
                    'is_available' => $variant->is_available,
                    'is_default' => $variant->is_default,
                ];

                $variant->update($data);

                AuditLog::record('catalog.variant_updated', 'product_variant', $variant->id, [
                    'product_id' => $product->id,
                    'before' => $before,
                    'after' => [
                        'name' => $variant->name,
                        'price' => $variant->price,
                        'is_active' => $variant->is_active,
                        'is_available' => $variant->is_available,
                        'is_default' => $variant->is_default,
                    ],
                ]);
            }

            // Bisa melempar ValidationException; transaction akan rollback
            // perubahan yang membuat produk kehilangan semua variasi aktif.
            $this->normalizeVariants($product);

            return $variant;
        });

        return $saved->refresh();
    }

    public function deleteVariant(ProductVariant $variant): void
    {
        DB::transaction(function () use ($variant): void {
            $product = $variant->product;

            $variant->delete();
            $this->normalizeVariants($product);

            AuditLog::record('catalog.variant_deleted', 'product_variant', $variant->id, [
                'product_id' => $product->id,
                'name' => $variant->name,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): Category
    {
        $category = DB::transaction(function () use ($data): Category {
            $category = Category::create($data);

            AuditLog::record('catalog.category_created', 'category', $category->id, [
                'name' => $category->name,
            ]);

            return $category;
        });

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(Category $category, array $data): Category
    {
        DB::transaction(function () use ($category, $data): void {
            $changedFields = [];

            foreach (['name', 'is_active', 'sort_order'] as $field) {
                if (array_key_exists($field, $data) && $category->{$field} != $data[$field]) {
                    $changedFields[] = $field;
                }
            }

            $category->update($data);

            AuditLog::record('catalog.category_updated', 'category', $category->id, [
                'changed_fields' => $changedFields,
            ]);
        });

        return $category->refresh();
    }

    public function deleteCategory(Category $category): void
    {
        DB::transaction(function () use ($category): void {
            if ($category->products()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Kategori masih digunakan oleh produk dan tidak dapat dihapus.',
                ]);
            }

            AuditLog::record('catalog.category_deleted', 'category', $category->id, [
                'name' => $category->name,
            ]);

            $category->delete();
        });
    }

    /**
     * BUSINESS-RULES §3: satu produk minimal memiliki satu variasi aktif
     * dan tepat satu variasi default di antara variasi aktif tersebut.
     */
    private function normalizeVariants(Product $product): void
    {
        $activeVariants = $product->variants()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($activeVariants->isEmpty()) {
            throw ValidationException::withMessages([
                'variants' => 'Produk harus memiliki minimal satu variasi aktif.',
            ]);
        }

        $default = $activeVariants->firstWhere('is_default', true) ?? $activeVariants->first();

        $product->variants()
            ->where('is_default', true)
            ->whereKeyNot($default->id)
            ->update(['is_default' => false]);

        if (! $default->is_default) {
            $default->update(['is_default' => true]);
        }
    }
}
