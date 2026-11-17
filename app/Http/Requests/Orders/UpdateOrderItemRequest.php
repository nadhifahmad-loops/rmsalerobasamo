<?php

namespace App\Http\Requests\Orders;

class UpdateOrderItemRequest extends OrderRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
