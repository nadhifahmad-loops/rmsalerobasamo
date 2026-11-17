<?php

namespace App\Http\Requests\Orders;

class UpdateOrderNotesRequest extends OrderRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => $this->notesRules(),
        ];
    }
}
