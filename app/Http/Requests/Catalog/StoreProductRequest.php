<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Validation\Rule;

class StoreProductRequest extends CatalogRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'unit' => ['required', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
            'is_available' => ['required', 'boolean'],
            'image' => $this->imageRules(),
            'variants' => ['required', 'array', 'min:1', 'max:20'],
            'variants.*.name' => ['required', 'string', 'max:80'],
            'variants.*.price' => $this->priceRules(),
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
            'variants.*.is_available' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->imageMessages(),
            'variants.min' => 'Produk harus memiliki minimal satu variasi.',
            'variants.*.price.min' => 'Harga tidak boleh negatif.',
            'variants.*.price.max' => 'Harga melebihi batas nominal yang diizinkan.',
        ];
    }
}
