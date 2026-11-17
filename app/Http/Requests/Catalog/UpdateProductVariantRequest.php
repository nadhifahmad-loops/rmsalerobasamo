<?php

namespace App\Http\Requests\Catalog;

class UpdateProductVariantRequest extends CatalogRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'price' => $this->priceRules(),
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price.min' => 'Harga tidak boleh negatif.',
            'price.max' => 'Harga melebihi batas nominal yang diizinkan.',
        ];
    }
}
