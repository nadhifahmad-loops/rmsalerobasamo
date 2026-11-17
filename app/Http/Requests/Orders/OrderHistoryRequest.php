<?php

namespace App\Http\Requests\Orders;

use App\Models\Payment;

final class OrderHistoryRequest extends OrderRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'view' => ['sometimes', 'in:active,history'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'cashier_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'payment_method' => ['sometimes', 'nullable', 'in:'.implode(',', Payment::METHODS)],
            'order_type' => ['sometimes', 'nullable', 'in:DINE_IN,TAKEAWAY'],
            'status' => ['sometimes', 'nullable', 'in:OPEN,AWAITING_PAYMENT,PAID,VOIDED'],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:50'],
        ];
    }
}
