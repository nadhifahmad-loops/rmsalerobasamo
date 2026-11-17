<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POS dapat dipakai kasir, admin, dan owner. Autentikasi diperiksa ulang di sini
 * agar endpoint tetap tertutup bila middleware route berubah kelak.
 */
abstract class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return list<string>
     */
    protected function quantityRules(): array
    {
        return [
            'required',
            'integer',
            'min:1',
            'max:'.config('pos.orders.max_quantity', 100),
        ];
    }

    /**
     * @return list<string>
     */
    protected function notesRules(): array
    {
        return [
            'nullable',
            'string',
            'max:'.config('pos.orders.notes_max_length', 500),
        ];
    }
}
