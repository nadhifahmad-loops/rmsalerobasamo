<?php

namespace App\Http\Requests\Orders;

class StoreOrderRequest extends OrderRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dining_table_id' => ['required', 'integer', 'exists:dining_tables,id'],
            'notes' => $this->notesRules(),
        ];
    }
}
