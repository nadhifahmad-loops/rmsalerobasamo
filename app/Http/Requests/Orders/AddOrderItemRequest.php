<?php

namespace App\Http\Requests\Orders;

class AddOrderItemRequest extends OrderRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => $this->quantityRules(),
            'notes' => $this->notesRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.min' => 'Jumlah minimal satu.',
            'quantity.max' => 'Jumlah melebihi batas yang diizinkan.',
        ];
    }
}
