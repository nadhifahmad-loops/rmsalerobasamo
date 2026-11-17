<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends CatalogRequest
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
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->imageMessages();
    }
}
