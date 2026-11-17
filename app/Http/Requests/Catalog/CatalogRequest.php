<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Semua request mutasi katalog memakai authorization yang sama dengan route.
 * Lapisan ini sengaja diduplikasi agar endpoint tetap terlindungi walaupun
 * route middleware diubah kelak.
 */
abstract class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-catalog');
    }

    /**
     * @return list<string>
     */
    protected function priceRules(): array
    {
        return [
            'required',
            'integer',
            'min:0',
            'max:'.config('pos.catalog.max_price', 1_000_000_000),
        ];
    }

    /**
     * Validasi file dilakukan dari konten, bukan dari nama/ekstensi yang dikirim browser.
     * GD/Imagick tidak tersedia, jadi optimasi dibatasi pada batas ukuran dan dimensi.
     *
     * @return list<string>
     */
    protected function imageRules(): array
    {
        return [
            'nullable',
            'file',
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'max:5120',
            'dimensions:max_width=2400,max_height=1800',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function imageMessages(): array
    {
        return [
            'image.file' => 'Foto produk harus berupa berkas.',
            'image.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'image.mimetypes' => 'Format foto harus JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran foto maksimal 5 MB.',
            'image.dimensions' => 'Dimensi foto maksimal 2400 × 1800 piksel.',
        ];
    }
}
