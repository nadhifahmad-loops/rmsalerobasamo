<?php

namespace App\Http\Requests\Takeaway;

use App\Http\Requests\Orders\OrderRequest;

final class StoreTakeawayOrderRequest extends OrderRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxItems = (int) config('pos.takeaway.max_items', 50);

        return [
            'notes' => $this->notesRules(),
            'items' => ['required', 'array', 'min:1', 'max:'.$maxItems],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => $this->quantityRules(),
            'items.*.notes' => $this->notesRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.min' => 'Pilih minimal satu menu sebelum checkout.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak ditemukan.',
            'items.*.product_variant_id.exists' => 'Variasi yang dipilih tidak ditemukan.',
        ];
    }
}
